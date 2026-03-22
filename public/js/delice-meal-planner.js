/**
 * Delice Recipe Manager — Meal Planner Calendar (v4.1.0)
 *
 * Weekly calendar view with AJAX recipe search + add/remove.
 * Requires jQuery and deliceMealPlannerData (via wp_localize_script).
 */
( function ( $ ) {
    'use strict';

    window.Delice = window.Delice || {};
    if ( window.Delice.mealPlannerLoaded ) { return; }
    window.Delice.mealPlannerLoaded = true;

    var DATA    = window.deliceMealPlannerData || {};
    var ajaxUrl = DATA.ajaxurl || '';
    var nonce   = DATA.nonce   || '';
    var S       = DATA.strings || {};

    var $root   = $( '#delice-meal-planner' );
    if ( ! $root.length ) { return; }

    var weekOffset  = 0;
    var meals       = [];
    var addingDate  = '';
    var searchTimer = null;

    var DAYS = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];
    var SLOTS = [ 'breakfast', 'lunch', 'dinner', 'snack' ];

    // ── Date helpers ───────────────────────────────────────────────────────

    function getWeekStart( offset ) {
        var d = new Date();
        d.setDate( d.getDate() - d.getDay() + ( offset * 7 ) );
        d.setHours( 0, 0, 0, 0 );
        return d;
    }

    function formatDate( d ) {
        var y = d.getFullYear();
        var m = String( d.getMonth() + 1 ).padStart( 2, '0' );
        var dd = String( d.getDate() ).padStart( 2, '0' );
        return y + '-' + m + '-' + dd;
    }

    function isToday( dateStr ) {
        return dateStr === formatDate( new Date() );
    }

    // ── Render ─────────────────────────────────────────────────────────────

    function render() {
        var start = getWeekStart( weekOffset );
        var days  = [];
        for ( var i = 0; i < 7; i++ ) {
            var d = new Date( start );
            d.setDate( d.getDate() + i );
            days.push( d );
        }

        var weekLabel = ( S.week || 'Week of' ) + ' ' + start.toLocaleDateString( undefined, { month: 'short', day: 'numeric' } );

        var html = '<div class="delice-mp-nav">' +
            '<button class="delice-mp-nav-btn" data-dir="-1" type="button">&larr;</button>' +
            '<span class="delice-mp-week-label">' + weekLabel + '</span>' +
            '<button class="delice-mp-nav-btn delice-mp-today-btn" type="button">' + ( S.today || 'Today' ) + '</button>' +
            '<button class="delice-mp-nav-btn" data-dir="1" type="button">&rarr;</button>' +
            '</div>';

        html += '<div class="delice-mp-grid">';
        days.forEach( function ( day ) {
            var ds   = formatDate( day );
            var cls  = isToday( ds ) ? ' delice-mp-today' : '';
            var dayMeals = meals.filter( function ( m ) { return m.date === ds; } );

            html += '<div class="delice-mp-day' + cls + '" data-date="' + ds + '">';
            html += '<div class="delice-mp-day-header">' + DAYS[ day.getDay() ] + '</div>';
            html += '<div class="delice-mp-day-date">' + day.getDate() + '</div>';

            dayMeals.forEach( function ( m ) {
                var thumb = m.thumbnail ? '<img class="delice-mp-meal-thumb" src="' + m.thumbnail + '" alt="">' : '';
                html += '<div class="delice-mp-meal">' +
                    thumb +
                    '<div class="delice-mp-meal-info">' +
                    '<a class="delice-mp-meal-title" href="' + ( m.viewUrl || '#' ) + '">' + escHtml( m.title ) + '</a>' +
                    '<div class="delice-mp-meal-slot">' + ( S[ m.slot ] || m.slot ) + '</div>' +
                    '</div>' +
                    '<button class="delice-mp-meal-remove" data-id="' + m.id + '" type="button" aria-label="' + ( S.remove || 'Remove' ) + '">&times;</button>' +
                    '</div>';
            } );

            html += '<button class="delice-mp-add-btn" data-date="' + ds + '" type="button">+ ' + ( S.addMeal || 'Add Recipe' ) + '</button>';
            html += '</div>';
        } );
        html += '</div>';

        // Modal
        html += '<div class="delice-mp-modal-overlay">' +
            '<div class="delice-mp-modal" role="dialog" aria-modal="true">' +
            '<h3>' + ( S.addMeal || 'Add Recipe' ) + '</h3>' +
            '<select class="delice-mp-slot-select">';
        SLOTS.forEach( function ( s ) {
            html += '<option value="' + s + '">' + ( S[ s ] || s ) + '</option>';
        } );
        html += '</select>' +
            '<input class="delice-mp-search-input" type="text" placeholder="' + ( S.search || 'Search recipes...' ) + '">' +
            '<ul class="delice-mp-search-results"></ul>' +
            '</div></div>';

        $root.html( html );
    }

    function escHtml( str ) {
        return $( '<span>' ).text( str ).html();
    }

    // ── Load meals ─────────────────────────────────────────────────────────

    function loadMeals() {
        var start = getWeekStart( weekOffset );
        var end   = new Date( start );
        end.setDate( end.getDate() + 6 );

        $.post( ajaxUrl, {
            action: 'delice_meal_plan_list',
            nonce:  nonce,
            start:  formatDate( start ),
            end:    formatDate( end )
        }, function ( res ) {
            meals = res.success ? res.data : [];
            render();
        } );
    }

    // ── Events ─────────────────────────────────────────────────────────────

    $root.on( 'click', '.delice-mp-nav-btn[data-dir]', function () {
        weekOffset += parseInt( $( this ).data( 'dir' ), 10 );
        loadMeals();
    } );

    $root.on( 'click', '.delice-mp-today-btn', function () {
        weekOffset = 0;
        loadMeals();
    } );

    $root.on( 'click', '.delice-mp-add-btn', function () {
        addingDate = $( this ).data( 'date' );
        $root.find( '.delice-mp-modal-overlay' ).addClass( 'delice-mp-open' );
        $root.find( '.delice-mp-search-input' ).val( '' ).focus();
        $root.find( '.delice-mp-search-results' ).empty();
    } );

    $root.on( 'click', '.delice-mp-modal-overlay', function ( e ) {
        if ( $( e.target ).hasClass( 'delice-mp-modal-overlay' ) ) {
            $( this ).removeClass( 'delice-mp-open' );
        }
    } );

    $root.on( 'input', '.delice-mp-search-input', function () {
        var q = $( this ).val().trim();
        clearTimeout( searchTimer );
        if ( q.length < 2 ) { $root.find( '.delice-mp-search-results' ).empty(); return; }

        searchTimer = setTimeout( function () {
            $.post( ajaxUrl, {
                action: 'delice_meal_plan_search',
                nonce:  nonce,
                query:  q
            }, function ( res ) {
                if ( ! res.success ) { return; }
                var $list = $root.find( '.delice-mp-search-results' ).empty();
                res.data.forEach( function ( r ) {
                    var thumb = r.thumbnail ? '<img class="delice-mp-search-thumb" src="' + r.thumbnail + '" alt="">' : '';
                    $list.append(
                        '<li class="delice-mp-search-item" data-recipe="' + r.id + '">' +
                        thumb +
                        '<span class="delice-mp-search-title">' + escHtml( r.title ) + '</span>' +
                        '</li>'
                    );
                } );
            } );
        }, 300 );
    } );

    $root.on( 'click', '.delice-mp-search-item', function () {
        var recipeId = $( this ).data( 'recipe' );
        var slot     = $root.find( '.delice-mp-slot-select' ).val();

        $.post( ajaxUrl, {
            action:    'delice_meal_plan_add',
            nonce:     nonce,
            recipe_id: recipeId,
            date:      addingDate,
            slot:      slot
        }, function ( res ) {
            if ( res.success ) {
                $root.find( '.delice-mp-modal-overlay' ).removeClass( 'delice-mp-open' );
                loadMeals();
            }
        } );
    } );

    $root.on( 'click', '.delice-mp-meal-remove', function ( e ) {
        e.preventDefault();
        e.stopPropagation();
        var mealId = $( this ).data( 'id' );

        $.post( ajaxUrl, {
            action:  'delice_meal_plan_remove',
            nonce:   nonce,
            meal_id: mealId
        }, function ( res ) {
            if ( res.success ) { loadMeals(); }
        } );
    } );

    // ── Init ───────────────────────────────────────────────────────────────

    $( document ).ready( function () {
        loadMeals();
    } );

} )( jQuery );
