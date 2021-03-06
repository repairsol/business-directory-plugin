<?php
/*
 * Debugging & logging
 */

class WPBDP_Debugging {

	private static $debug = false;
	private static $messages = array();

	public static function debug_on() {
		self::$debug = true;

		error_reporting(E_ALL | E_DEPRECATED);
		// @ini_set('display_errors', '1');
		set_error_handler(array('WPBDP_Debugging', '_php_error_handler'));

		add_action( 'wp_enqueue_scripts', array( 'WPBDP_Debugging', '_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( 'WPBDP_Debugging', '_enqueue_scripts' ) );

		add_action('admin_footer', array('WPBDP_Debugging', '_debug_bar_footer'), 99999);
		add_action('wp_footer', array('WPBDP_Debugging', '_debug_bar_footer'), 99999);
	}

	public static function _enqueue_scripts() {
		wp_enqueue_style( 'wpbdp-debugging-styles', WPBDP_URL . 'resources/css/debug.css' );
	}

	public static function _php_error_handler($errno, $errstr, $file, $line, $context) {
		static $errno_to_string = array(
			E_ERROR => 'error',
			E_WARNING => 'warning',
			E_NOTICE => 'notice',
			E_USER_ERROR => 'user-error',
			E_USER_WARNING => 'user-warning',
			E_USER_NOTICE => 'user-notice',
			E_DEPRECATED => 'deprecated'
		);

		self::add_debug_msg( $errstr,
							 isset( $errno_to_string[ $errno ] ) ? 'php-' . $errno_to_string[ $errno ] : 'php',
							 array( 'file' => $file,
							 		'line' => $line) );
	}

	public static function debug_off() {
		self::$debug = false;

		remove_action('admin_footer', array('WPBDP_Debugging', '_debug_bar_footer'), 99999);
		remove_action('wp_footer', array('WPBDP_Debugging', '_debug_bar_footer'), 99999);
	}

	public static function _debug_bar_footer() {
		if (!self::$debug)
			return;

		global $wpdb;
		$queries = $wpdb->queries;

		if (!self::$messages && !$queries)
			return;

		echo '<div id="wpbdp-debugging">';
		echo '<ul class="tab-selector">';
		echo '<li class="active"><a href="#logging">Logging</a></li>';
		echo '<li><a href="#wpdbqueries">$wpdb queries</a></li>';
		echo '</ul>';
		echo '<div class="tab" id="wpbdp-debugging-tab-logging">';
		echo '<table>';

		foreach (self::$messages as $item) {
			$time = explode( ' ', $item['timestamp'] );

			echo '<tr class="' . $item['type'] . '">';
			echo '<td class="handle">&raquo;</td>';
			echo '<td class="timestamp">' . date('H:i:s', $time[1]) . '</td>';

			echo '<td class="type">' . $item['type'] . '</td>';
			echo '<td class="message">' . $item['message'] . '</td>';

			if ($item['context']) {
				echo '<td class="context">' . $item['context']['function'] . '</td>';
				echo '<td class="file">' . basename($item['context']['file']) . ':' . $item['context']['line'] . '</td>';
			} else {
				echo '<td class="context"></td><td class="file"></td>';
			}
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';

		echo '<div class="tab" id="wpbdp-debugging-tab-wpdbqueries">';
		if ( !$queries ) {
			echo 'No SQL queries were logged.';
		} else {
			echo '<table>';

			foreach ( $queries as $q ) {
				echo '<tr class="wpdbquery">';
				echo '<td class="handle">&raquo;</td>';
				echo '<td class="query">';
				echo $q[0];
				echo '<div class="extradata">';
				echo '<dl>';
				echo '<dt>Time Spent:</dt><dd>' . $q[1] . '</dd>';
				echo '<dt>Backtrace:</dt><dd>' . $q[2] . '</dd>';
				echo '</dl>';
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			}

			echo '</table>';
		}
		echo '</div>';
		echo '</div>';

		printf( '<script type="text/javascript" src="%s"></script>', WPBDP_URL . 'resources/js/debug.js' );
	}

	private static function _extract_context($stack) {
		if ( !is_array( $stack ) || empty( $stack ) )
			return array();

		$context = array( 'class' => '', 'file' => '', 'function' => '', 'line' => '' );

		foreach ( $stack as $i => &$item ) {
			if ( ( isset( $item['class'] ) && $item['class'] == 'WPBDP_Debugging' ) || ( isset( $item['file'] ) && $item['file'] == __FILE__ ) )
				continue;

			if ( isset( $item['function'] ) && in_array( $item['function'], array( 'wpbdp_log', 'wpbdp_debug', 'wpbdp_log_deprecated' ) ) ) {
				$context['file'] = $item['file'];
				$context['line'] = $item['line'];
				$context['function'] = $item['function'];

				$i2 = current( $stack );
				$context['function'] = $i2['function'];
				break;
			} else {
				$context['file'] = $item['file'];
				$context['line'] = $item['line'];
				$context['stack'] = $stack;
			}
		}

		return $context;
	}

	private static function add_debug_msg($msg, $type='debug', $context=null) {
		self::$messages[] = array( 'timestamp' => microtime(),
								   'message' => $msg,
								   'type' => $type,
								   'context' => wpbdp_starts_with( $type, 'php', false ) ? $context : self::_extract_context($context),
								 );
	}

	private static function _var_dump($var) {
		if ( is_bool( $var ) || is_int( $var ) || ( is_string( $var ) && empty( $var ) ) )
			return var_export( $var, true );

		return print_r($var, true);
	}

	/* API */

	public static function debug() {
		if (self::$debug) {
			foreach (func_get_args() as $var)
				self::add_debug_msg(self::_var_dump($var), 'debug', debug_backtrace());
		}
	}

	public static function debug_e() {
		$ret = '';

		foreach (func_get_args() as $arg)
			$ret .= self::_var_dump($arg) . "\n";

		wp_die(sprintf('<pre>%s</pre>', $ret), '');
	}

	public static function log($msg, $type='info') {
		self::add_debug_msg($msg, sprintf('log-%s', $type), debug_backtrace());
	}

}

function wpbdp_log($msg, $type='info') {
	call_user_func(array('WPBDP_Debugging', 'log'), $msg, $type);
}

function wpbdp_log_deprecated() {
	wpbdp_log('Deprecated function called.', 'deprecated');
}

function wpbdp_debug() {
	$args = func_get_args();
	call_user_func_array(array('WPBDP_Debugging', 'debug'), $args);
}

function wpbdp_debug_e() {
	$args = func_get_args();
	call_user_func_array(array('WPBDP_Debugging', 'debug_e'), $args);
}

/**
 * E-mail handling class.
 * @since 2.1
 */
class WPBDP_Email {

	public function __construct() {
		$this->headers = array();

		$this->subject = '';
		$this->from = null;
		$this->to = array();
		$this->cc = array();

		$this->body = '';
		$this->plain = '';
		$this->html = '';
	}

	private function prepare_html() {
		if (!$this->html) {
			$text = $this->body ? $this->body : $this->plain;
			$text = str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $text);
			$this->html = nl2br($text);
		}
	}

	private function prepare_plain() {
		if (!$this->plain) {
			$text = $this->body ? $this->body : $this->html;
			$this->plain = strip_tags($text); // FIXME: this removes 'valid' plain text like <whatever>
		}
	}

	/**
	 * Sends the email.
	 * @param string $format allowed values are 'html', 'plain' or 'both'
	 * @return boolean true on success, false otherwise
	 */
	public function send($format='both') {
		// TODO: implement 'plain' and 'both'
		$this->prepare_html();
		$this->prepare_plain();

		$from = $this->from ? $this->from : sprintf('%s <%s>', get_option('blogname'), get_option('admin_email'));
		$headers = array_merge(array(
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/html; charset="' . get_option('blog_charset') . '"',
			'From' => $from
		), $this->headers);

		$email_headers = '';
		foreach ($headers as $k => $v) {
			$email_headers .= sprintf("%s: %s\r\n", $k, $v);
		}

		return wp_mail($this->to, $this->subject, $this->html, $email_headers);
	}

}

/*
 * Misc.
 */

function wpbdp_getv($dict, $key, $default=false) {
	$_dict = is_object($dict) ? (array) $dict : $dict;

	if (is_array($_dict) && isset($_dict[$key]))
		return $_dict[$key];

	return $default;
}

function wpbdp_render_page($template, $vars=array(), $echo_output=false) {
	if ($vars) {
		extract($vars);
	}

	ob_start();
	include($template);
	$html = ob_get_contents();
	ob_end_clean();

	if ($echo_output)
		echo $html;

	return $html;
}

function wpbdp_generate_password($length=6, $level=2) {
   list($usec, $sec) = explode(' ', microtime());
   srand((float) $sec + ((float) $usec * 100000));

   $validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
   $validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   $validchars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";

   $password  = "";
   $counter   = 0;

   while ($counter < $length)
   {
	 $actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);

	 // All character must be different
	 if (!strstr($password, $actChar))
	 {
		$password .= $actChar;
		$counter++;
	 }
   }

   return $password;
}

function wpbdp_capture_action($hook) {
	$output = '';

	$args = func_get_args();
	if (count($args) > 1) {
		$args = array_slice($args, 	1);
	} else {
		$args = array();
	}

	ob_start();
	do_action_ref_array($hook, $args);
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

function wpbdp_capture_action_array($hook, $args=array()) {
	$output = '';

	ob_start();
	do_action_ref_array($hook, $args);
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

function wpbdp_media_upload_check_env( &$error ) {
	if ( empty( $_FILES ) && empty( $_POST ) && isset( $_SERVER['REQUEST_METHOD'] ) &&
		 strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' ) {
		$post_max = wpbdp_php_ini_size_to_bytes( ini_get( 'post_max_size' ) );
		$posted_size = intval( $_SERVER['CONTENT_LENGTH'] );

		if ( $posted_size > $post_max ) {
			$error = _x( 'POSTed data exceeds PHP config. maximum. See "post_max_size" directive.', 'utils', 'WPBDM' );
			return false;
		}
	}

	return true;
}

function wpbdp_php_ini_size_to_bytes( $val ) {
	$val = trim( $val );
	$size = intval( $val );
	$unit = strtoupper( $val[strlen($val) - 1] );

	switch ( $unit ) {
		case 'G':
			$size *= 1024;
		case 'M':
			$size *= 1024;
		case 'K':
			$size *= 1024;
	}

	return $size;
}

/**
 * @since 2.1.6
 */
function wpbdp_media_upload($file, $use_media_library=true, $check_image=false, $constraints=array(), &$error_msg=null) {
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	// TODO(future): it could be useful to have additional constraints available
	$constraints = array_merge( array(
									'image' => false,
									'max-size' => 0,
									'mimetypes' => null
							  ), $constraints );

	if ($file['error'] == 0) {
		if ($constraints['max-size'] > 0 && $file['size'] > $constraints['max-size'] ) {
			$error_msg = sprintf( _x( 'File size (%s) exceeds maximum file size of %s', 'utils', 'WPBDM' ),
								size_format ($file['size'], 2),
								size_format ($constraints['max-size'], 2)
								);
			return false;
		}

		if ( is_array( $constraints['mimetypes'] ) ) {
			if ( !in_array( strtolower( $file['type'] ), $constraints['mimetypes'] ) ) {
				$error_msg = sprintf( _x( 'File type "%s" is not allowed', 'utils', 'WPBDM' ), $file['type'] );
				return false;
			}
		}

		if ( $upload = wp_handle_upload( $file, array('test_form' => FALSE) ) ) {
			if ( !$use_media_library ) {
				if (!is_array($upload) || isset($upload['error'])) {
					$error_msg = $upload['error'];
					return false;
				}

				return $upload;
			}

			if ( $attachment_id = wp_insert_attachment(array(
				'post_mime_type' => $upload['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
				'post_content' => '',
				'post_status' => 'inherit'
			), $upload['file']) ) {
				$attach_metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
				wp_update_attachment_metadata( $attachment_id, $attach_metadata );

				if ( $check_image && !wp_attachment_is_image( $attachment_id ) ) {
					wp_delete_attachment( $attachment_id, true );

					$error_msg = _x('Uploaded file is not an image', 'utils', 'WPBDM');
					return false;
				}

				return $attachment_id;
			}
		}
	} else {
		$error_msg = _x('Error while uploading file', 'utils', 'WPBDM');
	}

	return false;
}

/**
 * Returns the domain used in the current request, optionally stripping
 * the www part of the domain.
 *
 * @since 2.1.5
 * @param $www  boolean     true to include the 'www' part,
 *                          false to attempt to strip it.
 */
function wpbdp_get_current_domain($www=true, $prefix='') {
    $domain = wpbdp_getv($_SERVER, 'HTTP_HOST', '');
    if (empty($domain)) {
        $domain = wpbdp_getv($_SERVER, 'SERVER_NAME', '');
    }

    if (!$www && substr($domain, 0, 4) === 'www.') {
        $domain = $prefix . substr($domain, 4);
    }

    return $domain;
}

/**
 * Bulds WordPress ajax URL using the same domain used in the current request.
 *
 * @since 2.1.5
 */
function wpbdp_ajaxurl($overwrite=false) {
    static $ajaxurl = false;

    if ($overwrite || $ajaxurl === false) {
        $url = admin_url('admin-ajax.php');
        $parts = parse_url($url);

        $domain = wpbdp_get_current_domain();

        // Since $domain already contains the port remove it.
        if ( isset( $parts['port'] ) && $parts['port'] )
            $domain = str_replace( ':' . $parts['port'], '', $domain );

        $ajaxurl = str_replace($parts['host'], $domain, $url);
    }

    return $ajaxurl;
}

/**
 * Removes a value from an array.
 * @since 2.3
 */
function wpbdp_array_remove_value( &$array_, &$value_ ) {
	$key = array_search( $value_, $array_ );

	if ( $key !== false ) {
		unset( $array_[$key] );
	}

	return true;
}

/**
 * Checks if a given string starts with another string.
 * @param string $str the string to be searched
 * @param string $prefix the prefix to search for
 * @return TRUE if $str starts with $prefix or FALSE otherwise
 * @since 3.0.3
 */
function wpbdp_starts_with( $str, $prefix, $case_sensitive=true ) {
	if ( !$case_sensitive )
		return stripos( $str, $prefix, 0 ) === 0;

	return strpos( $str, $prefix, 0 ) === 0;
}

/**
 * @since 3.1
 */
function wpbdp_format_time( $time, $format='mysql', $time_is_date=false ) {
	// TODO: add more formats
	switch ( $format ) {
		case 'mysql':
			return date( 'Y-m-d H:i:s', $time );
			break;
		default:
			break;
	}

	return $time;
}

/**
 * Returns the contents of a directory (ignoring . and .. special files).
 * @param string $path a directory.
 * @return array list of files within the directory.
 * @since 3.3
 */
function wpbdp_scandir( $path ) {
	if ( !is_dir( $path ) )
		return array();
	
    return array_diff( scandir( $path ), array( '.', '..' ) );
}

/**
 * Recursively deletes a directory.
 * @param string $path a directory.
 * @since 3.3
 */
function wpbdp_rrmdir( $path ) {
    if ( !is_dir( $path ) )
        return;

    $files = wpbdp_scandir( $path );

    foreach ( $files as &$f ) {
        $filepath = rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . ltrim( $f, DIRECTORY_SEPARATOR );

        if ( is_dir( $filepath ) )
            wpbdp_rrmdir( $filepath );
        else
            unlink( $filepath );
    }

    rmdir( $path );
}
