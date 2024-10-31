<?php
/*
Widget Name: Sell Downloads Product
Description: Inserts a product's shortcode.
Documentation: https://wordpress.dwbooster.com/content-tools/sell-downloads
*/

class SiteOrigin_SellDownloads_Product extends SiteOrigin_Widget
{
	function __construct()
	{
		parent::__construct(
			'siteorigin-selldownloads-product',
			__('Sell Downloads Product', SD_TEXT_DOMAIN),
			array(
				'description' 	=> __('Inserts the Product shortcode', SD_TEXT_DOMAIN),
				'panels_groups' => array('sell-downloads'),
				'help'        	=> 'https://wordpress.dwbooster.com/content-tools/sell-downloads'
			),
			array(),
			array(
				'product' => array(
					'type' => 'number',
					'label' => __("Enter the product's id", SD_TEXT_DOMAIN)
				),
				'layout' => array(
					'type' 		=> 'select',
					'label' 	=> __("Select the product's layout", SD_TEXT_DOMAIN),
					'default' 	=> 'store',
					'options' 	=> array(
						'store'  => __("Short", SD_TEXT_DOMAIN),
						'single' => __("Completed", SD_TEXT_DOMAIN)
					)
				)
			),
			plugin_dir_path(__FILE__)
		);
	} // End __construct

	function get_template_name($instance)
	{
        return 'siteorigin-sd-product-shortcode';
    } // End get_template_name

    function get_style_name($instance)
	{
        return '';
    } // End get_style_name

} // End Class SiteOrigin_SellDownloads_Product

// Registering the widget
siteorigin_widget_register('siteorigin-selldownloads-product', __FILE__, 'SiteOrigin_SellDownloads_Product');