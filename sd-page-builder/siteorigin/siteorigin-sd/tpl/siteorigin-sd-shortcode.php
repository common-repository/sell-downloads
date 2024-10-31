<?php
$product_type = (!empty($instance['product_type'])) ? sanitize_text_field($instance['product_type']) : 'all';
$category 	  = (!empty($instance['category'])) ? sanitize_text_field($instance['category']) : 'all';
$exclude 	  = (!empty($instance['exclude'])) ?  sanitize_text_field($instance['exclude']) : '';
$columns 	  = (!empty($instance['columns'])) ?  $instance['columns'] : 2;

$shortcode		= '[sell_downloads';

if(!empty($product_type) && $product_type != 'all') $shortcode .= ' type="'.esc_attr($product_type).'"';

if(!empty($category) && $category != 'all') $shortcode .= ' category="'.esc_attr($category).'"';

$exclude	= preg_replace('/[^\d\,]/', '', $exclude);
$exclude	= trim($exclude, ',');
if(!empty($exclude)) $shortcode .= ' exclude="'.esc_attr($exclude).'"';

$columns	= max(1,@intval($columns));
if(!empty($columns)) $shortcode .= ' columns="'.esc_attr($columns).'"';

$shortcode .= ']';

print $shortcode;