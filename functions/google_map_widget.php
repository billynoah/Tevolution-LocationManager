<?php
/* Home page multicity widget */

add_action('widgets_init','directory_googlemap_widgets_init');
function directory_googlemap_widgets_init(){
	register_widget('widget_googlemap_homepage');
}
/* BOF - Home page Google map widget - FOr multicity */
class widget_googlemap_homepage extends WP_Widget {
	function widget_googlemap_homepage() {	
		$widget_ops = array('classname' => 'widget Google Map in Home page', 'description' => __('Display a Google map with custom icons and marker clusters while operating multiple cities. Widget works best inside the Homepage Slider or Homepage - Main Content area.','templatic-admin') );		
		$this->WP_Widget('googlemap_homepage', __('T &rarr; Homepage Map - multi city','templatic-admin'), $widget_ops);
	}
	function widget($arg, $instance) {
		global $current_cityinfo,$clustering,$city_category_id;
		$height = empty($instance['height']) ? '425' : apply_filters('widget_height', $instance['height']);
		$clustering = empty($instance['clustering']) ? '' : apply_filters('widget_clustering', $instance['clustering']);
		$city_category_id=($current_cityinfo['categories']!='')? explode(',',$current_cityinfo['categories']) :array();
                
		if(!empty($city_category_id)){
			$post_info=(strstr($current_cityinfo['post_type'],','))? explode(',',$current_cityinfo['post_type']):array($current_cityinfo['post_type']) ;		
		}else{
			$post_info=array();
		}		
		
		$tmpdata = get_option('templatic_settings');			
		$maptype=($current_cityinfo['map_type'] != '')? $current_cityinfo['map_type']: 'ROADMAP';		
		$latitude    = $current_cityinfo['lat'];
		$longitude   = $current_cityinfo['lng'];
		$map_type    = ($current_cityinfo['map_type']) ? $current_cityinfo['map_type'] : 'ROADMAP';
		$map_display = $current_cityinfo['is_zoom_home'];
		$zoom_level  = ($current_cityinfo['scall_factor']) ? $current_cityinfo['scall_factor'] : 3;
		/* Load data on home page map */

		wp_enqueue_script( 'widget-googlemap-js', TEVOLUTION_LOCATION_URL.'js/googlemap.js',true);	
		
		/* add the script in footer */
		add_action('wp_footer','tmpl_homepage_map_script',99);
		
		function tmpl_homepage_map_script(){
			global $current_cityinfo,$clustering;
			$tmpdata = get_option('templatic_settings');			
			$maptype=($current_cityinfo['map_type'] != '')? $current_cityinfo['map_type']: 'ROADMAP';		
			$latitude    = $current_cityinfo['lat'];
			$longitude   = $current_cityinfo['lng'];
			$map_type    = ($current_cityinfo['map_type']) ? $current_cityinfo['map_type'] : 'ROADMAP';
			$map_display = $current_cityinfo['is_zoom_home'];
			$zoom_level  = ($current_cityinfo['scall_factor']) ? $current_cityinfo['scall_factor'] : 3;
			/* Set the google customizer required settings */
			$google_map_customizer=get_option('google_map_customizer');
			?>		
        <script  type="text/javascript" async>
			var map_latitude= '<?php echo $latitude?>';
			var map_longitude= '<?php echo $longitude?>';
			var map_zomming_fact= <?php echo $zoom_level;?>;		
			<?php if($map_display == 1) { ?>
			var multimarkerdata = new Array();
			<?php }?>
			var zoom_option = '<?php echo $map_display; ?>';
			var markers = '';		
			var markerArray = [];
			var ClustererMarkers=[];
			var m_counter=0;
			var map = null;
			var mgr = null;
			var mc = null;		
			var mClusterer = null;
			var showMarketManager = false;
			var PIN_POINT_ICON_HEIGHT = 32;
			var PIN_POINT_ICON_WIDTH = 20;
			var clustering = '<?php echo $clustering; ?>';
			var infobox;
			var infoBubble;
			var maxMap = document.getElementById( 'triggermap' );		
			function initialize(){		
				var isDraggable = jQuery(document).width() > 480 ? true : false;
				var myOptions = {
					scrollwheel: false,
					draggable: isDraggable,
					zoom: map_zomming_fact,
					center: new google.maps.LatLng(map_latitude, map_longitude),
					mapTypeId: google.maps.MapTypeId.<?php echo $map_type;?>
				}
				map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);
				
				var styles = [<?php echo substr($google_map_customizer,0,-1);?>];			
				map.setOptions({styles: styles});
				/* Initialize Fluster and give it a existing map*/
				mgr = new MarkerManager( map );			
			}

			google.maps.event.addDomListener(window, 'load', initialize);
			google.maps.event.addDomListener(window, 'load', newgooglemap_initialize);
			
			/* Script to Show or Hide the Map in Full Screen */
			google.maps.event.addDomListener(maxMap, 'click', showFullscreen);
			function showFullscreen(){jQuery("#map_sidebar").toggleClass("map-fullscreen");jQuery("#map_canvas").toggleClass("map-fullscreen");jQuery(".map_category").toggleClass("map_category_fullscreen");jQuery(".map_post_type").toggleClass("map_category_fullscreen");jQuery("#toggle_post_type").toggleClass("map_category_fullscreen");jQuery("#trigger").toggleClass("map_category_fullscreen");jQuery("body").toggleClass("body_fullscreen");jQuery("#loading_div").toggleClass("loading_div_fullscreen");jQuery("#advmap_nofound").toggleClass("nofound_fullscreen");jQuery("#triggermap").toggleClass("triggermap_fullscreen");jQuery(".TopLeft").toggleClass("TopLeft_fullscreen");window.setTimeout(function(){var e=map.getCenter();google.maps.event.trigger(map,"resize");map.setCenter(e)},100)}		
			</script>  
		<?php } ?>
		
          <div id="map_sidebar" class="map_sidebar">
          <div class="top_banner_section_in clearfix">
               <div class="TopLeft"><span id="triggermap"></span></div>
               <div class="TopRight"></div>
               <div class="iprelative">
                <div class="map_loading_div" style="display: none;"></div>
                <div class="map_loading_div" style="display: none;"></div>
                <div class="map_loading_div map_loading_div_bg" style="display: none;"></div>
               	<div id="map_canvas" style="width: 100%; height:<?php echo $height;?>px" class="map_canvas"></div>               
                    
                    <div id="map_marker_nofound"><?php echo '<p>';_e('Your selected category does not have any records at your current location.','templatic');echo '</p>'; ?></div>     
               </div>             
              
               <form id="ajaxform" name="slider_search" class="pe_advsearch_form" action="javascript:void(0);"  onsubmit="return(new_googlemap_ajaxSearch());">
                	<div class="paf_search"><input  type="text" class="" id="search_string" name="search_string" value="" placeholder="<?php _e('Title or Keyword','templatic');?>" onclick="this.placeholder=''" onmouseover="this.placeholder='<?php _e('Title or Keyword','templatic');?>'"/></div>
               
               <?php if($post_info):
			   $tevolution_post= apply_filters('tevolution_custom_post_type',get_option('templatic_custom_post'));	
			
				
			
				foreach ($tevolution_post as $key => $val){
				
					$hspost = get_posts( array('post_type' => $key, 'posts_per_page' => -1) );
					if($hspost){
						$available_post_types[] = $key;
					}
				}
			
			   ?>
               	<div class="paf_row map_post_type" id="toggle_postID" style="display:block; max-height:<?php echo $height-105;?>px;">
                    <?php 
						/* get the results of post type */
						
						/* To Display the listing post type first on home page map */
						do_action('tmpl_before_map_post_type',$post_info);
						
						/* loop to get results of all post types */
						
						for($c=0;$c<count($post_info);$c++):
							/* Show taxonomy if its exists */
							if(!empty($available_post_types) && in_array($post_info[$c],$available_post_types))
							{	
							$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $post_info[$c],'public'   => true, '_builtin' => true ));	
							?>
							<div class="mw_cat_title">
							<label><input type="checkbox" data-category="<?php echo str_replace("&",'&amp;',$post_info[$c]).'categories';?>" onclick="newgooglemap_initialize(this,'');"  value="<?php echo str_replace("&",'&amp;',$post_info[$c]);?>"  <?php if(!empty($_POST['posttype']) && !in_array(str_replace("&",'&amp;',$post_info[$c]) ,$_POST['posttype'])):?> <?php else:?> checked="checked" <?php endif;?> class="<?php echo str_replace("&",'&amp;',$post_info[$c]).'custom_categories';?>" id="<?php echo str_replace("&",'&amp;',$post_info[$c]).'custom_categories';?>" name="posttype[]"> <?php echo ($tevolution_post[$post_info[$c]]['label'])? $tevolution_post[$post_info[$c]]['label']: ucfirst($post_info[$c]);?></label><span id='<?php echo $post_info[$c].'_toggle';?>' class="toggle_post_type toggleon" onclick="custom_post_type_taxonomy('<?php echo $post_info[$c].'categories';?>',this)"></span></div>
                        
							 <div class="custom_categories <?php echo str_replace("&",'&amp;',$post_info[$c]).'custom_categories';?>" id="<?php echo str_replace("&",'&amp;',$post_info[$c]).'categories';?>" >
								 <?php homepage_map_wp_terms_checklist(0, array( 'taxonomy' =>$taxonomies[0],'post_type'=>$post_info[$c],'selected_cats'=>$city_category_id) );?>
							 </div>
                         
						<?php }
						endfor;
						do_action('tmpl_after_map_post_type');
						?>
                    </div>
                    <div id="toggle_post_type" class="paf_row toggleon" onclick="toggle_post_type();"></div>
                    <?php endif;?>
               </form>     
               
          </div>
          </div>
          <?php
          
          
	}
	/*Widget update function */
	function update($new_instance, $old_instance) {
		/*save the widget*/
		return $new_instance;
	}
	
	/*Widget admin form display function */
	function form($instance) {
		/*widget form in backend*/
		$instance = wp_parse_args( (array) $instance, array(  'height' => 500,'clustering'=> '') );		
		$height = strip_tags($instance['height']);
		$clustering = strip_tags($instance['clustering']);
		?>
	
		<p>
		 <label for="<?php echo $this->get_field_id('height'); ?>"><?php echo __('Map Height: <small>(Default is 500px. To change enter a numeric value.)</small>','templatic-admin');?>
		 <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" />
		 </label>
	    </p>
		<p>
		<?php if($clustering) { $checked = "checked=checked"; }else{ $checked =''; } ?>
		 <label for="<?php echo $this->get_field_id('clustering'); ?>">
		 <input id="<?php echo $this->get_field_id('clustering'); ?>" name="<?php echo $this->get_field_name('clustering'); ?>" type="checkbox" value="1" <?php echo $checked; ?>/>
		 <?php echo __('Disable Clustering','templatic-admin'); ?></label>
	    </p>
	    <?php
	}
}
/* EOF - Home page Google map widget - FOr multicity */


/*
 * Return send the google map marker popup info in jason
 */
add_action('wp_ajax_nopriv_googlemap_initialize','googlemap_initialize');
add_action('wp_ajax_googlemap_initialize','googlemap_initialize');
function googlemap_initialize(){
	global $wpdb,$current_cityinfo;
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	$j=0;
	$cur_lang_code=(is_plugin_active('sitepress-multilingual-cms/sitepress.php'))? ICL_LANGUAGE_CODE :'';
	$pids=array("");
	$srcharr = array('"','\\');
	$replarr = array('\"'," ");
	$title_srcharr = array('"');
	$title_replarr = array('\"');
	$post_type =(explode(',',substr($_REQUEST['posttype'],0,-1)));
	$categoryname =(explode(',',substr($_REQUEST['categoryname'],0,-1)));
	$templatic_settings=get_option('templatic_settings');	
	for($i=0;$i<count($post_type);$i++){
		
		$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $post_type[$i],'public'   => true, '_builtin' => true ));	
		$cat_args = array(
					'taxonomy'=>$taxonomies[0],
					'orderby' => 'name', 				
					'hierarchical' => 'true',
					'title_li'=>''
				);		
		$r = wp_parse_args( $cat_args);
		
		if ( false === ( $catname_arr = get_transient( '_tevolution_query_googlemapwidget'.$post_type[$i].$cur_lang_code ))  && get_option('tevolution_cache_disable')==1 ) {
			$catname_arr=get_categories( $r );
			set_transient( '_tevolution_query_googlemapwidget'.$post_type[$i].$cur_lang_code, $catname_arr, 12 * HOUR_IN_SECONDS );				
		}elseif(get_option('tevolution_cache_disable')==''){
			$catname_arr=get_categories( $r );
		}	
		$cat_ID='';
		foreach($catname_arr as $cat){
			if(!in_array($cat->term_id,$categoryname))
				continue;
				
			$cat_ID.= $cat->term_id.',';
		}
		$args3=array('post_type'      => trim($post_type[$i]),
				   'posts_per_page' => 400,
				   'post_status'    => 'publish',
				   'tax_query'      => array(
									  array(
										 'taxonomy' => $taxonomies[0],
										 'field'    => 'id',
										 'terms'    => explode(',',$cat_ID),
										 'operator' => 'IN'
									  )
								   ),
				   );			
		add_filter( 'posts_where', 'google_search_posts_where', 10, 2 );
		$post_details= new WP_Query($args3);		
		remove_filter( 'posts_where', 'google_search_posts_where', 10, 2 );
		$content_data='';	

		if ($post_details->have_posts()) :			
			while ( $post_details->have_posts() ) : $post_details->the_post();
					global $post;
					$post_categories = get_the_terms( get_the_ID() ,$taxonomies[0]);
					foreach($post_categories as $post_category){
						if($post_category->term_icon && in_array($post_category->term_id,$categoryname)){
							$term_icon=$post_category->term_icon;
							break;
						}else{
							if($post_category->term_icon !=''){
								$term_icon=$post_category->term_icon;
								break;
							}else{
								$term_icon= apply_filters('tmpl_default_map_icon',TEVOLUTION_LOCATION_URL.'images/pin.png');
							}
						}
					}
					$ID =get_the_ID();				
					$title = str_replace("'","\'",get_the_title($ID));
					$comment_status = $post->comment_status;
					$plink = str_replace("'","\'",get_permalink($ID));
					$lat = get_post_meta($ID,'geo_latitude',true);
					$lng = get_post_meta($ID,'geo_longitude',true);					
					$address = str_replace($srcharr,$replarr,(get_post_meta($ID,'address',true)));
					$contact = str_replace($srcharr,$replarr,(get_post_meta($ID,'phone',true)));
					$website = get_post_meta($ID,'website',true);
					if(!strstr($website,'http') && $website)
						$website = 'http://'.$website;
					/*Fetch the image for display in map */
					if ( has_post_thumbnail()){
						$post_img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail');						
						$post_images=$post_img[0];
					}else{
						$post_img = bdw_get_images_plugin($ID,'thumbnail');					
						$post_images = $post_img[0]['file'];
					}
					$imageclass='';
					if($post_images){
						$post_image='<div class=map-item-img><a href='.get_permalink($ID).'><img src='.$post_images.' width=150 height=150/></a></div>';
						
					}else{
						$post_image='';
						$imageclass='no_map_image';
					}
					
					$image_class=($post_image)?'map-image' :'';					
						
					$comment_count= count(get_comments(array('post_id' => $ID)));
					$review=($comment_count ==1 )? __('review','templatic'):__('reviews','templatic');	
					
					if(($lat && $lng )&& !in_array($ID,$pids))
					{ 						
						$retstr ='{';
						$retstr .= '"name":"'.str_replace($title_srcharr,$title_replarr,$post->post_title).'",';
						$retstr .= '"location": ['.$lat.','.$lng.'],';
						$retstr .= '"message":"<div class=\"google-map-info '.$image_class.'\"><div class=map-inner-wrapper><div class=\"map-item-info '.$imageclass.'\">'.$post_image;
						$retstr .= '<h6><a href='.$plink.' class=ptitle><span>'.$title.'</span></a></h6>';							
						if($address){$retstr .= '<p class=address>'.trim($address).'</p>';}
						$retstr .= apply_filters('tmpl_map_after_address_fields','',$post->ID);
						if($contact){$retstr .= '<p class=contact>'.ltrim(rtrim($contact)).'</p>';}
						$retstr .= apply_filters('tmpl_map_after_contact_fields','',$post->ID);
						if($website){$retstr .= '<p class=website><a href= '.trim($website).'>'.trim($website).'</a></p>';}
						$retstr .= apply_filters('tmpl_map_custom_fields','',$post->ID);
						if( $comment_status =='open'){
							if($templatic_settings['templatin_rating']=='yes'){
								$rating=draw_rating_star_plugin(get_post_average_rating(get_the_ID()));
								$retstr .= '<div class=map_rating>'.str_replace('"','',$rating).' <span><a href='.$plink.'#comments>'.$comment_count.' '.$review.'</a></span></div>';
							}elseif(is_plugin_active('Templatic-MultiRating/multiple_rating.php') && function_exists('single_average_rating')){
								$rating=get_single_average_rating(get_the_ID());
								$retstr .= '<div class=map_rating>'.stripcslashes(str_replace('"','',$rating)).'<span><a href='.$plink.'#comments>'.$comment_count.' '.$review.'</a></span></div>';
							}
						}
						$retstr .= '</div></div></div>';
						$retstr .= '",';
						$retstr .= '"icons":"'.$term_icon.'",';
						$retstr .= '"pid":"'.$ID.'"';
						$retstr .= '}';
						$content_data[] = $retstr;
						$j++;
					}
					$pids[]=$ID;					
			endwhile;	
			wp_reset_query();
			wp_reset_postdata();
		endif;
		if($content_data)	
			$cat_content_info[]= implode(',',$content_data);
	}
	if($cat_content_info)
	{
		echo '[{"totalcount":"'.$j.'",'.substr(implode(',',$cat_content_info),1).']';
	}else
	{
		echo '[{"totalcount":"0"}]';
	}
	exit;
}
/*
 * Return post where condition on google map search results as per pass the search post title
 */
function google_search_posts_where( $where, &$wp_query){
	global $wpdb;
	$location_post_type=','.implode(',',get_option('location_post_type'));
	if(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!='' && strpos($location_post_type,','.$wp_query->query['post_type']) !== false){
		$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$_SESSION['post_city_id'].", pm.meta_value ))";
	}
	$city_where='';
	if(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!='' )
	{
		$city_where = "AND  pm1.meta_key ='post_city_id' and FIND_IN_SET( ".$_SESSION['post_city_id'].", pm1.meta_value )";
	}
	
	if(isset($_REQUEST['search_string']) && $_REQUEST['search_string']!=''){
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $_REQUEST['search_string']) ) . '%\'';
		
		$term_ids_qry = $wpdb->get_results("select t.term_id from $wpdb->terms t where t.name like '%" . esc_sql( like_escape( $_REQUEST['search_string']) ) . "%'");
		$term_ids = '';
		
		/* concate all term id to pass in query */
		for($row=0; $row < count($term_ids_qry); $row++ ) {
			if($row != ( count($term_ids_qry)-1)){
				$sep = ",";
			}else{
				$sep ="";
			}
			if($term_ids_qry[$row]->term_id !='' )
				$term_ids .= "'".$term_ids_qry[$row]->term_id."'{$sep}";
		}
		
		/* now get all the post id from term taxonomy table */
		$object_ids_qry = $wpdb->get_results("select tr.object_id from $wpdb->term_taxonomy tt, $wpdb->term_relationships tr where tt.term_id IN({$term_ids}) and tt.term_taxonomy_id=tr.term_taxonomy_id");
		
		$object_ids='';
		/* concate all post/object ids to pass in query */
		for($row=0; $row < count($object_ids_qry); $row++ ) {
			if($row != ( count($object_ids_qry)-1)){
				$sep = ",";
			}else{
				$sep ="";
			}
			if($object_ids_qry[$row]->object_id !='' )
				$object_ids .= "'".$object_ids_qry[$row]->object_id."'{$sep}";
		}
		
		/* get all posts id */
		$get_posts_qry = $wpdb->get_results("select ID from $wpdb->posts p,$wpdb->postmeta pm1 where p.ID IN({$object_ids}) AND p.post_status = 'publish' AND p.ID=pm1.post_id  $city_where group by  p.ID");

		$posts_id='';
		/* concate all the posts id */
		for($row=0; $row < count($get_posts_qry); $row++ ) {
			if($row != ( count($get_posts_qry)-1)){
				$sep = ",";
			}else{
				$sep ="";
			}
			if($get_posts_qry[$row]->ID !='' )
				$posts_id .= "'".$get_posts_qry[$row]->ID."'{$sep}";
		}
		if($posts_id!= '')
			$where .= " OR  ($wpdb->posts.ID in ({$posts_id}))";
	}
	return $where;	
}

/* This function use for hide google map on mobile responsive */
add_action('wp_head','location_google_map_responsive');
function location_google_map_responsive(){
	$templatic_settings = get_option('templatic_settings'); 		
	if(strtolower(@$templatic_settings['google_map_hide']) == strtolower('yes')){ ?>
		<style type='text/css'>
			@media only screen and (max-width: 719px){
				.map_sidebar{ display:none; }
			}
		</style>
	<?php }	
}

/**
 * Output an unordered list of checkbox <input> elements labelled
 * with term names. Taxonomy independent version of wp_category_checklist().
 *
 * @since 3.0.0
 *
 * @param int $post_id
 * @param array $args
 */
function homepage_map_wp_terms_checklist($post_id = 0, $args = array()) {
 	$defaults = array(		
		'selected_cats' => false,
		'walker' => null,
		'taxonomy' => 'category',
		'checked_ontop' => true,		
		'post_type' =>'post',
	);
	$args = apply_filters( 'wp_terms_checklist_args', $args, $post_id );
	
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );

	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Homepage_map_Walker_Category;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array('taxonomy' => $taxonomy);

	$tax = get_taxonomy($taxonomy);
	$args['disabled'] = !current_user_can($tax->cap->assign_terms);

	if($post_type!=""){
		$args['post_type'] = $post_type;	
	}
	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;	
	else
		$args['selected_cats'] = array();
		
	
	
	$categories = (array) get_terms($taxonomy, array('get' => 'all'));	
	/*Then the rest of them	*/
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}



/**
 * Create HTML list of categories.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class Homepage_map_Walker_Category extends Walker {	
	var $tree_type = 'category';
	
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= "\n";
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ) {		
		$output .= "\n";
	}
	
	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		extract($args);

		$cat_name = esc_attr( $category->name );		
		if(@$category->term_icon!='')
			$term_icon=$category->term_icon;
		else
			$term_icon= apply_filters('tmpl_default_map_icon',TEVOLUTION_LOCATION_URL.'images/pin.png');
			
		$depth_parent=0;
		if($depth!=0){
			$depth_parent=3*5;	
		}
		
		$category_image='<img height="14" width="8" alt="" src="'.$term_icon.'">';
			
		if($args['post_type'])
			$post_type=str_replace("&",'&amp;',$args['post_type']);
		else
			$post_type='post';
			
		$onclick='onclick="newgooglemap_initialize(this,&quot;'.$post_type.'&quot;)"';		
		
		if(in_array($category->term_id,$args['selected_cats']) || in_array('all',$args['selected_cats'])){
			$output .= "\n" . '<label for="in-'.$taxonomy.'-' . $category->term_id . '" style="margin-left:'.$depth_parent.'px"><input type="checkbox" name="categoryname[]" value="' . $category->term_id . '"   id="in-'.$taxonomy.'-' . $category->term_id . '" checked="checked"  onclick="newgooglemap_initialize(this,&quot;'.$post_type.'&quot;)" /> '.$category_image.'' . esc_html(  $category->name ).'</label>';	
		}
		
	
	}
	
	function end_el( &$output, $page, $depth = 0, $args = array() ) {		
		$output .= "\n";
	}
}
?>