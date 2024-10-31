<?php

if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

	echo 'Start IPN';

	$ipn_parameters = array();
	$_parameters = explode('|', sanitize_text_field($_GET['sd_action']));
	foreach($_parameters as $_parameter)
	{
		$_parameter_parts = explode('=',$_parameter);
		if(count($_parameter_parts) == 2)
		{
			$ipn_parameters[$_parameter_parts[0]] = $_parameter_parts[1];
		}
	}

	$item_name = sanitize_text_field($_POST['item_name']);
	$item_number = sanitize_text_field($_POST['item_number']);
	$payment_status = sanitize_text_field($_POST['payment_status']);
	$payment_amount = @floatval($_POST['mc_gross']);
	if( !empty( $_POST[ 'tax' ] ) ) $payment_amount -= @floatval($_POST[ 'tax' ]);
	$payment_currency = sanitize_text_field($_POST['mc_currency']);
	$txn_id = sanitize_text_field($_POST['txn_id']);
	$receiver_email = sanitize_email($_POST['receiver_email']);
	$payer_email = sanitize_email($_POST['payer_email']);
	$payment_type = sanitize_text_field($_POST['payment_type']);

	if ($payment_status != 'Completed' && $payment_type != 'echeck') exit;
	if ($payment_type == 'echeck' && $payment_status == 'Completed') exit;

    $paypal_data = "";
	foreach ($_POST as $item => $value) $paypal_data .= sanitize_text_field($item)."=".sanitize_text_field($value)."\r\n";


    if(!isset($ipn_parameters['purchase_id'])) exit;
    $purchase_id = $ipn_parameters['purchase_id'];

	if(isset($ipn_parameters['pid'])) $id = $ipn_parameters['pid'];
	elseif(isset($ipn_parameters['id'])) $id = $ipn_parameters['id'];
	else exit;

    $_post = get_post($id);
    if(is_null($_post)) exit;

    if($_post->post_type == "sd_product") $obj = new SDProduct($id);
    else exit;

	$quantity = max(@intval($ipn_parameters['quantity']), 1);

    if(!isset($obj->price) || ($payment_amount < $obj->price*$quantity && abs($payment_amount - $obj->price*$quantity) > 0.2 )) exit;
    if(sell_downloads_register_purchase($id, $quantity, $purchase_id, $payer_email, $payment_amount, $paypal_data)) $obj->purchases++;

	array_walk_recursive( $_POST, function( &$item, $index ){ $item = sanitize_text_field( wp_unslash( $item ) ); } );

	do_action('sd_paypal_ipn_received', $_POST, $obj);

	sd_send_emails(
		array(
			'item_name' => $item_name,
			'payment_amount' => $payment_amount,
			'payment_currency' => $payment_currency,
			'purchase_id' => $ipn_parameters['purchase_id'],
			'payer_email' => $payer_email
		)
	);

   echo 'OK';
   exit();
?>