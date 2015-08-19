<?php
/*
 * This file use for City dropdown city selector widget
 */
add_action('widgets_init','location_plugin_widgets_init');
/* Register widgets */
function location_plugin_widgets_init()
{	
	$location_options = get_option('location_options');
	if($location_options =='location_for_country'){
		register_widget('widget_location_for_country');
	}elseif($location_options  == 'location_for_cities'){
		register_widget('widget_location_for_cities');
	}else{
		register_widget('widget_location_post_city_id');
	}
}
/*
Multi city widget for global directory 
*/
class widget_location_post_city_id extends WP_Widget {
	
	function widget_location_post_city_id() {
		/*Constructor*/
		$widget_ops = array('classname' => 'Multi City Options', 'description' => __('Displays a dropdown for selecting a country, state and city. Use the widget only once per page. Works best in sidebar areas.','templatic-admin') );
		$this->WP_Widget('widget_post_city_id', __('T &rarr; City DropDown (Country &rsaquo; States &rsaquo; City)','templatic-admin'), $widget_ops);
	}
	function widget($args, $instance) {
		global $wpdb,$country_table,$zones_table,$multicity_table;
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		if(!session_id())
			session_start();			
		/* When we use drop down - pass the city id of posted city in session */
		if(isset($_POST['widget_city']) && $_POST['widget_city']!=""){
			$_SESSION['post_city_id']=$_POST['widget_city'];
		}
		$country_table = $wpdb->prefix."countries";
		$zones_table =$wpdb->prefix . "zones";
		$multicity_table = $wpdb->prefix . "multicity";
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		/* get the data of selected city id  */
		if(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!=''){
			if(get_query_var($multi_city)!='')
				$sql="SELECT * FROM $multicity_table where city_slug='".get_query_var($multi_city)."'";
			else
				$sql="SELECT * FROM $multicity_table where city_id=".$_SESSION['post_city_id'];
		}else{
			$sql="SELECT * FROM $multicity_table where is_default=1";
		}
		$default_city = $wpdb->get_results($sql);
		$default_city_id=$default_city[0]->city_id;
		if(!isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']=='')
			$_SESSION['post_city_id']=$default_city_id;
		$default_country_id=$default_city[0]->country_id;
		$default_zone_id=$default_city[0]->zones_id;
		$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta where `meta_key` ='post_city_id'");
		if($city_ids[0]->city_ids){
			foreach($city_ids as $ids){
				$cityids.=$ids->city_ids.",";
			}
			$cityids=str_replace(",","','",substr($cityids,0,-1));
			$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,GROUP_CONCAT(mc.cityname) as cityname, GROUP_CONCAT(mc.city_slug) as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 group by country_name order by country_name ASC");
		}
		$zoneinfo = $wpdb->get_results("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id=$default_country_id  order by zone_name ASC");
		
		if(!empty($default_zone_id))
		$cityinfo = $wpdb->get_results("SELECT * FROM $multicity_table mc where mc.zones_id=$default_zone_id AND country_id=$default_country_id order by cityname ASC");
		
		echo $args['before_widget'];
		?>
		<div id="widget_location">
            <?php echo $args['before_title'].$title.$args['after_title'];?>
			<ul class="widget_location_nav">
				<li>
					<select name="widget_country" id="widget_country">
						<option value=""><?php _e('Select Country','templatic');?></option>
					<?php foreach($countryinfo as $country): $selected=($country->country_id==$default_country_id)? 'selected':'';
						$country_name=$country->country_name;
						 if (function_exists('icl_register_string')){
								icl_register_string('location-manager', 'location_country_'.$country->country_id,$country_name);
								$country_name = icl_t('location-manager', 'location_country_'.$country->country_id,$country_name);
						  }
					?>
						<option value="<?php echo $country->country_id; ?>" <?php echo $selected;?>><?php echo $country_name;?></option>
					<?php endforeach; ?>
					</select>
				</li>
				<li>
					<select name="widget_zone" id="widget_zone">
						<option value=""><?php _e('All Regions','templatic');?></option>
					<?php foreach($zoneinfo as $zone): $selected=($zone->zones_id ==$default_zone_id)? 'selected':'';
							$zone_name=$zone->zone_name;
							 if (function_exists('icl_register_string')) {
									icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
									$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
							  }
					?>
						<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
					<?php endforeach;?>
					</select>
				</li>
				<li>
					<form name="widget_multicity_form" id="widget_multicity_form" action="<?php echo home_url( '/' ); ?>" method="post">
						<select name="widget_city" id="widget_city">
							<option value=""><?php _e('All Cities','templatic');?></option>
						<?php foreach($cityinfo as $city): $selected=($city->city_id ==$default_city_id)? 'selected':'';
								   $cityname=$city->cityname;
								   if (function_exists('icl_register_string')) {
										icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
										$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
								   }
						?>
							<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname;?></option>
						<?php endforeach;?>
						</select>
					</form>
				</li>
			</ul>
		</div>
		<?php
		/* Set the multicity info*/
		location_current_multicity();
		echo $args['after_widget'];		
	}
	function update($new_instance, $old_instance) {
		/*save the widget*/
		return $new_instance;
	}
	function form($instance) {
		/*widgetform in backend*/
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Multicity') );
		$title = strip_tags($instance['title']);
		$desc1 = ($instance['desc1']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Widget Title','templatic-admin');?>: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}
}
/*
 * multi city widget for one country and multiple state
 */
class widget_location_for_country extends WP_Widget {
	
	function widget_location_for_country() {
		/*Constructor*/
		$widget_ops = array('classname' => 'Multi City Options', 'description' => __('Displays a dropdown for state and city. Use the widget only once per page. Works best in sidebar areas.','templatic-admin') );		
		$this->WP_Widget('widget_post_city_id', __('T &rarr; City DropDown From Country','templatic-admin'), $widget_ops);
	}
	function widget($args, $instance) {
		global $wpdb,$country_table,$zones_table,$multicity_table;
		$title = empty($instance['title']) ? __('Multicity','templatic-admin') : apply_filters('widget_title', $instance['title']);
		if(!session_id())
			session_start();		
		/* When we use drop down - pass the city id of posted city in session */
		if(isset($_POST['widget_city']) && $_POST['widget_city']!=""){
			$_SESSION['post_city_id']=$_POST['widget_city'];
		}
		$country_table = $wpdb->prefix."countries";
		$zones_table =$wpdb->prefix . "zones";
		$multicity_table = $wpdb->prefix . "multicity";
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		/* get the data of selected city id  */
		if(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!=''){
			if(get_query_var($multi_city)!='')
				$sql="SELECT * FROM $multicity_table where  city_slug='".get_query_var($multi_city)."'";
			else
				$sql="SELECT * FROM $multicity_table where city_id=".$_SESSION['post_city_id'];
		}else{
			$sql="SELECT * FROM $multicity_table where is_default=1";
		}
		$default_city = $wpdb->get_results($sql);
		$default_city_id=$default_city[0]->city_id;
		if(!isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']=='')
			$_SESSION['post_city_id']=$default_city_id;
		$default_country_id = get_option('directory_country_id');
		$default_zone_id=$default_city[0]->zones_id;
		$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta where `meta_key` ='post_city_id'");
		if($city_ids[0]->city_ids){
			foreach($city_ids as $ids){
				$cityids.=$ids->city_ids.",";
			}
			$cityids=str_replace(",","','",substr($cityids,0,-1));
			$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,GROUP_CONCAT(mc.cityname) as cityname, GROUP_CONCAT(mc.city_slug) as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 group by country_name order by country_name ASC");
		}
		/* get the states of selected country from manage locations section */
		$zoneinfo = $wpdb->get_results("SELECT distinct z.zones_id,z.* FROM $zones_table z, $multicity_table mc where z.zones_id=mc.zones_id AND mc.country_id =$default_country_id order by zone_name ASC");		
		/* get the cities of selected country from manage locations section */
		if(!empty($default_zone_id))
			$cityinfo = $wpdb->get_results("SELECT * FROM $multicity_table mc where mc.city_id in('$cityids') and zones_id=$default_zone_id AND mc.country_id =$default_country_id order by cityname ASC");
			
		echo $args['before_widget'];
		?>
		<div id="widget_location">
               <?php echo $args['before_title'].$title.$args['after_title'];?>
			<ul class="widget_location_nav">				
				<li>
					<select name="widget_zone" id="widget_zone">
						<option value=""><?php _e('All Regions','templatic');?></option>
					<?php foreach($zoneinfo as $zone): $selected=($zone->zones_id ==$default_zone_id)? 'selected':'';
							$zone_name=$zone->zone_name;
							 if (function_exists('icl_register_string')){
									icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
									$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
							  }
					?>
						<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo $zone_name;?></option>
					<?php endforeach;?>
					</select>
				</li>
				<li>
					<form name="widget_multicity_form" id="widget_multicity_form" action="<?php echo home_url( '/' ); ?>" method="post">
						<select name="widget_city" id="widget_city">
							<option value=""><?php _e('All Cities','templatic');?></option>
						<?php foreach($cityinfo as $city): $selected=($city->city_id ==$default_city_id)? 'selected':'';
								   $cityname=$city->cityname;
								   if (function_exists('icl_register_string')){
										icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
										$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
								   }
						?>
							<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname;?></option>
						<?php endforeach;?>
						</select>
					</form>
				</li>
			</ul>
		</div>
		<?php		
		/* Set the multicity info*/
		location_current_multicity();
		echo $args['after_widget'];		
	}
	function update($new_instance, $old_instance) {
		/*save the widget*/
		return $new_instance;
	}
	function form($instance) {
		/*widgetform in backend*/
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Multicity') );
		$title = strip_tags($instance['title']);
		$desc1 = ($instance['desc1']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Widget Title','templatic-admin');?>: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}
}
/*
 * multi city widget for all cities 
 */
class widget_location_for_cities extends WP_Widget {
	
	function widget_location_for_cities() {
		/*Constructor*/
		$widget_ops = array('classname' => 'Multi City Options', 'description' => __('Displays a drop down for city. Works best in sidebar areas.','templatic-admin') );
		$this->WP_Widget('widget_post_city_id', __('T &rarr; City DropDown','templatic-admin'), $widget_ops);
	}
	function widget($args, $instance) {
		global $wpdb,$country_table,$zones_table,$multicity_table;
		$title = empty($instance['title']) ? __('Multicity','templatic-admin') : apply_filters('widget_title', $instance['title']);
		if(!session_id())
			session_start();
			
		/* When we use drop down - pass the city id of posted city in session */
		if(isset($_POST['widget_city']) && $_POST['widget_city']!=""){
			$_SESSION['post_city_id']=$_POST['widget_city'];
		}
		$country_table = $wpdb->prefix."countries";
		$zones_table =$wpdb->prefix . "zones";
		$multicity_table = $wpdb->prefix . "multicity";
		$city_slug=get_option('location_multicity_slug');
		$multi_city=($city_slug)? $city_slug : 'city';
		/* get the data of selected city id  */
		if(isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!=''){
			if(get_query_var($multi_city)!='')
				$sql="SELECT * FROM $multicity_table where  city_slug='".get_query_var($multi_city)."'";
			else
				$sql="SELECT * FROM $multicity_table where city_id=".$_SESSION['post_city_id'];
		}else{
			$sql="SELECT * FROM $multicity_table where is_default=1";
		}
		$default_city = $wpdb->get_results($sql);
		$default_city_id=$default_city[0]->city_id;
		if(!isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']=='')
			$_SESSION['post_city_id']=$default_city_id;
		
		/* to show the city which contains the post - means the city dont have post will not display */
		
		$city_ids=$wpdb->get_results("SELECT GROUP_CONCAT(distinct meta_value) as city_ids from {$wpdb->prefix}postmeta as pm,{$wpdb->prefix}posts as p where pm.post_id=p.ID AND p.post_status='publish' AND pm.meta_key = 'post_city_id' GROUP BY pm.post_id");
		
		foreach($city_ids as $cids){
			$cityids .= $cids->city_ids.",";
		}
		
		
		if(!empty($cityids)){
			foreach($city_ids as $ids){
				$cityids.=$ids->city_ids.",";
			}
			$cityids=str_replace(",","','",substr($cityids,0,-1));
			$countryinfo = $wpdb->get_results("SELECT  distinct  c.country_id,c.country_name,GROUP_CONCAT(mc.cityname) as cityname, GROUP_CONCAT(mc.city_slug) as city_slug   FROM $country_table c,$multicity_table mc where mc.city_id in('$cityids') AND c.`country_id`=mc.`country_id`  AND c.is_enable=1 group by country_name order by country_name ASC");
		}
		/* get all the cities */
		$cityinfo = $wpdb->get_results("SELECT * FROM $multicity_table mc where mc.city_id in('$cityids') order by cityname ASC");
		echo $args['before_widget'];
		?>
		<div id="widget_location">
               <?php echo $args['before_title'].$title.$args['after_title'];?>
			<ul class="widget_location_nav">
				<li>
					<form name="widget_multicity_form" id="widget_multicity_form" action="<?php echo home_url( '/' ); ?>" method="post">
						<select name="widget_city" id="widget_city">
							<option value=""><?php _e('All Cities','templatic');?></option>
						<?php foreach($cityinfo as $city): $selected=($city->city_id ==$default_city_id)? 'selected':'';
								   $cityname=$city->cityname;
								   if (function_exists('icl_register_string')) {
										icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
										$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
								   }
						?>
							<option value="<?php echo $city->city_id?>" <?php echo $selected;?>><?php echo $cityname;?></option>
						<?php endforeach;?>
						</select>
					</form>
				</li>
			</ul>
		</div>
		<?php
		/* Set the multicity info*/
		location_current_multicity();
		echo $args['after_widget'];
	}
	function update($new_instance, $old_instance) {
		/*save the widget*/
		return $new_instance;
	}
	function form($instance) {
		/*widgetform in backend*/
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Multicity') );
		$title = strip_tags($instance['title']);
		$desc1 = ($instance['desc1']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Widget Title','templatic-admin');?>: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}
}