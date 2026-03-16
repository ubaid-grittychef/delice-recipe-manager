/**
 * Delice Recipe Manager — User Favorites (v4.0.0)
 *
 * - Anonymous users: state stored in localStorage key "delice_favorites"
 * - Logged-in users: state synced to user meta via AJAX
 */
( function ( $ ) {
    'use strict';

    if ( window.deliceFavoritesLoaded ) { return; }
    window.deliceFavoritesLoaded = true;

    var DATA   = window.deliceFavoritesData || {};
    var ajaxUrl = DATA.ajaxurl || '';
    var nonce   = DATA.nonce   || '';
    var userId  = parseInt( DATA.currentUserId, 10 ) || 0;

    var LS_KEY  = 'delice_favorites';

    // ── localStorage helpers ─────────────────────────────────────────────────

    function getLocal() {
        try {
            var raw = localStorage.getItem( LS_KEY );
            return raw ? JSON.parse( raw ) : [];
        } catch ( e ) {
            return [];
        }
    }

    function setLocal( ids ) {
        try {
            localStorage.setItem( LS_KEY, JSON.stringify( ids ) );
        } catch ( e ) {}
    }

    function isFavorited( recipeId ) {
        return getLocal().indexOf( recipeId ) !== -1;
    }

    function addLocal( recipeId ) {
        var ids = getLocal();
        if ( ids.indexOf( recipeId ) === -1 ) { ids.push( recipeId ); }
        setLocal( ids );
    }

    function removeLocal( recipeId ) {
        setLocal( getLocal().filter( function ( id ) { return id !== recipeId; } ) );
    }

    // ── DOM helpers ──────────────────────────────────────────────────────────

    function renderButtons() {
        $( '.delice-favorite-btn' ).each( function () {
            var $btn = $( this );
            var id   = parseInt( $btn.data( 'recipe-id' ), 10 );
            if ( isFavorited( id ) ) {
                $btn.addClass( 'is-favorited' );
            } else {
                $btn.removeClass( 'is-favorited' );
            }
        } );
    }

    // ── Server sync (logged-in users) ────────────────────────────────────────

    function syncFromServer() {
        if ( ! userId || ! ajaxUrl ) { return; }
        $.ajax( {
            url:  ajaxUrl,
            type: 'POST',
            data: { action: 'delice_get_favorites', nonce: nonce },
            success: function ( response ) {
                if ( response.success && Array.isArray( response.data.favorites ) ) {
                    // Merge server favorites into localStorage
                    var local  = getLocal();
                    var server = response.data.favorites.map( function ( id ) { return parseInt( id, 10 ); } );
                    var merged = server.slice();
                    local.forEach( function ( id ) {
                        if ( merged.indexOf( id ) === -1 ) { merged.push( id ); }
                    } );
                    setLocal( merged );
                    renderButtons();
                }
            }
        } );
    }

    // ── Click handler ────────────────────────────────────────────────────────

    $( document ).on( 'click', '.delice-favorite-btn', function () {
        var $btn      = $( this );
        var recipeId  = parseInt( $btn.data( 'recipe-id' ), 10 );
        if ( ! recipeId ) { return; }

        var wasFav    = isFavorited( recipeId );

        // Optimistic update
        if ( wasFav ) {
            removeLocal( recipeId );
            $btn.removeClass( 'is-favorited' );
        } else {
            addLocal( recipeId );
            $btn.addClass( 'is-favorited' );
        }

        if ( ! ajaxUrl ) { return; }

        $.ajax( {
            url:  ajaxUrl,
            type: 'POST',
            data: {
                action:    'delice_toggle_favorite',
                nonce:     nonce,
                recipe_id: recipeId,
            },
            error: function () {
                // Rollback on network failure
                if ( wasFav ) {
                    addLocal( recipeId );
                    $btn.addClass( 'is-favorited' );
                } else {
                    removeLocal( recipeId );
                    $btn.removeClass( 'is-favorited' );
                }
            }
        } );
    } );

    // ── Init ─────────────────────────────────────────────────────────────────

    $( function () {
        renderButtons();
        if ( userId ) { syncFromServer(); }
    } );

}( window.jQuery ) );
