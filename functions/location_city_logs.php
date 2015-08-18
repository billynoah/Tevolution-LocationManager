<?php
/*
 * This file use for see city log in backend
 */
add_action('location_tabs_content','location_manage_city_logs');
function location_manage_city_logs($location_tabs){
	switch ($location_tabs):
		case 'location_city_log' :
			echo '<div class="wrap">';
			if(isset($_REQUEST['city_id']) && $_REQUEST['city_id']!=''){
				?>
                     <h2><?php echo __('City Wise Logs',LMADMINDOMAIN);?>	
                     <a id="country_list" href="<?php echo site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=location_city_log';?>" title="<?php echo __('Back to city logs',LMADMINDOMAIN);?>" name="btnviewlisting" class="add-new-h2" /><?php echo __('Back to city logs',LMADMINDOMAIN); ?></a>
                     </h2>
                    <form name="frm_citywise" id="frm_citywise" action="" method="post" >
				<?php
                    $directory_citywise_logs = new wp_list_citywise_logs();
                    $directory_citywise_logs->prepare_items();
                    $directory_citywise_logs->search_box('search', 'search_id');
                    $directory_citywise_logs->display();
                    ?>
                    <input type="hidden" name="check_compare">
               </form>
                    <?php
				
			}else{
				echo '<h2>'.__('City Logs',LMADMINDOMAIN).'</h2>';
				?>
                    <p class="tevolution_desc"><?php echo __('Use this section to see how many times was each of your cities visited. This is useful in determining which is your most popular city.',LMADMINDOMAIN);?></p>
				<form name="frm_city_log" id="frm_city_log" action="" method="post" >
					<?php
					$location_city_logs = new wp_list_city_logs();
					$location_city_logs->prepare_items();
					$location_city_logs->search_box('search', 'search_id');
					$location_city_logs->display();
					?>
					<input type="hidden" name="check_compare">
				</form>
				<?php
			}
			echo '</div>';
		break;
	endswitch;
}
/*
 * Manage city logs list table 
 */
if(!class_exists('Tmpl_WP_List_Table')){
    include_once( WP_PLUGIN_DIR . '/Tevolution/templatic.php');
}
class wp_list_city_logs extends Tmpl_WP_List_Table 
{	
	/* fetch all the country data */
	function fetch_citylogs()
	{
		global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table;
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('directory_setting_fields_per_page', 25);				
		if(isset($_POST['s']) && $_POST['s']!=""){			
			$sql = "select mc.city_id, mc.cityname,z.zone_name,c.country_name, sum(log_count) as total_count from $multicity_table mc,$country_table c,$zones_table z,$city_log_table l where l.log_city_id=mc.city_id AND mc.zones_id=z.zones_id AND mc.country_id=c.country_id AND c.country_id=z.country_id AND mc.cityname like'%".$_POST['s']."%' group by l.log_city_id";
		}else{
			if(isset($_GET['orderby']) && $_GET['orderby']=='state')
				$order_by='z.zone_name';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='country')
				$order_by='c.country';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='count')
				$order_by='total_count';
			else
				$order_by='mc.cityname';
				
			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			$sql = "select mc.city_id, mc.cityname,z.zone_name,c.country_name, sum(log_count) as total_count from $multicity_table mc,$country_table c,$zones_table z,$city_log_table l where l.log_city_id=mc.city_id AND mc.zones_id=z.zones_id AND mc.country_id=c.country_id AND c.country_id=z.country_id group by l.log_city_id order by $order_by  $order";
		}			
		$cityinfo = $wpdb->get_results($sql);		
		
		if($cityinfo)
		{ 
			 foreach($cityinfo as $resobj) :
			 	$view_detail='<a href="'.site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=location_city_log&city_id='.$resobj->city_id.'"  title="View Details"><img src="'.TEVOLUTION_LOCATION_URL.'images/veiw-detail-icon.png" /></a>';
			 	$country_data[] =  array('ID'      => $resobj->city_id,
									'title'	=> $resobj->cityname,
									'state'	=> $resobj->zone_name,
									'country'	=> $resobj->country_name,
									'count'	=> $resobj->total_count,
									'view'    => $view_detail,
							);
			endforeach;
		}
		return $country_data;
	}
	
	
	/* DEFINE THE COLUMNS FOR THE TABLE */
	function get_columns()
	{
		$columns = array(	
			'cb'      => '<input type="checkbox" />',
			'title'   =>  __('City Name',LMADMINDOMAIN),
			'state'   =>  __('State Name',LMADMINDOMAIN),
			'country' =>  __('Country Name',LMADMINDOMAIN),
			'count'   =>  __('Total Count',LMADMINDOMAIN),
			'view'    => __('Views',LMADMINDOMAIN),
			);
		return $columns;
	}
	
	/*Bulk Action process*/
	function process_bulk_action()
	{ 
		global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table;		
		$cids = @$_REQUEST['cf'];		
		if( 'delete' === $this->current_action() )
		{
			foreach( $cids as $cid )
			{
				
				
				if( wp_verify_nonce($_REQUEST['_wpnonce'],'bulk-tevolution_page_location_settings')){
					$sql_country="DELETE from $city_log_table where log_city_id=".$cid;
					$wpdb->query($sql_country);
				}else{		
					$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=location_city_log&msgtype=noncenotverify';
					wp_redirect($redirect_to);
					exit;
				}
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=location_city_log&msgtype=dele-suc';
			wp_redirect($redirect_to);
		}
	}
        
	function prepare_items()
	{
		$per_page = $this->get_items_per_page('directory_setting_fields_per_page', 25);
		$columns = $this->get_columns(); /* CALL FUNCTION TO GET THE COLUMNS */
		
		$hidden = array();
		$sortable = array();
		$sortable = $this->get_sortable_columns(); /* GET THE SORTABLE COLUMNS */
		
		$this->_column_headers = array($columns, $hidden, $sortable);		
		$this->process_bulk_action(); /* FUNCTION TO PROCESS THE BULK ACTIONS */		
		$data = $this->fetch_citylogs(); /* RETIRIVE THE TRANSACTION DATA */
		
		$current_page = $this->get_pagenum(); 
		$total_items = count($data); 
		if(is_array($data))
		$this->found_data = array_slice($data,(($current_page-1)*$per_page),$per_page); 
		$this->items = $this->found_data; 
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,      /*WE have to calculate the total number of items*/
			'per_page'    => $per_page         /*WE have to determine how many items to show on a page*/
		) );
	}
	
	/* To avoid the need to create a method for each column there is column_default that will process any column for which no special method is defined */
	function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'ID':
			case 'title':
			case 'state':
			case 'country':
			case 'count':
			case 'view';
			return $item[ $column_name ];
			default:
			return print_r( $item, true ) ; /*Show the whole array for troubleshooting purposes*/
		}
	}
	
	/* DEFINE THE COLUMNS TO BE SORTED */
	function get_sortable_columns()
	{
		$sortable_columns = array(			
			'title' => array('title',true),
			'state'=>array('state',true),
			'country' => array('country',true),
			'count' => array('count',true),
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
/*
 * Manage city wise logs list table 
 */
class wp_list_citywise_logs extends Tmpl_WP_List_Table 
{	
		/* fetch all the country data */
	function fetch_countries()
	{
		global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table;
		$paged   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page = $this->get_items_per_page('directory_setting_fields_per_page', 25);				
		if(isset($_POST['s']) && $_POST['s']!=""){
			$sql = "select * from $country_table where country_name ='".$_POST['s']."'";
		}else{
			if(isset($_GET['orderby']) && $_GET['orderby']=='state')
				$order_by='z.zone_name';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='country')
				$order_by='c.country';
			elseif (isset($_GET['orderby']) && $_GET['orderby']=='count')
				$order_by='total_count';
			else
				$order_by='mc.cityname';
				
			$order=(isset($_GET['order']))?$_GET['order']:'ASC';
			$sql = "select mc.city_id, mc.cityname, log_count,ip_address from $multicity_table mc,$city_log_table l where l.log_city_id=mc.city_id AND log_city_id=".$_REQUEST['city_id']." order by $order_by  $order";
		}		
		$citylogsinfo = $wpdb->get_results($sql);		
		
		if($citylogsinfo)
		{ 
			 foreach($citylogsinfo as $resobj) :			 	
			 	$country_data[] =  array('ID'         => $resobj->city_id,
									'title'	   => $resobj->cityname,
									'ip_address' => $resobj->ip_address,
									'counter'	   => $resobj->log_count,
								);
			endforeach;
		}
		return $country_data;
	}
	
	
	/* DEFINE THE COLUMNS FOR THE TABLE */
	function get_columns()
	{
		$columns = array(
			'title'   =>  __('City Name',LMADMINDOMAIN),
			'ip_address'   =>  __('IP Address',LMADMINDOMAIN),
			'counter' =>  __('Counter',LMADMINDOMAIN),
			);
		return $columns;
	}
	
	/*Bulk Action process*/
	function process_bulk_action()
	{ 
		global $wpdb,$country_table,$zones_table,$multicity_table,$city_log_table;		
		$cids = $_REQUEST['cf'];		
		if( 'delete' === $this->current_action() )
		{
			foreach( $cids as $cid )
			{
				$sql_country="DELETE from $city_log_table where log_city_id=".$cid;
				$wpdb->query($sql_country);				
			}
			$redirect_to=site_url().'/wp-admin/admin.php?page=location_settings&location_tabs=location_city_log&msgtype=dele-suc';
			wp_redirect($redirect_to);
		}		
		
		
	}
        
	function prepare_items()
	{
		$per_page = $this->get_items_per_page('directory_setting_fields_per_page', 25);
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
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,      /*WE have to calculate the total number of items*/
			'per_page'    => $per_page         /*WE have to determine how many items to show on a page*/
		) );
	}
	
	/* To avoid the need to create a method for each column there is column_default that will process any column for which no special method is defined */
	function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'ID':
			case 'title':
			case 'ip_address':
			case 'counter':
			return $item[ $column_name ];
			default:
			return print_r( $item, true ) ; /*Show the whole array for troubleshooting purposes*/
		}
	}
	
	/* DEFINE THE COLUMNS TO BE SORTED */
	function get_sortable_columns()
	{
		$sortable_columns = array('counter' => array('counter',true));
		return $sortable_columns;
	}	
	function column_cb($item)
	{ 
		return sprintf('<input type="checkbox" name="cf[]" value="%s" />', $item['ID']);
	}
}
?>