( function( blocks, element ) {
	var el 			= element.createElement,
		source 		= blocks.source,
		InspectorControls = ('blockEditor' in wp) ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;

	/* Plugin Category */
	blocks.getCategories().push({slug: 'cpsd', title: 'Sell Downloads'});

	/* ICONS */
	const iconCPSD = el('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAADZJREFUKJFj9N3hyUA6YCJDz0Bp2+S+DU4SAxipEyTI1uIih6dtcEBj29AAVW3DZNPGNtpqAwAA1TNXs9r4xQAAAABJRU5ErkJggg==" } );

	const iconCPSDP = el('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAAD1JREFUKJFj9N3hyUA6YCJDD0LbJvdtRJIQwEiRI3GZOmobebbhB7TxGy57BmFIbnLf5rfTC5dtlDmSVAAAhJRNJewsI0sAAAAASUVORK5CYII=" } );

	function esc_regexp(str)
	{
		return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	};

	function get_attr_value(attr, shortcode)
	{
		var reg = new RegExp('\\b'+esc_regexp(attr)+'\\s*=\\s*[\'"]([^\'"]*)[\'"]', 'i'),
			res = reg.exec(shortcode);
		if(res !== null) return res[1];
		return '';
	};

	function generate_shortcode(shortcode, attr, value, props)
	{
		var shortcode = wp.shortcode.next(shortcode, props.attributes.shortcode),
			attrs = shortcode.shortcode.attrs.named;

		shortcode.shortcode.attrs.named[attr] = value;
		props.setAttributes({'shortcode': shortcode.shortcode.string()});
	};

	/* Sell Downloads Shortcode */
	blocks.registerBlockType( 'cpsd/sell-downloads', {
		title: 'Sell Downloads',
		icon: iconCPSD,
		category: 'cpsd',
		supports: {
			customClassName	: false,
			className		: false
		},
		attributes: {
			shortcode : {
				type : 'string',
				source : 'text',
				default: '[sell_downloads columns="1"]'
			}
		},

		edit: function( props ) {
			var focus 		= props.isSelected,
				children 	= [],
				products_options 	= [],
				categories_options 	= [],
				product_type 		= get_attr_value('type', props.attributes.shortcode),
				category 			= get_attr_value('category', props.attributes.shortcode),
				default_category		= '',
				default_product_type 	= '';

			// Populate products_options
			if(/^\s*$/.test(product_type)) product_type = 'all';
			for(var i in sd_ge_config.products_type)
			{
				var key = 'sd_product_type_'+i,
					config = {key: key, value: i};

				if(default_product_type == '') default_product_type = i;
				if(i == product_type) default_product_type = i;
				products_options.push(el('option', config, sd_ge_config.products_type[i]));
			}

			// Populate categories_options
			if(/^\s*$/.test(category)) category = 'all';
			for(var i in sd_ge_config.categories)
			{
				var key = 'sd_category_'+i,
					config = {key: key, value: i};

				if(default_category == '') default_category = i;
				if(i == category) default_category = i;
				categories_options.push(el('option', config, sd_ge_config.categories[i]));
			}

			// Editor
			children.push(
				el(
					'div', {className: 'sd-iframe-container', key: 'sd_iframe_container'},
					el('div', {className: 'sd-iframe-overlay', key: 'sd_iframe_overlay'}),
					el('iframe',
						{
							key: 'sd_store_iframe',
							src: sd_ge_config.url+encodeURIComponent(props.attributes.shortcode),
							height: 0,
							width: '100%',
							scrolling: 'no'
						}
					)
				)
			);

			// InspectorControls
			if(!!focus)
			{
				children.push(
					el(
						InspectorControls,
						{
							key: 'sd_inspector'
						},
                        el(
                            'div',
                            {
                                key: 'sd_inspector_container',
                                style:{paddingLeft:'20px',paddingRight:'20px'}
                            },
                            [
                                el('hr', {key: 'sd_hr'}),
                                // Products
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_products_types',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_products_types_label'
                                    },
                                    sd_ge_config.labels.products_type
                                ),
                                el(
                                    'select',
                                    {
                                        key: 'sd_products_types',
                                        style: {width:'100%'},
                                        onChange : function(evt){generate_shortcode('sell_downloads', 'type', evt.target.value, props);},
                                        value: default_product_type
                                    },
                                    products_options
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle: 'italic'},
                                        key: 'sd_products_types_help'
                                    },
                                    sd_ge_config.help.products_type
                                ),

                                // Exclude
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_products_to_exclude',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_products_to_exclude_label'
                                    },
                                    sd_ge_config.labels.exclude
                                ),
                                el(
                                    'input',
                                    {
                                        key: 'sd_products_to_exclude',
                                        type: 'text',
                                        style: {width:'100%'},
                                        value : get_attr_value('exclude', props.attributes.shortcode),
                                        onChange : function(evt){generate_shortcode('sell_downloads', 'exclude', evt.target.value, props);}
                                    }
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle: 'italic'},
                                        key: 'sd_products_to_exclude_help'
                                    },
                                    sd_ge_config.help.exclude
                                ),

                                // Columns
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_columns',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_columns_label'
                                    },
                                    sd_ge_config.labels.columns
                                ),
                                el(
                                    'input',
                                    {
                                        key: 'sd_columns',
                                        type: 'number',
                                        style: {width:'100%'},
                                        value : get_attr_value('columns', props.attributes.shortcode),
                                        onChange : function(evt){generate_shortcode('sell_downloads', 'columns', evt.target.value, props);}
                                    }
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle: 'italic'},
                                        key: 'sd_columns_help'
                                    },
                                    sd_ge_config.help.columns
                                ),

                                // Categories
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_category',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_category_label'
                                    },
                                    sd_ge_config.labels.category
                                ),
                                el(
                                    'select',
                                    {
                                        key: 'sd_category',
                                        style: {width:'100%'},
                                        onChange : function(evt){generate_shortcode('sell_downloads', 'category', evt.target.value, props);},
                                        value: default_category
                                    },
                                    categories_options
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle: 'italic'},
                                        key: 'sd_category_help'
                                    },
                                    sd_ge_config.help.category
                                )
                            ]
                        )
					)
				);
			}
			return [children];
		},

		save: function( props ) {
			return props.attributes.shortcode;
		}
	});

	/* Sell Downloads Product Shortcode */
	blocks.registerBlockType( 'cpsd/sell-downloads-product', {
		title: 'Product',
		icon: iconCPSDP,
		category: 'cpsd',
		supports: {
			customClassName	: false,
			className		: false
		},
		attributes: {
			shortcode : {
				type : 'string',
				source : 'text',
				default: '[sell_downloads_product id=""]'
			}
		},

		edit: function( props ) {
			var focus 		= props.isSelected,
				children 	= [],
				layout_options 	= [],
				layout 			= get_attr_value('layout', props.attributes.shortcode),
				id 				= get_attr_value('id', props.attributes.shortcode),
				default_layout 	= '';

			if(/^\s*$/.test(layout)) layout = 'store';
			for(var i in sd_ge_config.layout)
			{
				var key = 'sd_product_layout_'+i,
					config = {key: key, value: i};

				if(default_layout == '') default_layout = i;
				if(i == layout) default_layout = i;
				layout_options.push(el('option', config, sd_ge_config.layout[i]));
			}

			// Editor
			if(/^\s*$/.test(id))
			{
				children.push(
					el(
						'div', {key: 'sd_product_required'}, sd_ge_config.labels.product_required
					)
				);
			}
			else
			{
				children.push(
					el(
						'div', {className: 'sd-iframe-container', key: 'sd_iframe_container'},
						el('div', {className: 'sd-iframe-overlay', key: 'sd_iframe_overlay'}),
						el('iframe',
							{
								key: 'sd_product_iframe',
								src: sd_ge_config.url+encodeURIComponent(props.attributes.shortcode),
								height: 0,
								width: '100%',
								scrolling: 'no'
							}
						)
					)
				);
			}

			// InspectorControls
			if(!!focus)
			{
				children.push(
					el(
						InspectorControls,
						{
							key: 'sd_inspector'
						},
                        el(
                            'div',
                            {key: 'sd_product_container', style:{paddingLeft:'20px',paddingRight:'20px'}},
                            [
                                el('hr', {key: 'sd_hr'}),
                                // Products
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_products',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_products_label'
                                    },
                                    sd_ge_config.labels.product
                                ),
                                el(
                                    'input',
                                    {
                                        key: 'sd_product',
                                        type: 'number',
                                        style: {width:'100%'},
                                        value : get_attr_value('id', props.attributes.shortcode),
                                        onChange : function(evt){generate_shortcode('sell_downloads_product', 'id', evt.target.value, props);}
                                    }
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle:'italic'},
                                        key: 'sd_products_help'
                                    },
                                    sd_ge_config.help.product
                                ),

                                // Layouts
                                el(
                                    'label',
                                    {
                                        htmlFor: 'sd_product_layout',
                                        style:{fontWeight:'bold'},
                                        key: 'sd_product_layout_label'
                                    },
                                    sd_ge_config.labels.layout
                                ),
                                el(
                                    'select',
                                    {
                                        key: 'sd_product_layout',
                                        style: {width:'100%'},
                                        onChange : function(evt){generate_shortcode('sell_downloads_product', 'layout', evt.target.value, props);},
                                        value: default_layout
                                    },
                                    layout_options
                                ),
                                el(
                                    'div',
                                    {
                                        style: {fontStyle: 'italic'},
                                        key: 'sd_product_layout_help'
                                    },
                                    sd_ge_config.help.layout
                                )
                            ]
                        )
					)
				);
			}
			return [children];
		},

		save: function( props ) {
			return props.attributes.shortcode;
		}
	});
} )(
	window.wp.blocks,
	window.wp.element
);