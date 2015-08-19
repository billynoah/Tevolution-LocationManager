<?php
/*
 * This file use for set multicity wise query in frontend 
 *
 * Check current detail page in given city if current detail post not in given city url slug then redirect to home page
 */
add_action('wp_head','location_multicity_get_posts',0);
function location_multicity_get_posts($query){
	global $wp_query, $post,$current_cityinfo,$wpdb;
	remove_action('parse_query', 'tmpl_location_parse_query');
	$multicity_table = $wpdb->prefix . "multicity";
	if(!is_admin() && is_single() && get_post_type()!='' && get_post_type()!='post' && get_post_status()=='publish'){
		$location_post_type=implode(',',get_option('location_post_type'));
		$city_slug=get_option('location_multicity_slug');	
		$multi_city=($city_slug)? $city_slug : 'city';
		if (strpos($location_post_type,','.get_post_type()) !== false) {
			$multi_citry_id=explode(',',get_post_meta($post->ID,'post_city_id',true));			
			$current_city=get_query_var($multi_city);			
			$sql=$wpdb->prepare("SELECT city_id FROM $multicity_table where city_slug=%s",urldecode(@$current_city));
			
			$default_city = $wpdb->get_results($sql);
			$is_default_city = 0;			
			for($i=0;$i<count($default_city);$i++)
			{
				if (!in_array($default_city[$i]->city_id,$multi_citry_id)){			
					$is_default_city = 1;
				}else{
					$is_default_city=0;
					break;
				}
			}
			if($is_default_city==1)
			{
				wp_redirect(site_url());
				exit;
			}
		}
	}
}
/*
 * This function use for add query where and query ordre by related filter in homepage, category page, detail page and author dashbord page.
 */
add_action('pre_get_posts','location_pre_get_posts',12);
function location_pre_get_posts($query){
	global $wp_query, $post,$current_cityinfo;	
	$as_posts_page = get_option('page_for_posts');
	$queried_object = get_queried_object();
	if(!is_admin()){
		$tmpdata = get_option('templatic_settings');
		/* its only for archive category and tag listing page */
		if((is_archive() || is_category() || is_tax() || $query->is_posts_page ==1) && (!is_search() && !is_author() && (!is_home() || $query->is_posts_page ==1) && !is_page() && !is_single())){
			/*
			 * Set the flag if any post type set as a location wise display on archive ,category and tag listing page
			 * if Tevolution home listing type value blank then check by default wordpress post type
			 */
			
			$flg=0;

			if(count(get_option('location_post_type'))> 1){ 
				$location_post_type= implode(',',get_option('location_post_type'));
			}else{ 
				$location_post_type= get_option('location_post_type');
				if(!empty($location_post_type)){
					$location_post_type = $location_post_type[0];
				}
			}
			
			if($query->is_posts_page ==1 && in_array('post',explode(',',$location_post_type))){
				$flg=1;
			}
			if(isset($query->query_vars['post_type']) && $query->query_vars['post_type']!='' && !empty($location_post_type)){
				/* check post types is set for locations or not */
				if($query->query_vars['post_type']){
					if (strstr($location_post_type,$query->query_vars['post_type'])) {
					   $flg=1;
					}
				}
			}else{
				if(!empty($location_post_type)){
					if (strstr($location_post_type,",".$query->tax_query->queries[0]['taxonomy']) && $query->tax_query->queries[0]['taxonomy']!='') {
					   $flg=1;
					}		
				}
			}
			/*
			 * if flag is set then multi city location where filter apply
			 */
			if($flg==1){
				location_current_multicity();
				add_filter('posts_where', 'location_multicity_where');
			}
		}		
		if(isset($_REQUEST['search_template']) && $_REQUEST['search_template']==1 && is_search())
		{
			add_filter('posts_where', 'advance_search_template_city_where');	
		}
		
		/* Is home page only */		
		if(is_home() && $query->is_posts_page !=1)
		{	
		
			/*
			 * Set the flag if any post type set as a location wise display on home page.
			 * if Tevolution home listing type value blank then check by default wordpress post type
			 */
			$flg=0;
			
			$location_posttype=get_option('location_post_type');			
			for($j=0;$j<count($location_posttype);$j++){
				
					$location_type = explode(',',$location_posttype[$j]);					
					$location_post_type.=$location_type[0].',';
			}
			if($query->query_vars['post_type']!='' && is_array($tmpdata['home_listing_type_value'])){
				$home_listing_type_value = implode(',',@$tmpdata['home_listing_type_value']);				
				if (strpos($location_post_type,$home_listing_type_value) !== false) {
				   $flg=1;
				}
			}else{
				if (strpos($location_post_type,$query->query_vars['post_type']) !== false) {
				   $flg=1;
				}
			}
			/*
			 * if flag is set then multicity location where filter apply
			 */
			if($flg==1){
				add_filter('posts_where', 'location_multicity_where');
			}
				
		}
	}
}
/* multi city set then concate multicity query in query where filter */
function location_city_filter($where){
	global $wpdb,$cityid;
	if($cityid!=''){
		$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$cityid.", pm.meta_value ))";
	}
	return $where;
}
function location_multicity_where($where){
	
	global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table,$current_cityinfo,$wp_query;	
	$city_slug=get_option('location_multicity_slug');	
	$multi_city=($city_slug)? $city_slug : 'city';
	/* latest post -page start */
	if(is_home() ){	/* in home page city could not be save we should refresh the page*/
		
		$multicity_table = $wpdb->prefix . "multicity";	
		if($wpdb->get_var("SHOW TABLES LIKE '$multicity_table'") == $multicity_table) {
			if(get_query_var($multi_city)){			
				$sql="SELECT * FROM $multicity_table where city_slug='".get_query_var($multi_city)."'";
			}elseif(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!=''){
				if(get_query_var($multi_city)!='')
					$sql=$wpdb->prepare("SELECT * FROM $multicity_table where city_slug=%s",get_query_var($multi_city));
				else
					$sql=$wpdb->prepare("SELECT * FROM $multicity_table where city_id=%d",$_SESSION['post_city_id']);
				
			}else{			
				$sql="SELECT * FROM $multicity_table where is_default=1";		
			}
		}
		$default_city = $wpdb->get_results($sql);
		$default_city_id=$default_city[0]->city_id;
		
		if($default_city_id!=''){
			$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$default_city_id.", pm.meta_value ))";
		}
	/* latest post -page end */
	}else{
		
		if($current_cityinfo['city_id']!=''){
			$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$current_cityinfo['city_id'].", pm.meta_value ))";
		}
		elseif($_SESSION['post_city_id']!='')
		{
			$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$_SESSION['post_city_id'].", pm.meta_value ))";
		}
	}
	return $where;
}
/*
 * Near by location where filter return
 */
function location_related_posts_where_filter($where){
	global $wpdb,$wp_query,$post,$current_cityinfo;
	$search = get_post_meta($post->ID,'address',true);	
	
	if($current_cityinfo['city_id']!=''){
		$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$current_cityinfo['city_id'].", pm.meta_value ))";
	}
	if($search!=""){		
		$lat =get_post_meta($post->ID,'geo_latitude',true);
		$long = get_post_meta($post->ID,'geo_longitude',true);
		
		$tmpdata = get_option('templatic_settings');
		$distance_type=$tmpdata['distance_type'];
		$miles = $tmpdata['related_radius'];
		if($miles ==''){ $miles ='1000'; }		
		
		/* Convert miles to kilometer*/
		if($distance_type== strtolower('Kilometer')){
			$miles = @$miles / 0.621;
		}else{
			$miles = @$miles;	
		}
		$tbl_postcodes = $wpdb->prefix . "postcodes";		
	
		if($search){
			if($long !='' && $lat !=''){				
				
				$sql="SELECT post_id FROM  $tbl_postcodes WHERE post_id!=".$post->ID." AND post_type='".$post->post_type."' AND truncate((degrees(acos( sin(radians(`latitude`)) * sin( radians('".$lat."')) + cos(radians(`latitude`)) * cos( radians('".$lat."')) * cos( radians(`longitude` - '".$long."') ) ) ) * 69.09),1) <= ".$miles." ORDER BY truncate((degrees(acos( sin(radians(`latitude`)) * sin( radians('".$lat."')) + cos(radians(`latitude`)) * cos( radians('".$lat."')) * cos( radians(`longitude` - '".$long."') ) ) ) * 69.09),1) ASC";
				$result=$wpdb->get_results($sql);
				$post_id='';
				foreach($result as $val){
					$post_id.=$val->post_id.",";	
				}	
				if($post_id !=''){
					$where .= " AND ($wpdb->posts.ID in (".substr($post_id,0,-1)."))";	
				}				
			}
		}	
	}	
	
	return $where;
}
/*
 * Include multilocation post where filter on post listing widget
 */
add_action('post_listing_widget_before_post_where','location_post_listing_post_where');
function location_post_listing_post_where($instance){	
	$location_post_type=implode(',',get_option('location_post_type'));
	if (strpos($location_post_type,$instance['post_type']) !== false) {
	 	add_filter('posts_where', 'location_multicity_where');
	}
}
add_action('post_listing_widget_after_post_where','remove_location_post_listing_post_where');
function remove_location_post_listing_post_where($instance){	
	$location_post_type=implode(',',get_option('location_post_type'));
	if (strpos($location_post_type,$instance['post_type']) !== false) {
	 	remove_filter('posts_where', 'location_multicity_where');
	}
}

/*
 * Search country and state wise
 */
function advance_search_template_city_where($where)
{
	global $wpdb;
	if(isset($_REQUEST['adv_country']) && $_REQUEST['adv_country']!= '')
	{
		$adv_country = $_REQUEST['adv_country'];
		$where .= " AND ($wpdb->posts.ID in (select $wpdb->postmeta.post_id from $wpdb->postmeta where $wpdb->postmeta.meta_key='country_id' and ($wpdb->postmeta.meta_value  = $adv_country ))) ";					
	}
	if(isset($_REQUEST['adv_zone']) && $_REQUEST['adv_zone']!= '')
	{
		$adv_zone = $_REQUEST['adv_zone'];
		$where .= " AND ($wpdb->posts.ID in (select $wpdb->postmeta.post_id from $wpdb->postmeta where $wpdb->postmeta.meta_key='zones_id' and ($wpdb->postmeta.meta_value  = $adv_zone ))) ";					
	}
	return $where;
}



/*
 * Join city wise query for display previous and next single post link on city wise.
 */
add_filter('get_previous_post_join','location_single_post_pagination',10,3);
add_filter('get_next_post_join','location_single_post_pagination',10,3);
function location_single_post_pagination($join, $in_same_term, $excluded_terms){
	global $wpdb,$current_cityinfo;
	$tvalue = get_option('location_post_type');
	if(!empty($tvalue)){
		$location_post_type=explode(',',implode(',',get_option('location_post_type')));
		if (in_array(get_post_type(),$location_post_type) && !empty($current_cityinfo) && $current_cityinfo['city_id']!='') {
			$join .= " JOIN $wpdb->postmeta m ON p.ID=m.post_id AND m.meta_key ='post_city_id' AND FIND_IN_SET( ".$current_cityinfo['city_id'].", m.meta_value )";
		}
	}
	return $join;
}
?>