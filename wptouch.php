<?php
/*
   Plugin Name: WPtouch iPhone Theme
   Plugin URI: http://bravenewcode.com/wptouch/
   Description: A plugin which reformats your site with a mobile theme when viewing with an <a href="http://www.apple.com/iphone/"> Apple iPhone</a>, <a href="http://www.apple.com/ipodtouch/">Apple iPod touch</a>, <a href="http://www.android.com/">Google Android</a> or <a href="http://www.rim.com/storm/">Blackberry Storm</a> touch mobile device. Set options for the theme by visiting the <a href="options-general.php?page=wptouch/wptouch.php">WPtouch Options admin panel</a>. &nbsp;
   Author: Dale Mugford & Duane Storey
   Version: 1.8
   Author URI: http://www.bravenewcode.com
   
   # Special thanks to ContentRobot and the iWPhone theme/plugin
   # (http://iwphone.contentrobot.com/) which the detection feature
   # of the plugin was based on.
   
   # This plugin is free software; you can redistribute it and/or
   # modify it under the terms of the GNU Lesser General Public
   # License as published by the Free Software Foundation; either
   # version 2.1 of the License, or (at your option) any later version.

	# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
	# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
	# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
	# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
	# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 
   # See the GNU lesser General Public License for more details.
*/


// WPtouch Theme Options
global $bnc_wptouch_version;
$bnc_wptouch_version = '1.8';

require_once( 'include/plugin.php' );

// uncomment this line to create a fresh install scenario
// update_option( 'bnc_iphone_pages', '' );

//No need to manually change these, they're all admin options saved to the database
global $wptouch_defaults;
$wptouch_defaults = array(
	'header-background-color' => '222222',
	'header-title' => get_bloginfo('name'),
	'main_title' => 'Default.png',
	'enable-post-excerpts' => true,
	'enable-page-coms' => false,
	'enable-cats-button' => true,
	'enable-login-button' => false,
	'enable-redirect' => true,
	'enable-js-header' => true,
	'enable-gravatars' => true,
	'enable-main-home' => true,
	'enable-main-rss' => true,
	'enable-main-name' => true,
	'enable-main-tags' => true,
	'enable-gzip' => false,
	'enable-main-categories' => true,
	'enable-main-email' => true,
	'header-border-color' => '333333',
	'header-text-color' => 'eeeeee',
	'style-text-justify' => 'full',
	'style-text-size' => 'small',
	'link-color' => '006bb3'
);

function wptouch_delete_icon( $icon ) {
	if ( !current_user_can( 'upload_files' ) ) {
		// don't allow users to delete who don't have access to upload (security feature)
		return;	
	}
			
	$dir = explode( 'wptouch', $icon );
	$loc = ABSPATH . 'wp-content/uploads/wptouch/' . $dir[1];
	unlink( $loc );
}

function wptouch_init() {
	if ( isset( $_GET['delete_icon'] ) ) {
		wptouch_delete_icon( $_GET['delete_icon'] );
		
		header( 'Location: ' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wptouch/wptouch.php#available_icons' );
		die;
	}	
}

function wptouch_content_filter( $content ) {
	$settings = bnc_wptouch_get_settings();
	if ( isset($settings['adsense-id']) && strlen($settings['adsense-id']) && is_single() ) {
		require_once( 'adsense.php' );
		
		$channel = '';
		if ( isset($settings['adsense-channel']) ) {
			$channel = $settings['adsense-channel'];
		}
		
		$ad = google_show_ad( $settings['adsense-id'], $channel );
		return $content . '<div class="wptouch-adsense-ad">' . $ad . '</div>';	
	} else {
		return $content;
	}
}

	add_filter('init', 'wptouch_init');

	function WPtouch($before = '', $after = '') {
		global $bnc_wptouch_version;
		echo $before . 'WPtouch ' . $bnc_wptouch_version . $after;
	}
 
	// WP Admin stylesheet, Javascript
	function wptouch_admin_css() {
		$url = get_bloginfo('wpurl');
		$version = (float)get_bloginfo('version');
		echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wptouch/admin-css/wptouch-admin.css" />';
		$version = (float)get_bloginfo('version');
		if ($version < 2.2) {
			echo '<script src="http://www.google.com/jsapi"></script>';
			echo '<script type="text/javascript">google.load("jquery", "1"); jQuery.noConflict( ); </script>';
		}
		echo "<script type=\"text/javascript\" src=\"" . get_bloginfo('wpurl') . '/wp-content/plugins/wptouch/js/jquery.ajax_upload.1.1.js' . "\"></script>";
	}
  
class WPtouchPlugin {
	var $applemobile;
	var $desired_view;
	var $output_started;
		
	function WPtouchPlugin() {
		$this->output_started = false;
		$this->applemobile = false;
		
		add_action('plugins_loaded', array(&$this, 'detectAppleMobile'));
		add_filter('stylesheet', array(&$this, 'get_stylesheet'));
		add_filter('theme_root', array(&$this, 'theme_root'));
		add_filter('theme_root_uri', array(&$this, 'theme_root_uri'));
		add_filter('template', array(&$this, 'get_template'));
		add_filter('init', array(&$this, 'bnc_filter_iphone'));
		add_filter('wp', array(&$this, 'bnc_do_redirect'));
		
		$this->detectAppleMobile();
	}

	function bnc_do_redirect() {
	   global $post;
	   if ($this->applemobile && $this->desired_view == 'mobile') {
	      if (is_front_page() && bnc_get_selected_home_page() > 0) {
	         $url = get_permalink(bnc_get_selected_home_page());
	         header('Location: ' . $url);
	         die;
	      }
	   }
	}
	
	function bnc_filter_iphone() {
		$key = 'bnc_mobile_' . md5(get_bloginfo('siteurl'));
		
	   	if (isset($_GET['bnc_view'])) {
	   		if ($_GET['bnc_view'] == 'mobile') {
				setcookie($key, 'mobile', 0); 
			} elseif ($_GET['bnc_view'] == 'normal') {
				setcookie($key, 'normal', 0);
			}
			header('Location: ' . get_bloginfo('siteurl'));
			die;
		}
			
		$settings = bnc_wptouch_get_settings();
		if (isset($_COOKIE[$key])) {
			$this->desired_view = $_COOKIE[$key];
		} else {
			if ( $settings['enable-regular-default'] ) {
				$this->desired_view = 'normal';
			} else {
		  		$this->desired_view = 'mobile';
			}
		}
	
		$value = ini_get( 'zlib.output_compression' );
		if ($this->desired_view == 'mobile' && !$this->output_started && !$value) {
	   	
			if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && isset($settings['enable-gzip']) && $settings['enable-gzip']) {
				@ob_start("ob_gzhandler");
			} else {
				@ob_start();
			}
			
			$this->output_started = true;
	   }
	}
	
	function detectAppleMobile($query = '') {
		$container = $_SERVER['HTTP_USER_AGENT'];
		// The below prints out the user agent array. Uncomment to see it shown on the page.
		// print_r($container); 
		
		// Add whatever user agents you want here to the array if you want to make this show on a Blackberry 
		// or something. No guarantees it'll look pretty, though!
		$useragents = array("iPhone", "iPod", "aspen", "dream", "incognito", "webmate", "BlackBerry9500", "BlackBerry9530");
		$this->applemobile = false;
		foreach ($useragents as $useragent) {
			if (eregi($useragent, $container)) {
				$this->applemobile = true;
			}
		}
	}
		  
	function get_stylesheet($stylesheet) {
		if ($this->applemobile && $this->desired_view == 'mobile') {
			return 'default';
		} else {
			return $stylesheet;
		}
	}
		  
	function get_template($template) {
		$this->bnc_filter_iphone();
		if ($this->applemobile && $this->desired_view === 'mobile') {
			return 'default';
		} else {	   
			return $template;
		}
	}
		  
	function get_template_directory($value) {
		$theme_root = dirname(__FILE__);
		if ($this->applemobile && $this->desired_view === 'mobile') {
				return $theme_root . '/themes';
		} else {
				return $value;
		}
	}
		  
	function theme_root($path) {
		$theme_root = dirname(__FILE__);
		if ($this->applemobile && $this->desired_view === 'mobile') {
			return $theme_root . '/themes';
		} else {
			return $path;
		}
	}
		  
	function theme_root_uri($url) {
		if ($this->applemobile && $this->desired_view === 'mobile') {
			$dir = get_bloginfo('wpurl') . "/wp-content/plugins/wptouch/themes";
			return $dir;
		} else {
			return $url;
		}
	}
}
  
global $wptouch_plugin;
$wptouch_plugin = &new WPtouchPlugin();

/*

function bnc_get_page_id_with_name ($name ) {
   global $table_prefix;
   global $wpdb;
   
   $sql = 'select * from ' . $table_prefix . 'posts where post_title = \'' . $name . '\';';
   
   if (mysql_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
      if (mysql_select_db(DB_NAME)) {
         $sql = 'select * from ' . $table_prefix . 'posts where post_title = \'' . $name . '\';';
         $result = mysql_query($sql);
         while ($row = mysql_fetch_assoc($result)) {
            print_r($row);
         }
      }
   }
}

*/

function bnc_is_iphone() {
	global $wptouch_plugin;
	return $wptouch_plugin->applemobile;
}
  
	// The Automatic Footer Template Switch Code (into "wp_footer()" in footer.php)
function wptouch_switch() {
	global $wptouch_plugin;
	if ($wptouch_plugin->desired_view == 'normal') {
		echo '<div style="width: auto;height: 48px;padding-top:17px;padding-bottom:15px;font-size: x-large;font-weight: bold;background: url(' . get_bloginfo('wpurl') . '/wp-content/plugins/wptouch/images/switch-bg.png) repeat-x 0 0;margin:0px;border-top: 1px solid #999;border-bottom: 2px solid #999;text-shadow: #e6e6e6 3px 3px 1px;" id="switch-footer-links">';
		echo sprintf( __( 'View %s\'s', "wptouch" ), get_bloginfo('title') ) . '<a href="' . get_bloginfo('siteurl') . '/?bnc_view=mobile">' . __( " Mobile Theme", "wptouch" ) . '</a>';
		echo '</div>';
	}
}
  
function bnc_options_menu() {
	add_options_page( __( 'WPtouch Theme', 'wptouch' ), 'WPtouch', 9, __FILE__, bnc_wp_touch_page);
}

function bnc_get_ordered_cat_list() {
	// We created our own function for this as wp_list_categories doesn't make the count linkable

	global $table_prefix;
	global $wpdb;

	$sql = "select * from " . $table_prefix . "term_taxonomy inner join " . $table_prefix . "terms on " . $table_prefix . "term_taxonomy.term_id = " . $table_prefix . "terms.term_id where taxonomy = 'category' order by count desc";	
	$results = $wpdb->get_results( $sql );
	foreach ($results as $result) {
		echo "<li><a href=\"" . get_category_link( $result->term_id ) . "\">" . $result->name . " (" . $result->count . ")</a></li>";
	}

}

function bnc_wptouch_get_settings() {
	return bnc_wp_touch_get_menu_pages();
}

function bnc_validate_wptouch_settings( &$settings ) {
	global $wptouch_defaults;
	foreach ( $wptouch_defaults as $key => $value ) {
		if ( !isset( $settings[$key] ) ) {
			$settings[$key] = $value;
		}
	}
}

function bnc_wp_touch_get_menu_pages() {
	$v = get_option('bnc_iphone_pages');
	if (!$v) {
		$v = array();
	}
	
	if (!is_array($v)) {
		$v = unserialize($v);
	}
	
	bnc_validate_wptouch_settings( $v );

	return $v;
}

function bnc_get_selected_home_page() {
   $v = bnc_wp_touch_get_menu_pages();
   return $v['home-page'];
}

function wptouch_get_stats() {
	$options = bnc_wp_touch_get_menu_pages();
	if (isset($options['statistics'])) {
		echo stripslashes($options['statistics']);
	}
}
  
function bnc_get_title_image() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['main_title'];
}

function bnc_excerpt_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-post-excerpts'];
}	

function bnc_is_page_coms_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-page-coms'];
}		

function bnc_is_cats_button_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-cats-button'];
}	

function bnc_is_login_button_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-login-button'];
}		

function bnc_is_redirect_enable() {
	$ids = bnc_wp_touch_get_menu_pages();
}
	
function bnc_is_js_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-js-header'];
}	
	
function bnc_is_gravatars_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-gravatars'];
}	
	
function bnc_is_home_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-main-home'];
}	

function bnc_is_rss_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-main-rss'];
}	

function bnc_show_author() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-main-name'];
}

function bnc_show_tags() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-main-tags'];
}

function bnc_show_categories() {
	$ids = bnc_wp_touch_get_menu_pages();
	return $ids['enable-main-categories'];
}

function bnc_is_email_enabled() {
	$ids = bnc_wp_touch_get_menu_pages();
		if (!isset($ids['enable-main-email'])) {
		return true;
		}
	return $ids['enable-main-email'];
}	

  
function bnc_wp_touch_get_pages() {
	global $table_prefix;
	global $wpdb;
	
	$ids = bnc_wp_touch_get_menu_pages();
	$a = array();
	$keys = array();
	foreach ($ids as $k => $v) {
		if ($k == 'main_title' || $k == 'enable-post-excerpts' || $k == 'enable-page-coms' || 
			 $k == 'enable-cats-button'  || $k == 'enable-login-button' || $k == 'enable-redirect' || 
			 $k == 'enable-js-header' || $k == 'enable-gravatars' || $k == 'enable-main-home' || 
			 $k == 'enable-main-rss' || $k == 'enable-main-email' || $k == 'enable-main-name' || $k == 'enable-main-tags' || $k == 'enable-main-categories') {
			} else {
				if (is_numeric($k)) {
					$keys[] = $k;
				}
			}
	}
	 
	$menu_order = array(); 
	$query = "select * from {$table_prefix}posts where ID in (" . implode(',', $keys) . ") order by post_title asc";
	$results = $wpdb->get_results( $query, ARRAY_A );

	if ( $results ) {
		foreach ( $results as $row ) {
			$row['icon'] = $ids[$row['ID']];
			$a[$row['ID']] = $row;
			if (isset($menu_order[$row['menu_order']])) {
				$menu_order[$row['menu_order']*100 + $inc] = $row;
			} else {
				$menu_order[$row['menu_order']*100] = $row;
			}
			$inc = $inc + 1;
		}
	}

	if (isset($ids['sort-order']) && $ids['sort-order'] == 'page') {
		asort($menu_order);
		return $menu_order;
	} else {
		return $a;
	}
}

function bnc_get_header_title() {
	$v = bnc_wp_touch_get_menu_pages();
	return $v['header-title'];
}

function bnc_get_header_background() {
	$v = bnc_wp_touch_get_menu_pages();
	return $v['header-background-color'];
}
  
function bnc_get_header_border_color() {
	$v = bnc_wp_touch_get_menu_pages();
	return $v['header-border-color'];
}

function bnc_get_header_color() {
	$v = bnc_wp_touch_get_menu_pages();
	return $v['header-text-color'];
}

function bnc_get_link_color() {
	$v = bnc_wp_touch_get_menu_pages();
	return $v['link-color'];
}

require_once( 'include/icons.php' );
  
function bnc_wp_touch_page() {
	if (isset($_POST['submit'])) {
		echo('<div class="wrap"><div id="wptouch-theme">');
		echo('<div id="wptouchupdated">' . __( "Your new WPtouch settings were saved.", "wptouch" ) . '</div>');
		echo('<div id="wptouch-title"><p>' . __( "WordPress on iPhone, iPod touch, and Android", "wptouch" ) . '</p>' . WPtouch('<div class="header-wptouch-version"> ' . __( "This is", "wptouch" ) . ' ','</div>') . '</div>');
	} else {
		echo('<div class="wrap"><div id="wptouch-theme">');
		echo('<div id="wptouch-title"><p>' . __( "WordPress on iPhone, iPod touch, and Android", "wptouch" ) . '</p>' . WPtouch('<div class="header-wptouch-version"> ' . __( "This is", "wptouch" ) . ' ','</div>') . '</div>');
	}
?>

<?php $icons = bnc_get_icon_list(); ?>

<?php require_once( 'include/submit.php' ); ?>

<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<?php require_once( 'html/news-area.php' ); ?>
<?php require_once( 'html/home-redirect-area.php' ); ?>
<?php require_once( 'html/javascript-area.php' ); ?>
<?php require_once( 'html/style-area.php' ); ?>
<?php require_once( 'html/post-listings-area.php' ); ?>
<?php require_once( 'html/advertising-area.php' ); ?>
<?php require_once( 'html/icon-area.php' ); ?>
<?php require_once( 'html/default-menu-area.php' ); ?>
<?php require_once( 'html/plugin-compat-area.php' ); ?>		
<?php echo('' . WPtouch('<div class="wptouch-version"> This is ','</div>') . ''); ?>
<input type="submit" name="submit" value="<?php _e('Save Options', 'wptouch' ); ?>" id="wptouch-button" class="button-primary" />
</form>
</div>

<?php 
echo('</div>'); } 
add_action('wp_footer', 'wptouch_switch');
add_action('admin_head', 'wptouch_admin_css');
add_action('admin_menu', 'bnc_options_menu'); 
add_action('the_content', 'wptouch_content_filter');
add_filter('the_content_rss', 'do_shortcode', 11);
?>
