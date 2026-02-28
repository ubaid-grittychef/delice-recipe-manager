/**
 * Affiliate Links admin JS — v3.8.4
 * - Add / remove keyword rule rows
 * - Re-index input names after every add/remove
 * - CSV import (keyword, url, store, match_type)
 * - Live rule-count badge
 */
/* global jQuery */
( function ( $ ) {
    'use strict';

    var $tbody      = $( '#aff-rules-tbody' );
    var $emptyState = $( '#aff-empty-state' );
    var $ruleCount  = $( '#aff-rule-count' );

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    function updateState() {
        var count = $tbody.find( '.aff-rule-row' ).length;
        $emptyState.toggleClass( 'hidden', count > 0 );
        $ruleCount.text( count === 1 ? '1 rule' : count + ' rules' );
    }

    /** Re-index all row input[name] / select[name] to 0-based after DOM change */
    function reindex() {
        $tbody.find( '.aff-rule-row' ).each( function ( i ) {
            $( this ).find( '[name]' ).each( function () {
                var n = $( this ).attr( 'name' );
                if ( n ) {
                    $( this ).attr( 'name', n.replace( /\[\d+\]/, '[' + i + ']' ) );
                }
            } );
        } );
    }

    function escAttr( s ) {
        return String( s )
            .replace( /&/g, '&amp;' )
            .replace( /"/g, '&quot;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' );
    }

    function buildRow( i, d ) {
        d = d || {};
        var id     = d.id         || 'aff_' + Date.now() + '_' + i;
        var kw     = d.keyword    || '';
        var url    = d.url        || '';
        var store  = d.store      || '';
        var mt     = d.match_type || 'contains';
        var active = d.active !== false;

        var sel = function ( val ) {
            return [
                '<option value="contains"' + ( mt === 'contains' ? ' selected' : '' ) + '>Contains</option>',
                '<option value="starts"'   + ( mt === 'starts'   ? ' selected' : '' ) + '>Starts with</option>',
                '<option value="exact"'    + ( mt === 'exact'    ? ' selected' : '' ) + '>Exact</option>',
            ].join( '' );
        };

        return (
            '<tr class="aff-rule-row aff-rule-row--new" data-id="' + escAttr( id ) + '">' +
            '<td class="col-active">' +
                '<input type="hidden" name="delice_affiliate_rules[' + i + '][id]" value="' + escAttr( id ) + '">' +
                '<label class="delice-sw">' +
                    '<input type="checkbox" name="delice_affiliate_rules[' + i + '][active]" value="1"' + ( active ? ' checked' : '' ) + '>' +
                    '<span class="delice-sw-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="col-keyword">' +
                '<input type="text" name="delice_affiliate_rules[' + i + '][keyword]" value="' + escAttr( kw ) + '" placeholder="e.g. olive oil" class="aff-input aff-input-keyword">' +
            '</td>' +
            '<td class="col-url">' +
                '<input type="url" name="delice_affiliate_rules[' + i + '][url]" value="' + escAttr( url ) + '" placeholder="https://amzn.to/xxxxx" class="aff-input aff-input-url">' +
            '</td>' +
            '<td class="col-store">' +
                '<input type="text" name="delice_affiliate_rules[' + i + '][store]" value="' + escAttr( store ) + '" placeholder="Amazon" class="aff-input aff-input-store">' +
            '</td>' +
            '<td class="col-match">' +
                '<select name="delice_affiliate_rules[' + i + '][match_type]" class="aff-select">' + sel() + '</select>' +
            '</td>' +
            '<td class="col-del">' +
                '<button type="button" class="button-link aff-remove-row" aria-label="Remove rule">&#x2715;</button>' +
            '</td>' +
            '</tr>'
        );
    }

    /* ── Add row ─────────────────────────────────────────────────────────── */

    $( '#aff-add-rule' ).on( 'click', function () {
        var count = $tbody.find( '.aff-rule-row' ).length;
        $tbody.append( buildRow( count ) );
        reindex();
        updateState();
        $tbody.find( '.aff-rule-row:last .aff-input-keyword' ).trigger( 'focus' );
        setTimeout( function () {
            $tbody.find( '.aff-rule-row--new' ).removeClass( 'aff-rule-row--new' );
        }, 800 );
    } );

    /* ── Remove row ──────────────────────────────────────────────────────── */

    $tbody.on( 'click', '.aff-remove-row', function () {
        $( this ).closest( '.aff-rule-row' ).remove();
        reindex();
        updateState();
    } );

    /* ── CSV import ──────────────────────────────────────────────────────── */

    $( '#aff-csv-import' ).on( 'change', function () {
        var file = this.files && this.files[ 0 ];
        if ( ! file ) return;

        var reader = new FileReader();
        reader.onload = function ( e ) {
            var lines  = e.target.result.split( /\r?\n/ );
            var added  = 0;
            var start  = /^\s*keyword/i.test( lines[ 0 ] ) ? 1 : 0;
            var validMt = [ 'exact', 'starts', 'contains' ];

            for ( var i = start; i < lines.length; i++ ) {
                var line = lines[ i ].trim();
                if ( ! line ) continue;

                var parts = line.split( ',' );
                var kw    = ( parts[ 0 ] || '' ).trim();
                var url   = ( parts[ 1 ] || '' ).trim();
                var store = ( parts[ 2 ] || '' ).trim();
                var mt    = ( parts[ 3 ] || '' ).trim().toLowerCase();

                if ( ! kw || ! url ) continue;
                if ( validMt.indexOf( mt ) === -1 ) mt = 'contains';

                var count = $tbody.find( '.aff-rule-row' ).length;
                $tbody.append( buildRow( count, { keyword: kw, url: url, store: store, match_type: mt, active: true } ) );
                added++;
            }

            reindex();
            updateState();

            if ( added > 0 ) {
                // eslint-disable-next-line no-alert
                alert( added + ( added === 1 ? ' rule' : ' rules' ) + ' imported. Review then click Save Rules.' );
            } else {
                // eslint-disable-next-line no-alert
                alert( 'No valid rows found. Expected: keyword, url, store, match_type' );
            }
        };
        reader.readAsText( file );
        this.value = ''; // allow re-import of same file
    } );

    /* ── Init ────────────────────────────────────────────────────────────── */
    updateState();

} )( jQuery );
