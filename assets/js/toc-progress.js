( function () {
	'use strict';

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part vii of xi
 * ====================================================================
 *  The goblin stared at him. Then he laughed. The other goblins
 *  laughed.
 *
 *    Erryn floated beside Marf. "You have successfully lowered their
 *    guard."
 *    "That was the plan."
 *    "It clearly was not."
 *
 *  The goblin chief swung his axe. Marf ducked. The blade struck the
 *  cart, split the axle and sent the chief tumbling backwards into
 *  the village fountain, where his armour wedged firmly between two
 *  decorative stone fish. There was a long silence.
 *
 *    [ Goblin Chief Immobilised ]
 *
 *  -> continues in assets/js/admin.js
 * ====================================================================
 */

	var initialized = false;

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function init() {
		if ( initialized ) {
			return;
		}

		var nav = document.querySelector( '[data-rptoc]' );
		if ( ! nav ) {
			return;
		}

		var pills = Array.prototype.slice.call( nav.querySelectorAll( '.rptoc__pill' ) );
		if ( ! pills.length ) {
			return;
		}

		initialized = true;

		// Some theme/block ancestor may apply a transform, filter, or
		// contain, any of which silently turns position: fixed into
		// "fixed relative to that ancestor" instead of the viewport. Moving
		// the widget to be a direct child of <body> sidesteps that
		// regardless of what CSS any current or future block applies
		// upstream. Done before anything else so all later measurements
		// (offsetLeft, getBoundingClientRect, etc.) reflect its final home.
		if ( nav.parentElement !== document.body ) {
			document.body.appendChild( nav );
		}

		var fill  = nav.querySelector( '[data-rptoc-fill]' );
		var track = nav.querySelector( '[data-rptoc-track]' );

		// Rail layout: the Medium-style edge minimap. All of this sleeps
		// when the layout is the bar, and also sleeps when the rail has
		// fallen back to the bar below the mobile breakpoint.
		var isRail     = nav.classList.contains( 'rptoc--rail' );
		var railToggle = nav.querySelector( '[data-rptoc-rail-toggle]' );
		var railPanel  = nav.querySelector( '[data-rptoc-panel]' );
		var railClose  = function () {};

		// Ticks, indexed by the heading id each points at, so the active-
		// section observer can light the right one without a DOM search.
		var tickMap = {};
		if ( isRail ) {
			Array.prototype.forEach.call( nav.querySelectorAll( '.rptoc__tick' ), function ( t ) {
				var tid = t.getAttribute( 'data-target' );
				if ( tid ) {
					tickMap[ tid ] = t;
				}
			} );
		}

		// Single place that moves the active state, so the observer, the
		// initial-state seed, and any future caller light the pill and its
		// matching tick the same way. Returns early when the target is
		// already active: the flicker guard near the top of the page where
		// entries can fire in quick succession.
		var activeId = null;

		function markActive( pill ) {
			if ( ! pill ) {
				return;
			}
			var id = pill.getAttribute( 'data-target' );
			if ( id === activeId ) {
				return;
			}
			activeId = id;

			pills.forEach( function ( p ) {
				var on = ( p === pill );
				p.classList.toggle( 'is-active', on );
				// aria-current tells a screen reader which section is the
				// current one, matching the visual highlight. "location" is
				// the precise token; assistive tech that doesn't know it
				// treats any value as current, so nothing is lost.
				if ( on ) {
					p.setAttribute( 'aria-current', 'location' );
				} else {
					p.removeAttribute( 'aria-current' );
				}
			} );

			if ( isRail ) {
				Object.keys( tickMap ).forEach( function ( k ) {
					tickMap[ k ].classList.toggle( 'is-active', k === id );
				} );
			}
		}

		// Below this width the rail is not shown: no hover to reveal a
		// popover, and an edge rail crowds the text on a phone, so it
		// becomes the bar instead. Held as a matchMedia so crossing the
		// breakpoint on resize flips behaviour without a width-watching
		// scroll listener.
		var narrow = window.matchMedia ?
			window.matchMedia( '(max-width: 689px)' ) :
			{ matches: false, addEventListener: null, addListener: null };

		function railActive() {
			return isRail && ! narrow.matches;
		}

		// The progress fill is hidden in the desktop rail, so writing to it
		// there is wasted work. Skipping it is exactly what lets rail mode
		// claim no per-frame scroll cost; the bar and the mobile fallback
		// still update every frame.
		function progressActive() {
			return ! railActive();
		}

		// headingMap holds everything the scroll/observer callbacks need,
		// so those callbacks never touch getBoundingClientRect() themselves.
		var headingMap = {};
		var i, id, headingEl;

		for ( i = 0; i < pills.length; i++ ) {
			id = pills[ i ].getAttribute( 'data-target' );
			headingEl = document.getElementById( id );
			if ( headingEl ) {
				headingMap[ id ] = { el: headingEl, pill: pills[ i ], top: 0 };
			}
		}

		var articleStart = 0;
		var articleEnd   = 0;

		// Content element resolution. An admin-supplied selector wins when
		// it is valid and actually matches something; anything else (an
		// empty setting, a malformed selector, a selector that matches no
		// element on this template) falls through to the built-in guesses.
		// The try/catch matters: querySelector throws on an invalid
		// selector, and a thrown error here would otherwise take the whole
		// widget down.
		var contentSelector = ( window.rptocSettings && window.rptocSettings.contentSelector ) || '';

		function resolveContentEl() {
			if ( contentSelector ) {
				try {
					var custom = document.querySelector( contentSelector );
					if ( custom ) {
						return custom;
					}
				} catch ( e ) {
					// Malformed selector: ignore and fall back.
				}
			}
			return document.querySelector( 'main, article, .entry-content, #content' ) || document.body;
		}

		// The only place getBoundingClientRect() is called for progress
		// purposes: on load and on resize, never inside the scroll handler.
		function measure() {
			var docHeight      = document.documentElement.scrollHeight;
			var viewportHeight = window.innerHeight;
			var firstTop       = null;

			Object.keys( headingMap ).forEach( function ( key ) {
				var rect = headingMap[ key ].el.getBoundingClientRect();
				var top  = rect.top + window.pageYOffset;
				headingMap[ key ].top = top;
				if ( firstTop === null || top < firstTop ) {
					firstTop = top;
				}
			} );

			articleStart = firstTop !== null ? firstTop : 0;
			articleEnd   = docHeight - viewportHeight;
			if ( articleEnd <= articleStart ) {
				articleEnd = articleStart + 1;
			}
		}

		measure();

		/* -------------------------------------------------- Theme matching */

		// Colour roles set to "match the theme" are resolved here rather than
		// server-side, for the reason the whole colour system exists: the
		// server has no idea what the theme actually paints, and no idea which
		// way a dark/light toggle is currently flipped. So read what the page
		// really renders, and re-read it whenever that changes.
		var autoColors = ( window.rptocSettings && window.rptocSettings.autoColors ) || {};

		function opaqueBackground( el ) {
			// Walk up until something declares a background that is not
			// transparent. A theme that sets its background on <body> and a
			// theme that sets it on a wrapper div both end up answering.
			var node = el;

			while ( node && node !== document.documentElement.parentNode ) {
				var bg = window.getComputedStyle( node ).backgroundColor;

				if ( bg && bg !== 'transparent' && bg.indexOf( 'rgba(0, 0, 0, 0)' ) === -1 ) {
					return bg;
				}

				node = node.parentElement;
			}

			return window.getComputedStyle( document.body ).backgroundColor;
		}

		function applyAutoColors() {
			if ( ! Object.keys( autoColors ).length ) {
				return;
			}

			var contentEl = resolveContentEl();
			var bodyStyle = window.getComputedStyle( document.body );

			// Accent: the theme's own link colour, taken from a real link in the
			// content where possible. A brand accent the reader already
			// associates with "this is clickable" beats anything invented here.
			var linkEl     = contentEl.querySelector( 'a[href]:not( .rptoc__pill )' );
			var linkColour = linkEl ? window.getComputedStyle( linkEl ).color : bodyStyle.color;

			var resolved = {
				bg: opaqueBackground( contentEl ),
				fg: bodyStyle.color,
				accent: linkColour,
				accent_contrast: opaqueBackground( contentEl )
			};

			Object.keys( autoColors ).forEach( function ( role ) {
				var spec  = autoColors[ role ];
				var value = resolved[ role ];

				if ( ! spec || ! value ) {
					return;
				}

				if ( spec.opacity < 100 ) {
					value = 'color-mix(in srgb, ' + value + ' ' + spec.opacity + '%, transparent)';
				}

				nav.style.setProperty( spec.var, value );
			} );
		}

		applyAutoColors();

		// One read at load is not always enough. On mobile especially, a
		// theme's full stylesheet, its web fonts, or a host's deferred and
		// critical-CSS swap can land a beat after load, so a single early
		// read samples the pre-theme state and leaves the bar on its neutral
		// fallback (the dark background and cyan accent). Re-reading a few
		// times, and once fonts settle, corrects that without costing
		// anything per scroll frame: these are one-off timers, not a loop.
		if ( Object.keys( autoColors ).length ) {
			[ 150, 500, 1200 ].forEach( function ( delay ) {
				window.setTimeout( applyAutoColors, delay );
			} );

			if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
				document.fonts.ready.then( applyAutoColors );
			}
		}

		// A dark-mode switch usually shows up as one of these: the OS-level
		// preference changing, or a theme toggling a class or data attribute on
		// <html> or <body>. Both are cheap to watch and neither costs anything
		// per scroll frame.
		if ( window.matchMedia ) {
			var scheme = window.matchMedia( '(prefers-color-scheme: dark)' );
			var onScheme = function () {
				window.setTimeout( applyAutoColors, 50 );
			};

			if ( scheme.addEventListener ) {
				scheme.addEventListener( 'change', onScheme );
			} else if ( scheme.addListener ) {
				scheme.addListener( onScheme );
			}
		}

		if ( 'MutationObserver' in window && Object.keys( autoColors ).length ) {
			var themeTimer = null;
			var themeObserver = new MutationObserver( function () {
				// Debounced: a theme toggling several attributes at once should
				// cost one re-read, not one per attribute.
				if ( themeTimer ) {
					window.clearTimeout( themeTimer );
				}
				themeTimer = window.setTimeout( applyAutoColors, 60 );
			} );

			var observerConfig = { attributes: true, attributeFilter: [ 'class', 'data-theme', 'data-color-scheme' ] };

			themeObserver.observe( document.documentElement, observerConfig );
			themeObserver.observe( document.body, observerConfig );
		}

		var resizeTimer = null;
		window.addEventListener(
			'resize',
			function () {
				if ( resizeTimer ) {
					clearTimeout( resizeTimer );
				}
				resizeTimer = setTimeout( measure, 150 );
			},
			{ passive: true }
		);

		// Progress fill: rAF-throttled, arithmetic-only, compositor-only write.
		var ticking = false;

		function updateProgress() {
			ticking = false;

			if ( ! progressActive() ) {
				return;
			}

			var scrollY  = window.pageYOffset;
			var progress = ( scrollY - articleStart ) / ( articleEnd - articleStart );

			if ( progress < 0 ) {
				progress = 0;
			} else if ( progress > 1 ) {
				progress = 1;
			}

			if ( fill ) {
				fill.style.transform = 'scaleX(' + progress + ')';
			}
		}

		function onScroll() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( updateProgress );
			}
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		updateProgress();

		// Crossing the breakpoint flips the rail between minimap and bar.
		// Coming back to the bar, refresh the fill it was not maintaining;
		// going to the rail, make sure the popover is not left open.
		function onBreakpoint() {
			if ( progressActive() ) {
				updateProgress();
			} else {
				railClose();
			}
		}

		if ( narrow.addEventListener ) {
			narrow.addEventListener( 'change', onBreakpoint );
		} else if ( narrow.addListener ) {
			narrow.addListener( onBreakpoint );
		}

		// Active section: IntersectionObserver, no scroll-position maths at all.
		if ( 'IntersectionObserver' in window ) {
			var observer = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( ! entry.isIntersecting ) {
							return;
						}

						var activePill = null;
						Object.keys( headingMap ).forEach( function ( key ) {
							if ( headingMap[ key ].el === entry.target ) {
								activePill = headingMap[ key ].pill;
							}
						} );

						if ( ! activePill ) {
							return;
						}

						markActive( activePill );

						// Horizontal autoscroll belongs to the bar, and to the rail's
						// mobile fallback, which is the bar. In the desktop rail the list
						// is a vertical popover, handled by the nudge below instead.
						if ( track && progressActive() ) {
							// Relative (scrollBy) rather than absolute (scrollTo), and
							// measured from bounding rects rather than offsetLeft/scrollLeft.
							// Under RTL, scrollLeft runs from -maxScroll to 0 per the CSSOM
							// spec, so the old clamp-at-zero maths pinned the track at one
							// end and never moved. A delta needs no sign handling, and the
							// browser clamps it to the scrollable range.
							var pillRect  = activePill.getBoundingClientRect();
							var trackRect = track.getBoundingClientRect();

							if ( pillRect.left < trackRect.left || pillRect.right > trackRect.right ) {
								// Anchor the active pill according to the chosen
								// alignment rather than always centring it. On a list
								// wide enough to overflow, justify-content cannot show
								// through, so this is where "start" and "end" actually
								// become visible: the active pill lands at the near
								// edge, the far edge, or the middle. A 16px margin keeps
								// it off the very edge, under the fade mask.
								var EDGE  = 16;
								var delta;

								if ( nav.classList.contains( 'rptoc--align-left' ) ) {
									delta = pillRect.left - ( trackRect.left + EDGE );
								} else if ( nav.classList.contains( 'rptoc--align-right' ) ) {
									delta = pillRect.right - ( trackRect.right - EDGE );
								} else {
									delta = ( pillRect.left + pillRect.width / 2 ) -
										( trackRect.left + trackRect.width / 2 );
								}

								// Scoped to the track itself, deliberately not
								// scrollIntoView(): that can walk up and scroll an ancestor
								// other than this track, including the page itself.
								track.scrollBy( { left: delta, behavior: 'smooth' } );
							}
						}

						// Desktop rail with the popover open: keep the active row in
						// view by nudging the popover vertically, the same idea as the
						// bar's horizontal nudge.
						if ( railActive() && railPanel && nav.classList.contains( 'is-open' ) ) {
							var rowRect = activePill.getBoundingClientRect();
							var panRect = railPanel.getBoundingClientRect();

							if ( rowRect.top < panRect.top || rowRect.bottom > panRect.bottom ) {
								railPanel.scrollBy( {
									top: ( rowRect.top + rowRect.height / 2 ) - ( panRect.top + panRect.height / 2 ),
									behavior: 'smooth'
								} );
							}
						}
					} );
				},
				{
					rootMargin: '-10% 0px -70% 0px',
					threshold: 0
				}
			);

			Object.keys( headingMap ).forEach( function ( key ) {
				observer.observe( headingMap[ key ].el );
			} );
		}

		// Initial active state. Before the reader has scrolled to the first
		// section, the first link reads as active rather than nothing being
		// lit. If the page was opened on a specific anchor, that section
		// wins instead, so a deep link does not flash the first section
		// first. The observer takes over from here on scroll, and
		// markActive's guard keeps this from fighting it.
		( function seedActive() {
			var hash = window.location.hash ? window.location.hash.slice( 1 ) : '';
			var target = null;

			if ( hash ) {
				pills.some( function ( p ) {
					if ( p.getAttribute( 'data-target' ) === hash ) {
						target = p;
						return true;
					}
					return false;
				} );
			}

			markActive( target || pills[ 0 ] );
		} )();

		// Rail popover: a disclosure. The button opens a panel of links,
		// so it needs to open on hover for pointers, on focus and Enter or
		// Space for keyboards, close on Escape with focus returned, and
		// close on focus leaving or a click outside. Hover alone would be
		// an accessibility trap; a keyboard user could never reach the
		// links.
		if ( isRail && railToggle && railPanel ) {
			var closeTimer = null;

			var cancelClose = function () {
				if ( closeTimer ) {
					window.clearTimeout( closeTimer );
					closeTimer = null;
				}
			};

			var openPanel = function () {
				cancelClose();
				if ( ! railActive() ) {
					return;
				}
				nav.classList.add( 'is-open' );
				railToggle.setAttribute( 'aria-expanded', 'true' );
			};

			railClose = function () {
				cancelClose();
				nav.classList.remove( 'is-open' );
				railToggle.setAttribute( 'aria-expanded', 'false' );
			};

			// There is a small gap between the tick rail and the popover.
			// Closing on the very first mouseleave means a slightly slow
			// cursor loses the panel before it can land on it, so give the
			// pointer a moment to cross. Re-entering anywhere in the widget
			// cancels the pending close.
			var scheduleClose = function () {
				cancelClose();
				closeTimer = window.setTimeout( railClose, 320 );
			};

			nav.addEventListener( 'mouseenter', openPanel );
			nav.addEventListener( 'mouseleave', scheduleClose );

			railToggle.addEventListener( 'click', function () {
				if ( nav.classList.contains( 'is-open' ) ) {
					railClose();
				} else {
					openPanel();
				}
			} );

			// Focus entering the widget opens it so the links become
			// reachable; focus leaving the whole widget closes it.
			nav.addEventListener( 'focusin', openPanel );
			nav.addEventListener( 'focusout', function ( e ) {
				if ( ! nav.contains( e.relatedTarget ) ) {
					railClose();
				}
			} );

			nav.addEventListener( 'keydown', function ( e ) {
				if ( ( e.key === 'Escape' || e.key === 'Esc' ) && nav.classList.contains( 'is-open' ) ) {
					railClose();
					railToggle.focus();
				}
			} );

			// Tapping a row jumps and then dismisses, so the popover does
			// not linger over the destination on touch.
			railPanel.addEventListener( 'click', function ( e ) {
				if ( e.target.closest( '.rptoc__pill' ) ) {
					railClose();
				}
			} );

			document.addEventListener( 'click', function ( e ) {
				if ( nav.classList.contains( 'is-open' ) && ! nav.contains( e.target ) ) {
					railClose();
				}
			} );
		}

		// Header detection. Read once here, never per click and never
		// inside a scroll handler.
		//
		// The previous approach walked a guessed selector list ending in
		// '.header', which matches any element on the page carrying that
		// class: a card header, a table header, a widget. First match won,
		// with no check that the thing found was actually pinned. This asks
		// the browser what is pinned instead of guessing at markup, which is
		// what makes it theme-agnostic rather than Blocksy-shaped with
		// fallbacks bolted on.
		var headerSettings = window.rptocSettings || {};
		var headerEl       = null;
		var headerPinned   = false;

		function isPinned( el ) {
			var pos = window.getComputedStyle( el ).position;
			return pos === 'fixed' || pos === 'sticky';
		}

		function findHeader() {
			// An admin-supplied selector always wins. Someone who has typed a
			// selector into the settings screen has more context than any
			// heuristic, including for headers this would otherwise reject.
			if ( headerSettings.headerSelector ) {
				return document.querySelector( headerSettings.headerSelector );
			}

			// Landmarks first, then the conventional ids and classes as a
			// second tier for themes still shipping div-based headers.
			var candidates = document.querySelectorAll(
				'header, [role="banner"], #masthead, #header, .site-header'
			);

			var best    = null;
			var bestTop = Infinity;
			var k, el, rect;

			for ( k = 0; k < candidates.length; k++ ) {
				el = candidates[ k ];

				// A <header> inside an article, section, aside or list item is
				// a content sub-header, not the site header.
				if ( el.closest( 'article, section, aside, li, .rptoc' ) ) {
					continue;
				}

				// Zero height means hidden, collapsed, or an off-canvas panel
				// that has not been opened.
				if ( ! el.offsetHeight ) {
					continue;
				}

				rect = el.getBoundingClientRect();

				// Must sit in the upper part of the document at load. A footer
				// landmark, or a header further down the page, is not what the
				// dock wants to sit beneath.
				if ( rect.top + window.pageYOffset > window.innerHeight ) {
					continue;
				}

				// Nearest the top wins; at equal offsets, the wider element.
				if ( rect.top < bestTop || ( rect.top === bestTop && best && rect.width > best.getBoundingClientRect().width ) ) {
					best    = el;
					bestTop = rect.top;
				}
			}

			return best;
		}

		headerEl = findHeader();

		if ( headerEl ) {
			headerPinned = isPinned( headerEl );
		}

		// Below-header docking assumes the header stays put. If it scrolls
		// away with the page, the bar sits under where the header used to be
		// and a gap opens as the reader scrolls. Warn rather than silently
		// misbehave; the bar still renders, just at offset zero.
		if ( nav.classList.contains( 'rptoc--dock-header' ) && ! headerPinned ) {
			if ( window.console && window.console.warn ) {
				window.console.warn(
					headerEl ?
						'Reading Progress & Table of Contents: the detected site header is not fixed or sticky, so below-header docking will drift as the page scrolls. Set a Header Selector in Settings, or switch to bottom docking.' :
						'Reading Progress & Table of Contents: no site header could be detected for below-header docking. Set a Header Selector in Settings, or switch to bottom docking.'
				);
			}
		}

		// Header offset for below-header docking.
		//
		// Custom mode uses the configured pixel value directly, which is
		// also the answer when no header could be detected at all. Auto
		// mode measures the detected header's bottom edge in viewport
		// coordinates: that already includes the WordPress admin bar when
		// it is pushing the header down, so the dock never tucks up under
		// the admin bar. An unpinned header offsets nothing, since it does
		// not stay on screen.
		var headerOffsetCfg = ( window.rptocSettings && window.rptocSettings.headerOffset ) || { mode: 'auto', value: null };
		var headerCustom    = ( 'custom' === headerOffsetCfg.mode && null !== headerOffsetCfg.value );

		var headerHeight;
		if ( headerCustom ) {
			headerHeight = headerOffsetCfg.value;
		} else if ( headerEl && headerPinned ) {
			headerHeight = Math.max( 0, headerEl.getBoundingClientRect().bottom );
		} else {
			headerHeight = 0;
		}

		if ( nav.classList.contains( 'rptoc--dock-header' ) ) {
			nav.style.setProperty( '--rptoc-header-h', headerHeight + 'px' );
		}

		// Auto mode keeps --rptoc-header-h live via ResizeObserver, which
		// fires whenever the header's rendered box changes (including a
		// sticky shrink-on-scroll class toggle), without polling scroll
		// position or reading layout ourselves. Custom mode is a fixed
		// number, so it needs no observer.
		if ( ! headerCustom && nav.classList.contains( 'rptoc--dock-header' ) && headerEl && headerPinned && 'ResizeObserver' in window ) {
			var headerObserver = new ResizeObserver( function () {
				var b = Math.max( 0, headerEl.getBoundingClientRect().bottom );
				nav.style.setProperty( '--rptoc-header-h', b + 'px' );
				headerHeight = b;
			} );
			headerObserver.observe( headerEl );
		}

		pills.forEach( function ( pill ) {
			pill.addEventListener( 'click', function ( e ) {
				var target = document.getElementById( pill.getAttribute( 'data-target' ) );
				if ( ! target ) {
					return;
				}
				e.preventDefault();
				var top = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 12;
				window.scrollTo( { top: top, behavior: 'smooth' } );
			} );
		} );
	}

	function deferInit() {
		if ( 'requestIdleCallback' in window ) {
			window.requestIdleCallback( init, { timeout: 2000 } );
		} else {
			setTimeout( init, 200 );
		}
	}

	ready( function () {
		window.addEventListener( 'load', deferInit );
		// Fallback in case 'load' already fired before this script ran.
		setTimeout( deferInit, 3000 );
	} );
} )();
