<?php
/*
 * This file use  for multicity wise permalink set in categort and detail page and as weel set city permaink in home page
 */
/* add  city wise permalink*/
if(get_option('tev_lm_new_city_permalink') == 1){
	add_action('init', 'templatic_add_rewrite_city_rules',10);
}else{
	add_action('init', 'templatic_add_rewrite_rules',10);
}

/*
 * permalink rule - return permalink with city slug inside category link and detail link
 */
function templatic_add_rewrite_city_rules() {
	global $wp_rewrite,$wpdb;
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	
	$wp_rewrite->add_rewrite_tag('%city%', '([^/]+)', $multi_city.'=');
	$pid = get_option('default_comments_page');
	if(!get_option('permalink_autoupdate')){
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name like '%s'",'%_tevolution_query_%' ));
		update_option('permalink_autoupdate',1);
	}
	if($pid =='last'){ $pid ='1'; }else{ $pid ='1';}
	$location_post_type=get_option('location_post_type');
	$tevolution_taxonomies=get_option('templatic_custom_taxonomy');

	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	if($location_post_type!='' ||!empty($location_post_type)){
		foreach($location_post_type as $post_type){
			$posttype=explode(',',$post_type);
			$wp_rewrite->add_rewrite_tag('%'.$posttype[0].'%', '([^/]+)', $posttype[0].'=');
			/*Remove city base slug */
			$wp_rewrite->add_permastruct($posttype[0], '/'.$multi_city.'/%city%/'.$posttype[0].'/%'.$posttype[0].'%', false);

			$category_slug=@$tevolution_taxonomies[$posttype[1]]['rewrite']['slug'];
			if($posttype[0]=='post'){
				$category_slug='category';
			}
			if($category_slug==""){
				$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $posttype[0],'public'   => true, '_builtin' => true ));
				$category_slug=$taxonomies[0];
			}
			/*Remove city base slug */
			$wp_rewrite->add_permastruct($posttype[1], '/'.$multi_city.'/%city%/'.$category_slug.'/%'.$posttype[1].'%', false);
		}
		$wp_rewrite->flush_rules();
	}
}
/*
 * permalink rule - return permalink  city slug only detail page
 */
function templatic_add_rewrite_rules() {
	global $wp_rewrite,$wp_query,$post,$pagenow;
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	$wp_rewrite->add_rewrite_tag('%city%', '([^/]+)', $multi_city.'=');
	$pid = get_option('default_comments_page');
	if($pid =='last'){ $pid ='1'; }else{ $pid ='1';}
	$location_post_type=get_option('location_post_type');
	$location_post_type=array_merge($location_post_type, array('attachment,attachement'));
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	if($location_post_type!='' ||!empty($location_post_type)){
		foreach($location_post_type as $post_type){
			$posttype=explode(',',$post_type);
			$wp_rewrite->add_rewrite_tag('%'.$posttype[0].'%', '([^/]+)', $posttype[0].'=');
			if($pagenow !='upload.php' && $post->post_type !='attachment')
			$wp_rewrite->add_permastruct($posttype[0], '/'.$multi_city.'/%city%/'.$posttype[0].'/%'.$posttype[0].'%', false);
		}
		$wp_rewrite->flush_rules();
	}
}

/*
 * Return set custom post type archive page as per location wise rewrite rules
 */
add_filter('rewrite_rules_array','location_archive_filter_rewrite_rules');
function location_archive_filter_rewrite_rules($rewrite_rules){
	global $current_cityinfo,$wpdb,$pagenow;
	
	$multicity_table = $wpdb->prefix . "multicity";
	$location_post_type=get_option('location_post_type');
	if(((isset($_REQUEST['page']) && $_REQUEST['page']=='templatic_system_menu') || is_admin()) && ($_REQUEST['page']=='templatic_settings' && $pagenow!='options-permalink.php' )){
		return $rewrite_rules;
	}
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	if($location_post_type!='' || !empty($location_post_type)){
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		$sql=$wpdb->prepare("SELECT * FROM $multicity_table where is_default=%d",1);
		$default_city = $wpdb->get_results($sql);
		if(!empty($default_city)){
			$city=(isset($current_cityinfo['city_slug']) && $current_cityinfo['city_slug']!='')? $current_cityinfo['city_slug'] :$default_city[0]->city_slug;
		}
		foreach($location_post_type as $post_type){
			$posttype=explode(',',$post_type);
			if(@$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
				unset($rewrite_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/?$']);
				unset($rewrite_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/feed/(feed|rdf|rss|rss2|atom)/?$']);
				unset($rewrite_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/(feed|rdf|rss|rss2|atom)/?$']);
				unset($rewrite_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/page/([0-9]{1,})/?$']);

				$new_archive_rules['([^/]+)/'.$posttype[0].'/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0];
				$new_archive_rules['([^/]+)/'.$posttype[0].'/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&feed=$matches[2]';
				$new_archive_rules['([^/]+)/'.$posttype[0].'/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&feed=$matches[2]';
				$new_archive_rules['([^/]+)/'.$posttype[0].'/page/([0-9]{1,})/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&paged=$matches[2]';
			}else{
				unset($rewrite_rules['([^/]+)/'.$posttype[0].'/?$']);
				unset($rewrite_rules['([^/]+)/'.$posttype[0].'/feed/(feed|rdf|rss|rss2|atom)/?$']);
				unset($rewrite_rules['([^/]+)/'.$posttype[0].'/(feed|rdf|rss|rss2|atom)/?$']);
				unset($rewrite_rules['([^/]+)/'.$posttype[0].'/page/([0-9]{1,})/?$']);

				$new_archive_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0];
				$new_archive_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&feed=$matches[2]';
				$new_archive_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&feed=$matches[2]';
				$new_archive_rules[$multi_city.'/([^/]+)/'.$posttype[0].'/page/([0-9]{1,})/?$'] = 'index.php?'.$multi_city.'=$matches[1]&post_type='.$posttype[0].'&paged=$matches[2]';
			}			
		}
                $rewrite_rules = array_merge($new_archive_rules,$rewrite_rules);
	}
	/*remove city base home page slug in home page permalink */
	if(is_plugin_active('Tevolution-LocationManager/location-manager.php') && @$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
		$remove_city_base=@$tevolution_taxonomies_data['tevolution_location_city_remove'];
		if ( false === ( $new_rules = get_transient( '_tevolution_query_cityslug_remove')) && get_option('tevolution_cache_disable')==1 ) {
			$multicity_table = $wpdb->prefix . "multicity";
			$city_slugs = $wpdb->get_results("SELECT city_slug FROM $multicity_table");
			foreach($city_slugs as $slug){
				$new_rules[$slug->city_slug.'/?$']='index.php?'.$multi_city.'='.$slug->city_slug;				
			}
			set_transient( '_tevolution_query_cityslug_remove', $new_rules, 12 * HOUR_IN_SECONDS );				
		}elseif(get_option('tevolution_cache_disable')==''){
			$multicity_table = $wpdb->prefix . "multicity";
			$city_slugs = $wpdb->get_results("SELECT city_slug FROM $multicity_table");
			foreach($city_slugs as $slug){
				$new_rules[$slug->city_slug.'/?$']='index.php?'.$multi_city.'='.$slug->city_slug;				
			}
		}		
                if(is_array($new_rules)){
			$rewrite_rules = array_merge($new_rules,$rewrite_rules);
		}
     	/*unset rewrite rules for remove city slug */
		unset($rewrite_rules['([^/]+)/?$']);
	}
	return $rewrite_rules;
}

/*
 * Return add locaton city slug on archive page
 */
add_filter('post_type_archive_link','templatic_create_archive_permalinks',10,2);
function templatic_create_archive_permalinks( $link, $post_type){
	global $current_cityinfo,$wpdb;
	
	$multicity_table = $wpdb->prefix . "multicity";
	if((isset($_REQUEST['page']) && $_REQUEST['page']=='templatic_system_menu') || get_option( 'permalink_structure' )==''){
		return $link;
	}
	$location_post_type=implode(',',get_option('location_post_type'));
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	if (strpos($location_post_type,','.$post_type) !== false) {
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		$sql=$wpdb->prepare("SELECT * FROM $multicity_table where is_default=%d",1);
		$default_city = $wpdb->get_results($sql);
		$city=(isset($current_cityinfo['city_slug']) && $current_cityinfo['city_slug']!='')? $current_cityinfo['city_slug'] :$default_city[0]->city_slug;
		/*remove city slug on archive page permalink */
		if(@$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
			$link=get_bloginfo('url')."/".$city."/".$post_type;	
		}else{
			$link=get_bloginfo('url')."/".$multi_city."/".$city."/".$post_type;
		}
	}
	return $link;
}

/* Set the city permalink for category listing page */
add_filter('category_link','templatic_create_category_permalinks',10,3);
function templatic_create_category_permalinks($termlink, $term){
	global $current_cityinfo;
	if(isset($current_cityinfo['city_slug']) && $current_cityinfo['city_slug']!=''){
		$city=$current_cityinfo['city_slug'];
	}else{
		$city='na';
	}
	$termlink = str_replace(array('%city%'), array($city), $termlink);
	return $termlink;
}
/* Set the city permalink for taxonomies listing page */
add_filter('term_link','templatic_create_term_permalinks',10,3);
function templatic_create_term_permalinks($termlink, $term, $taxonomy){
	global $current_cityinfo;	
	if(isset($current_cityinfo['city_slug']) && $current_cityinfo['city_slug']!=''){
		$city=$current_cityinfo['city_slug'];
	}else{
		$city='na';
	}
	$termlink = str_replace(array('%city%'), array($city), $termlink);
	return $termlink;
}
/*
 * it will return the permalink with city name for all type of listings e.g. listing,events etc.
 * Note: this will not work with posts.
 */
add_filter('post_type_link', 'templatic_create_permalinks', 10, 3);
function templatic_create_permalinks($permalink, $post, $leavename) {
	
	global $current_cityinfo,$wpdb,$city_info;
	$no_data = 'no-data';
	$post_id = $post->ID;
	$post_city_id = get_post_meta($post->ID,'post_city_id',true);
	$pcity_id = apply_filters('city_permalink_slug',$post_city_id );
	if(($post->post_type != '' && $pcity_id!='') && ( empty($permalink) || in_array($post->post_status, array('draft', 'pending', 'auto-draft'))))
		return $permalink;
	
	/*get the post_city_id on post id*/
	$pcity_id = $post_city_id;
	$multicity_db_table_name = $wpdb->prefix . "multicity"; /* DATABASE TABLE  MULTY CITY*/
	$pcity_id = $post_city_id;
	if(strstr($pcity_id,',')){
		$pcity_id_ = explode(',',$pcity_id);
		$pcity_id = $pcity_id_[0];
	}
	
	if(!is_admin() && !empty($pcity_id_) && is_array($pcity_id_) && in_array($current_cityinfo['city_id'],$pcity_id_) && strpos($_SERVER['REQUEST_URI'],'sitemap') === false && strpos($_SERVER['REQUEST_URI'],'feed') === false){
		$pcity_id=$current_cityinfo['city_id'];
	}
	
	/* check if city is exists or not */
	$is_pcity_exists = $wpdb->get_var("SELECT city_slug FROM $multicity_db_table_name WHERE city_id =\"$pcity_id\"");
	
	
	if($pcity_id!='' && $is_pcity_exists){
		/* customization start for global location urls */
		if(is_plugin_active('Directory-GlobalLocation/directory-globallocation.php')){
			$city = strtolower($wpdb->get_var("SELECT city_slug FROM $multicity_db_table_name WHERE city_id =\"$pcity_id\""));
		}else{ /* customization end */
			/* after instant search the url of mulitple selected city post does not return the url of currently selected city added the  'tevolution_autocomplete_callBack' in condition*/
			if(((!is_front_page() && !is_home() )&& (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || is_singular() || is_search() || (isset($_REQUEST['page']) && $_REQUEST['page']=='success') || strpos($_SERVER['REQUEST_URI'],'sitemap') !== false  || strpos($_SERVER['REQUEST_URI'],'feed') !== false))  && (is_admin() && @$_REQUEST['action'] != 'tevolution_autocomplete_callBack' && @$_REQUEST['action'] != 'googlemap_initialize') || strpos($_SERVER['REQUEST_URI'],'sitemap') !== false)
			{
				$city = strtolower($wpdb->get_var("SELECT city_slug FROM $multicity_db_table_name WHERE city_id =\"$pcity_id\""));
			}elseif(is_search() || (is_admin() && @$_REQUEST['action'] == 'tevolution_autocomplete_callBack')){
				$city = strtolower($wpdb->get_var("SELECT city_slug FROM $multicity_db_table_name WHERE city_id =\"$pcity_id\""));
			}
			else{
				$city = ($current_cityinfo['city_slug']!='' && !is_author())? $current_cityinfo['city_slug']:strtolower($wpdb->get_var("SELECT city_slug FROM $multicity_db_table_name WHERE city_id =\"$pcity_id\""));
			}
		}
	}else{
		$city = 'na';
	}
	$city_slug=get_option('location_multicity_slug');	
	$multi_city=($city_slug)? $city_slug : 'city';
	
	/* get the settings from custom permalink settings */
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	if(@$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
		
		$permalink = str_replace('%city%', $city, $permalink);
		$permalink = str_replace('/'.$multi_city.'/', '/', $permalink);
	}else{
		$permalink = str_replace('%city%', $city, $permalink);
	}
	return $permalink;
}
/* Commnet post redirect link with location manager*/
add_filter('comment_post_redirect', 'redirect_after_comment');

/*
 *  Redirect on same listing page afetr post the comment ( With location manager city permalink)
 */
function redirect_after_comment($location)
{
	global $wpdb;
	$pid = get_option('default_comments_page');
	$pid =($pid =='last')? '1': '2';
	return $_SERVER["HTTP_REFERER"]."/#comment-".$wpdb->insert_id;
}


/*
 * return city wise category link in wordpress seo sitemap xml
 */
add_filter('wpseo_sitemap_entry','location_manager_wpseo_sitemap_entry',10,3);
function location_manager_wpseo_sitemap_entry($url, $term, $c){

          $location_post_type=','.implode(',',get_option('location_post_type'));
          if($term=='term' &&  strpos($location_post_type,','.$c->taxonomy) !== false){
                    if($c->parent!=0){
                              $ancestors=location_term_ancestors($c->taxonomy,$c->parent)."/";
                    }else{
                              $ancestors="";
                    }
                    /*check tevolution taxonomies slug  */
                    $tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
                    if(!$tevolution_taxonomies_data['tevolution_taxonimies_remove'][$c->taxonomy]){
                              /* check taxonomies slug change */
                              $taxonomy_name=($tevolution_taxonomies_data['tevolution_taxonimies_add'][$c->taxonomy]!='')? $tevolution_taxonomies_data['tevolution_taxonimies_add'][$c->taxonomy] : $c->taxonomy;
                              /* city slug enable on category page then add city slug in categpry permalink in site map xml */
                              if(get_option('tev_lm_new_city_permalink') == 1){
                                        $url['loc']= get_bloginfo('url').$c->multicity.$taxonomy_name.'/'.$ancestors.$c->slug;
                              }else{
                                        $url['loc']= get_bloginfo('url')."/".$taxonomy_name.'/'.$ancestors.$c->slug;
                              }
                    }else{
                              if(get_option('tev_lm_new_city_permalink') == 1){
                                        $url['loc']= get_bloginfo('url').$c->multicity.$ancestors.$c->slug;
                              }else{
                                        $url['loc']= get_bloginfo('url')."/".$ancestors.$c->slug;
                              }
                    }
          }		
          return $url;
}


/*
 * return parent child slug
 */
function location_term_ancestors($tax,$id){
	$term=get_term($id,$tax);
	$ancestor=$term->slug;
	
	if($term->parent!=0){
		$ancestor=location_term_ancestors($tax,$term->parent)."/".$ancestor;
	}
	return $ancestor;
}

/*
 * multipal city wise generate custom taxonomy standclass object array
 */
add_filter('get_terms','yoast_seo_get_terms',10,3);
function yoast_seo_get_terms($terms, $taxonomies, $args ){
	global $wpdb;
	if(is_array(get_option('location_post_type')))
		$location_post_type=','.implode(',',get_option('location_post_type'));
	else
		$location_post_type=','.get_option('location_post_type');
		
	if(strpos($_SERVER['REQUEST_URI'],'sitemap') !== false && get_option('tev_lm_new_city_permalink') == 1 && strpos($location_post_type,','.$taxonomies[0]) !== false){
		$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
		global $current_cityinfo,$wpdb;
		$multicity_table = $wpdb->prefix . "multicity"; 
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';

		$sql="SELECT city_slug,city_id FROM $multicity_table";
		$seo_city = $wpdb->get_results($sql);
		$i=0;
		foreach ($terms as $term) {
			foreach ($seo_city as $key => $value) {
				$sql="select count(*) as count from $wpdb->posts p, $wpdb->postmeta m, $wpdb->term_relationships tr where p.post_status='publish' AND p.ID=m.post_id and m.meta_key='post_city_id' AND FIND_IN_SET( ".$value->city_id.", m.meta_value ) and p.ID=tr.object_id AND tr.term_taxonomy_id in (".$term->term_id.")";
				$result = $wpdb->get_results($sql);
				if($result[0]->count!=0){
					$new_terms=(array)($term);
					if(@$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
						$new_terms['multicity'] = "/".$value->city_slug."/";
					}else{
						$new_terms['multicity'] = "/".$multi_city."/".$value->city_slug."/";
					}
					$newterms[$i]=(object)$new_terms;
				}
				$i++;
			}
		}
		return $newterms;
	}
	return $terms;
}

/*
 * multicity wise generate taxonomies link
 */
add_filter('term_link','tmpl_category_sitemap_xml',10,3);
function tmpl_category_sitemap_xml($termlink, $term, $taxonomy ){

	if(is_array(get_option('location_post_type')))
		$location_post_type=','.implode(',',get_option('location_post_type'));
	else
		$location_post_type=','.get_option('location_post_type');

	if(strpos($_SERVER['REQUEST_URI'],'sitemap') !== false && get_option('tev_lm_new_city_permalink') == 1 && strpos($location_post_type,','.$taxonomy) !== false ){

		if($term->parent!=0){
			$ancestors=location_term_ancestors($term->taxonomy,$term->parent)."/";
		}else{
			$ancestors="";
		}

		/*check tevolution taxonomies slug  */
		$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
		if(!$tevolution_taxonomies_data['tevolution_taxonimies_remove'][$term->taxonomy]){
			/* check taxonomies slug change */
			$taxonomy_name=($tevolution_taxonomies_data['tevolution_taxonimies_add'][$term->taxonomy]!='')? $tevolution_taxonomies_data['tevolution_taxonimies_add'][$term->taxonomy] : $term->taxonomy;
			if(get_option('tev_lm_new_city_permalink') == 1){
				$termlink= get_bloginfo('url').$term->multicity.$taxonomy_name.'/'.$ancestors.$term->slug;
			}else{
				$termlink=get_bloginfo('url')."/".$taxonomy_name.'/'.$ancestors.$term->slug;
			}
		}else{
			if(get_option('tev_lm_new_city_permalink') == 1){
				$termlink= get_bloginfo('url').$term->multicity.$ancestors.$term->slug;
			}else{
				$termlink= get_bloginfo('url')."/".$ancestors.$term->slug;
			}
		}
	}/* end sitemap  if condition*/
	return $termlink;
}
/*
 * generate home page url multipal citywise in index sitemap xml
 */
add_action('sm_buildmap','tmpl_google_sitemap_home_url');
function tmpl_google_sitemap_home_url(){
	global $current_cityinfo,$wpdb,$gsg;
	$tevolution_taxonomies_data=get_option('tevolution_taxonomies_rules_data');
	$gsg= &GoogleSitemapGenerator::GetInstance();
	$multicity_table = $wpdb->prefix . "multicity";
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';

	$sql="SELECT city_slug FROM $multicity_table";
	$seo_city = $wpdb->get_var($sql);
	$i=0;
	foreach ($seo_city as $key => $value){
		if(@$tevolution_taxonomies_data['tevolution_location_city_remove']==1){
			$home = get_bloginfo('url')."/".$value->city_slug."/";
		}else{
			$home = get_bloginfo('url').'/'.$multi_city."/".$value->city_slug."/";
		}

		if('page' == get_option('show_on_front') && get_option('page_on_front')){
			$pageOnFront = get_option('page_on_front');
			$p = get_post($pageOnFront);
			if($p){
				echo $home;
				$gsg->AddUrl(trailingslashit($home), $gsg->GetTimestampFromMySql(($p->post_modified_gmt && $p->post_modified_gmt != '0000-00-00 00:00:00'
						? $p->post_modified_gmt
						: $p->post_date_gmt)), $gsg->GetOption("cf_home"), $gsg->GetOption("pr_home"));
			}
		} else {
			$gsg->AddUrl(trailingslashit($home), ($lm ? $gsg->GetTimestampFromMySql($lm)
					: time()), $gsg->GetOption("cf_home"), $gsg->GetOption("pr_home"));
		}
		$i++;
	}
}

/*this function return query vars variable for multicity slug */
function add_query_vars($aVars){
	$city_slug=get_option('location_multicity_slug');
	$multi_city=($city_slug)? $city_slug : 'city';
	/*represents the name of the product category as shown in the URL*/
	$aVars[] = $multi_city;
	return $aVars;
}
add_filter('query_vars', 'add_query_vars',99);

/* This function use for front page to get the front page */
add_action('parse_query','tmpl_location_parse_query');
function tmpl_location_parse_query($qv){
	$as_posts_page = get_option('page_for_posts');
	$queried_object = get_queried_object();
	if ( !is_single() && $qv->is_home && 'page' == get_option('show_on_front') && get_option('page_on_front') && !isset($_REQUEST['page']) && !defined( 'DOING_AJAX' )) {

		if($as_posts_page && $queried_object->ID == $as_posts_page){
			$qv->is_home = true;
			$qv->is_singular = false;
			$qv->is_posts_page = true;
		}else{
			$qv->is_page = true;
			$qv->is_home = false;
			$qv->is_singular = true;
			$qv->query_vars['page_id'] = get_option('page_on_front');
		}
	}
}
?>