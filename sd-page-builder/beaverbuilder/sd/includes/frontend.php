<?php
$columns 	= '';
$attributes = '';

// Processing columns
if(!empty($settings->columns)) $columns = preg_replace('/[^\d]/', '', $settings->columns);
if(!empty($columns)) $columns = ' columns="'.$columns.'"';

// Processing the additional attributes
if(!empty($settings->attributes)) $attributes = sanitize_text_field($settings->attributes);
if(!empty($attributes)) $attributes = ' '.$attributes;

echo '[sell_downloads'.$columns.$attributes.']';
