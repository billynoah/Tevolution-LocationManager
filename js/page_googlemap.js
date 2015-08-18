/* JavaScript Document*/
/*Search Google Map */
function new_googlemap_ajaxSearch(){
	var search_string = document.getElementById('search_string').value;
	var post_type='';
	var categoryname='';
	jQuery("input[name='posttype[]']").each(function() {
		if (jQuery(this).attr('checked'))
		{		
			post_type+=jQuery(this).val()+',';
		}
	});
	
	jQuery("input[name='categoryname[]']").each(function() {
		if (jQuery(this).attr('checked'))
		{		
			categoryname+=jQuery(this).val()+',';
		}
	});	
	document.getElementById('map_loading_div').style.display = 'block';
		jQuery.ajax({
			url:tevolutionajaxUrl,
			type:'POST',			
			data:'action=shortcode_googlemap_initialize&posttype='+post_type+'&categoryname='+categoryname+'&search_string=' + search_string,
			success:function(results) {				
				/*jQuery('#adv_city').html(results);*/
				document.getElementById('map_loading_div').style.display = 'none';
				googlemap(results);
				
			}
		});	
}
/*Google map widget initialize */
function newgooglemap_initialize(){	
	var post_type='';
	var categoryname='';
	var city_id=document.getElementById('short_code_city_id').value;
	jQuery("input[name='posttype[]']").each(function() {
		if (jQuery(this).attr('checked'))
		{		
			post_type+=jQuery(this).val()+',';
		}
	});
	
	jQuery("input[name='categoryname[]']").each(function() {
		if (jQuery(this).attr('checked'))
		{		
			categoryname+=jQuery(this).val()+',';
		}
	});	
	document.getElementById('map_loading_div').style.display = 'block';
		jQuery.ajax({
			url:tevolutionajaxUrl,
			type:'POST',
			data:'action=shortcode_googlemap_initialize&posttype='+post_type+'&categoryname='+categoryname+'&city_id='+city_id,
			success:function(results) {				
				document.getElementById('map_loading_div').style.display = 'none';
				googlemap(results);
				
			}
		});	
		
}
/* read the data, create markers*/
function googlemap(data) {
	
	/* get the bounds of the map*/
	bounds = new google.maps.LatLngBounds();  
	/* clear old markers*/
  	googlemap_deleteMarkers();
  
	jsonData = jQuery.parseJSON(data);
  	/* create the info window*/
	infowindow = new google.maps.InfoWindow();
  	/* if no markers found, display map_marker_nofound div with no search criteria met message	*/
  	 if (jsonData[0].totalcount <= 0) {
		document.getElementById('map_marker_nofound').style.display = 'block';
		var mapcenter = new google.maps.LatLng(map_latitude,map_longitude);
		googlemap_listMapMarkers1(jsonData);
		map.setCenter(mapcenter);
		map.setZoom(map_zomming_fact);
  	}else{	
		document.getElementById('map_marker_nofound').style.display = 'none';
		var mapcenter = new google.maps.LatLng(map_latitude,map_longitude);
		googlemap_listMapMarkers1(jsonData);
		if(zoom_option==1){
			map.fitBounds(bounds);
			var center = bounds.getCenter();
			map.setCenter(center);
		}else{
			map.setCenter(mapcenter);
			map.setZoom(map_zomming_fact);
		}
	}
}
/*Delete the existing google map markers */
function googlemap_deleteMarkers() {	
	 if (markerArray && markerArray.length > 0) {		
		for (i in markerArray) {
			if (!isNaN(i)){				
				/*alert(i);				*/
				markerArray[i].setMap(null);
				/*markers[i].setMap(null); 	*/
				
			}
		}
		markerArray.length = 0;
	  }	
	if(mClusterer != null)
	{
		mClusterer.clearMarkers();
	}
}
/*listMapMarkers function for display the markers on google map */
function googlemap_listMapMarkers1(input) {
	markers=input;
	var totalcount = input[0].totalcount;
	/*New infobundle			*/
	 infoBubble = new InfoBubble({
		maxWidth:210,minWidth:210,minHeight:"auto",padding:0,borderRadius:0,borderWidth:0,overflow:"visible",backgroundColor:"#fff"
	  });			
	/*finish new infobundle*/
	if(totalcount > 0){		
		for (var i = 0; i < input.length; i++) {							 
			var details = input[i];	
			/*alert(details+"=="+details.location[0]+"==="+details.location[1]);*/
			var image = new google.maps.MarkerImage(details.icons,new google.maps.Size(PIN_POINT_ICON_WIDTH, PIN_POINT_ICON_HEIGHT));
			var coord = new google.maps.LatLng(details.location[0], details.location[1]);			
			markers[i]  = new google.maps.Marker({
							position: coord,
							title: details.name,
							content: details.message,
							visible: true,
							clickable: true,
							map: map,
							icon: details.icons
						});
			
			bounds.extend(coord);
			markerArray[i] = markers[i];				
			markers[i]['infowindow'] = new google.maps.InfoWindow({
				content: details.message
			});
			attachMessage(markers[i], details.message);
			if(pipointeffect =='hover'){
				 var pinpointElement = document.getElementById( 'pinpoint_'+details.pid );
							   if ( pinpointElement ) { 
							
								  google.maps.event.addDomListener( pinpointElement, 'mouseover', (function( theMarker ) {
									 return function() { 
										google.maps.event.trigger( theMarker, 'click' );
									 };
								  })(markers[i]) );
								 
							   }
			}else{
				 var pinpointElement = document.getElementById( 'pinpoint_'+details.pid );
							   if ( pinpointElement ) { 
							
								  google.maps.event.addDomListener( pinpointElement, 'click', (function( theMarker ) {
									 return function() { 
										google.maps.event.trigger( theMarker, 'click' );
									 };
								  })(markers[i]) );
								 
							   }
			
			}
						   
			mgr.addMarkers( markers[i], 0 );
	
		}		
		mClusterer = new MarkerClusterer(map, markers,{
						maxZoom: 0,
						gridSize: 10,
						styles: null,
						infoOnClick: 1,
						infoOnClickZoom: 18,
						});
		
	}  
}
/* but that message is not within the marker's instance data */
function attachMessage(marker, msg) {
  var myEventListener = google.maps.event.addListener(marker, 'click', function() {
		infoBubble.setContent( msg );
		infoBubble.open(map, marker);	
  });
}
/* Custom post type taxonomy open hide*/
function custom_post_type_taxonomy(id,str){
	
	var div1 = document.getElementById(id);
	var toggal = document.getElementById(str.id);	
	if (div1.style.display == 'none') {
		div1.style.display = 'block';
		jQuery('#'+str.id).removeClass('toggleoff');
		toggal.setAttribute('class','toggleon toggle_post_type');
	} else {
		div1.style.display = 'none';
		jQuery('#'+str.id).removeClass('toggleon');
		toggal.setAttribute('class','toggleoff toggle_post_type');
	}
}