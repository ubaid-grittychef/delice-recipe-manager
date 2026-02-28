/**
 * Affiliate Links admin JS — v3.8.5
 *
 * Handles:
 *  - Add / remove keyword rule rows (with platform-aware dropdown)
 *  - Re-index names after every DOM change
 *  - CSV import for keyword rules
 *  - Add / remove custom platform rows
 *  - ASIN field show/hide based on selected platform type
 */
/* global jQuery, drmPlatforms */
( function ( $ ) {
    'use strict';

    /* ── Keyword Rules ──────────────────────────────────────────────────────── */

    var $tbody     = $( '#drm-rules-tbody' );
    var $empty     = $( '#drm-rules-empty' );
    var $ruleCount = $( '#drm-rule-count' );
    var platforms  = ( typeof window.drmPlatforms !== 'undefined' ) ? window.drmPlatforms : [];

    function updateRuleState() {
        var count = $tbody.find( '.drm-rule-row' ).length;
        $empty.toggleClass( 'hidden', count > 0 );
        $ruleCount.text( count === 1 ? '1 rule' : count + ' rules' );
    }

    function reindexRules() {
        $tbody.find( '.drm-rule-row' ).each( function ( i ) {
            $( this ).find( '[name]' ).each( function () {
                var n = $( this ).attr( 'name' );
                if ( n ) $( this ).attr( 'name', n.replace( /\[\d+\]/, '[' + i + ']' ) );
            } );
        } );
    }

    function escAttr( s ) {
        return String( s )
            .replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' )
            .replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
    }

    function buildPlatformOptions( selectedId ) {
        var opts = '<option value="">\u2014 Select \u2014</option>';
        for ( var i = 0; i < platforms.length; i++ ) {
            var p = platforms[ i ];
            opts += '<option value="' + escAttr( p.id ) + '"'
                + ' data-type="' + escAttr( p.type ) + '"'
                + ( p.id === selectedId ? ' selected' : '' ) + '>'
                + escAttr( p.name || p.type )
                + '</option>';
        }
        return opts;
    }

    function buildRuleRow( i, d ) {
        d = d || {};
        var id  = d.id         || 'rule_' + Date.now() + '_' + i;
        var kw  = d.keyword    || '';
        var mt  = d.match_type || 'contains';
        var pid = d.platform_id || '';
        var prd = d.product_id || '';
        var url = d.custom_url || '';
        var act = d.active !== false;

        var mtSel = [
            '<option value="contains"' + ( mt === 'contains' ? ' selected' : '' ) + '>Contains</option>',
            '<option value="starts"'   + ( mt === 'starts'   ? ' selected' : '' ) + '>Starts with</option>',
            '<option value="exact"'    + ( mt === 'exact'    ? ' selected' : '' ) + '>Exact</option>',
        ].join( '' );

        return (
            '<tr class="drm-rule-row drm-rule-row--new" data-id="' + escAttr( id ) + '">' +
            '<td class="col-on">' +
                '<input type="hidden" name="delice_affiliate_rules[' + i + '][id]" value="' + escAttr( id ) + '">' +
                '<label class="drm-sw">' +
                    '<input type="checkbox" name="delice_affiliate_rules[' + i + '][active]" value="1"' + ( act ? ' checked' : '' ) + '>' +
                    '<span class="drm-sw-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="col-kw"><input type="text" name="delice_affiliate_rules[' + i + '][keyword]" value="' + escAttr( kw ) + '" placeholder="e.g. olive oil" class="drm-aff-input"></td>' +
            '<td class="col-mt"><select name="delice_affiliate_rules[' + i + '][match_type]" class="drm-aff-select">' + mtSel + '</select></td>' +
            '<td class="col-plat"><select name="delice_affiliate_rules[' + i + '][platform_id]" class="drm-aff-select drm-platform-select">' + buildPlatformOptions( pid ) + '</select></td>' +
            '<td class="col-pid"><input type="text" name="delice_affiliate_rules[' + i + '][product_id]" value="' + escAttr( prd ) + '" placeholder="B07XXXXX" class="drm-aff-input drm-asin-field"></td>' +
            '<td class="col-url"><input type="url" name="delice_affiliate_rules[' + i + '][custom_url]" value="' + escAttr( url ) + '" placeholder="https://..." class="drm-aff-input"></td>' +
            '<td class="col-del"><button type="button" class="drm-row-del" aria-label="Remove">&#x2715;</button></td>' +
            '</tr>'
        );
    }

    /* Add rule */
    $( '#drm-add-rule' ).on( 'click', function () {
        var count = $tbody.find( '.drm-rule-row' ).length;
        $tbody.append( buildRuleRow( count ) );
        reindexRules();
        updateRuleState();
        $tbody.find( '.drm-rule-row:last .drm-aff-input:first' ).trigger( 'focus' );
        setTimeout( function () { $tbody.find( '.drm-rule-row--new' ).removeClass( 'drm-rule-row--new' ); }, 800 );
    } );

    /* Remove rule */
    $tbody.on( 'click', '.drm-row-del', function () {
        $( this ).closest( '.drm-rule-row' ).remove();
        reindexRules();
        updateRuleState();
    } );

    /* ASIN field toggle — show hint only for Amazon platforms */
    $tbody.on( 'change', '.drm-platform-select', function () {
        var $row  = $( this ).closest( 'tr' );
        var $opt  = $( this ).find( ':selected' );
        var type  = $opt.data( 'type' ) || '';
        var $asin = $row.find( '.drm-asin-field' );
        $asin.attr( 'placeholder', type === 'amazon' ? 'B07XXXXX (optional)' : '' );
        $asin.prop( 'disabled', type !== 'amazon' && type !== '' );
        if ( type !== 'amazon' ) $asin.val( '' );
    } );

    /* CSV import */
    $( '#drm-csv-import' ).on( 'change', function () {
        var file = this.files && this.files[ 0 ];
        if ( ! file ) return;
        var reader = new FileReader();
        reader.onload = function ( e ) {
            var lines = e.target.result.split( /\r?\n/ );
            var added = 0;
            var start = /^\s*keyword/i.test( lines[ 0 ] ) ? 1 : 0;
            var validMt = [ 'exact', 'starts', 'contains' ];
            for ( var i = start; i < lines.length; i++ ) {
                var line = lines[ i ].trim();
                if ( ! line ) continue;
                var parts = line.split( ',' );
                var kw  = ( parts[ 0 ] || '' ).trim();
                var pid = ( parts[ 1 ] || '' ).trim();
                var prd = ( parts[ 2 ] || '' ).trim();
                var url = ( parts[ 3 ] || '' ).trim();
                var mt  = ( parts[ 4 ] || '' ).trim().toLowerCase();
                if ( ! kw ) continue;
                if ( validMt.indexOf( mt ) === -1 ) mt = 'contains';
                var count = $tbody.find( '.drm-rule-row' ).length;
                $tbody.append( buildRuleRow( count, { keyword: kw, platform_id: pid, product_id: prd, custom_url: url, match_type: mt, active: true } ) );
                added++;
            }
            reindexRules();
            updateRuleState();
            // eslint-disable-next-line no-alert
            alert( added > 0 ? added + ' rule(s) imported. Review then click Save Rules.' : 'No valid rows. Expected: keyword, platform_id, product_id, custom_url, match_type' );
        };
        reader.readAsText( file );
        this.value = '';
    } );

    /* ── Custom Platforms (Platforms tab) ───────────────────────────────────── */

    var $customList   = $( '#drm-custom-platforms-list' );
    var $customEmpty  = $( '#drm-custom-empty' );

    // Count fixed platform inputs (Amazon=0, ShareASale=1, CJ=2, Impact=3 → next = 4)
    var FIXED_PLAT_COUNT = 4; // must match the PHP form_index starting point

    function updateCustomState() {
        var count = $customList.find( '.drm-custom-platform-row' ).length;
        $customEmpty.toggle( count === 0 );
    }

    function reindexCustomPlatforms() {
        // Re-number only the custom platform inputs; fixed platforms keep their indices
        $customList.find( '.drm-custom-platform-row' ).each( function ( i ) {
            var fi = FIXED_PLAT_COUNT + i;
            $( this ).find( '[name]' ).each( function () {
                var n = $( this ).attr( 'name' );
                if ( n ) $( this ).attr( 'name', n.replace( /\[\d+\]/, '[' + fi + ']' ) );
            } );
        } );
    }

    function buildCustomPlatformRow( fi ) {
        var id = 'plat_custom_' + Date.now();
        return (
            '<div class="drm-custom-platform-row drm-custom-row--new">' +
            '<div>' +
                '<label>Platform name</label>' +
                '<input type="hidden" name="delice_affiliate_platforms[' + fi + '][id]"   value="' + escAttr( id ) + '">' +
                '<input type="hidden" name="delice_affiliate_platforms[' + fi + '][type]" value="custom">' +
                '<input type="text"   name="delice_affiliate_platforms[' + fi + '][name]" value="" placeholder="e.g. Instacart">' +
            '</div>' +
            '<div>' +
                '<label>Search URL (use {keyword})</label>' +
                '<input type="url" name="delice_affiliate_platforms[' + fi + '][search_url]" value="" placeholder="https://.../{keyword}">' +
            '</div>' +
            '<div style="padding-top:18px;">' +
                '<button type="button" class="drm-remove-platform" title="Remove">&#x2715;</button>' +
            '</div>' +
            '</div>'
        );
    }

    $( '#drm-add-custom-platform' ).on( 'click', function () {
        var fi = FIXED_PLAT_COUNT + $customList.find( '.drm-custom-platform-row' ).length;
        $customList.append( buildCustomPlatformRow( fi ) );
        reindexCustomPlatforms();
        updateCustomState();
        setTimeout( function () { $customList.find( '.drm-custom-row--new' ).removeClass( 'drm-custom-row--new' ); }, 800 );
    } );

    $customList.on( 'click', '.drm-remove-platform', function () {
        $( this ).closest( '.drm-custom-platform-row' ).remove();
        reindexCustomPlatforms();
        updateCustomState();
    } );

    /* ── WP Recipe Maker Import ─────────────────────────────────────────────── */

    function escWprmAttr( s ) {
        return String( s ).replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' )
            .replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
    }

    $( '#drm-wprm-scan' ).on( 'click', function () {
        var $btn    = $( this );
        var $status = $( '#drm-wprm-status' );
        var $res    = $( '#drm-wprm-results' );
        var nonce   = window.drmAffTagsNonce || '';
        var ajaxUrl = window.drmAjaxUrl || window.ajaxurl || '';

        $btn.prop( 'disabled', true ).text( 'Scanning\u2026' );
        $status.text( '' );

        $.post( ajaxUrl, { action: 'delice_wprm_scan', nonce: nonce }, function ( res ) {
            $btn.prop( 'disabled', false ).text( 'Re-scan WP Recipe Maker' );
            if ( res && res.success ) {
                buildWprmTable( res.data );
                $res.show();
                $status.text( res.data.length + ' recipe(s) found.' );
            } else {
                var msg = ( res && res.data && res.data.msg ) ? res.data.msg : ( res && res.data ? res.data : 'Scan failed.' );
                $status.text( msg );
            }
        } ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Scan WP Recipe Maker' );
            $status.text( 'Network error.' );
        } );
    } );

    function buildWprmTable( recipes ) {
        var $tbody = $( '#drm-wprm-tbody' );
        $tbody.empty();
        if ( ! recipes || ! recipes.length ) {
            $tbody.append( '<tr><td colspan="4" style="padding:20px;text-align:center;color:#8c8f94;">No WP Recipe Maker recipes found.</td></tr>' );
            return;
        }
        $.each( recipes, function ( i, r ) {
            var ingList = r.tags ? r.tags.replace( /\n/g, ', ' ) : '\u2014';
            var matchCell = r.matched
                ? '<span style="color:#008a20;">' + escWprmAttr( r.delice_title ) + '</span>'
                : '<select class="drm-wprm-match" data-wprm-id="' + r.wprm_id + '" data-tags="' + escWprmAttr( r.tags ) + '" style="max-width:200px;font-size:12px;"><option value="0">\u2014 Select recipe \u2014</option>' + window.drmDeliceRecipes.map( function ( d ) { return '<option value="' + d.id + '">' + escWprmAttr( d.title ) + '</option>'; } ).join( '' ) + '</select>';
            $tbody.append(
                '<tr>' +
                '<td><input type="checkbox" class="drm-wprm-chk" data-wprm-id="' + r.wprm_id + '" data-delice-id="' + r.delice_id + '" data-tags="' + escWprmAttr( r.tags ) + '"' + ( r.matched && r.ing_count > 0 ? ' checked' : '' ) + '></td>' +
                '<td style="font-weight:600;">' + escWprmAttr( r.wprm_title ) + '</td>' +
                '<td style="font-size:11px;color:#555;max-width:220px;word-break:break-word;">' + escWprmAttr( ingList ) + '<br><em>' + r.ing_count + ' ingredient(s)</em></td>' +
                '<td>' + matchCell + '</td>' +
                '</tr>'
            );
        } );
    }

    /* Update checkbox data-delice-id when user picks from match dropdown */
    $( document ).on( 'change', '.drm-wprm-match', function () {
        var $sel   = $( this );
        var $chk   = $sel.closest( 'tr' ).find( '.drm-wprm-chk' );
        var newId  = parseInt( $sel.val(), 10 );
        $chk.data( 'delice-id', newId );
        if ( newId ) $chk.prop( 'checked', true );
    } );

    $( '#drm-wprm-select-all' ).on( 'change', function () {
        $( '.drm-wprm-chk' ).prop( 'checked', this.checked );
    } );

    $( '#drm-wprm-import' ).on( 'click', function () {
        var $btn  = $( this );
        var items = [];
        $( '.drm-wprm-chk:checked' ).each( function () {
            var deliceId = parseInt( $( this ).data( 'delice-id' ), 10 );
            var tags     = $( this ).data( 'tags' ) || '';
            if ( deliceId && tags ) items.push( { delice_id: deliceId, tags: tags } );
        } );
        if ( ! items.length ) {
            alert( 'No recipes selected with matched Delice recipes. Match recipes first, then import.' );
            return;
        }
        $btn.prop( 'disabled', true ).text( 'Importing\u2026' );
        $.post( window.drmAjaxUrl || window.ajaxurl || '', {
            action: 'delice_wprm_import',
            nonce:  window.drmAffTagsNonce || '',
            items:  JSON.stringify( items ),
        }, function ( res ) {
            $btn.prop( 'disabled', false ).text( 'Import Selected' );
            if ( res && res.success ) {
                alert( res.data.imported + ' recipe(s) imported. Reload the Coverage tab to see updated status.' );
            } else {
                alert( 'Import failed: ' + ( res && res.data ? res.data : 'unknown error' ) );
            }
        } ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Import Selected' );
            alert( 'Network error.' );
        } );
    } );

    /* ── Coverage Tab ────────────────────────────────────────────────────────── */

    /* Filter buttons */
    $( document ).on( 'click', '.drm-cov-filter-btn', function () {
        var filter   = $( this ).data( 'filter' );
        var $table   = $( '#drm-cov-table' );

        $( '.drm-cov-filter-btn' ).removeClass( 'is-active' );
        $( this ).addClass( 'is-active' );

        $table.find( '.drm-cov-row' ).each( function () {
            var status = $( this ).data( 'status' );
            $( this ).toggle( filter === 'all' || filter === status );
        } );
    } );

    /* AJAX save per-recipe ingredient tags */
    $( document ).on( 'click', '.drm-cov-save', function () {
        var $btn   = $( this );
        var $wrap  = $btn.closest( '.drm-cov-tag-wrap' );
        var pid    = parseInt( $btn.data( 'post-id' ), 10 );
        var tags   = $wrap.find( '.drm-cov-tags' ).val();
        var nonce  = window.drmAffTagsNonce || '';
        var ajaxUrl = window.drmAjaxUrl || window.ajaxurl || '';

        if ( ! pid || ! nonce || ! ajaxUrl ) return;

        $btn.prop( 'disabled', true ).text( 'Saving\u2026' );

        $.post( ajaxUrl, {
            action:  'delice_save_aff_tags',
            nonce:   nonce,
            post_id: pid,
            tags:    tags,
        }, function ( res ) {
            if ( res && res.success ) {
                $btn.text( 'Saved \u2713' ).addClass( 'is-saved' );
                setTimeout( function () {
                    $btn.text( 'Save' ).removeClass( 'is-saved' ).prop( 'disabled', false );
                }, 2200 );
            } else {
                $btn.text( 'Error' ).prop( 'disabled', false );
            }
        } ).fail( function () {
            $btn.text( 'Error' ).prop( 'disabled', false );
        } );
    } );

    /* ── Init ────────────────────────────────────────────────────────────────── */
    updateRuleState();
    updateCustomState();

    // Apply ASIN field state to existing rows on load
    $tbody.find( '.drm-platform-select' ).each( function () {
        var type = $( this ).find( ':selected' ).data( 'type' ) || '';
        var $asin = $( this ).closest( 'tr' ).find( '.drm-asin-field' );
        $asin.prop( 'disabled', type !== 'amazon' && type !== '' );
    } );

} )( jQuery );
