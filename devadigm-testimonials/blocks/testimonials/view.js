/**
 * Devadigm Testimonials - front-end behaviour.
 *
 * Built on the WordPress Interactivity API rather than a slider library. The
 * markup is rendered on the server and only hydrated here, so the block works
 * behind full-page caching and adds no framework to the page.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Find the scrolling track that belongs to the element currently being handled.
 *
 * @param {HTMLElement} ref Element the directive fired on.
 * @return {HTMLElement|null} The slide track, if there is one.
 */
const trackFor = ( ref ) => {
	const root = ref.closest( '.dvdm-t' );
	return root ? root.querySelector( '.dvdm-t__items' ) : null;
};

/**
 * Index of the slide nearest the centre of the track.
 *
 * @param {HTMLElement} track Scrolling track.
 * @return {number} Zero-based slide index.
 */
const nearestSlide = ( track ) => {
	const slides = Array.from( track.children );
	const middle = track.scrollLeft + track.clientWidth / 2;

	let best = 0;
	let shortest = Infinity;

	slides.forEach( ( slide, index ) => {
		const centre = slide.offsetLeft - track.offsetLeft + slide.clientWidth / 2;
		const distance = Math.abs( centre - middle );
		if ( distance < shortest ) {
			shortest = distance;
			best = index;
		}
	} );

	return best;
};

/**
 * Scroll the track to a given slide, clamped to the available range.
 *
 * @param {HTMLElement} track Scrolling track.
 * @param {number}      index Target slide index.
 * @return {number} The index actually scrolled to.
 */
const scrollTo = ( track, index ) => {
	const slides = Array.from( track.children );
	const target = Math.max( 0, Math.min( slides.length - 1, index ) );
	const slide = slides[ target ];

	if ( slide ) {
		track.scrollTo( {
			left: slide.offsetLeft - track.offsetLeft,
			behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
				? 'auto'
				: 'smooth',
		} );
	}

	return target;
};

store( 'devadigm/testimonials', {
	state: {
		/**
		 * Marks the dot matching the current slide. Returning false removes the
		 * attribute entirely, which is what assistive technology expects.
		 *
		 * Each dot carries its own index in a nested context, which merges with
		 * the carousel context above it. Deriving this from the element ref
		 * instead would break on hydration, when the ref is not yet attached.
		 *
		 * @return {string|boolean} 'true' for the active dot, false otherwise.
		 */
		get isCurrent() {
			const { current, index } = getContext();
			return current === index ? 'true' : false;
		},

		/**
		 * Announcement text for the carousel live region.
		 *
		 * @return {string} Human readable position.
		 */
		get liveText() {
			const context = getContext();
			return `${ context.current + 1 } / ${ context.total }`;
		},

		/**
		 * Label for the marquee pause toggle.
		 *
		 * @return {string} Current toggle label.
		 */
		get pauseLabel() {
			const context = getContext();
			return context.paused ? context.playText : context.pauseText;
		},

		/**
		 * Paused flag as a string, for binding onto a data attribute.
		 *
		 * @return {string} 'true' or 'false'.
		 */
		get paused() {
			return getContext().paused ? 'true' : 'false';
		},
	},

	actions: {
		/**
		 * Move to the previous slide.
		 */
		prev() {
			const context = getContext();
			const track = trackFor( getElement().ref );
			if ( track ) {
				context.current = scrollTo( track, context.current - 1 );
			}
		},

		/**
		 * Move to the next slide.
		 */
		next() {
			const context = getContext();
			const track = trackFor( getElement().ref );
			if ( track ) {
				context.current = scrollTo( track, context.current + 1 );
			}
		},

		/**
		 * Jump to the slide named by the clicked dot.
		 */
		goTo() {
			const context = getContext();
			const track = trackFor( getElement().ref );
			if ( track ) {
				context.current = scrollTo( track, context.index || 0 );
			}
		},

		/**
		 * Keep the dots in step when the track is scrolled or swiped directly.
		 */
		onScroll() {
			const context = getContext();
			const { ref } = getElement();

			window.clearTimeout( ref._dvdmScrollTimer );
			ref._dvdmScrollTimer = window.setTimeout( () => {
				context.current = nearestSlide( ref );
			}, 90 );
		},

		/**
		 * Arrow-key support on the focused track.
		 *
		 * @param {KeyboardEvent} event Key event.
		 */
		onKeydown( event ) {
			if ( event.key !== 'ArrowRight' && event.key !== 'ArrowLeft' ) {
				return;
			}
			event.preventDefault();

			const context = getContext();
			const track = trackFor( getElement().ref );
			if ( ! track ) {
				return;
			}

			const step = event.key === 'ArrowRight' ? 1 : -1;
			context.current = scrollTo( track, context.current + step );
		},

		/**
		 * Pause or resume the marquee. Required for any motion that runs past
		 * five seconds.
		 */
		togglePause() {
			const context = getContext();
			context.paused = ! context.paused;
		},
	},

	callbacks: {
		/**
		 * Sync the slide count in case the server and client disagree.
		 */
		init() {
			const context = getContext();
			const track = trackFor( getElement().ref );
			if ( track ) {
				context.total = track.children.length;
			}
		},
	},
} );
