/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part viii of xi
 * ====================================================================
 *  The remaining goblins looked at their leader. Then at Marf. Then
 *  at Erryn. Erryn projected a glowing red symbol into the air.
 *
 *    WARNING: ADVANCED TACTICAL ENTITY
 *
 *    Marf whispered, "Am I an advanced tactical entity?"
 *    "No. But they don't know that."
 *
 *  The goblins fled.
 *
 *    [ Quest Complete: Save the Village ]
 *    Experience gained:  500
 *    Marf has reached Level 4
 *    New Skill: Accidental Authority. When you appear confident,
 *    weaker enemies may assume you understand the situation.
 *
 *  -> continues in assets/css/toc-progress.css
 * ====================================================================
 */

/**
 * Settings screen behaviour.
 *
 * Vanilla, no build step, matching the frontend script. The only jQuery
 * touched is wpColorPicker, which is a jQuery widget and has no vanilla
 * equivalent in core.
 */
( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	/* ------------------------------------------------------------ Tabs */

	function initTabs( root ) {
		var tabs   = Array.prototype.slice.call( root.querySelectorAll( '[data-rptoc-tab]' ) );
		var panels = Array.prototype.slice.call( root.querySelectorAll( '[data-rptoc-panel]' ) );

		if ( ! tabs.length || ! panels.length ) {
			return;
		}

		function show( name ) {
			tabs.forEach( function ( tab ) {
				var active = tab.getAttribute( 'data-rptoc-tab' ) === name;
				tab.classList.toggle( 'is-active', active );
				tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );

			panels.forEach( function ( panel ) {
				panel.classList.toggle( 'is-active', panel.getAttribute( 'data-rptoc-panel' ) === name );
			} );

			// Survives the redirect back from options.php after saving,
			// so you land on the tab you were editing rather than the
			// first one. sessionStorage, not a URL parameter: nothing
			// here is worth putting in a shareable link.
			try {
				window.sessionStorage.setItem( 'rptocTab', name );
			} catch ( e ) {
				// Private browsing, quota, or storage disabled. The tabs
				// still work, they just forget between saves.
			}
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				show( tab.getAttribute( 'data-rptoc-tab' ) );
			} );
		} );

		// Left/right arrows across the tab strip, per the ARIA tabs
		// pattern; without it a keyboard user has to tab through every
		// control on a panel to reach the next tab.
		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'keydown', function ( e ) {
				var next = null;

				if ( e.key === 'ArrowRight' ) {
					next = tabs[ ( index + 1 ) % tabs.length ];
				} else if ( e.key === 'ArrowLeft' ) {
					next = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
				}

				if ( next ) {
					e.preventDefault();
					next.focus();
					show( next.getAttribute( 'data-rptoc-tab' ) );
				}
			} );
		} );

		var stored = null;
		try {
			stored = window.sessionStorage.getItem( 'rptocTab' );
		} catch ( e ) {
			stored = null;
		}

		if ( stored && root.querySelector( '[data-rptoc-panel="' + stored + '"]' ) ) {
			show( stored );
		}
	}

	/* --------------------------------------------------------- Sliders */

	/**
	 * Binds each range to the number input it names in data-rptoc-pair.
	 * The number field is the one that carries the form name, so the
	 * range is purely a second way to drive the same value and nothing
	 * breaks if it never loads.
	 */
	function initSliders( root ) {
		var ranges = root.querySelectorAll( '[data-rptoc-pair]' );

		Array.prototype.forEach.call( ranges, function ( range ) {
			var number = document.getElementById( range.getAttribute( 'data-rptoc-pair' ) );

			if ( ! number ) {
				return;
			}

			range.addEventListener( 'input', function () {
				number.value = range.value;
			} );

			number.addEventListener( 'input', function () {
				range.value = number.value;
			} );

			// Clamp on blur rather than on every keystroke: clamping mid-
			// typing turns "1" into "10" before you've typed the 5.
			number.addEventListener( 'blur', function () {
				var min = parseFloat( number.min );
				var max = parseFloat( number.max );
				var val = parseFloat( number.value );

				if ( isNaN( val ) ) {
					val = parseFloat( range.value );
				}
				if ( ! isNaN( min ) && val < min ) {
					val = min;
				}
				if ( ! isNaN( max ) && val > max ) {
					val = max;
				}

				number.value = val;
				range.value  = val;
			} );
		} );
	}

	/* ------------------------------------------------ Dependent fields */

	/**
	 * Shows or hides a block of fields based on which radio in a group is
	 * selected. Controls opt in with data-rptoc-toggle (the group name)
	 * and data-rptoc-toggle-when ('on' or 'off'); the dependent block
	 * carries data-rptoc-dependent with the same group name.
	 */
	function initDependents( root ) {
		var controls = Array.prototype.slice.call( root.querySelectorAll( '[data-rptoc-toggle]' ) );
		var groups   = {};

		controls.forEach( function ( control ) {
			var name = control.getAttribute( 'data-rptoc-toggle' );
			if ( ! groups[ name ] ) {
				groups[ name ] = [];
			}
			groups[ name ].push( control );
		} );

		Object.keys( groups ).forEach( function ( name ) {
			var dependents = Array.prototype.slice.call(
				root.querySelectorAll( '[data-rptoc-dependent="' + name + '"]' )
			);

			if ( ! dependents.length ) {
				return;
			}

			function sync() {
				var on = groups[ name ].some( function ( control ) {
					return control.checked && control.getAttribute( 'data-rptoc-toggle-when' ) === 'on';
				} );

				dependents.forEach( function ( el ) {
					el.hidden = ! on;
				} );
			}

			groups[ name ].forEach( function ( control ) {
				control.addEventListener( 'change', sync );
			} );

			sync();
		} );
	}

	/* ------------------------------------------------ Colour source */

	/**
	 * The Colours panel has one global source: follow, palette, or custom.
	 * The chosen mode is written to the container as a data attribute, and
	 * the stylesheet shows the right controls for it (roles hidden under
	 * follow, palette dropdowns under palette, colour pickers under custom).
	 * Keeping the switch in CSS means both a palette slug and a custom hex
	 * stay in the DOM the whole time, so flipping modes never discards the
	 * other mode's value.
	 */
	function initColors( root ) {
		var container = root.querySelector( '[data-rptoc-colors]' );

		if ( ! container ) {
			return;
		}

		var modeInputs = root.querySelectorAll( '[data-rptoc-color-mode]' );

		function currentMode() {
			var chosen = 'follow';
			Array.prototype.forEach.call( modeInputs, function ( input ) {
				if ( input.checked ) {
					chosen = input.value;
				}
			} );
			return chosen;
		}

		function syncMode() {
			container.setAttribute( 'data-mode', currentMode() );
			updateContrast();
		}

		Array.prototype.forEach.call( modeInputs, function ( input ) {
			input.addEventListener( 'change', syncMode );
		} );

		/* ------------------------------------------------ Contrast */

		// Advisory WCAG contrast, and only where a definite value exists:
		// custom mode, where each role is an explicit hex. In palette mode
		// the colour is a CSS variable the admin usually cannot resolve, so
		// rather than show a misleading number the check stays silent, per
		// the spec.
		function hexToRgb( hex ) {
			var h = ( hex || '' ).replace( '#', '' ).trim();
			if ( h.length === 3 ) {
				h = h[ 0 ] + h[ 0 ] + h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ];
			}
			if ( ! /^[0-9a-fA-F]{6}$/.test( h ) ) {
				return null;
			}
			return {
				r: parseInt( h.slice( 0, 2 ), 16 ),
				g: parseInt( h.slice( 2, 4 ), 16 ),
				b: parseInt( h.slice( 4, 6 ), 16 )
			};
		}

		function channel( c ) {
			c = c / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
		}

		function luminance( rgb ) {
			return 0.2126 * channel( rgb.r ) + 0.7152 * channel( rgb.g ) + 0.0722 * channel( rgb.b );
		}

		function ratio( hexA, hexB ) {
			var a = hexToRgb( hexA );
			var b = hexToRgb( hexB );
			if ( ! a || ! b ) {
				return null;
			}
			var la = luminance( a );
			var lb = luminance( b );
			var hi = Math.max( la, lb );
			var lo = Math.min( la, lb );
			return ( hi + 0.05 ) / ( lo + 0.05 );
		}

		function roleHex( role ) {
			var field = container.querySelector( '[data-rptoc-role="' + role + '"] [data-rptoc-hex]' );
			return field ? field.value : '';
		}

		function setNote( role, message ) {
			var note = container.querySelector( '[data-rptoc-contrast="' + role + '"]' );
			if ( ! note ) {
				return;
			}
			if ( message ) {
				note.textContent = message;
				note.hidden = false;
			} else {
				note.textContent = '';
				note.hidden = true;
			}
		}

		// Below AA for normal text. Advisory only: never blocks saving,
		// never changes a colour, clears itself once the ratio is fine.
		var THRESHOLD = 4.5;

		function checkPair( roleA, roleB, targetRole, label ) {
			if ( 'custom' !== currentMode() ) {
				setNote( targetRole, '' );
				return;
			}
			var r = ratio( roleHex( roleA ), roleHex( roleB ) );
			if ( r !== null && r < THRESHOLD ) {
				setNote( targetRole, label + ': ' + r.toFixed( 1 ) + ':1' );
			} else {
				setNote( targetRole, '' );
			}
		}

		function updateContrast() {
			checkPair( 'fg', 'bg', 'fg', 'Text against background may be difficult to read' );
			checkPair( 'accent_contrast', 'accent', 'accent_contrast', 'Accent contrast against accent may be difficult to read' );
		}

		Array.prototype.forEach.call( container.querySelectorAll( '[data-rptoc-hex], [data-rptoc-pair]' ), function ( input ) {
			input.addEventListener( 'input', updateContrast );
			input.addEventListener( 'change', updateContrast );
		} );

		syncMode();

		// Expose so the colour-picker change handler can refresh contrast.
		root.rptocUpdateContrast = updateContrast;
	}

	/* --------------------------------------------------------- Boot */

	ready( function () {
		var root = document.querySelector( '.rptoc-admin' );

		if ( ! root ) {
			return;
		}

		// Signals to the stylesheet that tab switching is available;
		// until this lands, every panel is visible.
		root.classList.add( 'is-enhanced' );

		initTabs( root );
		initSliders( root );
		initDependents( root );
		initColors( root );

		// wpColorPicker is a jQuery widget; there is no core vanilla
		// equivalent, so this is the one place jQuery is used. Its change
		// and clear both feed the contrast check.
		if ( window.jQuery && window.jQuery.fn && window.jQuery.fn.wpColorPicker ) {
			window.jQuery( '.rptoc-color-field' ).wpColorPicker( {
				change: function () {
					if ( root.rptocUpdateContrast ) {
						// The input's value updates a tick after this fires.
						window.setTimeout( root.rptocUpdateContrast, 0 );
					}
				},
				clear: function () {
					if ( root.rptocUpdateContrast ) {
						window.setTimeout( root.rptocUpdateContrast, 0 );
					}
				}
			} );
		}
	} );
} )();
