<?php
global $wp_query,$wpdb;
/**-- condition for activate booking system --**/
update_option('no_alive_days',1);
if(@$_REQUEST['activated'] == 'tevolution_location' && @$_REQUEST['true']==1)
{
	if(!is_plugin_active('Tevolution-LocationManager/location-manager.php'))
	{		
		$current = get_option( 'active_plugins' );
	     $plugin = plugin_basename( trim( 'Tevolution-LocationManager/location-manager.php') );	
	     if ( !in_array( $plugin, $current ) ) {
		   $current[] = $plugin;
		   sort( $current );		  
		   update_option( 'active_plugins', $current );		  
	     }
	}
	update_option('currency_symbol','$');
	update_option('currency_code','USD');
	update_option('currency_pos','1');
	
}else if(isset($_REQUEST['deactivate']) && $_REQUEST['deactivate'] == 'tevolution_location' && isset($_REQUEST['true']) && $_REQUEST['true']==0)
{
	delete_option('tevolution_location');
}
?>