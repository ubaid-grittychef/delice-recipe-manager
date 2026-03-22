/**
 * Delice Recipe Manager — Image Gallery + Lightbox (v4.1.0)
 *
 * Renders gallery grids from data attributes, opens a keyboard-accessible
 * lightbox on click with prev/next navigation.
 */
( function () {
    'use strict';

    window.Delice = window.Delice || {};
    if ( window.Delice.galleryLoaded ) { return; }
    window.Delice.galleryLoaded = true;

    var lightbox, lightboxImg, lightboxCounter, images, currentIndex;

    // ── Build lightbox overlay (once) ──────────────────────────────────────

    function buildLightbox() {
        if ( lightbox ) { return; }

        lightbox = document.createElement( 'div' );
        lightbox.className = 'delice-lightbox';
        lightbox.setAttribute( 'role', 'dialog' );
        lightbox.setAttribute( 'aria-modal', 'true' );
        lightbox.setAttribute( 'aria-label', 'Image lightbox' );
        lightbox.innerHTML = [
            '<button class="delice-lightbox-close" aria-label="Close">&times;</button>',
            '<button class="delice-lightbox-prev" aria-label="Previous image">&#8249;</button>',
            '<img class="delice-lightbox-img" src="" alt="">',
            '<button class="delice-lightbox-next" aria-label="Next image">&#8250;</button>',
            '<span class="delice-lightbox-counter"></span>'
        ].join( '' );

        document.body.appendChild( lightbox );

        lightboxImg     = lightbox.querySelector( '.delice-lightbox-img' );
        lightboxCounter = lightbox.querySelector( '.delice-lightbox-counter' );

        lightbox.querySelector( '.delice-lightbox-close' ).addEventListener( 'click', closeLightbox );
        lightbox.querySelector( '.delice-lightbox-prev' ).addEventListener( 'click', function () { navigate( -1 ); } );
        lightbox.querySelector( '.delice-lightbox-next' ).addEventListener( 'click', function () { navigate( 1 ); } );

        lightbox.addEventListener( 'click', function ( e ) {
            if ( e.target === lightbox ) { closeLightbox(); }
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( ! lightbox.classList.contains( 'delice-lightbox-open' ) ) { return; }
            if ( e.key === 'Escape' )      { closeLightbox(); }
            if ( e.key === 'ArrowLeft' )   { navigate( -1 ); }
            if ( e.key === 'ArrowRight' )  { navigate( 1 ); }
        } );
    }

    function openLightbox( srcs, index ) {
        buildLightbox();
        images = srcs;
        currentIndex = index;
        showImage();
        lightbox.classList.add( 'delice-lightbox-open' );
        document.body.style.overflow = 'hidden';
        lightbox.querySelector( '.delice-lightbox-close' ).focus();
    }

    function closeLightbox() {
        lightbox.classList.remove( 'delice-lightbox-open' );
        document.body.style.overflow = '';
    }

    function navigate( dir ) {
        currentIndex = ( currentIndex + dir + images.length ) % images.length;
        showImage();
    }

    function showImage() {
        lightboxImg.src = images[ currentIndex ];
        lightboxCounter.textContent = ( currentIndex + 1 ) + ' / ' + images.length;
    }

    // ── Delegate clicks on gallery items ───────────────────────────────────

    function init() {
        document.addEventListener( 'click', function ( e ) {
            var item = e.target.closest( '.delice-gallery-item' );
            if ( ! item ) { return; }

            var grid = item.closest( '.delice-gallery-grid' );
            if ( ! grid ) { return; }

            var items = grid.querySelectorAll( '.delice-gallery-item img' );
            var srcs  = [];
            var idx   = 0;

            items.forEach( function ( img, i ) {
                srcs.push( img.getAttribute( 'data-full' ) || img.src );
                if ( img === item.querySelector( 'img' ) ) { idx = i; }
            } );

            openLightbox( srcs, idx );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )();
