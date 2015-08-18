<?php
set_time_limit(0);
$file = dirname(__FILE__);
if( defined('WP_CONTENT_DIR') ){
	$content_dir = explode('/',WP_CONTENT_DIR);
	$file = substr($file,0,stripos($file, $content_dir[1]));
}else{
	$file = substr($file,0,stripos($file, "wp-content"));
}
require( dirname(dirname(dirname( dirname(dirname( __FILE__ ) ) ))) . "/wp-load.php");
global $wpdb,$country_table,$zones_table,$multicity_table;
/* 
 *  Export multicity 
 */	
if(isset($_POST['export_city_csv']) && isset($_POST['export_city']) && $_POST['export_city']=='1')
{
	$fname = "cities_report_".strtotime(date('Y-m-d')).".csv";
	header('Content-Description: File Transfer');
	header("Content-type: application/force-download");
	header('Content-Disposition: inline; filename="'.$fname.'"');
	ob_end_clean();
	ob_start();
	$f = fopen('php://output', 'w') or show_error("Can't open php://output");
	
	$city_info = $wpdb->get_results("select * from $multicity_table order by city_id");		
	if($city_info){
		echo $header_top =  "City Id,Country Id,Zones Id,City Name,City Slug,Latitude,Longitude,Scall Factor,Is Zoom Home,Map Type,Post Type,Category ids,Is Default,Message,Color,Image,header color,Header image\r\n";
		foreach($city_info as $city){				
			$city_content=  array("$city->city_id","$city->country_id","$city->zones_id","$city->cityname","$city->city_slug","$city->lat","$city->lng","$city->scall_factor","$city->is_zoom_home","$city->map_type","$city->post_type","$city->categories","$city->is_default","$city->message","$city->color","$city->images","$city->header_color","$city->header_image");	
		
			if ( !fputcsv($f, $city_content)){
				echo "Can't write line $n: $line";
			}
		}
		fclose($f) or show_error("Can't close php://output");
		$csvStr = ob_get_contents();
		ob_end_clean();	
		print_r($csvStr);
		
	}else{
		echo "No record available";
	}
	
}
?>
