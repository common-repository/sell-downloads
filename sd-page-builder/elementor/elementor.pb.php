<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_Sell_Downloads_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'sell-downloads';
	} // End get_name

	public function get_title()
	{
		return 'Sell Downloads';
	} // End get_title

	public function get_icon()
	{
		return 'eicon-cart-medium';
	} // End get_icon

	public function get_categories()
	{
		return array( 'sell-downloads-cat' );
	} // End get_categories

	public function is_reload_preview_required()
	{
		return true;
	} // End is_reload_preview_required

	protected function register_controls()
	{
		$this->start_controls_section(
			'sd_section',
			array(
				'label' => __( 'Sell Downloads', SD_TEXT_DOMAIN )
			)
		);

		// Load file types
		$types = array( 'all' => __('All', SD_TEXT_DOMAIN) );
		$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));
		foreach($type_list as $type)
		{
			$types[$type->term_id] = sd_strip_tags($type->name,true);
		}

		$this->add_control(
			'product_type',
			array(
				'label' =>  __('Product type', SD_TEXT_DOMAIN),
				'type' => Controls_Manager::SELECT,
				'options' => $types,
				'default'	  => 'all',
				'classes'	  => 'sd-widefat',
				'description' => '<i>'.__( 'Select the products type include in the store.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$this->add_control(
			'exclude',
			array(
				'label' =>  __('Enter the id of products to exclude', SD_TEXT_DOMAIN),
				'type' => Controls_Manager::TEXT,
				'default'	  => '',
				'classes'	  => 'sd-widefat',
				'description' => '<i>'.__( 'Enter the id of products to exclude from the store, separated by comma.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$this->add_control(
			'columns',
			array(
				'label' =>  __('Number of columns', SD_TEXT_DOMAIN),
				'type' => Controls_Manager::NUMBER,
				'default'	  => 2,
				'classes'	  => 'sd-widefat',
				'description' => '<i>'.__( 'Enter the number of columns, one column by default.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$categories = array('all' => __('All', SD_TEXT_DOMAIN));
		$category_list = get_terms('sd_category', array( 'hide_empty' => 0 ));
		foreach($category_list as $category)
		{
			$categories[$category->term_id] = sd_strip_tags($category->name,true);
		}

		$this->add_control(
			'category',
			array(
				'label' =>  __('Category', SD_TEXT_DOMAIN),
				'type' => Controls_Manager::SELECT,
				'options' => $categories,
				'default'	  => 'all',
				'classes'	  => 'sd-widefat',
				'description' => '<i>'.__( 'Select the category of the products to display.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode()
	{
		$attr 		= '';
		$settings 	= $this->get_settings_for_display();

		$product_type 	= sanitize_text_field($settings['product_type']);
		if(!empty($product_type)) $attr .= ' type="'.esc_attr($product_type).'"';

		$exclude 	= trim($settings['exclude']);
		$exclude	= preg_replace('/[^\d\,]/', '', $exclude);
		$exclude	= trim($exclude, ',');
		if(!empty($exclude)) $attr .= ' exclude="'.esc_attr($exclude).'"';

		$columns 	= trim($settings['columns']);
		$columns	= max(1,@intval($columns));
		if(!empty($columns)) $attr .= ' columns="'.esc_attr($columns).'"';

		$category = sanitize_text_field($settings['category']);
		if(!empty($category)) $attr .= ' category="'.esc_attr($category).'"';

		return '[sell_downloads'.$attr.']';
	} // End _get_shortcode

	protected function render()
	{
		$shortcode = $this->_get_shortcode();

		if(
			isset($_REQUEST['action']) &&
			(
				$_REQUEST['action'] == 'elementor' ||
				$_REQUEST['action'] == 'elementor_ajax'
			)
		)
		{
			$url = SD_H_URL;
			$url .= ((strpos($url, '?') === false) ? '?' : '&').'sd-preview='.urlencode($shortcode);
			?>
			<div class="sd-preview-container" style="position:relative;">
				<div class="sd-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print $url; ?>" scrolling="no">
			</div>
			<?php
		}
		else
		{
			print do_shortcode(shortcode_unautop($shortcode));
		}

	} // End render

	public function render_plain_content()
	{
		echo $this->_get_shortcode();
	} // End render_plain_content

} // End Elementor_Sell_Downloads_Widget

class Elementor_Sell_Downloads_Product_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'sell-downloads-product';
	} // End get_name

	public function get_title()
	{
		return 'Product';
	} // End get_title

	public function get_icon()
	{
		return 'eicon-single-product';
	} // End get_icon

	public function get_categories()
	{
		return array( 'sell-downloads-cat' );
	} // End get_categories

	public function is_reload_preview_required()
	{
		return true;
	} // End is_reload_preview_required

	protected function register_controls()
	{
		$this->start_controls_section(
			'sd_section',
			array(
				'label' => __( 'Product', SD_TEXT_DOMAIN )
			)
		);

		$this->add_control(
			'product',
			array(
				'label' =>  __("Enter the product's id", SD_TEXT_DOMAIN),
				'type' => Controls_Manager::NUMBER,
				'description' => '<i>'.__( 'Enter the id of a published product.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$this->add_control(
			'layout',
			array(
				'label' =>  __("Select the product's layout", SD_TEXT_DOMAIN),
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'store'  => __("Short", SD_TEXT_DOMAIN),
					'single' => __("Completed", SD_TEXT_DOMAIN)
				),
				'default'	  => 'store',
				'description' => '<i>'.__( 'Appearance applied to the product.', SD_TEXT_DOMAIN ).'</i>'
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode(&$product_id='')
	{
		$attr 		= '';
		$settings 	= $this->get_settings_for_display();

		$product 	= trim($settings['product']);
		if(!empty($product)) $attr .= ' id="'.esc_attr($product).'"';
		$product_id = $product;

		$layout = trim($settings['layout']);
		if(!empty($layout)) $attr .= ' layout="'.esc_attr($layout).'"';

		return '[sell_downloads_product'.$attr.']';
	} // End _get_shortcode

	protected function render()
	{
		$shortcode = $this->_get_shortcode($product_id);
		if(
			isset($_REQUEST['action']) &&
			(
				$_REQUEST['action'] == 'elementor' ||
				$_REQUEST['action'] == 'elementor_ajax'
			)
		)
		{
			if(empty($product_id))
			{
				_e("The product's id is required.", SD_TEXT_DOMAIN);
			}
			else
			{
				$url = SD_H_URL;
				$url .= ((strpos($url, '?') === false) ? '?' : '&').'sd-preview='.urlencode($shortcode);
				?>
				<div class="sd-preview-container" style="position:relative;">
					<div class="sd-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
					<iframe height="0" width="100%" src="<?php print $url; ?>" scrolling="no">
				</div>
				<?php
			}
		}
		else
		{
			print do_shortcode(shortcode_unautop($shortcode));
		}

	} // End render

	public function render_plain_content()
	{
		echo $this->_get_shortcode();
	} // End render_plain_content

} // End Elementor_Sell_Downloads_Product_Widget

// Register the widgets
Plugin::instance()->widgets_manager->register( new Elementor_Sell_Downloads_Widget );
Plugin::instance()->widgets_manager->register( new Elementor_Sell_Downloads_Product_Widget );
