<?php

if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

	// Errors management
	$sd_errors = array();
	function sell_downloads_setError($error_text){
		global $sd_errors;
		$sd_errors[] = __($error_text, SD_TEXT_DOMAIN);
	}

    function sd_sanitize_permalink( $value )
    {
        global $wpdb;

        $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

        if ( is_wp_error( $value ) ) $value = '';

        $value = esc_url_raw( trim( $value ) );
        $value = str_replace( 'http://', '', $value );
        return untrailingslashit( $value );
    } // End sd_sanitize_permalink

	function sd_complete_url($url)
	{
		if(get_option('sd_prevent_cache',false))
			$url = add_query_arg('_sdr', time(), remove_query_arg('_sdr', $url));
		return $url;

	} // End sd_complete_url

	function sd_strip_tags($vs, $esc_html = false)
	{
		$allowed_tags = "<a><abbr><audio><b><blockquote><br><cite><code><del><dd><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><q><source><span><strike><strong><table><tbody><theader><tfooter><tr><td><th><ul><video>";

		if(is_array($vs))
		{
			foreach($vs as $i=>$v)
				if(is_string($v))
				{
					$v = wp_check_invalid_utf8( $v, true );
					$v = strip_tags($v,$allowed_tags);
					$vs[$i] = ($esc_html)?esc_html($v):$v;
				}
		}
		elseif(is_string($vs))
		{
			$vs = wp_check_invalid_utf8( $vs, true );
			$vs = strip_tags($vs,$allowed_tags);
			if($esc_html) $vs = esc_html($vs);
		}
		return $vs;
	}

	if( !function_exists( 'sd_copy' ) )
	{
		function sd_copy( $from, $to )
		{
			try
			{
				if( filesize( $from ) < 104857600 ) return copy($from, $to);

				# 5 meg at a time
				$buffer_size = 5242880;
				$ret = 0;
				$fin = fopen($from, "rb");
				$fout = fopen($to, "w");
				while(!feof($fin)) {
					$ret += fwrite($fout, fread($fin, $buffer_size));
				}
				fclose($fin);
				fclose($fout);
			}
			catch( Exception $err )
			{
				return false;
			}
			return true;
		}
	}

	if( !function_exists( 'sd_mime_type_accepted' ) )
	{
		function sd_mime_type_accepted( $file )
		{
			$mime = wp_check_filetype( basename( $file ) );
			if(
				$mime[ 'type' ] == false ||
				preg_match( '/\b(php|asp|aspx|cgi|pl|perl|exe)\b/i', $mime[ 'type' ].' '.$mime[ 'ext' ] )
			)
			{
				return false;
			}
			return true;
		}
	}

	if (!function_exists('sd_mime_content_type')) {
		function sd_mime_content_type($filename) {

			$file_parts = explode('.', $filename);
			$idx = end($file_parts);
			$idx = strtolower($idx);

			$mimet = array(	'ai' =>'application/postscript',
				'3gp' =>'audio/3gpp',
				'flv' =>'video/x-flv',
				'aif' =>'audio/x-aiff',
				'aifc' =>'audio/x-aiff',
				'aiff' =>'audio/x-aiff',
				'asc' =>'text/plain',
				'atom' =>'application/atom+xml',
				'avi' =>'video/x-msvideo',
				'bcpio' =>'application/x-bcpio',
				'bmp' =>'image/bmp',
				'cdf' =>'application/x-netcdf',
				'cgm' =>'image/cgm',
				'cpio' =>'application/x-cpio',
				'cpt' =>'application/mac-compactpro',
				'crl' =>'application/x-pkcs7-crl',
				'crt' =>'application/x-x509-ca-cert',
				'csh' =>'application/x-csh',
				'css' =>'text/css',
				'dcr' =>'application/x-director',
				'dir' =>'application/x-director',
				'djv' =>'image/vnd.djvu',
				'djvu' =>'image/vnd.djvu',
				'doc' =>'application/msword',
				'dtd' =>'application/xml-dtd',
				'dvi' =>'application/x-dvi',
				'dxr' =>'application/x-director',
				'eps' =>'application/postscript',
				'etx' =>'text/x-setext',
				'ez' =>'application/andrew-inset',
				'gif' =>'image/gif',
				'gram' =>'application/srgs',
				'grxml' =>'application/srgs+xml',
				'gtar' =>'application/x-gtar',
				'hdf' =>'application/x-hdf',
				'hqx' =>'application/mac-binhex40',
				'html' =>'text/html',
				'html' =>'text/html',
				'ice' =>'x-conference/x-cooltalk',
				'ico' =>'image/x-icon',
				'ics' =>'text/calendar',
				'ief' =>'image/ief',
				'ifb' =>'text/calendar',
				'iges' =>'model/iges',
				'igs' =>'model/iges',
				'jpe' =>'image/jpeg',
				'jpeg' =>'image/jpeg',
				'jpg' =>'image/jpeg',
				'js' =>'application/x-javascript',
				'kar' =>'audio/midi',
				'latex' =>'application/x-latex',
				'm3u' =>'audio/x-mpegurl',
				'man' =>'application/x-troff-man',
				'mathml' =>'application/mathml+xml',
				'me' =>'application/x-troff-me',
				'mesh' =>'model/mesh',
				'm4a' =>'audio/x-m4a',
				'mid' =>'audio/midi',
				'midi' =>'audio/midi',
				'mif' =>'application/vnd.mif',
				'mov' =>'video/quicktime',
				'movie' =>'video/x-sgi-movie',
				'mp2' =>'audio/mpeg',
				'mp3' =>'audio/mpeg',
				'mp4' =>'video/mp4',
				'm4v' =>'video/x-m4v',
				'mpe' =>'video/mpeg',
				'mpeg' =>'video/mpeg',
				'mpg' =>'video/mpeg',
				'mpga' =>'audio/mpeg',
				'ms' =>'application/x-troff-ms',
				'msh' =>'model/mesh',
				'mxu m4u' =>'video/vnd.mpegurl',
				'nc' =>'application/x-netcdf',
				'oda' =>'application/oda',
				'ogg' =>'application/ogg',
				'pbm' =>'image/x-portable-bitmap',
				'pdb' =>'chemical/x-pdb',
				'pdf' =>'application/pdf',
				'pgm' =>'image/x-portable-graymap',
				'pgn' =>'application/x-chess-pgn',
				'php' =>'application/x-httpd-php',
				'php4' =>'application/x-httpd-php',
				'php3' =>'application/x-httpd-php',
				'phtml' =>'application/x-httpd-php',
				'phps' =>'application/x-httpd-php-source',
				'png' =>'image/png',
				'pnm' =>'image/x-portable-anymap',
				'ppm' =>'image/x-portable-pixmap',
				'ppt' =>'application/vnd.ms-powerpoint',
				'ps' =>'application/postscript',
				'qt' =>'video/quicktime',
				'ra' =>'audio/x-pn-realaudio',
				'ram' =>'audio/x-pn-realaudio',
				'ras' =>'image/x-cmu-raster',
				'rdf' =>'application/rdf+xml',
				'rgb' =>'image/x-rgb',
				'rm' =>'application/vnd.rn-realmedia',
				'roff' =>'application/x-troff',
				'rtf' =>'text/rtf',
				'rtx' =>'text/richtext',
				'sgm' =>'text/sgml',
				'sgml' =>'text/sgml',
				'sh' =>'application/x-sh',
				'shar' =>'application/x-shar',
				'shtml' =>'text/html',
				'silo' =>'model/mesh',
				'sit' =>'application/x-stuffit',
				'skd' =>'application/x-koan',
				'skm' =>'application/x-koan',
				'skp' =>'application/x-koan',
				'skt' =>'application/x-koan',
				'smi' =>'application/smil',
				'smil' =>'application/smil',
				'snd' =>'audio/basic',
				'spl' =>'application/x-futuresplash',
				'src' =>'application/x-wais-source',
				'sv4cpio' =>'application/x-sv4cpio',
				'sv4crc' =>'application/x-sv4crc',
				'svg' =>'image/svg+xml',
				'swf' =>'application/x-shockwave-flash',
				't' =>'application/x-troff',
				'tar' =>'application/x-tar',
				'tcl' =>'application/x-tcl',
				'tex' =>'application/x-tex',
				'texi' =>'application/x-texinfo',
				'texinfo' =>'application/x-texinfo',
				'tgz' =>'application/x-tar',
				'tif' =>'image/tiff',
				'tiff' =>'image/tiff',
				'tr' =>'application/x-troff',
				'tsv' =>'text/tab-separated-values',
				'txt' =>'text/plain',
				'ustar' =>'application/x-ustar',
				'vcd' =>'application/x-cdlink',
				'vrml' =>'model/vrml',
				'vxml' =>'application/voicexml+xml',
				'wav' =>'audio/x-wav',
				'wbmp' =>'image/vnd.wap.wbmp',
				'wbxml' =>'application/vnd.wap.wbxml',
				'wml' =>'text/vnd.wap.wml',
				'wmlc' =>'application/vnd.wap.wmlc',
				'wmlc' =>'application/vnd.wap.wmlc',
				'wmls' =>'text/vnd.wap.wmlscript',
				'wmlsc' =>'application/vnd.wap.wmlscriptc',
				'wmlsc' =>'application/vnd.wap.wmlscriptc',
				'wrl' =>'model/vrml',
				'xbm' =>'image/x-xbitmap',
				'xht' =>'application/xhtml+xml',
				'xhtml' =>'application/xhtml+xml',
				'xls' =>'application/vnd.ms-excel',
				'xml xsl' =>'application/xml',
				'xpm' =>'image/x-xpixmap',
				'xslt' =>'application/xslt+xml',
				'xul' =>'application/vnd.mozilla.xul+xml',
				'xwd' =>'image/x-xwindowdump',
				'xyz' =>'chemical/x-xyz',
				'zip' =>'application/zip'
			);

			if (isset( $mimet[$idx] )) {
				return $mimet[$idx];
			} else {
				return 'application/octet-stream';
			}
		}
	}

	// Check if URL is for a local file, and return the relative URL or false
	function sd_is_local( $file ){
		// Easy Check
		if ( defined( 'ABSPATH' ) ) {
			$path_component = parse_url( $file, PHP_URL_PATH );
			$path = rtrim( ABSPATH, '/' ) . '/' . ltrim( $path_component, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		// Complex check
		$file = trim($file);
		$file = str_replace('\\','/', $file);
		$file = preg_replace('/^https?:\/\/(www\.)?/','', $file);

		$site_url = str_replace('\\','/', SD_H_URL);
		$site_url = preg_replace('/^https?:\/\/(www\.)?/','', $site_url);

		$tmp_file = strtolower($file);
		$tmp_site_url = strtolower($site_url);
		if( strpos( $tmp_file, $tmp_site_url ) === false ) return false;

		$sd_url = str_replace('\\','/', SD_URL);
		$sd_url = preg_replace('/^https?:\/\/(www\.)?/','', $sd_url);

		$parts = explode('/', str_ireplace( $site_url, '', $sd_url.'/sd-core' ));
		$file = str_ireplace( $site_url, '', $file );
		$path = '';
		for( $i = 0; $i < count( $parts ); $i++ ){
			$path .= '../';
		}
		$file = urldecode( dirname( __FILE__ ).'/'.$path.$file );
		return file_exists( $file ) ? $file : false;
	}

	if( !function_exists( 'sd_getIP' ) )
	{
		function sd_getIP()
		{
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
			if( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) )
			{
				$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
			}
			elseif( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) )
			{
				$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
			}

			return str_replace( array( ':', '.' ), array( '_', '_' ), $ip );
		}
	}
	// Check downloads permissions
	function sd_check_download_permissions(){
		global $wpdb;

		// Check if download for free or the user is an admin
		if(	!empty( $GLOBALS[SD_SESSION_NAME][ 'sd_download_for_free' ] ) || current_user_can( 'manage_options' ) ) return true;

		// and check the existence of a parameter with the purchase_id
		if( empty( $_REQUEST[ 'purchase_id' ] ) ){
			sell_downloads_setError( 'The purchase id is required' );
			return false;
		}

		if( get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) ){
			// Check if the user has typed the email used to purchase the product
			if( !empty( $_REQUEST[ 'sd_user_email' ] ) ) $GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ] =  sanitize_email($_REQUEST[ 'sd_user_email' ]);

			if( empty( $GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ] ) ){
				sell_downloads_setError( "Please, enter the email address used in products purchasing" );
				return false;
			}
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN TIMESTAMPDIFF(MINUTE, NOW(), date) ELSE TIMESTAMPDIFF(MINUTE, NOW(), checking_date) END AS days, downloads, id FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s AND email=%s ORDER BY checking_date DESC, date DESC', array( sanitize_key($_REQUEST[ 'purchase_id' ]), sanitize_email($GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ]) ) ) );
		}else{
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN TIMESTAMPDIFF(MINUTE, NOW(), date) ELSE TIMESTAMPDIFF(MINUTE, NOW(), checking_date) END AS days, downloads, id FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s ORDER BY checking_date DESC, date DESC', array( sanitize_key($_REQUEST[ 'purchase_id' ]) ) ) );
		}

		if( is_null( $data ) ){
			sell_downloads_setError(
				'<div id="sell_downloads_error_mssg"></div><code style="display:none;"><script>var timeout_text = "'.esc_js(__( 'The store should be processing the purchase. You will be redirected in', SD_TEXT_DOMAIN )).'";</script></code>'
			);
            return false;
		}
		else
		{
			$days = abs($data->days)/1440;
			if( get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK) < $days )
			{
				sell_downloads_setError( 'The download link has expired, please contact to the vendor' );
				return false;
			}
			elseif( get_option('sd_downloads_number', SD_DOWNLOADS_NUMBER) > 0 &&  get_option('sd_downloads_number', SD_DOWNLOADS_NUMBER) <= $data->downloads )
			{
				sell_downloads_setError( 'The number of downloads has reached its limit, please contact to the vendor' );
				return false;
			}
		}
		if( isset( $_REQUEST[ 'f' ] ) && !isset( $GLOBALS[SD_SESSION_NAME][ 'cpsd_donwloads' ] ) )
		{
            $GLOBALS[SD_SESSION_NAME][ 'cpsd_donwloads' ] = 1;
			$wpdb->query( $wpdb->prepare( 'UPDATE '.$wpdb->prefix.SDDB_PURCHASE.' SET downloads=downloads+1 WHERE id=%d', $data->id ) );
		}

		return true;
	} // End sd_check_download_permissions

	// Check if the PHP memory is sufficient
	function sell_downloads_check_memory( $files = array(), $forceLocal = false ){
		$required = 0;

		$m = ini_get( 'memory_limit' );
		$m = trim($m);
		$l = strtolower($m[strlen($m)-1]); // last
		$m = substr($m, 0, -1);
		switch($l) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$m *= 1024;
			case 'm':
				$m *= 1024;
			case 'k':
				$m *= 1024;
		}

		foreach ( $files as $file ){
			$memory_available = $m - memory_get_usage(true);
			if( $forceLocal || ( $relative_path = sd_is_local( $file ) ) !== false ){
				if( $forceLocal )
				{
					$relative_path = dirname( __FILE__ ).'/../sd-downloads/'.$file;
				}

				$required += filesize( $relative_path );
				if( $required >= $memory_available - 100 ) return false;
			}else{
				$response = wp_remote_head( $file );
				if( !is_wp_error( $response ) && $response['response']['code'] == 200 ){
					$required += $response['headers']['content-length'];
					if( $required >= $memory_available - 100 ) return false;
				}else return false;
			}
		}
		return true;
	} // music_store_check_memory

	function sell_downloads_extract_attr_as_str($arr, $attr, $separator){
		$result = '';
		$c = count($arr);
		if($c){
			$t = (array)$arr[0];
			$result .= $t[$attr];
			for($i=1; $i < $c; $i++){
				$t = (array)$arr[$i];
				$result .= $separator.$t[$attr];
			}
		}

		return $result;
	} // End sell_downloads_extract_attr_as_str

	function sell_downloads_get_img_id($url){
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $wpdb->prefix . "posts" . " WHERE guid=%s;", $url ));
		return $attachment[0];
	} // End sell_downloads_get_img_id

	function sell_downloads_make_seed() {
		list($usec, $sec) = explode(' ', microtime());
		return intval( (float) $sec + ((float) $usec * 1000000) );
	}

	function sell_downloads_register_purchase($product_id, $product_quantity, $purchase_id, $email, $amount, $paypal_data){
		global $wpdb;
		return $wpdb->insert(
			$wpdb->prefix.SDDB_PURCHASE,
			array(
				'product_id'  => $product_id,
				'quantity'  => $product_quantity,
				'purchase_id' => $purchase_id,
				'date'		  => date( 'Y-m-d H:i:s'),
				'email'		  => sanitize_email($email),
				'amount'	  => @floatval($amount),
				'paypal_data' => $paypal_data
			),
			array('%d', '%d', '%s', '%s', '%s', '%f', '%s')
		);
	}

	function sd_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = basename($file).'_'.md5($file).'.'.$ext;
		$file_path = SD_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($file_path))
			return $new_file_name;

		if( ( $path = sd_is_local( $file ) ) !== false ){
			if( sd_copy( $path, $file_path) ) return $new_file_name;
		}else{
			$response = wp_remote_get($file, array( 'timeout' => SD_REMOTE_TIMEOUT, 'stream' => true, 'filename' => $file_path ) );
			if( !is_wp_error( $response ) && $response['response']['code'] == 200 ) return $new_file_name;
		}
		return $file;
	} // End sd_copy_download_links

	function sd_remove_download_links(){
		$now = time();
		$dif = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;
		$d = dir(SD_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
			if($entry != '.' && $entry != '..' && $entry != '.htaccess'){
				$file_name = SD_DOWNLOAD.'/'.$entry;
				$date = filemtime($file_name);
				if($now-$date >= $dif){ // Delete file
					@unlink($file_name);
				}
			}
		}
		$d->close();
	} // End sd_remove_download_links

	function sd_product_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	} // End sd_product_title

	function sd_generate_downloads(){
		global $wpdb, $download_links_str, $id;
		$str = '';
		if( sd_check_download_permissions() ){
			if($id){
				sd_remove_download_links();
				$purchase_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE purchase_id=%s", sanitize_key($_GET['purchase_id'])));

				if($purchase_rows){ // Exists the purchase
					$interval = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;

					$urls = array();
					$tmp_arr = array();
					$download_links_str = '';

					foreach($purchase_rows as $purchase){
						$id = $purchase->product_id;

						$_post = get_post($id);
						if(is_null($_post)) return '';
						if($_post->post_type == 'sd_product') $obj = new SDProduct($id);
						else return '';

						$productObj = new stdClass();
						if(isset($obj->file) && !in_array($obj->file, $tmp_arr)){
							$productObj->title = sd_product_title($obj);
							$productObj->link  = str_replace( ' ', '%20', wp_kses_decode_entities( $obj->file ) );
							$urls[] = $productObj;
							$tmp_arr[] = $obj->file;
						}
					}

					if(count($urls)){
						foreach($urls as $url){
							$download_link = sd_copy_download_links($url->link);
							if( $download_link !== $url->link ) $download_link = SD_H_URL.'?sd_action=f-download'.( ( isset( $GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ] ) ) ? '&sd_user_email='.$GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ] : '' ).'&f='.$download_link.( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ?  '&purchase_id='.sanitize_key($_REQUEST[ 'purchase_id' ]) : '' );
							$download_links_str .= '<div> <a href="'.esc_url($download_link).'">'.sd_strip_tags($url->title).'</a></div>';
						}
					}

					if(empty($download_links_str)){
						$download_links_str = __('The list of purchased products is empty', SD_TEXT_DOMAIN);
					}

                    $str .= $download_links_str;
				} // End purchase checking

			}
		}else{
			$error = ( !empty( $_REQUEST[ 'error_mssg' ] ) ) ? '<li>'.sd_strip_tags( $_REQUEST[ 'error_mssg' ] ).'</li>' : '';
			if( (!get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) && !empty($sd_errors)) || !empty( $GLOBALS[SD_SESSION_NAME][ 'sd_user_email' ] ) )
			{
				global $sd_errors;
				if( is_array($sd_errors) )
				{
					foreach( $sd_errors as $error_key => $error_message )
					{
						$error .= '<li>'.sd_strip_tags($error_message).'</li>';
					}
				}
				else
				{
					$error .= '<li>'.sd_strip_tags( $sd_errors ).'</li>';
				}
			}
			$str .= ( !empty( $error ) )  ? '<div class="sd-error-mssg"><ul>'.$error.'</ul></div>' : '';
			if( get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) ){
				$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
				$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).( ( isset( $_REQUEST[ 'purchase_id' ] ) ) ? 'purchase_id='.sanitize_key($_REQUEST[ 'purchase_id' ]) : '' );
				$str .= '
					<form action="'.$dlurl.'" method="POST" >
						<div style="text-align:center;">
							<div>
								'.__( 'Type the email address used to purchase our products', SD_TEXT_DOMAIN ).'
							</div>
							<div>
								<input type="text" name="sd_user_email" /> <input type="submit" value="Get Products" />
							</div>
						</div>
					</form>
				';
			}
		}
		return $str;

	} //sd_generate_downloads

	if(!function_exists('sd_apply_taxes'))
	{
		function sd_apply_taxes($v)
		{
			$sd_tax = get_option('sd_tax', '');
			if(!empty($sd_tax))
			{
				$v *= (1+$sd_tax/100);
			}
			return $v;
		} // End sd_apply_taxes
	}

	function sd_download_file(){
		global $wpdb, $sd_errors;

		if( isset( $_REQUEST[ 'f' ] ) ) $_REQUEST[ 'f' ] = sanitize_text_field(stripslashes($_REQUEST['f']));

		if( isset( $_REQUEST[ 'f' ] ) && sd_check_download_permissions() ){
			$file_name = basename( $_REQUEST[ 'f' ] );
			if( !sd_mime_type_accepted( $file_name ) )
			{
				_e( 'Invalid File Type', SD_TEXT_DOMAIN );
				exit;
			}

			$file = SD_DOWNLOAD.'/'.$file_name;
			if( file_exists( $file ) )
			{
				try
				{
					$file_name = basename( $file );
					$file_name = explode( '_', $file_name )[0];

					header( 'Content-Type: '.sd_mime_content_type( basename( $file ) ) );
					header( 'Content-Disposition: attachment; filename="'.esc_attr( sanitize_file_name( $file_name ) ).'"' );

					$file = wp_kses_decode_entities( $file );

					if(!get_option('sd_troubleshoot_no_ob')) @ob_end_clean();
					// @ob_start();

					$h = fopen( $file, 'rb');
					if( $h )
					{
						while(!feof($h)) {
							echo fread($h, 1024*8);
							if(!get_option('sd_troubleshoot_no_ob'))
							{
								@ob_flush();
								flush();
							}
						}
						fclose($h);
					}
					else
					{
						print 'The file cannot be opened';
					}
				}
				catch( Exception $err )
				{
					@unlink( SD_DOWNLOAD.'/.htaccess');
					header( 'location:'.esc_url_raw( SD_URL.'/sd-downloads/'.basename( $file ) ) );
				}
				exit;
			}
			else
			{
				_e( 'Wrong File Location', SD_TEXT_DOMAIN );
				exit;
			}

		}else{
			$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
			$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ? 'purchase_id='.sanitize_key($_REQUEST[ 'purchase_id' ]) : '' );
			header( 'location: '.esc_url_raw( $dlurl ) );
		}
	} // End ms_download_file

	// From PayPal Data RAW
	/*
	  $fieldsArr, array( 'fields name' => 'alias', ... )
	  $selectAdd, used if is required complete the results like: COUNT(*) as count
	  $groupBy, array( 'alias', ... ) the alias used in the $fieldsArr parameter
	  $orderBy, array( 'alias' => 'direction', ... ) the alias used in the $fieldsArr parameter, direction = ASC or DESC
	*/
	function sd_getFromPayPalData( $fieldsArr, $selectAdd = '', $from = '', $where = '', $groupBy = array(), $orderBy = array(), $returnAs = 'json' ){
		global $wpdb;

		$_select = 'SELECT ';
		$_from = 'FROM '.$wpdb->prefix.SDDB_PURCHASE.( ( !empty( $from ) ) ? ','.$from : '' );
		$_where = 'WHERE '.( ( !empty( $where ) ) ? $where : 1 );
		$_groupBy = ( !empty( $groupBy ) ) ? 'GROUP BY ' : '';
		$_orderBy = ( !empty( $orderBy ) ) ? 'ORDER BY ' : '';

		$separator = '';
		foreach( $fieldsArr as $key => $value ){
			$length = strlen( $key )+1;
			$_select .= $separator.'
							SUBSTRING(paypal_data,
							LOCATE("'.$key.'", paypal_data)+'.$length.',
							LOCATE("\r\n", paypal_data, LOCATE("'.$key.'", paypal_data))-(LOCATE("'.$key.'", paypal_data)+'.$length.')) AS '.$value;
			$separator = ',';
		}

		if( !empty( $selectAdd ) ){
			$_select .= $separator.$selectAdd;
		}

		$separator = '';
		foreach( $groupBy as $value ){
			$_groupBy .= $separator.$value;
			$separator = ',';
		}

		$separator = '';
		foreach( $orderBy as $key => $value ){
			$_orderBy .= $separator.$key.' '.$value;
			$separator = ',';
		}

		$query = $_select.' '.$_from.' '.$_where.' '.$_groupBy.' '.$_orderBy;
		$result = $wpdb->get_results( $query );

		if( !empty( $result ) ){
			switch( $returnAs ){
				case 'json':
					return json_encode( $result );
				break;
				default:
					return $result;
				break;
			}
		}
	} // End sd_getFromPayPalData

	if(!function_exists('sd_send_emails'))
	{
		function sd_send_emails($purchase_settings)
		{
			$sd_notification_from_email 		= get_option('sd_notification_from_email', SD_NOTIFICATION_FROM_EMAIL);
			$sd_notification_to_email   		= get_option('sd_notification_to_email', SD_NOTIFICATION_TO_EMAIL);

			$sd_notification_to_payer_subject   = get_option('sd_notification_to_payer_subject', SD_NOTIFICATION_TO_PAYER_SUBJECT);
			$sd_notification_to_payer_message   = get_option('sd_notification_to_payer_message', SD_NOTIFICATION_TO_PAYER_MESSAGE);

			$sd_notification_to_seller_subject  = get_option('sd_notification_to_seller_subject', SD_NOTIFICATION_TO_SELLER_SUBJECT);
			$sd_notification_to_seller_message  = get_option('sd_notification_to_seller_message', SD_NOTIFICATION_TO_SELLER_MESSAGE);

			$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
			$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

			$information_payer = "Product: {$purchase_settings['item_name']}\n".
								((!empty($purchase_settings['payment_amount'])) ? "Amount: {$purchase_settings['payment_amount']} {$purchase_settings['payment_currency']}\n" : "").
								"Download Link: ".$dlurl."purchase_id={$purchase_settings['purchase_id']}\n";

			$information_seller = "Product: {$purchase_settings['item_name']}\n".
								  ((!empty($purchase_settings['payment_amount'])) ? "Amount: {$purchase_settings['payment_amount']} {$purchase_settings['payment_currency']}\n" : "").
								  "Buyer Email: {$purchase_settings['payer_email']}\n".
								  "Download Link: ".$dlurl."purchase_id={$purchase_settings['purchase_id']}\n";

			$current_datetime = (isset($purchase_settings['date'])) ? $purchase_settings['date'] : date('Y-m-d h:ia');

			// Get the buyer name from the buyer email,
			// only if there is an user with the same email than buyer
			$buyer_name = "";
			$buyer_user = get_user_by('email', $purchase_settings['payer_email']);
			if($buyer_user)
			{
				if($buyer_user->first_name)
				{
					$buyer_name = $buyer_user->first_name;
					if($buyer_user->last_name) $buyer_name .= ' '.$buyer_user->last_name;
				}
				else $buyer_name = $buyer_user->display_name;
			}

			$sd_notification_to_payer_message  = str_replace(
				array(
					"%INFORMATION%",
					"%DATETIME%",
					"%BUYERNAME%"
				),
				array(
					$information_payer,
					$current_datetime,
					$buyer_name
				),
				$sd_notification_to_payer_message
			);

			$sd_notification_to_seller_message = str_replace(
				array(
					"%INFORMATION%",
					"%DATETIME%",
					"%BUYERNAME%"
				),
				array(
					$information_seller,
					$current_datetime,
					$buyer_name
				),
				$sd_notification_to_seller_message
			);

			// Send email to payer
			try
			{
				wp_mail($purchase_settings['payer_email'], $sd_notification_to_payer_subject, $sd_notification_to_payer_message,
						"From: \"$sd_notification_from_email\" <$sd_notification_from_email>\r\n".
						"Content-Type: text/plain; charset=utf-8\n".
						"X-Mailer: PHP/" . phpversion());
			}
			catch( Exception $err ){}

			// Send email to seller
			try
			{
				wp_mail($sd_notification_to_email , $sd_notification_to_seller_subject, $sd_notification_to_seller_message,
						"From: \"$sd_notification_from_email\" <$sd_notification_from_email>\r\n".
						"Content-Type: text/plain; charset=utf-8\n".
						"X-Mailer: PHP/" . phpversion());
			}
			catch( Exception $err ){}
		} // End sd_send_emails
	}