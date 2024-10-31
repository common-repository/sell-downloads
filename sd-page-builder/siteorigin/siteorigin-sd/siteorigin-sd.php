<?php
/*
Widget Name: Sell Downloads Store
Description: Inserts the Sell Downloads Store shortcode.
Documentation: https://wordpress.dwbooster.com/content-tools/sell-downloads
*/

class SiteOrigin_SellDownloads extends SiteOrigin_Widget
{
	function __construct()
	{

		$types = array( 'all' => __('All', SD_TEXT_DOMAIN) );
		$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));
		foreach($type_list as $type)
		{
			$types[$type->term_id] = sd_strip_tags($type->name,true);
		}

		$categories = array('all' => __('All', SD_TEXT_DOMAIN));
		$category_list = get_terms('sd_category', array( 'hide_empty' => 0 ));
		foreach($category_list as $category)
		{
			$categories[$category->term_id] = sd_strip_tags($category->name,true);
		}

		parent::__construct(
			'siteorigin-sell-downloads',
			__('Sell Downloads Store', SD_TEXT_DOMAIN),
			array(
				'description' 	=> __('Inserts the Sell Downloads Store shortcode', SD_TEXT_DOMAIN),
				'panels_groups' => array('sell-downloads'),
				'help'        	=> 'https://wordpress.dwbooster.com/content-tools/sell-downloads'
			),
			array(),
			array(
				'product_type' => array(
					'type' 		=> 'select',
					'label' 	=> __( 'Product Type', SD_TEXT_DOMAIN ),
					'default' 	=> 'all',
					'options' 	=> $types
				),
				'exclude' => array(
					'type' 		=> 'text',
					'label'		=> __('Enter the id of products to exclude', SD_TEXT_DOMAIN)
				),
				'columns' => array(
					'type' 		=> 'number',
					'label'		=> __('Number of columns', SD_TEXT_DOMAIN),
					'default'	=> 2
				),
				'category' => array(
					'type' 		=> 'select',
					'label' 	=> __('Category', SD_TEXT_DOMAIN),
					'default' 	=> 'all',
					'options' 	=> $categories
				)
			),
			plugin_dir_path(__FILE__)
		);
	} // End __construct

	function get_template_name($instance)
	{
		return 'siteorigin-sd-shortcode';
    } // End get_template_name

    function get_style_name($instance)
	{
        return '';
    } // End get_style_name

} // End Class SiteOrigin_SellDownloads

// Registering the widget
siteorigin_widget_register('siteorigin-sell-downloads', __FILE__, 'SiteOrigin_SellDownloads');