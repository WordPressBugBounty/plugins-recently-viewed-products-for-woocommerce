/**
 * Editor script for the Recently Viewed Products block.
 *
 * No build step: written against the global wp.* UMD bundles. The front-end
 * markup is produced server-side (ServerSideRender), so it always matches the
 * shortcode output.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'rvpw/recently-viewed', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Display', 'recently-viewed-products-for-woocommerce' ), initialOpen: true },
					el( TextControl, {
						label: __( 'Title', 'recently-viewed-products-for-woocommerce' ),
						value: a.title,
						onChange: function ( v ) { set( { title: v } ); }
					} ),
					el( RangeControl, {
						label: __( 'Products to display', 'recently-viewed-products-for-woocommerce' ),
						value: a.limit,
						min: 1,
						max: 24,
						onChange: function ( v ) { set( { limit: v } ); }
					} ),
					el( RangeControl, {
						label: __( 'Columns', 'recently-viewed-products-for-woocommerce' ),
						value: a.columns,
						min: 1,
						max: 6,
						onChange: function ( v ) { set( { columns: v } ); }
					} ),
					el( SelectControl, {
						label: __( 'Layout', 'recently-viewed-products-for-woocommerce' ),
						value: a.layout,
						options: [
							{ label: __( 'Grid', 'recently-viewed-products-for-woocommerce' ), value: 'grid' },
							{ label: __( 'List', 'recently-viewed-products-for-woocommerce' ), value: 'list' },
							{ label: __( 'Carousel', 'recently-viewed-products-for-woocommerce' ), value: 'carousel' }
						],
						onChange: function ( v ) { set( { layout: v } ); }
					} ),
					el( SelectControl, {
						label: __( 'Order by', 'recently-viewed-products-for-woocommerce' ),
						value: a.sort,
						options: [
							{ label: __( 'Recently viewed', 'recently-viewed-products-for-woocommerce' ), value: 'recent' },
							{ label: __( 'Price: low to high', 'recently-viewed-products-for-woocommerce' ), value: 'price_asc' },
							{ label: __( 'Price: high to low', 'recently-viewed-products-for-woocommerce' ), value: 'price_desc' },
							{ label: __( 'Newest products', 'recently-viewed-products-for-woocommerce' ), value: 'date' },
							{ label: __( 'Random', 'recently-viewed-products-for-woocommerce' ), value: 'random' }
						],
						onChange: function ( v ) { set( { sort: v } ); }
					} )
				),
				el(
					PanelBody,
					{ title: __( 'Elements', 'recently-viewed-products-for-woocommerce' ), initialOpen: false },
					el( ToggleControl, { label: __( 'Show image', 'recently-viewed-products-for-woocommerce' ), checked: a.showImage, onChange: function ( v ) { set( { showImage: v } ); } } ),
					el( ToggleControl, { label: __( 'Show title', 'recently-viewed-products-for-woocommerce' ), checked: a.showTitle, onChange: function ( v ) { set( { showTitle: v } ); } } ),
					el( ToggleControl, { label: __( 'Show price', 'recently-viewed-products-for-woocommerce' ), checked: a.showPrice, onChange: function ( v ) { set( { showPrice: v } ); } } ),
					el( ToggleControl, { label: __( 'Show rating', 'recently-viewed-products-for-woocommerce' ), checked: a.showRating, onChange: function ( v ) { set( { showRating: v } ); } } ),
					el( ToggleControl, { label: __( 'Show add-to-cart', 'recently-viewed-products-for-woocommerce' ), checked: a.showCart, onChange: function ( v ) { set( { showCart: v } ); } } )
				)
			);

			var preview = el( ServerSideRender, {
				block: 'rvpw/recently-viewed',
				attributes: a
			} );

			return el( Fragment, {}, controls, el( 'div', { className: 'rvpw-block-preview' }, preview ) );
		},

		save: function () {
			// Dynamic block — rendered in PHP.
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender, window.wp.i18n );
