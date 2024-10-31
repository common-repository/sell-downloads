<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the categories
Plugin::$instance->elements_manager->add_category(
	'sell-downloads-cat',
	array(
		'title'=>'Sell Downloads',
		'icon' => 'fa fa-plug'
	),
	2 // position
);
