<?php

if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

function sell_downloads_debug($mssg){
	$h = fopen(dirname(__FILE__).'/test.txt', 'a');
	fwrite($h, $mssg.'|');
	fclose($h);
}

if(!class_exists('SDProduct')){
	class SDProduct{
		/*
		* @var integer
		*/
		private $id;

		/*
		* @var object
		*/
		private $product_data 	= array();
		private $post_data 	= array();
		private $type	= array();

		/**
		* SDProduct constructor
		*
		* @access public
		* @return void
		*/
		function __construct($id){
			global $wpdb;

			$this->id = $id;
			// Read general data
			$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_POST_DATA." WHERE id=%d", array($id)));
			if($data) $this->product_data = (array)$data;

			$this->post_data = get_post($id, 'ARRAY_A');

			// Read the file type
			$this->type = (array)wp_get_object_terms($id, 'sd_type');

		} // End __construct

		function __get($name){
			switch($name){
				case 'type':
					return $this->type;
				break;
				case 'cover':
					if(!empty($this->product_data[$name])) return $this->get_cover(true);
                    elseif(($thumbnail = get_the_post_thumbnail_url( $this->id, 'medium' )) !== false) return $thumbnail;
					return null;
				break;
				case 'file':
				case 'demo':
					if(isset($this->product_data[$name])) return $this->get_file_url($this->product_data[$name]);
					return null;
				break;
				case 'post_title':
					$post_title_tmp = trim($this->post_data['post_title']);
					if(!is_admin() && empty($post_title_tmp)) return 'Id_'.$this->id;
				default:
					if(isset($this->product_data[$name])) return sd_strip_tags($this->product_data[$name]);
					elseif(isset($this->post_data[$name])) return sd_strip_tags($this->post_data[$name]);
					return null;
			} // End switch
		} // End __get

		function __set($name, $value){
			global $wpdb;

			if(
				isset($this->product_data[$name]) &&
				$wpdb->update(
					$wpdb->prefix.SDDB_POST_DATA,
					array($name => $value),
					array('id' => $this->id)
				)
			){
				$this->product_data[$name] = $value;
			}
		} // End __set

		function __isset($name){
			return isset($this->product_data[$name]) || isset($this->post_data[$name]);
		} // End __isset

		function get_cover($main = false)
		{
			if(!empty($this->product_data['cover']))
			{
				$cover = $this->product_data['cover'];
				$covers_arr = @json_decode($cover);
				if(empty($covers_arr)) $covers_arr = array($cover);
				if($main) return $this->get_file_url($covers_arr[0]);
				// Remove the main and return the others
				array_shift($covers_arr);
				foreach($covers_arr as $key => $value)
				{
					$covers_arr[$key] = $this->get_file_url($value);
				}
				return $covers_arr;
			}
			return null;
		} // End get_cover

		/*
		* Display content
		*/
		function get_file_url($url){
			if(preg_match('/attachment_id=(\d+)/', $url, $matches)){
				return wp_get_attachment_url( $matches[1]);
			}
			return $url;
		} // End get_file_url

		function display_content($mode, $tpl_engine, $output='echo'){
            $action  = sd_complete_url(SD_H_URL.'?sd_action=buynow');
			$cover_tmp = trim( ! empty( $this->cover ) ? $this->cover : '' );
			$reviews = SD_REVIEW::get_review($this->id);

			$product_data = array(
				'id'	=> $this->id,
				'title' => apply_filters('sell-downloads-title', $this->post_title, $this->id),
				'cover' => ( empty( $cover_tmp ) ) ? null : apply_filters('sell-downloads-cover-url', esc_url($this->cover), $this->id),
				'other_images' => null,
				'alt'	=> esc_attr($this->post_title),
				'link'	=> esc_url(sd_complete_url(get_permalink($this->id))),
				'popularity' => (get_option('sd_popularity', 1)) ? (($reviews) ? @intval($reviews['average']) : 0) : null,
				'votes'		 => apply_filters('sell-downloads-votes', ($reviews) ? @intval($reviews['votes']) : 0, $this->id),
                'social' => null,
				'facebook_app_id' => null,
                'price' => null,
				'has_types' => null,

				'label_description' => esc_html(__('Description',SD_TEXT_DOMAIN)),
				'label_year'		=> esc_html(__('Year',SD_TEXT_DOMAIN)),
				'label_time'		=> esc_html(__('Time',SD_TEXT_DOMAIN)),
				'label_go_to_the_store' => esc_html(__('Go to the store page',SD_TEXT_DOMAIN)),
				'label_more' 		=> esc_html(__('More Info',SD_TEXT_DOMAIN)),
				'label_popularity'	=> esc_html(__('popularity',SD_TEXT_DOMAIN))
			);

            if(empty($product_data['cover']) && ($cover_tmp = get_option( 'sd_pp_default_cover', '' )) != '')
            {
                $product_data['cover'] = esc_url($cover_tmp);
            }

            if(get_option('sd_social_buttons')){
                $product_data['social'] = esc_url(get_permalink( $this->id ));
            }

            $facebook_app_id = get_option( 'sd_facebook_app_id', '' );
			if(!empty( $facebook_app_id ))
			{
				$product_data['facebook_app_id'] = $facebook_app_id;
			}

            if($this->time) $product_data['time'] = apply_filters('sell-downloads-time', strip_tags(html_entity_decode($this->time)), $this->id);
			if($this->year) $product_data['year'] = apply_filters('sell-downloads-year', @intval($this->year), $this->id);
			if($this->info) $product_data['info'] = apply_filters('sell-downloads-info', esc_url($this->info), $this->id);

            if(count($this->type)){
				$product_data['has_types'] = true;
				$artists = array();
				foreach($this->type as $type){
					$types[] = array('data' => '<a href="'.esc_url(sd_complete_url(get_term_link($type))).'">'.sd_strip_tags($type->name).'</a>');
				}
				$tpl_engine->set_loop('types', $types);
			}

            if(!empty($this->file)){
                if(get_option('sd_paypal_enabled') && get_option('sd_paypal_email') && !empty($this->price)){
					$price = sd_apply_taxes($this->price);
                    $currency_symbol = get_option('sd_paypal_currency_symbol', SD_PAYPAL_CURRENCY_SYMBOL);
                    $product_data['price'] = ((!empty($currency_symbol)) ? $currency_symbol.sprintf("%.2f", $price) : sprintf("%.2f", $price).get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY));
                    $paypal_button = get_option('sd_paypal_button', SD_PAYPAL_BUTTON);

					$quantity = '';
					if($mode == 'single' && $this->individually == 0)
					{
						$quantity = '<div><span style="display:block;">'.__('Quantity:', SD_TEXT_DOMAIN).'</span><input aria-label="'.__('Quantity', SD_TEXT_DOMAIN).'" type="number" name="sd_quantity" min="1" size="5" value="1" /></div>';

					}

                    $product_data['salesbutton'] = '<form action="'.$action.'" method="post"><input type="hidden" name="sd_product_type" value="single" /><input type="hidden" name="sd_product_id" value="'.$this->id.'" />'.$quantity.
					$GLOBALS['sell_downloads']->get_paypal_button(
						array(
							'button' =>$paypal_button
						)
					).
					'</form>';
                }else{
                    if(get_option('sd_download_link_for_registered_only') == false || get_current_user_id())
					{
						$product_data['salesbutton']  = '<a href="'.$this->file.'" target="_blank">'.__('Download Here', SD_TEXT_DOMAIN).'</a>';
					}
                }
            }

			if($mode == 'store' || $mode == 'multiple'){
				if($mode == 'store')
					$tpl_engine->set_file('product', 'product.tpl.html');
				else
					$tpl_engine->set_file('product', 'product_multiple.tpl.html');

				$tpl_engine->set_var('product', $product_data);
			}elseif($mode == 'single'){

				// Enqueue fancybox library
				wp_enqueue_style('sd-fancybox-style', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css', array(), SellDownloads::$version);
				wp_enqueue_script('sd-fancybox-script', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js', array('jquery'), SellDownloads::$version);

				// Additional images
				$other_images = $this->get_cover();
				if(is_array($other_images))
				{
					$product_data['other_images'] = '';
					foreach($other_images as $image)
						$product_data['other_images'] .= '<a href="'.esc_attr($image).'"><img src="'.esc_attr($image).'" /></a>';
				}

				$tpl_engine->set_file('product', 'product_single.tpl.html');
				$sd_main_page = esc_url(get_option('sd_main_page', SD_MAIN_PAGE));
				if($sd_main_page){
					$product_data['store_page'] = sd_complete_url($sd_main_page);
				}

				$demo = $this->demo;

				if( !empty( $demo ) ){
					$ext = pathinfo( $demo, PATHINFO_EXTENSION );
					$type = '';
					$class = '';

					if( !empty( $ext ) && get_option( 'sd_online_demo', SD_ONLINE_DEMO) ){
						switch( strtolower( $ext ) ){
							case 'pdf':
								$type = 'application/pdf';
							break;
							case 'ps':
								$type = 'application/postscript';
							break;
							case 'odt':
								$type = 'application/vnd.oasis.opendocument.text';
							break;
							case 'ods':
								$type = 'application/vnd.oasis.opendocument.spreadsheet';
							break;
							case 'odp':
								$type = 'application/vnd.oasis.opendocument.presentation';
							break;
							case 'sxw':
								$type = 'application/vnd.sun.xml.writer';
							break;
							case 'sxc':
								$type = 'application/vnd.sun.xml.calc';
							break;
							case 'sxi':
								$type = 'application/vnd.sun.xml.impress';
							break;
							case 'doc':
							case 'docx':
								$type = 'application/msword';
							break;
							case 'xls':
								$type = 'application/vnd.ms-excel';
							break;
							case 'ppt':
								$type = 'application/vnd.ms-powerpoint';
							break;
							case 'rtf':
								$type = 'text/rtf';
							break;
							case 'txt':
								$type = 'text/plain';
							break;
							case 'wav':
							case 'mp3':
							case 'ogg':
							case 'mid':
								$type = 'audio';
							break;
							case 'mpg':
							case 'avi':
							case 'wmv':
							case 'mov':
							case 'mp4':
							case 'm4v':
							case 'flv':
								$type = 'video';
							break;

						}
					}

					$base  = SD_H_URL.(strpos(SD_H_URL, '?') === false ? '?' : '&');
					if( !empty( $type ) ){

						switch( $type ){
							case 'audio':
								$product_data['demo'] = '<br /><audio class="sd-demo-media" src="'.esc_url($demo).'" controls="controls"></audio><br />';
							break;
							case 'video':
								$product_data['demo'] = '<br /><video controls="controls" style="max-width:100%" class="sd-demo-media" src="'.esc_url($demo).'"></video><br />';
							break;
							default:
								$type = 'mtype="'.esc_attr($type).'"';
								$class = 'class="sd-demo-link"';
								$product_data['demo'] = '<a href="'.esc_attr($base.'sd_action=demo&file='.urlencode($demo)).'" target="_blank" '.$type.' '.$class.' >'.__('View File Demo', SD_TEXT_DOMAIN).'</a>';
						}
					} else {
						$product_data['demo'] = '<a href="'.esc_attr($base.'sd_action=demo&file='.urlencode($demo)).'" target="_blank" '.$type.' '.$class.' >'.__('Download File Demo', SD_TEXT_DOMAIN).'</a>';
					}
				} else {
					$product_data['demo'] = '';
				}

				if(strlen($this->post_content)){
					$product_data['description'] 	= apply_filters('sell-downloads-content', '<p>'.sd_strip_tags(preg_replace('/[\n\r]+/', '</p><p>', $this->post_content)).'</p>', $this->id);
				}

				$tpl_engine->set_var('product', $product_data);
			}

			// Custom fields
			$custom_fields = get_post_custom($this->id);
			$hidden_field = '_';
			foreach( $custom_fields as $key => $value )
			{
				if( !empty($value) )
				{
					$pos = strpos($key, $hidden_field);
					if( $pos !== false && $pos == 0 )  continue;
					elseif(is_array($value) && count($value)==1) $custom_fields[$key] = $value[0];

					if(is_array($custom_fields[$key])) $tpl_engine->set_loop($key, $custom_fields[$key]);
					else $tpl_engine->set_var($key, $custom_fields[$key]);
				}
			}

			return $tpl_engine->parse('product', $output);
		} // End display

		/*
		* Class method print_metabox, for metabox generation print
		*
		* @return void
		*/
		public static function print_metabox(){
			global $wpdb, $post;

			$obj  = new self($post->ID);
			$type_post_list = wp_get_object_terms($post->ID, 'sd_type');
			$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));

			wp_nonce_field( plugin_basename( __FILE__ ), 'sd_product_box_content_nonce' );

			if( !empty( $GLOBALS[SD_SESSION_NAME][ 'sd_errors' ] ) )
			{
				echo '<div class="sd-error-mssg">'.implode( '<br>', sd_strip_tags($GLOBALS[SD_SESSION_NAME][ 'sd_errors' ]) ).'</div>';
				unset($GLOBALS[SD_SESSION_NAME][ 'sd_errors' ]);
			}

			echo '
				<table class="form-table product-data">
					<tr>
						<td style="border-top:2px solid purple;border-left:2px solid purple;">
							'.__('Sales price:', SD_TEXT_DOMAIN).'
						</td>
						<td style="border-top:2px solid purple;border-right:2px solid purple;">
							<input type="text" name="sd_price" id="sd_price" value="'.esc_attr($obj->price ? esc_attr(sprintf("%.2f", $obj->price)) : '').'" /> '.get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY).'
                            <span class="sd_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="sd_display_more_info( this );">[ + more information]</a></span>
                            <div class="sd_more_info">
                                <p>If let empty the product\'s price, the Sell Downloads assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
                                <a href="javascript:void(0)" onclick="sd_hide_more_info( this );">[ + less information]</a>
                            </div>
						</td>
					</tr>
					<tr>
						<td style="border-left:2px solid purple;border-bottom:2px solid purple;">
							'.__('File for sale:', SD_TEXT_DOMAIN).'
						</td>
						<td style="border-right:2px solid purple;border-bottom:2px solid purple;">
							<input type="text" name="sd_file_path" class="file_path" id="sd_file_path" value="'.esc_url($obj->file ? $obj->file : '').'" placeholder="'.esc_attr(__('File path/URL', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_for_upload_sd button" value="'.esc_attr(__('Upload a file', SD_TEXT_DOMAIN)).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('File for demo:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_demo_file_path" id="sd_demo_file_path" class="file_path"  value="'.esc_url($obj->demo ? $obj->demo : '').'" placeholder="'.esc_attr(__('File path/URL', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_for_upload_sd button" value="'.esc_attr(__('Upload a file', SD_TEXT_DOMAIN)).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('Sold individually:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="checkbox" name="sd_individually" id="sd_individually" '.((empty($obj->individually) || $obj->individually) ? 'CHECKED' : '').' />
						</td>
					</tr>
					<tr>
						<td valign="top">
							'.__('File type:', SD_TEXT_DOMAIN).'
						</td>
						<td><div id="sd_type_list">';
						if($type_post_list){
							foreach($type_post_list as $type){
								echo '<div class="sd-property-container"><input type="hidden" name="sd_type[]" value="'.esc_attr($type->name).'" /><input type="button" onclick="sd_remove(this);" class="button" value="'.esc_attr($type->name).' [x]"></div>';
							}

						}
						echo '</div><div style="clear:both;"><select onchange="sd_select_element(this, \'sd_type_list\', \'sd_type\');"><option value="none">'.__('Select an File Type', SD_TEXT_DOMAIN).'</option>';
						if($type_list){
							foreach($type_list as $type){
								echo '<option value="'.esc_attr($type->name).'">'.sd_strip_tags($type->name,true).'</option>';
							}
						}
						echo '
								 </select>
								 <input type="text" id="new_type" placeholder="'.esc_attr(__('Enter a new file type', SD_TEXT_DOMAIN)).'">
								 <input type="button" value="'.esc_attr(__('Add file type', SD_TEXT_DOMAIN)).'" class="button" onclick="sd_add_element(\'new_type\', \'sd_type_list\', \'sd_type_new\');"/><br />
								 <span class="sd-comment">'.__('Select an File Type from the list or enter new one', SD_TEXT_DOMAIN).'</span>
							</div>
						</td>
					</tr>
					<tr>
						<td style="vertical-align:top;">
							'.__('Image:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<div class="sd-covers">
								<div class="sd-cover">
									<input type="text" name="sd_cover[]" class="file_path" id="sd_cover" value="'.esc_url(($obj->cover) ? $obj->cover : '').'" placeholder="'.esc_attr(__('File path/URL', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_for_upload_sd button" value="'.esc_attr(__('Upload a file', SD_TEXT_DOMAIN)).'" />
								</div>
						';
						$covers = $obj->get_cover();
						if(!empty($covers))
						{
							foreach($covers as $cover)
							{
								echo '
									<div class="sd-cover">
										<input type="text" name="sd_cover[]" class="file_path" id="sd_cover" value="'.esc_attr($cover).'" placeholder="'.esc_attr(__('File path/URL', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_for_upload_sd button" value="'.esc_attr(__('Upload a file', SD_TEXT_DOMAIN)).'" />  <input type="button" class="button_remove_cover_sd button" value="'.esc_attr(__('Delete', SD_TEXT_DOMAIN)).'" />
									</div>';
							}
						}
						echo '
							</div>
							<input type="button" class="add_new_cover_sd button" value="'.esc_attr(__('Add Image', SD_TEXT_DOMAIN)).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('Duration:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_time" id="sd_time" value="'.esc_attr($obj->time ? $obj->time : '').'" /> <span class="sd-comment">'.__('For example 00:00', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td>
							'.__('Publication Year:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_year" id="sd_year" value="'.esc_attr($obj->year ? $obj->year : '').'" /> <span class="sd-comment">'.__('For example 1999', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td style="white-space:nowrap;">
							'.__('Additional information:', SD_TEXT_DOMAIN).'
						</td>
						<td style="width:100%;">
							<input type="text" name="sd_info" id="sd_info" value="'.esc_url($obj->info ? $obj->info : '').'" placeholder="'.esc_attr(__('Page URL', SD_TEXT_DOMAIN)).'" /> <span class="sd-comment">'.__('Different webpage with additional information', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
								For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a>
							</p>
						</td>
					</tr>
				</table>
			';
			echo '
				<pre style="display:none;">
					<script>
						sd_cover_template = \'<div class="sd-cover">\'+
						\'<input type="text" name="sd_cover[]" class="file_path" value="" placeholder="'.esc_attr(__('File path/URL', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_for_upload_sd button" value="'.esc_attr(__('Upload a file', SD_TEXT_DOMAIN)).'" /> <input type="button" class="button_remove_cover_sd button" value="'.esc_attr(__('Delete', SD_TEXT_DOMAIN)).'" />\'+
						\'</div>\';
					</script>
				</pre>
			';
		} // End print_metabox

        public static function print_discount_metabox(){
			$currency = get_option('sd_paypal_currency', SD_PAYPAL_LANGUAGE);
            ?>

            <!--DISCOUNT BOX -->
            <div style="color:#FF0000;">The discounts management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads">Press Here</a></div>
            <h4><?php _e('Scheduled Discounts', SD_TEXT_DOMAIN);?></h4>
            <table class="form-table sd_discount_table" style="border:1px dotted #dfdfdf;">
                <tr>
                    <td style="font-weight:bold;"><?php _e('New price in '.$currency, SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                    <td></td>
                </tr>
            </table>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('New price (*)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /> <?php echo $currency; ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Valid from (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Valid to (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></th>
                    <td><textarea DISABLED cols="60"></textarea></td>
                </tr>
                <tr><td colspan="2"><input type="button" class="button" value="<?php _e('Add/Update Discount'); ?>" DISABLED ></td></tr>
            </table>
            <?php
        } // End print_discount_metabox


		/*
		* Save the song data
		*
		* @access public
		* @return void
		*/
		public static function save_data($post){
			global $wpdb, $sd_errors;

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

			if ( empty($_POST['sd_product_box_content_nonce']) || !wp_verify_nonce( $_POST['sd_product_box_content_nonce'], plugin_basename( __FILE__ ) ) )
			return;

			$id = $post->ID;

			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $id ) )
				return;
			} else {
				if ( !current_user_can( 'edit_post', $id ) )
				return;
			}

			$file_path = esc_url_raw(sanitize_text_field(stripcslashes($_POST['sd_file_path'])));
			$demo_file_path = esc_url_raw(sanitize_text_field(stripcslashes($_POST['sd_demo_file_path'])));
			$covers = array();
			if(is_array($_POST['sd_cover']))
			{
				foreach($_POST['sd_cover'] as $sd_cover)
				{
					$sd_cover = esc_url_raw(sanitize_text_field(stripcslashes($sd_cover)));
					if(!empty($sd_cover)){
						if(!sd_mime_type_accepted($sd_cover))
							sell_downloads_setError( __( 'Invalid file type for cover.', SD_TEXT_DOMAIN ) );
						else
							$covers[] = $sd_cover;
					}
				}
			}

			if( !empty( $file_path ) && !sd_mime_type_accepted( $file_path ) ){
				sell_downloads_setError( __( 'Invalid file type for selling.', SD_TEXT_DOMAIN ) );
				$file_path = '';
			}

			if( !empty( $demo_file_path ) && !sd_mime_type_accepted( $demo_file_path ) ){
				sell_downloads_setError( __( 'Invalid file type for demo.', SD_TEXT_DOMAIN ) );
				$demo_file_path = '';
			}

			$GLOBALS[SD_SESSION_NAME][ 'sd_errors' ] = $sd_errors;

			$data = array(
						'time'  	=> strip_tags(sanitize_text_field(stripcslashes($_POST['sd_time']))),
						'file'  	=> $file_path,
						'demo'  	=> $demo_file_path,
						'info' 		=> esc_url_raw(sanitize_text_field(stripcslashes($_POST['sd_info']))),
						'cover' 	=> !empty($covers) ? json_encode($covers) : null,
						'price' 	=> @floatval(stripcslashes($_POST['sd_price'])),
						'year'      => @intval(stripcslashes($_POST['sd_year'])),
						'individually' => (isset($_POST['sd_individually'])) ? 1 : 0
					);
			$format = array('%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d');
			$table = $wpdb->prefix.SDDB_POST_DATA;
			if(0 < $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id=%d;", $id) ) ){
				// Set an update query
				$wpdb->update(
					$table,
					$data,
					array('id'=>$id),
					$format,
					array('%d')
				);

			}else{
				// Set an insert query
				$data['id'] = $id;
				$wpdb->insert(
					$table,
					$data,
					$format
				);
			}

			// Clear the file types lists and then set the new ones
			wp_set_object_terms($id, null, 'sd_type');

			// Set the file types list
			if(isset($_POST['sd_type'])){
				if(is_array($_POST['sd_type']))
				{
					foreach($_POST['sd_type'] as $index => $sd_type)
					{
						$_POST['sd_type'][$index] = sanitize_text_field(stripcslashes($sd_type));
					}
				}
				else
				{
					$_POST['sd_type'] = sanitize_text_field(stripcslashes($_POST['sd_type']));
				}
				wp_set_object_terms($id, $_POST['sd_type'], 'sd_type', true);
			}

			if(isset($_POST['sd_type_new'])){
				if(is_array($_POST['sd_type_new']))
				{
					foreach($_POST['sd_type_new'] as $index => $sd_type_new)
					{
						$_POST['sd_type_new'][$index] = sanitize_text_field(stripcslashes($sd_type_new));
					}
				}
				else
				{
					$_POST['sd_type_new'] = sanitize_text_field(stripcslashes($_POST['sd_type_new']));
				}
				wp_set_object_terms($id, $_POST['sd_type_new'], 'sd_type', true);
			}
		} // End save_data

		/*
		* Create the list of properties to display of products
		* @param array
		* @return array
		*/
		public static function columns($columns){
			$product_columns = array(
				'cb'	 => '<input type="checkbox" />',
				'id'	 => __( 'Product Id', SD_TEXT_DOMAIN),
				'title'	 => __( 'Product Name', SD_TEXT_DOMAIN),
				'type'  =>  __( 'File Type', SD_TEXT_DOMAIN),
				'popularity'  => __( 'Popularity', SD_TEXT_DOMAIN),
				'purchases' => __( 'Purchases', SD_TEXT_DOMAIN),
				'date'	 => __( 'Date', SD_TEXT_DOMAIN)
			);

			if ( wp_is_mobile() ) {
				unset( $product_columns['id'] );
			}

			return $product_columns;
		} // End columns

		/*
		* Extrat the songs data for song list
		*/
		public static function columns_data($column){
			global $post;
			$obj = new SDProduct($post->ID);

			switch ($column){
				case "type":
					echo sell_downloads_extract_attr_as_str($obj->type, 'name', ', ');
				break;
				case "id":
					echo $post->ID;
				break;
				case "popularity":
					echo $obj->popularity;
				break;
				case "purchases":
					echo $obj->purchases;
				break;
			} // End switch
		} // End columns_data

	}// End SDProduct class
} // Class exists check

?>