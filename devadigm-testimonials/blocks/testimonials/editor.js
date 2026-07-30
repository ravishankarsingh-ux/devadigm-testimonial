/**
 * Devadigm Testimonials - block editor.
 *
 * Written against wp.element.createElement rather than JSX so the plugin ships
 * as a zip with no build step. Dependencies are declared in editor.asset.php.
 */

( function ( wp, data ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var Notice = wp.components.Notice;
	var ExternalLink = wp.components.ExternalLink;

	var settings = data || {};

	/**
	 * Build the option list for a colour override: theme default, any theme
	 * preset, or a custom value typed in below.
	 *
	 * @return {Array} Option objects for SelectControl.
	 */
	function colourOptions() {
		var options = [ { label: __( 'Theme default', 'devadigm-testimonials' ), value: '' } ];

		( settings.palette || [] ).forEach( function ( entry ) {
			options.push( {
				label: entry.name + ' (' + entry.color + ')',
				value: 'preset:' + entry.slug,
			} );
		} );

		options.push( { label: __( 'Custom', 'devadigm-testimonials' ), value: '__custom' } );
		return options;
	}

	/**
	 * Build the option list for a typeface override.
	 *
	 * @return {Array} Option objects for SelectControl.
	 */
	function fontOptions() {
		var options = [ { label: __( 'Theme default', 'devadigm-testimonials' ), value: '' } ];

		( settings.fonts || [] ).forEach( function ( entry ) {
			options.push( { label: entry.name, value: 'preset:' + entry.slug } );
		} );

		options.push( { label: __( 'Custom stack', 'devadigm-testimonials' ), value: '__custom' } );
		return options;
	}

	/**
	 * A paired select and free-text control, so a value can come from the theme
	 * palette or be typed in directly.
	 *
	 * @param {Object}   props           Control props.
	 * @param {string}   props.label     Field label.
	 * @param {string}   props.value     Current value.
	 * @param {Function} props.onChange  Change handler.
	 * @param {Array}    props.options   Select options.
	 * @param {string}   props.placeholder Placeholder for the custom field.
	 * @return {Object} Element tree.
	 */
	function PairedControl( props ) {
		var isCustom = props.value !== '' && props.value.indexOf( 'preset:' ) !== 0;

		return el(
			Fragment,
			null,
			el( SelectControl, {
				label: props.label,
				value: isCustom ? '__custom' : props.value,
				options: props.options,
				__nextHasNoMarginBottom: true,
				onChange: function ( next ) {
					props.onChange( next === '__custom' ? ' ' : next );
				},
			} ),
			isCustom
				? el( TextControl, {
						value: props.value.trim(),
						placeholder: props.placeholder,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							props.onChange( next === '' ? ' ' : next );
						},
				  } )
				: null
		);
	}

	wp.blocks.registerBlockType( 'devadigm/testimonials', {
		/**
		 * Editor view: a live server render plus the query and appearance panels.
		 *
		 * @param {Object}   props               Block props.
		 * @param {Object}   props.attributes    Current attributes.
		 * @param {Function} props.setAttributes Attribute setter.
		 * @return {Object} Element tree.
		 */
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps();

			var layout = a.layout || settings.defaultLayout || 'spotlight';
			var mark = a.markStyle || settings.defaultMark || 'ledger';

			var isMultiple = [ 'row', 'wall', 'marquee', 'slideshow' ].indexOf( layout ) !== -1;
			var hasColumns = [ 'row', 'wall' ].indexOf( layout ) !== -1;

			var inspector = el(
				InspectorControls,
				null,

				el(
					PanelBody,
					{ title: __( 'Layout', 'devadigm-testimonials' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Display as', 'devadigm-testimonials' ),
						value: layout,
						options: settings.layouts || [],
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { layout: next } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Quotation mark', 'devadigm-testimonials' ),
						value: mark,
						options: settings.markStyles || [],
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { markStyle: next } );
						},
					} ),
					isMultiple
						? el( RangeControl, {
								label: __( 'How many', 'devadigm-testimonials' ),
								value: a.count,
								min: 1,
								max: 24,
								__nextHasNoMarginBottom: true,
								onChange: function ( next ) {
									set( { count: next } );
								},
						  } )
						: null,
					hasColumns
						? el( RangeControl, {
								label: __( 'Columns', 'devadigm-testimonials' ),
								value: a.columns,
								min: 1,
								max: 6,
								__nextHasNoMarginBottom: true,
								onChange: function ( next ) {
									set( { columns: next } );
								},
						  } )
						: null
				),

				el(
					PanelBody,
					{ title: __( 'Which testimonials', 'devadigm-testimonials' ), initialOpen: false },
					el( SelectControl, {
						label: __( 'Service line', 'devadigm-testimonials' ),
						value: a.service,
						options: settings.services || [],
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { service: next } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Source', 'devadigm-testimonials' ),
						value: a.source,
						options: settings.sources || [],
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { source: next } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Minimum rating', 'devadigm-testimonials' ),
						value: a.minRating,
						min: 0,
						max: 5,
						step: 0.5,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { minRating: next } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Featured only', 'devadigm-testimonials' ),
						checked: !! a.featuredOnly,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { featuredOnly: next } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Order by', 'devadigm-testimonials' ),
						value: a.order,
						options: [
							{ label: __( 'Newest first', 'devadigm-testimonials' ), value: 'newest' },
							{ label: __( 'Highest rated', 'devadigm-testimonials' ), value: 'rating' },
							{ label: __( 'Random', 'devadigm-testimonials' ), value: 'random' },
							{ label: __( 'Manual order', 'devadigm-testimonials' ), value: 'manual' },
						],
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { order: next } );
						},
					} )
				),

				el(
					PanelBody,
					{ title: __( 'Parts', 'devadigm-testimonials' ), initialOpen: false },
					el( ToggleControl, {
						label: __( 'Show rating', 'devadigm-testimonials' ),
						checked: !! a.showRating,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { showRating: next } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Show avatar', 'devadigm-testimonials' ),
						checked: !! a.showAvatar,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { showAvatar: next } );
						},
					} )
				),

				el(
					PanelBody,
					{ title: __( 'Appearance for this block', 'devadigm-testimonials' ), initialOpen: false },
					el(
						Notice,
						{ status: 'info', isDismissible: false },
						__( 'These override the site-wide design settings for this block only.', 'devadigm-testimonials' ),
						' ',
						settings.settingsUrl
							? el(
									ExternalLink,
									{ href: settings.settingsUrl },
									__( 'Site defaults', 'devadigm-testimonials' )
							  )
							: null
					),
					el( PairedControl, {
						label: __( 'Accent', 'devadigm-testimonials' ),
						value: a.accentColor || '',
						options: colourOptions(),
						placeholder: '#b08d57',
						onChange: function ( next ) {
							set( { accentColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Quote text', 'devadigm-testimonials' ),
						value: a.quoteColor || '',
						options: colourOptions(),
						placeholder: '#14161c',
						onChange: function ( next ) {
							set( { quoteColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Name', 'devadigm-testimonials' ),
						value: a.nameColor || '',
						options: colourOptions(),
						placeholder: '#14161c',
						onChange: function ( next ) {
							set( { nameColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Role and company', 'devadigm-testimonials' ),
						value: a.metaColor || '',
						options: colourOptions(),
						placeholder: '#6b675e',
						onChange: function ( next ) {
							set( { metaColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Card background', 'devadigm-testimonials' ),
						value: a.surfaceColor || '',
						options: colourOptions(),
						placeholder: '#fffdf7',
						onChange: function ( next ) {
							set( { surfaceColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Borders', 'devadigm-testimonials' ),
						value: a.borderColor || '',
						options: colourOptions(),
						placeholder: '#ede7dc',
						onChange: function ( next ) {
							set( { borderColor: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Quote typeface', 'devadigm-testimonials' ),
						value: a.quoteFont || '',
						options: fontOptions(),
						placeholder: 'Georgia, serif',
						onChange: function ( next ) {
							set( { quoteFont: next } );
						},
					} ),
					el( PairedControl, {
						label: __( 'Name and meta typeface', 'devadigm-testimonials' ),
						value: a.bodyFont || '',
						options: fontOptions(),
						placeholder: 'Inter, sans-serif',
						onChange: function ( next ) {
							set( { bodyFont: next } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Quote size scale', 'devadigm-testimonials' ),
						value: a.quoteScale || 1,
						min: 0.5,
						max: 3,
						step: 0.05,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { quoteScale: next } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Mark size scale', 'devadigm-testimonials' ),
						value: a.markScale || 1,
						min: 0.5,
						max: 3,
						step: 0.05,
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							set( { markScale: next } );
						},
					} )
				)
			);

			return el(
				Fragment,
				null,
				inspector,
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'devadigm/testimonials',
						attributes: Object.assign( {}, a, { layout: layout, markStyle: mark } ),
					} )
				)
			);
		},

		/**
		 * Rendering happens on the server.
		 *
		 * @return {null} Nothing is saved to post content.
		 */
		save: function () {
			return null;
		},
	} );

	// One inserter entry per layout, so the choice is made before insertion.
	( settings.layouts || [] ).forEach( function ( layout ) {
		wp.blocks.registerBlockVariation( 'devadigm/testimonials', {
			name: 'devadigm-testimonials-' + layout.value,
			title: layout.label.split( ' - ' )[ 0 ],
			description: layout.label,
			icon: 'format-quote',
			attributes: { layout: layout.value },
			scope: [ 'inserter' ],
			isActive: function ( blockAttributes ) {
				return blockAttributes.layout === layout.value;
			},
		} );
	} );
} )( window.wp, window.dvdmTestimonials );
