/**
 * Devadigm Testimonials - settings screen.
 *
 * Each colour and font field is three inputs working together: a select for
 * "inherit or a theme preset", a free-text field for a custom value, and a
 * hidden field carrying whichever one is active. Only the hidden field is
 * submitted, so the stored value is always a single clean string.
 */

( function ( $ ) {
	'use strict';

	/**
	 * Wire one paired control.
	 *
	 * @param {jQuery} $cell   The table cell holding the group.
	 * @param {string} kind    Either 'colour' or 'font'.
	 * @param {Function} onCustomInit Optional setup for the custom field.
	 */
	function wire( $cell, kind, onCustomInit ) {
		var $source = $cell.find( '.dvdm-' + kind + '-source' );
		var $custom = $cell.find( '.dvdm-' + kind + '-custom' );
		var $value = $cell.find( '.dvdm-' + kind + '-value' );

		/**
		 * Push the active input into the hidden field.
		 */
		function sync() {
			if ( $source.val() === '__custom' ) {
				$value.val( $custom.val() || '' );
			} else {
				$value.val( $source.val() || '' );
			}
		}

		$source.on( 'change', function () {
			var custom = $source.val() === '__custom';
			$custom.prop( 'disabled', ! custom );
			$cell.toggleClass( 'is-custom', custom );

			if ( custom && onCustomInit ) {
				onCustomInit( $custom );
			}
			sync();
		} );

		$custom.on( 'change keyup', sync );

		if ( $source.val() === '__custom' ) {
			$cell.addClass( 'is-custom' );
			if ( onCustomInit ) {
				onCustomInit( $custom );
			}
		}

		return sync;
	}

	$( function () {
		/*
		 * The less common fields live inside a collapsed <details> (see
		 * class-settings.php). A colour field already set to a custom value
		 * still needs wiring on page load - wpColorPicker() just cannot
		 * actually build the widget yet: Iris measures the container's width
		 * to lay itself out, and a closed <details> reports zero for
		 * anything inside it, leaving the picker with a collapsed, broken
		 * layout even after the section is opened. Building it the moment
		 * its own <details> opens instead avoids ever measuring a hidden
		 * container.
		 */
		$( '.dvdm-colour-cell' ).each( function () {
			var $cell = $( this );
			var $details = $cell.closest( '.dvdm-more' );
			var built = false;

			function build( $custom ) {
				if ( built || ( $details.length && ! $details.prop( 'open' ) ) ) {
					return;
				}
				built = true;

				$custom.wpColorPicker( {
					change: function ( event, ui ) {
						$cell.find( '.dvdm-colour-value' ).val( ui.color.toString() );
					},
					clear: function () {
						$cell.find( '.dvdm-colour-value' ).val( '' );
					},
				} );
			}

			var sync = wire( $cell, 'colour', build );
			sync();

			if ( $details.length ) {
				$details.on( 'toggle', function () {
					if ( this.open ) {
						build( $cell.find( '.dvdm-colour-custom' ) );
					}
				} );
			}
		} );

		$( '.dvdm-font-cell' ).each( function () {
			wire( $( this ), 'font' )();
		} );
	} );
} )( window.jQuery );
