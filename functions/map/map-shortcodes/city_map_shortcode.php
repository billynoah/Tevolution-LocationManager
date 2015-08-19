<?php
add_shortcode( 'TCITY-DIRECTORYMAP', 'tcity_directory_map' );

/* end */
/*
Function Name : tcity_directory_map
args : atts - to pass attributes
Description : Add Map on page using shortcode
*/
function tcity_directory_map( $atts ) {
     
	 global $wpdb,$templatic_settings,$wp_query,$current_cityinfo,$short_code_city_id;
	 
	 $atts = shortcode_atts(
		array(
			'cityid' 	=> false,
			'post_type' 	=> false,
			'width' 	=> '100%',
			'height' 	=> '400',
			'map_type' 	=> 'ROAD_MAP',
			'showmap' 	=> 1,
			'slider' 	=> 1,
			'listing' 	=> 0,
			'showfullmap'=> 1,
			'showclustering'=>1
		),
		$atts
	);
	ob_start();
	wp_enqueue_script( 'google-map-js', TEVOLUTION_LOCATION_URL.'js/page_googlemap.js',true);	
	wp_print_scripts( 'google-map-js' );
	

	
	$city_id = $atts['cityid']; 
	
	/* get city information BOF */
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";	
	$multicity_table = $wpdb->prefix . "multicity";	
	if($wpdb->get_var("SHOW TABLES LIKE '$multicity_table'") == $multicity_table) {
		$cityinfo = $wpdb->get_results("SELECT mc.*,mc.message as msg,c.country_name,c.country_flg,z.zone_name FROM $multicity_table mc,$zones_table z,$country_table c where c.country_id=mc.country_id AND z.zones_id=mc.zones_id AND  mc.city_id =".$city_id." order by cityname   ASC");	
	}
	$post_info=(strstr($atts['post_type'],','))? explode(',',$atts['post_type']):array($atts['post_type']) ;		
	
	$short_code_city_id=$cityinfo[0]->city_id;
	$current_cityinfo=array('city_id'      =>$cityinfo[0]->city_id,
					    'country_id'   =>$cityinfo[0]->country_id,
					    'zones_id'     =>$cityinfo[0]->zones_id,
					    'cityname'     =>$cityinfo[0]->cityname,
					    'city_slug'    =>$cityinfo[0]->city_slug,
					    'city_code'    =>$cityinfo[0]->city_code,
					    'lat'          =>$cityinfo[0]->lat,
					    'lng'          =>$cityinfo[0]->lng,
					    'scall_factor' =>$cityinfo[0]->scall_factor,
					    'is_zoom_home' =>$cityinfo[0]->is_zoom_home,
					    'map_type'     =>$cityinfo[0]->map_type,
					    'post_type'    =>$cityinfo[0]->post_type,
					    'color'        =>$cityinfo[0]->color,
					    'message'      =>$cityinfo[0]->msg,
					    'color'        =>$cityinfo[0]->color,
					    'images'       =>$cityinfo[0]->images,
					    'country_name' =>$cityinfo[0]->country_name,
					    'country_flg'  =>$cityinfo[0]->country_flg,
					    'zone_name'    =>$cityinfo[0]->zone_name,
					    );
	/* get city information EOF */
	$latitude    = $current_cityinfo['lat'];
	$longitude   = $current_cityinfo['lng'];
	$map_type    = $atts['map_type'];
	$showlisting    = $atts['listing'];
	$showmap    = $atts['showmap'];
	$slider    = $atts['slider'];
	$map_display = $current_cityinfo['is_zoom_home'];
	$zoom_level  = $current_cityinfo['scall_factor'];	
	$map_clustering  = $atts['showclustering'];	
	$mapcategory_info =get_citymap_categoryinfo($post_info,$city_id);	
	$zoom_level=13;
	$heigh = $atts['height'];
	if($templatic_settings['pippoint_effects'] =='click'){ $class="wmap_static"; }else{ $class="wmap_scroll"; }
	
	$google_map_customizer=get_option('google_map_customizer');/* store google map customizer required formate.*/
	?>  
    <div class="city_map_frame <?php echo $class; ?>">      
        <script  type="text/javascript" async >
		var map_latitude= '<?php echo $latitude?>';
		var map_longitude= '<?php echo $longitude?>';
		var map_zomming_fact= <?php echo $zoom_level;?>;		
		<?php if($map_display == 1) { ?>
		var multimarkerdata = new Array();
		<?php }?>
		var zoom_option = '<?php echo $map_display; ?>';
		var markers = '';
		var markerArray = [];	
		var pipointeffect = '<?php echo $templatic_settings['pippoint_effects']; ?>';
		var map = null;
		var mgr = null;
		var fluster =null;
		var mc = null;
		var markerClusterer = null;
		var mClusterer = null;
		var showMarketManager = false;
		var PIN_POINT_ICON_HEIGHT = 32;
		var PIN_POINT_ICON_WIDTH = 20;   	    
		var infoBubble;
		function initialize(){
			var isDraggable = jQuery(document).width() > 480 ? true : false;
			var myOptions = {
				zoom: map_zomming_fact,
				draggable: isDraggable,
				center: new google.maps.LatLng(map_latitude, map_longitude),
				mapTypeId: google.maps.MapTypeId.<?php echo $map_type;?>
			}
			map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);
			var styles = [<?php echo substr($google_map_customizer,0,-1);?>];			
		    map.setOptions({styles: styles});			
			mgr = new MarkerManager( map );
		}
		google.maps.event.addDomListener(window, 'load', initialize);
		google.maps.event.addDomListener(window, 'load', newgooglemap_initialize);
		</script>
		<?php if($showmap ==1 ){ ?>
          <div class="map_sidebar">
          <div class="top_banner_section_in clearfix">
               <div class="TopLeft"><span id="triggermap"></span></div>
               <div class="TopRight"></div>
               <div class="iprelative">
               	<div id="map_canvas" style="width: 100%; height:<?php echo $heigh;?>px" class="map_canvas"></div>               
                    <div id="map_loading_div" style="width: 100%; height:<?php echo $heigh;?>px; display: none;"></div>                     
                    <div id="map_marker_nofound"><?php echo '<p>';_e('Your selected category do not have any records yet at your current location.','templatic-admin');echo '</p>'; ?></div>     
               </div>   
                <form id="ajaxform" name="slider_search" class="" action="javascript:void(0);"  onsubmit="return(new_googlemap_ajaxSearch());">
                		<input type="hidden" name="short_code_city_id" id="short_code_city_id" value="<?php echo $short_code_city_id;?>" />
				<?php if($post_info):?>
               	<div class="paf_row map_post_type" id="toggle_postID" style="display:none;">
                    <?php for($c=0;$c<count($post_info);$c++): ?>
					<label><input type="checkbox" onclick="newgooglemap_initialize(this);"  value="<?php echo str_replace("&",'&amp;',$post_info[$c]);?>"  <?php if(!empty($_POST['posttype']) && !in_array(str_replace("&",'&amp;',$post_info[$c]) ,$_POST['posttype'])):?> <?php else:?> checked="checked" <?php endif;?> id="<?php echo str_replace("&",'&amp;',$post_info[$c]);?>" name="posttype[]"> <?php echo $post_info[$c];?></label><span id='<?php echo $post_info[$c].'_toggle';?>' class="toggle_post_type toggleoff" onclick="custom_post_type_taxonomy('<?php echo $post_info[$c].'_category';?>',this)"></span>
                         <div class="custom_categories" id="<?php echo $post_info[$c].'_category';?>" style="display:none;">
                         	 <?php foreach($mapcategory_info[$post_info[$c]] as $key => $value){ ?>
                    				<label><input type="checkbox" onclick="newgooglemap_initialize(this);"  value="<?php echo $value['slug'];?>"  <?php if(!empty($_POST['categoryname']) && !in_array($key,$_POST['categoryname'])):?> <?php else:?> checked="checked" <?php endif;?> id="<?php echo $key;?>" name="categoryname[]<?php /* echo $key; */ ?>"><img height="14" width="8" alt="" src="<?php echo $value['icon']?>"> <?php echo $value['name']?></label>
                    
                    <?php }?>
                         </div>
                         
                    <?php endfor;?>
                    </div>
                    <?php endif;?>
               </form>     
              
          </div>
		  </div>
        <script>
			var maxMap = document.getElementById( 'triggermap' );		
			google.maps.event.addDomListener(maxMap, 'click', showFullscreen);
			function showFullscreen() {			  
				jQuery('#map_canvas').toggleClass('map-fullscreen');
				jQuery('.map_category').toggleClass('map_category_fullscreen');
				jQuery('.map_post_type').toggleClass('map_category_fullscreen');
				jQuery('#trigger').toggleClass('map_category_fullscreen');
				jQuery('body').toggleClass('body_fullscreen');
				jQuery('#loading_div').toggleClass('loading_div_fullscreen');
				jQuery('#advmap_nofound').toggleClass('nofound_fullscreen');
				jQuery('#triggermap').toggleClass('triggermap_fullscreen');
				jQuery('.TopLeft').toggleClass('TopLeft_fullscreen');					 
					 window.setTimeout(function() { 
					var center = map.getCenter(); 
					google.maps.event.trigger(map, 'resize'); 
					map.setCenter(center); 
			   		}, 100);			 }
		</script>
		 <?php } ?>
		<?php if($showlisting ==1 ){ ?>
		<div id="cities_post">
		<?php 		
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1; 
		$args = array('post_type'      => $post_info,
				    'posts_per_page' =>get_option('posts_per_page'),
				    'paged'          =>$paged,
				    'post_status'    => 'publish',
				    'orderby'        => 'meta_value',
				    'order'          => 'ASC',				    
					);
		add_filter('posts_where', 'location_shortcode_multicity_where');
		$result = new WP_Query( $args );		
		remove_filter('posts_where', 'location_shortcode_multicity_where');
		$wp_query=$result;
		$pcount=0; 
		while ( $result->have_posts() ) : 
				$result->the_post(); 
				location_listing_format($post);
		
		endwhile;
		
		?>
		</div>
		
          <div id="listpagi">
               <div class="pagination pagination-position">
               	<?php if(function_exists('pagenavi_plugin')) { pagenavi_plugin(); } ?>
               </div>
          </div>
		<?php  } ?>
        </div><?php  
	   wp_reset_query(); 
	return ob_get_clean();
}
function get_citymap_categoryinfo($post_type,$city_id){
	
	global $current_cityinfo;	
	for($i=0;$i<count($post_type);$i++){		
		$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $post_type[$i],'public'   => true, '_builtin' => true ));	
		$cat_args = array(
					'taxonomy'=>$taxonomies[0],
					'orderby' => 'name', 				
					'hierarchical' => 'true',
					'title_li'=>''
				);	
		$r = wp_parse_args( $cat_args);	
		$catname_arr=get_categories( $r );
		$categoriesinfo='';		
		foreach($catname_arr as $cat)	{			
			if($cat->term_icon)
				$term_icon=$cat->term_icon;
			else
				$term_icon= apply_filters('tmpl_default_map_icon',TEVOLUTION_LOCATION_URL.'images/pin.png');
			$categoriesinfo[]=array( 'slug'=>$cat->slug,'name'=>$cat->name,'icon'=>$term_icon);
		}
		$catinfo_arr[$post_type[$i]]=$categoriesinfo;
	}	
	return $catinfo_arr;
}

add_action('wp_ajax_nopriv_shortcode_googlemap_initialize','shortcode_googlemap_initialize');
add_action('wp_ajax_shortcode_googlemap_initialize','shortcode_googlemap_initialize');
function shortcode_googlemap_initialize(){
	global $wpdb,$current_cityinfo,$city_id;
	$j=0;
	$pids=array("");
	$srcharr = array('"');
	$replarr = array('\"');
	$title_srcharr = array('"');
	$title_replarr = array('\"');
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
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
		$catname_arr=get_categories( $r );
		foreach($catname_arr as $cat)	{	
		
			$catname=$cat->slug;
			if(!in_array($cat->slug,$categoryname))
				continue;
				
			$cat_ID.= $cat->term_id.',';	
		}
			$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $post_type[$i],'public'   => true, '_builtin' => true ));
			$args=array(
					   'post_type'      => trim($post_type[$i]),
					   'posts_per_page' => 400,
					   'post_status'    => 'publish',
					   'tax_query'      => array(
										  array(
											 'taxonomy' =>$taxonomies[0],
											 'field'    => 'id',
											 'terms'    =>  explode(',',$cat_ID),
											 'operator' => 'IN'
										  )
									   ),
					   'order_by'       =>'date',
					   'order'          => 'rand'
					   );
			add_filter('posts_where', 'location_shortcode_multicity_where');
			$post_details= new WP_Query($args);			
			remove_filter('posts_where', 'location_shortcode_multicity_where');
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
?>