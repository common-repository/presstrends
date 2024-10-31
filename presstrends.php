<?php
/*
Plugin Name: PressTrends
Plugin URI: http://www.presstrends.io
Description: PressTrends makes it incredibly easy to get real value from your data. By combining your WordPress data with Visitor, Social and SEO data, PressTrends provides rich clarity into how to improve your site and reach your goals.
Version: 2.3
Author: PressTrends
Author URI: http://www.presstrends.io
*/

/*  Copyright 2012-2013  PressTrends  (email : hello@presstrends.io)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Don't expose any info if called directly
if ( !defined( 'ABSPATH' ) )
	exit;

// Add meta links after activation for support and settings
function set_presstrends_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge(
			$links,
			array( sprintf( '<a href="admin.php?page=presstrends_settings">Settings</a>', $plugin, __('Settings') ), sprintf( '<a href="http://www.presstrends.me/support">Support</a>', $plugin, __('Support') ) )
		);
	}
	return $links;
}

add_filter( 'plugin_row_meta', 'set_presstrends_plugin_meta', 10, 2 );


// add redirect after activation to settings
function presstrends_activate() {
    add_option('presstrends_activation_redirect', true);
}

function presstrends_redirect() {
    if (get_option('presstrends_activation_redirect', false)) {
        delete_option('presstrends_activation_redirect');
        wp_redirect('admin.php?page=presstrends_settings');
    }
}

register_activation_hook(__FILE__, 'presstrends_activate');
add_action('admin_init', 'presstrends_redirect');

function setup_tracking() {
	
	$presstrends_options_array = get_option( 'presstrends_options' );
	$presstrends_site_key = $presstrends_options_array['site_key'];
	
	echo "
	<!-- PressTrends - Analytics Re-Imagined. -->
	<script type='text/javascript'>
	//<![CDATA[
	var owa_baseUrl = 'http://stats.presstrends.me/';
	var owa_cmds = owa_cmds || [];
	owa_cmds.push(['setSiteId', '".$presstrends_site_key."']);
	owa_cmds.push(['trackPageView']);
	owa_cmds.push(['trackClicks']);
	owa_cmds.push(['trackDomStream']);
	 
	(function() {
		var _owa = document.createElement('script'); _owa.type = 'text/javascript'; _owa.async = true;
		_owa.src = owa_baseUrl + 'modules/base/js/owa.tracker-combined-min.js';
		var _owa_s = document.getElementsByTagName('script')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
	}());
	//]]>
	</script>
	<!-- End PressTrends -->
	";
	
}
add_action('wp_head', 'setup_tracking');

// Setup Eventsf
function prstrends_track_event($event_name) {
	// PressTrends Account API Key & Theme/Plugin Unique Auth Code
	$api_key 		= '3ef8b6w1cfye1kdzfav0qs0y1b1gme1riwd0';
	$auth 			= 'awpmy4fy5bv5a6fitxr8lz9knvsekkcrt';
	$api_base 		= 'http://api.presstrends.io/index.php/api/events/track/auth/';
	$api_string     = $api_base . $auth . '/api/' . $api_key . '/';
	$site_url 		= base64_encode(site_url());
    $event_string	= $api_string . 'name/' . urlencode($event_name) . '/url/' . $site_url . '/';
	wp_remote_get( $event_string );
}
add_action( 'prstrends_event', 'prstrends_track_event', 1, 1 );

// Main plugin class
class PressTrendsPlugin {
	
	public static $instance;
	const OPTIONS = 'presstrends_options';

	public function __construct() {
		self::$instance = $this;
		// We defer our hooks to plugins_loaded, so other plugins can interact
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );	
		add_action( 'wp_loaded', array( $this, 'get_data' ) );
	}

	public function plugins_loaded() {
		load_plugin_textdomain( 'presstrends', false, basename( dirname( __FILE__ ) ) . '/languages' );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	private function get_option( $name ) {
		$options = $this->get_options();
		if ( isset( $options[$name] ) )
			return $options[$name];
		else
			return null;
	}

	private function get_options() {
		return get_option( self::OPTIONS );
	}

	public function enqueue_css() {
		wp_enqueue_style( 'presstrends', plugin_dir_url( __FILE__ ) . 'css/presstrends.css', NULL, '20120612' );
	}
	
	// ================================================================== 
	//
	//			        CREATE WORDPRESS MENU ITEMS
	//
	// ==================================================================
	
	public function admin_menu() {
		$menu_icon_url = plugin_dir_url( __FILE__ ) . '/images/menu-icon.png';
		$hooks = array();
		// Add PressTrends Top Level Menu
		$hooks[] = add_menu_page( __( 'Settings', 'presstrends' ), __( 'PressTrends', 'presstrends' ), 'manage_options', 'presstrends_settings', array( $this, 'display_settings' ), $menu_icon_url );
		foreach ( $hooks as $hook )
			add_action( 'load-' . $hook, array( $this, 'enqueue_css' ) );
	}
	
	
	// ================================================================== 
	//
	//						DISPLAY SETTINGS PAGE
	//
	// ==================================================================
	
	public function display_settings() {
		if (isset($_GET['flush']))
		{
			delete_option(presstrends_options);
		}
		
		$site_key = $this->get_option( 'site_key' );
		
		?>
		<div class="presstrends wrap">
			
				<h2 class="dummy"></h2>
				<div class="logo">
					<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/presstrends_logo_prple.png" alt="<?php esc_attr_e( 'PressTrends', 'presstrends' ); ?>" width="50px" height="50px" border="0" />
				</div>
				
				<div class="clear"></div>
				
				<div class="section_heading">
				<?php $site_story = urlencode( 'Turning data into insights' ); ?>
				<?php echo '<h2 style="font-size:20px;">' . __( 'Thanks for using PressTrends. We understand this isn\'t just a website, but a dream. A passion. Do what you love and we\'ll help guide you. <a href="https://twitter.com/share?text='.$site_story.'%20%40prstrends&url=http://www.presstrends.io" target="_blank">Tweet your story.</a>', 'presstrends' ) . '</h2><p>To activate PressTrends on this site, <a href="http://live.presstrends.io/login" title="Log In" target="_blank">log in</a> or <a href="http://live.presstrends.io/register" title="Register" target="_blank">create a new account</a>, add this site in your PressTrends account, and finally copy/paste the <strong>Site Key</strong> below. We process report data every 2 hours. If you have any questions, please let us know @ <a href="mailto:hello@presstrends.io" title="PressTrends Support">hello@presstrends.io</a>.</p>'; ?>
				</div>
				
				<div class="clear"></div><div class="hr" style="margin-top:25px;"></div><div class="clear"></div>
				
				<?php
				if ( isset( $_GET['settings-updated'] ) ) {
					echo '<div class="updated" style="margin: 0 0 28px 0px;width:95%;"><p>' . __( 'Settings saved.', 'presstrends' ) . '</p></div><div class="clear"></div><div class="hr" style="margin-top:5px;"></div><div class="clear"></div>';
					do_action( 'prstrends_event', 'Added Key');
				}
			?>
				
			<form action="options.php" method="post">
				<?php settings_fields( 'presstrends_options' ); ?>
				<?php do_settings_sections( 'presstrends' ); ?>
				
				<div class="clear"></div><div class="hr" style="margin-top:25px;"></div><div class="clear"></div>
				
				<?php submit_button(); ?>
				
			</form>
			
			
		</div>

		<?php
	}
	
	public function get_data() {
	
	
		global $wpdb;
		$site_key = $this->get_option( 'site_key' );
		include_once("_inc/core.php");
		
		// ================================================================== 
		//
		//						 GET FIRST PUBLISHING DATE
		//
		// ==================================================================
		function ax_first_post_date($format = 'Y-m-d') {
		 // Setup get_posts arguments
		 $ax_args = array(
		 'numberposts' => -1,
		 'post_status' => 'publish',
		 'order' => 'ASC'
		 );
		 // Get all posts in order of first to last
		 $ax_get_all = get_posts($ax_args);
		 // Extract first post from array
		 $ax_first_post = $ax_get_all[0];
		 // Assign first post date to var
		 $ax_first_post_date = $ax_first_post->post_date;
		 // return date in required format
		 $output = date($format, strtotime($ax_first_post_date));
		 return $output;
		}
		
		$first_post = ax_first_post_date();
		$presstrends_plugin_data = get_plugin_data( __FILE__ );
		$current_plugin_version = $presstrends_plugin_data['Version'];
		$data = get_transient( 'presstrends_data_2_3' );
		if ( $site_key != '' && !$data ) {
			$url = 'http://api.presstrends.io/index.php/api/wp/site';
			$data = array();
			$count_posts = wp_count_posts();
			$count_pages = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();
			// backwards compatibility
			if ( ! function_exists('wp_get_theme'))
			{
				$theme_data = array('Tags' => array());
			}else
			{
				$theme_data = wp_get_theme();
			}
			
			$plugin_count = count( get_option( 'active_plugins' ) );
			$all_plugins = get_plugins();
			$plugin_name = '';
			$tag_name = '';
		
			foreach ( $all_plugins as $plugin_file => $plugin_data ) {
				$plugin_name .= $plugin_data['Name'];
				$plugin_name .= '&';
			}
		
			$tags = $theme_data['Tags'];
		
			foreach( $tags as $keytag => $tag ) {
				$tag_name .= $tag;
				$tag_name .= '&';
			}
		
			$data['api_key']                	= '3ef8b6w1cfye1kdzfav0qs0y1b1gme1riwd0';
			$data['auth']                		= 'awpmy4fy5bv5a6fitxr8lz9knvsekkcrt';
			$data['url']                 		= base64_encode(site_url());
			$data['site_key'] 					= $site_key;
			$data['posts']               		= $count_posts->publish;
			$data['pages']               		= $count_pages->publish;
			$data['comments']            		= $comments_count->total_comments;
			$data['approved']            		= $comments_count->approved;
			$data['spam']                		= $comments_count->spam;
			$data['approved_percentage'] 		= $approved_percentage;
			$data['spam_percentage']     		= $spam_percentage;
			$data['reply_percentage']    		= $reply_percentage;
			$data['category_count']      		= $category_sum;
			$data['pingbacks']           		= $pingback_result;
			$data['post_conversion']     		= $total_post_conversion;
			$data['between_posts']    			= $avg_time_btw_posts;
			$data['between_comments']  			= $avg_time_btw_comments;
			$data['theme_version']       		= $current_plugin_version;
			$data['theme_name']          		= urlencode($theme_data->Name);
			if ( $tag_name != '' )
				$data['theme_tags']      		= $tag_name;
			$data['site_name']           		= urlencode( get_bloginfo( 'name' ) ); // Why?
			$data['plugins']            		= $plugin_count;
			$data['plugin']              		= urlencode( $plugin_name );
			$data['wpversion']           		= get_bloginfo( 'version' );
			$data['post_timeline']       		= serialize($posts_by_month);
			$data['comment_timeline']    		= serialize($comments_by_month);
			$data['first_post']    		 		= $first_post;
			$data['sale_revenue'] 				= $revenue;
			$data['monthly_sales'] 				= $completed_orders;
			$data['cart_checkout'] 				= $cart_checkout_conversion;
			$data['between_sales'] 				= $avg_time_btw_checkout;
			$data['week_replies'] 				= $week_replies;
			$data['week_comments'] 				= $week_comments;
			$data['week_posts'] 				= $week_posts;
			$data['week_pingbacks'] 			= $week_pingbacks;
			$data['week_interactions'] 			= $week_interactions;
			$data['week_post_conversion'] 		= $week_post_conversion;
			$data['week_approved_percentage'] 	= $week_approved_percentage;
			$data['week_between_posts'] 		= $week_avg_time_btw_posts;
			$data['week_between_comments'] 		= $week_avg_time_btw_comments;
			$data['month_replies'] 				= $month_replies;
			$data['month_comments'] 			= $month_comments;
			$data['month_posts'] 				= $month_posts;
			$data['month_pingbacks'] 			= $month_pingbacks;
			$data['month_interactions'] 		= $month_interactions;
			$data['month_post_conversion'] 		= $month_post_conversion;
			$data['month_approved_percentage'] 	= $month_approved_percentage;
			$data['month_between_posts'] 		= $month_avg_time_btw_posts;
			$data['month_between_comments'] 	= $month_avg_time_btw_comments;		
								
			$reponse = wp_remote_post( $url , array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $data,
				'cookies' => array()
			    ) 
			);
			
			set_transient( 'presstrends_data_2_3', $data, 60*60*2 );
					
		}
		return $data;
	}
	
	public function admin_init() 
	{
		register_setting( 'presstrends_options', 'presstrends_options' );
		
		add_settings_section( 'presstrends_main', '', array( $this, 'section_text' ), 'presstrends' );
		
		add_settings_field( 'presstrends_sitekey', __( '<h4 class="skinny" style="margin-top:9px;"><span>Site Key:</span></h4>', 'presstrends' ), array( $this, 'site_key' ), 'presstrends', 'presstrends_main' );
		
	}

	public function section_text() 
	{
		$script = '/wp-content/plugins/presstrends/authorize.php';
		$script = ((!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$script: "http://".$_SERVER['SERVER_NAME'].$script);
		
 	}
	
	public function site_key() 
	{
		$presstrends_site_key = $this->get_option( 'site_key' ); ?>
		
		<input type="text" name="presstrends_options[site_key]" value="<?php if($presstrends_site_key != ''){echo $presstrends_site_key;} ?>" style="padding:10px 15px;float:left;width:300px;margin-top:0px;" />
		<a style="float:left;margin-left:10px;padding:6px 30px;height:auto;color:#707070;" class="button" href="http://live.presstrends.io/login" target="blank">Get Site Key</a>
		
		<?php
	}

}

// Bootstrap everything
new PressTrendsPlugin;