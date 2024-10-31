<?php
if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

	$ms_paypal_email = sanitize_email(get_option('sd_paypal_email'));

    $notify_url_params = 'ipn';

	$returnurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
    $returnurl .= ( strpos( $returnurl, '?' ) === false ) ? '?' : '&';

	$quantity = max(@intval($_POST['sd_quantity']), 1);
	$notify_url_params .= '|quantity='.$quantity;

	if( preg_match( '/^(http(s)?:\/\/[^\/\n]*)/i', SD_H_URL, $matches ) && strpos( @$_SERVER['HTTP_REFERER'], $matches[ 0 ] ) ) $cancel_url = $_SERVER['HTTP_REFERER'];
    if(empty($cancel_url)) $cancel_url = SD_H_URL;

    if($ms_paypal_email){ // Check for sealer email
        mt_srand(sell_downloads_make_seed());
        $randval = mt_rand(1,999999);

        $purchase_id = md5($randval.uniqid('', true));

        if(isset($_POST['sd_product_id'])){
            $product = $wpdb->get_row($wpdb->prepare("SELECT posts_data.id as id, posts_data.price as price, posts.post_title as title FROM ".$wpdb->prefix.SDDB_POST_DATA." as posts_data INNER JOIN ".$wpdb->prefix."posts as posts ON posts.ID = posts_data.id WHERE posts_data.id=%d AND posts.post_status='publish' AND posts.post_type='sd_product'", @intval($_POST['sd_product_id'])));

            if($product){
                $amount = $product->price;
				$title = $product->title.'('.$amount.' x '.$quantity.')';
				$amount *= $quantity;
                $number = $product->id;
                $ID = $product->id;
            }else{
                $price = 0;
            }
        }

        if($amount > 0){

			// Remove invalid characters from products names
			$title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
			$notify_url_params .= '|pid='.$ID.'|purchase_id='.$purchase_id.'|rtn_act=purchased_product_sell_downloads';

			$transient_id = uniqid( 'sd-ipn-', true );
			set_transient( $transient_id, $notify_url_params, 24 * 60 *60 );
			$notify_url_params = $transient_id;
?>
<form action="https://www.<?php print( ( get_option( 'sd_paypal_sandbox', false ) ) ? 'sandbox.' : '' ); ?>paypal.com/cgi-bin/webscr" name="ppform<?php print $randval; ?>" method="post">
<input type="hidden" name="charset" value="utf-8" />
<input type="hidden" name="business" value="<?php print esc_attr($ms_paypal_email); ?>" />
<input type="hidden" name="item_name" value="<?php print esc_attr( sanitize_text_field( $title ) ); ?>" />
<input type="hidden" name="item_number" value="Item Number <?php print esc_attr($number); ?>" />
<input type="hidden" name="amount" value="<?php print esc_attr($amount); ?>" />
<input type="hidden" name="currency_code" value="<?php print esc_attr(get_option('sd_paypal_currency', SD_PAYPAL_LANGUAGE)); ?>" />
<input type="hidden" name="lc" value="<?php print esc_attr(get_option('sd_paypal_language', SD_PAYPAL_LANGUAGE)); ?>" />
<input type="hidden" name="return" value="<?php print esc_url($returnurl.'purchase_id='.$purchase_id); ?>" />
<input type="hidden" name="cancel_return" value="<?php print esc_url($cancel_url); ?>" />
<input type="hidden" name="notify_url" value="<?php print esc_url(SD_H_URL.'?sd_action=' . $notify_url_params); ?>" />
<input type="hidden" name="cmd" value="_xclick" />
<input type="hidden" name="page_style" value="Primary" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="no_note" value="1" />
<input type="hidden" name="bn" value="NetFactorSL_SI_Custom" />
<input type="hidden" name="ipn_test" value="1" />
<?php
	$sd_tax = get_option('sd_tax', '');
	if(!empty($sd_tax)) print '<input type="hidden" name="tax_rate" value="'.esc_attr($sd_tax).'" />';
	do_action('sd_paypal_form_html_before_submit', $product, $purchase_id);
?>
</form>
<script type="text/javascript">document.ppform<?php print $randval; ?>.submit();</script>
<?php
            exit;
        }
    }

	header('location: '.esc_url_raw($cancel_url));
?>