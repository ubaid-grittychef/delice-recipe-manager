/**
 * Delice Recipe Manager — Shopping List (v4.0.0)
 *
 * Client-side only. Persists across page loads via localStorage.
 * No AJAX, no server-side component.
 */
( function () {
    'use strict';

    window.Delice = window.Delice || {};
    if ( window.Delice.shoppingListLoaded ) { return; }
    window.Delice.shoppingListLoaded = true;

    var LS_KEY = 'delice_shopping_list';

    // i18n helper — reads from wp_localize_script data with English fallbacks
    var _s = (typeof deliceShoppingListData !== 'undefined' && deliceShoppingListData.strings) ? deliceShoppingListData.strings : {};
    function t( key, fallback ) { return _s[ key ] || fallback; }

    // ── localStorage helpers ─────────────────────────────────────────────────

    function getList() {
        try {
            var raw = localStorage.getItem( LS_KEY );
            return raw ? JSON.parse( raw ) : {};
        } catch ( e ) {
            return {};
        }
    }

    function saveList( list ) {
        try {
            localStorage.setItem( LS_KEY, JSON.stringify( list ) );
        } catch ( e ) {}
    }

    // ── HTML escaping ────────────────────────────────────────────────────────

    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#39;' );
    }

    // ── Count helpers ────────────────────────────────────────────────────────

    function countItems( list ) {
        var n = 0;
        Object.keys( list ).forEach( function ( key ) {
            n += ( list[ key ].ingredients || [] ).length;
        } );
        return n;
    }

    function countUnchecked( list ) {
        var n = 0;
        Object.keys( list ).forEach( function ( key ) {
            ( list[ key ].ingredients || [] ).forEach( function ( ing ) {
                if ( ! ing.checked ) { n++; }
            } );
        } );
        return n;
    }

    // ── Badge ────────────────────────────────────────────────────────────────

    function updateBadge() {
        var list  = getList();
        var count = countUnchecked( list );
        var badge = document.getElementById( 'delice-sl-badge' );
        if ( ! badge ) { return; }
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }

    // ── Panel render ─────────────────────────────────────────────────────────

    function renderPanel() {
        var list  = getList();
        var keys  = Object.keys( list );
        var body  = document.getElementById( 'delice-sl-body' );
        if ( ! body ) { return; }

        if ( keys.length === 0 ) {
            body.innerHTML = '<p class="delice-sl-empty">' + escHtml( t( 'empty', 'Your shopping list is empty.' ) ) + '<br>' + escHtml( t( 'emptyHint', 'Add ingredients from any recipe card.' ) ) + '</p>';
            updateBadge();
            return;
        }

        var html = '';
        keys.forEach( function ( recipeId ) {
            var recipe = list[ recipeId ];
            html += '<div class="delice-sl-recipe-group">';
            html += '<div class="delice-sl-recipe-title">' + escHtml( recipe.title || 'Recipe' ) + '</div>';
            html += '<ul class="delice-sl-items">';
            ( recipe.ingredients || [] ).forEach( function ( ing, idx ) {
                var checked = ing.checked ? ' delice-sl-checked' : '';
                var chkd    = ing.checked ? ' checked' : '';
                var qty     = '';
                if ( ing.amount ) { qty += ing.amount + ' '; }
                if ( ing.unit )   { qty += ing.unit + ' '; }
                html += '<li class="delice-sl-item' + checked + '" data-recipe-id="' + escHtml( recipeId ) + '" data-index="' + idx + '">';
                html += '<label class="delice-sl-label">';
                html += '<input type="checkbox" class="delice-sl-checkbox"' + chkd + '>';
                html += '<span class="delice-sl-text">';
                if ( qty ) { html += '<span class="delice-sl-qty">' + escHtml( qty.trim() ) + '</span> '; }
                html += escHtml( ing.name );
                html += '</span>';
                html += '</label>';
                html += '</li>';
            } );
            html += '</ul>';
            html += '<button class="delice-sl-remove-recipe" data-recipe-id="' + escHtml( recipeId ) + '">' + escHtml( t( 'removeRecipe', 'Remove recipe' ) ) + '</button>';
            html += '</div>';
        } );

        body.innerHTML = html;
        updateBadge();
    }

    // ── Toggle panel ─────────────────────────────────────────────────────────

    function togglePanel() {
        var panel = document.getElementById( 'delice-shopping-panel' );
        if ( ! panel ) { return; }
        var isOpen = panel.classList.contains( 'delice-sl-open' );
        if ( isOpen ) {
            panel.classList.remove( 'delice-sl-open' );
            panel.setAttribute( 'aria-hidden', 'true' );
        } else {
            renderPanel();
            panel.classList.add( 'delice-sl-open' );
            panel.setAttribute( 'aria-hidden', 'false' );
        }
    }

    // ── Add recipe ingredients ───────────────────────────────────────────────

    function addRecipeToList( recipeId, title, ingredients ) {
        var list = getList();
        list[ recipeId ] = {
            title:       title,
            added:       Math.floor( Date.now() / 1000 ),
            ingredients: ingredients.map( function ( ing ) {
                return { name: ing.name, amount: ing.amount || '', unit: ing.unit || '', checked: false };
            } ),
        };
        saveList( list );
    }

    function removeRecipeFromList( recipeId ) {
        var list = getList();
        delete list[ recipeId ];
        saveList( list );
        renderPanel();
    }

    // ── Check/uncheck item ───────────────────────────────────────────────────

    function toggleItem( recipeId, idx, checked ) {
        var list = getList();
        if ( list[ recipeId ] && list[ recipeId ].ingredients[ idx ] ) {
            list[ recipeId ].ingredients[ idx ].checked = checked;
            saveList( list );
        }
        updateBadge();
    }

    // ── Copy to clipboard ────────────────────────────────────────────────────

    function copyList() {
        var list  = getList();
        var keys  = Object.keys( list );
        if ( keys.length === 0 ) { return; }
        var lines = [];
        keys.forEach( function ( recipeId ) {
            var recipe = list[ recipeId ];
            lines.push( '== ' + ( recipe.title || t( 'recipe', 'Recipe' ) ) + ' ==' );
            ( recipe.ingredients || [] ).forEach( function ( ing ) {
                var line = '';
                if ( ing.amount ) { line += ing.amount + ' '; }
                if ( ing.unit )   { line += ing.unit + ' '; }
                line += ing.name;
                lines.push( ( ing.checked ? '[x] ' : '[ ] ' ) + line );
            } );
            lines.push( '' );
        } );
        var text = lines.join( '\n' );
        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( text ).then( function () {
                showToast( t( 'copied', 'Copied to clipboard!' ) );
            } ).catch( function () {
                showToast( t( 'copyFailed', 'Could not copy.' ) );
            } );
        } else {
            showToast( t( 'clipboardNA', 'Clipboard not available.' ) );
        }
    }

    // ── Print ────────────────────────────────────────────────────────────────

    function printList() {
        document.body.classList.add( 'delice-sl-printing' );
        window.print();
        document.body.classList.remove( 'delice-sl-printing' );
    }

    // ── Clear all ────────────────────────────────────────────────────────────

    function clearAll() {
        if ( ! window.confirm( t( 'confirmClear', 'Clear the entire shopping list?' ) ) ) { return; }
        saveList( {} );
        renderPanel();
    }

    // ── Toast notification ───────────────────────────────────────────────────

    function showToast( msg ) {
        var toast = document.createElement( 'div' );
        toast.className = 'delice-sl-toast';
        toast.textContent = msg;
        document.body.appendChild( toast );
        setTimeout( function () { toast.classList.add( 'delice-sl-toast-show' ); }, 10 );
        setTimeout( function () {
            toast.classList.remove( 'delice-sl-toast-show' );
            setTimeout( function () { toast.parentNode && toast.parentNode.removeChild( toast ); }, 300 );
        }, 2500 );
    }

    // ── Inject panel & trigger into DOM ─────────────────────────────────────

    function injectPanel() {
        if ( document.getElementById( 'delice-shopping-panel' ) ) { return; }

        var panel = document.createElement( 'div' );
        panel.id = 'delice-shopping-panel';
        panel.className = 'delice-shopping-panel';
        panel.setAttribute( 'aria-hidden', 'true' );
        panel.setAttribute( 'aria-label', 'Shopping List' );
        panel.innerHTML = [
            '<div class="delice-sl-header">',
            '  <div class="delice-sl-header-title">',
            '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true">',
            '      <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>',
            '      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            '    </svg>',
            '    ' + escHtml( t( 'panelTitle', 'Shopping List' ) ),
            '  </div>',
            '  <button class="delice-sl-close-btn" id="delice-sl-close" aria-label="Close shopping list">&times;</button>',
            '</div>',
            '<div class="delice-sl-body" id="delice-sl-body"></div>',
            '<div class="delice-sl-footer">',
            '  <button class="delice-sl-footer-btn" id="delice-sl-copy">' + escHtml( t( 'copyList', 'Copy List' ) ) + '</button>',
            '  <button class="delice-sl-footer-btn" id="delice-sl-print">' + escHtml( t( 'print', 'Print' ) ) + '</button>',
            '  <button class="delice-sl-footer-btn delice-sl-footer-btn--danger" id="delice-sl-clear">' + escHtml( t( 'clearAll', 'Clear All' ) ) + '</button>',
            '</div>',
        ].join( '' );
        document.body.appendChild( panel );

        var trigger = document.createElement( 'div' );
        trigger.id = 'delice-shopping-trigger';
        trigger.className = 'delice-shopping-trigger';
        trigger.setAttribute( 'role', 'button' );
        trigger.setAttribute( 'tabindex', '0' );
        trigger.setAttribute( 'aria-label', 'Open shopping list' );
        trigger.innerHTML = [
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22" aria-hidden="true">',
            '  <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>',
            '  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            '</svg>',
            '<span class="delice-sl-badge" id="delice-sl-badge" aria-live="polite">0</span>',
        ].join( '' );
        document.body.appendChild( trigger );

        // Wire events
        trigger.addEventListener( 'click', togglePanel );
        trigger.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); togglePanel(); }
        } );

        document.getElementById( 'delice-sl-close' ).addEventListener( 'click', togglePanel );

        document.getElementById( 'delice-sl-copy' ).addEventListener( 'click', copyList );
        document.getElementById( 'delice-sl-print' ).addEventListener( 'click', printList );
        document.getElementById( 'delice-sl-clear' ).addEventListener( 'click', clearAll );

        panel.addEventListener( 'click', function ( e ) {
            // Checkbox toggle
            var checkbox = e.target.closest( '.delice-sl-checkbox' );
            if ( checkbox ) {
                var item = checkbox.closest( '.delice-sl-item' );
                if ( item ) {
                    var rId = item.getAttribute( 'data-recipe-id' );
                    var idx = parseInt( item.getAttribute( 'data-index' ), 10 );
                    toggleItem( rId, idx, checkbox.checked );
                    if ( checkbox.checked ) {
                        item.classList.add( 'delice-sl-checked' );
                    } else {
                        item.classList.remove( 'delice-sl-checked' );
                    }
                }
                return;
            }
            // Remove recipe
            var removeBtn = e.target.closest( '.delice-sl-remove-recipe' );
            if ( removeBtn ) {
                removeRecipeFromList( removeBtn.getAttribute( 'data-recipe-id' ) );
            }
        } );

        // ── Swipe-right-to-dismiss on mobile ──────────────────────────────────
        ( function () {
            var startX = 0, startY = 0;
            panel.addEventListener( 'touchstart', function ( e ) {
                var touch = e.touches[ 0 ];
                startX = touch.clientX;
                startY = touch.clientY;
            }, { passive: true } );

            panel.addEventListener( 'touchend', function ( e ) {
                var touch = e.changedTouches[ 0 ];
                var dx    = touch.clientX - startX;
                var dy    = touch.clientY - startY;
                // Swipe right at least 100 px and more horizontal than vertical
                if ( dx > 100 && Math.abs( dy ) < Math.abs( dx ) ) {
                    if ( panel.classList.contains( 'delice-sl-open' ) ) {
                        togglePanel();
                    }
                }
            }, { passive: true } );
        } )();

        updateBadge();
    }

    // ── "Add to Shopping List" button click ──────────────────────────────────

    document.addEventListener( 'click', function ( e ) {
        var btn = e.target.closest( '.delice-add-to-list-btn' );
        if ( ! btn ) { return; }

        var recipeId    = btn.getAttribute( 'data-recipe-id' );
        var recipeTitle = btn.getAttribute( 'data-recipe-title' ) || 'Recipe';

        // Find the recipe card container (walks up from the button)
        var card = btn.closest( '[id^="drd-"], [id^="drm-"], [id^="dre-"]' );
        var ingredients = [];

        if ( card ) {
            var items = card.querySelectorAll( '.delice-recipe-ingredient' );
            items.forEach( function ( li ) {
                var name   = li.getAttribute( 'data-name' )   || '';
                var amount = li.getAttribute( 'data-amount' ) || '';
                var unit   = li.getAttribute( 'data-unit' )   || '';
                if ( name ) {
                    ingredients.push( { name: name, amount: amount, unit: unit } );
                }
            } );
        }

        if ( ingredients.length === 0 ) {
            showToast( t( 'noIngredients', 'No ingredients found in this recipe.' ) );
            return;
        }

        addRecipeToList( recipeId, recipeTitle, ingredients );
        updateBadge();
        showToast( t( 'ingredientAdded', '%d ingredient(s) added to Shopping List' ).replace( '%d', ingredients.length ) );
    } );

    // ── Init ─────────────────────────────────────────────────────────────────

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', injectPanel );
    } else {
        injectPanel();
    }

}() );
