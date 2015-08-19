<?php
add_action( 'widgets_init', 'tmpl_category_page_map',22 );

/* return map for necessary category page map with location manager */
function tmpl_category_page_map(){
	
	register_widget('widget_category_googlemap_widget');
	unregister_widget('widget_listingpagemap');
	unregister_widget('widget_homepagemap');
}

class widget_category_googlemap_widget extends WP_Widget {
	function widget_category_googlemap_widget() {	
		$widget_ops = array('classname' => 'widget Google Map in Listing page', 'description' => __('Show a map on category pages while operating multiple cities. Use in category page sidebar and category page - below header areas.','templatic-admin') );		
		$this->WP_Widget('category_googlemap', __('T &rarr; Category Page Map - multi city','templatic-admin'), $widget_ops);
	}
	function widget($args, $instance) {		
      	$heigh = empty($instance['heigh']) ? 425 : apply_filters('widget_heigh', $instance['heigh']);
      	$clustering = empty($instance['clustering']) ? '' : apply_filters('widget_heigh', $instance['clustering']);
      	$show_all_posts = empty($instance['show_all_posts']) ? '' : apply_filters('widget_show_all_posts', $instance['show_all_posts']);
		
		global $current_cityinfo,$wp_query,$wpdb;
		$templatic_settings=get_option('templatic_settings');
		
		$taxonomy= get_query_var( 'taxonomy' );		
		$slug=get_query_var( get_query_var( 'taxonomy' ) );
		$term=get_term_by( 'slug',$slug , $taxonomy ) ;
		if($taxonomy==''){
			$taxonomies = get_object_taxonomies( (object) array( 'post_type' => get_post_type(),'public'   => true, '_builtin' => true ));
			$taxonomy=$taxonomies[0];
		}
		$term_icon=$term->term_icon;	
		if($term_icon=='')
			$term_icon = apply_filters('tmpl_default_map_icon',TEMPL_PLUGIN_URL.'tmplconnector/monetize/images/pin.png');
		/*Get the directory listing page map settings */
		$templatic_settings=get_option('templatic_settings');	
		$googlemap_setting=get_option('city_googlemap_setting');
		$current_term = $wp_query->get_queried_object();
		
		/* Show map view in catgeory page tyen return in google map widget */
		if($templatic_settings['category_googlemap_widget']=='yes'){
			return;
		}
		remove_all_filters('posts_where');
		if($show_all_posts =='yes' && $templatic_settings['category_googlemap_widget']!='yes' && get_post_type()!='' && !is_search()){			
			add_filter('posts_where', 'location_multicity_where');
			if(get_post_type() =='event' && is_plugin_active('Tevolution-Events/events.php')){
				add_filter('posts_where', 'event_manager_posts_where');
			}
			if(is_tax()){
				$args4 = array(
					'post_type' => get_post_type(),
					'post_status'  => 'publish',
					'tax_query' => array(
						array(
							'taxonomy' => $taxonomy,
							'field' => 'slug',
							'terms' => $term
						)
					),
					'posts_per_page' => -1
				);			
			}else{
				$args4 = array(
					'post_type' => get_post_type(),					
					'posts_per_page' => -1,
					'post_status'   => 'publish',
				);
			}
			$query = new WP_Query( $args4 );
		}else{
			$query = $wp_query;
		}
		/* create query string for taxonomy page */
		if(get_post_type()!='' && get_post_type()!='custom_fields' && (is_tax() || is_archive())){
			$operator=(isset($_SERVER['QUERY_STRING'])&& $_SERVER['QUERY_STRING']!='') ?'&':'';
			$_SERVER['QUERY_STRING'].= $operator. 'post_type='.get_post_type().'&taxonomy='.$taxonomy.'&slug='.$slug;
		}
		
		$pids=array("");
		$cat_name = single_cat_title('',false);
		$srcharr = array("'");
		$replarr = array("\'");	
		
		/*added the condition while search from advance search cityfield*/
		if(isset($_REQUEST['adv_city']) && $_REQUEST['adv_city']!='')
		{
			$multicity_table = $wpdb->prefix . "multicity";	
			$cityinfo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $multicity_table where city_id=%d",$_REQUEST['adv_city']));
			$map_type = ($cityinfo->map_type != '')? $cityinfo->map_type: 'ROADMAP';		
			$latitude    = $cityinfo->lat;
			$longitude   = $cityinfo->lng;	
			$map_display = $cityinfo->is_zoom_cat;
			$zoom_level  = ($cityinfo->cat_scall_factor)? $cityinfo->cat_scall_factor :'13';
		}
		else
		{
			$map_type = ($current_cityinfo['map_type'] != '')? $current_cityinfo['map_type']: 'ROADMAP';		
			$latitude    = $current_cityinfo['lat'];
			$longitude   = $current_cityinfo['lng'];	
			$map_display = $current_cityinfo['is_zoom_cat'];
			$zoom_level  = ($current_cityinfo['cat_scall_factor'])? $current_cityinfo['cat_scall_factor'] :'13';
		}
		
		if ($query->have_posts() && $templatic_settings['category_googlemap_widget']!='yes') :
	
			echo $args['before_widget'];
			while ($query->have_posts()) : $query->the_post();
				global $post;
				$ID = get_the_ID();	
				$comment_status = $post->comment_status;			
				$post_id=get_the_ID();
				if(get_post_meta($post_id,'_event_id',true)){
					$post_id=get_post_meta($post_id,'_event_id',true);
				}
				$title = get_the_title(get_the_ID());
				$marker_title = str_replace($srcharr,$replarr,$post->post_title);
				$plink = get_permalink(get_the_ID());
				$lat = get_post_meta(get_the_ID(),'geo_latitude',true);
				$lng = get_post_meta(get_the_ID(),'geo_longitude',true);					
				$address = get_post_meta(get_the_ID(),'address',true);
				$address = str_replace($srcharr,$replarr,$address);
				if(is_search() || is_archive() && !is_tax()){
					$taxonomies = get_object_taxonomies( (object) array( 'post_type' => get_post_type(),'public'   => true, '_builtin' => true ));				
					$post_categories = get_the_terms( get_the_ID() ,$taxonomies[0]);
					if(!empty($post_categories)){
                                            foreach($post_categories as $post_category)
                                                if($post_category->term_icon){
                                                        $term_icon=$post_category->term_icon;
                                                }
                                        }
				}
				if(get_post_type()=='listing'){
					$timing=get_post_meta(get_the_ID(),'listing_timing',true);
					$contact=get_post_meta(get_the_ID(),'phone',true);
				}
				if(get_post_type()=='event'){
					$st_time=get_post_meta(get_the_ID(),'st_time',true);
					$end_time=get_post_meta(get_the_ID(),'end_time',true);
					$timing=$st_time.' To '.$end_time;
					$contact=get_post_meta(get_the_ID(),'phone',true);
				}
				if ( has_post_thumbnail()){
					$post_img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail');						
					$post_images=$post_img[0];
				}else{
					$post_img = bdw_get_images_plugin($post_id,'thumbnail');					
					$post_images = $post_img[0]['file'];
				}
				$imageclass='';
				if($post_images)
					$post_image='<div class=map-item-img><a href='.get_permalink($ID).'><img width="150" height="150" class="map_image" src="'.$post_images.'" /></a></div>';					
				else{
					$post_image='';
					$imageclass='no_map_image';
				}
					
				$image_class=($post_image)?'map-image' :'';	
				$comment_count= count(get_comments(array('post_id' => $ID)));
				$review=($comment_count ==1 )? __('review','templatic'):__('reviews','templatic');
				if($lat && $lng && !in_array($post_id,$pids))
				{ 
					if(!isset($more)){ $more =''; }
					$retstr ="{";
					$retstr .= "'name':'$marker_title',";
					$retstr .= "'location': [$lat,$lng],";
					$retstr .= "'message':'<div class=\"google-map-info $image_class forrent\"><div class=\"map-inner-wrapper\"><div class=\"map-item-info ".$imageclass."\">$post_image";
					$retstr .= "<h6><a href=\"$plink\" class=\"ptitle\" ><span>$title</span></a></h6>";
					
					if($address){ $retstr .= "<p class=address>".trim($address)."</p>";}
					$retstr .= apply_filters('tmpl_map_after_address_fields','',$post->ID);
					if($timing){$retstr .= "<p class=timing>$timing</p>";}
					if($contact){$retstr .= "<p class=contact>".ltrim(rtrim($contact))."</p>";}
					$retstr .= apply_filters('tmpl_map_after_contact_fields','',$post->ID);
					if($website){ $retstr .= "<p class=website><a href= ".trim($website).">".trim($website)."</a></p>";}
					$retstr .= apply_filters('tmpl_map_custom_fields','',$post->ID);
					if($comment_status =='open'){
						if($templatic_settings['templatin_rating']=='yes'){
							$rating=draw_rating_star_plugin(get_post_average_rating(get_the_ID()));
							$retstr .= '<div class=map_rating>'.str_replace('"','',$rating).' <span><a href='.$plink.'#comments>'.$comment_count.' '.$review.'</a></span></div>';
						}elseif(is_plugin_active('Templatic-MultiRating/multiple_rating.php') && function_exists('single_average_rating')){
							$rating=get_single_average_rating(get_the_ID());
							$retstr .= '<div class=map_rating>'.stripcslashes(str_replace('"','',$rating)).'<span> <a href='.$plink.'#comments>'.$comment_count.' '.$review.'</a></span></div>';
						}
					}
					$retstr .= "</div></div></div>";
					$retstr .= "',";
					$retstr .= "'icons':'$term_icon',";
					$retstr .= "'pid':'$ID'";
					$retstr .= "}";						
					$content_data[] = $retstr;
				}		
				$pids[]=$post_id;
			endwhile;			
		if($content_data)	
			$catinfo_arr= "[".implode(',',$content_data)."]";
			wp_reset_query();		
	
		wp_print_scripts( 'google-maps-apiscript' );
		wp_print_scripts( 'google-clusterig' );
	
	$google_map_customizer=get_option('google_map_customizer');/* store google map customizer required formate.*/
	?>
    <script  type="text/javascript" async >
		var CITY_MAP_CENTER_LAT= '<?php echo $latitude?>';
		var CITY_MAP_CENTER_LNG= '<?php echo $longitude?>';
		var CITY_MAP_ZOOMING_FACT= <?php echo $zoom_level;?>;
		var infowindow;		
		var zoom_option = '<?php echo $map_display; ?>';
		var markers = <?php echo $catinfo_arr;?>;
		var clustering = '<?php echo $clustering;?>';
		var map;
		var mgr;
		var markerClusterer;		
		var markerArray = [];		
		var pippoint_effects='<?php echo ($templatic_settings['pippoint_effects'] == 'hover')? 'hover':'click';?>';
		var PIN_POINT_ICON_HEIGHT = 32;
		var PIN_POINT_ICON_WIDTH = 20;						
		var infoBubble = new InfoBubble({maxWidth:210,minWidth:210,minHeight:"auto",padding:0,borderRadius:0,borderWidth:0,overflow:"visible",backgroundColor:"#fff"});
		var isDraggable = jQuery(document).width() > 480 ? true : false;
		var isscrollwheel = jQuery(document).width() > 480 ? true : false;
		var dragging = zoom_changed = false;		
		var bounds_modified = false;
		var new_bounds;
		var query_string='<?php echo $_SERVER['QUERY_STRING'];?>';
		var zoom_changed=1;
		function initialize() {
			bounds = new google.maps.LatLngBounds();
			var myOptions = {
				scrollwheel: isscrollwheel,
				draggable: isDraggable,
				zoom: CITY_MAP_ZOOMING_FACT,
				center: new google.maps.LatLng(CITY_MAP_CENTER_LAT, CITY_MAP_CENTER_LNG),
				mapTypeId: google.maps.MapTypeId.<?php echo $map_type;?>
			}
			map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);			
			/* map style customizer */
			var styles = [<?php echo substr($google_map_customizer,0,-1);?>];			
			map.setOptions({styles: styles});		   
			
		    /* Add Google Map marlkers */
			mgr = new MarkerManager( map );
			templ_add_googlemap_markers(markers);		   
		   
		   
			if(zoom_option==1){				
				map.fitBounds(bounds);
				map.setCenter(bounds.getCenter());
			}				
			/* but that message is not within the marker's instance data */
			google.maps.event.addListener(map, 'dragstart', function() {
				dragging = false;
			});
			google.maps.event.addListener(map, 'dragend', function() {
				dragging = true;				
			});			
			
			google.maps.event.addListener(map, 'bounds_changed', function() {
			  bounds_modified = true;
			  new_bounds = map.getBounds();
			});			
			google.maps.event.addListener(map, 'zoom_changed', function() {
				if(zoom_changed!=1){
					dragging = true;					
				}
				zoom_changed=0;
			});
						
			google.maps.event.addListener(map, 'idle', function(ev){
				refresh_markers();
			});
		}
		
		google.maps.event.addDomListener(window, 'load', initialize);
	</script>
	<div id="listing_google_map" class="listing_google_map" >
		<div class="map_sidebar">
		<div class="top_banner_section_in clearfix">
			<div class="TopLeft"><span id="triggermap"></span></div>
			<div class="TopRight"></div>
			<div id="map_canvas" style="width: 100%; height:<?php echo $heigh;?>px" class="map_canvas"></div>
		</div>
		</div>
	</div> 
	<script  type="text/javascript" async >
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
		}, 100);			 
	}

	</script>
	<?php	
	echo $args['after_widget'];	
	
	endif;	
		/* if want to show the map evnet there is not results available */
		if((current_theme_supports('map_with_noresultsfound') && !$query->have_posts())){ 
			wp_print_scripts( 'google-maps-apiscript' );
			wp_print_scripts( 'google-clusterig' );
			?>
			<script  type="text/javascript" async >
		
			
			var infowindow;
			<?php if($map_display == 1) { ?>
			var multimarkerdata = new Array();
			<?php }?>
			var zoom_option = '<?php echo $map_display; ?>';
			

			function initialize() {
				var CITY_MAP_CENTER_LAT= '<?php echo $latitude?>';
				var CITY_MAP_CENTER_LNG= '<?php echo $longitude?>';
				var CITY_MAP_ZOOMING_FACT= <?php echo $zoom_level;?>;
				var myLatlng = new google.maps.LatLng(CITY_MAP_CENTER_LAT,CITY_MAP_CENTER_LNG);
				var mapOptions = {
				zoom: CITY_MAP_ZOOMING_FACT,
				center: myLatlng
			}
			var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

			var marker = new google.maps.Marker({
			  position: myLatlng,
			  map: map,
			  title: 'Hello World!'
			});
			}

			google.maps.event.addDomListener(window, 'load', initialize);

			</script>

			<div id="listing_google_map" class="listing_google_map" >
				<div class="map_sidebar">
				<div class="top_banner_section_in clearfix">
					<div class="TopLeft"><span id="triggermap"></span></div>
					<div class="TopRight"></div>
					<div id="map_canvas" style="width: 100%; height:<?php echo $heigh;?>px" class="map_canvas"></div>
				</div>
				</div>
			</div>  
			<?php
		}
		/* end */
	}
	/*Widget update function */
	function update($new_instance, $old_instance) {
		/*save the widget*/
		return $new_instance;
	}
	/*Widget admin form display function */
	function form($instance) {
		/*widgetform in backend*/
		$instance = wp_parse_args( (array) $instance, array( 'width' => '', 'heigh' => 500, 'clustering' => '','show_all_posts'=>'') );		
		$width = strip_tags($instance['width']);
		$heigh = strip_tags($instance['heigh']);
		$clustering = strip_tags($instance['clustering']);
		$show_all_posts = $instance['show_all_posts'];
	
		?>
	
		<p>
			<label for="<?php echo $this->get_field_id('heigh'); ?>"><?php  echo __('Map Height: <small>(Default is 500px. To change enter a numeric value.)</small>','templatic-admin');?>
			<input class="widefat" id="<?php echo $this->get_field_id('heigh'); ?>" name="<?php echo $this->get_field_name('heigh'); ?>" type="text" value="<?php echo esc_attr($heigh); ?>" />
			</label>
	    </p>
		<p>
		<?php if($clustering) { $checked = "checked=checked"; }else{ $checked =''; } ?>
			<label for="<?php echo $this->get_field_id('clustering'); ?>">
			<input  id="<?php echo $this->get_field_id('clustering'); ?>" name="<?php echo $this->get_field_name('clustering'); ?>" type="checkbox" value="1" <?php echo $checked; ?>/>&nbsp;<?php echo __('Disable Clustering','templatic-admin');?></label>
	    </p>
		<p>
		   <label id="<?php echo $this->get_field_id('show_all_posts'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('show_all_posts'); ?>" name="<?php echo $this->get_field_name('show_all_posts'); ?>" value="yes" <?php if(@$show_all_posts =='yes') echo 'checked';?>/>&nbsp;<?php echo __('Show all posts on category map',ADMINDOMAIN);?></label>
            <p class="description"><?php echo __('With large categories this can significantly increase category page load time. When this option is disabled, the map will only show posts from the current page.',ADMINDOMAIN);?></p>
		</p>
	    <?php
	}
}

?>