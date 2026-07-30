/**
 * Devadigm Testimonials - front-end behaviour.
 *
 * Built on the WordPress Interactivity API rather than a slider library. The
 * markup is rendered on the server and only hydrated here, so the block works
 * behind full-page caching and adds no framework to the page.
 *
 * The slider moves by scroll snapping, which means touch and trackpad already
 * work with no script at all. Everything below exists to keep the arrows, the
 * dots and the screen-reader announcement in step with where the track is, and
 * to move a long quote into a dialog when someone asks to read it.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Whether the visitor has asked for less motion.
 *
 * @return {boolean} True when reduced motion is preferred.
 */
const reducedMotion = () =>
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

/**
 * The block root for the element a directive fired on.
 *
 * @param {HTMLElement} ref Element the directive fired on.
 * @return {HTMLElement|null} The block wrapper.
 */
const rootFor = ( ref ) => ref.closest( '.dvdm-t' );

/**
 * The scrolling track inside a block.
 *
 * @param {HTMLElement|null} root Block wrapper.
 * @return {HTMLElement|null} The slide track, if there is one.
 */
const trackIn = ( root ) => ( root ? root.querySelector( '.dvdm-t__items' ) : null );

/**
 * How many slides are actually visible, which is not always what was asked for.
 *
 * Below the narrow breakpoint the stylesheet forces one slide per view, so
 * paging has to agree with the stylesheet or the dots stop matching the track.
 *
 * @param {HTMLElement} track   Slide track.
 * @param {number}      perView Configured slides per view.
 * @return {number} Slides visible right now, at least one.
 */
const visibleCount = ( track, perView ) => {
	const first = track.firstElementChild;
	if ( ! first ) {
		return 1;
	}
	const slideWidth = first.getBoundingClientRect().width;
	if ( slideWidth <= 0 ) {
		return Math.max( 1, perView );
	}
	return Math.max( 1, Math.round( track.clientWidth / slideWidth ) );
};

/**
 * Index of the slide nearest the start edge of the track.
 *
 * @param {HTMLElement} track Slide track.
 * @return {number} Zero-based slide index.
 */
const nearestSlide = ( track ) => {
	const slides = Array.from( track.children );
	const edge = track.scrollLeft;

	let best = 0;
	let shortest = Infinity;

	slides.forEach( ( slide, index ) => {
		const distance = Math.abs( slide.offsetLeft - track.offsetLeft - edge );
		if ( distance < shortest ) {
			shortest = distance;
			best = index;
		}
	} );

	return best;
};

/**
 * Scroll a page of slides into view.
 *
 * Marks the track as "settling" for the duration of the scroll, so onScroll's
 * drift-reconciliation (which exists for direct swipes and scrollbar drags)
 * does not read the track mid-animation and overwrite the target this call
 * just set. Without that guard, a click landing while the previous smooth
 * scroll was still travelling would have onScroll snap context.current back
 * to wherever the track physically was at that instant - one page behind the
 * click that was just made - which is what made rapid, normally-paced use of
 * the arrows feel like it was lagging or occasionally failing to wrap.
 *
 * @param {HTMLElement} track   Slide track.
 * @param {number}      page    Target page.
 * @param {number}      perView Slides per page.
 * @return {number} The page actually scrolled to.
 */
const scrollToPage = ( track, page, perView ) => {
	const slides = Array.from( track.children );
	const pages = Math.max( 1, Math.ceil( slides.length / perView ) );
	const target = Math.max( 0, Math.min( pages - 1, page ) );
	const slide = slides[ target * perView ];

	if ( slide ) {
		track._dvdmSettling = true;
		window.clearTimeout( track._dvdmSettleFallback );
		// scrollend is the definitive signal; the timeout is only a safety net
		// for the rare browser that does not fire it.
		track._dvdmSettleFallback = window.setTimeout( () => {
			track._dvdmSettling = false;
		}, 1000 );

		track.scrollTo( {
			left: slide.offsetLeft - track.offsetLeft,
			behavior: reducedMotion() ? 'auto' : 'smooth',
		} );
	}

	return target;
};

/**
 * Move the slider, wrapping around when looping is on.
 *
 * @param {HTMLElement} root    Block wrapper.
 * @param {Object}      context Block context.
 * @param {number}      step    1 for forwards, -1 for backwards.
 */
const advance = ( root, context, step ) => {
	const track = trackIn( root );
	if ( ! track ) {
		return;
	}

	const perView = visibleCount( track, context.perView );
	const pages = Math.max( 1, Math.ceil( context.total / perView ) );
	let next = context.current + step;

	if ( next > pages - 1 ) {
		next = context.loop ? 0 : pages - 1;
	} else if ( next < 0 ) {
		next = context.loop ? pages - 1 : 0;
	}

	context.pages = pages;
	context.current = scrollToPage( track, next, perView );
};

/**
 * Stop an autoplay timer and forget it.
 *
 * @param {HTMLElement|null} root Block wrapper.
 */
const clearTimer = ( root ) => {
	if ( root && root._dvdmTimer ) {
		window.clearInterval( root._dvdmTimer );
		root._dvdmTimer = null;
	}
};

/**
 * Start advancing on a timer.
 *
 * @param {HTMLElement} root    Block wrapper.
 * @param {Object}      context Block context.
 */
const startTimer = ( root, context ) => {
	clearTimer( root );
	if ( ! context.autoplay || reducedMotion() ) {
		return;
	}
	root._dvdmTimer = window.setInterval( () => {
		advance( root, context, 1 );
	}, context.autoplay * 1000 );
};

store( 'devadigm/testimonials', {
	state: {
		/**
		 * Marks the dot for the page on screen.
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
		 * Whether the previous arrow has nothing to do. Never true when looping.
		 *
		 * @return {boolean} True when the arrow should be disabled.
		 */
		get atStart() {
			const { current, loop } = getContext();
			return ! loop && current <= 0;
		},

		/**
		 * Whether the next arrow has nothing to do. Never true when looping.
		 *
		 * @return {boolean} True when the arrow should be disabled.
		 */
		get atEnd() {
			const { current, pages, loop } = getContext();
			return ! loop && current >= pages - 1;
		},

		/**
		 * Announcement text for the carousel live region.
		 *
		 * @return {string} Human readable position.
		 */
		get liveText() {
			const { current, pages } = getContext();
			return `${ current + 1 } / ${ pages }`;
		},

		/**
		 * Label for the pause toggle, on both the slider and the marquee.
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

		/**
		 * Whether the marquee track should actually be stopped right now:
		 * explicitly paused via its button, or currently hovered or focused.
		 *
		 * This is the value the track's own animation state binds to, kept
		 * separate from the manual `paused` flag so hovering away afterwards
		 * does not un-pause a marquee someone deliberately stopped, and so
		 * hovering it in the first place does not flip the button's own label
		 * to "Play" for a pause nobody asked for.
		 *
		 * @return {string} 'true' or 'false'.
		 */
		get trackPaused() {
			const context = getContext();
			return context.paused || context.hovering ? 'true' : 'false';
		},
	},

	actions: {
		/**
		 * Move back a page.
		 */
		prev() {
			const { ref } = getElement();
			advance( rootFor( ref ), getContext(), -1 );
		},

		/**
		 * Move on a page.
		 */
		next() {
			const { ref } = getElement();
			advance( rootFor( ref ), getContext(), 1 );
		},

		/**
		 * Jump to the page named by the clicked dot.
		 */
		goTo() {
			const context = getContext();
			const root = rootFor( getElement().ref );
			const track = trackIn( root );
			if ( ! track ) {
				return;
			}
			const perView = visibleCount( track, context.perView );
			context.pages = Math.max( 1, Math.ceil( context.total / perView ) );
			context.current = scrollToPage( track, context.index || 0, perView );
		},

		/**
		 * Keep the controls in step when the track is scrolled or swiped directly.
		 *
		 * Skipped while a click-triggered scroll is still travelling
		 * (`_dvdmSettling`): reading the track's position mid-animation would
		 * read a page short of where the click was actually headed, and this
		 * handler would confidently overwrite the correct value with a stale
		 * one. This only exists to catch direct touch and trackpad scrolling,
		 * which never sets that flag in the first place.
		 */
		onScroll() {
			const context = getContext();
			const { ref } = getElement();

			if ( ref._dvdmSettling ) {
				return;
			}

			window.clearTimeout( ref._dvdmScrollTimer );
			ref._dvdmScrollTimer = window.setTimeout( () => {
				if ( ref._dvdmSettling ) {
					return;
				}
				const perView = visibleCount( ref, context.perView );
				context.pages = Math.max( 1, Math.ceil( context.total / perView ) );
				context.current = Math.min(
					context.pages - 1,
					Math.floor( nearestSlide( ref ) / perView )
				);
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
			const { ref } = getElement();
			advance( rootFor( ref ), getContext(), event.key === 'ArrowRight' ? 1 : -1 );
		},

		/**
		 * Pause or resume. Required for any motion that runs past five seconds,
		 * and shared by the slider's autoplay and the marquee's drift.
		 */
		togglePause() {
			const context = getContext();
			const root = rootFor( getElement().ref );
			context.paused = ! context.paused;

			if ( context.paused ) {
				clearTimer( root );
			} else if ( root ) {
				startTimer( root, context );
			}
		},

		/**
		 * Stop the marquee's drift the moment a pointer or keyboard focus
		 * enters it, independent of the CSS :hover / :focus-within rule that
		 * does the same thing visually. A Read More link only sits still long
		 * enough to click once the drift has actually stopped, and driving
		 * that from script rather than trusting CSS alone means it keeps
		 * working even against a theme whose own styles happen to touch
		 * animation-play-state with a rule that outranks a plain :hover.
		 */
		marqueeHoverStart() {
			getContext().hovering = true;
		},

		/**
		 * Resume the marquee's drift once the pointer or focus leaves it,
		 * unless it was already explicitly paused by its own button.
		 */
		marqueeHoverEnd() {
			getContext().hovering = false;
		},

		/**
		 * Stop advancing as soon as anyone shows an interest.
		 *
		 * Motion that keeps moving while someone is reading, or that carries the
		 * slide out from under a focused control, is worse than no motion.
		 */
		stopAutoplay() {
			clearTimer( rootFor( getElement().ref ) );
		},

		/**
		 * Open the full quote in a dialog.
		 *
		 * The card already holds the whole quote - it is only trimmed in CSS - so
		 * the markup is moved here rather than the server printing it twice. With
		 * scripting unavailable the same control is an ordinary link to the
		 * testimonial, which is why the default is only prevented once the dialog
		 * is known to exist.
		 *
		 * @param {MouseEvent} event Click event.
		 */
		openDialog( event ) {
			const { ref } = getElement();
			const root = rootFor( ref );
			const item = ref.closest( '.dvdm-t__item' );
			const dialog = root ? root.querySelector( '.dvdm-t__dialog' ) : null;

			if ( ! dialog || ! item || typeof dialog.showModal !== 'function' ) {
				return;
			}
			event.preventDefault();

			const body = dialog.querySelector( '.dvdm-t__dialog-body' );
			body.replaceChildren();

			[
				'.dvdm-t__rating',
				'.dvdm-t__quote',
				'.dvdm-t__metric',
				'.dvdm-t__attribution',
			].forEach( ( selector ) => {
				const part = item.querySelector( selector );
				if ( part ) {
					body.appendChild( part.cloneNode( true ) );
				}
			} );

			clearTimer( root );
			dialog.showModal();
		},

		/**
		 * Close the dialog from its own button.
		 */
		closeDialog() {
			const root = rootFor( getElement().ref );
			const dialog = root ? root.querySelector( '.dvdm-t__dialog' ) : null;
			if ( dialog ) {
				dialog.close();
			}
		},

		/**
		 * Close when the backdrop is clicked, which people expect of a modal.
		 *
		 * @param {MouseEvent} event Click event.
		 */
		dialogClickOut( event ) {
			if ( event.target === event.currentTarget ) {
				event.currentTarget.close();
			}
		},

		/**
		 * Drop the copied markup once the dialog is closed, however it closed.
		 */
		dialogClosed() {
			const { ref } = getElement();
			const body = ref.querySelector( '.dvdm-t__dialog-body' );
			if ( body ) {
				body.replaceChildren();
			}
		},
	},

	callbacks: {
		/**
		 * Settle the page count against what is really on screen, then start
		 * autoplay if it was asked for.
		 */
		initSlider() {
			const context = getContext();
			const { ref } = getElement();
			const track = trackIn( ref );
			if ( ! track ) {
				return;
			}

			const sync = () => {
				const perView = visibleCount( track, context.perView );
				context.total = track.children.length;
				context.pages = Math.max( 1, Math.ceil( context.total / perView ) );
				if ( context.current > context.pages - 1 ) {
					context.current = context.pages - 1;
				}
			};

			sync();
			window.addEventListener( 'resize', sync, { passive: true } );

			// The definitive end-of-scroll signal, clearing the settling flag
			// scrollToPage sets before a click-triggered smooth scroll starts.
			// The scrollToPage's own timeout is the fallback for a browser that
			// never fires this.
			track.addEventListener(
				'scrollend',
				() => {
					window.clearTimeout( track._dvdmSettleFallback );
					track._dvdmSettling = false;
				},
				{ passive: true }
			);

			startTimer( ref, context );
		},
	},
} );
