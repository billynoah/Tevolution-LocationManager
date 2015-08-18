<?php
/*
 * This file use for manage location plugin related function and include other plugin related files
 */

/* Front end country label name */
function tmpl_frontend_country_label(){
	_e('Country',LDOMAIN);
}
/* Front end select country option label */
function tmpl_frontend_select_country_option(){
	_e('Select Country',LDOMAIN);
}
/* Front end state label name */
function tmpl_frontend_state_label(){
	_e('State',LDOMAIN);
}
/* Front end select state option label */
function tmpl_frontend_select_state_option(){
	_e('Select State',LDOMAIN);
}
/* Front end city label name */
function tmpl_frontend_city_label(){
	_e('City',LDOMAIN);
}
/* Front end select city option label */
function tmpl_frontend_select_city_option(){
	_e('Select City',LDOMAIN);
}
/* Backend string*/
function tmpl_backend_country_label(){
	echo __('Country',LMADMINDOMAIN);
}
/* Front end select country option label */
function tmpl_backend_select_country_option(){
	echo __('Select Country',LMADMINDOMAIN);
}
/* Front end state label name */
function tmpl_backend_state_label(){
	echo __('State',LMADMINDOMAIN);
}
/* Front end select state option label */
function tmpl_backend_select_state_option(){
	echo __('Select State',LMADMINDOMAIN);
}
/* Front end city label name */
function tmpl_backend_city_label(){
	echo __('City',LMADMINDOMAIN);
}
/* Front end select city option label */
function tmpl_backend_select_city_option(){
	echo __('Select City',LMADMINDOMAIN);
}
/* location manager functions - manage_functions.php */
add_action('wp_enqueue_scripts','googlemap_script'); /* add google map script*/
function googlemap_script(){
	wp_enqueue_script('location_script',TEVOLUTION_LOCATION_URL.'js/location_script.min.js',array( 'jquery' ),'',false);
}

add_action('tevolution_css','tmpl_add_locationplugin_css',11); /* to call the css on top*/

/*Location plugin stylesheet file enqueue */
function tmpl_add_locationplugin_css(){
	global $tev_css;
	
	if(!empty($tev_css)){
		$tev_css = array_merge($tev_css,array(TEVOLUTION_LOCATION_URL.'css/location.css'));
	}else{
		$tev_css = array(TEVOLUTION_LOCATION_URL.'css/location.css');
	}
}

/* to add style in admin head */
add_action('admin_head','location_function_style');

/* Add event css above the directory plugin */
add_action('wp_enqueue_scripts','tmpl_add_locationplugin_css',3); /* to call the css on top*/

add_action('wp_head','location_function_style',2); /* to add style in head*/
/*Call location_function_style function on wp_head for set the javascript variable and location script js file */
function location_function_style()
{
	global $pagenow,$post,$wp_query;
	if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
		$site_url = get_bloginfo( 'wpurl' )."/wp-admin/admin-ajax.php?lang=".ICL_LANGUAGE_CODE ;
	}else{
		$site_url = get_bloginfo( 'wpurl' )."/wp-admin/admin-ajax.php";
	}
	?>
	<script type="text/javascript" async>
		var loading = '<?php _e('Loading...',LDOMAIN); ?>';
		var ajaxUrl = "<?php echo esc_js( $site_url); ?>";
		var default_city_text = '<?php _e('Default City',LMADMINDOMAIN);?>';
	</script>
	<?php
	wp_enqueue_script('location_script',TEVOLUTION_LOCATION_URL.'js/location_script.min.js',array( 'jquery' ),'',false);
	/* Directory Plugin Style Sheet File */	
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/manage_locations.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/manage_locations.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/location_filter.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/location_filter.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/location_rewrite_rule.php') && !strstr($_SERVER['REQUEST_URI'],'plugin-install.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/location_rewrite_rule.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/widget_functions.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/widget_functions.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/google_map_widget.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/google_map_widget.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/googlemap_listing_widget.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/googlemap_listing_widget.php');
}
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/location_functions.php')){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/location_functions.php');
}
if(is_admin() && file_exists(TEVOLUTION_LOCATION_DIR.'functions/location_city_logs.php') && get_option('disable_city_log')==1){
	include_once(TEVOLUTION_LOCATION_DIR.'functions/location_city_logs.php');
}
/*
 * Function Name: get_custom_terms
 * Return: fetch taxonomy category ids;
 */
function get_custom_terms($taxonomies, $args){
	$args = array('orderby'=>'asc');
	$custom_terms = get_terms($taxonomies, $args);
	$count_term = count($custom_terms);
	$sep =',';
	$i=0;
	$termid='';
	foreach($custom_terms as $term){
		if($i == ($count_term-1)){
			$sep ="";
		}
		if($term)
			$termid .=  $term->term_id.$sep;
		$i++;
	}
	return $termid;
}
/*
 * Function Name: location_tables_creatation
 * Return: generate country, zone and multicity table
 */
add_action('admin_init','location_tables_creatation');
function location_tables_creatation(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table,$pagenow;	
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";
	$multicity_table = $wpdb->prefix . "multicity";
	$city_log_table = $wpdb->prefix . "city_log";
	
	if(isset($_POST['reset_custom_fields']) && (isset($_POST['custom_reset']) && $_POST['custom_reset']==1))
	{
		update_option('location_plugin_setup','none');
	}
	
	/* For manage locations - locations for option - default set*/
	if(!get_option('location_options'))
		 update_option('location_options','location_default');
	/*
	 * Create postcodes table and save the sorting option in templatic setting on plugin page or tevolution system menu page
	 */
	if(($pagenow=='plugins.php' || $pagenow=='themes.php' || (isset($_REQUEST['page']) && ($_REQUEST['page']=='templatic_system_menu' || $_REQUEST['page']=='location_settings' || $_REQUEST['page']=='custom_fields' )) ) && get_option('location_plugin_setup') !='inserted'){
		
		update_option('location_plugin_setup','inserted');
		if(!get_option('disable_city_log_')){
			update_option('disable_city_log','1');
			update_option('disable_city_log_',1);
		}
		/*Country Table Creation BOF */
		if($wpdb->get_var("SHOW TABLES LIKE \"$country_table\"") != $country_table) {
			$create_country = "CREATE TABLE IF NOT EXISTS ".$country_table." (
			country_id int(8) NOT NULL AUTO_INCREMENT,
			country_name varchar(255) NOT NULL,
			iso_code_2 char(2) NOT NULL,
			iso_code_3 char(3) NOT NULL,
			country_flg varchar(255) NOT NULL,
			is_enable int(1) NOT NULL DEFAULT '1',
			PRIMARY KEY (country_id))DEFAULT CHARSET=utf8";
			$wpdb->query($create_country);
			$country_file = TEVOLUTION_LOCATION_DIR."functions/csv/country.csv";
			$country_handel = fopen($country_file, 'r');
			$theData = fgets($country_handel);
			$i = 0;
			$j=0;
			$insert_country = "INSERT INTO $country_table(country_id,country_name,iso_code_2,iso_code_3,country_flg) VALUES";
			while (!feof($country_handel)){
				$country_data[] = fgets($country_handel, 1024);
				$country_array = explode(",",$country_data[$i]);
				if(trim($country_array[0])!='' && trim($country_array[1])!='' && trim($country_array[2])!='' && trim($country_array[3])!='')
				{
					$flag='';
					if(trim($country_array[4])!="")
						$flag=TEVOLUTION_LOCATION_URL."images/flags/".trim($country_array[4]);

					$insert_country.="('".trim($country_array[0])."','".addslashes(trim($country_array[1]))."','". addslashes(trim($country_array[2]))."','". addslashes(trim($country_array[3]))."','".$flag."'),";
				}
				$j++;
				$i++;
			}
			$wpdb->query(substr($insert_country,0,-1));
			fclose($country_handel);
		}
		$message = $wpdb->get_var("SHOW COLUMNS FROM $country_table LIKE 'message'");
		if('message'==$message){
			$wpdb->query("ALTER TABLE $country_table DROP `message`;");
		}		
		/*Zone Table Creation BOF */
		if($wpdb->get_var("SHOW TABLES LIKE \"$zones_table\"") != $zones_table){
			$create_zones = "CREATE TABLE " . $zones_table . " (
			  zones_id int(8) NOT NULL AUTO_INCREMENT,
			  country_id int(8) NOT NULL,
			  zone_code varchar(10) NOT NULL,
			  zone_name varchar(255) NOT NULL,
			  PRIMARY KEY zones_id (zones_id))DEFAULT CHARSET=utf8;";
			$wpdb->query($create_zones);
			$zones_file = TEVOLUTION_LOCATION_DIR."functions/csv/zones.csv";
			$zones_handel = fopen($zones_file, 'r');
			$theData = fgets($zones_handel);
			$i = 0;
			$j=0;
			$counter=1;
			$insert_zones = "INSERT INTO $zones_table(country_id,zone_code,zone_name) VALUES";
			while (!feof($zones_handel)) {
				$zones_data[] = fgets($zones_handel, 1024);
				$zones_array = explode(",",$zones_data[$i]);
				if(trim($zones_array[0])!='' && trim($zones_array[1])!='' && trim($zones_array[2])!='' && trim($zones_array[3])!='')
				{
					$insert_zones.= "(".trim($zones_array[1]).",'". addslashes(trim($zones_array[2]))."','".addslashes(trim($zones_array[3]))."'), ";
				}
				$i++;
			}
			$wpdb->query(substr($insert_zones,0,-2));
			fclose($zones_handel);
		}
		/*zones Table Creation EOF */

		/*MultiCity Table Creation BOF */
		$terms1 = get_custom_terms('listingcategory', @$args);
		$terms = 'all,'.$terms1;
		if($wpdb->get_var("SHOW TABLES LIKE \"$multicity_table\"") != $multicity_table) {
			$create_multicity = "CREATE TABLE IF NOT EXISTS $multicity_table (
			city_id int(8) NOT NULL AUTO_INCREMENT,
			country_id int(8) NOT NULL,
			zones_id int(8) NOT NULL,
			cityname varchar(255) NOT NULL,
			city_slug varchar(255) NOT NULL,
			lat varchar(255) NOT NULL,
			lng varchar(255) NOT NULL,
			scall_factor int(100) NOT NULL,
			is_zoom_home varchar(100) NOT NULL,
			map_type varchar(1000) NOT NULL,
			post_type text NOT NULL,
			categories text NOT NULL,
			is_default tinyint(4) NOT NULL DEFAULT '0',
			message text NOT NULL,
			color varchar(255) NOT NULL DEFAULT '',
			images varchar(255) NOT NULL DEFAULT '',
			header_color varchar(255) NOT NULL DEFAULT '',
			header_image varchar(255) NOT NULL DEFAULT '',
			cat_scall_factor INT(11) NULL DEFAULT NULL,
			is_zoom_cat INT(11) NULL DEFAULT NULL,
			PRIMARY KEY (city_id))DEFAULT CHARSET=utf8";
			$wpdb->query($create_multicity);
			
			$post_types =  tevolution_get_post_type();
			$post_types = implode(',',$post_types);
			if($post_types ==''){
				$post_types ='listing,event,property';
			}
			$insert_muticity = $wpdb->query("INSERT INTO $multicity_table (country_id,zones_id,cityname,city_slug,lat,lng,scall_factor,is_zoom_home,map_type,post_type,categories,is_default) VALUES
			('226','3713','New York','new-york','40.714321', '-74.00579', 13, '0','ROADMAP', '$post_types','$terms', 1),
			('226','3721','Philadelphia','philadelphia', '39.952473', '-75.164106', 13, '1','ROADMAP', '$post_types','$terms', 0),('226','3682','San Francisco','san-francisco', '37.774936', '-122.4194229', 13, '1','ROADMAP', '$post_types','$terms', 0)");
		}
		
		$cat_scall_factor = $wpdb->get_var("SHOW COLUMNS FROM $multicity_table LIKE 'cat_scall_factor'");
		if('cat_scall_factor' != $cat_scall_factor){
			$wpdb->query("ALTER TABLE $multicity_table  ADD `cat_scall_factor` INT(11) NULL DEFAULT NULL ");
		}
		
		$is_zoom_cat = $wpdb->get_var("SHOW COLUMNS FROM $multicity_table LIKE 'is_zoom_cat'");
		if('is_zoom_cat' != $is_zoom_cat){
			$wpdb->query("ALTER TABLE $multicity_table ADD is_zoom_cat INT(11) NULL DEFAULT NULL");
		}		
		/*MultiCity Table Creation BOF */
		
		if($wpdb->get_var("SHOW TABLES LIKE \"$city_log_table\"") != $city_log_table) {
			$create_city_log = "CREATE TABLE IF NOT EXISTS $city_log_table (
			log_id int(10) NOT NULL AUTO_INCREMENT,
			log_city_id int(10) NOT NULL,
			log_count int(10) NOT NULL,
			ip_address varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (log_id)
			)DEFAULT CHARSET=utf8";
			$wpdb->query($create_city_log);
		}
		/*MultiCity Table Creation BOF */
		$postcodes_table = $wpdb->prefix . "postcodes";	
		if($wpdb->get_var("SHOW TABLES LIKE \"$postcodes_table\"") != $postcodes_table) {
			$postcodes_table = "CREATE TABLE IF NOT EXISTS $postcodes_table (
			  pcid bigint(20) NOT NULL AUTO_INCREMENT,
			  post_id bigint(20) NOT NULL,
			  post_type varchar(100) NOT NULL,
			  address varchar(255) NOT NULL,
			  latitude varchar(255) NOT NULL,
			  longitude varchar(255) NOT NULL,
			  PRIMARY KEY (pcid)
			)DEFAULT CHARSET=utf8";
			$wpdb->query($postcodes_table);
		}
		/* Insert Multicity custom field */
		 $post_content = $wpdb->get_row("SELECT post_title,ID FROM $wpdb->posts WHERE $wpdb->posts.post_name = 'post_city_id' and $wpdb->posts.post_type = 'custom_fields'");
		 if(count($post_content) == 0)
		 {
			$my_post = array(
				 'post_title' => 'Multi City',
				 'post_content' => '',
				 'post_status' => 'publish',
				 'post_author' => 1,
				 'post_name' => 'post_city_id',
				 'post_type' => "custom_fields",
				);
			$post_meta = array(
				'heading_type' => 'Locations & Map',
			    'listing_heading_type'=>'Locations & Map',
				'ctype'=>'multicity',
				'htmlvar_name'=>'post_city_id',
				'field_category' =>'all',
				'sort_order' => '8',
				'listng_sort_order' => '8',
				'is_active' => '1',
				'is_submit_field' => '1',
				'is_require' => '1',
				'show_on_page' => 'both_side',
				'show_in_column' => '0',
				'show_on_listing' => '0',
				'is_edit' => 'false',
				'show_on_detail' => '0',
				'is_search'=>'1',
				'show_in_email'  =>'1',
				'is_delete' => '0',
				'validation_type'=>'require',
				'field_require_desc' => 'Please Enter Location'
				);
			$post_id = wp_insert_post( $my_post );
			wp_set_post_terms($post_id,'1','category',true);
			if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
				global $sitepress;
				$current_lang_code= ICL_LANGUAGE_CODE;
				$default_language = $sitepress->get_default_language();
				/* Insert wpml  icl_translations table*/
				$sitepress->set_element_language_details($post_id, $el_type='post_custom_fields', $post_id, $current_lang_code, $default_language );
				if(function_exists('wpml_insert_templ_post'))
					wpml_insert_templ_post($post_id,'custom_fields'); /* insert post in language */
			}
			foreach($post_meta as $key=> $_post_meta)
			{
				add_post_meta($post_id, $key, $_post_meta);
			}
			
			$post_types=get_option('templatic_custom_post');
			$posttype='post,';
			foreach($post_types as $key=>$val){
				$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));	
				$posttype.=$key.',';
				update_post_meta($post_id, 'post_type_'.$key,$key );
				update_post_meta($post_id, 'taxonomy_type_'.$taxonomies[0],$taxonomies[0] );
				update_post_meta($post_id, $key.'_sort_order',get_post_meta($post_id,'sort_order',true));
				update_post_meta($post_id, $key.'_heading_type',get_post_meta($post_id,'heading_type',true));
			}
			update_post_meta($post_id, 'post_type_post','post' );
			update_post_meta($post_id, 'taxonomy_type_category','category');
			update_post_meta($post_id, 'post_type',substr($posttype,0,-1));			
		}else{
			$post_types=get_option('templatic_custom_post');
			$posttype='post,';
			foreach($post_types as $key=>$val){
				$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));	
				$posttype.=$key.',';
				update_post_meta($post_content->ID, 'post_type_'.$key,$key );
				update_post_meta($post_content->ID, 'taxonomy_type_'.$taxonomies[0],$taxonomies[0] );
				update_post_meta($post_content->ID, $key.'_heading_type','Locations & Map' );				
				update_post_meta($post_content->ID, $key.'_sort_order',8);
				update_post_meta($post_content->ID, $key.'_heading_type',get_post_meta($post_content->ID,'heading_type',true));
			}
		}
		
	}/* Finish the id condition for check plugin page or tevolution page*/
	if(isset($_REQUEST['page']) && $_REQUEST['page']=='custom_taxonomy') {
		$post_content = $wpdb->get_row("SELECT post_title,ID FROM $wpdb->posts WHERE $wpdb->posts.post_name = 'post_city_id' and $wpdb->posts.post_type = 'custom_fields'");
		$post_id=$post_content->ID;
		$post_types=get_option('templatic_custom_post');
		$posttype='post,';
		foreach($post_types as $key=>$val){
			$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));
			$posttype.=$key.',';
			update_post_meta($post_id, 'post_type_'.$key,$key );
			update_post_meta($post_id, 'taxonomy_type_'.$taxonomies[0],$taxonomies[0] );
			update_post_meta($post_id, $key.'_sort_order',get_post_meta($post_id,'sort_order',true));
			update_post_meta($post_id, $key.'_heading_type',get_post_meta($post_id,'heading_type',true));
		}
		update_post_meta($post_id, 'post_type_post','post' );
		update_post_meta($post_id, 'taxonomy_type_category','category');
		update_post_meta($post_id, 'post_type',substr($posttype,0,-1));
	}
	$templatic_settings=get_option('templatic_settings');
	if(!isset($templatic_settings['related_post_type'])){
		$post_types=get_option('templatic_custom_post');
		$posttype='';
		foreach($post_types as $key=>$val){
			$posttype[]=$key;
		}
		$settings=array('related_post_type'=>$posttype);
		update_option('templatic_settings',array_merge($templatic_settings,$settings));
	}
	$templatic_settings=get_option('templatic_settings');
}
/* Function for screen option */
function location_settings_option() {
 	global $location_settings_option;
 	$screen = get_current_screen();
 	/* get out of here if we are not on our settings page*/
	if(!is_object($screen) || $screen->id != $location_settings_option)
		return;
 
	$args = array(
		'label' => __('Location setting fields per page', LMADMINDOMAIN),
		'default' => 25,
		'option' => 'location_setting_fields_per_page'
	);
	add_screen_option( 'per_page', $args );
}
/*
 * Function Name: directory_plugin_settings
 * Display directory settings list
 */
function location_plugin_settings(){
                         /* this condition is added here for show city log tab after submit the new value*/
                         if(isset($_POST['location_submit']) && $_POST['location_submit'] != ''){
                                        update_option('disable_city_log',$_POST['disable_city_log']);
                         }
	echo '<div id="icon-options-general" class="icon32 clearfix"><br></div>';
	echo "<h1 class=''>".__('Locations',LMADMINDOMAIN)."</h1>";
	echo '<p class="tevolution_desc">'.__('Using this section you will be able to define country, states and cities which can then be used to filter content on your site. Posts, listings or events added for one city will not show for other cities. Read more about <strong>how to manage cities <a href="http://templatic.com/docs/directory-theme-guide/#multiplecities">here</a></strong>.',LMADMINDOMAIN).'</p>';
	echo '<h2 class="nav-tab-wrapper">';
		$tabs=isset($_REQUEST['location_tabs'])?$_REQUEST['location_tabs']:'';
		location_settings_tabs($tabs);
	echo '</h2>';
	/*do action for directory settings tabs content */
	$tabs_content=isset($_REQUEST['location_tabs'])?$_REQUEST['location_tabs']:'location_manage_locations';
	do_action('location_tabs_content',$tabs_content);
}
/*
 * Display the directory general settings tabs
 */
function location_settings_tabs($current = 'location_manage_locations'){
	if(get_option('disable_city_log')!=1){
		$tabs = apply_filters('location_settings_tabs', array('location_manage_locations' => __('Manage Locations',LMADMINDOMAIN),'countries_manage_locations' => __('Countries',LMADMINDOMAIN),
									'state_manage_locations' => __('States',LMADMINDOMAIN),
									'city_manage_locations' => __('Cities',LMADMINDOMAIN)));
	}else{
		$tabs = apply_filters('location_settings_tabs', array(
									'location_manage_locations' => __('Manage Locations',LMADMINDOMAIN),
									'countries_manage_locations' => __('Countries',LMADMINDOMAIN),
									'state_manage_locations' => __('States',LMADMINDOMAIN),
									'city_manage_locations' => __('Cities',LMADMINDOMAIN),
									'location_city_log'=>__('City Logs',LMADMINDOMAIN),
									));
	}	
    $links = array();
	if($current=="")
		$current='location_manage_locations';

	foreach( $tabs as $tab => $name ) :
		if ( $tab == $current ) :
			$links[] = "<a class='nav-tab nav-tab-active' id='".$tab."_pointer' href='?page=location_settings&location_tabs=$tab'>$name</a>";
		else :
			$links[] = "<a class='nav-tab' id='".$tab."_pointer' href='?page=location_settings&location_tabs=$tab'>$name</a>";
		endif;
	endforeach;
	foreach ( $links as $link )
		echo $link;
}
/*
 * Add action for create new multisite custom field type 
 * Function Name: multicity_custom_field_type
 * Return: add multicity type on custom fields menu
 */
add_action('cunstom_field_type','multicity_custom_field_type');
function multicity_custom_field_type($post_id){	
	?>
	<option value="multicity" <?php if(get_post_meta($post_id,"ctype",true)=='multicity'){ echo 'selected="selected"';}?>><?php _e('Multi City',LMADMINDOMAIN);?></option>
	<?php
}
/*
 * Function Name:directory_multicity_custom_fieldtype
 * display listing multi city custom field on front end Submit form page
 */
add_action('tevolution_custom_fieldtype','location_multicity_custom_fieldtype',10,3);
function location_multicity_custom_fieldtype($key,$val,$post_type){
	
	global $wpdb,$country_table,$zones_table,$multicity_table,$validation_info;
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";
	$multicity_table = $wpdb->prefix . "multicity";
	$name = $val['name'];
	$site_title = $val['label'];
	$type = $val['ctype'];
	$htmlvar_name = $val['htmlvar_name'];
	$admin_desc = $val['desc'];
	$option_values = $val['option_values'];
	$default_value = $val['default'];
	$style_class = $val['style_class'];
	$extra_parameter = $val['extra_parameter'];

	$location_post_type=explode(',',implode(',',get_option('location_post_type')));
	$ID = $wpdb->get_var("SELECT ID FROM $wpdb->posts where post_name='".$htmlvar_name."'");
	$milti_city_post=get_post_meta($ID,'post_type_'.$post_type,$post_type);	

	if($type=='multicity' && in_array($milti_city_post,$location_post_type)){
		/*validation info array for country, state, city */
		$validation_info[] = array('title'=>__('Select Country',LDOMAIN),'name'=> 'country_id','espan'=> 'country_id_error','type'=> 'select','text'=> __('Please select Country',LDOMAIN),'is_require' => 1,					'validation_type'=> 'require');
		$validation_info[] = array('title' => __('Select State',LDOMAIN),'name'=> 'zones_id','espan'=> 'zones_id_error','type'=> 'select','text'=> __('Please select State',LDOMAIN),'is_require'=> 1,'validation_type'=> 'require');
		$validation_info[] = array('title'=> __('Select City',LDOMAIN),'name'=> 'city_id','espan'=> 'city_id_error','type'=> 'select','text'=> __('Please select City',LDOMAIN),'is_require'=> 1,'validation_type'=> 'require');
		/* Finish validation info array for country, state, city */		
		if(isset($_REQUEST['pid']) && $_REQUEST['pid']!='')
		{
			$country_id=$country_id = get_post_meta($_REQUEST['pid'], 'country_id',true);
			$zones_id=$zones_id = get_post_meta($_REQUEST['pid'], 'zones_id',true);
			$post_city_id=$post_city_id = get_post_meta($_REQUEST['pid'], 'post_city_id',true);
		}elseif(isset($_SESSION['custom_fields']) && !empty($_SESSION['custom_fields'])){
			$country_id = $_SESSION['custom_fields']['country_id'];
			$zones_id = $_SESSION['custom_fields']['zones_id'];
			$post_city_id = $_SESSION['custom_fields']['post_city_id'];
		}elseif(isset($_REQUEST['action']) && $_REQUEST['action']=='submit_category_custom_fields'){
			$country_id = $_REQUEST['country_id'];
			$zones_id = $_REQUEST['zones_id'];
			$post_city_id = $_REQUEST['post_city_id'];
		}else{
			$country_id=$current_cityinfo['country_id'];
			$zones_id=$current_cityinfo['zones_id'];
			$post_city_id=$current_cityinfo['city_id'];	
		}
		/* Fetch available country listing as per city wise */
		$countryinfo = $wpdb->get_results($wpdb->prepare("SELECT  distinct  c.country_id,c.*  FROM $country_table c,$multicity_table mc where  c.`country_id`=mc.`country_id`  AND c.is_enable=%d group by country_name order by country_name ASC",1));
		/* more than one country available then display select country dropdown*/
		/* Get the only one country then selected country by default */
		if(count($countryinfo)==1){
			$country_id=$countryinfo[0]->country_id;
			echo '<input type="hidden" name="country_id" value="'.$country_id.'"/>';
		}
		/* Location options */
		$location_options = get_option('location_options');	
		/* if default locations option selected */
		if($location_options =='location_for_country'){
			$country_id = get_option('directory_country_id');
		}
		/* if global option selected  - fetch country then state then city */
		if($location_options!='location_for_country' && $location_options!='location_for_cities'):
		?>
			<div class="form_row clearfix">
				<label><?php tmpl_frontend_country_label(); ?><span class="required">*</span></label>
				<select name="country_id" id="country_id" onchange="fill_zones_cmb(this,'1');" class="textfield textfield_x <?php echo $style_class;?>">
					<option value=""><?php tmpl_frontend_select_country_option()?></option>
					<?php 
					foreach($countryinfo as $country): 
						$selected=($country->country_id==$country_id)? 'selected':'';
						$country_name=$country->country_name;
						if (function_exists('icl_register_string')){
							icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
							$country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
						}?>
					 <option value="<?php echo $country->country_id?>" <?php echo $selected;?> data-name="<?php echo strtolower($country->iso_code_2);?>"><?php echo $country_name;?></option>
					<?php endforeach; ?>
				</select>
				<span class="message_note"></span>
				<span id="country_id_error" class=""></span>
			</div>
		<?php endif;

		if($location_options!='location_for_cities'):?>
			<div class="form_row clearfix">
				<label><?php tmpl_frontend_state_label(); ?><span class="required">*</span></label>
				<select name="zones_id" id="zones_id" onchange="fill_city_cmb(this);" class="textfield textfield_x <?php echo $style_class;?>">
					<option value=""><?php tmpl_frontend_select_state_option()?></option>
					<?php
					$zoneinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id=%d  order by zone_name ASC",$country_id));			                /* Get the only one Zone then selected zoned by default */
					if(count($zoneinfo)==1){
						$zones_id=$zoneinfo[0]->zones_id;
					}
					foreach($zoneinfo as $zone):
						$selected=($zone->zones_id ==$zones_id)? 'selected':'';
						$zone_name=$zone->zone_name;
						if (function_exists('icl_register_string')) {
							icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
							$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
						}?>
						<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
					<?php endforeach;?>
				</select>
				<span id="process_state" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></span>
				<span class="message_note"></span>
				<span id="zones_id_error" class=""></span>
			</div>
		<?php endif;?>
			<div class="form_row clearfix">
				<label><?php tmpl_frontend_city_label();?><span class="required">*</span></label>
				<select name="<?php echo $key; ?>" id="city_id" class="textfield textfield_x <?php echo $style_class;?>">
					<option value=""><?php tmpl_frontend_select_city_option();?></option>
					<?php
					if($country_id!='' && isset($zones_id) && $zones_id > 0){
						$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where country_id=%d and zones_id=%d order by cityname ASC",$country_id,$zones_id));
					}elseif($location_options=='location_for_cities'){
						$cityinfo = $wpdb->get_results("SELECT * FROM $multicity_table order by cityname ASC");
					}
					/* Get the only one city then selected city by default */
					if(count($cityinfo)==1){
						$post_city_id=$cityinfo[0]->city_id;
					}
					foreach($cityinfo as $city):
						$selected=(in_array($city->city_id,$post_city_id) || ($city->city_id==$post_city_id))? 'selected':'';
						$cityname=$city->cityname;
						if (function_exists('icl_register_string')) {
							$city_slug = str_replace(' ','-',strtolower($country_name));
							$country_name = icl_t('location-manager', 'location_country_'.$city_slug ,$country_name);
						}?>
						<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname ;?></option>
					<?php endforeach;?>
				</select>
				<span id="process_city" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></span>
				<span class="message_note"></span>
				<span id="city_id_error" class=""></span>
				<?php if($val['desc']!=""):?><div class="description"><?php echo $val['desc']; ?></div><?php endif; ?>
			</div>
		<?php
	}
}
/*
 * Function Name: directory_backend_custom_field
 * Display the listing multi city custom field display on backend side
 */
add_action('tevolution_backend_custom_fieldtype','directory_backend_custom_field',10,3);
function directory_backend_custom_field($pt_id,$pt_metabox,$post)
{
	global $wpdb,$country_table,$zones_table,$multicity_table,$validation_info;
	$type = $pt_metabox['type'];
	
	$location_post_type=explode(',',implode(',',get_option('location_post_type')));
	$ID = $wpdb->get_var("SELECT ID FROM $wpdb->posts where post_name='".$pt_metabox['htmlvar_name']."'");
	
	if(isset($_REQUEST['post_type']) && $_REQUEST['post_type']!=''){
		$posttype=$_REQUEST['post_type'];
	}else{
		$posttype=(@get_post_type($_REQUEST['post']))? @get_post_type($_REQUEST['post']) :'post';
	}
	$milti_city_post=get_post_meta($ID,'post_type_'.$posttype,$posttype);

	if($type=='multicity' && in_array($milti_city_post,$location_post_type)){
		$country_id=get_post_meta($post->ID,'country_id',true);
		$zones_id=get_post_meta($post->ID,'zones_id',true);
		$city_id=get_post_meta($post->ID,'post_city_id',true);
		$post_city_id=explode(',',$city_id);
		if($city_id!='')
			$sql = $wpdb->get_results("SELECT * FROM $multicity_table where city_id in ($city_id) order by cityname ASC");
		?>
		<tr>
          	<th><label><?php tmpl_backend_country_label();?></label></th>
          	<td>
				<?php $countryinfo = $wpdb->get_results($wpdb->prepare("SELECT  distinct  c.country_id,c.*  FROM $country_table c,$multicity_table mc where  c.`country_id`=mc.`country_id`  AND c.is_enable=%d group by country_name order by country_name ASC",1));?>
                <select name="country_id" id="country_id" onchange="fill_multicity_cmb(this,'1'); fill_zones_cmb(this,'1');"  class="textfield textfield_x <?php echo $style_class;?>">
                     <option value=""><?php tmpl_backend_select_country_option();?></option>
                <?php foreach($countryinfo as $country): $selected=($country->country_id==$country_id)? 'selected':'';
                    $country_name=$country->country_name;
                     if (function_exists('icl_register_string')) {
                            icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
                            $country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
                      }
                ?>
                     <option value="<?php echo $country->country_id?>" <?php echo $selected;?> data-name='<?php echo $country->iso_code_2?>'><?php echo $country_name;?></option>
                <?php endforeach; ?>
                </select>
          </td>
		</tr>
        <tr>
          	<th><label><?php tmpl_backend_state_label();?></label> </th>
          	<td>
               	<select name="zones_id"  id="zones_id" onchange="fill_city_cmb(this.value);"  class="textfield textfield_x <?php echo $style_class;?>">
                    	<option value=""><?php tmpl_backend_select_state_option();?></option> 
                         <?php if($country_id!=""):
						 $zoneinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id=%d  order by zone_name ASC",$country_id));
						foreach($zoneinfo as $zone): $selected=($zone->zones_id ==$zones_id)? 'selected':'';
							$zone_name=$zone->zone_name;
							 if (function_exists('icl_register_string')) {
									icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
									$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
							  }
						?>
                              <option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
                         <?php endforeach;
						 endif;?>
               	</select>
            	<span id="process_state" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></span>
			</td>
        </tr>
        <tr>
          	<th><label><?php tmpl_backend_city_label();?></label> </th>
          	<td>
               	<select multiple="multiple"  name="<?php echo $pt_id;?>[]" id="city_id" class="textfield textfield_x <?php echo $style_class;?>">
                     	<option value=""><?php tmpl_backend_select_city_option();?></option>
                          <?php if($post_city_id!=''):
                          if(isset($zones_id) && $zones_id > 0){
								$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where country_id=%d and zones_id=%d order by cityname ASC",$country_id,$zones_id));
						  }else{
							  $cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where country_id=%d order by cityname ASC",$country_id));
						  }
						foreach($cityinfo as $city):
							   $selected=(in_array($city->city_id,$post_city_id))? 'selected':'';
							   $cityname=$city->cityname;
							   if (function_exists('icl_register_string')) {
									icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
									$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
							   }
						?>
                              <option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname;?></option>
                         <?php endforeach;
						 endif;?>           
               	</select> <br/>
				<?php do_action('tevolution_multicity');  ?>					
            	<span id="process_city" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></span>
			</td>
        </tr>
		<?php
	}
}
/*
 * Function Name: advancesearch_custom_fieldtype
 * Return : display the multicity field type in advance search shortcodes
 */
add_action('advancesearch_custom_fieldtype','advancesearch_custom_multicitytype',10,4);
function advancesearch_custom_multicitytype($key,$val,$post_type,$show_label=''){
	global $wpdb,$country_table,$zones_table,$multicity_table;

	/* to show the city only which contain the posts - no blank city */
	$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta where `meta_key` ='post_city_id' group by {$wpdb->prefix}postmeta.post_id");
	if($city_ids[0]->city_ids){
		foreach($city_ids as $ids){
			$cityids.=$ids->city_ids.",";
		}
		$cityids=str_replace(",","','",substr($cityids,0,-1));
		$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,GROUP_CONCAT(mc.cityname) as cityname, GROUP_CONCAT(mc.city_slug) as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 group by country_name order by country_name ASC");
	}	
	$location_options = get_option('location_options');
	if($location_options  == 'location_for_cities'){
		/* if only state/city/country option selected  */
		$cityinfo = $wpdb->get_results("SELECT * FROM $multicity_table where $multicity_table.city_id in('$cityids') order by cityname ASC");
	}else{
		/* if default locations option selected */
		if($location_options =='location_for_country'){
			$default_country_id = get_option('directory_country_id');
		}
		if($default_country_id)
			$zoneinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id=%d  order by zone_name ASC",$default_country_id));
		if($default_zone_id  && $default_country_id)
			$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where zones_id=%d AND country_id=%d AND $multicity_table.city_id in('$cityids') order by cityname ASC",$default_zone_id,$default_country_id));
	}
	if($val['type']=='multicity'){
		if($location_options!='location_for_country' && $location_options!='location_for_cities'):
		?>
			<div class="form_row clearfix">
				   <?php if($show_label ==1){ echo "<label>"; tmpl_frontend_country_label(); echo "</label>";  } ?>
				    <div class="selectbox">
				    <select name="adv_country" id="adv_country">
						<option value=""><?php tmpl_frontend_select_country_option();?></option>
						<?php foreach($countryinfo as $country): $selected=($country->country_id==$default_country_id)? 'selected':'';
								$country_name=$country->country_name;
								if (function_exists('icl_register_string')) {
									icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
									$country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
								}
						?>
						<option value="<?php echo $country->country_id?>" <?php echo $selected;?>><?php echo $country_name;?></option>
						<?php endforeach; ?>
				    </select>
				    </div>
			  </div>
			<?php endif;
            if($location_options!='location_for_cities'):?>
			   <div class="form_row clearfix"> 
					<?php if($show_label ==1){ echo "<label>"; tmpl_frontend_state_label(); echo "</label>";  } ?>
					<div class="selectbox">
				   <select name="adv_zone" id="adv_zone">
						<option value=""><?php tmpl_frontend_select_state_option();?></option>
						<?php
						if($zoneinfo){
						foreach($zoneinfo as $zone): $selected=($zone->zones_id ==$default_zone_id)? 'selected':'';
						$zone_name=$zone->zone_name;
						 if (function_exists('icl_register_string')) {
								icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
								$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
						  }
					?>
						<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
						<?php endforeach;
						} ?>
				   </select>
				   </div>
			  </div>
			<?php endif;?>
			   <div class="form_row clearfix">
					 <?php if($show_label ==1){ echo "<label>"; tmpl_frontend_city_label(); echo "</label>";  } ?>
					 <div class="selectbox">
				     <select name="adv_city" id="adv_city">
						<option value=""><?php tmpl_frontend_select_city_option();?></option>
						<?php
						if($cityinfo){
						foreach($cityinfo as $city): $selected=($city->city_id ==$default_city_id)? 'selected':'';
							$cityname=$city->cityname;
							if (function_exists('icl_register_string')){
									icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
									$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
							} ?>
							<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname;?></option>
						<?php endforeach;
						} ?>
				     </select>
				  </div>
			</div>
        <?php
	}
}
/*
 * Function Name:directory_multisity_custom_field_save
 * Save the multi site id, country id, zone id when admin user update or new create listing.
 */
add_action('save_post','location_multicity_custom_field_save',12);
function location_multicity_custom_field_save($post_id){
	global $wpdb,$post;
	$post_type= @$_POST['post_type'];
	
	$country = get_post_meta($_POST['ID'],'country_id',true);
	$zone = get_post_meta($_POST['ID'],'zones_id',true);
	
	$_POST['country_id'] = (!empty($country)) ? $country : $_POST['country_id'];
	$_POST['zones_id'] = (!empty($zone)) ? $zone : $_POST['zones_id'];
	
	update_post_meta($_POST['post_ID'],'country_id',$_POST['country_id']);
	update_post_meta($_POST['post_ID'],'zones_id',$_POST['zones_id']);
	if(isset($_POST['post_city_id']) ){
		$post_city_id=$_POST['post_city_id'];
		if(is_array($post_city_id)){
			$post_city_id	=implode(',',$post_city_id);
		}
		update_post_meta($_POST['post_ID'],'post_city_id',$post_city_id);
	}
	$post_address = (isset($_POST['address']))?$_POST['address']:@$_SESSION['custom_fields']['address'];
	$latitude = (isset($_POST['geo_latitude']))?$_POST['geo_latitude']:@$_SESSION['custom_fields']['geo_latitude'];
	$longitude = (isset($_POST['geo_longitude']))?$_POST['geo_longitude']:@$_SESSION['custom_fields']['geo_longitude'];
	$pID = (isset($_POST['post_ID']))?$_POST['post_ID'] : $post_id;
	$post_type=get_post_type( $pID );
	if($post_address && $latitude && $longitude){
		$postcodes_table = $wpdb->prefix . "postcodes";
		$pcid = $wpdb->get_var($wpdb->prepare("select pcid from $postcodes_table where post_id = %d",$pID));
		if($pcid){
			$wpdb->update($postcodes_table , array('post_type' => $post_type,'address'=>$post_address,'latitude'=>$latitude,'longitude'=>$longitude), array('pcid' => $pcid,'post_id'=>$pID) );
		}else{
			$wpdb->query( $wpdb->prepare("INSERT INTO $postcodes_table (post_id, post_type,address, latitude,longitude) VALUES ( %s, %s, %s,%s,%s )", $pID, $post_type, $post_address,$latitude,$longitude) );
		}
	}
	
	/* delete the saved json of map markers on save posts - for home page maulticity map*/

	/* $citylistings = $wpdb->get_results("select * from $wpdb->options where option_name LIKE '%post_city_id_%'");
	for($c =0; $c <= count($citylistings); $c++){
		$option_id = $citylistings[$c]->option_id;
		if($option_id !='')
			$wpdb->query("delete from $wpdb->options where option_id = {$option_id}");
	} */
}
/*
 * Function Name: location_multicity_logs
 * Return: insert/update city log user location(ip address) wise
 */
add_action('wp_head','location_multicity_logs');
function location_multicity_logs(){
	/* DOING_AJAX is define then return false for admin ajax*/	
	if ( (defined( 'DOING_AJAX' ) && DOING_AJAX) || get_option('disable_city_log')!=1 ) {
		return ;
	}
	if(!session_id())
		session_start();
	global $city_log_table,$wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo,$wp_query;
	/* Store header multi city id in settion */
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";
	$multicity_table = $wpdb->prefix . "multicity";
	$city_log_table =$wpdb->prefix . "city_log";
	$default_city_id=$current_cityinfo['city_id'];
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	/*Get city slug permalink */
	if((get_option('show_on_front')=='page' && is_front_page()) || is_home()){
		if(get_query_var($multi_city)!='' && $default_city_id!=''){
			$city_log_table =$wpdb->prefix . "city_log";
			$city_log_res = $wpdb->get_row($wpdb->prepare("SELECT log_count FROM $city_log_table where log_city_id=%d AND ip_address=%s",$default_city_id,$_SERVER['REMOTE_ADDR']));
			if(count($city_log_res) == 0)
				$wpdb->query( $wpdb->prepare("INSERT INTO $city_log_table (log_city_id, log_count,ip_address) VALUES ( %d, %d, %s )", $default_city_id, 1, $_SERVER['REMOTE_ADDR']));
			else	
				$wpdb->query("UPDATE $city_log_table set log_count=log_count+1 where log_city_id=$default_city_id and ip_address='".$_SERVER['REMOTE_ADDR']."'" );
		}
	}

	if(isset($_POST['header_city']) && $_POST['header_city']!=""){
		$_SESSION['post_city_id']=$_POST['header_city'];
		$city_log_res = $wpdb->get_row($wpdb->prepare("SELECT log_count FROM $city_log_table where log_city_id=%d AND ip_address=%s",$_POST['header_city'],$_SERVER['REMOTE_ADDR']));
		if(count($city_log_res) == 0)
			$wpdb->query( $wpdb->prepare("INSERT INTO $city_log_table (log_city_id, log_count,ip_address) VALUES ( %d, %d, %s )", $_POST['header_city'], 1, $_SERVER['REMOTE_ADDR']));
		else
			$wpdb->query("UPDATE $city_log_table set log_count=log_count+1 where log_city_id=".$_POST['header_city']." and ip_address='".$_SERVER['REMOTE_ADDR']."'" );
	}
	/* Store widget multi city id in settion */
	if(isset($_POST['widget_city']) && $_POST['widget_city']!=""){
		$_SESSION['post_city_id']=$_POST['widget_city'];
		$city_log_res = $wpdb->get_row($wpdb->prepare("SELECT log_count FROM $city_log_table where log_city_id=%d AND ip_address=%s",$_POST['widget_city'],$_SERVER['REMOTE_ADDR']));
		if(count($city_log_res) == 0)
			$wpdb->query( $wpdb->prepare("INSERT INTO $city_log_table (log_city_id, log_count,ip_address) VALUES ( %d, %d, %s )", $_POST['widget_city'], 1, $_SERVER['REMOTE_ADDR']));
		else
			$wpdb->query("UPDATE $city_log_table set log_count=log_count+1 where log_city_id=".$_POST['widget_city']." and ip_address='".$_SERVER['REMOTE_ADDR']."'" );
	}
}
/*
 *This action will return the cities after click on location selection	
 */
add_action('before_desk_menu_primary','location_header_navigation',10);
add_action('wp_ajax_nopriv_tev_ajax_headerlocation','tev_ajax_headerlocation');
add_action('wp_ajax_tev_ajax_headerlocation','tev_ajax_headerlocation');
function tev_ajax_headerlocation(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo;
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";
	$multicity_table = $wpdb->prefix . "multicity";

	location_current_multicity(); /* Set the multicity info*/
	$default_country_id=$current_cityinfo['country_id'];
	$default_zone_id=$current_cityinfo['zones_id'];
	$default_city_id=$current_cityinfo['city_id'];
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	?>
	<div class="my_nearest_city"><a href="<?php echo get_bloginfo( 'url' )."?find_city=nearest"; ?>"><?php _e('My Nearest City',LDOMAIN);?></a></div>
    <?php do_action('horizontal_before_location_nav');?>
	<ul class="horizontal_location_nav">						
		<?php
		$cityids='';
		$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta as pm,{$wpdb->prefix}posts as p where pm.post_id=p.ID AND p.post_status='publish' AND pm.meta_key = 'post_city_id'");
		if($city_ids[0]->city_ids){
			foreach($city_ids as $ids){
				$cityids.=$ids->city_ids.",";
			}
			$cityids=str_replace(",","','",substr($cityids,0,-1));

			$location_options = get_option('location_options');
			$default_country_id = get_option('directory_country_id');
			if($location_options =='location_for_country'){
				/* if "only Country" option selected then fetch the city of that country only */
				$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,mc.cityname as cityname, mc.city_slug as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 AND c.`country_id` = $default_country_id group by mc.cityname order by mc.cityname ASC");

			}elseif($location_options =='location_default'){
				/* fetch the all cities for all country except empty */
				$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,mc.cityname as cityname, mc.city_slug as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1  order by c.country_name,mc.cityname ASC");
			}else{
				/* fetch the all cities except empty */
				$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,mc.cityname as cityname, mc.city_slug as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 order by mc.cityname ASC");
			}
		}		
				
		$tmp_country_id=0;
	if(!empty($countryinfo)){		
		foreach($countryinfo as $country):
		
			$country_id=$country->country_id;
			$country_name=$country->country_name;
			$cityname=$country->cityname;
			$city_slug=$country->city_slug;
			
			if(($location_options =='location_default' || $location_options=='') && $tmp_country_id != $country_id) {			
				/* Show country name only if global directory option selected */ 
				if (function_exists('icl_register_string')) {
					icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
					$country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
				}
				echo '<li><h3>'.$country_name.'</h3></li>';
			}
			
			
			if($tevolution_taxonomies_data['tevolution_location_city_remove']==1){
				$city_url= rtrim(get_bloginfo('url'), '/').'/'.$city_slug;
			}else{
				$city_url= rtrim(get_bloginfo('url'), '/').'/'.$multi_city.'/'.$city_slug;
			}
			if (function_exists('icl_register_string')){
				icl_register_string('location-manager', 'location_city_'.$city_slug,$cityname);
				$cityname = icl_t('location-manager', 'location_city_'.$city_slug,$cityname);
				if($tevolution_taxonomies_data['tevolution_location_city_remove']==1){
					$city_url= rtrim(icl_get_home_url(), '/').'/'.$city_slug;
				}else{
					$city_url= rtrim(icl_get_home_url(), '/').'/'.$multi_city.'/'.$city_slug;
				}
			}
			echo '<li class="cities_names"><a href="'.$city_url.'">'.$cityname.'</a></li>';
			$tmp_country_id = $country_id;
		endforeach;
		}
	?>
    </ul>
    <?php do_action('horizontal_after_location_nav');
	exit;
}

/*
 * Display the locations as a link of city.
 * To any the design you can place the CIty <li> anywhere,  
 * Take the code commented As "City selection code start"	
*/
function location_header_navigation(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo;
	$directory_citylocation_view=get_option('directory_citylocation_view');
	if(!$directory_citylocation_view){
		$directory_citylocation_view ="location_aslink";
	}
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";
	$multicity_table = $wpdb->prefix . "multicity";
	/* Set the multi city info*/
	location_current_multicity();

	$default_country_id=$current_cityinfo['country_id'];
	$default_zone_id=$current_cityinfo['zones_id'];
	$default_city_id=$current_cityinfo['city_id'];
	
	$country_id=$current_cityinfo['country_id'];
	$zones_id=$current_cityinfo['zones_id'];
	$post_city_id=$current_cityinfo['city_id'];
	$class_name=(has_nav_menu( 'primary' ))?'primary_location':'';
	?>
	<div class="location_fld_wrapper">
	<?php
	/* City selection code start */
	$city_slug = str_replace(' ','-',strtolower( $current_cityinfo['cityname']));
	if (function_exists('icl_register_string')) {
		$cityname = icl_t('location-manager', 'location_city_'.$city_slug, $current_cityinfo['cityname']);
	}else{
		$cityname = $current_cityinfo['cityname'];
	}
	do_action('before_city_link');
	?>
	<!-- City name link -->
	<div id="loc_city_link" class="toggle_handler <?php echo $class_name?>" data-location="<?php echo $directory_citylocation_view; ?>">
		<a id="directorytab"  href="#" ><?php echo ($current_cityinfo['country_flg']!='')? '<img src="'.$current_cityinfo['country_flg'].'"  width="18" height="12" alt="'.$current_cityinfo['cityname'].'"/> ':'';echo ($current_cityinfo['cityname']!='')? $cityname:_e('Location',LDOMAIN);?>
		<i class="fa fa-angle-down"></i></a>
	</div>
	<?php
	do_action('after_city_link');
	/* City selection end */	
	/* if drop down option is selected */
	do_action('lm_city_selection_start');
	
	if($directory_citylocation_view=='location_asdropdown'){
		/* to show the city only which contain the posts - no blank city */
		$city_ids=$wpdb->get_results("SELECT distinct meta_value as city_ids from {$wpdb->prefix}postmeta where `meta_key` ='post_city_id' group by {$wpdb->prefix}postmeta.post_id");
		if(!empty($city_ids)){
			$cityids = '';
			foreach($city_ids as $ids){
				$cityids.=$ids->city_ids.",";
			}
			if($cityids != ''){
				$cityids=str_replace(",","','",substr($cityids,0,-1));
				$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,GROUP_CONCAT(mc.cityname) as cityname, GROUP_CONCAT(mc.city_slug) as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 group by country_name order by country_name ASC");
			}
		}
		/* Location options */
		$location_options = get_option('location_options');
		/* if default locations option selected */
		if($location_options =='location_for_country'){
			$country_id = get_option('directory_country_id');
		}
		$zones_id=$wpdb->get_results("SELECT distinct meta_value as zones_id from {$wpdb->prefix}postmeta pm,{$wpdb->prefix}posts p where pm.meta_key ='zones_id' and pm.post_id = p.ID and p.post_status ='publish' and pm.meta_value!= '' ");
		
		$post_zones_id='';
		if($zones_id[0]->zones_id){
				foreach($zones_id as $ids){
					$zonesids.=$ids->zones_id.",";
				}
				$post_zones_id=str_replace(",","','",substr($zonesids,0,-1));
		}
		$zoneinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id=%d AND z.zones_id in ('".rtrim($post_zones_id,',')."')  order by zone_name ASC",$country_id));				
		do_action('lm_city_selection_dp');
		?>
		<div id="directory_location_navigation" class="d_location_type_navigation clearfix" >
		<div id="location_navigation_wrap">
               <div id="horizontal_header_location" class="d_location_navigation_left">
	               	<?php do_action('horizontal_dropdown_before_location_nav');?>
                    <ul class="horizontal_location_nav">
					<li>
						<a href="<?php echo get_bloginfo( 'url' )."?find_city=nearest"; ?>"><?php _e('My Nearest City',LDOMAIN);?></a>
					</li>
                    <?php if($location_options!='location_for_country' && $location_options!='location_for_cities' && isset($countryinfo)):?>
						<li>
                            <select name="header_country" id="header_country">
                                <option value=""><?php tmpl_frontend_select_country_option();?></option>
                                <?php foreach($countryinfo as $country): $selected=($country->country_id==$default_country_id)? 'selected':'';
										$country_name=$country->country_name;
										if (function_exists('icl_register_string')){
											icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
											$country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
										}?>
                                	<option value="<?php echo $country->country_id?>" <?php echo $selected;?>><?php echo $country_name;?></option>
                            	<?php endforeach; ?>
                            </select>
						</li>
					<?php endif;
					if($location_options!='location_for_cities'):?>
						<li>
                            <select name="header_zone" id="header_zone">
                                <option value=""><?php tmpl_frontend_select_state_option();?></option>
                                <?php foreach($zoneinfo as $zone): $selected=($zone->zones_id ==$zones_id)? 'selected':'';
										$zone_name=$zone->zone_name;
										if (function_exists('icl_register_string')) {
											icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
											$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
										}?>
									<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
                                <?php endforeach;?>
                            </select>
						</li>
					<?php endif;?>
						<li>
                            <form name="multicity_form" id="multicity_form" action="<?php echo home_url( '/' ); ?>" method="post">
                                <select name="header_city" id="header_city">
                                    <option value=""><?php tmpl_frontend_select_city_option();?></option>
                                    <?php
									if($country_id!='' && isset($zones_id) && $zones_id > 0 && $location_options != 'location_for_cities'){
										$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where country_id=%d and zones_id=%d order by cityname ASC",$country_id,$zones_id));
									}elseif($location_options=='location_for_cities'){
										$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta as pm,{$wpdb->prefix}posts as p where pm.post_id=p.ID AND p.post_status='publish' AND pm.meta_key = 'post_city_id'");
										if($city_ids[0]->city_ids){
											foreach($city_ids as $ids){
												$cityids.=$ids->city_ids.",";
											}
											$cityids=str_replace(",","','",substr($cityids,0,-1));

											
											/* fetch the all cities except empty */
												$cityinfo = $wpdb->get_results("SELECT  distinct  mc.city_id,c.country_id,c.country_name,mc.cityname as cityname, mc.city_slug as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 order by mc.cityname ASC");
										}
									}
									 /* Get the only one city then selected city by default */
									if(count($cityinfo)==1){
										$post_city_id=$cityinfo[0]->city_id;
									}
									foreach($cityinfo as $city): $selected=($city->city_id ==$default_city_id)? 'selected':'';
											$cityname=$city->cityname;
											if (function_exists('icl_register_string')) {
												$city_slug = str_replace(' ','-',strtolower($country_name));
												$country_name = icl_t('location-manager', 'location_country_'.$city_slug ,$country_name);
											}?>
                                    	<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname ;?></option>
                                    <?php endforeach;?>
                                </select>
                            </form>
						</li>
                    </ul>
                     <?php do_action('horizontal_dropdown_after_location_nav');?>
             </div>
		</div>
		</div>
     <?php
	 	do_action('lm_city_selection_dp_end');
	}else{
		/* Location set as link option selection */
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		/* Finish the  */
		$class_name=(has_nav_menu( 'primary' ))?'primary_location':'';
		$city_slug = str_replace(' ','-',strtolower( $current_cityinfo['cityname']));
		if (function_exists('icl_register_string')){
			$cityname = icl_t('location-manager', 'location_city_'.$city_slug, $current_cityinfo['cityname']);
		}else{
			$cityname = $current_cityinfo['cityname'];
		}
		do_action('lm_city_selection_link');
		?>
		<div id="directory_location_navigation" class="d_location_type_navigation clearfix" >
			<div id="location_navigation_wrap">
				<div id="horizontal_header_location" class="d_location_navigation_left"></div>
				<div id="location_loading" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></div>
			</div>
		</div>
		<?php
		do_action('lm_city_selection_link_end');
	} ?>
	</div>
	<?php
	do_action('lm_city_selection_end');
}
/*
 *Change the background colour as per a city selection. 
 */
add_action('after_body','nearest_location_set',9);
function nearest_location_set(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo,$wp_query;
	$location_tracking= get_option('default_city_set');
	if(is_ssl()){ $http = "https://"; }else{ $http ="http://"; } 
	if((is_home() || is_front_page()) && $location_tracking=='location_tracking')
	{
		if(!session_id())
			session_start();
		echo '<div id="nearest_city_load" style="display:none;"><p class="loading_msg"><i class="fa fa-circle-o-notch fa-spin"></i>';
		_e('Please wait, We are taking you to your nearest city.',LDOMAIN);
		echo '</p></div>';
		if(!isset($_COOKIE['c_latitude']) && !isset($_COOKIE['c_longitude'])): ?>
		<script  type="text/javascript" async src="<?php echo $http; ?>gmaps-samples-v3.googlecode.com/svn/trunk/geolocate/geometa.js"></script>
		<script  type="text/javascript" async >
		/* <![CDATA[*/
		function doGeolocation(){if(navigator.geolocation){navigator.geolocation.getCurrentPosition(positionSuccess,positionError)}else{positionError(-1)}}function positionError(e){var t;switch(e.code){case e.UNKNOWN_ERROR:t="Unable to find your location";break;case e.PERMISSION_DENINED:t="Permission denied in finding your location";break;case e.POSITION_UNAVAILABLE:t="Your location is currently unknown";break;case e.BREAK:t="Attempt to find location took too long";break;default:t="Location detection not supported in browser"}}onload=doGeolocation()
		function positionSuccess(position) {
		    /* Centre the map on the new location*/
		    var coords = position.coords || position.coordinate || position;
		    var c_latitude=coords.latitude;
		    var c_longitude=coords.longitude;
		    setCookie('c_latitude',c_latitude,1);
		    setCookie('c_longitude',c_longitude,1);
		    jQuery('#nearest_city_load').css('display','block');
		    jQuery.ajax({
				url:ajaxUrl,
				type:'POST',
				data:'action=nearest_location_redirect&c_latitude=' + c_latitude+'&c_longitude='+c_longitude,
				success:function(results) {
					window.location =results;
					return false;
				}
			});
		  }
		 /* ]]> */
		</script>
		<?php
		endif;		
	}
	location_current_multicity(); /* Set the multicity info*/
}
/*
 * Set the URL Of nearest city when user click on "My Nearest City"
 */
add_action('wp_ajax_nopriv_nearest_location_redirect','nearest_location_redirect');
add_action('wp_ajax_nearest_location_redirect','nearest_location_redirect');
function nearest_location_redirect(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo,$wp_query;
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	/*added condition to clear cache of supper cache plugin while change in city*/
	if(function_exists('wp_cache_clear_cache'))
	{
		wp_cache_clear_cache();
	}
	$lat = $_REQUEST['c_latitude'];
	$long = $_REQUEST['c_longitude'];
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	$sql="SELECT distinct city_id, cityname,city_slug FROM  $multicity_table, {$wpdb->prefix}postmeta WHERE meta_key='post_city_id' AND meta_value=city_id and  truncate((degrees(acos( sin(radians(lat)) * sin( radians('".$lat."')) + cos(radians(lat)) * cos( radians('".$lat."')) * cos( radians(lng - '".$long."') ) ) ) * 69.09),1) ORDER BY truncate((degrees(acos( sin(radians(lat)) * sin( radians('".$lat."')) + cos(radians(lat)) * cos( radians('".$lat."')) * cos( radians(lng - '".$long."') ) ) ) * 69.09),1) ASC LIMIT 0,1";
	$nearest_result=$wpdb->get_results($sql);
	$city_slug=$nearest_result[0]->city_slug;
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	/*Remove multicity slug from detail page breadcrumb */
	if($tevolution_taxonomies_data['tevolution_location_city_remove']==1){
		echo home_url().'/'.$city_slug;
	}else{
		echo home_url().'/'.$multi_city.'/'.$city_slug;
	}
	exit;
}
/*
 * Function Name: location_body_class
 * Return: add citywise background image
 */
add_filter('body_class','location_body_class',11,2);
function location_body_class($classes,$class){
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo,$wp_query;
	if($current_cityinfo['images']){
		$classes[]='city_image';
	}
	$classes[]='location_manager';
	return $classes;
}
/*save city base slug add in category page  permalink */
add_action('admin_init','save_permalink_set');
function save_permalink_set(){
	if(isset($_POST['tevolution_taxonimies_add'])){
		update_option('tev_lm_new_city_permalink',$_POST['tev_lm_new_city_permalink']);
	}
}
add_action('tev_before_permaliknk_frmrow','tev_before_permaliknk_frmrow_');

/* function to add permalink option */
function tev_before_permaliknk_frmrow_(){ 
	$prm= get_option('tev_lm_new_city_permalink');
	if($prm ==1){ $checked = "checked=checked"; }else{ $checked = ""; }
?>
	<tr>
		<th><?php echo __('City Base In Category Pages',LMADMINDOMAIN);?></th>
		<td>
		<div class="input-switch">
			<input type="checkbox" name="tev_lm_new_city_permalink" id="tev_lm_new_city_permalink" value="1" <?php echo $checked; ?>/>
			<label for="tev_lm_new_city_permalink"><?php echo __('Enable',LMADMINDOMAIN);?></label>
		</div>
		<p class="description"><?php echo __('Enabling this will include the city slug and city name inside category URLs, allowing you to link to a category inside a specific city.<br/> <strong>Do not enable this</strong> if your site has already been indexed by Google as those old links will lead to a 404 page.',LMADMINDOMAIN);?></p>
		</td>
	</tr>
<?php 
}
/* City Message Short code */
add_shortcode('city_message','tmpl_lm_add_city_message');
function tmpl_lm_add_city_message( $atts){
	global $current_cityinfo;
	if( $current_cityinfo['message']){
		return $current_cityinfo['message'];
	}
}

/* add plugin's fileds for exlude in "show on " option in backend */
add_filter('exclude_show_fields','tmpl_location_custom_fileds',10,2);
function tmpl_location_custom_fileds($exclude_show_fields,$htmlvar_name){

	$exclude_show_fields = array_values(array_filter(array_merge($exclude_show_fields, array("map_view"))));
	return $exclude_show_fields;
}

/* craete json data for city wise category */
//add_action('init','tmpl_create_json');

function tmpl_create_json(){
	global $wpdb;
	$multicity = $wpdb->prefix . "multicity";
	$cities = $wpdb->get_results("select * from  $multicity");
	$data = file_get_contents('http://localhost/demos2/city_cat_listings.json');
	
	if(empty($data) && filesize('F:/xampp/htdocs/demos2/city_cat_listings.json') <= 0){
		for($c =0 ; $c <= count($cities); $c++){
		
		$city_id = $cities[$c]->city_id;
		if($city_id !=''){
			$category = $wpdb->get_row("select categories from  $multicity where city_id = {$city_id}");
			$categories = explode(',',$category->categories);
			
				for($cat=0; $cat < count($categories ); $cat++){ 
				
					$catid = trim(str_replace(',','',$categories[$cat]));
					
					if($catid !=''){
						$args = array(
							'post_type' => 'listing',
							'posts_per_page' =>-1,
							'meta_query' => array(
								'relation'=>'OR',
									array(
										'key'     => 'post_city_id',
										'value'   => ",".$city_id.",",
										'compare' => 'LIKE',
									),
									array(
										'key'     => 'post_city_id',
										'value'   => $city_id.",",
										'compare' => 'LIKE',
									),
									array(
										'key'     => 'post_city_id',
										'value'   => ",".$city_id,
										'compare' => 'LIKE',
									),
									array(
										'key'     => 'post_city_id',
										'value'   => $city_id,
										'compare' => '==',
									),
								),
							'tax_query' => array(
									array(
										'taxonomy' => 'listingcategory',
										'field'    => 'term_id',
										'terms'    => array($catid),
									)
							),
						);
						
						$city_data_details = new WP_Query( $args );
						$termdetails = get_term($catid,'listingcategory');
						$postcount = array('count'=> $city_data_details->found_posts,'term_name'=>$termdetails->name,'slug'=> $termdetails->slug,'term_icon' => $termdetails->trem_icon);
						$cat_id[$catid] = apply_filters('tmpl_json_posts',$postcount);
						/* 
						if($city_data_details->have_posts()){
							while($city_data_details->have_posts()){ 
								global $post;
								$city_data_details->the_post();
								$postdata[$post->ID] = array('postid'=> $post->ID,'post_title'=>$post->post_title,'address' => get_post_meta($post->ID,'address',true));
							} wp_reset_postdata();
							
						}
						 */
					}
					
				}
			$listings[$city_id] = $cat_id;
			$ld = json_encode($listings);
			$file = fopen('F:/xampp/htdocs/demos2/city_cat_listings.json','w');
			fwrite($file,$ld);
			fclose('F:/xampp/htdocs/demos2/city_cat_listings.json');
		}
		}
	}	
}
?>