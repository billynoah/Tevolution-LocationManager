<?php
/*
 * This file user add/edit country, State and city section from backend also get the country wise state and state wise city using ajax and set global current city info
 * Add action for display the directory locations content page
 */
add_action('admin_head','location_manage_locations_scriptjs');
function location_manage_locations_scriptjs($taxonomy){ ?>
	<script  type="text/javascript" async >
		function get_categories_checklist(str,city_id){
			if (str==""){
				document.getElementById("field_category").innerHTML="";
				return;
			}else{
				document.getElementById("field_category").innerHTML="";
				document.getElementById("process").style.display ="block";
			}
			var checkedValue = null;
			var checkedValue = '';
			var inputElements = document.getElementsByName('city_post_type[]');
			for(var i=0; inputElements[i]; ++i){
				if(inputElements[i].checked){
					checkedValue += inputElements[i].value +",";
				}
			}
			var cityid='';
			if(city_id!=''){
				cityid='&city_id='+city_id;
			}
			jQuery.ajax({
				url:ajaxUrl,
				type:'POST',
				data:'action=GetCategories_fn&post_type='+checkedValue+'&mod=custom_fields'+cityid,
				success:function(results){
					document.getElementById("process").style.display ="none";
					document.getElementById("field_category").innerHTML=results;
				}
			});
		}
function displaychk_frm(){
	dml = document.forms['price_frm'];
	chk = dml.elements['category[]'];
	len = dml.elements['category[]'].length;
	if(document.getElementById('selectall').checked == true) {
		for (i = 0; i < len; i++)
		chk[i].checked = true ;
	}else{
		for (i = 0; i < len; i++)
		chk[i].checked = false ;
	}
}
jQuery(document).ready(function(){
	jQuery("#cate_set_zooming_opt1, #cat_set_zooming_opt").click(function(){
		if(jQuery(this).val()==1){
			jQuery('#category_map_scaling_factor').hide();
		}else{
			jQuery('#category_map_scaling_factor').show();
		}
	});
});
function location_for_country_(cid){
	if(cid =='location_for_country'){
		document.getElementById('directory_country_id').style.display ='inline-block';
	}else{
		document.getElementById('directory_country_id').style.display ='none';
	}
}
<?php if(isset($_REQUEST['page']) && $_REQUEST['page'] =='location_settings' && isset($_REQUEST['action']) && $_REQUEST['action']=='addnew'):?>
window.onload = function(){
					jQuery('#selectall').attr('checked','checked');
                    displaychk_frm();
                };
<?php endif;?>
</script>
<?php 
}
/**
* Function: GetCategories_fn_callback
* Filter: wp_ajax_nopriv_XXX and wp_ajax_XXX
* Return: Get all categories for selected post type
*/
add_action('wp_ajax_nopriv_GetCategories_fn','GetCategories_fn_callback');
add_action('wp_ajax_GetCategories_fn','GetCategories_fn_callback');
function GetCategories_fn_callback(){
	global $wpdb,$country_table,$zones_table,$multicity_table;
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	if(empty($_REQUEST['post_type']) || $_REQUEST['post_type']==""){
		echo '<ul><li>'.__("Please select any post type.",LMADMINDOMAIN).'</li></ul>';
		exit;
	}
	$my_post_type = explode(",",$_REQUEST['post_type']);
	$catid = $_REQUEST['mcatid'];
	$term_icon = $_REQUEST['term_icon'];
	$cprice = $_REQUEST['cprice'];
	$categories='';
	if(isset($_REQUEST['city_id']) && $_REQUEST['city_id']!=''){
		$cityinfo = $wpdb->get_results($wpdb->prepare("select categories  from $multicity_table where city_id =%d",$_REQUEST['city_id'] ));
		$categories=$cityinfo[0]->categories;
	}
	for($c=0 ; $c < count($my_post_type) ; $c ++){
		if($my_post_type[$c] !=''){
			if($c ==0){
			get_location_category_checklist($my_post_type[$c],$categories,$_REQUEST['mod'],'select_all');
			}else{
			get_location_category_checklist($my_post_type[$c],$categories,$_REQUEST['mod'],'');
			}
		}
	}
	exit;
}
add_action('location_selection_option','location_selection_option');
/* to give the locations options in back end , choose how city selections come in front end and how user want to create directory */

function location_selection_option(){
	global $wpdb,$country_table,$zones_table,$multicity_table;
	/* get multi city options */
	$directory_citylocation_view=get_option('directory_citylocation_view');
	/* get location options */
	$location_options=get_option('location_options');
	/* get country id if its one country directory */
	$directory_country_id=get_option('directory_country_id');
	$country_table = $wpdb->prefix."countries";
	$countries = $wpdb->get_results("select country_id,country_name from $country_table");
	
	/* check the country have posts or not - not blank country display */
	$country_ids=$wpdb->get_results("select country_id from $multicity_table");

	if(!empty($country_ids)){
		foreach($country_ids as $ids){
			if(!empty($ids->country_id))
				$country_ids1 .= "'".$ids->country_id."',";
		}
		$country_ids_= rtrim($country_ids1,','); /* remove last comma*/
		/* get the cities - to get all countries remove "where ct.country_id in($country_ids_) group by country_name" from below query */
		$countries = $wpdb->get_results("select country_id,country_name from $country_table ct where ct.country_id in($country_ids_) group by country_name order by country_name ASC");
	}
	
	if(!empty($countries)){
		foreach($countries as $cid){
			if($directory_country_id == $cid->country_id){ $selected ='selected=selected';}else{  $selected =''; }
			$options .= "<option value ='".$cid->country_id."' ".$selected.">".$cid->country_name."</option>";
		}
	}
	if($location_options =='location_for_country' ){ $style="style="; }else{$style="style=display:none;"; }
	?>
     <tr class="directory_city_option">
		<th><label><?php echo __('City selector style',LMADMINDOMAIN);?> </label></th>
		<td>
		<fieldset>
		  <label for="location_asdropdown"><input type="radio" id="location_asdropdown" name="directory_citylocation_view" value="location_asdropdown" <?php if($directory_citylocation_view=='location_asdropdown'){echo 'checked';}?> />&nbsp;<?php echo __('Dropdowns',LMADMINDOMAIN);?>&nbsp;&nbsp;</label>
		  <label for="location_aslink"><input type="radio" id="location_aslink" name="directory_citylocation_view" value="location_aslink" <?php if($directory_citylocation_view=='location_aslink'){echo 'checked';}?>/>&nbsp;<?php echo __('Links',LMADMINDOMAIN);?>&nbsp;&nbsp;</label>
		</fieldset>
		<p class="description"><?php echo __('City selector is used to change the current city. It is located inside the header. ',LMADMINDOMAIN);?></p>
        </td>
     </tr>
	 <tr class="directory_city_option">
		<th><label><?php echo __('City selector levels',LMADMINDOMAIN);?> </label></th>
		<td><fieldset><label for="location_default"><input type="radio" id="location_default" name="location_options" value="location_default" <?php if($location_options=='location_default'){echo 'checked';}?>  onclick="location_for_country_(this.id);"/>&nbsp;<?php echo __('All Countries',LMADMINDOMAIN);?>&nbsp;&nbsp;</label>
		<label for="location_for_country"><input type="radio" id="location_for_country" name="location_options" value="location_for_country" <?php if($location_options=='location_for_country'){echo 'checked';}?> onclick="location_for_country_(this.id);"/>&nbsp;<?php echo __('One Country',LMADMINDOMAIN);?>&nbsp;&nbsp;</label>
		  <select id="directory_country_id" name="directory_country_id" <?php echo $style; ?>>
				<?php echo $options; ?>
		  </select>
		<label for="location_for_cities"><input type="radio" id="location_for_cities" name="location_options" value="location_for_cities" <?php if($location_options=='location_for_cities'){echo 'checked';}?>  onclick="location_for_country_(this.id);"/>&nbsp;<?php echo __('Only Cities',LMADMINDOMAIN);?>&nbsp;&nbsp;</label></fieldset>
		<p class="description"><?php echo __('1) Displays a 3-level city selector Country > State > City with ability to change country. </br> 2) One Country: Displays a 2-level city selector State > City without ability to change country. </br> 3) Only Cities: Displays a 1-level city selector without countries or states.',LMADMINDOMAIN);?></p>	</td>
     </tr>
     <?php
}

/*
 * This function manage country, state and city manage tab and also manage location manager related setting option
 */
add_action('location_tabs_content','location_manage_locations_tab');
function location_manage_locations_tab($location_tabs='location_manage_locations'){
	switch ($location_tabs):
		case 'location_manage_locations':		
		 global $wpdb,$country_table,$zones_table,$multicity_table;
				 if(isset($_POST['location_submit'])){
						update_option('directory_citylocation_view',$_POST['directory_citylocation_view']);
						update_option('location_options',$_POST['location_options']);
						if(isset($_POST['location_options']) && $_POST['location_options']=='location_for_country'){
							update_option('directory_country_id',$_POST['directory_country_id']);
						}
						/*Delete the  multi city post type */
						$post_content = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_name = %s and $wpdb->posts.post_type = %s",'post_city_id','custom_fields'));
						$post_id=$post_content->ID;
						 $total_post_type = get_option('templatic_custom_post');
						 delete_post_meta($post_id, 'post_type');
						 delete_post_meta($post_id, 'post_type_post');
						 delete_post_meta($post_id, 'taxonomy_type_category');
						 foreach($total_post_type as $key=> $_total_post_type)
						 {
							delete_post_meta($post_id, 'post_type_'.$key.'');
						 	delete_post_meta($post_id, 'taxonomy_type_'.$_total_post_type['slugs'][0].'');
						 }
						 if(isset($_POST['location_post_type']) && $_POST['location_post_type']!="")
						 {
							 $post_type = $_POST['location_post_type'];
							 foreach($post_type as $_post_type){				 
									$post_type_ex = explode(",",$_post_type);
									update_post_meta($post_id, 'post_type_'.$post_type_ex[0].'', $post_type_ex[0]);
									update_post_meta($post_id, 'taxonomy_type_'.$post_type_ex[1].'', $post_type_ex[1]);
									$finpost_type .= $post_type_ex[0].",";
							  }
							 update_post_meta($post_id, 'post_type',substr($finpost_type,0,-1));
						 }
						/*Finish the multi cisty post type */
						update_option('location_post_type',$_POST['location_post_type']);
						update_option('location_tracking',$_POST['location_tracking']);
						update_option('disable_city_log',$_POST['disable_city_log']);
						/*Location Tracking option */
						if(isset($_POST['geoip_location_tracking']))
						{
							update_option('geoip_location_tracking',$_POST['geoip_location_tracking']);
						}
						if( (!is_dir(TEVOLUTION_LOCATION_DIR."maxmind_location_geoip") && $_POST['geoip_location_tracking']=='maxmind' )){
							update_option('geoip_location_tracking','freegeoip');
							$maxmind_geoip='1';
						}
						update_option('default_city_set',$_POST['default_city_set']);
						$message = __('Record updated successfully.',LMADMINDOMAIN);
				  }
				  if(isset($_REQUEST['msg']) && $_REQUEST['msg']==1){
					$message = __('Record updated successfully.',LMADMINDOMAIN);
				  }
				 $directory_citylocation_view=get_option('directory_citylocation_view');
				 $default_city_set=get_option('default_city_set');
				 $location_tracking=get_option('location_tracking');
				 $disable_city_log=get_option('disable_city_log');
				 $geoip_location_tracking=get_option('geoip_location_tracking');
				 
				if(@$message){?>
				<div class="updated fade below-h2" id="message" style="padding:5px; font-size:12px;" >
				  <?php echo $message;?>
				</div>
				<?php }
				if($maxmind_geoip==1):?>
                <div class="error fade below-h2" id="message" style="padding:5px; font-size:12px;" >
					<?php echo __('The MaxMind option cannot be saved because you have not placed MaxMind folder under plugins/Tevolution-LocationManager.','LMADMINDOMAIN');?>
                </div>
                <?php endif;
			if(!isset($_REQUEST['action'])){ /* show option only on city listing page*/
			?>
			<form name="location_settings" id="location_settings" action="" method="post">
			<table class="form-table manage-locations">
               <?php do_action('location_selection_option');?>
				<tr class="directory_city_option">
                    <th><?php _e('Location Tracking Method',LMADMINDOMAIN);?></th>
                    <td>
                        <fieldset>
                            <label for="location_tracking_freegeoip"><input id="location_tracking_freegeoip" type="radio" name="geoip_location_tracking" value="freegeoip" <?php if($geoip_location_tracking=='freegeoip' || $geoip_location_tracking==''){echo 'checked';}?> />&nbsp;<?php echo __('Freegeoip',LMADMINDOMAIN);?></label>
                            <label for="location_tracking_maxmind"><input id="location_tracking_maxmind" type="radio" name="geoip_location_tracking" value="maxmind" <?php if($geoip_location_tracking=="maxmind"){echo 'checked';}?> />&nbsp;<?php echo __('MaxMind',LMADMINDOMAIN);?></label>
                            <p class="maxmind_description" <?php if($geoip_location_tracking!='maxmind'):?>style="display:none"<?php endif;?>><?php echo sprintf(__('Download this <a href="%s">zip file</a>, extract it and place it into wp-content/plugins/Tevolution-LocationManager/ folder.',LMADMINDOMAIN),'http://templatic.com/_data/plugins/Maxmind_Location_Geoip.zip');?></p>
                        </fieldset>
                    </td>
                </tr>
				<tr class="directory_city">
					<th><label><?php echo __('Homepage Displays',LMADMINDOMAIN);?></label></th>
					<td><?php $cityinfo = $wpdb->get_results("SELECT cityname FROM $multicity_table where is_default=1");?>
						<label for="default_city_set"><input type="radio" onclick="change_default_city_set(this);" id="default_city_set" name="default_city_set" value="default_city" <?php if($default_city_set=='default_city'){echo "checked='checked'";}?>/>&nbsp;<span id="default_city_name"><?php echo __('Default City',LMADMINDOMAIN).' ('.$cityinfo[0]->cityname.')';?></span></label>&nbsp;&nbsp;
                              <label for="location_tracking_set"><input type="radio" onclick="change_default_city_set(this);" id="location_tracking_set" name="default_city_set" value="location_tracking" <?php if($default_city_set=='location_tracking'){echo "checked='checked'";}?>/>&nbsp;<?php echo __('Ask To Show Nearest City',LMADMINDOMAIN);?></label>&nbsp;&nbsp;
						<label for="nearest_city_set"><input type="radio" onclick="change_default_city_set(this);" id="nearest_city_set" name="default_city_set" value="nearest_city" <?php if($default_city_set=='nearest_city'){echo "checked='checked'";}?>/>&nbsp;<?php echo __('Nearest City',LMADMINDOMAIN);?></label>
						<p class="description" id="default_city_set_msg" <?php if($default_city_set!='default_city'){ echo "style='display:none'"; } ?>><?php echo __('Displays listings from your default city to visitors once they land on your homepage.',LMADMINDOMAIN);?></p>
                              <p class="description" id="nearest_city_set_msg" <?php if($default_city_set!='nearest_city'){ echo "style='display:none'"; } ?>><?php echo __('Show listings from the city nearest to the visitors without asking to share their location. Note: Your cities must have atleast one listing added to get tracked as nearest city in front end. ',LMADMINDOMAIN);?><a href="http://templatic.com/docs/tevolution-location-manager/#generalsettings" target="_blank"><?php echo __('Need help?',LMADMINDOMAIN);?></a></p>
						<p  id="location_tracking" class="description" <?php if($default_city_set!='location_tracking'){ echo "style='display:none'"; } ?>><?php echo __('Asks visitors to share their location and if they agree, they are shown their nearest city. If visitor doesnt share location, listings from the default city will be displayed.',LMADMINDOMAIN);?></p>
					</td>
				</tr>
                    <tr class="location_option">
					<th><label><?php echo __('Activate location management for',LMADMINDOMAIN);?></label></th>
					<td>
					<div class="locaiton_post_type">
						<fieldset class="location_post_type_option">
						<?php
							$location_post_type=get_option('location_post_type');
							$post_types=get_option('templatic_custom_post');
							if(!empty($location_post_type)){
								if(in_array('post,category,post_tag',$location_post_type)){
									$post_checked ='checked="checked"';
								}else{
									$post_checked ='';
								}
							}else{
								$post_checked ='';
							}
							?>
							<label for="location_post"><input type="checkbox" name="location_post_type[]" value="post,category,post_tag" <?php echo $post_checked;  ?> id="location_post"  />&nbsp;<?php echo 'Post';?></label><br/>
						<?php foreach($post_types as $key=>$post_type):
								
								if((isset($key) && !empty($key)) || (is_array($post_type['slugs']) && count($post_type['slugs'])>0))	
									$value=$key.','.implode(',',$post_type['slugs']);
									
								if(!empty($location_post_type)){
									if(in_array($value,$location_post_type)){
										$checked = 'checked="checked"';
									}else{ $checked=''; }
								}else{
									$checked='';
								}
							?>
							<label for="location_<?php echo $key?>"><input type="checkbox" name="location_post_type[]" value="<?php echo $value;?>" <?php ?> id="location_<?php echo $key?>" <?php echo $checked; ?> />&nbsp;<?php echo $post_type['label'];?></label><br/>
						<?php endforeach;
						if(empty($location_post_type)){
							echo "<p id='default_city_set_msg' class='description'>"; 
							_e('We Suggest you to deactivate the <span style="color:red;">Tevolution-LocationManager</span> plugin instead of disabling all the post types, as there is not single feature work if you don&#39;t select any posttype.',ADMINDOMAIN);
							echo "</p>";
						}
						?>
						</fieldset>
					</div></td>
				</tr>
                <tr>
                	<th><label><?php echo __('Track city visits (city logs)',LMADMINDOMAIN);?></label></th>
                	<td>
					<div class="input-switch">
						<input type="checkbox" id="disable_city_log" name="disable_city_log" value="1" <?php if($disable_city_log==1){echo "checked='checked'";}?>/>
						<label for="disable_city_log">&nbsp;<?php echo __('Enable',LMADMINDOMAIN);?></label>
					</div>
                    <p class="description"><?php echo __('Enabling this option will turn on city logging and show the "City Logs" tab. ',LMADMINDOMAIN);?></p>
                    </td>
                </tr>
				<tr><td colspan="2">
				<input type="submit" name="location_submit" value="<?php echo __('Save All Changes',LMADMINDOMAIN); ?>" class="button button-primary button-hero"  />
				</td></tr>				
			</table>
			</form>
				<?php				
			 } /* end */
			/*do action for display the manage locations content */
			break;
			case ('countries_manage_locations' || 'manage_state_locations' || 'city_manage_locations') :
				$subtabs_content=isset($_REQUEST['location_tabs'])?$_REQUEST['location_tabs']:'countries_manage_locations';
				do_action('manage_location_content',$subtabs_content);
			break;
	endswitch;
}
/*
 * Function Name: manage_countries_locations
 * Return: add and display the countries
 */
add_action('manage_location_content','manage_countries_locations');
function manage_countries_locations($location_tabs='countries_manage_locations'){
	
	switch ($location_tabs):
		case 'countries_manage_locations' :
				?>
                    <div class="wrap">
                         <?php if(isset($_REQUEST['action']) && $_REQUEST['action']=='addnew'): ?>                         
                              <div class="tevo_sub_title"><?php echo __('Add a Country',LMADMINDOMAIN);?>
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=countries_manage_locations';?>" title="<?php echo __('Back to countries list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to countries list',LMADMINDOMAIN); ?></a>
                              </div>
                         	<?php add_edit_countries();
							elseif(isset($_REQUEST['action']) && $_REQUEST['action']=='edit'):?>
                         	 <div class="tevo_sub_title"><?php echo __('Edit Country',LMADMINDOMAIN);?>
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=countries_manage_locations';?>" title="<?php echo __('Back to countries list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to countries list',LMADMINDOMAIN); ?></a>
                              </div>
                               <?php add_edit_countries();
							 else:/* Display countries List*/?>
                       		 <!--Display the countries list -->
                             <div class="tevo_sub_title"><?php echo __('Manage Countries',LMADMINDOMAIN);?>
                                   <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=countries_manage_locations&action=addnew';?>" title="<?php echo __('Add a field for country',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Add new country',LMADMINDOMAIN); ?></a>
                              </div>
                              <p class="tevolution_desc"><?php echo __('Add and manage your country details from this section. To add new country, use above add link.<br> This section helps your user to select between all the countries available on your site.',LMADMINDOMAIN);?></p>
                                <?php
							if(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype']=='add-suc')
								$message = __('Country created successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='edit-suc')
								$message = __('Country updated successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='dele-suc')
								$message = __('Country deleted  successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='enable-suc')
								$message = __('Country enable successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='disable-suc')
								$message = __('Country disable successfully.',LMADMINDOMAIN);
                              if(@$message){?>
                              <div class="updated fade below-h2" id="message" style="padding:5px; font-size:12px;" >
                                <?php echo $message;?>
                              </div>
                              <?php }?>
                             <form name="frm_country" id="frm_country" action="" method="post" >
                                   <?php
                                   $directory_country_table = new wp_list_manage_countries();
                                   $directory_country_table->prepare_items();
                                   $directory_country_table->search_box('search', 'search_id');
                                   $directory_country_table->display();
                                   ?>
                                   <input type="hidden" name="check_compare">
                              </form>
                         <?php endif;?>
                    </div>
				<?php
			break;
	endswitch;
}
/*
 * Display the country, state city and maneg locations form in admin manage locations section
 */
add_action('manage_location_content','manage_state_locations');
function manage_state_locations($location_tabs='state_manage_locations'){
	switch ($location_tabs):
		case 'state_manage_locations' :
				?>
                      <div class="wrap">
                          <?php if(isset($_REQUEST['action']) && $_REQUEST['action']=='addnew'): ?>
                              <div class="tevo_sub_title"><?php echo __('Add a State',LMADMINDOMAIN);?>	
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations';?>" title="<?php echo __('Back to states list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to states list',LMADMINDOMAIN); ?></a>
                              </div>
                         	<?php add_edit_zone();
						elseif(isset($_REQUEST['action']) && $_REQUEST['action']=='edit'):?>
                         	<div class="tevo_sub_title"><?php echo __('Edit State',LMADMINDOMAIN);?>
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations';?>" title="<?php echo __('Back to states list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to states list',LMADMINDOMAIN); ?></a>
                              </div>
                               <?php add_edit_zone();
						else:/* Display countries List*/?>
                       		 <!--Display the countries list -->                              
                              <div class="tevo_sub_title"><?php echo __('Manage States',LMADMINDOMAIN);?>
                                   <a id="zone_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations&action=addnew';?>" title="<?php echo __('Add a field for state',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Add new State',LMADMINDOMAIN); ?></a>
                              </div>
                              <p class="tevolution_desc"><?php echo __('Add and manage your state details from this section. You can add new states using above add link.<br> These states will be listed in the dropdown according to the country selected by your user.',LMADMINDOMAIN);?></p>
                                <?php
							if(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype']=='add-suc') 
								$message = __('State created successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='edit-suc')
								$message = __('State updated successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='dele-suc')
								$message = __('State deleted  successfully.',LMADMINDOMAIN);
                                
                              if(@$message){?>
                              <div class="updated fade below-h2" id="message" style="padding:5px; font-size:12px;" >
                                <?php echo $message;?>
                              </div>
                              <?php }?>                        
                         <form name="frm_zone" id="frm_zone" action="" method="post" >
						<?php
                              $directory_state_table = new wp_list_manage_states();
                              $directory_state_table->prepare_items();
                              $directory_state_table->search_box('search', 'search_id');
                              $directory_state_table->display();
						?>
                         </form>
                         <?php endif;?>
                    </div>
				<?php
			break;
	endswitch;
}
/*
 * Function Name: manage_city_locations
 * Return: add and display the city
 */
add_action('manage_location_content','manage_city_locations');
function manage_city_locations($location_tabs='city_manage_locations'){
	
	global $wpdb,$country_table,$zones_table,$multicity_table;
	switch ($location_tabs):
		case 'city_manage_locations' :
				?>
                      <div class="wrap">
                          <?php if(isset($_REQUEST['action']) && $_REQUEST['action']=='addnew'): ?>                         
                              <div class="tevo_sub_title"><?php echo __('Add a city',LMADMINDOMAIN);?>
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations';?>" title="<?php echo __('Back to city list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to city list',LMADMINDOMAIN); ?></a>
                              </div>
                              <p class="tevolution_desc"><?php echo __('Adequate information will lead to accurate results and map. So, please enter all information accordingly.',LMADMINDOMAIN);?></p>
                         	<?php add_edit_multicity();
						elseif(isset($_REQUEST['action']) && $_REQUEST['action']=='edit'):?>
                         	<div class="tevo_sub_title"><?php echo __('Edit city',LMADMINDOMAIN);?>
                              <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations';?>" title="<?php echo __('Back to city list',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to city list',LMADMINDOMAIN); ?></a>
                              </div>
                              <p class="tevolution_desc"><?php echo __('For accurate results, please enter the adequate information.',LMADMINDOMAIN);?></p>
                               <?php add_edit_multicity();
						else:/* Display countries List*/?>
                       		 <!--Display the countries list -->                              
                              <div class="tevo_sub_title"><?php echo __('Manage Cities',LMADMINDOMAIN);?>
                                   <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations&action=addnew';?>" title="<?php echo __('Add a field for country',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Add new city',LMADMINDOMAIN); ?></a>
                              </div>
                              <p class="tevolution_desc"><?php echo __('<b>Important note:</b> Cities will appear in front-end city selection box only after you add some listings/events to them. <br/><br/>',LMADMINDOMAIN);?></p>
                                <?php
							if(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype']=='add-suc')
								$message = __('City created successfully. Until you do not create any post for this particular city, it will not appear in a navigation strip for selection.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='edit-suc')
								$message = __('City updated successfully.',LMADMINDOMAIN);
							elseif(isset($_REQUEST['msgtype']) && $_REQUEST['msgtype'] =='dele-suc')
								$message = __('City deleted  successfully.',LMADMINDOMAIN);
                               ?>
                         <form name="frm_city" id="frm_city" action="" method="post" >
                         	<input type="hidden" value="<?php echo wp_create_nonce('delete_city');?>" name="_wpnonce"  />                         	
						<?php
                              $directory_multicitiy_table = new wp_list_manage_multicitiy();
                              $directory_multicitiy_table->prepare_items();
                              $directory_multicitiy_table->search_box('search', 'search_id');
                              $directory_multicitiy_table->display();
                              ?>
                              <input type="hidden" name="check_compare">
                         </form>
                         <?php endif;?>
                    </div>
                    <?php
			break;
	endswitch;
}
/*========================== WP List table================================= */
if(!class_exists('Tmpl_WP_List_Table')){
    include_once( WP_PLUGIN_DIR . '/Tevolution/templatic.php');
}
/*
 * Manage countries list table 
 */
class wp_list_manage_countries extends Tmpl_WP_List_Table 
{
	/* fetch all the country data */
	function fetch_countries()
	{
		global $post,$wpdb,$country_table;
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);
		if(isset($_POST['s']) && $_POST['s']!=""){
			$sql = "select * from $country_table where country_name ='".$_POST['s']."'";
		}else{
			if(isset($_GET['orderby']) && $_GET['orderby']=='ISO_Code2')
				$order_by='iso_code_2';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='ISO_Code2')
				$order_by='iso_code_3';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='is_enable')
				$order_by='is_enable';
			else
				$order_by='country_name';

			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			$sql = "select * from $country_table order by $order_by  $order";
		}
		$countryinfo = $wpdb->get_results($sql);
		if($countryinfo)
		{
			 foreach($countryinfo as $resobj) :
			 	$flag='';
			 	if($resobj->country_flg)
					$flag='<img src="'.$resobj->country_flg.'" title="'.$resobj->country_name.'">' ;

				$url= site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations&country_id='.$resobj->country_id;	
			 	$country_data[] =  array('ID'           => $resobj->country_id,
									'title'		=> '<a href="'.$url.'">'.$resobj->country_name.'</a>',
									'ISO_Code2'	=> $resobj->iso_code_2,
									'ISO_Code3'	=> $resobj->iso_code_3,
									'is_enable'	=> $resobj->is_enable,
									'flag'		=> $flag,
									'is_browse'    => '<a href="'.$url.'">Browse States</a>',
								);
			endforeach;
		}
		return $country_data;
	}
	/* DEFINE THE COLUMNS FOR THE TABLE */
	function get_columns()
	{
		$columns = array('cb' => '<input type="checkbox" />',
					'title' =>  __('Country Name',LMADMINDOMAIN),
					'flag' =>  __('Country Flag',LMADMINDOMAIN),
					'ISO_Code2' =>  __('ISO Code2',LMADMINDOMAIN),
					'ISO_Code3' => __('ISO Code3',LMADMINDOMAIN),
					'is_enable' => __('Active',LMADMINDOMAIN),
					'is_browse' => __('Browse',LMADMINDOMAIN),
				);
		return $columns;
	}
	/*Bulk Action process*/
	function process_bulk_action()
	{
		global $wpdb,$country_table,$zones_table,$multicity_table;
		$cids = $_REQUEST['cf'];
		if( 'delete' === $this->current_action() )
		{
			foreach( $cids as $cid ){
				if( wp_verify_nonce($_REQUEST['_wpnonce'],'bulk-tevolution_page_location_settings')){
					$wpdb->delete( "$country_table", array( 'country_id' => $cid ), array( '%d' ) );
					$wpdb->delete( "$zones_table", array( 'country_id' => $cid ), array( '%d' ) );
					$wpdb->delete( "$multicity_table", array( 'country_id' => $cid ), array( '%d' ) );
				}else{
					$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&msgtype=noncenotverify';
					wp_redirect($redirect_to);
					exit;
				}
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&msgtype=dele-suc';
			wp_redirect($redirect_to);
		}
		if( 'enable' === $this->current_action() ){
			foreach( $cids as $cid ){	
				$wpdb->update($country_table , array('is_enable' => 1), array('country_id' => $cid) );
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&msgtype=enable-suc';
			wp_redirect($redirect_to);
		}
		if( 'disable' === $this->current_action() ){
			foreach( $cids as $cid ){
				$wpdb->update($country_table , array('is_enable' => 0), array('country_id' => $cid) );
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&msgtype=disable-suc';
			wp_redirect($redirect_to);
		}
	}
	function prepare_items(){
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);
		$columns = $this->get_columns(); /* CALL FUNCTION TO GET THE COLUMNS */
		$hidden = array();
		$sortable = array();
		$sortable = $this->get_sortable_columns(); /* GET THE SORTABLE COLUMNS */
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action(); /* FUNCTION TO PROCESS THE BULK ACTIONS */
		$data = $this->fetch_countries(); /* RETIRIVE THE TRANSACTION DATA */
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		if(is_array($data))
		$this->found_data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $this->found_data;
		$this->set_pagination_args( array('total_items' => $total_items,'per_page'    => $per_page) );
	}
	/* To avoid the need to create a method for each column there is column_default that will process any column for which no special method is defined */
	function column_default( $item, $column_name )
	{
		switch( $column_name ){
			case 'ID':
			case 'title':
			case 'flag':
			case 'ISO_Code3':
			case 'ISO_Code2':
			case 'message':
			case 'is_browse':
			return $item[ $column_name ];
			case 'is_enable':
			return ($item[ $column_name ]==1)?'Yes': 'No';
			default:
			return print_r( $item, true ) ; /*Show the whole array for troubleshooting purposes*/
		}
	}
	/* DEFINE THE COLUMNS TO BE SORTED */
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'title' => array('title',true),
			'ISO_Code2'=>array('ISO_Code2',true),
			'ISO_Code3' => array('ISO_Code3',true),
			'is_enable' => array('is_enable',true),
			);
		return $sortable_columns;
	}
	function column_title($item)
	{
		$delete_url="<a href='?page=".$_REQUEST['page']."&action=delete&cf[]=".$item['ID']."&location_tabs=countries_manage_locations&_wpnonce=".wp_create_nonce('bulk-tevolution_page_location_settings')."'>Delete</a>";
		$actions = array(
			'edit' => sprintf('<a href="?page=%s&action=%s&cf=%s&%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID'],'location_tabs=countries_manage_locations'),
			'delete' => $delete_url
			);
		return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions , $always_visible = false) );
	}
	function get_bulk_actions(){
		$actions = array('delete' => 'Delete','enable' => 'Enable','disable' => 'Disable');
		return $actions;
	}
	function column_cb($item){ 
		return sprintf('<input type="checkbox" name="cf[]" value="%s" />', $item['ID']);
	}
}
/*
 * Manage state list table 
 */
class wp_list_manage_states extends Tmpl_WP_List_Table 
{	
	/* fetch all the state data */
	function fetch_states()
	{
		global $post,$wpdb,$zones_table,$country_table;		
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);
		if(isset($_POST['s']) && $_POST['s']!=""){
			$sql = "select z.zones_id,z.zone_name,z.zone_code ,c.country_name,c.country_id from $zones_table z ,$country_table c where c.country_id=z.country_id AND zone_name ='".$_POST['s']."'";
		}else{
			if(isset($_GET['orderby']) && $_GET['orderby']=='zone_code')
				$order_by='z.zone_code';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='country_name')
				$order_by='c.country_name';
			else
				$order_by='z.zone_name';
				
			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			if(isset($_GET['country_id']) && $_GET['country_id']!='')
				$sql = "select z.zones_id,z.zone_name,z.zone_code ,c.country_name ,c.country_id from $zones_table z ,$country_table c where c.country_id=z.country_id AND z.country_id=".$_GET['country_id']." ORDER BY $order_by  $order ";
			else
				$sql = "select z.zones_id,z.zone_name,z.zone_code ,c.country_name,c.country_id from $zones_table z ,$country_table c where c.country_id=z.country_id ORDER BY $order_by  $order ";
		}
		$zonesinfo = $wpdb->get_results($sql);		
		if($zonesinfo){ 
			 foreach($zonesinfo as $resobj) :
			 	$url= site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations&country_id='.$resobj->country_id.'&zone_id='.$resobj->zones_id;	
			 	$zone_data[] =  array(
								  'ID'          => $resobj->zones_id,
								  'title'		 => '<a href="'.$url.'">'.$resobj->zone_name.'</a>',
								  'zone_code'	 => $resobj->zone_code,
								  'country_name'=> $resobj->country_name,
								  'is_browse'    => '<a href="'.$url.'">'.__('Browse Cities',LMADMINDOMAIN).'</a>',
								);
			endforeach;
		}
		return $zone_data;
	}
	/* DEFINE THE COLUMNS FOR THE TABLE */
	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' =>  __('State Name',LMADMINDOMAIN),
			'zone_code' =>  __('State Code',LMADMINDOMAIN),
			'country_name' => __('Country Name',LMADMINDOMAIN),
			'is_browse' => __('Browse',LMADMINDOMAIN)
			);
		return $columns;
	}
	function process_bulk_action(){ 
		global $wpdb,$country_table,$zones_table,$multicity_table;
		$cids = $_REQUEST['cf'];
		if( 'delete' === $this->current_action() ){
			foreach( $cids as $cid ){
				if( wp_verify_nonce($_REQUEST['_wpnonce'],'bulk-tevolution_page_location_settings')){
					$wpdb->delete( "$zones_table", array( 'zones_id' => $cid ), array( '%d' ) );
					$wpdb->delete( "$multicity_table", array( 'zones_id' => $cid ), array( '%d' ) );
				}else{
					$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations&msgtype=noncenotverify';
					wp_redirect($redirect_to);
					exit;
				}
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=state_manage_locations&msgtype=dele-suc';
			wp_redirect($redirect_to);
		}
	}
	function prepare_items()
	{
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);
		$columns = $this->get_columns(); /* CALL FUNCTION TO GET THE COLUMNS */
		
		$hidden = array();
		$sortable = array();
		$sortable = $this->get_sortable_columns(); /* GET THE SORTABLE COLUMNS */
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action(); /* FUNCTION TO PROCESS THE BULK ACTIONS */
		$data = $this->fetch_states(); /* RETIRIVE THE TRANSACTION DATA */
		
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		if(is_array($data))
		$this->found_data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $this->found_data; 		
		$this->set_pagination_args( array('total_items' => $total_items,'per_page'=> $per_page) );
	}
	/* To avoid the need to create a method for each column there is column_default that will process any column for which no special method is defined */
	function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'ID':
			case 'title':
			case 'zone_code':
			case 'country_name':
			case 'is_browse':
			return $item[ $column_name ];
			default:
			return print_r( $item, true ) ; /*Show the whole array for troubleshooting purposes*/
		}
	}
	function column_title($item){
		$delete_url="<a href='?page=".$_REQUEST['page']."&action=delete&cf[]=".$item['ID']."&location_tabs=state_manage_locations&_wpnonce=".wp_create_nonce('bulk-tevolution_page_location_settings')."'>Delete</a>";
		$actions = array(
			'edit' => sprintf('<a href="?page=%s&action=%s&cf=%s&%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID'],'location_tabs=state_manage_locations'),
			'delete' => $delete_url
			);		
		return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions , $always_visible = false) );
	}	
	/* DEFINE THE COLUMNS TO BE SORTED */
	function get_sortable_columns(){
		$sortable_columns = array('title' => array('title',true),'zone_code'=>array('zone_code',true),'country_name' => array('country_name',true));
		return $sortable_columns;
	}
	function get_bulk_actions(){
		$actions = array('delete' => 'Delete');
		return $actions;
	}	
	function column_cb($item){
		return sprintf('<input type="checkbox" name="cf[]" value="%s" />', $item['ID']);
	}
}
/*
 * Manage multicity list table 
 */
class wp_list_manage_multicitiy extends Tmpl_WP_List_Table 
{
	function total_fetch_multicity(){
		global $post,$wpdb,$zones_table,$country_table,$multicity_table;
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);		
		if(isset($_POST['s']) && $_POST['s']!=""){
			$sql = "select mc.*,z.zone_name,c.country_name from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id AND mc.cityname ='".$_POST['s']."'";
		}else{
			if(isset($_GET['orderby']) && $_GET['orderby']=='zone_name')
				$order_by='z.zone_name';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='country_name')
				$order_by='c.country_name';	
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='city_code')
				$order_by='mc.city_code';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='city_id')
				$order_by='mc.city_id';
			else
				$order_by='mc.cityname';
				
			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			
			if(isset($_GET['country_id']) && $_GET['country_id']!='' && isset($_GET['zone_id']) && $_GET['zone_id']!='')
				$sql = "select count(*) as count from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id AND mc.country_id=".$_GET['country_id']." AND mc.zones_id=".$_GET['zone_id']."  ORDER BY $order_by $order ";
			else
				$sql = "select count(*) as count from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id ORDER BY $order_by $order ";
		}			
		$multicitiyinfo = $wpdb->get_results($sql);		
		return $multicitiyinfo[0]->count;
	}
	
	/* fetch the all multicity list data*/
	function fetch_multicity()
	{
		global $post,$wpdb,$zones_table,$country_table,$multicity_table;
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);		
		if(isset($_POST['s']) && $_POST['s']!=""){
			$sql = "select mc.*,z.zone_name,c.country_name from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id AND mc.cityname ='".$_POST['s']."'";
		}else{
			
			if(isset($_GET['orderby']) && $_GET['orderby']=='zone_name')
				$order_by='z.zone_name';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='country_name')
				$order_by='c.country_name';	
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='city_code')
				$order_by='mc.city_code';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='city_id')
				$order_by='mc.city_id';
			else
				$order_by='mc.cityname';
				
			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			
			if(isset($_GET['country_id']) && $_GET['country_id']!='' && isset($_GET['zone_id']) && $_GET['zone_id']!='')
				$sql = "select mc.*,z.zone_name,c.country_name from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id AND mc.country_id=".$_GET['country_id']." AND mc.zones_id=".$_GET['zone_id']."  ORDER BY $order_by $order ";
			else
				$sql = "select mc.*,z.zone_name,c.country_name from $multicity_table mc, $zones_table z ,$country_table c where mc.country_id=c.country_id AND mc.zones_id=z.zones_id AND c.country_id=z.country_id ORDER BY $order_by $order ";
		}
		$paged=((isset($_REQUEST['paged']) && $_REQUEST['paged']!='')? ($_REQUEST['paged']-1) : 0)*$per_page;
		$sql=$sql." LIMIT $paged, $per_page";
		$multicitiyinfo = $wpdb->get_results($sql);
		if($multicitiyinfo)
		{ 
			 foreach($multicitiyinfo as $resobj) :
			 	if($resobj->map_type=='ROADMAP')
					$map_type=__('Road Map',LDOMAIN);
				elseif($resobj->map_type=='TERRAIN')
					$map_type=__('Terrain Map',LDOMAIN);
				elseif($resobj->map_type=='SATELLITE')
					$map_type=__('Satellite Map',LDOMAIN);
				elseif($resobj->map_type=='HYBRID')
					$map_type=__('Hybrid Map',LDOMAIN);
				elseif($resobj->map_type=='streetview')
					$map_type=__('Street View Map',LDOMAIN);					
					
				
				if (function_exists('icl_register_string')) {
					/*City name translate using wpml*/
					icl_register_string('location-manager', 'location_city_'.$resobj->city_slug,$resobj->cityname);
					$resobj->cityname = icl_t('location-manager', 'location_city_'.$resobj->city_slug,$resobj->cityname);
					
					/*Zone name translate using wpml */
					icl_register_string('location-manager', 'location_zone_'.$resobj->zones_id,$resobj->zone_name);
					$resobj->zone_name = icl_t('location-manager', 'location_zone_'.$resobj->zones_id,$resobj->zone_name);					
					/* Country name translate using wpml*/
					icl_register_string('location-manager', 'location_country_'.$resobj->country_id,$resobj->country_name);
					$resobj->country_name = icl_t('location-manager', 'location_country_'.$resobj->country_id,$resobj->country_name);

				}
				if($resobj->is_default==1){
					$cityname='<span style="font-weight:bold;" id="city_default_'.$resobj->city_id.'">'.$resobj->cityname.'</span>&nbsp;<span style="color:green;" class="default_city" id="set_default_city_'.$resobj->city_id.'">'.__('Default City',LMADMINDOMAIN).'</span>';
				}else{
					$cityname= '<span id="city_default_'.$resobj->city_id.'">'.$resobj->cityname.'</span>';
				}
				
			 	$multicity_data[] =  array(		
								  'ID'             => $resobj->city_id,								 
								  'title'		    => $cityname,								  
								  'country_name'   => $resobj->zone_name.', '.$resobj->country_name,
								  'map_type'       => $map_type,
								  'city_post_type' => $resobj->post_type,
								  'message'        => substr($resobj->message,0,50),
								  'scaling_factor' => $resobj->scall_factor,
								  'set_default'    => '<a id="default_city_'.$resobj->city_id.'" '. @$onclick.'>'.$resobj->is_default.'</a>',
								);
			endforeach;
		}			
		return $multicity_data;
	}
	
	/* DEFINE THE COLUMNS FOR THE TABLE */
	function get_columns()
	{
		$columns = array(	
			'cb'             => '<input type="checkbox" />',			
			'title'          => __('City',LMADMINDOMAIN),			
			'country_name'   => __('Located In',LMADMINDOMAIN),
			'map_type'       => __('Map Type',LMADMINDOMAIN),
			'city_post_type' => __('Post Type',LMADMINDOMAIN),
			'message'        => __('Message',LMADMINDOMAIN),
			'scaling_factor' => __('Scaling Factor',LMADMINDOMAIN),			
			);
		return $columns;
	}
	
	function process_bulk_action()
	{ 
		global $wpdb,$country_table,$zones_table,$multicity_table;		
		$cids = @$_REQUEST['cf'];
		if( 'delete' === $this->current_action() )
		{
			foreach( $cids as $cid )
			{		
				if( wp_verify_nonce($_REQUEST['_wpnonce'],'bulk-tevolution_page_location_settings')){					
					$wpdb->delete( "$multicity_table", array( 'city_id' => $cid ), array( '%d' ) );
				}else{		
					$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations&msgtype=noncenotverify';
					wp_redirect($redirect_to);		
				}	
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=city_manage_locations&msgtype=dele-suc';
			wp_redirect($redirect_to);
		}
	}
        
	function prepare_items()
	{
		$per_page = $this->get_items_per_page('location_setting_fields_per_page', 25);
		$columns = $this->get_columns(); /* CALL FUNCTION TO GET THE COLUMNS */
		
		$hidden = array();
		$sortable = array();
		$sortable = $this->get_sortable_columns(); /* GET THE SORTABLE COLUMNS */
		
		$this->_column_headers = array($columns, $hidden, $sortable);				
		$this->process_bulk_action(); /* FUNCTION TO PROCESS THE BULK ACTIONS */	
		$data = $this->fetch_multicity(); /* RETIRIVE THE TRANSACTION DATA */
		
		$current_page = $this->get_pagenum(); 
		$total_items = $this->total_fetch_multicity(); 
		if(is_array($data))
		$this->found_data = $data; 
		$this->items = $this->found_data; 
		
		$this->set_pagination_args( array('total_items' => $total_items,'per_page'=> $per_page) );
	}
	function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'ID':			
			case 'title':
			case 'city_code':
			case 'zone_name':
			case 'country_name':
			case 'scaling_factor';
			case 'map_type';
			case 'city_post_type';
			case 'message';			
			return $item[ $column_name ];
			default:
			return $item[ $column_name ]; /*Show the whole array for troubleshooting purposes*/
		}
	}
	function column_title($item)
	{	
		$onclick="onClick=set_default_city(this,'".$item['ID']."')";
		$delete_url="<a href='?page=".$_REQUEST['page']."&action=delete&cf[]=".$item['ID']."&location_tabs=city_manage_locations&_wpnonce=".wp_create_nonce('bulk-tevolution_page_location_settings')."'>".__('Delete',LMADMINDOMAIN)."</a>";
		$actions = array(
				'city_id' => __('City ID:',LMADMINDOMAIN).' '.$item['ID'],
				'edit' => sprintf('<a href="?page=%s&action=%s&cf=%s&%s&paged=%s">'.__('Edit',LMADMINDOMAIN).'</a>',$_REQUEST['page'],'edit',$item['ID'],'location_tabs=city_manage_locations',$_REQUEST['paged']),
				'delete' => $delete_url,
				'set_default' => sprintf('<a href="javascript:void(0);" id="default_city_%s" class="%s" %s>'.__('Set Default',LMADMINDOMAIN).'</a>',$item['ID'],@$item['city_name'],$onclick)
			);
		
		return sprintf('%1$s %2$s', stripslashes($item['title']), $this->row_actions($actions , $always_visible = false) );
	}	
	
	/* DEFINE THE COLUMNS TO BE SORTED */
	function get_sortable_columns()
	{
		$sortable_columns = array(	
			'city_id' => array('city_id',true),
			'title' => array('title',true),
			'city_code' => array('city_code',true),
			'zone_name' => array('zone_name',true),
			'country_name' => array('country_name',true),
			);
		return $sortable_columns;
	}
	
	function get_bulk_actions()
	{
		$actions = array('delete' => 'Delete');
		return $actions;
	}
	function column_cb($item)
	{ 
		return sprintf('<input type="checkbox" name="cf[]" value="%s" />', $item['ID']);
	}
}
/*======================= Add Edit submit form for country, state, and multicity ========================= */
/*
 * Add and edit countries
 *
 */
function add_edit_countries(){
	global $wpdb,$country_table;
	
	/* Check exists country field edit */
	if(isset($_POST['edit_country']) && (isset($_POST['country_id']) && $_POST['country_id']!='')){
		
		/* update country query using update statement */		
		$wpdb->update($country_table , array('country_name' => $_POST['country_name'],'iso_code_2'=>$_POST['iso_code2'],'iso_code_3'=>$_POST['iso_code3'],'country_flg'=>$_POST['country_flag']), array('country_id' => $_POST['country_id']) );
		
		$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=edit-suc';
		wp_redirect($redirect_to);
	}
	/* Check new country field insert */
	if(isset($_POST['save_country'])){
		/* Insert Into country query using prepare statement */		
		$wpdb->query( $wpdb->prepare("INSERT INTO $country_table ( country_name, iso_code_2, iso_code_3, country_flg) VALUES ( %s, %s, %s,%s )", $_POST['country_name'], $_POST['iso_code2'], $_POST['iso_code3'],$_POST['country_flag'] ) );		
		$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=add-suc';
		wp_redirect($redirect_to);
	}
	$submit='save_country';
	?>
     <form action="" method="post">     	
     	<?php
		if((isset($_REQUEST['action']) && $_REQUEST['action']=='edit') && (isset($_REQUEST['cf']) && $_REQUEST['cf']!=''))
		{			
			$countryinfo = $wpdb->get_results($wpdb->prepare("select * from $country_table where country_id =%d",$_REQUEST['cf']));			
			?>
               <input type="hidden" name="country_id" value="<?php echo ($countryinfo[0]->country_id)? $countryinfo[0]->country_id: '';?>" />
               <?php
			$submit="edit_country";
		}		
		?>
          <input type="hidden" name="redirect_to" value="location_settings&location_tabs=countries_manage_locations" />
    		
     	<table class="form-table" width="70%" cellspacing="1" cellpadding="4" border="0">
          	<tbody>
               	<tr>
                    	<th><?php echo __('Country Name',LMADMINDOMAIN);?></th>
                         <td>
                         	<input type="text" name="country_name" value="<?php echo ($countryinfo[0]->country_name)? $countryinfo[0]->country_name: '';?>" />
                         	<p class="description"><?php echo __('Write the country name.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('ISO Code 2',LMADMINDOMAIN);?></th>
                         <td>
                         	<input type="text" name="iso_code2" value="<?php echo ($countryinfo[0]->iso_code_2)? $countryinfo[0]->iso_code_2: '';?>" />
                         	<p class="description"><?php echo __('Write the two letter country code here <br>(e.g. "US" for United States of America. You can find these codes from',LMADMINDOMAIN).' <a href ="http://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank"> '.__('here',LMADMINDOMAIN).' </a> )';?></p>
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('ISO Code 3',LMADMINDOMAIN);?></th>
                         <td>
                         	<input type="text" name="iso_code3" value="<?php echo ($countryinfo[0]->iso_code_3)? $countryinfo[0]->iso_code_3: '';?>" />
                         	<p class="description"><?php echo __('Write the three letter country code here <br>(e.g. "US" for United States of America. You can find these codes from',LMADMINDOMAIN).' <a href ="http://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank"> '.__('here',LMADMINDOMAIN).' </a> )';?></p>
                         </td>
                    </tr>                   
                     <tr>
                    	<th><?php echo __('Country flag',LMADMINDOMAIN);?></th>
                         <td>
                         	<input id="country_flag_icon" type="text" size="60" name="country_flag" value="<?php echo ($countryinfo[0]->country_flg)?$countryinfo[0]->country_flg:'';?>" />	
                             <?php echo __('Or',LMADMINDOMAIN);?>
                             <a class="button upload_button" title="Add country flag icon" id="country_flag_icon" data-editor="country_flag_icon" href="#">
                             <span class="wp-media-buttons-icon"></span><?php echo __('Browse',LMADMINDOMAIN);?>	</a>				
                              
                              <p class="description"><?php echo __('Upload flag image for above mentioned country. It will appear on your site along with the country name.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr id="save_coupon">
                         <td colspan="2">
                         	<input id="save" class="button button-primary button-hero" type="submit" value="<?php echo __('Save all changes',LMADMINDOMAIN); ?>"  name="<?php echo $submit;?>">
                         </td>
                    </tr>
               </tbody>
          </table>
     </form>
     <?php
}
/*
 * Add and edit zone
 *
 */
function add_edit_zone(){
	global $wpdb,$country_table,$zones_table;
	
	/* Check exists country field edit */
	if(isset($_POST['edit_zone']) && (isset($_POST['country_id']) && $_POST['country_id']!='')){
		/* update country query using update statement */		
		$wpdb->update($zones_table , array('country_id' => $_POST['country_id'],'zone_code'=>$_POST['zone_code'],'zone_name'=>$_POST['zone_name']), array('zones_id' => $_POST['zone_id']) );
		
		$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=edit-suc';
		wp_redirect($redirect_to);
	}
	/* Check new country field insert */
	if(isset($_POST['save_zone'])){		
		/* Insert Into zone query using prepare statement */		
		$wpdb->query( $wpdb->prepare("INSERT INTO $zones_table ( country_id, zone_code, zone_name) VALUES ( %d, %s, %s)", $_POST['country_id'], $_POST['zone_code'], $_POST['zone_name']) );	
		
		$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=add-suc';
		wp_redirect($redirect_to);
	}
	
	$submit='save_zone';
	?>
     <form action="" method="post">     	
     	<?php
		if((isset($_REQUEST['action']) && $_REQUEST['action']=='edit') && (isset($_REQUEST['cf']) && $_REQUEST['cf']!=''))
		{
			$zoneinfo = $wpdb->get_results($wpdb->prepare("select * from $zones_table where zones_id =%d",$_REQUEST['cf'] ));
			?>
               <input type="hidden" name="zone_id" value="<?php echo ($zoneinfo[0]->zones_id)? $zoneinfo[0]->zones_id: '';?>" />
               <?php
			$submit="edit_zone";
		}		
		?>
          <input type="hidden" name="redirect_to" value="location_settings&location_tabs=state_manage_locations" />
    		
     	<table class="form-table" width="70%" cellspacing="1" cellpadding="4" border="0">
          	<tbody>
               	<tr>
                    	<th><?php tmpl_backend_country_label();?></th>
                         <td>
                         	<?php $countryinfo = $wpdb->get_results("SELECT * FROM $country_table order by country_name ASC");?>
                              	<select name="country_id" >
								<option value=""><?php tmpl_backend_select_country_option();?></option>
                              <?php foreach($countryinfo as $country): $selected=($country->country_id==$zoneinfo[0]->country_id)? 'selected':'';?>
							<option value="<?php echo $country->country_id?>" <?php echo $selected;?>><?php echo $country->country_name;?></option>
						<?php endforeach; ?>
                              </select>
                         
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('State Name',LMADMINDOMAIN);?></th>
                         <td><input type="text" name="zone_name" value="<?php echo ($zoneinfo[0]->zone_name)? $zoneinfo[0]->zone_name: '';?>" />
                          <p class="description"><?php echo __('Write the state name that falls in the above selected country',LMADMINDOMAIN);?></p>
                          </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('State Code',LMADMINDOMAIN);?></th>
                         <td><input type="text" name="zone_code" value="<?php echo ($zoneinfo[0]->zone_code)? $zoneinfo[0]->zone_code: '';?>" />
                        <p class="description"><?php echo __('Write the zone code of the above mentioned state',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    
                    <tr id="save_coupon">
                         <td colspan="2">
                         	<input id="save" class="button button-primary button-hero" type="submit" value="<?php echo __('Save all changes',LMADMINDOMAIN); ?>"  name="<?php echo $submit;?>">
                         </td>
                    </tr>
               </tbody>
          </table>
     </form>
     <?php
}
/*
 * Add or Edit multicity
 *
 */
function add_edit_multicity(){
	global $wpdb,$country_table,$zones_table,$multicity_table;
	
	/* Check exists city field edit */	
	if(isset($_POST['edit_city']) && (isset($_POST['country_id']) && $_POST['country_id']!='')){
		$city_post_type=implode(',',$_POST['city_post_type']);
		$categories=implode(',',$_POST['category']);	
		
                $city_id = $_POST['city_id'];
                $city_slug_change = 0;
                $get_db_city = $wpdb->get_results("SELECT cityname,city_slug FROM $multicity_table WHERE city_id =$city_id");		
		
                /* city slug only change if city name change*/
                if($get_db_city[0]->cityname != $_POST['city_name']){ 
                    $city_slug_change = 1;
                    $city_slug = sanitize_title($_POST['city_name']).'_'.rand(10,999);
                }else{
                    $city_slug = $get_db_city[0]->city_slug;
                }
                
                $wpdb->update($multicity_table , array('country_id' => $_POST['country_id'],'zones_id'=>$_POST['zones_id'],'cityname'=> stripslashes($_POST['city_name']),'city_slug'=>$city_slug,'lat'=>$_POST['geo_latitude'],'lng'=>$_POST['geo_longitude'],'scall_factor'=>$_POST['scaling_factor'],'is_zoom_home'=>$_POST['set_zooming_opt'],'map_type'=>$_POST['map_type'],'post_type'=>$city_post_type,'categories'=>$categories,'message'=>stripslashes($_POST['city_message']),'color'=>$_POST['background_city_colour'],'images'=>$_POST['city_image'],'header_color'=>$_POST['header_background_city_colour'],'header_image'=>$_POST['header_city_image'],'cat_scall_factor'=>$_POST['cat_scaling_factor'],'is_zoom_cat'=>$_POST['cat_set_zooming_opt']), array('city_id' => $_POST['city_id']) );
		/*City String Name change using wpml  */
		if (function_exists('icl_register_string') && $city_slug_change == 1) {			
			icl_register_string('location-manager', 'location_city_'.$city_slug,$_POST['city_name']);		
		}
		if(isset($_GET['paged']) && $_GET['paged']!=""){
			$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=edit-suc&paged='.$_GET['paged'];
		}
		else{
			$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=edit-suc';
		}
		
		wp_redirect($redirect_to);
	}
	/* Check new city field insert */
	if(isset($_POST['save_city'])){
		
			$city_post_type=implode(',',$_POST['city_post_type']);
			$categories=implode(',',$_POST['category']);	
			$city_slug=sanitize_title($_POST['city_name']);
			
			$already_slug = $wpdb->query("SELECT city_slug FROM $multicity_table WHERE city_slug = '$city_slug'");

           if($already_slug > 0)
				$city_slug = sanitize_title($_POST['city_name']).'_'.rand(10,999);
		
		/* Insert Into zone query using prepare statement */			
		$wpdb->query( $wpdb->prepare("INSERT INTO $multicity_table ( country_id,zones_id,cityname,city_slug,lat,lng,scall_factor,is_zoom_home,map_type,post_type,categories,message,color,images,header_color,header_image,cat_scall_factor,is_zoom_cat) VALUES ( %d, %d, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s,%s,%s,%s,%d,%d)", $_POST['country_id'], $_POST['zones_id'],  stripslashes($_POST['city_name']),$city_slug,$_POST['geo_latitude'],$_POST['geo_longitude'],$_POST['scaling_factor'], $_POST['set_zooming_opt'], $_POST['map_type'], $city_post_type, $categories, stripslashes($_POST['city_message']), $_POST['background_city_colour'], $_POST['city_image'],$_POST['header_background_city_colour'],$_POST['header_city_image'],$_POST['cat_scaling_factor'],$_POST['cat_set_zooming_opt']   ) );	
		
		/*City String Name change using wpml  */
		if (function_exists('icl_register_string')) {			
			icl_register_string('location-manager', 'location_city_'.$city_slug,$_POST['city_name']);		
		}
		
		$redirect_to=site_url().'/wp-admin/admin.php?page='.$_POST['redirect_to'].'&msgtype=add-suc';
		wp_redirect($redirect_to);
	}
	$submit='save_city';
	?>
     <form action="" method="post" onsubmit="return manage_city_validation();" name="price_frm">     	
     	<?php
		if((isset($_REQUEST['action']) && $_REQUEST['action']=='edit') && (isset($_REQUEST['cf']) && $_REQUEST['cf']!=''))
		{
			
			$cityinfo = $wpdb->get_results($wpdb->prepare("select * from $multicity_table where city_id =%d",$_REQUEST['cf'] ));
			?>
               <input type="hidden" name="city_id" value="<?php echo ($cityinfo[0]->city_id)? $cityinfo[0]->city_id: '';?>" />
               <?php
			$submit="edit_city";
			$zonesinfo = $wpdb->get_results($wpdb->prepare("select * from $zones_table where country_id =%d",$cityinfo[0]->country_id ));
		}			
		?>
          <input type="hidden" name="redirect_to" value="location_settings&location_tabs=city_manage_locations" />
    		
     	<table class="form-table" width="70%" cellspacing="1" cellpadding="4" border="0">
          	<tbody>
               	<tr id="admin_country_id">
                    	<th><?php tmpl_backend_country_label();?><span class="required">*</span></th>
                         <td>
                              <?php $countryinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $country_table where is_enable=%d order by country_name ASC",1 ));?>
                              	<select name="country_id" id="country_id" onchange="fill_zones_cmb(this,'');">
								<option value=""><?php tmpl_backend_select_country_option();?></option>
                              <?php foreach($countryinfo as $country): $selected=($country->country_id==$cityinfo[0]->country_id)? 'selected':'';?>
							<option value="<?php echo $country->country_id?>" <?php echo $selected;?>><?php echo $country->country_name;?></option>
						<?php endforeach; ?>
                              </select>                         
                         </td>
                    </tr>
                    <tr id="admin_zones_id">
                    	<th><?php echo tmpl_backend_state_label();?><span class="required">*</span></th>
                         <td>                         	
                             	<select name="zones_id"  id="zones_id">
								<option value=""><?php tmpl_backend_select_state_option();?></option>
                              <?php if($zonesinfo):
								foreach($zonesinfo as $zone): $selected=($zone->zones_id==$cityinfo[0]->zones_id)? 'selected':'';?>
									<option value="<?php echo $zone->zones_id?>" <?php echo $selected;?>><?php echo htmlspecialchars($zone->zone_name);?></option>
						<?php 	endforeach;
							 endif;?>
                              </select>
                              <span id="process_state" style="display:none;"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                         
                         </td>
                    </tr>
                     <tr>
                    	<th><?php echo __('Map type',LMADMINDOMAIN);?></th>
                         <td>
                              <input type="radio" id="roadmap" name="map_type" value="ROADMAP" <?php if( @$cityinfo[0]->map_type == 'ROADMAP' || @$cityinfo[0]->map_type == ''){?>checked="checked"<?php }?> /> <label for="roadmap"> <?php echo __('  Road Map',LMADMINDOMAIN);?></label>
                              	<input type="radio" id="terrain" name="map_type" <?php if( @$cityinfo[0]->map_type == 'TERRAIN'){?> checked="checked"<?php }?> value="TERRAIN" /> <label for="terrain"><?php echo __('Terrain Map',LMADMINDOMAIN);?></label> 
           &nbsp;
                              	<input type="radio" id="satellite" name="map_type" <?php if( @$cityinfo[0]->map_type == 'SATELLITE'){?> checked="checked"<?php }?> value="SATELLITE" /> <label for="satellite"><?php echo __('Satellite Map',LMADMINDOMAIN);?></label> 
                              &nbsp;
                              	<input type="radio" id="hybrid" name="map_type" <?php if( @$cityinfo[0]->map_type == 'HYBRID'){?> checked="checked"<?php }?> value="HYBRID" /> <label for="hybrid"><?php echo __('Hybrid  Map',LMADMINDOMAIN);?></label>   
                              
                         	<p class="description"><?php echo __('Select any of the above type for your map',LMADMINDOMAIN); ?></p>
                         	
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('Map scaling factor',LMADMINDOMAIN);?></th>
                         <td>
							<select name="scaling_factor">
								<?php for($sf=1; $sf < 20 ; $sf++){ ?>
									<?php if($cityinfo[0]->scall_factor !=''){ $sf1=$cityinfo[0]->scall_factor; }else{ if($sf == 13) { $sf1 = '13'; }else{ $sf1=''; }  }
									if($sf == $sf1){ $sel ="selected=selected"; }else{ $sel =''; }
									?>
									<option value="<?php echo $sf; ?>" <?php echo $sel; ?>><?php echo $sf; ?></option>
								<?php } ?>							
							</select>
                         	<p class="description"><?php echo __('Define the zoom level of the map here.<br/> Its Min. value is 1 and Max. Value is 19. The recommended level is 13',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr id="admin_city_name">
                    	<th><?php echo __('City Name',LMADMINDOMAIN);?><span class="required">*</span></th>
                         <td>
                         	<div style="width:25%; float:left;">
                         	<input type="text"  id="address" class="pt_input_text" name="city_name" value="<?php echo ( @$cityinfo[0]->cityname)? stripslashes($cityinfo[0]->cityname): '';?>" />
                         	<input type="hidden" name="geo_address" id="geo_address" value="<?php echo stripslashes($cityinfo[0]->cityname);?>" />                              
                              </div>
                             
                         </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                              <div class="option option-select"  >     		
                              <?php 
						$zooming_factor=( @$cityinfo[0]->scall_factor)? $cityinfo[0]->scall_factor: '13';
						$map_lat=( @$cityinfo[0]->lat)? $cityinfo[0]->lat: '40.714623';
						$map_lng=( @$cityinfo[0]->lng)? $cityinfo[0]->lng: '-74.006605';
						$maptype = ( @$cityinfo[0]->map_type)? $cityinfo[0]->map_type: 'ROADMAP';
						include_once(TEVOLUTION_LOCATION_DIR . "functions/map/locations_map.php"); ?>                              
                              </div>
                         </td>
                    </tr>
                     <tr>
                    	<th><?php echo __('City latitude',LMADMINDOMAIN);?></th>
                        <td><input type="text"  onblur="changeMap();" class="textfield" id="geo_latitude"  name="geo_latitude" value="<?php echo ( @$cityinfo[0]->lat)? $cityinfo[0]->lat: '';?>" /></td>
                    </tr>
                    <tr>
                    	<th><?php echo __('City longitude',LMADMINDOMAIN);?></th>
                        <td><input type="text" onblur="changeMap();" class="textfield" id="geo_longitude" name="geo_longitude" value="<?php echo ( @$cityinfo[0]->lng)? $cityinfo[0]->lng: '';?>" /></td>
                    </tr>                     
                    <tr>
                    	<th><?php echo __('Home Page Map display',LMADMINDOMAIN);?></th>
                         <td>
                              
                              <input type="radio" id="set_zooming_opt" name="set_zooming_opt" value="0" <?php if( @$cityinfo[0]->is_zoom_home == '0' || @$cityinfo[0]->is_zoom_home == ''){?>checked="checked"<?php }?> /> <label for="set_zooming_opt"> <?php echo __('According to Map Scaling factor',LMADMINDOMAIN);?></label>
                              &nbsp;
                              	<input type="radio" id="set_zooming_opt1" name="set_zooming_opt" <?php if( @$cityinfo[0]->is_zoom_home == '1'){?> checked="checked"<?php }?> value="1" /> <label for="set_zooming_opt1"><?php echo __('Fit all available listings',LMADMINDOMAIN);?></label>                              	
                         	<p class="description"><?php echo __('Select whether you want to display map as per Map scaling factor you have set above or adjust the scaling factor<br/> automatically so that all available listings appear on the map.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('Category/Archive page Map display',LMADMINDOMAIN);?></th>
                         <td>
                              
                              <input type="radio" id="cat_set_zooming_opt" name="cat_set_zooming_opt" value="0" <?php if( @$cityinfo[0]->is_zoom_cat == '0' || @$cityinfo[0]->is_zoom_cat == ''){?>checked="checked"<?php }?> /> <label for="cat_set_zooming_opt"> <?php echo __('According to Map Scaling factor',LMADMINDOMAIN);?></label>
                              &nbsp;
                              	<input type="radio" id="cate_set_zooming_opt1" name="cat_set_zooming_opt" <?php if( @$cityinfo[0]->is_zoom_cat == '1'){?> checked="checked"<?php }?> value="1" /> <label for="cate_set_zooming_opt1"><?php echo __('Fit all available listings',LMADMINDOMAIN);?></label>                              	
                         	<p class="description"><?php echo __('Select whether you want to display map as per Map scaling factor you have set above or adjust the scaling factor<br/> automatically so that all available listings appear on the map.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr id="category_map_scaling_factor" <?php if( @$cityinfo[0]->is_zoom_cat == '1'){?> style="display:none" <?php }?>>
                    	<th><?php echo __('Category/Archive page Map scaling factor',LMADMINDOMAIN);?></th>
                         <td>
							<select name="cat_scaling_factor">
								<?php for($sf=1; $sf < 20 ; $sf++){ ?>
									<?php if($cityinfo[0]->cat_scall_factor !=''){ $sf1=$cityinfo[0]->cat_scall_factor; }else{ if($sf == 13) { $sf1 = '13'; }else{ $sf1=''; }  }
									if($sf == $sf1){ $sel ="selected=selected"; }else{ $sel =''; }
									?>
									<option value="<?php echo $sf; ?>" <?php echo $sel; ?>><?php echo $sf; ?></option>
								<?php } ?>							
							</select>
                         	<p class="description"><?php echo __('Define the zoom level of the map here.<br/> Its Min. value is 1 and Max. Value is 19. The recommended level is 13',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>                    
                    <tr id="admin_post_type">
                    	<th><?php echo __('Post Type',LMADMINDOMAIN);?><span class="required">*</span></th>
                           <td>
                         	<?php						
						$location_post_type=implode(',',get_option('location_post_type'));
						$post_types = apply_filters('tmpl_allow_pkg_posttype',get_option("templatic_custom_post"));
						$posttype_post['post'] = array('label'=>'post');
						$post_types = array_merge($post_types,$posttype_post);
						$city_post_type=( @$cityinfo[0]->post_type!='')? explode(',',$cityinfo[0]->post_type):'';
						foreach($post_types as $key=>$post_type):
							$checked=(((!empty($city_post_type)) && in_array($key,$city_post_type) ) || (isset($_REQUEST['action']) && $_REQUEST['action']=='addnew'))?'checked':'';							
							$taxonomies = get_object_taxonomies( (object) array( 'post_type' => $key,'public'   => true, '_builtin' => true ));
							if($key !='' && strpos($location_post_type,$key) !== false){
							?>
							<div class="input_wrap">
                                   	<input type="checkbox" class="checkbox_list" id="<?php echo $key;?>" name="city_post_type[]" value="<?php echo $key;?>" <?php echo $checked?> onclick="get_categories_checklist('<?php echo $key; ?>','<?php echo ($cityinfo[0]->city_id)? $cityinfo[0]->city_id: '';?>');" />&nbsp;&nbsp;<label for="<?php echo $key;?>"><?php echo $post_type['label'];?></label>
                                   </div>
                                   
						<?php } endforeach;?>
                              <p class="description"><?php echo __('Selected post type will be displayed on home page map. <br> <b>Note:</b> Make sure about its category selection in the below given &quot;Categories&quot; option',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>               
				</tr>
                    <tr id="admin_post_type">
                    	<th><?php echo __('Categories',LMADMINDOMAIN);?><span class="required">*</span></th>
                         <td>
						 <div class="element wp-tab-panel" id="field_category" style="height:120px;overflow-y: scroll; margin-bottom:5px;">
							 <?php 
							 	$post_types = get_option("templatic_custom_post");
								$categories=( @$cityinfo[0]->categories!='')? $cityinfo[0]->categories:'';								
								$c=0;
								if(!empty($city_post_type)){
								foreach($post_types as $key=>$post_type):
									if(in_array($key,$city_post_type)){
										if($c == 0){
											echo get_location_category_checklist($key,$categories,'','select_all');
										}else{ 
											echo get_location_category_checklist($key,$categories,'','');
										}
										$c++;
									}
								endforeach;
								}else{
									foreach($post_types as $key=>$post_type):									
										if($c == 0){
											echo get_location_category_checklist($key,$categories,'','select_all');
										}else{ 
											echo get_location_category_checklist($key,$categories,'','');
										}
										$c++;									
									endforeach;
								}
							?>  
						  </div>
						  <span id='process' style='display:none;'><i class="fa fa-circle-o-notch fa-spin"></i></span>
						  <p class="description"><?php echo __('Select the categories for which this field should appear in on the place or event submission form.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
					<script type="text/javascript" async>
					/* select all or not  */
					jQuery(document).ready(function(){
						var a = jQuery("input[name='category[]']").not("#selectall"); /* exclude select all button from loop */
						a.change(function(){
						console.log(a.length +'==='+ a.filter(":checked").length);
							if(a.length == a.filter(":checked").length){
								jQuery("#selectall").prop('checked', true);
							}else{
								jQuery("#selectall").prop('checked', false);
							}
						});
					});
					</script>
                    <tr>
                    	<th><?php echo __('City Message',LMADMINDOMAIN);?></th>
                         <td>
                         	<textarea name="city_message" cols="60" rows="5"><?php echo ( @$cityinfo[0]->message)?$cityinfo[0]->message:'';?></textarea>
                              <p class="description"><?php echo __('Write a description of the city that you would like to display on your site.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <tr>
                    	<th><?php echo __('Background Color',LMADMINDOMAIN);?></th>
                         <td>   
                         	<script type="text/javascript">
							/*BEING City background color picker */
							jQuery(document).ready(function($){
								jQuery('#background_city_colour').farbtastic('#city_color');
							});
							</script>
                              <input type="text" name="background_city_colour" onclick="show_colorpicker(this.id);" id="city_color" value="<?php echo ( @$cityinfo[0]->color)?$cityinfo[0]->color:'#';?>" >
                              <img style="position:relative;vertical-align:middle;" src="<?php echo TEVOLUTION_LOCATION_URL; ?>images/Color_block.png" />
                              <div id="background_city_colour"  name="city_color" style="display:none" ></div>
                              <div class="clearfix"></div>
                              <p class="description"><?php echo __('choose a background color for this city. <br> <b>Tip:</b>You can define a color that represents the particular city to add an advantage to recognize the cities on your site.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <!-- Set city back ground image html -->
                    <tr>
                    	<th><?php echo __('Background Image',LMADMINDOMAIN);?></th>
                         <td>                          	
							  <input id="city_upload_icon" type="text" size="60"  name="city_image" value="<?php echo ( @$cityinfo[0]->images)?$cityinfo[0]->images:'';?>" />	<a data-id="city_upload_icon" id="Background Image" type="submit" class="upload_file_button button"><?php  echo __('Browse',LMADMINDOMAIN);?></a>   
                              <p class="description"><?php echo __('Upload image to display it as a background image for this city. Ideal width size for it is <b>1900 pixels</b>.',LMADMINDOMAIN);?></p>                              
                         </td>
                    </tr>
                    
                    <tr>
                    	<th><?php echo __('Header Background Color',LMADMINDOMAIN);?></th>
                         <td>   
						<script  type="text/javascript" async >
							/*BEING City background color picker */
							jQuery(document).ready(function($){
								jQuery('#header_background_city_colour').farbtastic('#header_city_color');
							});
						</script>
                              <input type="text" name="header_background_city_colour" onclick="show_colorpicker(this.id);" id="header_city_color" value="<?php echo ( @$cityinfo[0]->header_color)?$cityinfo[0]->header_color:'#';?>" >
                              <img style="position:relative;vertical-align:middle;" src="<?php echo TEVOLUTION_LOCATION_URL; ?>images/Color_block.png" />
                              <div id="header_background_city_colour"  name="header_city_color" style="display:none" ></div>
                              <div class="clearfix"></div>
                              <p class="description"><?php echo __('choose a header background color for this city. <br> <b>Tip:</b>You can define a color that represents the particular city to add an advantage to recognize the cities on your site.',LMADMINDOMAIN);?></p>
                         </td>
                    </tr>
                    <!-- Set city header image html -->
                    <tr>
                    	<th><?php echo __('Header Upload Image',LMADMINDOMAIN);?></th>
                         <td>                          	
                             <input id="header_city_upload_icon" type="text" size="60" name="header_city_image" value="<?php echo ( @$cityinfo[0]->header_image)?$cityinfo[0]->header_image:'';?>" />	<a data-id="header_city_upload_icon" id="Header Image" type="submit" class="upload_file_button button"><?php  echo __('Browse',LMADMINDOMAIN);?></a>
                       
                              <p class="description"><?php echo __('Upload image to display it as a header background image for this city.Ideal width size for it is 1240 pixels and keep the height upto 240px.',LMADMINDOMAIN);?></p>                              
                         </td>
                    </tr>
                    <tr id="save_coupon">
                         <td colspan="2">
                         	<input id="save" class="button button-primary button-hero" type="submit" value="<?php echo __('Save all changes',LMADMINDOMAIN); ?>"  name="<?php echo $submit;?>">
                         </td>
                    </tr>
               </tbody>
          </table>
     </form>
     <?php
}
/*=============================================================================================================================================== */
/*
 * Function Name: location_set_default_city
 * Return: fill the default city
 */
add_action('wp_ajax_nopriv_default_city','location_set_default_city');
add_action('wp_ajax_default_city','location_set_default_city');
function location_set_default_city()
{
	global $wpdb,$country_table,$zones_table,$multicity_table;	
	$default_city_id = $wpdb->get_results($wpdb->prepare("SELECT city_id FROM $multicity_table where is_default=%d",1));		
	$last_default_city=$default_city_id[0]->city_id;	
	$wpdb->update($multicity_table , array('is_default' => 0), array('is_default' => 1) );
	$wpdb->update($multicity_table , array('is_default' => 1), array('city_id' => $_POST['city_id']) );	
	echo $last_default_city;
	exit;
}
/*
 * Wp_ajax action call for fill the state name according country when new city insert
 * Function Name: location_fill_states_cmb
 * Return: fill the state drop down box
 */
add_action('wp_ajax_nopriv_fill_states_cmb','location_fill_states_cmb');
add_action('wp_ajax_fill_states_cmb','location_fill_states_cmb');
function location_fill_states_cmb()
{
	global $wpdb,$country_table,$zones_table,$multicity_table;
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	if(isset($_REQUEST['front']) && $_REQUEST['front']==1 && isset($_REQUEST['header']) && $_REQUEST['header'] == 1){
		
		$zones_id=$wpdb->get_results("SELECT distinct meta_value as zones_id from {$wpdb->prefix}postmeta pm,{$wpdb->prefix}posts p where pm.meta_key ='zones_id' and pm.post_id = p.ID and p.post_status ='publish' and pm.meta_value!= '' ");
		
		$post_zones_id='';
		if($zones_id[0]->zones_id){
				foreach($zones_id as $ids){
					$zonesids.=$ids->zones_id.",";
				}
				$post_zones_id=str_replace(",","','",substr($zonesids,0,-1));
		}
		$zonesinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id, z.* FROM $zones_table z,$country_table c,$multicity_table mc where mc.zones_id=z.zones_id AND z.country_id=c.country_id AND c.is_enable=%d AND z.zones_id in ('".rtrim($post_zones_id,',')."') AND z.country_id =%d order by zone_name ASC",1,$_REQUEST['country_id']));		
	}elseif(isset($_REQUEST['front']) && $_REQUEST['front']==1 && !isset($_REQUEST['header'])){
		$zonesinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id, z.* FROM $zones_table z,$multicity_table mc where z.zones_id=mc.zones_id AND z.country_id =%d order by zone_name ASC",$_REQUEST['country_id']));	
	}
	else{
		$zonesinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct z.zones_id, z.* FROM $zones_table z,$country_table c where z.country_id=c.country_id AND c.is_enable=%d AND z.country_id =%d order by zone_name ASC",1,$_REQUEST['country_id']));	
	}	
	$zones_ontion.='<option value="">'.__('Select State',LDOMAIN).'</option>';
	
	if($zonesinfo):		
		foreach($zonesinfo as $zone):
			$zone_name=$zone->zone_name;
			if (function_exists('icl_register_string')) {
				icl_register_string('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
				$zone_name = icl_t('location-manager', 'location_zone_'.$zone->zones_id,$zone_name);
			}
			$selected=(count($zonesinfo)==1)? 'selected' :'';
			$zones_ontion.='<option value="'.$zone->zones_id.'"  '.$selected.'>'.($zone_name).'</option>';
		endforeach;
	else:
		$zones_ontion='<option value="">'.__('States not available',LDOMAIN).'</option>';
	endif;
	
	if(isset($_REQUEST['header']) && $_REQUEST['header']==1){
		$zones_ontion.='++<option value="">'.__('Select City',LDOMAIN).'</option>';	
	}
	echo $zones_ontion;
	exit;
}
/*
 * Wp_ajax action call for fill the state name according country when new city insert
 * Function Name: location_fill_cities_cmb
 * Return: Fill the city drop downbox
 */
add_action('wp_ajax_nopriv_fill_city_cmb','location_fill_cities_cmb');
add_action('wp_ajax_fill_city_cmb','location_fill_cities_cmb');
function location_fill_cities_cmb()
{
	global $wpdb,$country_table,$zones_table,$multicity_table;	
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	if(isset($_REQUEST['front']) && $_REQUEST['front']==1){
		
		$city_ids=$wpdb->get_results("SELECT distinct meta_value as city_ids from {$wpdb->prefix}postmeta pm,{$wpdb->prefix}posts p where pm.meta_key ='post_city_id' and pm.post_id = p.ID and p.post_status ='publish'");

		$post_city_id='';						 
		if($city_ids[0]->city_ids){
				foreach($city_ids as $ids){
					$cityids.=$ids->city_ids.",";
				}
				$post_city_id=str_replace(",","','",substr($cityids,0,-1));
		}

		$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT distinct c.city_id,c.* FROM $multicity_table c where c.city_id in ('".rtrim($post_city_id,',')."') AND zones_id =%d order by cityname  ASC",$_REQUEST['state_id']));
				
	}else{
		$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where zones_id =%d order by cityname  ASC",$_REQUEST['state_id']));
	}	
	
	$city_ontion.='<option value="">'.__('Select City',LDOMAIN).'</option>';
	if($cityinfo):		
		foreach($cityinfo as $city):
		   $cityname=$city->cityname;		   
		   if (function_exists('icl_register_string')) {			   		
				icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
				$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
		   }
		   $selected=(count($cityinfo)==1)? 'selected' :'';
		   $city_ontion.='<option value="'.$city->city_id.'" '.$selected.'>'.$cityname.'</option>';
		endforeach;
	else:
		$city_ontion='<option value="">'.__('City not available',LDOMAIN).'</option>';
	endif;
	echo $city_ontion;
	exit;
}
/*
 * Function Name: location_fill_multicity_cmb
 * Return: Fill the multi city drop down box
 */
add_action('wp_ajax_nopriv_fill_multicity_cmb','location_fill_multicity_cmb');
add_action('wp_ajax_fill_multicity_cmb','location_fill_multicity_cmb');
function location_fill_multicity_cmb(){
	if(isset($_REQUEST['lang']) && $_REQUEST['lang']!=""){
		$_COOKIE['_icl_current_language']=$_REQUEST['lang'];
	}
	global $wpdb,$country_table,$zones_table,$multicity_table;			
	$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT * FROM $multicity_table where country_id =%d order by cityname  ASC",$_REQUEST['country_id']));	
	if($cityinfo):		
		foreach($cityinfo as $city):		 
			$cityname=$city->cityname;		   
			if (function_exists('icl_register_string')) {			   		
				icl_register_string('location-manager', 'location_city_'.$city->city_slug,$cityname);
				$cityname = icl_t('location-manager', 'location_city_'.$city->city_slug,$cityname);
			}
			$city_ontion.='<option value="'.$city->city_id.'" >'.$city->cityname.'</option>';
		endforeach;
	else:
		$city_ontion='<option value="">'.__('City not available',LDOMAIN).'</option>';
	endif;
	echo $city_ontion;
	exit;
}
/* get category checklist tree BOF*/
function get_location_category_checklist($post_type,$pid,$mod='',$select_all='')
{
	global $wpdb;
	if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
		global $sitepress;
		remove_filter('terms_clauses', array($sitepress, 'terms_clauses'));    
	}
	$post_taxonomy = get_object_taxonomies( (object) array( 'post_type' => $post_type,'public'   => true, '_builtin' => true ));
	$pid = explode(',',$pid);
	
	$taxonomy_details = get_option('templatic_custom_taxonomy');
		$taxonomy = $post_taxonomy[0];
		$post_taxonomy = $post_taxonomy[0];
		$table_prefix = $wpdb->prefix;
		$wpcat_id = NULL;
		/*-Fetch main category-*/
		if($taxonomy == "")
		{
			$sql= $wpdb->prepare("SELECT * FROM {$table_prefix}terms, {$table_prefix}term_taxonomy WHERE {$table_prefix}terms.term_id = {$table_prefix}term_taxonomy.term_id AND ({$table_prefix}term_taxonomy.taxonomy =%s'".$post_taxonomy."') and  {$table_prefix}term_taxonomy.parent=%d  ORDER BY {$table_prefix}terms.name",$post_taxonomy,0);
			$wpcategories = (array)$wpdb->get_results($sql);
		}else{
			$sql= $wpdb->prepare("SELECT * FROM {$table_prefix}terms, {$table_prefix}term_taxonomy WHERE {$table_prefix}terms.term_id = {$table_prefix}term_taxonomy.term_id AND {$table_prefix}term_taxonomy.taxonomy =%s and  {$table_prefix}term_taxonomy.parent=%d  ORDER BY {$table_prefix}terms.name",$post_taxonomy,0);
			
			$wpcategories = (array)$wpdb->get_results($sql);
		}
		$wpcategories = array_values($wpcategories);
		$wpcat2 = NULL;
		if($wpcategories)
		{
			$counter = 0;
		echo "<ul>";
		if($select_all == 'select_all')
		{
		?>
		<li><label for="selectall"><input type="checkbox" name="category[]" id="selectall" value="all" class="checkbox" <?php if( @$_REQUEST['mod']=='custom_fields'){ ?> onclick="displaychk_frm();"<?php  } elseif( @$_REQUEST['mod']=='price'){ ?> onclick="displaychk_price();"<?php  }else{ ?>onclick="displaychk_frm();"<?php } ?> <?php if( @$pid[0]){ if(in_array('all',$pid)){ echo "checked=checked"; } }else{  }?>/>&nbsp;<?php echo __("Select All",LMADMINDOMAIN); ?></label></li>
		<?php
		}
		foreach ($wpcategories as $wpcat)
		{ 
			if($counter ==0){ 
				$tname = $taxonomy_details[$post_taxonomy]['label']; 
				if($post_taxonomy =='category' || $post_taxonomy ==''): ?>
				<li><label style="font-weight:bold;"><?php _e('Categories',LMADMINDOMAIN); ?></label></li>
				<?php else:?>
						<li><label style="font-weight:bold;"><?php echo $tname; ?></label></li>
			<?php 	
				endif;
			}
		
		$counter++;
		$termid = $wpcat->term_id;;
		$name = ucfirst($wpcat->name); 
		$termprice = $wpcat->term_price;
		$tparent =  $wpcat->parent;	
		?>
		<li><label for="<?php echo $termid; ?>"><input type="checkbox" name="category[]" id="<?php echo $termid; ?>" value="<?php echo $termid; ?>" class="checkbox" <?php if($pid[0]){ if(in_array($termid,$pid) || in_array('all',$pid)){ echo "checked=checked"; } }else{  }?> />&nbsp;<?php echo $name; if($termprice != "") { echo " (".display_amount_with_currency_plugin($termprice).") ";}else{  echo " (".display_amount_with_currency_plugin('0').") "; } ?></label></li>
		<?php
		
		if($taxonomy !=""){
		 $child = get_term_children( $termid, $post_taxonomy );
		 $args = array(
				'type'                     => 'place,event',
				'child_of'                 => $termid,
				'hide_empty'               => 0,
				'taxonomy'                 => $post_taxonomy
				);
		 $categories = get_categories( $args );
		 
		 foreach($categories as $child_of)
		 { 
			$child_of = $child_of->term_id; 
		 	$p = 0;
			$term = get_term_by( 'id', $child_of,$post_taxonomy);
			$termid = $term->term_taxonomy_id;
			$term_tax_id = $term->term_id;
			$termprice = $term->term_price;
			$name = $term->name;
			if($child_of)
			{				
				$catprice = $wpdb->get_row($wpdb->prepare("select * from $wpdb->term_taxonomy tt ,$wpdb->terms t where t.term_id=%s and t.term_id = tt.term_id AND tt.taxonomy =%s",$child_of,$taxonomy));
				for($i=0;$i<count($catprice);$i++)
				{
					if($catprice->parent)
					{	
						$p++;
						$catprice1 = $wpdb->get_row($wpdb->prepare("select * from $wpdb->term_taxonomy tt ,$wpdb->terms t where t.term_id=%s and t.term_id = tt.term_id AND tt.taxonomy =%s",$catprice->parent,$taxonomy));
						if($catprice1->parent)
						{
							$i--;
							$catprice = $catprice1;
							continue;
						}
					}
				}
			}
			$p = $p*15;
		 ?>
			<li style="margin-left:<?php echo $p; ?>px;"><label for="<?php echo $term_tax_id; ?>"><input type="checkbox" name="category[]" id="<?php echo $term_tax_id; ?>" value="<?php echo $term_tax_id; ?>" class="checkbox" <?php if($pid[0]){ if(in_array($term_tax_id,$pid) || in_array('all',$pid)){ echo "checked=checked"; } }else{  }?> />&nbsp;<?php echo $name; if($termprice != "") { echo " (".display_amount_with_currency_plugin($termprice).") ";}else{  echo " (".display_amount_with_currency_plugin('0').") "; } ?></label></li>
		<?php  }	}else{
		 $child = get_term_children( $termid, $post_taxonomy );
		 
		 foreach($child as $child_of)
		 { 
		 	$p = 0;
			$term = get_term_by( 'id', $child_of,$post_taxonomy);
			$termid = $term->term_taxonomy_id;
			$term_tax_id = $term->term_id;
			$termprice = $term->term_price;
			$name = $term->name;
			if($child_of)
			{
				$catprice = $wpdb->get_row($wpdb->prepare("select * from $wpdb->term_taxonomy tt ,$wpdb->terms t where t.term_id=%s and t.term_id = tt.term_id AND (tt.taxonomy =%s)",$child_of,$post_taxonomy));
				for($i=0;$i<count($catprice);$i++)
				{
					if($catprice->parent)
					{	
						$p++;
						$catprice1 = $wpdb->get_row($wpdb->prepare("select * from $wpdb->term_taxonomy tt ,$wpdb->terms t where t.term_id=%s and t.term_id = tt.term_id AND (tt.taxonomy =%s)",$catprice->parent,$post_taxonomy));
						if($catprice1->parent)
						{
							$i--;
							$catprice = $catprice1;
							continue;
						}
					}
				}
			}
			$p = $p*15;
		 ?>
			<li style="margin-left:<?php echo $p; ?>px;"><label><input type="checkbox" name="category[]" id="<?php echo $term_tax_id; ?>" value="<?php echo $term_tax_id; ?>" class="checkbox" <?php if($pid[0]){ if(in_array($term_tax_id,$pid) || in_array('all',$pid)){ echo "checked=checked"; } }else{  }?> />&nbsp;<?php echo $name; if($termprice != "") { echo " (".display_amount_with_currency_plugin($termprice).") ";}else{  echo " (".display_amount_with_currency_plugin('0').") "; } ?></label></li>
		<?php  }	
				}		
}
	echo "</ul>"; } else{
			sprintf(__('There is no categories in %s',LMADMINDOMAIN),$post_type);
	}
}
/*
 * Function Name:  get_current_multicity_info
 * This function create current city information global as per change city selection or current city selected
 */
 
add_action('init','location_current_multicity',9);
function location_current_multicity(){
	
	/* after instant search the url of mulitple selected city post does not return the url of currently selected city added the  'tevolution_autocomplete_callBack' in condition*/
	
	if(((is_admin() || strstr($_SERVER['REQUEST_URI'],'/wp-login.php')) && (is_admin() && @$_REQUEST['action'] != 'submit_category_custom_fields')) && (is_admin() && (@$_REQUEST['action'] != 'tevolution_autocomplete_callBack' && @$_REQUEST['action'] != 'googlemap_initialize'))) {
		return;	
	}
	
	global $wpdb,$country_table,$zones_table,$multicity_table,$current_cityinfo,$wp_query;	
	$city_slug=get_option('location_multicity_slug');	
	$multi_city=($city_slug)? $city_slug : 'city';
	$country_table = $wpdb->prefix."countries";
	$zones_table =$wpdb->prefix . "zones";	
	$multicity_table = $wpdb->prefix . "multicity";	
	$geoip_location_tracking=get_option('geoip_location_tracking');
	
	if(isset($_REQUEST['find_city']) && $_REQUEST['find_city']=='nearest'){
		$lat=$_COOKIE['c_latitude'];
		$long=$_COOKIE['c_longitude'];
		if(!isset($_COOKIE['c_latitude']) && !isset($_COOKIE['c_longitude'])){
			$ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
			/* get geo ip location tracking option */
			if($geoip_location_tracking=='maxmind'){
				$rsGeoData = get_geoip_record_by_addr($ip);
				if ($rsGeoData!='' && is_object($rsGeoData)) {			    
					$lat = $_COOKIE['c_latitude'] = $rsGeoData->latitude;
					$long = $_COOKIE['c_longitude']= $rsGeoData->longitude;
				}	
			}else{
				$url = "http://freegeoip.net/json/$ip";
				$data=wp_remote_get( $url, array( 'timeout' => 10, 'httpversion' => '1.1','redirection' => 5));			
				if ($data && !is_wp_error($data)) {
					$location = json_decode($data['body']);				
					$lat = $_COOKIE['c_latitude'] = $location->latitude;
					$long = $_COOKIE['c_longitude']= $location->longitude;
				}
			}
			
			
		}
		
		$sql="SELECT distinct city_id, cityname,city_slug FROM  $multicity_table, {$wpdb->prefix}postmeta,{$wpdb->prefix}posts WHERE meta_key='post_city_id' AND meta_value=city_id and {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID and {$wpdb->prefix}posts.post_status = 'publish'  ORDER BY truncate((degrees(acos( sin(radians(lat)) * sin( radians('".$lat."')) + cos(radians(lat)) * cos( radians('".$lat."')) * cos( radians(lng - '".$long."') ) ) ) * 69.09),1) ASC LIMIT 0,1";
		
		$nearest_result=$wpdb->get_results($sql);
		$_SESSION['post_city_id']=$nearest_result[0]->city_id;	
	}
	
	/* Header City */
	if(isset($_POST['header_city']) && $_POST['header_city']!=""){
		$_SESSION['post_city_id']=$_POST['header_city'];	
	}
	if(isset($_POST['widget_city']) && $_POST['widget_city']!=""){
		$_SESSION['post_city_id']=$_POST['widget_city'];	
	}
	
	/*Change default post is location manager wise then change current city as per set on post */
	if(get_post_type()=='post' && is_single()){
		$location_post_type=','.implode(',',get_option('location_post_type'));		
		if((strpos($location_post_type,','.get_post_type())) !== false ){
			global $post;			
			$pcity_id = get_post_meta($post->ID,'post_city_id',true);
			if(in_array($_SESSION['post_city_id'],explode(',',$pcity_id))){
				$pcity_id=$_SESSION['post_city_id'];
			}			
			$sql=$wpdb->prepare("SELECT city_slug FROM $multicity_table where city_id=%d",$pcity_id);
			$default_city = $wpdb->get_var($sql);
			$wp_query->set($multi_city,$default_city);
			
		}			
	}
		
	if((isset($_SESSION['post_city_id']) && $_SESSION['post_city_id']!='') || get_query_var($multi_city)!=''){
		/* 
		 * Check the query var city not equal blank then set the multicity by cityname slug
		 */		
		if(get_query_var($multi_city)!='')
			$sql=$wpdb->prepare("SELECT * FROM $multicity_table where city_slug=%s",sanitize_title(get_query_var($multi_city)));
		else
			$sql=$wpdb->prepare("SELECT * FROM $multicity_table where city_id=%d",$_SESSION['post_city_id']);
		
	}else{
		/*  Fetch the remote address location*/		
		if(get_option('default_city_set')=='nearest_city'){
			$ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
			if($geoip_location_tracking=='maxmind'){
				$rsGeoData = get_geoip_record_by_addr($ip);
				if ($rsGeoData!='' && is_object($rsGeoData)) {			    
					$lat = $_COOKIE['c_latitude'] = $rsGeoData->latitude;
					$long = $_COOKIE['c_longitude']= $rsGeoData->longitude;
				}
			}else{
				$url = "http://freegeoip.net/json/$ip";
				$data=wp_remote_get( $url, array( 'timeout' => 120, 'httpversion' => '1.1','redirection' => 5) );
				if ($data && !is_wp_error($data)) {
					$location = json_decode($data['body']);				
					$lat = $_COOKIE['c_latitude'] = $location->latitude;
					$long = $_COOKIE['c_longitude']= $location->longitude;
				}
			}			
			if ($lat!='' && $long!='') {
                                                                               $sql="SELECT distinct city_id, cityname,city_slug FROM  $multicity_table, {$wpdb->prefix}postmeta,{$wpdb->prefix}posts WHERE meta_key='post_city_id' AND meta_value=city_id and {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID and {$wpdb->prefix}posts.post_status = 'publish'  ORDER BY truncate((degrees(acos( sin(radians(lat)) * sin( radians('".$lat."')) + cos(radians(lat)) * cos( radians('".$lat."')) * cos( radians(lng - '".$long."') ) ) ) * 69.09),1) ASC LIMIT 0,1";
			}else{
				$sql=$wpdb->prepare("SELECT * FROM $multicity_table where is_default=%d",1);
			}
		/* Finish fetach remote address location */
		}else{
			$sql=$wpdb->prepare("SELECT * FROM $multicity_table where is_default=%d",1);
		}
	}
	$default_city = $wpdb->get_results($sql);
	$default_city_id=$default_city[0]->city_id;
	$_SESSION['post_city_id']=$default_city_id;
	
	$cityinfo = $wpdb->get_results($wpdb->prepare("SELECT mc.*,mc.message as msg,c.country_name,c.iso_code_2,c.iso_code_3,c.country_flg,z.zone_name FROM $multicity_table mc,$zones_table z,$country_table c where c.country_id=mc.country_id AND z.zones_id=mc.zones_id AND  mc.city_id =%d order by cityname   ASC",$_SESSION['post_city_id']));
	
	if (function_exists('icl_register_string')){
		icl_register_string('location-manager', 'location_city_'.$cityinfo[0]->city_slug,$cityinfo[0]->cityname);
		icl_register_string('location-manager', 'location_city_msg'.$cityinfo[0]->city_slug,$cityinfo[0]->msg);
		$cityinfo[0]->cityname = icl_t('location-manager', 'location_city_'.$cityinfo[0]->city_slug,$cityinfo[0]->cityname);
		$cityinfo[0]->msg = icl_t('location-manager', 'location_city_msg'.$cityinfo[0]->city_slug,$cityinfo[0]->msg);
	}
	
	$current_cityinfo=array('city_id'      =>$cityinfo[0]->city_id,
					    'country_id'   =>$cityinfo[0]->country_id,
						'country_iso2'   =>$cityinfo[0]->iso_code_2,
						'country_iso3'   =>$cityinfo[0]->iso_code_3,
					    'zones_id'     =>$cityinfo[0]->zones_id,
					    'cityname'     => stripslashes($cityinfo[0]->cityname),
					    'city_slug'    =>$cityinfo[0]->city_slug,
					    'lat'          =>$cityinfo[0]->lat,
					    'lng'          =>$cityinfo[0]->lng,
					    'scall_factor' =>$cityinfo[0]->scall_factor,
					    'is_zoom_home' =>$cityinfo[0]->is_zoom_home,
					    'map_type'     =>$cityinfo[0]->map_type,
					    'post_type'    =>$cityinfo[0]->post_type,
					    'categories'   =>$cityinfo[0]->categories,
					    'color'        =>$cityinfo[0]->color,
					    'message'      =>$cityinfo[0]->msg,
					    'color'        =>$cityinfo[0]->color,
					    'images'       =>$cityinfo[0]->images,
					    'country_name' =>$cityinfo[0]->country_name,
					    'country_flg'  =>$cityinfo[0]->country_flg,
					    'zone_name'    =>$cityinfo[0]->zone_name,
					    'header_color' =>$cityinfo[0]->header_color,
					    'header_image' =>$cityinfo[0]->header_image,
						'cat_scall_factor' =>$cityinfo[0]->cat_scall_factor,
					    'is_zoom_cat' =>$cityinfo[0]->is_zoom_cat,
					    );
	return apply_filters('location_current_cityinfo',$current_cityinfo);
}
?>