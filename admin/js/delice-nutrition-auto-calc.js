/**
 * Delice Recipe Manager — Auto Nutrition Calculation (v4.0.0)
 *
 * Sends ingredient data to the Edamam Nutrition API (server-side proxy)
 * and populates the nutrition meta box fields with the result.
 */
( function ( $ ) {
    'use strict';

    if ( window.deliceNutritionCalcLoaded ) { return; }
    window.deliceNutritionCalcLoaded = true;

    var DATA    = window.deliceNutritionData || {};
    var ajaxUrl = DATA.ajaxurl || '';
    var nonce   = DATA.nonce   || '';
    var strings = DATA.strings || {};

    /**
     * Collect ingredient strings from the admin meta box ingredient rows.
     * Looks for rows with name + optionally amount and unit inputs.
     * Falls back to reading each row's text if structured inputs aren't found.
     *
     * @return string[]  e.g. ["2 cups flour", "3 eggs"]
     */
    function collectIngredients() {
        var ingredients = [];

        // Each ingredient row added by delice-recipe-admin.js
        $( '.delice-ingredient-row, .ingredient-row' ).each( function () {
            var $row   = $( this );
            var name   = $.trim( $row.find( '[name*="[name]"], .ingredient-name-input, input[placeholder="Ingredient"]' ).val() || '' );
            var amount = $.trim( $row.find( '[name*="[amount]"], .ingredient-amount-input, input[placeholder="Amount"]' ).val() || '' );
            var unit   = $.trim( $row.find( '[name*="[unit]"], .ingredient-unit-input, input[placeholder="Unit"]' ).val() || '' );
            if ( name ) {
                var str = '';
                if ( amount ) { str += amount + ' '; }
                if ( unit   ) { str += unit + ' '; }
                str += name;
                ingredients.push( $.trim( str ) );
            }
        } );

        return ingredients;
    }

    /**
     * Write a value into a nutrition input field.
     *
     * @param {string} fieldId   e.g. "nutrition_calories"
     * @param {number} value
     */
    function fillField( fieldId, value ) {
        var $input = $( '#' + fieldId );
        if ( $input.length ) {
            $input.val( value ).trigger( 'change' );
        }
    }

    /**
     * Populate all nutrition fields from the API response.
     *
     * @param {Object} data  { calories, carbs, protein, fat, saturated_fat, sugar, fiber, sodium }
     */
    function populateNutritionFields( data ) {
        fillField( 'nutrition_calories',     data.calories      || '' );
        fillField( 'nutrition_carbs',        data.carbs         || '' );
        fillField( 'nutrition_protein',      data.protein       || '' );
        fillField( 'nutrition_fat',          data.fat           || '' );
        fillField( 'nutrition_saturated_fat', data.saturated_fat || '' );
        fillField( 'nutrition_sugar',        data.sugar         || '' );
        fillField( 'nutrition_fiber',        data.fiber         || '' );
        fillField( 'nutrition_sodium',       data.sodium        || '' );
    }

    // ── Click handler ─────────────────────────────────────────────────────────

    $( document ).on( 'click', '#delice-auto-calc-btn', function () {
        var $btn    = $( this );
        var $status = $( '#delice-auto-calc-status' );

        var ingredients = collectIngredients();
        if ( ingredients.length === 0 ) {
            $status.css( 'color', '#b91c1c' ).text( strings.no_ingredients || 'No ingredients found.' );
            return;
        }

        var servings = parseInt( $( '#delice_recipe_servings, [name="delice_recipe_servings"], input[id*="servings"]' ).first().val(), 10 ) || 1;

        $btn.prop( 'disabled', true );
        $status.css( 'color', '#6b7280' ).text( strings.calculating || 'Calculating…' );

        $.ajax( {
            url:  ajaxUrl,
            type: 'POST',
            data: {
                action:      'delice_auto_calculate_nutrition',
                nonce:       nonce,
                ingredients: ingredients,
                servings:    servings,
            },
            success: function ( response ) {
                if ( response.success ) {
                    populateNutritionFields( response.data );
                    $status.css( 'color', '#15803d' ).text( strings.success || 'Nutrition filled in!' );
                } else {
                    var msg = ( response.data && response.data.message ) ? response.data.message : ( strings.error || 'Calculation failed.' );
                    $status.css( 'color', '#b91c1c' ).text( msg );
                }
            },
            error: function () {
                $status.css( 'color', '#b91c1c' ).text( strings.error || 'Calculation failed. Check your Edamam API keys.' );
            },
            complete: function () {
                $btn.prop( 'disabled', false );
            },
        } );
    } );

}( window.jQuery ) );
