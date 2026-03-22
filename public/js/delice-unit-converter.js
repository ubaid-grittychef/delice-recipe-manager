/**
 * Delice Recipe Manager — Unit Converter (v4.1.0)
 *
 * Client-side metric ↔ imperial conversion for ingredient quantities.
 * Reads data-base-amount / data-base-unit from .delice-recipe-ingredient-quantity
 * and rewrites the visible text.  Preference persisted in localStorage.
 */
( function () {
    'use strict';

    window.Delice = window.Delice || {};
    if ( window.Delice.unitConverterLoaded ) { return; }
    window.Delice.unitConverterLoaded = true;

    var LS_KEY = 'delice_unit_system'; // 'metric' | 'imperial'

    // ── Conversion tables ──────────────────────────────────────────────────
    // Each entry: { to: targetUnit, factor: multiply-by }
    var TO_METRIC = {
        'cup':   { to: 'ml',  factor: 236.588 },
        'cups':  { to: 'ml',  factor: 236.588 },
        'oz':    { to: 'g',   factor: 28.3495 },
        'ounce': { to: 'g',   factor: 28.3495 },
        'ounces':{ to: 'g',   factor: 28.3495 },
        'lb':    { to: 'kg',  factor: 0.453592 },
        'lbs':   { to: 'kg',  factor: 0.453592 },
        'pound': { to: 'kg',  factor: 0.453592 },
        'pounds':{ to: 'kg',  factor: 0.453592 },
        'tbsp':  { to: 'ml',  factor: 14.7868 },
        'tsp':   { to: 'ml',  factor: 4.92892 },
        'fl oz': { to: 'ml',  factor: 29.5735 },
        'quart': { to: 'ml',  factor: 946.353 },
        'quarts':{ to: 'ml',  factor: 946.353 },
        'gallon':{ to: 'l',   factor: 3.78541 },
        'gallons':{ to: 'l',  factor: 3.78541 },
        'pint':  { to: 'ml',  factor: 473.176 },
        'pints': { to: 'ml',  factor: 473.176 },
        'inch':  { to: 'cm',  factor: 2.54 },
        'inches':{ to: 'cm',  factor: 2.54 },
        'F':     { to: 'C',   factor: null },  // special conversion
        '°F':    { to: '°C',  factor: null }
    };

    var TO_IMPERIAL = {
        'ml':  { to: 'fl oz', factor: 0.033814 },
        'l':   { to: 'quart', factor: 1.05669 },
        'g':   { to: 'oz',    factor: 0.035274 },
        'kg':  { to: 'lb',    factor: 2.20462 },
        'cm':  { to: 'inch',  factor: 0.393701 },
        'C':   { to: 'F',     factor: null },
        '°C':  { to: '°F',    factor: null }
    };

    // ── Helpers ────────────────────────────────────────────────────────────

    function getSavedSystem() {
        try { return localStorage.getItem( LS_KEY ) || 'original'; }
        catch ( e ) { return 'original'; }
    }

    function saveSystem( system ) {
        try { localStorage.setItem( LS_KEY, system ); } catch ( e ) {}
    }

    function roundNice( n ) {
        if ( n === 0 ) { return '0'; }
        if ( n >= 100 )  { return Math.round( n ).toString(); }
        if ( n >= 10 )   { return ( Math.round( n * 10 ) / 10 ).toString(); }
        if ( n >= 1 )    { return ( Math.round( n * 100 ) / 100 ).toString(); }
        return ( Math.round( n * 100 ) / 100 ).toString();
    }

    function convertTemp( value, toMetric ) {
        if ( toMetric ) { return ( value - 32 ) * 5 / 9; }
        return value * 9 / 5 + 32;
    }

    function detectSystem( unit ) {
        var lower = unit.toLowerCase().replace( /°/g, '' );
        if ( TO_METRIC[ unit ] || TO_METRIC[ lower ] ) { return 'imperial'; }
        if ( TO_IMPERIAL[ unit ] || TO_IMPERIAL[ lower ] ) { return 'metric'; }
        return null;
    }

    function convertValue( amount, unit, targetSystem ) {
        var num = parseFloat( amount );
        if ( isNaN( num ) ) { return null; }

        var lower = unit.toLowerCase().replace( /°/g, '' );
        var table, entry;

        if ( targetSystem === 'metric' ) {
            table = TO_METRIC;
            entry = table[ unit ] || table[ lower ];
        } else {
            table = TO_IMPERIAL;
            entry = table[ unit ] || table[ lower ];
        }

        if ( ! entry ) { return null; }

        // Temperature special case
        if ( entry.factor === null ) {
            var converted = convertTemp( num, targetSystem === 'metric' );
            return { amount: roundNice( converted ), unit: entry.to };
        }

        return { amount: roundNice( num * entry.factor ), unit: entry.to };
    }

    // ── DOM operations ─────────────────────────────────────────────────────

    function applyConversion( system ) {
        var spans = document.querySelectorAll( '.delice-recipe-ingredient-quantity' );
        spans.forEach( function ( span ) {
            var baseAmount = span.getAttribute( 'data-base-amount' );
            var baseUnit   = span.getAttribute( 'data-base-unit' );

            if ( ! baseAmount || ! baseUnit ) { return; }

            if ( system === 'original' ) {
                span.textContent = baseAmount + ' ' + baseUnit;
                return;
            }

            var sourceSystem = detectSystem( baseUnit );
            if ( ! sourceSystem || sourceSystem === system ) {
                // Already in the target system or unknown unit — show original
                span.textContent = baseAmount + ' ' + baseUnit;
                return;
            }

            var result = convertValue( baseAmount, baseUnit, system );
            if ( result ) {
                span.textContent = result.amount + ' ' + result.unit;
            } else {
                span.textContent = baseAmount + ' ' + baseUnit;
            }
        } );
    }

    function updateToggleButtons( system ) {
        var buttons = document.querySelectorAll( '.delice-unit-toggle-btn' );
        buttons.forEach( function ( btn ) {
            var btnSystem = btn.getAttribute( 'data-unit-system' );
            if ( btnSystem === system ) {
                btn.classList.add( 'delice-unit-active' );
                btn.setAttribute( 'aria-pressed', 'true' );
            } else {
                btn.classList.remove( 'delice-unit-active' );
                btn.setAttribute( 'aria-pressed', 'false' );
            }
        } );
    }

    // ── Init ───────────────────────────────────────────────────────────────

    function init() {
        var system = getSavedSystem();
        if ( system !== 'original' ) {
            applyConversion( system );
        }
        updateToggleButtons( system );

        // Delegate clicks on toggle buttons
        document.addEventListener( 'click', function ( e ) {
            var btn = e.target.closest( '.delice-unit-toggle-btn' );
            if ( ! btn ) { return; }

            var newSystem = btn.getAttribute( 'data-unit-system' );
            saveSystem( newSystem );
            applyConversion( newSystem );
            updateToggleButtons( newSystem );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )();
