/* Location navigation open close script */
jQuery(document).ready(function(){jQuery("#directorytab").click(function(){jQuery("#directory_location_navigation").toggleClass("horizontal_open");jQuery("#horizontal_show_togglebox-button").toggleClass('horizontal_open');jQuery("#directorytab").toggleClass("directorytab_open")})})

jQuery(document).ready(function(){
	/* city selection*/
	jQuery('#directorytab').click(function() {
		jQuery('#directorytab').toggleClass('temp-zindex'); 
		jQuery( "body" ).toggleClass('overlay-dark'); 
	}); 
	jQuery('.exit-selection').click(function() {
		jQuery('#directorytab').removeClass('temp-zindex');
		jQuery( "body" ).removeClass('overlay-dark'); 
		jQuery("#directory_location_navigation").removeClass('horizontal_open');
		jQuery("#directorytab").removeClass('directorytab_open');
	});
	  /* remove class when 'esc' key pressed*/
	jQuery( "html" ).keydown(function( event ) {
	  if ( event.which == 27 ) {
		jQuery('#directorytab').removeClass('temp-zindex');
		jQuery( "body" ).removeClass('overlay-dark'); 
		jQuery("#directory_location_navigation").removeClass('horizontal_open');
		jQuery("#directorytab").removeClass('directorytab_open');
	  }
	});
});

/* Get the cities after click on city link in header */

jQuery(document).ready(function(){
	jQuery("#directorytab").click(function(){ 
		if(jQuery("#loc_city_link").attr('data-location') =='location_aslink'){
		jQuery("#location_loading").show();
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=tev_ajax_headerlocation',
			success:function(results) {	
				jQuery("#location_loading").hide();
				jQuery('#horizontal_header_location').html(results);
			}
		});	
		}
	});		
});
function set_default_city(str,city_id){
	
	var city_id=city_id;
	var city_name=jQuery('#'+str.id).attr( "class" );	
	jQuery.ajax({
		url:ajaxUrl,
		type:'POST',
		data:'action=default_city&city_id=' + city_id,
		success:function(results) {					
			jQuery('#city_default_'+city_id).css({'font-weight':'bold'}); 
			jQuery("#city_default_"+city_id).after('&nbsp;<span style="color:green;" class="default_city" id="set_default_city_'+city_id+'">Default City</span>');
			jQuery('#city_default_'+results).removeAttr('style');
			jQuery('#set_default_city_'+results ).remove();
			jQuery('#default_city_name').html('Default City ('+city_name+')');
		}
	});
	
}
function change_default_city_set(str){	
	if(str.value=='location_tracking'){
		jQuery('#location_tracking' ).show();
		jQuery('#default_city_set_msg' ).hide();
		jQuery('#nearest_city_set_msg' ).hide();
	}else if(str.value=='nearest_city'){
		jQuery('#location_tracking' ).hide();
		jQuery('#default_city_set_msg' ).hide();
		jQuery('#nearest_city_set_msg' ).show();
	}else{
		jQuery('#location_tracking' ).hide();
		jQuery('#nearest_city_set_msg' ).hide();
		jQuery('#default_city_set_msg' ).show();
	}
}
/* Start City background color picker */
function show_colorpicker(id)
{
	document.getElementsByName(id)[0].style.display = '';				
}
/*END City background color picker */
/* Fill Zone select box according to country */
function fill_zones_cmb(str,f){
	
	var country_id=str.value;
	var front='';
	if(f==1){
		front='&front=1';	
	}
	/* Remove selected city in dorp down*/
	if(jQuery('select#city_id option:selected').val()!=''){
		jQuery('select#city_id option:selected').remove();
	}
	
	document.getElementById('process_state').style.display="";
	jQuery.ajax({
		url:ajaxUrl,
		type:'POST',
		data:'action=fill_states_cmb&country_id=' + country_id+front,
		success:function(results) {	
			document.getElementById('process_state').style.display="none";
			jQuery('#zones_id').html(results);
			/* call fill city cmb function is one state in available */
			if(jQuery('select#zones_id option:selected').val()!=''){	
				jQuery('form#submit_form select#zones_id').trigger('change');			
				fill_city_cmb(jQuery('select#zones_id option:selected').val());
			}else{					
				/*header_zone*/
				var first_option = jQuery('#zones_id option').first();					
				jQuery('#zones_id + span.select').text(first_option.text());
				/*header_city*/
				var city_first_option = jQuery('#city_id option').first();					
				jQuery('#city_id + span.select').text(city_first_option.text());
			}
		}
	});
	
}
function fill_city_cmb(str){	
	
	var state_id=(str.value!='' && jQuery.isNumeric(str.value) )? str.value : str ;
	document.getElementById('process_city').style.display="";
	if(state_id==''){
		var country_id=document.getElementById('country_id');
		fill_multicity_cmb(country_id,'');
		
	}else{
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=fill_city_cmb&state_id=' + state_id,
			success:function(results) {	
				document.getElementById('process_city').style.display="none";
				jQuery('#city_id').html(results);
				jQuery('form#submit_form select#city_id').trigger('change');
			}
		});
		}
	
}
function fill_multicity_cmb(str,f){
	
	var country_id=str.value;
	var front='';
	if(f==1){
		front='&front=1';	
	}
	document.getElementById('process_city').style.display="";
	jQuery.ajax({
		url:ajaxUrl,
		type:'POST',
		data:'action=fill_multicity_cmb&country_id=' + country_id+front,
		success:function(results) {	
			document.getElementById('process_city').style.display="none";
			jQuery('#city_id').html(results);
			jQuery('form#submit_form select#city_id').trigger('change');
		}
	});
	
}
jQuery(document).ready(function(){	
	jQuery("#header_country").change(function(){
	    	var country_id = jQuery('#header_country').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=fill_states_cmb&country_id=' + country_id+'&front=1&header=1',
			success:function(results) {				
				results=results.split('++');
				jQuery('#header_zone').html(results[0]);
				jQuery('#header_city').html(results[1]);
				if(jQuery('select#header_zone option:selected').val()!=''){
					//jQuery('.horizontal_location_nav select#header_city').trigger('change');		
					jQuery('select#header_zone option:selected').prop('selected',false);
				}else{					
					/*header_zone*/
					var first_option = jQuery('#header_zone option').first();					
					jQuery('#header_zone + span.select').text(first_option.text());
					/*header_city*/
					var city_first_option = jQuery('#header_city option').first();					
					jQuery('#header_city + span.select').text(city_first_option.text());
				}
			}
		});
	});
	
	
});
jQuery(document).ready(function(){	
	jQuery("#header_zone").change(function(){				
	    	var state_id = jQuery('#header_zone').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=fill_city_cmb&state_id=' + state_id+'&front=1',
			success:function(results) {				
				jQuery('#header_city').html(results);
				/* call fill city cmb function is one state in available */
				jQuery('.horizontal_location_nav select#header_city').trigger('change');
				if(jQuery('select#header_city option:selected').val()!=''){									
					jQuery('#multicity_form').submit()
				}else{
					/*header_city*/
					var city_first_option = jQuery('#header_city option').first();					
					jQuery('#header_city + span.select').text(city_first_option.text());
				}
			}
		});	
	});		
});
jQuery(document).ready(function(){	
	jQuery("#header_city").change(function(){
		if(jQuery(this).val()!=''){
			jQuery('#multicity_form').submit();
		}
	});		
});
/*Advance Search shortcode country wise field zone and city */
jQuery(document).ready(function(){	
	jQuery("#adv_country").change(function(){				
	    	var country_id = jQuery('#adv_country').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=fill_states_cmb&country_id=' + country_id+'&front=1',
			success:function(results) {				
				jQuery('#adv_zone').html(results);
				
				if(jQuery('select#adv_zone option:selected').val()!=''){		
					jQuery('.widget select#adv_zone').trigger('change');					
					jQuery('select#adv_city').trigger('change');		
					jQuery('select#adv_zone option:selected').prop('selected',false);
				}else{					
					/*header_zone*/
					var first_option = jQuery('#adv_zone option').first();					
					jQuery('#adv_zone + span.select').text(first_option.text());
					/*header_city*/
					var city_first_option = jQuery('#adv_city option').first();					
					jQuery('#adv_city + span.select').text(city_first_option.text());
				}
			}
		});	
	});		
});
jQuery(document).ready(function(){	
	jQuery("#adv_zone").change(function(){				
	    	var state_id = jQuery('#adv_zone').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			data:'action=fill_city_cmb&state_id=' + state_id,
			success:function(results) {				
				jQuery('#adv_city').html(results);
				/* call fill city cmb function is one state in available */
				jQuery('.widget select#adv_city').trigger('change');
				if(jQuery('select#adv_city option:selected').val()!=''){
					jQuery('select#adv_city').trigger('change');						
				}else{
					var city_first_option = jQuery('#header_city option').first();					
					jQuery('#adv_city + span.select').text(city_first_option.text());
				}
			}
		});	
	});		
});

/*Location Widget for get country, zone and city */
jQuery(document).ready(function(){	
	jQuery("#widget_country").change(function(){				
		var country_id = jQuery('#widget_country').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			async: true,
			data:'action=fill_states_cmb&country_id=' + country_id+'&front=1',
			success:function(results) {				
				jQuery('#widget_zone').html(results);
				if(jQuery('select#widget_zone option:selected').val()!=''){
					jQuery('.widget_location_nav select').trigger('change');		
					jQuery('select#widget_zone option:selected').prop('selected',false);
				}else{					
					/*widget_zone*/
					var first_option = jQuery('#widget_zone option').first();					
					jQuery('#widget_zone + span.select').text(first_option.text());
					/*header_city*/
					var city_first_option = jQuery('#widget_city option').first();					
					jQuery('#widget_city + span.select').text(city_first_option.text());
				}
			}
		});	
	});		
});

jQuery(document).ready(function(){	
	jQuery("#widget_zone").change(function(){				
		var state_id = jQuery('#widget_zone').val();		
		jQuery.ajax({
			url:ajaxUrl,
			type:'POST',
			async: true,
			data:'action=fill_city_cmb&state_id=' + state_id+'&front=1',
			success:function(results) {				
				jQuery('#widget_city').html(results);
				/* call fill city cmb function is one state in available */
				jQuery('.widget_location_nav select#widget_city').trigger('change');
				if(jQuery('select#widget_city option:selected').val()!=''){				
					jQuery('#widget_multicity_form').submit()
				}
			}
		});	
	});		
});

jQuery(document).ready(function(){	
	jQuery("#widget_multicity_form #widget_city").change(function(){					   
		if(jQuery(this).val()!=''){
			jQuery('#widget_multicity_form').submit();
		}		
	});		
});
/*Finish the advance search shortcode multicity */
/*Set Cookies Function */
function setCookie(name,value,days) {
   if (days) {
	  var date = new Date();
	  date.setTime(date.getTime()+(days*24*60*60*1000));
	  var expires = "; expires="+date.toGMTString();
   }
   else var expires = "";
   document.cookie = name+"="+value+expires+"; path=/";
}
function getCookie(name) {
   var nameEQ = name + "=";
   var ca = document.cookie.split(';');
   for(var i=0;i < ca.length;i++) {
	  var c = ca[i];
	  while (c.charAt(0)==' ') c = c.substring(1,c.length);
	  if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
   }
   return null;
}
function deleteCookie(name) {
   setCookie(name,"",-1);
}
function manage_city_validation(){
	jQuery.noConflict();
	var country_id = jQuery('#country_id').val();
	var zones_id = jQuery('#zones_id').val();
	var address = jQuery("#address").val();
	var check=0;
	jQuery("input[name='city_post_type[]']").each(function() {
		if (jQuery(this).attr('checked'))
		{
			check=1;
		}
	});
	
	var flg=0;
	if(country_id==''){
		jQuery('#admin_country_id').addClass('form-invalid');
		flg=1;
	}else{
		jQuery('#admin_country_id').removeClass('form-invalid');			
	}
	
	if(zones_id==''){
		jQuery('#admin_zones_id').addClass('form-invalid');
		flg=1;
	}else{
		jQuery('#admin_zones_id').removeClass('form-invalid');			
	}
	
	if(address==''){
		jQuery('#admin_city_name').addClass('form-invalid');
		flg=1;
	}else{
		jQuery('#admin_city_name').removeClass('form-invalid');			
	}
	
	if(check==0){
		jQuery('#admin_post_type').addClass('form-invalid');
		flg=1;
	}else{
		jQuery('#admin_post_type').removeClass('form-invalid');
	}
	
	if(flg==1){
		return false;	
	}else{
		return true;	
	}
}

jQuery(document).ready(function(){
	jQuery("input[name='geoip_location_tracking']").on('change',function(e){
		
		jQuery("input[name='geoip_location_tracking']").each(function(){
			if (jQuery(this).attr('checked') && jQuery(this).val()=='maxmind'){
				jQuery('.maxmind_description').css('display','block');
			}else{
				jQuery('.maxmind_description').css('display','none');	
			}
		});
	});
	
	jQuery( "a.left-off-canvas-toggle" ).click(function() {
		jQuery( "span.toggle_handler" ).toggleClass( "menu-show" ); 
	});
	jQuery( "a.exit-off-canvas" ).click(function() {
		jQuery( "span.toggle_handler" ).toggleClass( "menu-show" ); 
	});  
});