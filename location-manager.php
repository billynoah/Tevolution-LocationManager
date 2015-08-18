<?php
/*
Plugin Name: Tevolution - LocationManager
Plugin URI: http://templatic.com/docs/tevolution-location-manager/
Description: Tevolution - Location Manager plugin is specially built to enhance your site's functionality by allowing location search and sort, setup the maps on your custom post pages with pin point effects. You can also add and manage locations for your site and even have city logs that will show you the number of visits to each of your cities.
Version: 2.0.8
Author: Templatic
Author URI: http://templatic.com/
*/
ob_start();

@define( 'LDOMAIN', 'templatic');  /*tevolution* deprecated*/
@define( 'LMADMINDOMAIN', 'templatic-admin');  /*tevolution* deprecated*/

define( 'TEVOLUTION_LOCATION_VERSION', '2.0.8' );
define('TEVOLUTION_LOCATION_SLUG','Tevolution-LocationManager/location-manager.php');
/* Plugin Folder URL*/
define( 'TEVOLUTION_LOCATION_URL', plugin_dir_url( __FILE__ ) );
/* Plugin Folder Path*/
define( 'TEVOLUTION_LOCATION_DIR', plugin_dir_path( __FILE__ ) );
/* Plugin Root File*/
define( 'TEVOLUTION_LOCATION_FILE', __FILE__ );
/*Define domain name*/

if(!defined('INCLUDE_ERROR'))
	define('INCLUDE_ERROR',__('System might facing the problem in include ',LMADMINDOMAIN));
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(strstr($_SERVER['REQUEST_URI'],'plugins.php') || strstr($_SERVER['REQUEST_URI'],'update.php') ){
	require_once('wp-updates-plugin.php');
	new WP_Location_Manager_Updates( 'http://templatic.com/updates/api/index.php', plugin_basename(__FILE__) );
}
/*
Name:get_tmpl_plugin_directory
desc: return the plugin directory path
*/
if(!function_exists('get_tmpl_plugin_directory')){
function get_tmpl_plugin_directory() {
	 return WP_CONTENT_DIR."/plugins/";
}
}

if(file_exists(get_tmpl_plugin_directory() . 'Tevolution-LocationManager/language.php')){
	include_once( get_tmpl_plugin_directory() . 'Tevolution-LocationManager/language.php');
}

/* provide REST api compatibility with city url */
if( is_plugin_active( 'json-rest-api/plugin.php' ) ) {
	/**
	 * Include our City files for the API.
	 */
	include_once( dirname( __FILE__ ) . '/api/class-wp-json-city.php' );

	$wp_json_city = new WP_JSON_City( $server );
	add_filter( 'json_endpoints', array( $wp_json_city, 'register_routes' ), 0 );
	add_filter( 'json_prepare_city', array( $wp_json_city, 'add_post_type_data' ), 10, 3 );
}

if(is_plugin_active('Tevolution/templatic.php'))
{

	$locale = get_locale();
	
	if(is_admin()){
		load_textdomain( LMADMINDOMAIN,TEVOLUTION_LOCATION_DIR.'languages/lm-templatic-admin-'.$locale.'.mo' );
	}else{
		load_textdomain( LDOMAIN,TEVOLUTION_LOCATION_DIR.'languages/lmtemplatic-'.$locale.'.mo' );
	}
	
	/*Include the tevolution plugins main file to use the core functionalities of plugin.*/
	if(file_exists(get_tmpl_plugin_directory() . 'Tevolution/templatic.php')){
		include_once( get_tmpl_plugin_directory() . 'Tevolution/templatic.php');
	}
	
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
	
	/* Bundle Box*/
	if(is_admin() && (isset($_REQUEST['page']) && $_REQUEST['page']=='templatic_system_menu')){
		include(TEVOLUTION_LOCATION_DIR."bundle_box.php");	
		include(TEVOLUTION_LOCATION_DIR."install.php");
	}
	include(TEVOLUTION_LOCATION_DIR.'functions/manage_function.php');
	if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/map/map-shortcodes/map-shortcodes.php')){
		include(TEVOLUTION_LOCATION_DIR.'functions/map/map-shortcodes/map-shortcodes.php');
	}
	
	
	
}else
{
	add_action('admin_notices','location_admin_notices');
}
/*This function display notice for base plugin tevolution not activate */
function location_admin_notices(){
	echo '<div class="error"><p>' . sprintf(__('You have not activated the base plugin %s. Please activate it to use Tevolution-LocationManager plugin.',LMADMINDOMAIN),'<b>Tevolution</b>'). '</p></div>';
	
}
/* plugin activation hook */
register_activation_hook(__FILE__,'location_plugin_activate');
if(!function_exists('location_plugin_activate')){
	function location_plugin_activate(){
		global $wpdb;
		update_option('tevolution_location','Active');
		$field_check = $wpdb->get_var("SHOW COLUMNS FROM $wpdb->terms LIKE 'term_icon'");
		if('term_icon' != $field_check)	{
			$wpdb->query("ALTER TABLE $wpdb->terms ADD term_icon varchar(255) NOT NULL DEFAULT ''");
		}
		
		$location_post_type[]='post,category,post_tag';
		$post_types=get_option('templatic_custom_post');
		foreach($post_types as $key=>$val){
			$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));
			$location_post_type[]=$key.','.$taxonomies[0].','.$taxonomies[1];
		}
		if(!get_option('location_post_type'))
			$post_types=update_option('location_post_type',$location_post_type);
		
		update_option('directory_citylocation_view','location_aslink');
		if(!get_option('location_options'))
		 update_option('location_options','location_default');
		
		/* set default option for map */
		if(!get_option('directory_citylocation_view'))
		 update_option('directory_citylocation_view','location_aslink');
		update_option('default_city_set','default_city');
	}
	
}

/* Plugin activation hook
	- will disable the single city settings
	- add the term icon column in terms table
	- if locations display settings not set then set default as LINK
	*/
function location_plugin_activate_settings(){ 
	global $wpdb,$pagenow;
	/*
	 * Create postcodes table and save the sorting option in templatic setting on plugin page or location setting system menu page
	 */
	remove_action('after_map_setting','googlemap_settings');	
	if($pagenow=='plugins.php' || $pagenow=='themes.php' || (isset($_REQUEST['page']) && $_REQUEST['page']=='location_settings')){
		update_option('tevolution_location','Active');
		$field_check = $wpdb->get_var("SHOW COLUMNS FROM $wpdb->terms LIKE 'term_icon'");
		if('term_icon' != $field_check)	{
			$wpdb->query("ALTER TABLE $wpdb->terms ADD term_icon varchar(255) NOT NULL DEFAULT ''");
		}
		
		$location_post_type[]='';
		$post_types=get_option('templatic_custom_post');
		foreach($post_types as $key=>$val){
			$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));
			$location_post_type[]= @$key.','. @$taxonomies[0].','. @$taxonomies[1];
		}
	
		if(!get_option('location_post_type'))
			update_option('location_post_type',$location_post_type);
			
			
		if(isset($_REQUEST['activate']) && $_REQUEST['activate'] !='')
			update_option('location_post_type',$location_post_type);
		
		
		if(!get_option('directory_citylocation_view'))
		 update_option('directory_citylocation_view','location_aslink');
		
	}
}

add_action('admin_init', 'location_plugin_activate_settings',21);

/* remove the menu which are not related to location manager plugin */
add_action( 'admin_menu', 'tmpl_remove_lm_notrelative_menus', 999 );
function tmpl_remove_lm_notrelative_menus() {
	remove_submenu_page( 'templatic_wp_admin_menu','googlemap_settings' );
	remove_submenu_page( 'templatic_system_menu','googlemap_settings' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),'location_action_links'  );
/* Plugin action link filter call for display settings link in plugins page before deactivate plugin link */
function location_action_links($links){
	if(!is_plugin_active('Tevolution/templatic.php')){
		return $links;
	}
	
	$plugin_links = array('<a href="' . admin_url( 'admin.php?page=location_settings' ) . '">' . __( 'Settings', LMADMINDOMAIN ) . '</a>',);
	
	return array_merge( $plugin_links, $links );
}
/*
	Display the admin sub menu page of tevolution menu page
 */
add_action('templ_add_admin_menu_', 'location_add_page_menu', 20);
function location_add_page_menu(){
	global $location_settings_option;
	$menu_title2 = __('Manage Locations', LMADMINDOMAIN);	
	$location_settings_option=add_submenu_page('templatic_system_menu', $menu_title2, $menu_title2,'administrator', 'location_settings', 'location_plugin_settings');
	add_action("load-$location_settings_option", "location_settings_option");
}
/* 
	Remove the wpml icl_redirect_canonical_wrapper function for home page redirect issue
*/
add_action('plugins_loaded', 'location_init'); 
function location_init(){
	if(is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
	{
	 	remove_action('template_redirect', 'icl_redirect_canonical_wrapper', 11);
	}
}

/*
	Update directory_update_login plugin version after templatic member login
*/
add_action('wp_ajax_location-manager','location_manager_update_login');
function location_manager_update_login()
{
	check_ajax_referer( 'location-manager', '_ajax_nonce' );
	$plugin_dir = rtrim( plugin_dir_path(__FILE__), '/' );	
	require_once( $plugin_dir .  '/templatic_login.php' );	
	exit;
}
/* remove wp auto updates */
add_action('admin_init','location_manager_wpup_changes',20);
function location_manager_wpup_changes(){ 
	 remove_action( 'after_plugin_row_Tevolution-LocationManager/location-manager.php', 'wp_plugin_update_row' ,10, 2 );
}

/*
	Display comment review city wise
 */

function location_comments_clauses($pieces){
	
	global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table,$current_cityinfo,$wp_query;
	if($current_cityinfo['city_id']!=''){
		$pieces['where'] .= " AND $wpdb->comments.comment_post_ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$current_cityinfo['city_id'].", pm.meta_value ))";	
	}
	return $pieces;
}

/*
	This function will the return the goip information - which will be use whe user looks for nearest city 
*/
function get_geoip_record_by_addr($ipaddress){
	
	$rsGeoData='';
	if(is_dir(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip")){
		/*$ipaddress = "202.4.32.0";*/
		/*$ipaddress='111.90.168.253';*/
		require_once(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip/geoip.inc");
		require_once(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip/geoipcity.inc");	
		require_once(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip/geoipregionvars.php");
		$gi = geoip_open(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip/GeoLiteCity.dat", GEOIP_STANDARD);
		$rsGeoData = geoip_record_by_addr($gi, $ipaddress);
	}
	return $rsGeoData;
}
?>