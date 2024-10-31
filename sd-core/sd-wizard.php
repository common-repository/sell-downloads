<?php
if (
	isset( $_POST['sd_wizard'] ) &&
	wp_verify_nonce( $_POST['sd_wizard'], plugin_basename( __FILE__ ) )
)
{
	$sd_paypal_email = (!empty($_POST['sd_paypal_email'])) ? sanitize_email($_POST['sd_paypal_email']) : '';
	$sd_items_page = (!empty($_POST['sd_items_page']) && 0 < ($sd_items_page = @intval($_POST['sd_items_page']))) ? $sd_items_page : 10;

	update_option('sd_paypal_email', $sd_paypal_email);
	update_option('sd_items_page', $sd_items_page);

	$columns = (!empty($_POST['sd_columns']) && 0 < ($columns = @intval($_POST['sd_columns']))) ? $columns : 1;
	$sd_shortcode = '[sell_downloads columns="'.$columns.'"]';

	if(!empty($_POST['sd_shop_page_title']) && ($sd_shop_page_title = sanitize_text_field($_POST['sd_shop_page_title'])))
	{
		$page_id = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'post_title' => $sd_shop_page_title,
				'post_content' => $sd_shortcode,
				'post_status' => 'publish',
				'post_type' => 'page'
			)
		);
		update_option( 'sd_main_page', get_permalink($page_id) );
	}
	print '<div class="updated notice">'.__('Store Wizard Completed', SD_TEXT_DOMAIN).'</div>';
	if(isset($_POST['sd_wizard_goto']) && $_POST['sd_wizard_goto'] == 'products')
	{
?>
	<script>document.location.href="<?php print esc_js(admin_url('post-new.php?post_type=sd_product')); ?>";</script>
<?php
	}
}

$sd_has_been_configured = get_option('sd_has_been_configured', false);
if(get_option('sd_paypal_email', SD_PAYPAL_EMAIL) == SD_PAYPAL_EMAIL && !$sd_has_been_configured)
{
	?>
	<h1 style="text-align:center;"><?php _e('Store Wizard', SD_TEXT_DOMAIN); ?></h1>
	<form id="sd_wizard" method="post" action="<?php echo admin_url('admin.php?page=sell-downloads-menu-settings'); ?>">
		<div>
			<h3 class='hndle' style="padding:5px;"><span><?php _e('Step 1 of 2', SD_TEXT_DOMAIN); ?>: <?php _e('Payment Gateway', SD_TEXT_DOMAIN); ?></span></h3>
			<hr />
			<table class="form-table">
				<tr valign="top">
					<th scope="row" style="white-space:nowrap;">
						<?php _e('Enter the email address associated to your PayPal account', SD_TEXT_DOMAIN); ?>
					</th>
					<td>
						<input type="text" name="sd_paypal_email" size="40" placeholder="<?php _e('Email address', SD_TEXT_DOMAIN); ?>" /><br />
						<i style="font-weight:normal;"><?php _e('Leave in blank if you want distribute your products for free.', SD_TEXT_DOMAIN); ?></i>
					</td>
				</tr>
			</table>
			<div style="border:1px dotted #333333; margin-top:10px; margin-bottom:10px; padding: 10px;">Please, remember that the Instant Payment Notification (IPN) must be enabled in your PayPal account, because if the IPN is disabled PayPal does not notify the payments to your website. Please, visit the following link: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a>. PayPal needs the URL to the IPN Script in your website, however, you simply should enter the URL to the home page.</div>
			<input type="button" class="button" value="<?php esc_attr_e('Next step', SD_TEXT_DOMAIN); ?>" onclick="jQuery(this).closest('div').hide().next('div').show();">
		</div>
		<div style="display:none;">
			<h3 class='hndle' style="padding:5px;"><span><?php _e('Step 2 of 2', SD_TEXT_DOMAIN); ?>: <?php _e('Store Page', SD_TEXT_DOMAIN); ?></span></h3>
			<hr />
			<table class="form-table">
				<tr valign="top">
					<th><?php _e('Enter the shop page\'s title', SD_TEXT_DOMAIN); ?></th>
					<td>
						<input type="text" name="sd_shop_page_title" size="40" /><br />
						<i><?php _e('Leave in blank if you want to configure the shop\'s page after.', SD_TEXT_DOMAIN); ?></i>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e('Products per page', SD_TEXT_DOMAIN); ?></th>
					<td><input type="text" name="sd_items_page" value="<?php echo @intval(get_option('sd_items_page', SD_ITEMS_PAGE)); ?>" /></td>
				</tr>
				<tr valign="top">
					<th><?php _e('Number of columns', SD_TEXT_DOMAIN); ?></th>
					<td><input type="text" name="sd_columns" value="3" /></td>
				</tr>
			</table>
			<input type="hidden" id="sd_wizard_goto" name="sd_wizard_goto" value="settings" />
			<input type="button" class="button" value="<?php esc_attr_e('Previous step', SD_TEXT_DOMAIN); ?>" onclick="jQuery(this).closest('div').hide().prev('div').show();" />
			<input type="submit" class="button button-primary" value="<?php esc_attr_e('Save wizard and create my first product', SD_TEXT_DOMAIN); ?>" onclick="jQuery('#sd_wizard_goto').val('products');" />
			<input type="submit" class="button button-primary" value="<?php esc_attr_e('Save wizad and go to the store\'s settings', SD_TEXT_DOMAIN); ?>" />
		</div>
		<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sd_wizard' ); ?>
	</form>
	<script>jQuery(document).on('keydown', '#sd_wizard input[type="text"]', function(e){var code = e.keyCode || e.which;if(code == 13) {e.preventDefault();e.stopPropagation();return false;}});</script>
	<?php
	update_option('sd_has_been_configured', true);
	$wizard_active = true;
}