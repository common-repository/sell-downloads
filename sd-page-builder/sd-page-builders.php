<?php
/**
 * Main class to interace with the different Content Editors: SD_PAGE_BUILDERS class
 *
 */
if(!class_exists('SD_PAGE_BUILDERS'))
{
	class SD_PAGE_BUILDERS
	{
		private static $_instance;

		private function __construct(){}
		private static function instance()
		{
			if(!isset(self::$_instance)) self::$_instance = new self();
			return self::$_instance;
		} // End instance

		public static function run()
		{
			$instance = self::instance();
			add_action('init', array($instance, 'init'));
			add_action('after_setup_theme', array($instance, 'after_setup_theme'));
		}

		public static function init()
		{
			$instance = self::instance();

			// Gutenberg
			add_action( 'enqueue_block_editor_assets', array($instance,'gutenberg_editor' ) );

			// Elementor
			add_action( 'elementor/widgets/register', array($instance, 'elementor_editor') );
			add_action( 'elementor/elements/categories_registered', array($instance, 'elementor_editor_category') );

			// Beaver builder
			if(class_exists('FLBuilder'))
			{
				include_once dirname(__FILE__).'/beaverbuilder/sd.inc.php';
			}
		}

		public function after_setup_theme()
		{
			$instance = self::instance();

			// SiteOrigin
			add_filter('siteorigin_widgets_widget_folders', array($instance, 'siteorigin_widgets_collection'));
			add_filter('siteorigin_panels_widget_dialog_tabs', array($instance, 'siteorigin_panels_widget_dialog_tabs'));
		} // End after_setup_theme

		/**************************** GUTENBERG ****************************/

		/**
		 * Loads the javascript resources to integrate the plugin with the Gutenberg editor
		 */
		public function gutenberg_editor()
		{
			wp_enqueue_style('sd-admin-gutenberg-editor-css', plugin_dir_url(__FILE__).'gutenberg/gutenberg.css');

			$url = SD_H_URL;
			$url .= ((strpos($url, '?') === false) ? '?' : '&').'sd-preview=';

			// Load file types
			$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));
			$products_type = array('all' => __('All', SD_TEXT_DOMAIN));
			foreach($type_list as $type)
			{
				$products_type[$type->term_id] = sd_strip_tags($type->name,true);
			}

			$category_list = get_terms('sd_category', array( 'hide_empty' => 0 ));
			$categories = array('all' => __('All', SD_TEXT_DOMAIN));
			foreach($category_list as $category)
			{
				$categories[$category->term_id] = sd_strip_tags($category->name,true);
			}

			$config = array(
				'url' => $url,
				'products_type' => $products_type,
				'categories' 	=> $categories,
				'list_types'	=> array(
					'new_products' 	=> __('New products', SD_TEXT_DOMAIN),
					'top_rated'		=> __('Top rated products', SD_TEXT_DOMAIN),
					'top_selling'	=> __('Most sold products', SD_TEXT_DOMAIN)
				),
				'layout' => array(
					'store' => strip_tags(__('Like in the store\'s page', SD_TEXT_DOMAIN)),
					'single' => strip_tags(__('Like in the product\'s page', SD_TEXT_DOMAIN)),
				),
				'labels' => array(
					'products_type'=> __('Product type', SD_TEXT_DOMAIN),
					'category' => __('Category', SD_TEXT_DOMAIN),
					'product' => __('Enter the product\'s id', SD_TEXT_DOMAIN),
					'number_of_products' => __('Enter the number of products', SD_TEXT_DOMAIN),
					'product_required' => __('The product\'s id is required.'),
					'layout'  => __('Select the product\'s layout', SD_TEXT_DOMAIN),
					'columns' => __('Number of columns', SD_TEXT_DOMAIN),
					'list_types' => __('List the products', SD_TEXT_DOMAIN),
					'exclude' => __('Enter the id of products to exclude', SD_TEXT_DOMAIN)
				),
				'help' => array(
					'products_type' => __('Select the products type to include.', SD_TEXT_DOMAIN),
					'category' => __('Select the category of the products to display.', SD_TEXT_DOMAIN),
					'product' => __('Enter the id of a published product.', SD_TEXT_DOMAIN),
					'number_of_products' => __('Number of products to load. Three products by default.', SD_TEXT_DOMAIN),
					'layout'  => __('Appearance applied to the product.', SD_TEXT_DOMAIN),
					'columns' => __('Enter the number of columns, one column by default.', SD_TEXT_DOMAIN),
					'list_types' => __('Products to include in the list.', SD_TEXT_DOMAIN),
					'exclude' => __('Enter the id of products to exclude from the store, separated by comma.', SD_TEXT_DOMAIN)
				)
			);

			wp_enqueue_script('sd-admin-gutenberg-editor', plugin_dir_url(__FILE__).'gutenberg/gutenberg.js', array( 'jquery', 'wp-blocks', 'wp-element' ), null, true);
			wp_localize_script('sd-admin-gutenberg-editor', 'sd_ge_config', $config);
		} // End gutenberg_editor

		/**************************** ELEMENTOR ****************************/

		public function elementor_editor_category()
		{
			require_once dirname(__FILE__).'/elementor/elementor-category.pb.php';
		} // End elementor_editor

		public function elementor_editor()
		{
			wp_enqueue_style('sd-admin-elementor-editor-css', plugin_dir_url(__FILE__).'elementor/elementor.css');
			require_once dirname(__FILE__).'/elementor/elementor.pb.php';
		} // End elementor_editor

		/**************************** SITEORIGIN ****************************/

		public function siteorigin_widgets_collection($folders)
		{
			$folders[] = dirname(__FILE__).'/siteorigin/';
			return $folders;
		} // End siteorigin_widgets_collection

		public function siteorigin_panels_widget_dialog_tabs($tabs)
		{
			$tabs[] = array(
				'title' => __('Sell Downloads', SD_TEXT_DOMAIN),
				'filter' => array(
					'groups' => array('sell-downloads')
				)
			);

			return $tabs;
		} // End siteorigin_panels_widget_dialog_tabs
	} // End SD_PAGE_BUILDERS
}