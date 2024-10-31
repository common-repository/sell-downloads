<?php
require_once dirname(__FILE__).'/sd/sd.pb.php';

FLBuilder::register_module(
	'CPSDBeaver',
	array(
		'cpsd-tab' => array(
			'title'	=> __('Sell Downloads Store', SD_TEXT_DOMAIN),
			'sections' => array(
				'cpsd-section' => array(
					'title' 	=> __('Store\'s Attributes', SD_TEXT_DOMAIN),
					'fields'	=> array(
						'columns' => array(
							'type' 	=> 'text',
							'label' => __('Number of Columns', SD_TEXT_DOMAIN),
							'description'	=> __('Number of columns to distribute the products in the store\'s pages', SD_TEXT_DOMAIN)
						),
						'attributes' => array(
							'type' 	=> 'text',
							'label' => __('Additional attributes', SD_TEXT_DOMAIN),
							'description' => '<a href="https://wordpress.dwbooster.com/content-tools/sell-downloads#shortcode-attributes" target="_blank">'.__('Click here to know the complete list of attributes', SD_TEXT_DOMAIN).'</a>'
						),
					)
				)
			)
		)
	)
);