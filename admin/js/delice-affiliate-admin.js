/**
 * Affiliate Links admin JS — v3.9.23
 *
 * Handles:
 *  - Add / remove keyword rule rows (with platform-aware dropdown)
 *  - Re-index names after every DOM change
 *  - CSV import for keyword rules
 *  - Add / remove custom platform rows
 *  - ASIN field show/hide based on selected platform type
 *  - Coverage tab: live scan, filter, per-recipe AJAX save
 *  - WP Recipe Maker: scan + bulk import (fixed import-button enablement,
 *    column alignment, and null-safety on drmDeliceRecipes)
 */
/* global jQuery, drmPlatforms */
( function ( $ ) {
    'use strict';

    /* ── Shared helpers ─────────────────────────────────────────────────────── */

    function escAttr( s ) {
        return String( s )
            .replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' )
            .replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
    }

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

    /* ── Coverage Tab ────────────────────────────────────────────────────────── */

    /* Filter buttons */
    $( document ).on( 'click', '.drm-cov-filter-btn', function () {
        var filter = $( this ).data( 'filter' );
        $( '.drm-cov-filter-btn' ).removeClass( 'is-active' );
        $( this ).addClass( 'is-active' );
        $( '#drm-cov-table' ).find( '.drm-cov-row' ).each( function () {
            $( this ).toggle( filter === 'all' || $( this ).data( 'status' ) === filter );
        } );
    } );

    /* ── Shared data helper (wp_localize_script object > inline-script globals) */
    function drmCfg() {
        var cfg = window.drmAffAdmin || {};
        return {
            nonce:   cfg.nonce   || window.drmAffTagsNonce  || '',
            ajaxUrl: cfg.ajaxUrl || window.drmAjaxUrl || window.ajaxurl || '',
            recipes: ( cfg.deliceRecipes && Array.isArray( cfg.deliceRecipes ) )
                        ? cfg.deliceRecipes
                        : ( window.drmDeliceRecipes && Array.isArray( window.drmDeliceRecipes ) )
                            ? window.drmDeliceRecipes : [],
        };
    }

    /* AJAX save per-recipe ingredient tags */
    $( document ).on( 'click', '.drm-cov-save', function () {
        var $btn    = $( this );
        var $wrap   = $btn.closest( '.drm-cov-tag-wrap' );
        var pid     = parseInt( $btn.data( 'post-id' ), 10 );
        var tags    = $wrap.find( '.drm-cov-tags' ).val();
        var cfg     = drmCfg();
        var nonce   = cfg.nonce;
        var ajaxUrl = cfg.ajaxUrl;

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
                $btn.text( 'Error \u2014 retry' ).prop( 'disabled', false );
                setTimeout( function () { $btn.text( 'Save' ); }, 3000 );
            }
        } ).fail( function () {
            $btn.text( 'Network error' ).prop( 'disabled', false );
            setTimeout( function () { $btn.text( 'Save' ); }, 3000 );
        } );
    } );

    /* Live coverage scan — updates stats and table without a full page reload */
    $( document ).on( 'click', '#drm-cov-scan', function () {
        var $btn    = $( this );
        var $status = $( '#drm-cov-scan-status' );
        var cfg     = drmCfg();
        var nonce   = cfg.nonce;
        var ajaxUrl = cfg.ajaxUrl;

        if ( ! nonce || ! ajaxUrl ) {
            $status.text( 'Configuration error \u2014 missing nonce or AJAX URL.' );
            return;
        }

        $btn.prop( 'disabled', true ).text( 'Scanning\u2026' );
        $status.text( '' );

        $.post( ajaxUrl, { action: 'delice_coverage_scan', nonce: nonce }, function ( res ) {
            $btn.prop( 'disabled', false ).text( 'Scan Recipes' );

            if ( ! res || ! res.success ) {
                $status.text( ( res && res.data ) ? res.data : 'Scan failed.' );
                return;
            }

            var data    = res.data;
            var rows    = data.rows || [];
            var ready   = 0, noMatch = 0, needsTags = 0;

            /* Update stat counts */
            $.each( rows, function ( i, r ) {
                if ( r.status === 'ready' )       ready++;
                else if ( r.status === 'no-match' ) noMatch++;
                else                               needsTags++;
            } );
            $( '#drm-stat-ready' ).text( ready );
            $( '#drm-stat-nomatch' ).text( noMatch );
            $( '#drm-stat-needs' ).text( needsTags );
            $( '#drm-stat-total' ).text( rows.length );

            /* Update table rows in-place */
            var $table = $( '#drm-cov-table' );
            if ( $table.length ) {
                var $tbody = $table.find( 'tbody' );
                $tbody.find( '.drm-cov-row' ).each( function () {
                    var rowPid = parseInt( $( this ).find( '.drm-cov-save' ).data( 'post-id' ), 10 );
                    if ( ! rowPid ) return;
                    /* Find matching data */
                    var match = null;
                    $.each( rows, function ( i, r ) {
                        if ( r.id === rowPid ) { match = r; return false; }
                    } );
                    if ( ! match ) return;
                    /* Update status pill */
                    var $pill = $( this ).find( '.drm-pill' );
                    $pill
                        .removeClass( 'drm-pill-green drm-pill-orange drm-pill-grey' )
                        .addClass( {
                            'ready':      'drm-pill-green',
                            'no-match':   'drm-pill-orange',
                            'needs-tags': 'drm-pill-grey',
                        }[ match.status ] || 'drm-pill-grey' );
                    $pill.find( '.drm-pill-text' ).text( {
                        'ready':      'Ready',
                        'no-match':   'No Match',
                        'needs-tags': 'Needs Tags',
                    }[ match.status ] || match.status );
                    /* Update match count cell */
                    $( this ).find( '.drm-cov-match-count' )
                        .text( match.match_count )
                        .css( 'color', match.match_count > 0 ? '#008a20' : '#8c8f94' );
                    /* Update row data-status for filters */
                    $( this ).data( 'status', match.status ).attr( 'data-status', match.status );
                } );
                /* Re-apply active filter */
                var activeFilter = $( '.drm-cov-filter-btn.is-active' ).data( 'filter' ) || 'all';
                $table.find( '.drm-cov-row' ).each( function () {
                    $( this ).toggle( activeFilter === 'all' || $( this ).data( 'status' ) === activeFilter );
                } );
            }

            $status.text( 'Scan complete \u2014 ' + rows.length + ' recipe(s).' );
            setTimeout( function () { $status.text( '' ); }, 4000 );
        } ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Scan Recipes' );
            $status.text( 'Network error. Please try again.' );
        } );
    } );

    /* ── WP Recipe Maker Import ─────────────────────────────────────────────── */

    /**
     * Build the WPRM results table.
     * Fixes (v3.9.16):
     *  - Matches the 5-column PHP header (Checkbox | WPRM Recipe | # Ings | Match | Preview)
     *  - Enables the Import button after populating results
     *  - Null-checks window.drmDeliceRecipes before calling .map()
     */
    function buildWprmTable( recipes ) {
        var $tbl     = $( '#drm-wprm-tbody' );
        var $importBtn = $( '#drm-wprm-import' );
        $tbl.empty();

        if ( ! recipes || ! recipes.length ) {
            $tbl.append(
                '<tr><td colspan="5" style="padding:20px;text-align:center;color:#8c8f94;">' +
                'No WP Recipe Maker recipes found.</td></tr>'
            );
            $importBtn.prop( 'disabled', true );
            return;
        }

        /* Null-safe Delice recipe list (prefer wp_localize_script data) */
        var deliceList = drmCfg().recipes;

        $.each( recipes, function ( i, r ) {
            /* Build the match cell */
            var matchCell;
            if ( r.matched ) {
                var deliceLink = r.delice_edit_url
                    ? '<a href="' + escAttr( r.delice_edit_url ) + '" target="_blank" style="color:#008a20;font-weight:600;">' + escAttr( r.delice_title ) + '</a>'
                    : '<span style="color:#008a20;font-weight:600;">' + escAttr( r.delice_title ) + '</span>';
                matchCell = deliceLink;
            } else {
                var opts = '<option value="0">\u2014 Select recipe \u2014</option>';
                $.each( deliceList, function ( j, d ) {
                    opts += '<option value="' + d.id + '">' + escAttr( d.title ) + '</option>';
                } );
                matchCell = '<select class="drm-wprm-match drm-aff-select"'
                    + ' data-wprm-id="' + r.wprm_id + '"'
                    + ' data-tags="' + escAttr( r.tags || '' ) + '"'
                    + ' style="max-width:200px;font-size:12px;">'
                    + opts + '</select>';
            }

            /* Ingredient tags preview — first 60 chars */
            var ingPreview = '';
            if ( r.tags ) {
                var parts = r.tags.split( '\n' ).filter( Boolean );
                var preview = parts.slice( 0, 5 ).join( ', ' );
                if ( parts.length > 5 ) preview += ' \u2026 +' + ( parts.length - 5 ) + ' more';
                ingPreview = '<span style="font-size:11px;color:#555;">' + escAttr( preview ) + '</span>';
            } else {
                ingPreview = '<em style="font-size:11px;color:#8c8f94;">none</em>';
            }

            var wprmTitleHtml = r.wprm_edit_url
                ? '<a href="' + escAttr( r.wprm_edit_url ) + '" target="_blank" style="font-weight:600;">' + escAttr( r.wprm_title ) + '</a>'
                : '<span style="font-weight:600;">' + escAttr( r.wprm_title ) + '</span>';

            $tbl.append(
                '<tr>' +
                '<td style="text-align:center;">' +
                    '<input type="checkbox" class="drm-wprm-chk"' +
                    ' data-wprm-id="' + r.wprm_id + '"' +
                    ' data-delice-id="' + r.delice_id + '"' +
                    ' data-tags="' + escAttr( r.tags || '' ) + '"' +
                    ( r.matched && r.ing_count > 0 ? ' checked' : '' ) + '>' +
                '</td>' +
                '<td>' + wprmTitleHtml + '</td>' +
                '<td style="text-align:center;">' + r.ing_count + '</td>' +
                '<td>' + matchCell + '</td>' +
                '<td>' + ingPreview + '</td>' +
                '</tr>'
            );
        } );

        /* ── BUG FIX: enable the Import button that starts as disabled in HTML ── */
        $importBtn.prop( 'disabled', false );
    }

    /* Scan button */
    $( document ).on( 'click', '#drm-wprm-scan', function () {
        var $btn    = $( this );
        var $status = $( '#drm-wprm-status' );
        var $res    = $( '#drm-wprm-results' );
        var cfg     = drmCfg();
        var nonce   = cfg.nonce;
        var ajaxUrl = cfg.ajaxUrl;

        if ( ! nonce || ! ajaxUrl ) {
            $status.text( 'Configuration error \u2014 missing nonce or AJAX URL.' );
            return;
        }

        $btn.prop( 'disabled', true ).text( 'Scanning\u2026' );
        $( '#drm-wprm-import' ).prop( 'disabled', true );
        $status.text( '' );

        $.post( ajaxUrl, { action: 'delice_wprm_scan', nonce: nonce }, function ( res ) {
            $btn.prop( 'disabled', false ).text( 'Re-scan WP Recipe Maker' );
            if ( res && res.success ) {
                buildWprmTable( res.data );
                $res.show();
                $status.text( res.data.length + ' recipe(s) found.' );
            } else {
                var msg = ( res && res.data && res.data.msg )
                    ? res.data.msg
                    : ( res && res.data ? String( res.data ) : 'Scan failed.' );
                $status.text( msg );
            }
        } ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Scan WP Recipe Maker' );
            $status.text( 'Network error \u2014 please try again.' );
        } );
    } );

    /* Update checkbox data-delice-id when user picks from match dropdown */
    $( document ).on( 'change', '.drm-wprm-match', function () {
        var $sel  = $( this );
        var $chk  = $sel.closest( 'tr' ).find( '.drm-wprm-chk' );
        var newId = parseInt( $sel.val(), 10 );
        var tags  = $sel.data( 'tags' ) || '';
        $chk.data( 'delice-id', newId ).data( 'tags', tags );
        if ( newId ) $chk.prop( 'checked', true );
    } );

    /* Select-all */
    $( document ).on( 'change', '#drm-wprm-select-all', function () {
        $( '.drm-wprm-chk' ).prop( 'checked', this.checked );
    } );

    /* Import selected */
    $( document ).on( 'click', '#drm-wprm-import', function () {
        var $btn  = $( this );
        var items = [];

        $( '.drm-wprm-chk:checked' ).each( function () {
            var deliceId = parseInt( $( this ).data( 'delice-id' ), 10 );
            var tags     = String( $( this ).data( 'tags' ) || '' );
            if ( deliceId && tags ) items.push( { delice_id: deliceId, tags: tags } );
        } );

        if ( ! items.length ) {
            // eslint-disable-next-line no-alert
            alert( 'No recipes selected with matched Delice recipes. Match recipes first, then import.' );
            return;
        }

        $btn.prop( 'disabled', true ).text( 'Importing\u2026' );

        var cfg = drmCfg();
        $.post(
            cfg.ajaxUrl,
            {
                action: 'delice_wprm_import',
                nonce:  cfg.nonce,
                items:  JSON.stringify( items ),
            },
            function ( res ) {
                $btn.prop( 'disabled', false ).text( 'Import Selected' );
                if ( res && res.success ) {
                    // eslint-disable-next-line no-alert
                    alert( res.data.imported + ' recipe(s) imported successfully. The Coverage tab stats will update after a scan or page reload.' );
                } else {
                    // eslint-disable-next-line no-alert
                    alert( 'Import failed: ' + ( res && res.data ? String( res.data ) : 'unknown error' ) );
                }
            }
        ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Import Selected' );
            // eslint-disable-next-line no-alert
            alert( 'Network error. Please try again.' );
        } );
    } );

    /* ── Init ────────────────────────────────────────────────────────────────── */
    updateRuleState();
    updateCustomState();

    /* Apply ASIN field disabled state to existing rows on load */
    $tbody.find( '.drm-platform-select' ).each( function () {
        var type = $( this ).find( ':selected' ).data( 'type' ) || '';
        $( this ).closest( 'tr' ).find( '.drm-asin-field' )
            .prop( 'disabled', type !== 'amazon' && type !== '' );
    } );

} )( jQuery );
