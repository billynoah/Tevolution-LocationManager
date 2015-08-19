<?php 
if(file_exists(TEVOLUTION_LOCATION_DIR.'functions/map/map-shortcodes/city_map_shortcode.php') ||( defined( 'DOING_AJAX' ) && isset($_REQUEST['action']) && $_REQUEST['action']=='shortcode_googlemap_initialize' )){
	include(TEVOLUTION_LOCATION_DIR.'functions/map/map-shortcodes/city_map_shortcode.php');
}
function location_shortcode_multicity_where(){
	global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table,$current_cityinfo,$short_code_city_id;
	
	if($current_cityinfo['city_id']!='' && $short_code_city_id!=''){		
		$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$short_code_city_id.", pm.meta_value ))";
	} 
	if(isset($_REQUEST['city_id']) && $_REQUEST['city_id']!=''){
		$where .= " AND $wpdb->posts.ID in (select pm.post_id from $wpdb->postmeta pm where pm.meta_key ='post_city_id' and FIND_IN_SET( ".$_REQUEST['city_id'].", pm.meta_value ))";
	}
	return $where;
}
function location_listing_format($post){ 
	global $post;
	add_filter('the_content','directory_the_content',20);
	$featured=get_post_meta(get_the_ID(),'featured_c',true);
	$classes=($featured=='c')?'featured_c':'';
	
	 $tmpdata = get_option('templatic_settings');
?>
    <div class="post <?php echo $classes;?>">  
    <?php do_action('directory_before_archive_image');           /*do_action before the post image */?>
    
    <?php do_action('directory_archive_page_image');?>  
      
    <?php do_action('directory_after_archive_image');           /*do action after the post image */?> 
    <div class="entry"> 
           <!--start post type title -->
		<?php do_action('directory_before_post_title');         /* do action for before the post title.*/ ?>
          
         <?php do_action('show_map_shortcode_content'); 		/* do action for content.*/ ?>
          
          <?php do_action('directory_after_post_title');          /* do action for after the post title.*/?>
          <!--end post type title -->
          <?php do_action('directory_post_info');                 /*do action for display the post info */ ?>     
          
          
          <!--Start Post Content -->
          <?php do_action('directory_before_post_content');       /* do action for before the post content. */ ?> 
          
         <?php
         $tmpdata = get_option('templatic_settings');
         if($tmpdata['listing_hide_excerpt']=='' || !in_array(get_post_type(),$tmpdata['listing_hide_excerpt'])){
               if(function_exists('supreme_prefix')){
                    $theme_settings = get_option(supreme_prefix()."_theme_settings");
               }else{
                    $theme_settings = get_option("supreme_theme_settings");
               }
               if($theme_settings['supreme_archive_display_excerpt']){
                    echo '<div itemprop="description" class="entry-summary">';
                    the_excerpt();
                    echo '</div>';
               }else{
                    echo '<div itemprop="description" class="entry-content">';
                    the_content(); 
                    echo '</div>';
               }
         }
          ?>
          
          <?php do_action('directory_after_post_content');        /* do action for after the post content. */?>
          <!-- End Post Content -->
          
          <!-- Show custom fields where show on listing = yes -->
          <?php do_action('directory_listing_custom_field');/*add action for display the listing page custom field */?>
          
          <?php do_action('templ_the_taxonomies');   ?>  
          
          <?php do_action('directory_after_taxonomies');?>
          
          <?php
		echo '<div class="rev_pin">';
		echo '<ul>';		
		
		$comment_count= count(get_comments(array('post_id' => $post->ID)));
		$review=($comment_count <=1 )? __('review','templatic'):__('reviews','templatic');
		?>
          <?php if(current_theme_supports('tevolution_my_favourites') && get_post_type($post->ID)!='post' ):?> 
               <li class="favourite"><?php tevolution_favourite_html();?></li>
          <?php endif;?>    
                     
		<li class="review"> <?php echo $comment_count.' <a href="'.get_permalink($post->ID).'#comments">'.$review.'</a>';?></li>
		<?php if($address!=""):?>
          <li class='pinpoint'><a id="pinpoint_<?php echo $post->ID;?>" class="ping" href="#map_canvas"><?php _e('Pinpoint','templatic');?></a></li>               
		<?php endif;?>
		<?php
		echo '</ul>';
		echo '</div>';
          ?>
    </div>
    </div><?php
	remove_filter('the_content','directory_the_content',20);
}
?>