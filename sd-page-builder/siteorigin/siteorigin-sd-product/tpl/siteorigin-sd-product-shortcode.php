<?php
$product = sanitize_text_field(!empty($instance['product']) ? $instance['product'] : '');
$product = @intval($product);
if($product)
{
	$shortcode = '[sell_downloads_product id="'.esc_attr($product).'"';
	$layout = sanitize_text_field(!empty($instance['layout']) ? $instance['layout']  : '');
	if(!empty($layout)) $shortcode .= ' layout="'.esc_attr($layout).'"';
	$shortcode .= ']';
}
print !empty($shortcode) ? $shortcode : '';