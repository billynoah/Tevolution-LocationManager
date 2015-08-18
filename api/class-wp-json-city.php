<?php

class WP_JSON_City {

    /**
     * Server object
     *
     * @var WP_JSON_ResponseHandler
     */
    protected $server;

    /**
     * Register the post-related routes
     *
     * @param array $routes Existing routes
     * @return array Modified routes
     */
    public function register_routes($routes) {
        $post_routes = array(
            // Post endpoints
            '/city/(?P<slug>[\w-]+)' => array(
                array($this, 'get_post_city_slug'), WP_JSON_Server::READABLE
            ),
            '/city/(?P<slug>[\w-]+)/post/type/(?P<type>[\w-]+)/page/(?P<page>[\w-]+)' => array(
                array(array($this, 'get_city_post_type'), WP_JSON_Server::READABLE)
            ),
            '/city/(?P<slug>[\w-]+)/post/type/(?P<type>[\w-]+)/taxonomy/(?P<taxonomy>[\w-]+)/category_id/(?P<category_id>[\w-]+)' => array(
                array(array($this, 'get_city_type_category'), WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT),
            ),
            '/city/(?P<slug>[\w-]+)/post/type/(?P<type>[\w-]+)/taxonomy/(?P<taxonomy>[\w-]+)/category_name/(?P<category_name>[\w-]+)' => array(
                array(array($this, 'get_city_type_category_name'), WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT),
            ),
            '/city/(?P<slug>[\w-]+)/post/type/(?P<type>[\w-]+)/taxonomy/(?P<taxonomy>[\w-]+)/tag_name/(?P<tag_name>[\w-]+)' => array(
                array(array($this, 'get_city_type_tag_name'), WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT),
            ),
			'/city/(?P<slug>[\w-_0-9]+)/taxonomy/(?P<taxonomy>[\w-]+)/hideempty/(?P<hideempty>[\w-_0-9]+)/terms' => array(
				array( array( $this, 'get_city_terms' ),      WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT ),
			),'/city/(?P<city>[\w-_0-9]+)/type/(?P<type>[\w-_0-9]+)/taxonomy/(?P<taxonomy>[\w-_0-9]+)/term_id/(?P<term_id>[\w-_0-9]+)/hideempty/(?P<hideempty>[\w-_0-9]+)/catlavel/(?P<catlavel>[\w-_0-9]+)/numberofcats/(?P<numberofcats>[\w-_0-9]+)/lang/(?P<lang>[\w-_0-9]+)/terms' => array( array( array( $this, 'get_child_cats_with_count' ),      WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT ),
			),

        );

        return array_merge($routes, $post_routes);
    }

    /* Return city details using city slug */

    public function get_post_city_slug($slug) {

        if (empty($slug))
            return new WP_Error('json_post_invalid_id', __('Invalid City slug.'), array('status' => 404));

        // Link headers (see RFC 5988)
        global $wpdb, $country_table, $zones_table, $multicity_table, $current_cityinfo, $wp_query;
        $country_table = $wpdb->prefix . "countries";
        $zones_table = $wpdb->prefix . "zones";
        $multicity_table = $wpdb->prefix . "multicity";

        $cityinfo = $wpdb->get_results($wpdb->prepare("SELECT mc.*,mc.message as msg,c.country_name,c.country_flg,z.zone_name FROM $multicity_table mc,$zones_table z,$country_table c where c.country_id=mc.country_id AND z.zones_id=mc.zones_id AND  mc.city_slug =%s order by cityname ASC", $slug));

        $response = new WP_JSON_Response();
        $response->set_data($cityinfo);

        return $response;
    }

    /* This function return filter posts whose city and post type pass as filter */

    public function get_city_post_type($slug, $type, $page) {

        if (empty($slug) || empty($type)) {
            return new WP_Error('json_post_invalid_id', __('Invalid slug or post type.'), array('status' => 404));
        }

        $city_id = $this->get_city_id($slug);
        if ($city_id == '')
            return new WP_Error('json_post_invalid_id', __('Invalid city slug.'), array('status' => 404));

        $args = array(
			'posts_per_page' => $page,
            'post_type' => $type,
            'meta_query' =>
            array(
                array(
                    'key' => 'post_city_id',
                    'value' => $city_id,
                    'compare' => '=',
                ),
            ),
        );

        $my_query = new WP_Query($args);
        $response = new WP_JSON_Response();
		
		while($my_query->have_posts()){
			global $post;
			$my_query->the_post();
			$address = str_replace($srcharr,$replarr,(get_post_meta($ID,'address',true)));
			$contact = str_replace($srcharr,$replarr,(get_post_meta($ID,'phone',true)));
			$data[] = apply_filters('tmpl_get_fields',array('id'=>$post->ID,'title'=>str_replace($title_srcharr,$title_replarr,$post->post_title),'address'=>$address, 'contact' => $contact));
		}
        $response->set_data($data);

        return $response;
    }

    /* This function return filter posts whose city, post type, taxonomt and category id pass as filter */

    public function get_city_type_category($slug, $type, $taxonomy, $category_id) {
        if (empty($slug) || empty($type) || empty($taxonomy) || empty($category_id)) {
            return new WP_Error('json_post_invalid_id', __('Invalid slug or post type or taxonomy or categry id.'), array('status' => 404));
        }

        $city_id = $this->get_city_id($slug);
        if ($city_id == '')
            return new WP_Error('json_post_invalid_id', __('Invalid city slug.'), array('status' => 404));

        $args = array(
            'post_type' => $type,
            'tax_query' => array(
                array(
                    'taxonomy' => "$taxonomy",
                    'field' => 'id',
                    'terms' => $category_id
                )
            ),
            'meta_query' =>
            array(
                array(
                    'key' => 'post_city_id',
                    'value' => $city_id,
                    'compare' => '=',
                ),
            ),
        );
        $my_query = new WP_Query($args);

        $response = new WP_JSON_Response();
        $response->set_data($my_query->posts);

        return $response;
    }

    /* This function return filter posts whose city, post type, taxonomy and category slug pass as filter */

    public function get_city_type_category_name($slug, $type, $taxonomy, $category_name) {
        if (empty($slug) || empty($type) || empty($taxonomy) || empty($category_name)) {
            return new WP_Error('json_post_invalid_id', __('Invalid slug or post type or taxonomy or categry id.'), array('status' => 404));
        }

        $city_id = $this->get_city_id($slug);
        if ($city_id == '')
            return new WP_Error('json_post_invalid_id', __('Invalid city slug.'), array('status' => 404));

        $args = array(
            'post_type' => $type,
            'tax_query' =>
            array(
                array(
                    'taxonomy' => "$taxonomy",
                    'field' => 'slug',
                    'terms' => "$category_name",
                )
            ),
            'meta_query' =>
            array(
                array(
                    'key' => 'post_city_id',
                    'value' => $city_id,
                    'compare' => '=',
                ),
            ),
        );

        $my_query = new WP_Query($args);
        $response = new WP_JSON_Response();
        $response->set_data($my_query->posts);

        return $response;
    }

    /* This function return filter posts whose city, post type, taxonomt and tag slug pass as filter */

    public function get_city_type_tag_name($slug, $type, $taxonomy, $tag_name) {
        if (empty($slug) || empty($type) || empty($taxonomy) || empty($tag_name)) {
            return new WP_Error('json_post_invalid_id', __('Invalid slug or post type or taxonomy or categry id.'), array('status' => 404));
        }

        $city_id = $this->get_city_id($slug);
        if ($city_id == '')
            return new WP_Error('json_post_invalid_id', __('Invalid city slug.'), array('status' => 404));

        $args = array(
            'post_type' => $type,
            'meta_query' =>
            array(
                array(
                    'key' => 'post_city_id',
                    'value' => $city_id,
                    'compare' => '=',
                ),
            ),
            'tax_query' =>
            array(
                array(
                    'taxonomy' => "$taxonomy",
                    'field' => 'slug',
                    'terms' => "$tag_name",
                )
            )
        );

        $my_query = new WP_Query($args);
        $response = new WP_JSON_Response();
        $response->set_data($my_query->posts);

        return $response;
    }

    /*  Return city id from city slug 
     *  $multi_city is contain city slug
     */

    public function get_city_id($multi_city) {

        global $wpdb, $multicity_table;
        $multicity_table = $wpdb->prefix . "multicity";
        $sql = "SELECT * FROM $multicity_table where city_slug='" . $multi_city . "'";
        $default_city = $wpdb->get_results($sql);
        $default_city_id = $default_city[0]->city_id;
        return $default_city_id;
    }
	
	private function get_city_cats($cityid){
		global $wpdb, $multicity_table;
        $multicity_table = $wpdb->prefix . "multicity";
        $sql = "SELECT categories FROM $multicity_table where city_id='" . $cityid . "'";
        $cats = $wpdb->get_var($sql);
        return $cats;
	}
	/* get the category of city */
	
	public function get_city_terms($slug,$taxonomy,$hideempty,$filter = array()){
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'json_taxonomy_invalid_id', __( 'Invalid taxonomy ID.' ), array( 'status' => 404 ) );
		}
		
		$cityid = $this->get_city_id($slug);
		$citycats = $this->get_city_cats($cityid);
		$citycats_array = explode(',',$citycats);
		if($hideempty==0){ $hideempty = false; }else{ $hideempty = true; }
		$args = array(
			 'order' => 'name',
			 'hide_empty' => $hideempty,
			 'include' => $citycats_array ,
			 'order' => 'ASC',
			  'show_count' => 0,
			  'pad_counts' => true,
		);
		
		// Allow args in get_terms function. This is a partial list and does not include hide_empty and cache_domain.  
		$valid_vars = array(
			'orderby',
			'order',
			'exclude',
			'exclude_tree',
			'include',
			'number',
			'fields',
			'slug',
			'parent',
			'hierarchical',
			'child_of',
			'get',
			'name__like',
			'description__like',
			'pad_counts',
			'offset',
			'search',
		);
		
		foreach ( $valid_vars as $var ) {
			if ( isset( $filter[ $var ] ) ) {
				$args[ $var ] = apply_filters( 'json_tax_query_var-' . $var, $filter[ $var ] );
			}
		}
		$terms = get_terms( $taxonomy, $args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		return $terms;
	}
	
	public function get_child_cats_with_count($city,$type,$taxonomy,$term_id,$hideempty,$catlavel,$numberofcats,$lang){
			$city_id = $this->get_city_id($city);
			if($hideempty == 0) { $hideempty = false; }else{ $hideempty = true ; }
			
			$transient_name = (!empty($current_cityinfo)) ? $current_cityinfo['city_slug'] : '';
			delete_transient('_tevolution_query_catwidget' . $term_id . $type . $transient_name . $lang);
			if (get_option('tevolution_cache_disable') == 1 && false === ( $featured_catlist_list = get_transient('_tevolution_query_catwidget' . $term_id . $type . $transient_name . $lang) )) {
			   do_action('tevolution_category_query');
			   $featured_catlist_list = wp_list_categories('title_li=&child_of=' . $term_id . '&echo=0&depth=' . $catlavel . '&number=' . $numberofcats . '&taxonomy=' . $taxonomy . '&show_count=1&hide_empty=' . $hideempty . '&pad_counts=0&show_option_none=1');
			  set_transient('_tevolution_query_catwidget' . $term_id. $type . $transient_name . $lang, $featured_catlist_list, 12 * HOUR_IN_SECONDS);
			} elseif (get_option('tevolution_cache_disable') == '') {
			   do_action('tevolution_category_query');
			   $featured_catlist_list = wp_list_categories('title_li=&child_of=' . $term_id . '&echo=0&depth=' . $catlavel . '&number=' . $numberofcats . '&taxonomy=' . $taxonomy . '&show_count=1&hide_empty=' . $hideempty . '&pad_counts=0&show_option_none=1');
			}
			update_option($taxonomy."_".$term_id,$featured_catlist_list);
			return $featured_catlist_list;
	}
}
