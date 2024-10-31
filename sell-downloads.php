<?php
/*
Plugin Name: Sell Downloads
Plugin URI: http://wordpress.dwbooster.com/content-tools/sell-downloads
Version: 1.1.7
Author: CodePeople
Author URI: http://wordpress.dwbooster.com/content-tools/sell-downloads
Text Domain: sell-downloads
Description: Sell Downloads is an online store for selling downloadable files: audio, video, documents, pictures all that may be published in Internet. Sell Downloads uses PayPal as payment gateway, making the sales process easy and secure.
*/

// Feedback system
require_once 'feedback/cp-feedback.php';
new CP_FEEDBACK('sell-downloads', __FILE__, 'https://wordpress.dwbooster.com/contact-us');

require_once 'banner.php';
$codepeople_promote_banner_plugins[ 'codepeople-sell-downloads' ] = array(
	'plugin_name' => 'Sell Downloads',
	'plugin_url'  => 'https://wordpress.org/support/plugin/sell-downloads/reviews/#new-post'
);

 // CONSTANTS
 define( 'SD_FILE_PATH', dirname( __FILE__ ) );
 define( 'SD_URL', plugins_url( '', __FILE__ ) );
 define( 'SD_H_URL', rtrim( get_home_url( get_current_blog_id() ), "/" ).( ( strpos( get_current_blog_id(), '?' ) === false ) ? "/" : "" ) );
 define( 'SD_DOWNLOAD', dirname( __FILE__ ).'/sd-downloads' );
 define( 'SD_OLD_DOWNLOAD_LINK', 3); // Number of days considered old download links
 define( 'SD_DOWNLOADS_NUMBER', 3);  // Number of downloads by purchase
 define( 'SD_CORE_IMAGES_URL',  SD_URL . '/sd-core/images' );
 define( 'SD_CORE_IMAGES_PATH', SD_FILE_PATH . '/sd-core/images' );
 define( 'SD_TEXT_DOMAIN', 'sell-downloads' );
 define( 'SD_MAIN_PAGE', false ); // The location to the sell downloads main page
 define( 'SD_REMOTE_TIMEOUT', 300 ); // wp_remote_get timeout

 // PAYPAL CONSTANTS
 define( 'SD_PAYPAL_EMAIL', '' );
 define( 'SD_PAYPAL_ENABLED', true );
 define( 'SD_PAYPAL_CURRENCY', 'USD' );
 define( 'SD_PAYPAL_CURRENCY_SYMBOL', '$' );
 define( 'SD_PAYPAL_LANGUAGE', 'en_US' );
 define( 'SD_PAYPAL_BUTTON', 'button_d.gif' );
 define( 'SD_PAYPAL_ADD_CART_BUTTON', 'shopping_cart/button_e.gif' );
 define( 'SD_PAYPAL_VIEW_CART_BUTTON', 'shopping_cart/button_f.gif' );

 // NOTIFICATION CONSTANTS
 define( 'SD_NOTIFICATION_FROM_EMAIL', 'put_your@emailhere.com' );
 define( 'SD_NOTIFICATION_TO_EMAIL', 'put_your@emailhere.com' );
 define( 'SD_NOTIFICATION_TO_PAYER_SUBJECT', 'Thank you for your purchase...' );
 define( 'SD_NOTIFICATION_TO_SELLER_SUBJECT','New product purchased...' );
 define( 'SD_NOTIFICATION_TO_PAYER_MESSAGE', "We have received your purchase notification with the following information:\n\n%INFORMATION%\n\nThank you.\n\nBest regards." );
 define( 'SD_NOTIFICATION_TO_SELLER_MESSAGE', "New purchase made with the following information:\n\n%INFORMATION%\n\nBest regards." );

 // DISPLAY CONSTANTS
 define('SD_ITEMS_PAGE', 10);
 define('SD_ITEMS_PAGE_SELECTOR', true);
 define('SD_FILTER_BY_TYPE', true);
 define('SD_FILTER_BY_CATEGORY', true);
 define('SD_ORDER_BY_POPULARITY', true);
 define('SD_ORDER_BY_PRICE', true);
 define('SD_ONLINE_DEMO', false);
 define('SD_SAFE_DOWNLOAD', false);

 // TABLE NAMES
 define( 'SDDB_POST_DATA', 'msdb_post_data');
 define( 'SDDB_POST_COLLECTION', 'msdb_post_collection');
 define( 'SDDB_PURCHASE', 'msdb_purchase');
 define( 'SDDB_SHOPPING_CART', 'msdb_shopping_cart');

 include "sd-core/sd-functions.php";
 include "sd-core/sd-product.php";
 include "sd-core/tpleng.class.php";

 add_filter('option_sbp_settings', array('SellDownloads', 'troubleshoot'));

 // Load files
 require_once SD_FILE_PATH.'/sd-core/sd-review.php';

 // Load the addons
 function sd_loading_add_ons()
 {
	$path = dirname( __FILE__ ).'/sd-addons';
	if( file_exists( $path ) )
	{
		$addons = dir( $path );
		while( false !== ( $entry = $addons->read() ) )
		{
			if( strlen( $entry ) > 3 && strtolower( pathinfo( $entry, PATHINFO_EXTENSION) ) == 'php' )
			{
				require_once $addons->path.'/'.$entry;
			}
		}
	}
 }
 sd_loading_add_ons();

 if ( !class_exists( 'SellDownloads' ) ) {
 	 /**
	 * Main SellDownloads Class
	 *
	 * Contains the main functions for Sell Downloads, stores variables, and handles error messages
	 *
	 * @class SellDownloads
	 * @version	1.0.1
	 * @since 1.4
	 * @package	SellDownloads
	 * @author CodePeople
	 */

	class SellDownloads{

		static public $version = '1.1.7';
		var $sell_downloads_slug = 'sell-downloads-menu';
        var $layouts = array();
		var $layout = array();

		/**
		* SellDownloads constructor
		*
		* @access public
		* @return void
		*/
		function __construct(){
			add_action('after_setup_theme', array(&$this, 'after_setup_theme'), 1);
			add_action('init', array(&$this, 'init'), 1);
			add_action('admin_init', array(&$this, 'admin_init'), 1);
            add_action('current_screen', array($this, '_permalinks_screen'));

			// Set the menu link
			add_action('admin_menu', array(&$this, 'menu_links'), 10);

            // Load selected layout
			if ( false !== get_option( 'sd_layout' ) )
			{
				$this->layout = get_option( 'sd_layout' );
			}

			// Integration with the pages builders
			require_once dirname(__FILE__).'/sd-page-builder/sd-page-builders.php';
			SD_PAGE_BUILDERS::run();

			// Add a post display state for special pages.
			add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );

			//Reject Cache URIs
			$this->_reject_cache_uris();

		} // End __constructor

/** INITIALIZE PLUGIN FOR PUBLIC WORDPRESS AND ADMIN SECTION **/

		function after_setup_theme()
		{
			// I18n
			load_plugin_textdomain(SD_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

			$this->init_taxonomies(); // Init SellDownloads taxonomies
			$this->init_post_types(); // Init SellDownloads custom post types

		} // End after_setup_theme

		/**
		* Init SellDownloads when WordPress Initialize
		*
		* @access public
		* @return void
		*/
		function init(){
            global $wpdb;

			add_action('save_post', array(&$this, 'save_data'), 10, 3);

			if ( ! is_admin()){
                add_filter('get_pages', array( &$this, '_sd_exclude_pages') ); // for download-page

				// Check parameter
                if(isset($_REQUEST['sd_action'])){
                    switch($_REQUEST['sd_action']){
                        case 'buynow':
                            include_once('sd-core/sd-submit.php');
                        break;
                        case 'demo':
                            if( isset($_REQUEST['file']) ){
								$f_url = stripslashes(wp_kses_decode_entities($_REQUEST['file']));
								if($wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->prefix".SDDB_POST_DATA." WHERE demo=%s", $f_url ) ))
								{
									if( !sd_mime_type_accepted( $f_url ) )
									{
										_e( 'Invalid File Type', SD_TEXT_DOMAIN );
										exit;
									}

									$f_content = @file_get_contents($f_url);
									if($f_content !== false){
										$f_name = substr($f_url, strrpos($f_url, '/')+1);
										header('Content-Type: '.sd_mime_content_type($f_name));
										header('Content-Disposition: attachment; filename="'.esc_attr($f_name).'"');
										print $f_content;
									}else{
										print '<script>document.location.href = "'.esc_url_raw($f_url).'";</script>';
									}
									exit;
								}
                            }
                        break;
						case 'f-download':
							sd_download_file();
							exit;
						break;
						case 'popularity':
							if(!headers_sent()) header('Content-Type: application/json');
							if(
								isset($_POST['id']) &&
								($id = @intval($_POST['id'])) != 0 &&
								isset($_POST['review']) &&
								($review = @intval($_POST['review'])) <= 5 &&
								1 <= $review
							)
							{
								SD_REVIEW::set_review($id,$review);
								$data = SD_REVIEW::get_review($id);
								if($data) exit(json_encode($data));
							}
							exit(json_encode(array('error' => true)));
						break;
						default:
							$sd_action = sanitize_text_field( wp_unslash( $_REQUEST['sd_action'] ) );
							if(
								stripos($sd_action,'ipn|') !== false ||
								( $_GET['sd_action'] = get_transient( $sd_action ) ) !== false
							)
							{
								delete_transient( $sd_action );
								if(false != get_option('sd_debug_payment'))
								{
									try
									{
										@error_log('Sell Downloads payment gateway GET parameters: '.json_encode($_GET));
										@error_log('Sell Downloads payment gateway POST parameters: '.json_encode($_POST));
									}
									catch(Exception $err){}
								}
								include_once('sd-core/sd-ipn.php');exit;
							}
                        break;

                    }
                }

				// Set custom post_types on search result
				add_shortcode('sell_downloads', array(&$this, 'load_store'));
                add_shortcode('sell_downloads_product', array(&$this, 'load_store_product'));
				add_filter( 'the_content', array( &$this, '_sd_the_content' ), 99 );
				add_filter( 'the_excerpt', array( &$this, '_sd_the_excerpt' ), 1 );
				add_filter( 'get_the_excerpt', array( &$this, '_sd_the_excerpt' ), 1 );
				add_action( 'wp_head', array( &$this, 'load_meta'));
                $this->load_templates(); // Load the sell downloads template for songs and collections display

				// Load public resources
				add_action( 'wp_enqueue_scripts', array(&$this, 'public_resources'), 10 );

				// Search functions
				if( get_option( 'sd_search_taxonomy', false ) )
				{
					add_filter( 'posts_where', array( &$this, 'custom_search_where' ) );
					add_filter( 'posts_join', array( &$this, 'custom_search_join' ) );
					add_filter( 'posts_groupby', array( &$this, 'custom_search_groupby' ) );
				}

				// Display Preview
				$this->_preview();
			}

			// Init action
			do_action( 'selldownloads_init' );
		} // End init

        /************ PERMALINKS ************/

		function _permalinks_screen()
        {
            if( ! function_exists( 'get_current_screen' ) ) { return; }
			$screen = get_current_screen();

            if(!$screen || $screen->id != 'options-permalink') return;
            self::save_permalink();
            add_settings_section( 'sell-downloads-permalink', __( 'Sell Downloads Permalinks', SD_TEXT_DOMAIN ), function(){
                $GLOBALS['sell_downloads']::permalink_settings();
            }, 'permalink' );
        } // End _permalinks_screen

        static function get_permalink($base)
        {
            return get_option($base.'_permalink', $base);
        } // End get_permalink

        static function save_permalink()
        {
            if(isset($_POST['sd_product_permalink']))
            {
                $permalink = sd_sanitize_permalink(wp_unslash($_POST['sd_product_permalink']));
                if(empty($permalink)) $permalink = 'sd_product';
                update_option('sd_product_permalink', $permalink);
            }

            if(isset($_POST['sd_type_permalink']))
            {
                $permalink = sd_sanitize_permalink(wp_unslash($_POST['sd_type_permalink']));
                if(empty($permalink)) $permalink = 'sd_type';
                update_option('sd_type_permalink', $permalink);
            }

            if(isset($_POST['sd_category_permalink']))
            {
                $permalink = sd_sanitize_permalink(wp_unslash($_POST['sd_category_permalink']));
                if(empty($permalink)) $permalink = 'sd_category';
                update_option('sd_category_permalink', $permalink);
            }
        } // End save_permalink

        static function permalink_settings()
        {
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label><?php esc_html_e( 'Sell Downloads Product permalink', SD_TEXT_DOMAIN ); ?></label></th>
                        <td>
                            <input name="sd_product_permalink" id="sd_product_permalink" type="text" value="<?php echo esc_attr(self::get_permalink('sd_product')); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', SD_TEXT_DOMAIN ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Product Type permalink', SD_TEXT_DOMAIN ); ?></label></th>
                        <td>
                            <input name="sd_type_permalink" id="sd_type_permalink" type="text" value="<?php echo esc_attr(self::get_permalink('sd_type')); ?>" class="regular-text code">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Product Category permalink', SD_TEXT_DOMAIN ); ?></label></th>
                        <td>
                            <input name="sd_category_permalink" id="sd_category_permalink" type="text" value="<?php echo esc_attr(self::get_permalink('sd_category')); ?>" class="regular-text code">
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        } // End permalink_settings

		function load_meta( ){
            global $post;
            if( isset( $post ) ){
                if( $post->post_type == 'sd_product' ) $obj = new SDProduct( $post->ID );

				if( !empty( $obj ) )
				{
					$output = '';

					if( isset( $obj->cover ) ) $output .= '<meta property="og:image" content="'.esc_url( $obj->cover ).'" />';
					if( !empty( $obj->post_title ) ) $output .= '<meta property="og:title" content="'.esc_attr( $obj->post_title ).'" />';
					if( !empty( $obj->post_excerpt ) ) $output .= '<meta property="og:description" content="'.esc_attr( $obj->post_excerpt ).'" />';
					elseif( !empty( $obj->post_content ) ) $output .= '<meta property="og:description" content="'.esc_attr( wp_trim_words( $obj->post_content ) ).'" />';

					$types = array();
					if( is_array( $obj->type ) && count( $obj->type ) )
					{

						foreach( $obj->type as $type )
						{
							if( !empty( $type->name ) ) $types[] = $type->name;
						}
					}
					if( empty( $types ) ) $types[] = 'product';

					$output .= '<meta property="og:type" content="'.esc_attr( implode( ',', $types ) ).'" />';
					$output .= '<meta property="og:url" content="'.esc_url( get_permalink( $obj->ID ) ).'" />';

					print $output;
				}
            }
        }

        function _preview()
		{
			$user = wp_get_current_user();
			$allowed_roles = array('editor', 'administrator', 'author');

			if(array_intersect($allowed_roles, $user->roles ))
			{
				if(!empty($_REQUEST['sd-preview']))
				{
					// Sanitizing variable
					$preview = sanitize_text_field(stripcslashes($_REQUEST['sd-preview']));
					$preview = strip_tags($preview);

					// Remove every shortcode that is not in the music store list
					remove_all_shortcodes();

					add_shortcode('sell_downloads', array(&$this, 'load_store'));
                    add_shortcode('sell_downloads_product', array(&$this, 'load_store_product'));

					if(
						has_shortcode($preview, 'sell_downloads') ||
                        has_shortcode($preview, 'sell_downloads_product')
					)
					{
						print '<!DOCTYPE html>';
						$output = do_shortcode($preview);
						$output = preg_replace('/width:(\d+%);/', 'width:$1 !important;', $output);

						print '<style>body{width:640px;-ms-transform: scale(0.78);-moz-transform: scale(0.78);-o-transform: scale(0.78);-webkit-transform: scale(0.78);transform: scale(0.78);-ms-transform-origin: 0 0;-moz-transform-origin: 0 0;-o-transform-origin: 0 0;-webkit-transform-origin: 0 0;transform-origin: 0 0;}</style>';

						// Deregister all scripts and styles for loading only the plugin styles.
						global  $wp_styles, $wp_scripts;
						if(!empty($wp_scripts)) $wp_scripts->reset();
						$this->public_resources();
						if(!empty($wp_styles))  $wp_styles->do_items();
						if(!empty($wp_scripts)) $wp_scripts->do_items();

						print '<div class="sd-preview-container">'.$output.'</div>';

						print'<script type="text/javascript">jQuery(window).on("load", function(){ var frameEl = window.frameElement; if(frameEl) frameEl.height = jQuery(".sd-preview-container").outerHeight(true)*0.78+15; });</script><style>.sell-downloads-item {clear:none;} .sell-downloads-header span{clear:none;} .sell-downloads-ordering{float:right;} .sell-downloads-product .left-column{width:150px;clear:none;} .sell-downloads-product .right-column{float:left; padding-left:10px; width:-moz-calc(100% - 260px); width:-webkit-calc(100% - 260px); width:calc(100% - 260px);} .sell-downloads-product .product-cover, .sell-downloads-product .product-cover.single{width:150px; max-height:150px;} </style>';
						exit;
					}
				}
			}
		} // End _preview

        function add_display_post_states($post_states, $post)
		{
			if($post->post_name == 'sd-download-page')
				$post_states['sd-download-page'] = 	'Sell Downloads - '.__('Download Page', SD_TEXT_DOMAIN);

			return $post_states;
		} //  End add_display_post_states

        function _sd_create_pages( $slug, $title ){
			if( isset( $GLOBALS[SD_SESSION_NAME][ $slug ] ) ) return $GLOBALS[SD_SESSION_NAME][ $slug ];

            $page = get_page_by_path( $slug );
			if( is_null( $page ) ){
				if( is_admin() ){
					if( false != ($id = wp_insert_post(
								array(
									'comment_status' => 'closed',
									'post_name' => $slug,
									'post_title' => __( $title, SD_TEXT_DOMAIN ),
									'post_status' => 'publish',
									'post_type' => 'page',
									'post_content' => '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->'
								)
							)
						)
					){
						$GLOBALS[SD_SESSION_NAME][ $slug ] =  get_permalink($id);
					}
				}
			}else{
				if( is_admin() && $page->post_status != 'publish' )
				{
					$page->post_status = 'publish';
					wp_update_post( $page );
				}
				$GLOBALS[SD_SESSION_NAME][ $slug ] =  get_permalink($page->ID);
			}

            $GLOBALS[SD_SESSION_NAME][ $slug ] = esc_url(( isset( $GLOBALS[SD_SESSION_NAME][ $slug ] ) ) ? $GLOBALS[SD_SESSION_NAME][ $slug ] : SD_H_URL);
            return $GLOBALS[SD_SESSION_NAME][ $slug ];
        }

        function _sd_exclude_pages( $pages ){

            $exclude = array();
            $length = count( $pages );
            $new_pages = array();

            $p = get_page_by_path( 'sd-download-page' );
            if( !is_null( $p ) ) $exclude[] = $p->ID;

            foreach( $pages as $page ) {
				if ( !in_array( $page->ID, $exclude ) ) {
                    $new_pages[] = $page;
                }
            }

            return $new_pages;
        }

		function _sd_the_excerpt( $the_excerpt ){
			global $post;
			if(
				/* is_search() &&  */
				isset( $post) &&
				$post->post_type == 'sd_product'
			){
				$tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');
				$obj = new SDProduct( $post->ID );
				return $obj->display_content( 'multiple', $tpl, 'return');
			}

			return $the_excerpt;
		}


        function _sd_the_content( $the_content  ){
            global $post;

            $slug = $post->post_name;

            if( $slug == "sd-download-page" ){
				global $sd_errors;
				if(preg_match('/\{download\-links\-here\}/', $the_content, $matches))
					$the_content = str_replace($matches[0],sd_generate_downloads(),$the_content);
				else
					$the_content .= sd_generate_downloads();
				if( count( $sd_errors ) )
				{
					$the_content .= '<p>'.implode( '</p><p>', $sd_errors ).'</p>';
				}
            }
            return $the_content;
        }

		/**
		* Init SellDownloads when the WordPress is open for admin
		*
		* @access public
		* @return void
		*/
		function admin_init(){
			if(
				($sd_current_version = get_option('sell_downloads_version_number')) == false ||
				version_compare($sd_current_version, SellDownloads::$version, '<')
			)
			{
				update_option('sell_downloads_version_number', SellDownloads::$version);
				$this->_create_db_structure();
			}

			// Init the metaboxs for product
			add_meta_box('sd_product_metabox', __("Product's data", SD_TEXT_DOMAIN), array(&$this, 'metabox_form'), 'sd_product', 'normal', 'high');
            add_meta_box('sd_product_metabox_discount', __("Programming Discounts", SD_TEXT_DOMAIN), array(&$this, 'metabox_discount'), 'sd_product', 'normal', 'high');

			// Only accessible by website's administrators
			if(current_user_can('administrator'))
			{
				add_meta_box('sd_product_metabox_emulate_purchase', __("Manual Purchase", SD_TEXT_DOMAIN), array(&$this, 'metabox_manual_purchase'), 'sd_product', 'side', 'low');
			}

			// add_action('save_post', array(&$this, 'save_data'), 10, 3);

			if (current_user_can('delete_posts')) add_action('delete_post', array(&$this, 'delete_post'));

			// Load admin resources
			add_action('admin_enqueue_scripts', array(&$this, 'admin_resources'), 10);

			// Set a new media button for sell downloads insertion
			add_action('media_buttons', array(&$this, 'set_sell_downloads_button'), 100);

			$plugin = plugin_basename(__FILE__);
			add_filter('plugin_action_links_'.$plugin, array(&$this, 'customizationLink'));
            $this->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); // for download-page

			if( isset( $_REQUEST[ 'sd_action' ] ) && $_REQUEST[ 'sd_action' ] == 'paypal-data' ){
				if( isset( $_REQUEST[ 'data' ] ) && isset( $_REQUEST[ 'from' ] ) && isset( $_REQUEST[ 'to' ] ) ){
					global $wpdb;

					$where = $wpdb->prepare( 'DATEDIFF(date, "%s")>=0 AND DATEDIFF(date, "%s")<=0', sanitize_text_field($_REQUEST[ 'from' ]), sanitize_text_field($_REQUEST[ 'to' ]) );

					switch( $_REQUEST[ 'data' ] ){
						case 'residence_country':
							print sd_getFromPayPalData( array( 'residence_country' => 'residence_country'), 'COUNT(*) AS count', '', $where, array( 'residence_country' ), array( 'count' => 'DESC' ) );
						break;
						case 'mc_currency':
							print sd_getFromPayPalData( array( 'mc_currency' => 'mc_currency'), 'SUM(amount) AS sum', '', $where, array( 'mc_currency' ), array( 'sum' => 'DESC' ) );
						break;
						case 'product_name':
							$json =  sd_getFromPayPalData( array( 'mc_currency' => 'mc_currency'), 'SUM(amount) AS sum, post_title', $wpdb->posts.' AS posts', $where.' AND product_id = posts.ID', array( 'product_id', 'mc_currency' ) );
							$obj = json_decode( $json );
							foreach( $obj as $key => $value){
								$obj[ $key ]->post_title .= ' ['.$value->mc_currency.']';
							}
							print json_encode( $obj );
						break;
					}
				}
				exit;
			}

			// Init action
			do_action( 'selldownloads_admin_init' );
		} // End init

		function customizationLink($links){
			array_unshift(
				$links,
				'<a href="admin.php?page=sell-downloads-menu-settings">'.__('Settings').'</a>',
				'<a href="http://wordpress.dwbooster.com/contact-us" target="_blank">'.__('Request custom changes').'</a>',
				'<a href="https://wordpress.org/support/plugin/sell-downloads/#new-post" target="_blank">'.__('Help').'</a>'
			);
			return $links;
		} // End customizationLink

/** MANAGE DATABASES FOR ADITIONAL POST DATA **/

		/*
		*  Create database tables
		*
		*  @access public
		*  @return void
		*/
		function register($networkwide){
			global $wpdb;

			if (function_exists('is_multisite') && is_multisite()) {
				if ($networkwide) {
					$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						$this->_create_db_structure( true );
						update_option('sd_social_buttons', true);
					}
					switch_to_blog($old_blog);
					return;
				}
			}
			$this->_create_db_structure( true );
            update_option('sd_social_buttons', true);
		}  // End register

		/*
		* A new blog has been created in a multisite WordPress
		*/
		function install_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ){
			global $wpdb;
			if ( is_plugin_active_for_network() )
			{
				$current_blog = $wpdb->blogid;
				switch_to_blog( $blog_id );
				$this->_create_db_structure( true );
				update_option('sd_social_buttons', true);
				switch_to_blog( $current_blog );
			}
		}

		function redirect_to_settings($plugin, $network_activation)
		{
			if(
				empty( $_REQUEST['_ajax_nonce'] ) &&
				$plugin == plugin_basename( __FILE__ ) &&
				(!isset($_POST["action"]) || $_POST["action"] != 'activate-selected') &&
				(!isset($_POST["action2"]) || $_POST["action2"] != 'activate-selected')
			)
			{
				exit( wp_redirect( admin_url( 'admin.php?page=sell-downloads-menu-settings' ) ) );
			}
		}

		/*
		* Create the Sell Downloads tables
		*
		* @access private
		* @return void
		*/
		private function _create_db_structure(  $installing = false  ){
			global $wpdb;
            try{
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

				$charset_collate = $wpdb->get_charset_collate();

				$db_queries = array();
                /*
                    The name of columns are treated as below to make table of Sell Downloads compatible with the tables of Sell Downloads and Sell Videos
                    - id is the primary key, and the same value as the ID column of wp_posts table
                    - time, may be used in video and audio files
                    - plays, number of times the file has been visited
                    - purchases, number of times the file has been purchase
                    - file, location of file to purchase.
                    - demo, location of demo file to downloaded for free
                    - protect, (not used)
                    - info, the URL of webpage with additional information of file
                    - cover, location of image that represent the file
                    - price, price of file
                    - year, may be used for books, audio files, videos, etc.
                    - as single, (not used)
                */
                $db_queries[] = "CREATE TABLE ".$wpdb->prefix.SDDB_POST_DATA." (
                    id mediumint(9) NOT NULL,
                    time VARCHAR(25) NULL,
                    popularity TINYINT NOT NULL DEFAULT 0,
                    individually TINYINT NOT NULL DEFAULT 1,
                    plays mediumint(9) NOT NULL DEFAULT 0,
                    purchases mediumint(9) NOT NULL DEFAULT 0,
                    file VARCHAR(255) NULL,
                    demo VARCHAR(255) NULL,
                    protect TINYINT(1) NOT NULL DEFAULT 0,
                    info VARCHAR(255) NULL,
                    cover TEXT,
                    price FLOAT NULL,
                    year VARCHAR(25),
                    as_single TINYINT(1) NOT NULL DEFAULT 0,
                    UNIQUE KEY id (id)
                 ) $charset_collate;";

                $db_queries[] = "CREATE TABLE ".$wpdb->prefix.SDDB_PURCHASE." (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    product_id mediumint(9) NOT NULL,
                    quantity mediumint(9) NOT NULL DEFAULT 1,
                    purchase_id varchar(50) NOT NULL,
                    date DATETIME NOT NULL,
                    checking_date DATETIME,
                    email VARCHAR(255) NOT NULL,
                    amount FLOAT NOT NULL DEFAULT 0,
                    downloads INT NOT NULL DEFAULT 0,
                    paypal_data TEXT,
					note TEXT,
                    UNIQUE KEY id (id)
                 ) $charset_collate;";

				$db_queries[] = "CREATE TABLE ".$wpdb->prefix.SDDB_SHOPPING_CART." (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    product_id mediumint(9) NOT NULL,
                    purchase_id varchar(50) NOT NULL,
                    date DATETIME NOT NULL,
                    PRIMARY KEY id (id),
                    UNIQUE KEY purchase_product (purchase_id, product_id)
                 ) $charset_collate;";

				$db_queries[] = SD_REVIEW::db_structure();

				dbDelta($db_queries); // Running the queries
				$index = $wpdb->get_var("SHOW INDEX FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE key_name = 'product_purchase'");
				if(!empty($index)) $wpdb->query("ALTER TABLE ".$wpdb->prefix.SDDB_PURCHASE." DROP INDEX product_purchase");
			}
            catch( Exception $err )
            {
            }

			// Add new columns
			$this->_add_column($wpdb->prefix.SDDB_POST_DATA, 'individually', 'TINYINT NOT NULL DEFAULT 1');
			$this->_add_column($wpdb->prefix.SDDB_POST_DATA, 'as_single', 'TINYINT(1) NOT NULL DEFAULT 0');
		} // End _create_db_structure

		private function _add_column($table_name, $column_name, $column_structure)
		{
			global $wpdb;

            $results = $wpdb->get_results("SHOW columns FROM `".$table_name."` where field='".$column_name."'");
            if (!count($results))
            {
                $sql = "ALTER TABLE  `".$table_name."` ADD `".$column_name."` ".$column_structure;
                $wpdb->query($sql);
            }
		} // End _add_column

/** REGISTER POST TYPES AND TAXONOMIES **/

		/**
		* Init SellDownloads post types
		*
		* @access public
		* @return void
		*/
		function init_post_types(){
            global $wpdb;
            if(post_type_exists('sd_product')) return;

			// Post Types
			// Create song post type
			register_post_type( 'sd_product',
				array(
					/* 'description'		   => __('This is where you can add products to your store.', SD_TEXT_DOMAIN), */
					'capability_type'      => 'page',
                    'taxonomies'      	   => array( 'sd_category' ),
					'supports'             => array( 'title', 'editor', 'thumbnail', 'comments', 'custom-fields' ),
					'exclude_from_search'  => false,
					'public'               => true,
					'show_ui'              => true,
					'show_in_nav_menus'    => true,
					'show_in_menu'    	   => $this->sell_downloads_slug,
					'labels'               => array(
						'name'               => __( 'Products', SD_TEXT_DOMAIN),
						'singular_name'      => __( 'Product', SD_TEXT_DOMAIN),
						'add_new'            => __( 'Add New', SD_TEXT_DOMAIN),
						'add_new_item'       => __( 'Add New Product', SD_TEXT_DOMAIN),
						'edit_item'          => __( 'Edit Product', SD_TEXT_DOMAIN),
						'new_item'           => __( 'New Product', SD_TEXT_DOMAIN),
						'view_item'          => __( 'View Product', SD_TEXT_DOMAIN),
						'search_items'       => __( 'Search Products', SD_TEXT_DOMAIN),
						'not_found'          => __( 'No products found', SD_TEXT_DOMAIN),
						'not_found_in_trash' => __( 'No products found in Trash', SD_TEXT_DOMAIN),
						'menu_name'          => __( 'Products for Sale', SD_TEXT_DOMAIN),
						'parent_item_colon'  => '',
					),
					'query_var'            => true,
					'has_archive'		   => true,
					//'register_meta_box_cb' => 'wpsc_meta_boxes',
                    'rewrite'              => ( ( get_option( 'sd_friendly_url', false )*1 ) ? ['slug' => self::get_permalink('sd_product')] : false )
				)
			);

            register_post_type( 'sd_download',
				array(
					'capability_type'      => 'page',
					'exclude_from_search'  => true,
					'public'               => true,
					'show_ui'              => false,
					'show_in_nav_menus'    => false,
					'show_in_menu'         => false,
					'query_var'            => true,
					'has_archive'		   => false,
					'rewrite'              => false,
                    'supports'             => array( 'title', 'editor' )
				)
			);

            if(!$wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts as posts WHERE post_type="sd_download"')){
                $my_post = array(
                  'post_title'    => 'download',
                  'post_type'     => 'sd_download',
                  'post_content'  => 'download',
                  'post_status'   => 'publish',
                  'post_author'   => 1
                );

                wp_insert_post($my_post);
            }

			add_filter('manage_sd_product_posts_columns' , 'SDProduct::columns');
			add_action('manage_sd_product_posts_custom_column', 'SDProduct::columns_data', 2 );
			if( get_option( 'sd_friendly_url', false )*1 && empty( $GLOBALS[SD_SESSION_NAME][ 'sd_flush_rewrite_rules' ] ) )
			{
				flush_rewrite_rules();
				$GLOBALS[SD_SESSION_NAME][ 'sd_flush_rewrite_rules' ] = 1;
			}
		}// End init_post_types

		/**
		* Init SellDownloads taxonomies
		*
		* @access public
		* @return void
		*/
		function init_taxonomies(){

			if ( !taxonomy_exists('sd_type') ) {
				// Register sd_type taxonomy
				register_taxonomy(
					'sd_type',
					array(
						'sd_product'
					),
					array(
						'hierarchical'	=> false,
						'label' 	   	=> __('File Type', SD_TEXT_DOMAIN),
						'labels' 		=> array(
							'name' 				=> __( 'File Types', SD_TEXT_DOMAIN),
							'singular_name' 	=> __( 'File Type', SD_TEXT_DOMAIN),
							'search_items' 		=> __( 'Search File Types', SD_TEXT_DOMAIN),
							'all_items' 		=> __( 'All File Types', SD_TEXT_DOMAIN),
							'edit_item' 		=> __( 'Edit File Type', SD_TEXT_DOMAIN),
							'update_item' 		=> __( 'Update File Type', SD_TEXT_DOMAIN),
							'add_new_item' 		=> __( 'Add New File Type', SD_TEXT_DOMAIN),
							'new_item_name' 	=> __( 'New File Type', SD_TEXT_DOMAIN),
							'menu_name'			=> __( 'File Types', SD_TEXT_DOMAIN)
						),
						'public' => true,
						'show_ui' => true,
						'show_admin_column' => true,
						'query_var' => true,
                        'rewrite'              => ( ( get_option( 'sd_friendly_url', false )*1 ) ? ['slug' => self::get_permalink('sd_type')] : false )
					)
				);
			}

			if ( !taxonomy_exists('sd_category') ) {
                // Register sd_category taxonomy
                register_taxonomy(
                    'sd_category',
                    array(
                        'sd_product'
                    ),
                    array(
                        'hierarchical'	=> false,
                        'label' 	   	=> __('Category', SD_TEXT_DOMAIN),
                        'labels' 		=> array(
                            'name' 				=> __( 'Categories', SD_TEXT_DOMAIN),
                            'singular_name' 	=> __( 'Category', SD_TEXT_DOMAIN),
                            'search_items' 		=> __( 'Search Categories', SD_TEXT_DOMAIN),
                            'all_items' 		=> __( 'All Categories', SD_TEXT_DOMAIN),
                            'edit_item' 		=> __( 'Edit Category', SD_TEXT_DOMAIN),
                            'update_item' 		=> __( 'Update Category', SD_TEXT_DOMAIN),
                            'add_new_item' 		=> __( 'Add New Category', SD_TEXT_DOMAIN),
                            'new_item_name' 	=> __( 'New Category', SD_TEXT_DOMAIN),
                            'menu_name'			=> __( 'Categories', SD_TEXT_DOMAIN)
                        ),
                        'public' => true,
                        'show_ui' => true,
                        'show_admin_column' => true,
                        'query_var' => true,
                        'rewrite'              => ( ( get_option( 'sd_friendly_url', false )*1 ) ? ['slug' => self::get_permalink('sd_category')] : false )
                    )
                );
			}

			add_action( 'admin_menu' , array(&$this, 'remove_meta_box') );
			do_action( 'sell_downloads_register_taxonomy' );
		} // End init_taxonomies

		/**
		*	Remove the taxonomies metabox
		*
		* @access public
		* @return void
		*/
		function remove_meta_box(){
			remove_meta_box( 'tagsdiv-sd_type', 'sd_product', 'side' );
		} // End remove_meta_box

/** METABOXS FOR ENTERING POST_TYPE ADDITIONAL DATA **/

		/**
		* Save data of store products
		*
		* @access public
		* @return void
		*/
		function save_data($post_id, $post, $update){
            if ( isset( $post ) ) {
				if ( $post->post_type == 'sd_product' ) {
					SDProduct::save_data($post);
				} elseif ( preg_match( '/\[\s*sell_downloads\s*/i', $post->post_content ) ) {
					if (
						defined( 'SD_SESSION_NAME' ) &&
						! empty( $GLOBALS[SD_SESSION_NAME] )
					) {
						unset( $GLOBALS[SD_SESSION_NAME][ 'sd_page_' . $post_id ] );
					}
				}
			}
		} // End save_data

		/**
		* Print metabox for products
		*
		* @access public
		* @return void
		*/
		function metabox_form($obj){
			global $post;
            SDProduct::print_metabox();
		} // End metabox_form

		function metabox_discount($obj){
            SDProduct::print_discount_metabox();
		} // End metabox_form

		function metabox_manual_purchase($obj){
			print '<p>'.__('To emulate a purchase it is possible to include a manual entry in the sales reports. Configure the product, and press the manual purchase button', SD_TEXT_DOMAIN).'</p><div style="text-align:right;"><a href="'.get_admin_url(null, 'admin.php?page=sell-downloads-menu-reports&sd-product-id='.$obj->ID).'" class="button-primary">'.__('Manual Purchase', SD_TEXT_DOMAIN).'</a></div>';
		} // End metabox_manual_purchase

/** SETTINGS PAGE FOR SELL DOWNLOADS CONFIGURATION AND SUBMENUS**/

		// highlight the proper top level menu for taxonomies submenus
		function tax_menu_correction($parent_file) {
			global $current_screen;
			$taxonomy = $current_screen->taxonomy;
			if ($taxonomy == 'sd_type')
				$parent_file = $this->sell_downloads_slug;
			return $parent_file;
		} // End tax_menu_correction

		/*
		* Create the link for sell downloads menu, submenus and settings page
		*
		*/
		function menu_links(){
			if(is_admin()){
				add_options_page('Sell Downloads', 'Sell Downloads', 'manage_options', $this->sell_downloads_slug.'-settings1', array(&$this, 'settings_page'));

				add_menu_page('Sell Downloads', 'Sell Downloads', 'edit_pages', $this->sell_downloads_slug, null, SD_CORE_IMAGES_URL."/sell-downloads-menu-icon.png", 3.5);

				//Submenu for taxonomies
				add_submenu_page($this->sell_downloads_slug, __( 'File Types', SD_TEXT_DOMAIN), __( 'Set File Types', SD_TEXT_DOMAIN), 'edit_pages', 'edit-tags.php?taxonomy=sd_type');

				add_action('parent_file', array(&$this, 'tax_menu_correction'));

				// Settings Submenu
				add_submenu_page($this->sell_downloads_slug, 'Sell Downloads Settings', 'Sell Downloads Settings', 'manage_options', $this->sell_downloads_slug.'-settings', array(&$this, 'settings_page'));

				// Templates Submenu
				add_submenu_page($this->sell_downloads_slug, __( 'Sell Downloads Templates', SD_TEXT_DOMAIN ), __( 'Products Templates', SD_TEXT_DOMAIN ), 'manage_options', $this->sell_downloads_slug.'-templates', array(&$this, 'templates_page'));

				// Sales report submenu
				add_submenu_page($this->sell_downloads_slug, 'Sell Downloads Sales Report', 'Sales Report', 'manage_options', $this->sell_downloads_slug.'-reports', array(&$this, 'settings_page'));

			}
		} // End menu_links

		/*
		*	Create tabs for setting page and payment stats
		*/
		function settings_tabs($current = 'reports'){
			$tabs = array( 'settings' => 'Sell Downloads Settings', 'product' => 'Sell Downloads Products','reports' => 'Sales Report');
			echo '<h2 class="nav-tab-wrapper">';
			foreach( $tabs as $tab => $name ){
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				if($tab == 'product')
					echo "<a class='nav-tab$class' href='edit.php?post_type=sd_$tab'>$name</a>";
				else
					echo "<a class='nav-tab$class' href='admin.php?page={$this->sell_downloads_slug}-$tab&tab=$tab'>$name</a>";

			}
			echo '</h2>';
		} // End settings_tabs

       /**
		* Get the list of available layouts
		*/
		function _layouts(){
			$tpls_dir = dir( SD_FILE_PATH.'/sd-layouts' );
			while( false !== ( $entry = $tpls_dir->read() ) )
			{
				if ( $entry != '.' && $entry != '..' && is_dir( $tpls_dir->path.'/'.$entry ) && file_exists( $tpls_dir->path.'/'.$entry.'/config.ini' ) )
				{
					if( ( $ini_array = parse_ini_file( $tpls_dir->path.'/'.$entry.'/config.ini' ) ) !== false )
					{
						if( !empty( $ini_array[ 'style_file' ] ) ) $ini_array[ 'style_file' ] = 'sd-layouts/'.$entry.'/'.$ini_array[ 'style_file' ];
						if( !empty( $ini_array[ 'script_file' ] ) ) $ini_array[ 'script_file' ] = 'sd-layouts/'.$entry.'/'.$ini_array[ 'script_file' ];
						if( !empty( $ini_array[ 'thumbnail' ] ) ) $ini_array[ 'thumbnail' ] = SD_URL.'/sd-layouts/'.$entry.'/'.$ini_array[ 'thumbnail' ];
						$this->layouts[ $ini_array[ 'id' ] ] = $ini_array;
					}
				}
			}
		} // End _layouts

		/**
		* Get the list of possible paypal butt
		*/
		function get_paypal_button($args = array())
		{
			$attrs = isset($args['attrs']) ? $args['attrs'] : '';
			$class = isset($args['class']) ? $args['class'] : '';

			$buttons = array(
				'button_a.gif' => '<div class="sd-payment-button-container"><input type="submit" '.$attrs.' class="sd-payment-button '.$class.'" value="'.__('Buy Now', SD_TEXT_DOMAIN).'" /><div class="sd-payment-button-cards-container"><span class="sd-payment-button-card sd-payment-button-visa"></span><span class="sd-payment-button-card sd-payment-button-mastercard"></span><span class="sd-payment-button-card sd-payment-buttonmex"></span><span class="sd-payment-button-card sd-payment-button-discover"></span></div></div>',

				'button_b.gif' => '<div class="sd-payment-button-container"><input type="submit" '.$attrs.' class="sd-payment-button '.$class.'" value="'.__('Pay Now', SD_TEXT_DOMAIN).'" /><div class="sd-payment-button-cards-container"><span class="sd-payment-button-card sd-payment-button-visa"></span><span class="sd-payment-button-card sd-payment-button-mastercard"></span><span class="sd-payment-button-card sd-payment-buttonmex"></span><span class="sd-payment-button-card sd-payment-button-discover"></span></div></div>',

				'button_c.gif' => '<div class="sd-payment-button-container"><input type="submit" '.$attrs.' class="sd-payment-button '.$class.'" value="'.__('Pay Now', SD_TEXT_DOMAIN).'" /></div>',

				'button_d.gif' => '<div class="sd-payment-button-container"><input type="submit" '.$attrs.' class="sd-payment-button '.$class.'" value="'.__('Buy Now', SD_TEXT_DOMAIN).'" /></div>',

				'shopping_cart/button_e.gif' => '<div class="sd-payment-button-container"><input type="submit" '.$attrs.' class="sd-payment-button shopping-cart-btn '.$class.'" value="'.__('Add to Cart', SD_TEXT_DOMAIN).'" /></div>',

				'shopping_cart/button_f.gif' => '<div class="sd-payment-button-container "><input type="submit" '.$attrs.' class="sd-payment-button view-cart-btn '.$class.'" value="'.__('View Cart', SD_TEXT_DOMAIN).'" /></div>',
			);
			if(isset($args['button']) && !empty($buttons[$args['button']])) return $buttons[$args['button']];
			return '';
		}

		function _paypal_buttons(){
			$b = get_option('sd_paypal_button', SD_PAYPAL_BUTTON);
			$str = "";
			$buttons_names = array('button_a.gif','button_b.gif','button_c.gif','button_d.gif');
			foreach($buttons_names as $button){
				$str .= "<input type='radio' name='sd_paypal_button' value='".esc_attr($button)."' ".(($b == $button) ? "checked" : "")." />&nbsp;".$this->get_paypal_button(array('button' => $button, 'attrs' => 'DISABLED'))."&nbsp;&nbsp;";
			}

			return $str;
		} // End _paypal_buttons

		function templates_page()
		{
			include_once dirname(__FILE__).'/sd-core/sd-templates.php';
		} // End templates_page

		/*
		* Set the sell downloads settings
		*/
		function settings_page(){
			global $wpdb;
            $this->_layouts(); // Load the available layouts

			$sd_video_style = 'style="display:none;"';
			$sd_first_time_mssg = '';

			if ( isset($_POST['sd_settings']) && wp_verify_nonce( $_POST['sd_settings'], plugin_basename( __FILE__ ) ) ){
				update_option('sd_main_page', esc_url_raw(sanitize_text_field(stripcslashes($_POST['sd_main_page']))));
				update_option('sd_online_demo', ((isset($_POST['sd_online_demo'])) ? 1 : 0));
				update_option('sd_prevent_cache', (isset($_POST['sd_prevent_cache']) ? 1 : 0));
				update_option('sd_filter_by_type', ((isset($_POST['sd_filter_by_type'])) ? 1 : 0));
				update_option('sd_filter_by_category', ((isset($_POST['sd_filter_by_category'])) ? 1 : 0));
				update_option('sd_search_taxonomy', ((isset($_POST['sd_search_taxonomy'])) ? true : false));
				update_option('sd_items_page_selector', ((isset($_POST['sd_items_page_selector'])) ? 1 : 0));
				update_option('sd_items_page', @intval(stripcslashes($_POST['sd_items_page'])));
				update_option('sd_friendly_url', ((isset($_POST['sd_friendly_url'])) ? 1 : 0));
				update_option('sd_pp_accept_zip', ((isset($_POST['sd_pp_accept_zip'])) ? 1 : 0));

                $cover = '';
                if(isset($_POST['sd_pp_default_cover']))
                {
                    $cover = esc_url_raw(trim(stripcslashes($_POST['sd_pp_default_cover'])));
                }
				update_option('sd_pp_default_cover', $cover);

				if(
					isset($_POST['sd_pp_cover_size']) &&
					(
						$_POST['sd_pp_cover_size'] == 'full' ||
						$_POST['sd_pp_cover_size'] == 'medium'
					)
				) update_option('sd_pp_cover_size', $_POST['sd_pp_cover_size']);

                if( !empty( $_POST[ 'sd_layout' ] ) && isset( $this->layouts[ $_POST[ 'sd_layout' ] ] ) )
				{
					$this->layout = $this->layouts[ $_POST[ 'sd_layout' ] ];
					update_option( 'sd_layout', $this->layout );
				}
				else
				{
					delete_option( 'sd_layout' );
					$this->layout = array();
				}
				update_option('sd_paypal_email', sanitize_email(stripcslashes($_POST['sd_paypal_email'])));
				update_option('sd_paypal_button', ( !empty( $_POST['sd_paypal_button'] ) && in_array( $_POST['sd_paypal_button'], array( 'button_a.gif', 'button_b.gif', 'button_c.gif', 'button_d.gif' ) )) ? $_POST['sd_paypal_button'] : 'button_d.gif' );
				update_option('sd_paypal_enabled', ((isset($_POST['sd_paypal_enabled'])) ? 1 : 0));
				update_option('sd_paypal_sandbox', ((isset($_POST['sd_paypal_sandbox'])) ? true : false));
				update_option('sd_notification_from_email', sanitize_email(stripcslashes($_POST['sd_notification_from_email'])));
				update_option('sd_notification_to_email', sanitize_email(stripcslashes($_POST['sd_notification_to_email'])));
				update_option('sd_notification_to_payer_subject', wp_kses_data(stripcslashes($_POST['sd_notification_to_payer_subject'])));
				update_option('sd_notification_to_payer_message', wp_kses_data(stripcslashes($_POST['sd_notification_to_payer_message'])));
				update_option('sd_notification_to_seller_subject', wp_kses_data(stripcslashes($_POST['sd_notification_to_seller_subject'])));
				update_option('sd_notification_to_seller_message', wp_kses_data(stripcslashes($_POST['sd_notification_to_seller_message'])));
				update_option('sd_tax', (!empty($_POST['sd_tax']) && ($sd_tax = trim($_POST['sd_tax'])) !== '') ? @floatval($sd_tax) : '');
				update_option('sd_old_download_link', @floatval(stripcslashes($_POST['sd_old_download_link'])));
				update_option('sd_downloads_number', @intval(stripcslashes($_POST['sd_downloads_number'])));
                update_option('sd_social_buttons', ((isset($_POST['sd_social_buttons'])) ? true : false));
                update_option('sd_popularity', ((isset($_POST['sd_popularity'])) ? 1 : 0));
				update_option('sd_facebook_app_id', ((!empty($_POST['sd_facebook_app_id'])) ? sanitize_key(stripcslashes($_POST['sd_facebook_app_id'])) : ''));
				update_option('sd_paypal_currency', strip_tags(html_entity_decode(stripcslashes($_POST['sd_paypal_currency']))));
				update_option('sd_paypal_currency_symbol', strip_tags(html_entity_decode(stripcslashes($_POST['sd_paypal_currency_symbol']))));
				update_option('sd_paypal_language', strip_tags(html_entity_decode(stripcslashes($_POST['sd_paypal_language']))));
				update_option('sd_safe_download', ((isset($_POST['sd_safe_download'])) ? true : false));
				update_option('sd_debug_payment', (isset($_POST['sd_debug_payment'])) ? 1 : 0 );
				update_option('sd_download_link_for_registered_only', (isset($_POST['sd_download_link_for_registered_only'])) ? 1 : 0 );

				update_option('sd_troubleshoot_no_ob', (isset($_POST['sd_troubleshoot_no_ob'])) ? 1 : 0 );

				do_action( 'sd_save_settings' );
				if(
					get_option('sd_paypal_enabled', SD_PAYPAL_ENABLED) &&
					get_option('sd_paypal_first_time_enable', false) == false
				)
				{
					$sd_first_time_mssg = '<span id="sd_first_time_mssg">'.__('Settings Updated', SD_TEXT_DOMAIN).' - </span>';
					update_option('sd_paypal_first_time_enable', true);
					$sd_video_style = 'style="display:block;"';
				}
?>
				<div class="updated" style="margin:5px 0;"><strong><?php _e("Settings Updated", SD_TEXT_DOMAIN); ?></strong></div>
<?php
				unset( $GLOBALS[SD_SESSION_NAME][ 'sd_flush_rewrite_rules' ] );
			}

			// Checks if it is the first time and display the wizard
			include_once dirname(__FILE__).'/sd-core/sd-wizard.php';
			if(!empty($wizard_active)) return;

			$current_tab = (isset($_REQUEST['tab'])) ? sanitize_text_field($_REQUEST['tab']) : (($_REQUEST['page'] == 'sell-downloads-menu-reports') ? 'reports' : 'settings');

			$this->settings_tabs(
				$current_tab
			);
?>
			<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
				For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a><br />
				If you want test the premium version of Sell Downloads go to the following links:<br/> <a href="https://demos.dwbooster.com/sell-downloads/wp-login.php" target="_blank">Administration area: Click to access the administration area demo</a><br/>
				<a href="https://demos.dwbooster.com/sell-downloads/" target="_blank">Public page: Click to access the Store Page</a>
			</p>
<?php
			switch($current_tab){
				case 'settings':
?>
					<form method="post" action="<?php echo admin_url('admin.php?page=sell-downloads-menu-settings&tab=settings'); ?>">
					<input type="hidden" name="tab" value="settings" />
					<!-- STORE CONFIG -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Sell Downloads page config', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<table class="form-table">
								<tr valign="top">
									<th><?php _e('URL of store page', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="sd_main_page" size="40" value="<?php echo esc_attr(get_option('sd_main_page', SD_MAIN_PAGE)); ?>" />
										<br />
										<em><?php _e('Set the URL of page where the Sell Downloads was inserted', SD_TEXT_DOMAIN); ?></em>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Prevent the products pages be cached', SD_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="sd_prevent_cache" <?php if( get_option('sd_prevent_cache', false) ) echo 'checked'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow searching by taxonomies', SD_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="sd_search_taxonomy" value="1" <?php if( get_option( 'sd_search_taxonomy', false ) ) echo 'checked'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow display online demos', SD_TEXT_DOMAIN); ?></th>

									<td><input type="checkbox" name="sd_online_demo" value="1" <?php if (get_option('sd_online_demo', SD_ONLINE_DEMO)) echo 'checked'; ?> /><br />
									<?php _e( 'The demo files will be displayed with plugins on browser, if they are enabled', SD_TEXT_DOMAIN); ?>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow filtering by type', SD_TEXT_DOMAIN); ?></th>

									<td><input type="checkbox" name="sd_filter_by_type" value="1" <?php if (get_option('sd_filter_by_type', SD_FILTER_BY_TYPE)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow filtering by category', SD_TEXT_DOMAIN); ?></th>

									<td><input type="checkbox" name="sd_filter_by_category" value="1" <?php if (get_option('sd_filter_by_category', SD_FILTER_BY_CATEGORY)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow multiple pages', SD_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="sd_items_page_selector" size="40" value="1" <?php if (get_option('sd_items_page_selector', SD_ITEMS_PAGE_SELECTOR)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Uses friendly URLs on products', SD_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="sd_friendly_url" value="1" <?php if (get_option('sd_friendly_url', false)) echo 'checked'; ?> /></td>
								</tr>
                                <tr valign="top">
									<th><?php _e('Store layout', SD_TEXT_DOMAIN); ?></th>
									<td>
										<select name="sd_layout" id="sd_layout">
											<option value=""><?php _e( 'Default layout', SD_TEXT_DOMAIN ); ?></option>
										<?php
											foreach( $this->layouts as $id => $layout )
											{
												print '<option value="'.esc_attr($id).'" '.( ( !empty( $this->layout ) && $id == $this->layout[ 'id' ] ) ? 'SELECTED' : '' ).' thumbnail="'.$layout[ 'thumbnail' ].'">'.$layout[ 'title' ].'</option>';
											}
										?>
										</select>
										<div id="sd_layout_thumbnail">
										<?php
											if( !empty( $this->layout ) )
											{
												print '<img src="'.esc_url($this->layout[ 'thumbnail' ]).'" title="'.esc_attr($this->layout[ 'title' ]).'" />';
											}
										?>
										</div>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Items per page', SD_TEXT_DOMAIN); ?></th>
									<td><input type="text" name="sd_items_page" value="<?php echo esc_attr(get_option('sd_items_page', SD_ITEMS_PAGE)); ?>" /></td>
								</tr>
                                <tr valign="top">
									<th><?php _e('Show the products popularity', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="checkbox" name="sd_popularity" id="sd_popularity" <?php if(get_option('sd_popularity',1)) print 'CHECKED'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Share in social networks', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="checkbox" name="sd_social_buttons" <?php echo ((get_option('sd_social_buttons')) ? 'CHECKED' : ''); ?> /><br />
										<em><?php _e('The option enables the buttons for share the products in social networks', SD_TEXT_DOMAIN); ?></em>

									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Facebook app id for sharing in Facebook', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="sd_facebook_app_id" value="<?php echo esc_attr(get_option( 'sd_facebook_app_id', '' )); ?>" size="40" /><br />
										<em><?php _e('Click the link to generate the Facebook App and get its ID: <a target="_blank" href="https://developers.facebook.com/apps">https://developers.facebook.com/apps</a>', SD_TEXT_DOMAIN); ?></em>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<div class="postbox product-data">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Products Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<table class="form-table">
								<?php
								if(!defined('SD_INCLUDE_ZIP_ATTRIBUTE') || SD_INCLUDE_ZIP_ATTRIBUTE == true)
								{
								?>
								<tr valign="top">
									<th><?php _e('Allows to associate zip files to the products, as the files for selling', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="checkbox" name="sd_pp_accept_zip" <?php if(get_option('sd_pp_accept_zip',0)) echo 'checked'; ?> />
									</td>
								</tr>
								<?php
								}
								?>
								<tr valign="top">
									<th><?php _e('Default cover image', SD_TEXT_DOMAIN); ?></th>
									<td>
                                        <?php
										$sd_pp_default_cover = get_option( 'sd_pp_default_cover', '' );
										?>
										<input type="text" name="sd_pp_default_cover" value="<?php echo esc_attr($sd_pp_default_cover); ?>" class="file_path" placeholder="<?php print esc_attr(__('File URL', SD_TEXT_DOMAIN)); ?>" size="40" />
                                        <input type="button" class="button_for_upload_sd button" value="<?php print esc_attr(__('Upload a file', SD_TEXT_DOMAIN)); ?>" />
										<br />
										<i><?php _e('Cover to display when products do not have an associated image', SD_TEXT_DOMAIN); ?></i>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Size of cover image', SD_TEXT_DOMAIN); ?></th>
									<td>
										<?php
										$sd_pp_cover_size = get_option( 'sd_pp_cover_size', 'medium' );
										?>
										<input type="radio" name="sd_pp_cover_size" <?php if( $sd_pp_cover_size == 'medium') echo 'checked'; ?> value="medium" /> <?php _e('Medium size', SD_TEXT_DOMAIN); ?><br>
										<input type="radio" name="sd_pp_cover_size" <?php if( $sd_pp_cover_size == 'full') echo 'checked'; ?> value="full" /> <?php _e('Full size', SD_TEXT_DOMAIN); ?><br />
										<i><?php _e( 'The size of cover image selected only affects to the images associated to the products from now, the images selected previously won\'t be modified' , SD_TEXT_DOMAIN); ?></i>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<!-- PAYPAL BOX -->
                    <div id="sd_ipn_video_tutorial" <?php print $sd_video_style; ?>>
						<div style="padding:0 10px 10px 0;"><span><?php print $sd_first_time_mssg; _e('How configure your PayPal account', SD_TEXT_DOMAIN); ?></span><span style="float:right;"><a href="javascript:void(0);" onclick="jQuery('#sd_ipn_video_tutorial').hide();"><?php _e('close [x]', SD_TEXT_DOMAIN);?></a></span></div>
						<video controls preload="none" width="100%">
							<source src="https://wordpress.dwbooster.com/videos/sell-downlodas/ipn.mp4" type="video/mp4">
						</video>
					</div>
					<p class="sd_more_info" style="display:block;">The Sell Downloads uses PayPal only as payment gateway, but depending of your PayPal account, it is possible to charge the purchase directly from the Credit Cards of customers.</p>
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Paypal Payment Configuration', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
						<?php
							do_action( 'selldownloads_before_payment_gateway_settings' );
							if(
								substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.'
								|| $_SERVER['REMOTE_ADDR'] == '::1'
							)
							{
								print '<div style="border: 1px solid #FF0000; background-color: rgba(255,0,0,0.1); padding: 10px; margin: 10px 0;font-weight:bold;">'.__('Your website is hosted locally, so, the PayPal IPN cannot reach your website. For testing the purchases the website should be hosted publicly.', SD_TEXT_DOMAIN).'</div>';
							}
						?>
						<table class="form-table">
							<tr valign="top">
							<th scope="row" style="border-top:2px solid purple;border-left:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><?php _e('Enable Paypal Payments?', SD_TEXT_DOMAIN); ?></th>
							<td style="border-top:2px solid purple;border-right:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><input type="checkbox" name="sd_paypal_enabled" size="40" value="1" <?php if (get_option('sd_paypal_enabled', SD_PAYPAL_ENABLED)) echo 'checked'; ?> /><br><i>Remember to enable the IPN (Instant Payments Notification) in the PayPal account (use the URL to your home page in the process): <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a></i></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Use Paypal Sandbox', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="sd_paypal_sandbox" value="1" <?php if (get_option('sd_paypal_sandbox', false)) echo 'checked'; ?> /><br><i>The PayPal Sandbox account and the PayPal account are independent, remember to enable the IPN in the PayPal Sandbox account too: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a></i></td>
							</tr>

							<tr valign="top">
							<th scope="row" style="border-top:2px solid purple;border-left:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><?php _e('Paypal email', SD_TEXT_DOMAIN); ?></th>
							<td style="border-top:2px solid purple;border-right:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><input type="text" name="sd_paypal_email" size="40" value="<?php echo esc_attr(get_option('sd_paypal_email', SD_PAYPAL_EMAIL)); ?>" />
                            <span class="sd_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="sd_display_more_info( this );">[ + more information]</a></span><span style="margin-left: 10px;"><a href="javascript:void(0);" onclick="jQuery('#sd_first_time_mssg').hide();jQuery('#sd_ipn_video_tutorial').show();">[ + <?php _e('enabling the IPN in PayPal tutorial', SD_TEXT_DOMAIN); ?>]</a></span>
                            <div class="sd_more_info">
                                <p>If let empty the email associated to PayPal, the Sell Downloads assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
                                <a href="javascript:void(0)" onclick="sd_hide_more_info( this );">[ + less information]</a>
                            </div>
                            </td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Currency', SD_TEXT_DOMAIN); ?></th>
							<td>
                            <select name="sd_paypal_currency">
                            <?php
                                $currency_list = array( "USD","EUR","GBP","CAD","AUD","RUB","BRL","CZK","DKK","HKD","HUF","ILS","JPY","MYR","MXN","NOK","NZD","PHP","PLN","SGD","SEK","CHF","TWD","THB","TRY" );
                                $currency_selected = get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY);
                                foreach($currency_list as $currency_item)
                                    echo '<option value="'.esc_attr($currency_item).'" '.(($currency_item == $currency_selected) ? 'SELECTED' : '').'>'.sd_strip_tags($currency_item,true).'</option>';
                            ?>
                            </select>
                            </td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Currency Symbol', SD_TEXT_DOMAIN); ?></th>
							<td>
                                <input type="text" name="sd_paypal_currency_symbol" value="<?php echo esc_attr(get_option('sd_paypal_currency_symbol', SD_PAYPAL_CURRENCY_SYMBOL)); ?>" />
                            </td>
							</tr>

							<tr valign="top">
                            <th scope="row"><?php _e('Paypal language', SD_TEXT_DOMAIN); ?></th>
							<td>
                            <?php
							$languages_list = array(
                                "en_AL" => "Albania - U.K. English",
                                "en_DZ" => "Algeria - U.K. English",
                                "en_AD" => "Andorra - U.K. English",
                                "en_AO" => "Angola - U.K. English",
                                "en_AI" => "Anguilla - U.K. English",
                                "en_AG" => "Antigua and Barbuda - U.K. English",
                                "en_AR" => "Argentina - U.K. English",
                                "en_AM" => "Armenia - U.K. English",
                                "en_AW" => "Aruba - U.K. English",
                                "en_AU" => "Australia - Australian English",
                                "de_AT" => "Austria - German",
                                "en_AT" => "Austria - U.S. English",
                                "en_AZ" => "Azerbaijan Republic - U.K. English",
                                "en_BS" => "Bahamas - U.K. English",
                                "en_BH" => "Bahrain - U.K. English",
                                "en_BB" => "Barbados - U.K. English",
                                "en_BE" => "Belgium - U.S. English",
                                "nl_BE" => "Belgium - Dutch",
                                "fr_BE" => "Belgium - French",
                                "en_BZ" => "Belize - U.K. English",
                                "en_BJ" => "Benin - U.K. English",
                                "en_BM" => "Bermuda - U.K. English",
                                "en_BT" => "Bhutan - U.K. English",
                                "en_BO" => "Bolivia - U.K. English",
                                "en_BA" => "Bosnia and Herzegovina - U.K. English",
                                "en_BW" => "Botswana - U.K. English",
                                "en_BR" => "Brazil - U.K. English",
                                "en_VG" => "British Virgin Islands - U.K. English",
                                "en_BN" => "Brunei - U.K. English",
                                "en_BG" => "Bulgaria - U.K. English",
                                "en_BF" => "Burkina Faso - U.K. English",
                                "en_BI" => "Burundi - U.K. English",
                                "en_KH" => "Cambodia - U.K. English",
                                "en_CA" => "Canada - U.S. English",
                                "fr_CA" => "Canada - French",
                                "en_CV" => "Cape Verde - U.K. English",
                                "en_KY" => "Cayman Islands - U.K. English",
                                "en_TD" => "Chad - U.K. English",
                                "en_CL" => "Chile - U.K. English",
                                "en_C2" => "China - U.S. English",
                                "zh_C2" => "China - Simplified Chinese",
                                "en_CO" => "Colombia - U.K. English",
                                "en_KM" => "Comoros - U.K. English",
                                "en_CK" => "Cook Islands - U.K. English",
                                "en_CR" => "Costa Rica - U.K. English",
                                "en_HR" => "Croatia - U.K. English",
                                "en_CY" => "Cyprus - U.K. English",
                                "en_CZ" => "Czech Republic - U.K. English",
                                "en_CD" => "Democratic Republic of the Congo - U.K. English",
                                "en_DK" => "Denmark - U.K. English",
                                "en_DJ" => "Djibouti - U.K. English",
                                "en_DM" => "Dominica - U.K. English",
                                "en_DO" => "Dominican Republic - U.K. English",
                                "en_EC" => "Ecuador - U.K. English",
                                "en_SV" => "El Salvador - U.K. English",
                                "en_ER" => "Eritrea - U.K. English",
                                "en_EE" => "Estonia - U.K. English",
                                "en_ET" => "Ethiopia - U.K. English",
                                "en_FK" => "Falkland Islands - U.K. English",
                                "en_FO" => "Faroe Islands - U.K. English",
                                "en_FM" => "Federated States of Micronesia - U.K. English",
                                "en_FJ" => "Fiji - U.K. English",
                                "en_FI" => "Finland - U.K. English",
                                "fr_FR" => "France - French",
                                "en_FR" => "France - U.S. English",
                                "en_GF" => "French Guiana - U.K. English",
                                "en_PF" => "French Polynesia - U.K. English",
                                "en_GA" => "Gabon Republic - U.K. English",
                                "en_GM" => "Gambia - U.K. English",
                                "de_DE" => "Germany - German",
                                "en_DE" => "Germany - U.S. English",
                                "en_GI" => "Gibraltar - U.K. English",
                                "en_GR" => "Greece - U.K. English",
                                "en_GL" => "Greenland - U.K. English",
                                "en_GD" => "Grenada - U.K. English",
                                "en_GP" => "Guadeloupe - U.K. English",
                                "en_GT" => "Guatemala - U.K. English",
                                "en_GN" => "Guinea - U.K. English",
                                "en_GW" => "Guinea Bissau - U.K. English",
                                "en_GY" => "Guyana - U.K. English",
                                "en_HN" => "Honduras - U.K. English",
                                "zh_HK" => "Hong Kong - Traditional Chinese",
                                "en_HK" => "Hong Kong - U.K. English",
                                "en_HU" => "Hungary - U.K. English",
                                "en_IS" => "Iceland - U.K. English",
                                "en_IN" => "India - U.K. English",
                                "en_ID" => "Indonesia - U.K. English",
                                "en_IE" => "Ireland - U.K. English",
                                "en_IL" => "Israel - U.K. English",
                                "it_IT" => "Italy - Italian",
                                "en_IT" => "Italy - U.S. English",
                                "en_JM" => "Jamaica - U.K. English",
                                "ja_JP" => "Japan - Japanese",
                                "en_JP" => "Japan - U.S. English",
                                "en_JO" => "Jordan - U.K. English",
                                "en_KZ" => "Kazakhstan - U.K. English",
                                "en_KE" => "Kenya - U.K. English",
                                "en_KI" => "Kiribati - U.K. English",
                                "en_KW" => "Kuwait - U.K. English",
                                "en_KG" => "Kyrgyzstan - U.K. English",
                                "en_LA" => "Laos - U.K. English",
                                "en_LV" => "Latvia - U.K. English",
                                "en_LS" => "Lesotho - U.K. English",
                                "en_LI" => "Liechtenstein - U.K. English",
                                "en_LT" => "Lithuania - U.K. English",
                                "en_LU" => "Luxembourg - U.K. English",
                                "en_MG" => "Madagascar - U.K. English",
                                "en_MW" => "Malawi - U.K. English",
                                "en_MY" => "Malaysia - U.K. English",
                                "en_MV" => "Maldives - U.K. English",
                                "en_ML" => "Mali - U.K. English",
                                "en_MT" => "Malta - U.K. English",
                                "en_MH" => "Marshall Islands - U.K. English",
                                "en_MQ" => "Martinique - U.K. English",
                                "en_MR" => "Mauritania - U.K. English",
                                "en_MU" => "Mauritius - U.K. English",
                                "en_YT" => "Mayotte - U.K. English",
                                "es_MX" => "Mexico - Spanish",
                                "en_MX" => "Mexico - U.S. English",
                                "en_MN" => "Mongolia - U.K. English",
                                "en_MS" => "Montserrat - U.K. English",
                                "en_MA" => "Morocco - U.K. English",
                                "en_MZ" => "Mozambique - U.K. English",
                                "en_NA" => "Namibia - U.K. English",
                                "en_NR" => "Nauru - U.K. English",
                                "en_NP" => "Nepal - U.K. English",
                                "nl_NL" => "Netherlands - Dutch",
                                "en_NL" => "Netherlands - U.S. English",
                                "en_AN" => "Netherlands Antilles - U.K. English",
                                "en_NC" => "New Caledonia - U.K. English",
                                "en_NZ" => "New Zealand - U.K. English",
                                "en_NI" => "Nicaragua - U.K. English",
                                "en_NE" => "Niger - U.K. English",
                                "en_NU" => "Niue - U.K. English",
                                "en_NF" => "Norfolk Island - U.K. English",
                                "en_NO" => "Norway - U.K. English",
                                "en_OM" => "Oman - U.K. English",
                                "en_PW" => "Palau - U.K. English",
                                "en_PA" => "Panama - U.K. English",
                                "en_PG" => "Papua New Guinea - U.K. English",
                                "en_PE" => "Peru - U.K. English",
                                "en_PH" => "Philippines - U.K. English",
                                "en_PN" => "Pitcairn Islands - U.K. English",
                                "pl_PL" => "Poland - Polish",
                                "en_PL" => "Poland - U.S. English",
                                "en_PT" => "Portugal - U.K. English",
                                "en_QA" => "Qatar - U.K. English",
                                "en_CG" => "Republic of the Congo - U.K. English",
                                "en_RE" => "Reunion - U.K. English",
                                "en_RO" => "Romania - U.K. English",
                                "en_RU" => "Russia - U.K. English",
                                "en_RW" => "Rwanda - U.K. English",
                                "en_VC" => "Saint Vincent and the Grenadines - U.K. English",
                                "en_WS" => "Samoa - U.K. English",
                                "en_SM" => "San Marino - U.K. English",
                                "en_ST" => "São Tomé and Príncipe - U.K. English",
                                "en_SA" => "Saudi Arabia - U.K. English",
                                "en_SN" => "Senegal - U.K. English",
                                "en_SC" => "Seychelles - U.K. English",
                                "en_SL" => "Sierra Leone - U.K. English",
                                "en_SG" => "Singapore - U.K. English",
                                "en_SK" => "Slovakia - U.K. English",
                                "en_SI" => "Slovenia - U.K. English",
                                "en_SB" => "Solomon Islands - U.K. English",
                                "en_SO" => "Somalia - U.K. English",
                                "en_ZA" => "South Africa - U.K. English",
                                "en_KR" => "South Korea - U.K. English",
                                "es_ES" => "Spain - Spanish",
                                "en_ES" => "Spain - U.S. English",
                                "en_LK" => "Sri Lanka - U.K. English",
                                "en_SH" => "St. Helena - U.K. English",
                                "en_KN" => "St. Kitts and Nevis - U.K. English",
                                "en_LC" => "St. Lucia - U.K. English",
                                "en_PM" => "St. Pierre and Miquelon - U.K. English",
                                "en_SR" => "Suriname - U.K. English",
                                "en_SJ" => "Svalbard and Jan Mayen Islands - U.K. English",
                                "en_SZ" => "Swaziland - U.K. English",
                                "en_SE" => "Sweden - U.K. English",
                                "de_CH" => "Switzerland - German",
                                "fr_CH" => "Switzerland - French",
                                "en_CH" => "Switzerland - U.S. English",
                                "en_TW" => "Taiwan - U.K. English",
                                "en_TJ" => "Tajikistan - U.K. English",
                                "en_TZ" => "Tanzania - U.K. English",
                                "en_TH" => "Thailand - U.K. English",
                                "en_TG" => "Togo - U.K. English",
                                "en_TO" => "Tonga - U.K. English",
                                "en_TT" => "Trinidad and Tobago - U.K. English",
                                "en_TN" => "Tunisia - U.K. English",
                                "en_TR" => "Turkey - U.K. English",
                                "en_TM" => "Turkmenistan - U.K. English",
                                "en_TC" => "Turks and Caicos Islands - U.K. English",
                                "en_TV" => "Tuvalu - U.K. English",
                                "en_UG" => "Uganda - U.K. English",
                                "en_UA" => "Ukraine - U.K. English",
                                "en_AE" => "United Arab Emirates - U.K. English",
                                "en_GB" => "United Kingdom - U.K. English",
                                "en_US" => "United States - U.S. English",
                                "fr_US" => "United States - French",
                                "es_US" => "United States - Spanish",
                                "zh_US" => "United States - Simplified Chinese",
                                "en_UY" => "Uruguay - U.K. English",
                                "en_VU" => "Vanuatu - U.K. English",
                                "en_VA" => "Vatican City State - U.K. English",
                                "en_VE" => "Venezuela - U.K. English",
                                "en_VN" => "Vietnam - U.K. English",
                                "en_WF" => "Wallis and Futuna Islands - U.K. English",
                                "en_YE" => "Yemen - U.K. English",
                                "en_ZM" => "Zambia - U.K. English",
                                "en_GB" => "International",
                                "en_AL" => "Albania - U.K. English",
                                "en_DZ" => "Algeria - U.K. English",
                                "en_AD" => "Andorra - U.K. English",
                                "en_AO" => "Angola - U.K. English",
                                "en_AI" => "Anguilla - U.K. English",
                                "en_AG" => "Antigua and Barbuda - U.K. English",
                                "en_AR" => "Argentina - U.K. English",
                                "en_AM" => "Armenia - U.K. English",
                                "en_AW" => "Aruba - U.K. English",
                                "en_AU" => "Australia - Australian English",
                                "de_AT" => "Austria - German",
                                "en_AT" => "Austria - U.S. English",
                                "en_AZ" => "Azerbaijan Republic - U.K. English",
                                "en_BS" => "Bahamas - U.K. English",
                                "en_BH" => "Bahrain - U.K. English",
                                "en_BB" => "Barbados - U.K. English",
                                "en_BE" => "Belgium - U.S. English",
                                "nl_BE" => "Belgium - Dutch",
                                "fr_BE" => "Belgium - French",
                                "en_BZ" => "Belize - U.K. English",
                                "en_BJ" => "Benin - U.K. English",
                                "en_BM" => "Bermuda - U.K. English",
                                "en_BT" => "Bhutan - U.K. English",
                                "en_BO" => "Bolivia - U.K. English",
                                "en_BA" => "Bosnia and Herzegovina - U.K. English",
                                "en_BW" => "Botswana - U.K. English",
                                "en_BR" => "Brazil - U.K. English",
                                "en_VG" => "British Virgin Islands - U.K. English",
                                "en_BN" => "Brunei - U.K. English",
                                "en_BG" => "Bulgaria - U.K. English",
                                "en_BF" => "Burkina Faso - U.K. English",
                                "en_BI" => "Burundi - U.K. English",
                                "en_KH" => "Cambodia - U.K. English",
                                "en_CA" => "Canada - U.S. English",
                                "fr_CA" => "Canada - French",
                                "en_CV" => "Cape Verde - U.K. English",
                                "en_KY" => "Cayman Islands - U.K. English",
                                "en_TD" => "Chad - U.K. English",
                                "en_CL" => "Chile - U.K. English",
                                "en_C2" => "China - U.S. English",
                                "zh_C2" => "China - Simplified Chinese",
                                "en_CO" => "Colombia - U.K. English",
                                "en_KM" => "Comoros - U.K. English",
                                "en_CK" => "Cook Islands - U.K. English",
                                "en_CR" => "Costa Rica - U.K. English",
                                "en_HR" => "Croatia - U.K. English",
                                "en_CY" => "Cyprus - U.K. English",
                                "en_CZ" => "Czech Republic - U.K. English",
                                "en_CD" => "Democratic Republic of the Congo - U.K. English",
                                "en_DK" => "Denmark - U.K. English",
                                "en_DJ" => "Djibouti - U.K. English",
                                "en_DM" => "Dominica - U.K. English",
                                "en_DO" => "Dominican Republic - U.K. English",
                                "en_EC" => "Ecuador - U.K. English",
                                "en_SV" => "El Salvador - U.K. English",
                                "en_ER" => "Eritrea - U.K. English",
                                "en_EE" => "Estonia - U.K. English",
                                "en_ET" => "Ethiopia - U.K. English",
                                "en_FK" => "Falkland Islands - U.K. English",
                                "en_FO" => "Faroe Islands - U.K. English",
                                "en_FM" => "Federated States of Micronesia - U.K. English",
                                "en_FJ" => "Fiji - U.K. English",
                                "en_FI" => "Finland - U.K. English",
                                "fr_FR" => "France - French",
                                "en_FR" => "France - U.S. English",
                                "en_GF" => "French Guiana - U.K. English",
                                "en_PF" => "French Polynesia - U.K. English",
                                "en_GA" => "Gabon Republic - U.K. English",
                                "en_GM" => "Gambia - U.K. English",
                                "de_DE" => "Germany - German",
                                "en_DE" => "Germany - U.S. English",
                                "en_GI" => "Gibraltar - U.K. English",
                                "en_GR" => "Greece - U.K. English",
                                "en_GL" => "Greenland - U.K. English",
                                "en_GD" => "Grenada - U.K. English",
                                "en_GP" => "Guadeloupe - U.K. English",
                                "en_GT" => "Guatemala - U.K. English",
                                "en_GN" => "Guinea - U.K. English",
                                "en_GW" => "Guinea Bissau - U.K. English",
                                "en_GY" => "Guyana - U.K. English",
                                "en_HN" => "Honduras - U.K. English",
                                "zh_HK" => "Hong Kong - Traditional Chinese",
                                "en_HK" => "Hong Kong - U.K. English",
                                "en_HU" => "Hungary - U.K. English",
                                "en_IS" => "Iceland - U.K. English",
                                "en_IN" => "India - U.K. English",
                                "en_ID" => "Indonesia - U.K. English",
                                "en_IE" => "Ireland - U.K. English",
                                "en_IL" => "Israel - U.K. English",
                                "it_IT" => "Italy - Italian",
                                "en_IT" => "Italy - U.S. English",
                                "en_JM" => "Jamaica - U.K. English",
                                "ja_JP" => "Japan - Japanese",
                                "en_JP" => "Japan - U.S. English",
                                "en_JO" => "Jordan - U.K. English",
                                "en_KZ" => "Kazakhstan - U.K. English",
                                "en_KE" => "Kenya - U.K. English",
                                "en_KI" => "Kiribati - U.K. English",
                                "en_KW" => "Kuwait - U.K. English",
                                "en_KG" => "Kyrgyzstan - U.K. English",
                                "en_LA" => "Laos - U.K. English",
                                "en_LV" => "Latvia - U.K. English",
                                "en_LS" => "Lesotho - U.K. English",
                                "en_LI" => "Liechtenstein - U.K. English",
                                "en_LT" => "Lithuania - U.K. English",
                                "en_LU" => "Luxembourg - U.K. English",
                                "en_MG" => "Madagascar - U.K. English",
                                "en_MW" => "Malawi - U.K. English",
                                "en_MY" => "Malaysia - U.K. English",
                                "en_MV" => "Maldives - U.K. English",
                                "en_ML" => "Mali - U.K. English",
                                "en_MT" => "Malta - U.K. English",
                                "en_MH" => "Marshall Islands - U.K. English",
                                "en_MQ" => "Martinique - U.K. English",
                                "en_MR" => "Mauritania - U.K. English",
                                "en_MU" => "Mauritius - U.K. English",
                                "en_YT" => "Mayotte - U.K. English",
                                "es_MX" => "Mexico - Spanish",
                                "en_MX" => "Mexico - U.S. English",
                                "en_MN" => "Mongolia - U.K. English",
                                "en_MS" => "Montserrat - U.K. English",
                                "en_MA" => "Morocco - U.K. English",
                                "en_MZ" => "Mozambique - U.K. English",
                                "en_NA" => "Namibia - U.K. English",
                                "en_NR" => "Nauru - U.K. English",
                                "en_NP" => "Nepal - U.K. English",
                                "nl_NL" => "Netherlands - Dutch",
                                "en_NL" => "Netherlands - U.S. English",
                                "en_AN" => "Netherlands Antilles - U.K. English",
                                "en_NC" => "New Caledonia - U.K. English",
                                "en_NZ" => "New Zealand - U.K. English",
                                "en_NI" => "Nicaragua - U.K. English",
                                "en_NE" => "Niger - U.K. English",
                                "en_NU" => "Niue - U.K. English",
                                "en_NF" => "Norfolk Island - U.K. English",
                                "en_NO" => "Norway - U.K. English",
                                "en_OM" => "Oman - U.K. English",
                                "en_PW" => "Palau - U.K. English",
                                "en_PA" => "Panama - U.K. English",
                                "en_PG" => "Papua New Guinea - U.K. English",
                                "en_PE" => "Peru - U.K. English",
                                "en_PH" => "Philippines - U.K. English",
                                "en_PN" => "Pitcairn Islands - U.K. English",
                                "pl_PL" => "Poland - Polish",
                                "en_PL" => "Poland - U.S. English",
                                "en_PT" => "Portugal - U.K. English",
                                "en_QA" => "Qatar - U.K. English",
                                "en_CG" => "Republic of the Congo - U.K. English",
                                "en_RE" => "Reunion - U.K. English",
                                "en_RO" => "Romania - U.K. English",
                                "en_RU" => "Russia - U.K. English",
                                "en_RW" => "Rwanda - U.K. English",
                                "en_VC" => "Saint Vincent and the Grenadines - U.K. English",
                                "en_WS" => "Samoa - U.K. English",
                                "en_SM" => "San Marino - U.K. English",
                                "en_ST" => "São Tomé and Príncipe - U.K. English",
                                "en_SA" => "Saudi Arabia - U.K. English",
                                "en_SN" => "Senegal - U.K. English",
                                "en_SC" => "Seychelles - U.K. English",
                                "en_SL" => "Sierra Leone - U.K. English",
                                "en_SG" => "Singapore - U.K. English",
                                "en_SK" => "Slovakia - U.K. English",
                                "en_SI" => "Slovenia - U.K. English",
                                "en_SB" => "Solomon Islands - U.K. English",
                                "en_SO" => "Somalia - U.K. English",
                                "en_ZA" => "South Africa - U.K. English",
                                "en_KR" => "South Korea - U.K. English",
                                "es_ES" => "Spain - Spanish",
                                "en_ES" => "Spain - U.S. English",
                                "en_LK" => "Sri Lanka - U.K. English",
                                "en_SH" => "St. Helena - U.K. English",
                                "en_KN" => "St. Kitts and Nevis - U.K. English",
                                "en_LC" => "St. Lucia - U.K. English",
                                "en_PM" => "St. Pierre and Miquelon - U.K. English",
                                "en_SR" => "Suriname - U.K. English",
                                "en_SJ" => "Svalbard and Jan Mayen Islands - U.K. English",
                                "en_SZ" => "Swaziland - U.K. English",
                                "en_SE" => "Sweden - U.K. English",
                                "de_CH" => "Switzerland - German",
                                "fr_CH" => "Switzerland - French",
                                "en_CH" => "Switzerland - U.S. English",
                                "en_TW" => "Taiwan - U.K. English",
                                "en_TJ" => "Tajikistan - U.K. English",
                                "en_TZ" => "Tanzania - U.K. English",
                                "en_TH" => "Thailand - U.K. English",
                                "en_TG" => "Togo - U.K. English",
                                "en_TO" => "Tonga - U.K. English",
                                "en_TT" => "Trinidad and Tobago - U.K. English",
                                "en_TN" => "Tunisia - U.K. English",
                                "en_TR" => "Turkey - U.K. English",
                                "en_TM" => "Turkmenistan - U.K. English",
                                "en_TC" => "Turks and Caicos Islands - U.K. English",
                                "en_TV" => "Tuvalu - U.K. English",
                                "en_UG" => "Uganda - U.K. English",
                                "en_UA" => "Ukraine - U.K. English",
                                "en_AE" => "United Arab Emirates - U.K. English",
                                "en_GB" => "United Kingdom - U.K. English",
                                "en_US" => "United States - U.S. English",
                                "fr_US" => "United States - French",
                                "es_US" => "United States - Spanish",
                                "zh_US" => "United States - Simplified Chinese",
                                "en_UY" => "Uruguay - U.K. English",
                                "en_VU" => "Vanuatu - U.K. English",
                                "en_VA" => "Vatican City State - U.K. English",
                                "en_VE" => "Venezuela - U.K. English",
                                "en_VN" => "Vietnam - U.K. English",
                                "en_WF" => "Wallis and Futuna Islands - U.K. English",
                                "en_YE" => "Yemen - U.K. English",
                                "en_ZM" => "Zambia - U.K. English",
                                "en_GB" => "International"
                            );

                            ?>
                                <select name="sd_paypal_language">
                                    <?php
                                        $language_selected = get_option('sd_paypal_language', SD_PAYPAL_LANGUAGE);
                                        foreach($languages_list as $key => $value){
                                            echo '<option value="'.esc_attr($key).'" '.(($key == $language_selected) ? 'SELECTED' : '').'>'.sd_strip_tags($value,true).'</option>';
                                        }
                                    ?>
                                </select>
                            </td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Paypal button for instant purchases', SD_TEXT_DOMAIN); ?></th>
							<td><?php print $this->_paypal_buttons(); ?></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e("or use a shopping cart", SD_TEXT_DOMAIN); ?></th>
							<td>
								<input type='radio' DISABLED />
								<?php
								print $this->get_paypal_button(array('button'=>'shopping_cart/button_e.gif', 'attrs'=>'DISABLED'));
								print $this->get_paypal_button(array('button'=>'shopping_cart/button_f.gif', 'attrs'=>'DISABLED'));
								?>
                                <span style="color:#FF0000;">The shopping cart is available only in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
							</td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Apply taxes (in percentage)', SD_TEXT_DOMAIN); ?></th>
							<td><input type="number" name="sd_tax" value="<?php echo esc_attr(get_option('sd_tax', '')); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Download link valid for', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_old_download_link" value="<?php echo esc_attr(get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)); ?>" /> <?php _e('day(s)', SD_TEXT_DOMAIN)?></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Number of downloads allowed by purchase', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_downloads_number" value="<?php echo esc_attr(get_option('sd_downloads_number', SD_DOWNLOADS_NUMBER)); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Increase the download page security', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="sd_safe_download" <?php echo ( ( get_option('sd_safe_download', SD_SAFE_DOWNLOAD)) ? 'CHECKED' : '' ); ?> /> <?php _e('The customers must enter the email address used in the product\'s purchasing to access to the download link. The Store verifies the customer\'s data, from the file link too.', SD_TEXT_DOMAIN)?></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Pack all purchased files as a single ZIP file', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" DISABLED >
                            <span style="color:#FF0000;">To distribute the file as a zipped file is required the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
							<?php
								if(!class_exists('ZipArchive'))
									echo '<br /><span class="explain-text">'.__("Your server can't create Zipped files dynamically. Please, contact to your hosting provider for enable ZipArchive in the PHP script", SD_TEXT_DOMAIN).'</span>';
							?>
							</td>
							</tr>
							<tr>
								<td colspan="2">
									<div style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
										<p style="font-size:1.3em;">If you detect any issue with the payments or downloads please: <a href="#" onclick="jQuery('.sd-troubleshoot-area').show();return false;">CLICK HERE [ + ]</a></p>
										<div class="sd-troubleshoot-area" style="display:none;">
											<h3>An user has paid for a product but has not received the download link</h3>
											<p><b>Possible causes:</b></p>
											<p><span style="font-size:1.3em;">*</span> The Instant Payment Notification (IPN) is not enabled in your PayPal account, in whose case the website won't notified about the payments. Please, visit the following link: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a>. PayPal needs the URL to the IPN Script in your website, however, you simply should enter the URL to the home page, because the store will send the correct URL to the IPN Script.</p>
											<p><span style="font-size:1.3em;">*</span> The status of the payment is different to "Completed". If the payment status is different to "Completed" the Store won't generate the download link, or send the notification emails, to protect the sellers against frauds. PayPal will contact to the store even if the payment is "Pending" or has "Failed".</p>
											<p><b>But if the IPN is enabled, how can be detected the cause of issue?</b></p>
											<p>In this case you should check the IPN history (<a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNOperations/#link-viewipnmessagesanddetails" target="_blank">CLICK HERE</a>)  for checking all variables that your PayPal account has sent to your website, and pays special attention to the "payment_status" variable (<a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNOperations/#link-viewipnmessagesanddetails" target="_blank">CLICK HERE</a>)</p>
											<p><b>The IPN is enabled, and the status of the payment in the PayPal account is "Completed", the purchase has been registered in the sales reports of the Store (the menu option in your WordPress: "Sell Downloads/Sales Report") but the buyer has not received the notification email. What is the cause?</b></p>
											<p><span style="font-size:1.3em;">*</span> Enter an email address belonging to your website's domain through the attribute: "Notification "from" email" in the store's settings ( accessible from the menu option: "Sell Downloads/Sell Downloads Settings"). The email services (like AOL, YAHOO, MSN, etc.) check the email addresses in the "Sender" header of the emails, and if they do not belong to the websites that send the emails, can be classified as spam or even worst, as "Phishing" emails.</p>
											<p><span style="font-size:1.3em;">*</span> The email address in the "From" attribute belongs to the store's domain, but the buyer is not receiving the notification email. In this case you should ask the hosting provider the accesses to the SMTP server (all hosting providers include one), and install any of the plugin for SMTP connection distributed for free through the WordPress directory.</p>
											<p><b>The buyer has received the notification email with the download link, but cannot download the files.</b></p>
											<p><span style="font-size:1.3em;">*</span> The Sell Downloads plugin prevents the direct access to the files for security reasons. From the download page, the store checks the number of downloads, the buyer email, or the expiration time for the download link, so, the plugin works as proxy between the browser, and the product's file, so, the PHP Script should have assigned sufficient memory to load the file. Pay attention, the amount of memory assigned to the PHP Script in the web server can be bigger than the file's size, however, you should to consider that all the concurrent accesses to your website are sharing the same PHP memory, and if two buyers are downloading a same file at the same time, the PHP Script in the server should to load in memory the file twice.</p>
											<p><a href="#" onclick="jQuery('.sd-troubleshoot-area').hide();return false;">CLOSE SECTION [ - ]</a></p>
										</div>
									</div>
									<div style="border:1px solid #ddd;padding:15px;">
									<input type="checkbox" name="sd_debug_payment" <?php print get_option('sd_debug_payment') ? 'CHECKED' : ''; ?> /> <b><?php _e('Debugging Payment Process', SD_TEXT_DOMAIN); ?></b><br /><br />
									<i><?php _e("(If the checkbox is ticked the plugin will create two new entries in the error  logs file on your server, with the texts <b>Sell Downloads payment gateway GET parameters</b> and <b>Sell Downloads payment gateway POST parameters</b>.  If after a purchase, none of these entries appear in the error logs file, the payment notification has not reached the plugin's code)", SD_TEXT_DOMAIN); ?></i>
									</div>
								</td>
							</tr>
							<tr><td colspan="2"><hr /></td></tr>
							<tr>
							<th scope="row">
								<?php _e( 'Restrict the access to registered users only', SD_TEXT_DOMAIN ); ?>
							</th>
							<td>
								<input type="checkbox" name="sd_download_link_for_registered_only" <?php print get_option('sd_download_link_for_registered_only') ? 'CHECKED' : ''; ?> />
								<?php _e('Display the free download links only for registered users', SD_TEXT_DOMAIN ); ?><br />
							</td>
							</tr>
							<tr><td colspan="2"><hr /></td></tr>
						 </table>
					  </div>
					</div>

					<!--DISCOUNT BOX -->
                    <div class="postbox">
                        <h3 class='hndle' style="padding:5px;"><span><?php _e('Discount Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
                            <div style="color:#FF0000;">The discounts management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a></div>
                            <div><input type="checkbox" DISABLED /> <?php _e('Display discount promotions in the store page', SD_TEXT_DOMAIN)?></div>
                            <h4><?php _e('Scheduled Discounts', SD_TEXT_DOMAIN);?></h4>
                            <table class="form-table sd_discount_table" style="border:1px dotted #dfdfdf;">
                                <tr>
                                    <td style="font-weight:bold;"><?php _e('Percent of discount', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('In Sales over than ... ', SD_TEXT_DOMAIN); echo( ( !empty( $currency_selected ) ) ? $currency_selected : '' ); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                                    <td></td>
                                </tr>
                            </table>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Percent of discount (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> %</td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid for sales over than (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> USD</td>
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
                                <tr><td colspan="2"><input type="button" class="button" value="<?php esc_attr_e(__('Add/Update Discount')); ?>" DISABLED></td></tr>
                            </table>
                        </div>
                    </div>

                    <!--COUPONS BOX -->
                    <div class="postbox">
                        <h3 class='hndle' style="padding:5px;"><span><?php _e('Coupons Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
                            <div style="color:#FF0000;">The coupons management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a></div>
                            <h4><?php _e('Coupons List', SD_TEXT_DOMAIN);?></h4>
                            <table class="form-table sd_coupon_table" style="border:1px dotted #dfdfdf;">
                                <tr>
                                    <td style="font-weight:bold;"><?php _e('Percent of discount', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Coupon', SD_TEXT_DOMAIN);?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                                    <td></td>
                                </tr>
                            </table>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Percent of discount (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> %</td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Coupon (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid from (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid to (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr><td colspan="2"><input type="button" class="button" value="<?php esc_attr_e(__('Add/Update Coupon')); ?>"DISABLED /></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- NOTIFICATIONS BOX -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Notification Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
						<?php
							$_sd_notification_from_email = get_option('sd_notification_from_email', SD_NOTIFICATION_FROM_EMAIL);
							$_sd_notification_to_email 	 = get_option('sd_notification_to_email', SD_NOTIFICATION_TO_EMAIL);
							if($_sd_notification_from_email == SD_NOTIFICATION_FROM_EMAIL)
							{
								$user_email = get_the_author_meta('user_email', get_current_user_id());
								$host = $_SERVER['HTTP_HOST'];
								preg_match("/[^\.\/]+(\.[^\.\/]+)?$/", $host, $matches);
								$domain = $matches[0];
								$pos = strpos($user_email, $domain);
								if ($pos === false) $_sd_notification_from_email = 'admin@'.$domain;
							}

							if($_sd_notification_to_email == SD_NOTIFICATION_TO_EMAIL)
							{
								if(!isset($user_email)) $user_email = get_the_author_meta('user_email', get_current_user_id());
								if(!empty($user_email)) $_sd_notification_to_email = $user_email;
							}
						?>
						<table class="form-table">
							<tr valign="top">
							<th scope="row"><?php _e('Notification "from" email', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_from_email" size="40" value="<?php echo esc_attr($_sd_notification_from_email); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Send notification to email', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_email" size="40" value="<?php echo esc_attr($_sd_notification_to_email); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Email subject confirmation to user', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_payer_subject" size="40" value="<?php echo esc_attr(get_option('sd_notification_to_payer_subject', SD_NOTIFICATION_TO_PAYER_SUBJECT)); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Email confirmation to user', SD_TEXT_DOMAIN); ?></th>
							<td><textarea name="sd_notification_to_payer_message" cols="60" rows="5"><?php echo esc_textarea( stripcslashes(get_option('sd_notification_to_payer_message', SD_NOTIFICATION_TO_PAYER_MESSAGE))); ?></textarea></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Email subject notification to admin', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_seller_subject" size="40" value="<?php echo esc_attr(get_option('sd_notification_to_seller_subject', SD_NOTIFICATION_TO_SELLER_SUBJECT)); ?>" /></td>
							</tr>

							<tr valign="top">
							<th scope="row"><?php _e('Email notification to admin', SD_TEXT_DOMAIN); ?></th>
							<td><textarea name="sd_notification_to_seller_message"  cols="60" rows="5"><?php echo esc_textarea(stripcslashes(get_option('sd_notification_to_seller_message', SD_NOTIFICATION_TO_SELLER_MESSAGE))); ?></textarea></td>
							</tr>
						 </table>
					  </div>
					</div>

					<!-- TROUBLESHOOT AREA -->
					<div class="postbox" style="border: 1px solid #FF0000; background-color: rgba(255,0,0,0.1);">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Troubleshoot Area', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
						<table class="form-table">
							<tr valign="top">
							<th scope="row"><?php _e('The downloaded file is broken', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="sd_troubleshoot_no_ob" <?php if(get_option('sd_troubleshoot_no_ob')) print 'CHECKED'; ?> />
							</td>
							</tr>
						 </table>
					  </div>
					</div>
					<?php
						do_action( 'sd_show_settings' );
						wp_nonce_field( plugin_basename( __FILE__ ), 'sd_settings' );
					?>
					<div class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e(__('Update Settings', SD_TEXT_DOMAIN)); ?>" />
					</form>

<?php
				break;
				case 'reports':
					$message_list = '';
					$new_entry_message = '';

					if ( isset($_POST['sd_purchase_stats']) && wp_verify_nonce( $_POST['sd_purchase_stats'], plugin_basename( __FILE__ ) ) ){
						if(isset($_POST['sd_new_entry'])) // Create a new entry in the sales reports
						{
							if( !empty($_POST['new_entry_buyer']) )
							{
								if(!empty($_POST['new_entry_product']))
								{
									if(!empty($_POST['new_entry_year']))
									{
										if(
											!empty($_POST['new_entry_amount']) &&
											!empty($_POST['new_entry_currency'])
										)
										{
											mt_srand(sell_downloads_make_seed());
											$randval = mt_rand(1,999999);
											$new_entry_purchase_id = md5($randval.uniqid('', true));

											if(
												$wpdb->insert(
													$wpdb->prefix.SDDB_PURCHASE,
													array(
														'product_id'  => sanitize_text_field($_POST['new_entry_product']),
														'purchase_id' => $new_entry_purchase_id,
														'date'		  => sanitize_text_field($_POST['new_entry_year'].'-'.$_POST['new_entry_month'].'-'.$_POST['new_entry_day']),
														'email'		  => sanitize_text_field($_POST['new_entry_buyer']),
														'amount'	  => @floatval($_POST['new_entry_amount']),
														'paypal_data' => sanitize_text_field($_POST['new_entry_payment_data'].' mc_currency='.$_POST['new_entry_currency'])
													),
													array('%d', '%s', '%s', '%s', '%f', '%s')
												)
											)
											{
												// Sends the download link to the buyer
												if(!empty($_POST['new_entry_send_mail']))
													$_POST['resend_purchase_id'] = $wpdb->insert_id;

												// Updates the number of purchases
												$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix.SDDB_POST_DATA." SET purchases=purchases+1 WHERE id=%d", @intval($_POST['new_entry_product'])));

												$new_entry_message .= '<li>'.__('New entry added to the sales report',SD_TEXT_DOMAIN).'</li>';
											}
											else
											{
												$error_message .= '<li>'.__('The new entry couldn\'t be inserted in the sales reports',SD_TEXT_DOMAIN).'</li>';
											}
										}
										else
										{
											$error_message .= '<li>'.__('The amount and currency are required',SD_TEXT_DOMAIN).'</li>';
										}
									}
									else
									{
										$error_message .= '<li>'.__('The date is wrong',SD_TEXT_DOMAIN).'</li>';
									}
								}
								else
								{
									$error_message .= '<li>'.__('The product is required',SD_TEXT_DOMAIN).'</li>';
								}
							}
							else
							{
								$error_message .= '<li>'.__('The buyer is required',SD_TEXT_DOMAIN).'</li>';
							}
						} // End manual entry

						if(isset($_POST['delete_purchase_id']) && @intval( $_POST['delete_purchase_id'] ) ){ // Delete the purchase
							$wpdb->query($wpdb->prepare(
								"DELETE FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE id=%d",
								@intval($_POST['delete_purchase_id'])
							));
						}

						if(isset($_POST['resend_purchase_id'])){ // Resend the email to the buyer with the download link
							$purchase_to_resend = $wpdb->get_row(
								$wpdb->prepare(
									"SELECT * FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE id=%d",
									@intval($_POST['resend_purchase_id'])
								)
							);

							if(!empty($purchase_to_resend))
							{
								sd_send_emails(
									array(
										'item_name' => __("Products from ", SD_TEXT_DOMAIN).get_bloginfo('show'),
										'purchase_id' => $purchase_to_resend->purchase_id,
										'payer_email' => $purchase_to_resend->email,
										'date' => $purchase_to_resend->date
									)
								);

								$message_list .= '<li>'.__('Email sent',SD_TEXT_DOMAIN).'</li>';
							}
						}

						if(
							isset($_POST['reset_purchase_id']) ||
							isset($_POST['resend_purchase_id'])
						){ // Reset the purchase
							$purchase_id = isset($_POST['reset_purchase_id']) ? @intval($_POST['reset_purchase_id']) : @intval($_POST['resend_purchase_id']);
							$wpdb->query($wpdb->prepare(
								"UPDATE ".$wpdb->prefix.SDDB_PURCHASE." SET checking_date = NOW(), downloads = 0 WHERE id=%d",
								$purchase_id
							));
						}

						if(isset($_POST['show_purchase_id']) && @intval( $_POST['show_purchase_id'] ) ){ // PayPal Data
							$paypal_data = '<div class="sd-paypal-data"><h3>' . __( 'PayPal data', SD_TEXT_DOMAIN ) . '</h3>' . $wpdb->get_var($wpdb->prepare(
								"SELECT paypal_data FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE id=%d",
								@intval($_POST['show_purchase_id'])
							)) . '</div>';
							$paypal_data = preg_replace( '/\n+/', '<br />', $paypal_data );
						}
					}

					$group_by_arr = array(
										'no_group'  => 'Group by',
										'sd_type'    => 'Type of file'
									);


					$from_day = (isset($_POST['from_day'])) ? @intval($_POST['from_day']) : date('j');
					$from_month = (isset($_POST['from_month'])) ? @intval($_POST['from_month']) : date('m');
					$from_year = (isset($_POST['from_year'])) ? @intval($_POST['from_year']) : date('Y');
					$buyer = ( !empty( $_POST['buyer'] ) ) ? stripcslashes($_POST[ 'buyer' ]) : '';
                    $buyer = sanitize_email( $buyer );

					$to_day = (isset($_POST['to_day'])) ? @intval($_POST['to_day']) : date('j');
					$to_month = (isset($_POST['to_month'])) ? @intval($_POST['to_month']) : date('m');
					$to_year = (isset($_POST['to_year'])) ? @intval($_POST['to_year']) : date('Y');

					$group_by = (isset($_POST['group_by']) && sanitize_text_field( wp_unslash( $_POST['group_by'] ) ) == 'sd_type') ? 'sd_type' : 'no_group';
					$to_display = (isset($_POST['to_display'])) ? sanitize_text_field( wp_unslash( $_POST['to_display'] ) ) : 'sales';

					$_select = "";
					$_from 	 = " FROM ".$wpdb->prefix.SDDB_PURCHASE." AS purchase, ".$wpdb->prefix."posts AS posts ";
					$_where  = $wpdb->prepare( " WHERE posts.ID = purchase.product_id
									AND posts.post_type = 'sd_product'
									AND DATEDIFF(purchase.date, '%d-%d-%d')>=0
									AND DATEDIFF(purchase.date, '%d-%d-%d')<=0 ",
									$from_year, $from_month, $from_day,
									$to_year, $to_month, $to_day
								);
					if( !empty( $buyer ) )
                    {
                        $_where .= $wpdb->prepare( "AND purchase.email LIKE %s", "%".$buyer."%" );
                    }

					$_group  = "";
					$_order  = "";
					$_date_dif = floor( max( abs( strtotime( $to_year.'-'.$to_month.'-'.$to_day ) - strtotime( $from_year.'-'.$from_month.'-'.$from_day ) ) / ( 60*60*24 ), 1 ) );
					$_table_header = array( 'Date', 'Product', 'Buyer', 'Amount', 'Currency', 'Download link', '' );

					if( $group_by == 'no_group' )
					{
						if( $to_display == 'sales' )
						{
							$_select .= "SELECT purchase.*, posts.*";
						}
						else
						{
							$_select .= $wpdb->prepare( "SELECT SUM(purchase.amount)/%d as purchase_average, SUM(purchase.amount) as purchase_total, COUNT(posts.ID) as purchase_count, posts.*", $_date_dif );
							$_group   = " GROUP BY posts.ID";
							if( $to_display == 'amount' )
							{
								$_table_header = array( 'Product', 'Amount of Sales', 'Total' );
								$_order = " ORDER BY purchase_count DESC";
							}
							else
							{
								$_table_header = array( 'Product', 'Daily Average', 'Total' );
								$order =  " ORDER BY purchase_average DESC";
							}
						}
					}
					else
					{
						$_select .= $wpdb->prepare("SELECT SUM(purchase.amount)/%d as purchase_average, SUM(purchase.amount) as purchase_total, COUNT(posts.ID) as purchase_count, terms.name as term_name, terms.slug as term_slug", $_date_dif);

						$_from   .= ", {$wpdb->prefix}term_taxonomy as taxonomy,
								     {$wpdb->prefix}term_relationships as term_relationships,
								     {$wpdb->prefix}terms as terms";
						$_where  .= $wpdb->prepare(" AND taxonomy.taxonomy = %s
									 AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
									 AND term_relationships.object_id=posts.ID
									 AND taxonomy.term_id=terms.term_id", $group_by);
						$_group  = " GROUP BY terms.term_id";
						$_order  = " ORDER BY terms.slug;";

						if( $to_display == 'amount' )
						{
							$_order = " ORDER BY purchase_count DESC";
							$_table_header = array( $group_by_arr[ $group_by ], 'Amount of Sales', 'Total' );
						}
						else
						{
							$order =  " ORDER BY purchase_average DESC";
							if( $to_display == 'sales' )
							{
								$_table_header = array( $group_by_arr[ $group_by ], 'Total' );
							}
							else
							{
								$_table_header = array( $group_by_arr[ $group_by ], 'Daily Average', 'Total' );
							}
						}
					}
					$purchase_list = $wpdb->get_results( $_select.$_from.$_where.$_group.$_order );
?>
					<form method="post" action="<?php echo admin_url('admin.php?page=sell-downloads-menu-reports&tab=reports'); ?>" id="purchase_form">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sd_purchase_stats' ); ?>
					<input type="hidden" name="tab" value="reports" />
					<?php
						$months_list = array(
							'01' => __('January', SD_TEXT_DOMAIN),
							'02' => __('February', SD_TEXT_DOMAIN),
							'03' => __('March', SD_TEXT_DOMAIN),
							'04' => __('April', SD_TEXT_DOMAIN),
							'05' => __('May', SD_TEXT_DOMAIN),
							'06' => __('June', SD_TEXT_DOMAIN),
							'07' => __('July', SD_TEXT_DOMAIN),
							'08' => __('August', SD_TEXT_DOMAIN),
							'09' => __('September', SD_TEXT_DOMAIN),
							'10' => __('October', SD_TEXT_DOMAIN),
							'11' => __('November', SD_TEXT_DOMAIN),
							'12' => __('December', SD_TEXT_DOMAIN),
						);
						$today = getdate();
					?>
					<!-- MANUAL ENTRY -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Manual Entry', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<div>
								<?php
								if(!empty($error_message))
								{
									print '<div class="sd-error-mssg"><ul>'.$error_message.'</ul></div>';
								}
								if(!empty($new_entry_message))
								{
									print '<div class="sd-mssg"><ul>'.$new_entry_message.'</ul></div>';
								}
								?>
								<table>
									<tr>
										<td>
											<label><?php _e('Buyer', SD_TEXT_DOMAIN); ?>*:</label>
										</td>
										<td>
											<input type="text" name="new_entry_buyer" id="new_entry_buyer" />
											<input type="checkbox" name="new_entry_send_mail" name="new_entry_send_mail" /> <?php _e('Send the download link to the buyer', SD_TEXT_DOMAIN);?>
										</td>
									</tr>
									<tr>
										<td>
											<label><?php _e('Product', SD_TEXT_DOMAIN); ?>*:</label>
										</td>
										<td>
											<select name="new_entry_product" id="new_entry_product">
												<option value=""><?php _e('Select a product', SD_TEXT_DOMAIN); ?></option>
												<?php
													$all_products = $wpdb->get_results(
														"SELECT ID,post_title,post_type FROM ".$wpdb->posts." WHERE post_type='sd_product' AND post_status='publish' ORDER BY post_title ASC",
														ARRAY_A
													);
													if($all_products)
													{
														$manual_product_id = isset($_GET['sd-product-id']) ? @intval($_GET['sd-product-id']) : 0;
														foreach($all_products as $product)
														{
															print '<option value="'.esc_attr($product['ID']).'" '.($manual_product_id == $product['ID'] ? 'SELECTED' : '').'>'.esc_html('('.$product['ID'].') '.$product['post_title']).'</option>';
														}
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td>
											<label><?php _e('Date', SD_TEXT_DOMAIN); ?>*:</label>
										</td>
										<td>
											<select name="new_entry_day">
												<?php
													for($i=1; $i <=31; $i++)
														print '<option value="'.$i.'" '.(($i==$today['mday']) ? 'SELECTED' : '').'>'.$i.'</option>';
												?>
											</select>
											<select name="new_entry_month">
												<?php
													foreach($months_list as $month => $name)
														print '<option value="'.esc_attr($month).'" '.(($month*1==$today['mon']) ? 'SELECTED' : '').'>'.$name.'</option>';
												?>
											</select>
											<input type="text" name="new_entry_year" value="<?php esc_attr_e($today['year']); ?>" />
										</td>
									</tr>
									<tr>
										<td>
											<label><?php _e('Amount', SD_TEXT_DOMAIN); ?>*: </label>
										</td>
										<td>
											<input type="text" name="new_entry_amount" />
											<label><?php _e('Currency', SD_TEXT_DOMAIN); ?>*: </label>
											<input type="text" name="new_entry_currency" placeholder="<?php _e('for example: USD', SD_TEXT_DOMAIN); ?>" value="<?php print esc_attr(get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY)); ?>" />
										</td>
									</tr>
									<tr>
										<td valign="top">
											<label><?php _e('Payment data', SD_TEXT_DOMAIN); ?>: </label>
										</td>
										<td>
											<textarea name="new_entry_payment_data" style="min-width:50%;height:100px;"></textarea>
										</td>
									</tr>
								</table>
								<input type="submit" value="<?php esc_attr_e(__('Add Entry', SD_TEXT_DOMAIN)); ?>" class="button-primary" onmousedown="jQuery(this).closest('form').append('<input type=\'hidden\' name=\'sd_new_entry\' value=\'1\' />');" />
							</div>
							<div style="clear:both;"></div>
						</div>
					</div>

					<!-- FILTER REPORT -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Filter the sales reports', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<div>
								<h4><?php _e('Filter by date', SD_TEXT_DOMAIN); ?></h4>
								<label><?php _e('Buyer: ', SD_TEXT_DOMAIN); ?></label><input type="text" name="buyer" id="buyer" value="<?php print esc_attr($buyer); ?>" />
								<label><?php _e('From: ', SD_TEXT_DOMAIN); ?></label>
								<select name="from_day">
								<?php
									for($i=1; $i <=31; $i++) print '<option value="'.esc_attr($i).'"'.(($from_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
								?>
								</select>
								<select name="from_month">
								<?php
									foreach($months_list as $month => $name) print '<option value="'.esc_attr($month).'"'.(($from_month == $month) ? ' SELECTED' : '').'>'.sd_strip_tags($name,true).'</option>';
								?>
								</select>
								<input type="text" name="from_year" value="<?php esc_attr_e($from_year); ?>" />

								<label><?php _e('To: ', SD_TEXT_DOMAIN); ?></label>
								<select name="to_day">
								<?php
									for($i=1; $i <=31; $i++) print '<option value="'.esc_attr($i).'"'.(($to_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
								?>
								</select>
								<select name="to_month">
								<?php
									foreach($months_list as $month => $name) print '<option value="'.esc_attr($month).'"'.(($to_month == $month) ? ' SELECTED' : '').'>'.sd_strip_tags($name,true).'</option>';
								?>
								</select>
								<input type="text" name="to_year" value="<?php esc_attr_e($to_year); ?>" />

								<input type="submit" value="<?php esc_attr_e(__('Search', SD_TEXT_DOMAIN)); ?>" class="button-primary" />
							</div>

							<div style="float:left;margin-right:20px;">
								<h4><?php _e('Grouping the sales', SD_TEXT_DOMAIN); ?></h4>
								<label><?php _e('By: ', SD_TEXT_DOMAIN); ?></label>
								<select name="group_by">
								<?php
									foreach( $group_by_arr as $key => $value )
									{
										print '<option value="'.esc_attr($key).'"'.( ( isset( $group_by ) && $group_by == $key ) ? ' SELECTED' : '' ).'>'.sd_strip_tags($value,true).'</option>';
									}
								?>
								</select>
							</div>
							<div style="float:left;margin-right:20px;">
								<h4><?php _e('Display', SD_TEXT_DOMAIN); ?></h4>
								<label><input type="radio" name="to_display" <?php echo ( ( !isset( $to_display ) || $to_display == 'sales' ) ? 'CHECKED' : '' ); ?> value="sales" /> <?php _e('Sales', SD_TEXT_DOMAIN); ?></label>
								<label><input type="radio" name="to_display" <?php echo ( ( isset( $to_display ) && $to_display == 'amount' ) ? 'CHECKED' : '' ); ?> value="amount" /> <?php _e('Amount of sales', SD_TEXT_DOMAIN); ?></label>
								<label><input type="radio" name="to_display" <?php echo ( ( isset( $to_display ) && $to_display == 'average' ) ? 'CHECKED' : '' ); ?> value="average" /> <?php _e('Daily average', SD_TEXT_DOMAIN); ?></label>
							</div>
							<div style="clear:both;"></div>
						</div>
					</div>
					<!-- PURCHASE LIST -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Sell Downloads sales report', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<?php
								if( !empty( $paypal_data ) ) print $paypal_data;
								if(count($purchase_list)){
									print '
										<div>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="sd_load_report(this, \'sales_by_country\', \''.esc_js(__( 'Sales by country', SD_TEXT_DOMAIN )).'\', \'residence_country\', \'Pie\', \'residence_country\', \'count\');" /> '.__( 'Sales by country', SD_TEXT_DOMAIN ).'</label>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="sd_load_report(this, \'sales_by_currency\', \''.esc_js(__( 'Sales by currency', SD_TEXT_DOMAIN )).'\', \'mc_currency\', \'Bar\', \'mc_currency\', \'sum\');" /> '.__( 'Sales by currency', SD_TEXT_DOMAIN ).'</label>
											<label><input type="checkbox" onclick="sd_load_report(this, \'sales_by_product\', \''.esc_js(__( 'Sales by product', SD_TEXT_DOMAIN )).'\', \'product_name\', \'Bar\', \'post_title\', \'sum\');" /> '.__( 'Sales by product', SD_TEXT_DOMAIN ).'</label>
										</div>';
								}
							?>
						    <div id="charts_content" >
								<div id="sales_by_country"></div>
								<div id="sales_by_currency"></div>
								<div id="sales_by_product"></div>
							</div>
							<?php
							if(!empty($message_list))
							{
								print '<div class="sd-mssg" style="margin-top: 20px;margin-bottom: 20px;"><ul>'.$message_list.'</ul></div>';
							}
							?>
							<div class="sd-section-title"><?php _e( 'Products List', SD_TEXT_DOMAIN ); ?></div>
							<table class="form-table" style="border-bottom:1px solid #CCC;margin-bottom:10px;">
								<THEAD>
									<TR style="border-bottom:1px solid #CCC;">
								<?php
									foreach( $_table_header as $_header )
									{
										print "<TH>{$_header}</TH>";
									}
								?>
									</TR>
								</THEAD>
								<TBODY>
								<?php
								$totals = array('UNDEFINED'=>0);
                                if(count($purchase_list)){
									$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
									$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

									foreach($purchase_list as $purchase){
										if( $group_by == 'no_group' )
										{

											if( $to_display == 'sales' )
											{
												if(preg_match('/mc_currency=([^\s]*)/', $purchase->paypal_data, $matches)){
													$currency = strtoupper($matches[1]);
													if(!isset($totals[$currency])) $totals[$currency] = $purchase->amount;
														else $totals[$currency] += $purchase->amount;
												}else{
													$currency = '';
													$totals['UNDEFINED'] += $purchase->amount;
												}
												$post_title_tmp = trim( $purchase->post_title );
												echo '
													<TR>
														<TD>'.$purchase->date.'</TD>
														<TD><a href="'.get_permalink($purchase->ID).'" target="_blank">'.( ( empty( $post_title_tmp ) ) ? 'Id_'.$purchase->ID : sd_strip_tags($purchase->post_title) ).' (x '.$purchase->quantity.')</a></TD>
														<TD>'.sd_strip_tags($purchase->email).'</TD>
														<TD>'.sd_strip_tags($purchase->amount).'</TD>
														<TD>'.sd_strip_tags($currency).'</TD>
														<TD><a href="'.esc_url($dlurl.'purchase_id='.$purchase->purchase_id).'" target="_blank">Download Link</a></TD>
														<TD class="sd-sales-report-actions">
															<input type="button" class="button-primary" onclick="sd_delete_purchase('.esc_js($purchase->id).');" value="Delete">
															<input type="button" class="button-primary" onclick="sd_resend_email('.esc_js($purchase->id).');" value="Resend Download Link">
															<input type="button" class="button-primary" onclick="sd_reset_purchase('.esc_js($purchase->id).');" value="Reset Time and Downloads">
															<input type="button" class="button-primary" onclick="sd_show_purchase('.esc_js($purchase->id).');" value="PayPal Info">
														</TD>
													</TR>
												';
											}elseif( $to_display == 'amount' ){
												echo '
													<TR>
														<TD><a href="'.get_permalink($purchase->ID).'" target="_blank">'.sd_strip_tags($purchase->post_title).'</a></TD>
														<TD>'.(round( $purchase->purchase_count*100 )/100).'</TD>
														<TD>'.(round( $purchase->purchase_total*100)/100).'</TD>
													</TR>
												';
											}else{
												echo '
													<TR>
														<TD><a href="'.get_permalink($purchase->ID).'" target="_blank">'.sd_strip_tags($purchase->post_title).'</a></TD>
														<TD>'.$purchase->purchase_average.'</TD>
														<TD>'.(round($purchase->purchase_total*100)/100).'</TD>
													</TR>
												';
											}
										}
										else
										{

											if( $to_display == 'sales' ){
												echo '
														<TR>
															<TD><a href="'.get_term_link($purchase->term_slug, $group_by ).'" target="_blank">'.sd_strip_tags($purchase->term_name).'</a></TD>
															<TD>'.(round( $purchase->purchase_total*100)/100).'</TD>
														</TR>
													';
											}elseif(  $to_display == 'amount'  ){
												echo '
														<TR>
															<TD><a href="'.get_term_link($purchase->term_slug, $group_by ).'" target="_blank">'.sd_strip_tags($purchase->term_name).'</a></TD>
															<TD>'.(round( $purchase->purchase_count*100)/100).'</TD>
															<TD>'.(round( $purchase->purchase_total*100)/100).'</TD>
														</TR>
													';
											}else{
												echo '
														<TR>
															<TD><a href="'.get_term_link($purchase->term_slug, $group_by ).'" target="_blank">'.sd_strip_tags($purchase->term_name).'</a></TD>
															<TD>'.$purchase->purchase_average.'</TD>
															<TD>'.(round( $purchase->purchase_total*100)/100).'</TD>
														</TR>
													';
											}
										}
									}
								}else{
									echo '
										<TR>
											<TD COLSPAN="7">
												'.__('There are not sales registered with those filter options', SD_TEXT_DOMAIN).'
											</TD>
										</TR>
									';
								}
								?>
								</TBODY>
							</table>

							<?php
								if(count($totals) > 1 || $totals['UNDEFINED']){
							?>
									<table style="border: 1px solid #CCC;">
										<TR><TD COLSPAN="2" style="border-bottom:1px solid #CCC;">TOTALS</TD></TR>
										<TR><TD style="border-bottom:1px solid #CCC;">CURRENCY</TD><TD style="border-bottom:1px solid #CCC;">AMOUNT</TD></TR>
									<?php
										foreach($totals as $currency=>$amount)
											if($amount)
												print "<TR><TD><b>{$currency}</b></TD><TD>{$amount}</TD></TR>";
									?>
									</table>
							<?php
								}
							?>
						</div>
					</div>
					</form>
<?php
				break;
			}
		} // End settings_page

/** LOADING PUBLIC OR ADMINSITRATION RESOURCES **/

		/**
		* Load public scripts and styles
		*/
		function public_resources(){
			wp_enqueue_script('jquery');
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_script( 'wp-mediaelement' );
			wp_enqueue_style('sd-buttons', plugin_dir_url(__FILE__).'sd-styles/sd-buttons.css');
			wp_enqueue_style('sd-style', plugin_dir_url(__FILE__).'sd-styles/sd-public.css');
            wp_enqueue_script('sd-media-script', plugin_dir_url(__FILE__).'sd-script/sd-public.js', array('wp-mediaelement'), null);
            // Load resources of layout
			if( !empty( $this->layout) )
			{
				if( !empty( $this->layout[ 'style_file' ] ) ) wp_enqueue_style('sd-css-layout', plugin_dir_url(__FILE__).$this->layout[ 'style_file' ] , array( 'sd-style' ) );
				if( !empty( $this->layout[ 'script_file' ] ) ) wp_enqueue_script('sd-js-layout', plugin_dir_url(__FILE__).$this->layout[ 'script_file' ] , array( 'sd-media-script' ), false);
			}

			wp_localize_script( 'sd-media-script',
								'sd_global',
								array(
									'url' => esc_url_raw(SD_URL),
									'hurl' => esc_url_raw(sd_complete_url(SD_H_URL)),
									'texts' => array(
										'close_demo' => __('close', SD_TEXT_DOMAIN),
										'download_demo' => __('download file', SD_TEXT_DOMAIN),
										'plugin_fault' => __('The Object to display the demo file is not enabled in your browser. CLICK HERE to download the demo file', SD_TEXT_DOMAIN),
									)
								)
							);
		} // End public_resources

		/**
		* Load admin scripts and styles
		*/
		function admin_resources($hook){
			global $post;

			if(strpos($hook, "sell-downloads") !== false){
                if(function_exists('wp_enqueue_media')) wp_enqueue_media();
				wp_enqueue_style('sd-buttons', plugin_dir_url(__FILE__).'sd-styles/sd-buttons.css');
				wp_enqueue_script('sd-chart-script', plugin_dir_url(__FILE__).'sd-script/Chart.min.js', array( 'jquery' ) );
				wp_enqueue_script('sd-admin-script', plugin_dir_url(__FILE__).'sd-script/sd-admin.js', array('jquery','sd-chart-script'), null, true);
                wp_enqueue_style('sd-admin-style', plugin_dir_url(__FILE__).'sd-styles/sd-admin.css');
				wp_localize_script('sd-admin-script', 'sd_global', array( 'aurl' => esc_url_raw(admin_url() )));
                wp_localize_script('sd-admin-script', 'sell_downloads', array('cover' => get_option('sd_pp_cover_size', 'medium')));
			}

			if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'index.php') {
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('sd-admin-script', plugin_dir_url(__FILE__).'sd-script/sd-admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'media-upload'), null, true);

				if( isset( $post ) && $post->post_type == "sd_product"){
					// Scripts and styles required for metaboxs
					wp_enqueue_style('sd-admin-style', plugin_dir_url(__FILE__).'sd-styles/sd-admin.css');
					wp_localize_script('sd-admin-script', 'sell_downloads', array('post_id' => $post->ID, 'cover' => get_option('sd_pp_cover_size', 'medium')));
					wp_enqueue_media();
				}else{
					// Scripts required for sell downloads insertion
					wp_enqueue_style('wp-jquery-ui-dialog');

					// Set the variables for insertion dialog
					$tags = '';
					// Load file types
					$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));

                    $tags .= '<div title="'.esc_attr(__('Insert Sell Downloads', SD_TEXT_DOMAIN)).'"><div style="padding:20px;">';
					$tags .= '<div>'.__('Columns:', SD_TEXT_DOMAIN).' <br /><input type="text" name="columns" id="columns" style="width:100%" value="1" /></div>';

					$tags .= '<div>'.__('Filter results by file type:', SD_TEXT_DOMAIN).'<br /><select id="type" name="type" style="width:100%"><option value="all">'.__('All file types', SD_TEXT_DOMAIN).'</option>';
					foreach($type_list as $type){
							$tags .= '<option value="'.esc_attr($type->term_id).'">'.sd_strip_tags($type->name,true).'</option>';
					}
					$tags .= '</select></div>';
					$tags .= '</div></div>';

					// Set the variables for insertion dialog
					$tags_p  = '<div title="'.esc_attr(__('Insert a Product', SD_TEXT_DOMAIN)).'"><div style="padding:20px;">';
					$tags_p .= '<div>'.__('Enter the product ID:', SD_TEXT_DOMAIN).'<br /><input id="product_id" name="product_id" style="width:100%" /></div>';
					$tags_p .= '</div></div>';

					wp_localize_script('sd-admin-script', 'sell_downloads', array('tags' => $tags, 'tags_p' => $tags_p));
				}
			}
		} // End admin_resources


/** LOADING SELL DOWNLOADS AND ITEMS ON WORDPRESS SECTIONS **/
        /**
		* Replace the sell_downloads_product shortcode with correct item
		*
		*/
		function load_store_product( $atts ){
            extract( shortcode_atts( array('id' => '', 'layout' => 'store'), $atts ) );
            $r  = '';
            $id = @intval(trim($id));
			$layout = (in_array($layout, array('store','single','multiple'))) ? $layout : 'store';

            if(!empty($id)){
                $p = get_post($id);
                if( !empty( $p ) ){
                    if( $p->post_type == "sd_product" ){
                        $obj = new SDProduct( $p->ID );
                    }

                    if( isset( $obj ) ){
                        $tpl = new sell_downloads_tpleng( dirname(__FILE__).'/sd-templates/', 'comment' );
                        $r = $obj->display_content( $layout, $tpl, 'return' );
                    }
                }
            }
            return $r;
        } // End load_store_product

		/**
		* Replace the sell_downloads shortcode with correct items
		*
		*/
		function load_store($atts, $content, $tag){
			global $wpdb;

			$page_id = 'sd_page_'.get_the_ID();

            if( !isset( $GLOBALS[SD_SESSION_NAME][ $page_id ] ) ) $GLOBALS[SD_SESSION_NAME][ $page_id ] = array();

			// Generated sell downloads
			$sell_downloads = "";
			$page_links = "";
			$header = "";

			// Extract the sell downloads attributes
			extract(shortcode_atts(array(
					'type'		=> 'all',
					'columns'  	=> 1,
					'category'  => 'all',
					'exclude'	=> ''
				), $atts)
			);

			$type 		= trim($type);
			$category 	= trim($category);

			if ( empty( $type ) ) 	  $type 	= 'all';
			if ( empty( $category ) ) $category = 'all';

			// Extract query_string variables correcting sell downloads attributes
			if(isset($_REQUEST['filter_by_type'])){
				$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_type'] = sanitize_text_field($_REQUEST['filter_by_type']);
			}

			if(isset($_REQUEST['filter_by_category'])){
				$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_category'] = sanitize_text_field($_REQUEST['filter_by_category']);
			}

			if(isset($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_type'])){
				$type = $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_type'];
			}


			if(isset($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_category'])){
				$category = $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_category'];
			}

			if(isset($_REQUEST['ordering_by']) && in_array($_REQUEST['ordering_by'], ['popularity', 'price_high_low', 'price_low_high', 'post_title', 'post_date'])){
				$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] = $_REQUEST['ordering_by'];
			}elseif( !isset($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering']) ){
				$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] = (isset($atts['order_by']) && in_array($atts['order_by'], ['popularity', 'price_high_low', 'price_low_high', 'post_title', 'post_date'])) ? $atts[ 'order_by' ] : "post_date";
			}

			// Extract info from sell downloads options
			$allow_filter_by_type = ( isset( $atts[ 'filter_by_type' ] ) ) ? @intval(trim($atts[ 'filter_by_type' ])) : get_option('sd_filter_by_type', SD_FILTER_BY_TYPE);

 			$allow_filter_by_category = ( isset( $atts[ 'filter_by_category' ] ) ) ? @intval(trim($atts[ 'filter_by_category' ])) : get_option('sd_filter_by_category', SD_FILTER_BY_CATEGORY);

 			// Items per page
			$items_page 			= max(@intval(get_option('sd_items_page', SD_ITEMS_PAGE)), 1);
			// Display pagination
			$items_page_selector 	= get_option('sd_items_page_selector', SD_ITEMS_PAGE_SELECTOR);

			// Query clauses
			$_select 	= "SELECT DISTINCT posts.ID, posts.post_type";
			$_from 		= "FROM ".$wpdb->prefix."posts as posts,".$wpdb->prefix.SDDB_POST_DATA." as posts_data";
			$_where 	= "WHERE posts.ID = posts_data.id AND posts.post_status='publish'";

			// Exclude the products passed as parameters
			$exclude = preg_replace('/[^\d\,]/', '', $exclude);
			$exclude = trim($exclude, ',');
			if(!empty($exclude)) $_where .=" AND posts.ID NOT IN (".$exclude.")";

			$_order_by 	= "ORDER BY ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == "post_title" || $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == "post_date" ) ? "posts" : "posts_data").".".(
				(
					$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'price_high_low' ||
					$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'price_low_high'
				) ? 'price' : $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering']
			)." ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'popularity' || $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'post_date' || $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == "price_high_low") ? "DESC" : "ASC");
			$_limit 	= "";

			if($type !== 'all' || $category !== 'all' ){
				// Load the taxonomy tables
				if( $type !== 'all')
				{
					$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy, ".$wpdb->prefix."term_relationships as term_relationships, ".$wpdb->prefix."terms as terms";

					$_where .= " AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id AND term_relationships.object_id=posts.ID AND taxonomy.term_id=terms.term_id ";


					// Search for types assigned directly to the posts
					$_where .= "AND taxonomy.taxonomy='sd_type' AND ";

					if(is_numeric($type))
						$_where .= $wpdb->prepare("terms.term_id=%d", $type);
					else
						$_where .= $wpdb->prepare("terms.slug=%s", $type);
				}


				if( $category !== 'all')
				{
					$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy1, ".$wpdb->prefix."term_relationships as term_relationships1, ".$wpdb->prefix."terms as terms1";

					$_where .= " AND taxonomy1.term_taxonomy_id=term_relationships1.term_taxonomy_id AND term_relationships1.object_id=posts.ID AND taxonomy1.term_id=terms1.term_id ";

					// Search for types assigned directly to the posts
					$_where .= "AND taxonomy1.taxonomy='sd_category' AND ";

					if(is_numeric($category))
						$_where .= $wpdb->prepare("terms1.term_id=%d", $category);
					else
						$_where .= $wpdb->prepare("terms1.slug=%s", $category);
				}
				// End taxonomies
			}

			$_where .= " AND post_type='sd_product'";

			// Create pagination section
			if($items_page_selector && $items_page){
				// Checking for page parameter or get page from session variables
				// Clear the page number if filtering option change
				if(isset($_REQUEST['filter_by_type']) || isset( $_REQUEST[ 'filter_by_category' ] )){
					$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number'] = 0;
				}elseif(isset($_GET['page_number'])){
					$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number'] = @intval($_GET['page_number']);
				}elseif(!isset($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number'])){
					$GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number'] = 0;
				}

				$_limit = $wpdb->prepare("LIMIT %d, %d", $GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number']*$items_page, $items_page);

				// Get total records for pagination
				$query = "SELECT COUNT(DISTINCT posts.ID) ".$_from." ".$_where;
				$total = $wpdb->get_var($query);
				$total_pages = ceil($total/max($items_page,1));

				if($total_pages > 1){

					// Make page links
					$page_links .= "<DIV class='sell-downloads-pagination'>";
					$page_href = '?'.((!empty($_SERVER['QUERY_STRING'])) ? preg_replace('/(&)?page_number=\d+/', '', sanitize_text_field($_SERVER['QUERY_STRING'])).'&' : '');


					for($i=0, $h = $total_pages; $i < $h; $i++){
						if($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_page_number'] == $i)
							$page_links .= "<span class='page-selected'>".($i+1)."</span> ";
						else
							$page_links .= "<a class='page-link' href='".esc_url($page_href."page_number=".$i)."'>".($i+1)."</a> ";
					}
					$page_links .= "</DIV>";
				}
			}

			// Create items section
			$query = $_select." ".$_from." ".$_where." ".$_order_by." ".$_limit;
			$results = $wpdb->get_results($query);
			$tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');

            $width = floor(100/max($columns, 1));
			$sell_downloads .= "<div class='sell-downloads-items'>";
			$item_counter = 0;
			foreach($results as $result){
				$obj = new SDProduct($result->ID);
				$sell_downloads .= "<div style='width:".esc_attr($width)."%;' class='sell-downloads-item'>".$obj->display_content('store', $tpl, 'return')."</div>";
				$item_counter++;
				if($item_counter % $columns == 0)
					$sell_downloads .= "<div style='clear:both;'></div>";
			}
			$sell_downloads .= "<div style='clear:both;'></div>";
			$sell_downloads .= "</div>";
			$header .= "
						<form method='post'>
						<div class='sell-downloads-header'>
						";
			// Create filter section
			if($allow_filter_by_type || $allow_filter_by_category ){
				$header .= "<div class='sell-downloads-filters'><span>".__('Filter by', SD_TEXT_DOMAIN)."</span>";
                // List all file types
				if($allow_filter_by_type){
					$header .= "<span>".__(' file type: ', SD_TEXT_DOMAIN).
							"<select aria-label='".esc_attr(__('File type', SD_TEXT_DOMAIN))."' id='filter_by_type' name='filter_by_type' onchange='this.form.submit();'>
							<option value='all'>".__('All file types', SD_TEXT_DOMAIN)."</option>
							";
					$types = get_terms("sd_type");
					foreach($types as $type_item){
                    	$header .= "<option value='".esc_attr($type_item->slug)."' ".(($type == $type_item->slug || $type == $type_item->term_id) ? "SELECTED" : "").">".sd_strip_tags($type_item->name,true)."</option>";
					}
					$header .= "</select></span>";
				}

				// List all categories
				if($allow_filter_by_category){
					$header .= "<span>".__(' category: ', SD_TEXT_DOMAIN).
							"<select aria-label='".esc_attr(__('Category', SD_TEXT_DOMAIN))."' id='filter_by_category' name='filter_by_category' onchange='this.form.submit();'>
							<option value='all'>".__('All categories', SD_TEXT_DOMAIN)."</option>
							";
					$categories = get_terms("sd_category");
					foreach($categories as $category_item){
                    	$header .= "<option value='".esc_attr($category_item->slug)."' ".(($category == $category_item->slug || $category == $category_item->term_id) ? "SELECTED" : "").">".sd_strip_tags($category_item->name,true)."</option>";
					}
					$header .= "</select></span>";
				}

				$header .="</div>";
			}

			// Create order filter
            if( !isset( $atts[ 'show_order_by' ] ) || $atts[ 'show_order_by' ] * 1 )
            {
                $header .= "<div class='sell-downloads-ordering'>".
                                __('Order by: ', SD_TEXT_DOMAIN).
                                "<select aria-label='".esc_attr(__('Order by', SD_TEXT_DOMAIN))."' id='ordering_by' name='ordering_by' onchange='this.form.submit();'>
                                    <option value='post_date' ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'post_date') ? "SELECTED" : "").">".__('Newest', SD_TEXT_DOMAIN)."</option>
                                    <option value='post_title' ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'post_title') ? "SELECTED" : "").">".__('Name', SD_TEXT_DOMAIN)."</option>
                                    <option value='popularity' ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'popularity') ? "SELECTED" : "").">".__('Popularity', SD_TEXT_DOMAIN)."</option>
                                    <option value='price_low_high' ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'price_low_high') ? "SELECTED" : "").">".__('Price: Low to High', SD_TEXT_DOMAIN)."</option>
									<option value='price_high_low' ".(($GLOBALS[SD_SESSION_NAME][ $page_id ]['sd_ordering'] == 'price_high_low') ? "SELECTED" : "").">".__('Price: High to Low', SD_TEXT_DOMAIN)."</option>
                                </select>
                            </div>";
            }

            $header .= "<div style='clear:both;'></div>
						</div>
						</form>
						";

            return $header.$sell_downloads.$page_links;
		} // End load_store

/** MODIFY CONTENT OF POSTS LOADED **/

		/*
		* Load the templates for products display
		*/
		function load_templates(){
            add_filter('the_content', array(&$this, 'display_content'), 1 );
		} // End load_templates

		/**
		* Display content of products through templates
		*/
		function display_content($content){
			global $post;
			if(
				/* in_the_loop() &&  */
				$post &&
				$post->post_type == 'sd_product'
			)
			{
				remove_filter( 'the_content', 'wpautop' );
                remove_filter( 'the_excerpt', 'wpautop' );
                remove_filter( 'comment_text', 'wpautop', 30 );
                $tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');
				$product = new SDProduct($post->ID);
				return $product->display_content(((is_singular()) ? 'single' : 'multiple'), $tpl, 'return');
			}else{
				return $content;
			}
		} // End display_content


		/**
		* Set a media button for sell downloads insertion
		*/
		function set_sell_downloads_button(){
			global $post;

			if( isset( $post ) && $post->post_type != 'sd_product')
            {
                print '<a href="javascript:open_insertion_sell_downloads_window();" title="'.__('Insert Sell Downloads').'"><img src="'.esc_url(SD_CORE_IMAGES_URL.'/sell-downloads-icon.png').'" alt="'.esc_attr(__('Insert Sell Downloads')).'" /></a>';

                print '<a href="javascript:open_insertion_sell_downloads_product_window();" title="'.esc_attr(__('Insert a product')).'"><img src="'.esc_url(SD_CORE_IMAGES_URL.'/sell-downloads-product-icon.png').'" alt="'.esc_attr(__('Insert a product')).'" /></a>';
            }
		} // End set_sell_downloads_button


		/**
		*	Check for post to delete and remove the metadata saved on additional metadata tables
		*/
		function delete_post($pid){
			global $wpdb;
			return  $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix.SDDB_POST_DATA." WHERE id=%d;",$pid));
		} // End delete_post

		/******* SEARCHING METHODS *******/

		function custom_search_where($where)
		{
			global $wpdb;
			if( is_search() && get_search_query() )
			{
				$q = "%".get_search_query()."%";
				$where .= $wpdb->prepare(" OR ((t.name LIKE %s OR t.slug LIKE %s) AND tt.taxonomy='sd_type' AND {$wpdb->posts}.post_status = 'publish')", $q, $q);
			}
			return $where;
		}

		function custom_search_join($join)
		{
			global $wpdb;
			if( is_search() && get_search_query() )
			{
				$join .= " LEFT JOIN ({$wpdb->term_relationships} tr INNER JOIN ({$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id) ON tr.term_taxonomy_id = tt.term_taxonomy_id) ON {$wpdb->posts}.ID = tr.object_id ";
			}
			return $join;
		}

		function custom_search_groupby($groupby)
		{
			global $wpdb;

			// we need to group on post ID
			$groupby_id = "{$wpdb->posts}.ID";
			if( !is_search() || strpos( $groupby, $groupby_id ) !== false || !get_search_query() )
			{
				return $groupby;
			}
			// groupby was empty, use ours
			if( !strlen( trim( $groupby ) ) )
			{
				return $groupby_id;
			}
			// wasn't empty, append ours
			return $groupby.", ".$groupby_id;
		}

		public static function troubleshoot($option)
		{
			if(!is_admin())
			{
				// Solves a conflict caused by the "Speed Booster Pack" plugin
				if(is_array($option) && isset($option['jquery_to_footer'])) unset($option['jquery_to_footer']);
				if(is_array($option) && isset($option['sbp_css_async'])) unset($option['sbp_css_async']);
			}
			return $option;
		} // End troubleshoot

		/**
		 * Prevent conflicts with third party plugins that manage the websites cache
		 */
		private function _reject_cache_uris()
		{
			if(is_admin()) return;
			// For WP Super Cache plugin
			global 	$cache_rejected_uri;
			if(!empty($cache_rejected_uri)) $cache_rejected_uri[] = 'sd-download-page';
		} // End _reject_cache_uris

	} // End SellDownloads class

	// Initialize SellDownloads class
define('SD_SESSION_NAME', 'sd_session_20200815' );
if(!function_exists('sd_start_session'))
{
	function sd_start_session()
	{
		$GLOBALS[SD_SESSION_NAME] = array();
		$set_cookie = true;
		if(isset($_COOKIE[SD_SESSION_NAME]))
		{
			$GLOBALS['SD_SESSION_ID'] = $_COOKIE[SD_SESSION_NAME];
			$_stored_session = get_transient($GLOBALS['SD_SESSION_ID']);
			if($_stored_session !== false)
			{
				$GLOBALS[SD_SESSION_NAME] = $_stored_session;
				$set_cookie = false;
			}
		}

		if($set_cookie)
		{
			$GLOBALS['SD_SESSION_ID'] = uniqid('', true);
			if(!headers_sent()) @setcookie( SD_SESSION_NAME, $GLOBALS['SD_SESSION_ID'], 0, '/' );
		}
	}
	sd_start_session();
}

if(!function_exists('sd_session_dump'))
{
	function sd_session_dump()
	{
        if(count($GLOBALS[SD_SESSION_NAME]))
        {
            set_transient( $GLOBALS['SD_SESSION_ID'], $GLOBALS[SD_SESSION_NAME], 12*60*60 );
        }
		delete_expired_transients(true);
	}
	add_action('shutdown', 'sd_session_dump', 99, 0);
}

	$GLOBALS['sell_downloads'] = new SellDownloads;

	register_activation_hook( __FILE__, array( &$GLOBALS[ 'sell_downloads' ], 'register' ) );
	add_action( 'activated_plugin', array( &$GLOBALS[ 'sell_downloads' ], 'redirect_to_settings' ), 10, 2 );
	add_action( 'wpmu_new_blog', array( &$GLOBALS[ 'sell_downloads' ], 'install_new_blog' ), 10, 6 );
} // Class exists check