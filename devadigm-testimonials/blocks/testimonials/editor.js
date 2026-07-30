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

			// Blocks saved before the layouts were reorganised keep working, but
			// their old names are not offered as choices any more.
			var isLegacy = ( settings.legacyLayouts || [] ).indexOf( layout ) !== -1;

			var isGrid = layout === 'grid';
			var canSlide = ! isLegacy && [ 'spotlight', 'grid' ].indexOf( layout ) !== -1;
			var canTrim = ! isLegacy && [ 'grid', 'marquee' ].indexOf( layout ) !== -1;
			var isMultiple = isLegacy || [ 'grid', 'marquee' ].indexOf( layout ) !== -1 || !! a.slider;

			var columns = a.columns || settings.defaultColumns || 3;
			var isSlider = !! a.slider;
			var perView = a.slidesPerView || ( isGrid ? columns : 1 );
			var loop =
				a.loop === undefined
					? settings.defaultLoop !== false
					: !! a.loop;
			var autoplay =
				a.autoplay === undefined ? settings.defaultAutoplay || 0 : a.autoplay;
			var readMore =
				a.readMore === undefined
					? settings.defaultReadMore !== false
					: !! a.readMore;

			var inspector = el(
				InspectorControls,
				null,

				el(
					PanelBody,
					{ title: __( 'Layout', 'devadigm-testimonials' ), initialOpen: true },
					isLegacy
						? el(
								Notice,
								{ status: 'warning', isDismissible: false },
								__(
									'This block still uses an older layout name. It renders the same, but pick a layout below to get the newer controls.',
									'devadigm-testimonials'
								)
						  )
						: null,
					el( SelectControl, {
						label: __( 'Display as', 'devadigm-testimonials' ),
						value: isLegacy ? '' : layout,
						options: ( isLegacy
							? [ { label: __( 'Older layout', 'devadigm-testimonials' ), value: '' } ]
							: [] 
						).concat( settings.layouts || [] ),
						__nextHasNoMarginBottom: true,
						onChange: function ( next ) {
							if ( next !== '' ) {
								set( { layout: next } );
							}
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
					isGrid
						? el( RangeControl, {
								label: __( 'Columns', 'devadigm-testimonials' ),
								value: columns,
								min: 1,
								max: 6,
								__nextHasNoMarginBottom: true,
								help: __(
									'Columns drop automatically when the space cannot fit them.',
									'devadigm-testimonials'
								),
								onChange: function ( next ) {
									set( { columns: next } );
								},
						  } )
						: null,
					isGrid
						? el( ToggleControl, {
								label: __( 'Masonry', 'devadigm-testimonials' ),
								checked: !! a.masonry,
								__nextHasNoMarginBottom: true,
								help: a.masonry
									? __( 'Cards pack by height.', 'devadigm-testimonials' )
									: __( 'Cards in a row share the same height.', 'devadigm-testimonials' ),
								onChange: function ( next ) {
									set( { masonry: next } );
								},
						  } )
						: null,
					isMultiple
						? el( RangeControl, {
								label: __( 'How many to show', 'devadigm-testimonials' ),
								value: a.count,
								min: 1,
								max: 24,
								__nextHasNoMarginBottom: true,
								onChange: function ( next ) {
									set( { count: next } );
								},
						  } )
						: null
				),

				canSlide
					? el(
							PanelBody,
							{ title: __( 'Slider', 'devadigm-testimonials' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Turn this into a slider', 'devadigm-testimonials' ),
								checked: isSlider,
								__nextHasNoMarginBottom: true,
								help: __(
									'Available on Spotlight and Grid. Set how many are visible at once below.',
									'devadigm-testimonials'
								),
								onChange: function ( next ) {
									set( { slider: next } );
								},
							} ),
							isSlider
								? el( RangeControl, {
										label: __( 'Visible at once', 'devadigm-testimonials' ),
										value: perView,
										min: 1,
										max: 6,
										__nextHasNoMarginBottom: true,
										help: __(
											'Drops to one on narrow screens.',
											'devadigm-testimonials'
										),
										onChange: function ( next ) {
											set( { slidesPerView: next } );
										},
								  } )
								: null,
							isSlider
								? el( ToggleControl, {
										label: __( 'Loop back to the start', 'devadigm-testimonials' ),
										checked: loop,
										__nextHasNoMarginBottom: true,
										help: loop
											? __( 'The arrows carry on past the ends.', 'devadigm-testimonials' )
											: __( 'The arrows stop at the first and last slide.', 'devadigm-testimonials' ),
										onChange: function ( next ) {
											set( { loop: next } );
										},
								  } )
								: null,
							isSlider
								? el( RangeControl, {
										label: __( 'Advance automatically after', 'devadigm-testimonials' ),
										value: autoplay,
										min: 0,
										max: 30,
										__nextHasNoMarginBottom: true,
										help: autoplay
											? __(
													'Seconds. A pause button appears, and it stops as soon as anyone interacts.',
													'devadigm-testimonials'
											  )
											: __( 'Off. Visitors advance it themselves.', 'devadigm-testimonials' ),
										onChange: function ( next ) {
											set( { autoplay: next } );
										},
								  } )
								: null
					  )
					: null,

				canTrim
					? el(
							PanelBody,
							{ title: __( 'Long quotes', 'devadigm-testimonials' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Trim long quotes', 'devadigm-testimonials' ),
								checked: readMore,
								__nextHasNoMarginBottom: true,
								help: __(
									'Long quotes are shortened with a Read more link that opens the full quote. Short quotes are shown in full.',
									'devadigm-testimonials'
								),
								onChange: function ( next ) {
									set( { readMore: next } );
								},
							} ),
							readMore
								? el( RangeControl, {
										label: __( 'Lines before trimming', 'devadigm-testimonials' ),
										value: a.clampLines || 6,
										min: 2,
										max: 20,
										__nextHasNoMarginBottom: true,
										onChange: function ( next ) {
											set( { clampLines: next } );
										},
								  } )
								: null
					  )
					: null,

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

	/*
	 * Inserter entries. Sliding is a property rather than a layout, so the
	 * common combinations are offered here as starting points instead of being
	 * assembled by hand every time.
	 */
	[
		{
			name: 'spotlight',
			title: __( 'Spotlight', 'devadigm-testimonials' ),
			description: __( 'One large testimonial.', 'devadigm-testimonials' ),
			attributes: { layout: 'spotlight', count: 1 },
		},
		{
			name: 'spotlight-slider',
			title: __( 'Spotlight slider', 'devadigm-testimonials' ),
			description: __( 'One large testimonial at a time, with arrows.', 'devadigm-testimonials' ),
			attributes: { layout: 'spotlight', slider: true, slidesPerView: 1, count: 6 },
		},
		{
			name: 'grid',
			title: __( 'Grid', 'devadigm-testimonials' ),
			description: __( 'Three across, equal height.', 'devadigm-testimonials' ),
			attributes: { layout: 'grid', columns: 3, count: 6 },
		},
		{
			name: 'grid-slider-2',
			title: __( 'Two-column slider', 'devadigm-testimonials' ),
			description: __( 'Two cards at a time, with arrows.', 'devadigm-testimonials' ),
			attributes: { layout: 'grid', slider: true, slidesPerView: 2, columns: 2, count: 8 },
		},
		{
			name: 'grid-slider-3',
			title: __( 'Three-column slider', 'devadigm-testimonials' ),
			description: __( 'Three cards at a time, with arrows.', 'devadigm-testimonials' ),
			attributes: { layout: 'grid', slider: true, slidesPerView: 3, columns: 3, count: 9 },
		},
		{
			name: 'masonry',
			title: __( 'Masonry wall', 'devadigm-testimonials' ),
			description: __( 'Cards packed by height, for a dedicated page.', 'devadigm-testimonials' ),
			attributes: { layout: 'grid', masonry: true, columns: 3, count: 12 },
		},
		{
			name: 'marquee',
			title: __( 'Marquee', 'devadigm-testimonials' ),
			description: __( 'A continuous drift of short quotes.', 'devadigm-testimonials' ),
			attributes: { layout: 'marquee', count: 8 },
		},
		{
			name: 'inline',
			title: __( 'Inline proof', 'devadigm-testimonials' ),
			description: __( 'A single pull-quote to sit beside a call to action.', 'devadigm-testimonials' ),
			attributes: { layout: 'inline', count: 1 },
		},
	].forEach( function ( variation ) {
		wp.blocks.registerBlockVariation( 'devadigm/testimonials', {
			name: 'devadigm-testimonials-' + variation.name,
			title: variation.title,
			description: variation.description,
			icon: 'format-quote',
			attributes: variation.attributes,
			scope: [ 'inserter' ],
			isActive: function ( blockAttributes ) {
				return Object.keys( variation.attributes ).every( function ( key ) {
					if ( key === 'count' ) {
						return true;
					}
					return blockAttributes[ key ] === variation.attributes[ key ];
				} );
			},
		} );
	} );

} )( window.wp, window.dvdmTestimonials );
