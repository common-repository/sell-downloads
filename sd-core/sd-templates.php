<?php
if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

// Initializing variables
$tpl = new sell_downloads_tpleng(SD_FILE_PATH.'/sd-templates/sources/', 'comment');
wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

?>
<div class="wrap">
	<h1 style="margin-bottom:30px;"><?php _e('Customizing the Products Templates', SD_TEXT_DOMAIN); ?></h1>
<?php
if(isset($_POST['sd_templates']) && wp_verify_nonce( $_POST['sd_templates'], 'sd_templates_nonce' ))
{
	$message = '';
	if(isset($_POST['sd_default_templates']))
	{
		update_option('product_single.tpl.html', $tpl->get_template_content('product_single.tpl.html', true));
		update_option('product_multiple.tpl.html', $tpl->get_template_content('product_multiple.tpl.html', true));
		update_option('product.tpl.html', $tpl->get_template_content('product.tpl.html', true));

		$message = __("Default Templates Reloaded", SD_TEXT_DOMAIN);
	}
	else
	{
		$_POST = array_map('stripcslashes', $_POST);
		update_option('sd_custom_templates_active', (isset($_POST['sd_custom_templates_active'])) ? 1 : 0);
		$allowed_tags['tpl'] = array( 'ifset' => true );
		if(!empty($_POST['product_single_tpl'])) update_option('product_single.tpl.html', $_POST['product_single_tpl']);
		if(!empty($_POST['product_multiple_tpl'])) update_option('product_multiple.tpl.html', $_POST['product_multiple_tpl']);
		if(!empty($_POST['product_tpl'])) update_option('product.tpl.html', $_POST['product_tpl']);

		$message = __("Templates Updated", SD_TEXT_DOMAIN);
	}
?>
	<div class="updated" style="margin:5px 0;"><strong><?php print $message; ?></strong></div>
<?php
}

?>
	<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
		<?php _e('This section is accessible only by website administrators. Please, be careful when editing the templates. If editing the templates breaks the products or store pages, first try disabling the custom templates or reload the default ones.', SD_TEXT_DOMAIN); ?>
	</p>
	<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
	<?php _e('If have been associated custom fields to the products, they can be displayed in the pages of products and store. For example, if has been associated to the product the custom field: my_field, it is possible to include a block similar to the following one, as part of the template structure:<br><br>
	&lt;tpl ifset="my_field"&gt;<br>&lt;div&gt;&lt;label&gt;The label text:&lt;/label&gt;{my_field}&lt;/div&gt;<br>&lt;/tpl ifset="my_field"&gt;', SD_TEXT_DOMAIN); ?>
	</p>
	<div id="cff_templates_help" style="display:none;position:fixed;width:400px;height:400px;right: 40px; top: 40px; z-index: 9999; background-color:white; border:1px solid #DADADA;">
		<div style="text-align:right;padding:5px;"><a href="javascript:jQuery('#cff_templates_help').hide();">X</a></div>
		<div style="height:340px; overflow:auto;padding:10px;">
<!-- Help -->
<p>The templates are basically html tags, and some few <b>&lt;tpl&gt;</b> tag and <b>vars</b>.</p>
<p>The <b>&lt;tpl&gt;</b> tags, similar to the html tags, are composed of an open and close tag, and accept some self explained attributes, they works as conditional tags:</p>
<p>
<pre>
&lt;tpl
ifset=&quot;product.cover&quot;&gt;
&lt;div class=&quot;product-cover single&quot;&gt;
&lt;img src=&quot;{product.cover}&quot;&gt;
&lt;/div&gt;
&lt;/tpl ifset=&quot;product.cover&quot;&gt;
</pre>
</p>
<p>In the previous block of code  <b>&lt;tpl ifset=&quot;product.cover&quot;&gt;&lt;/tpl ifset=&quot;product.cover&quot;&gt;</b> are the open and close  <b>&lt;tpl&gt;</b> tags, and this means:
</p>
<p>Include the tags:</p>
<p>
<pre>
&lt;div class=&quot;product-cover single&quot;&gt;
&lt;img src=&quot;{product.cover}&quot;&gt;
&lt;/div&gt;
</pre>
</p>
<p>in the page, only if product includes a cover image.</p>
<p>The following example, uses the <b>&lt;tpl&gt;</b> tag with the <b>"loop"</b> attribute:</p>
<p>
<pre>
&lt;tpl loop=&quot;types&quot;&gt;
&lt;li&gt;&lt;span class=&quot;arrow&quot;&gt;&rsaquo;&lt;/span&gt;{types.data}&lt;/li&gt;
&lt;/tpl loop=&quot;types&quot;&gt;
</pre>
</p>
<p>Similar to the previous example, the tags <b>&lt;tpl loop=&quot;types&quot;&gt;&lt;/tpl loop=&quot;types&quot;&gt;</b> are the open and close &lt;tpl&gt; tags, and this means:</p>
<p>Repeat the tags:</p>
<p>
<pre>
&lt;li&gt;&lt;span class=&quot;arrow&quot;&gt;&rsaquo;&lt;/span&gt;{types.data}&lt;/li&gt;
</pre>
</p>
<p>for every item in the "types" array.</p>
<p>The variables, as you have surely sensed, are represented between symbols: <b>"{}"</b>, in the previous examples, to access the URL of the cover image in products was used the variable: <b>{product.cover}</b>, and for accessing to the files types information: <b>{types.data}</b></p>
<!-- End Help -->
		</div>
	</div>
	<form method="post" action="<?php echo admin_url('admin.php?page=sell-downloads-menu-templates'); ?>">
		<div class="postbox">
			<div class="inside">
				<div style="border-bottom:1px solid #DADADA;padding-bottom:20px; margin-bottom:20px;">
					<label>
						<input type="checkbox" name="sd_custom_templates_active" <?php if(get_option('sd_custom_templates_active')) print 'CHECKED'; ?>>
						<?php _e('Using custom templates', SD_TEXT_DOMAIN)?>
					</label>
					<a href="javascript:jQuery('#cff_templates_help').show();" style="float:right;"><?php _e('Help?', SD_TEXT_DOMAIN); ?></a>
				</div>

				<h2><?php _e('Products Templates', SD_TEXT_DOMAIN); ?></h2>
				<div>
					<p><b><?php _e('Template used on the products pages', SD_TEXT_DOMAIN); ?></b></p>
					<p>
						<textarea name="product_single_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea($tpl->get_template_content('product_single.tpl.html'));
						?></textarea>
					</p>
				</div>
				<div>
					<p><b><?php _e('Template used by the products on the shop pages', SD_TEXT_DOMAIN); ?></b></p>
					<p>
						<textarea name="product_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea($tpl->get_template_content('product.tpl.html'));
						?></textarea>
					</p>
				</div>
				<div>
					<p><b><?php _e('Template used by the products on the archive pages (like file type)', SD_TEXT_DOMAIN); ?></b></p>
					<p>
						<textarea name="product_multiple_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea($tpl->get_template_content('product_multiple.tpl.html'));
						?></textarea>
					</p>
				</div>
				<input type="submit" value="<?php esc_attr_e(__('Update', SD_TEXT_DOMAIN)); ?>" class="button-primary" />
				<input type="button" name="sd_reload_template_button" value="<?php esc_attr_e(__('Reload Default Templates', SD_TEXT_DOMAIN)); ?>" class="button-secondary" style="float:right;" />
			</div>
		</div>
		<?php
		wp_nonce_field( 'sd_templates_nonce', 'sd_templates' );
		?>
	</form>
</div>
<script>
jQuery(document).on('click', '[name="sd_reload_template_button"]', function(){
	if(confirm('<?php print esc_js( __("Do you really want to reload the default templates?", SD_TEXT_DOMAIN)); ?>'))
		jQuery(this).closest('form').append('<input type="hidden" name="sd_default_templates" value="1">').submit();
});
(function($){
    $(function(){
        if('codeEditor' in wp) {
            var editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
            editorSettings.codemirror = _.extend(
                {},
                editorSettings.codemirror,
                {
                    indentUnit: 2,
                    tabSize: 2,
					autoCloseTags: false
                }
            );
			editorSettings['htmlhint']['spec-char-escape'] = false;
			editorSettings['htmlhint']['alt-require'] = false;
			editorSettings['htmlhint']['tag-pair'] = false;
			wp.codeEditor.initialize( $('[name="product_single_tpl"]'), editorSettings );
            wp.codeEditor.initialize( $('[name="product_multiple_tpl"]'), editorSettings );
            wp.codeEditor.initialize( $('[name="product_tpl"]'), editorSettings );
        }
    });
 })(jQuery);
</script>