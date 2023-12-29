<?php

namespace DentalAdvocacyCore\Core\PostTypes;

class Vital {
	
	public function __construct()
	{
		add_action( 'init', [$this, 'post_type_init'] );
		add_filter('manage_da-core-vitals_posts_columns', [$this, 'only_show_title'], 10, 1);
		add_filter( 'post_row_actions', [$this, 'remove_row_actions_post'], 10, 2 );
		add_action('wp_trash_post', [$this, 'restrict_post_deletion'] );
	}
	
	
	public function post_type_init()
	{
		$labels = array(
			'name'                  => _x( 'Vitals', 'Post type general name', 'dental-advocacy-core' ),
			'singular_name'         => _x( 'Vital', 'Post type singular name', 'dental-advocacy-core' ),
			'add_new'               =>     __('Add New Vital', 'dental-advocacy-core'),
			'add_new_item'          => __('Add New Vital', 'dental-advocacy-core'),
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=da-core-vitals',
			'has_archive'        => true,
			'supports'           => array( 'title'),
		);
		
		register_post_type( 'da-core-vitals', $args );
	}
	
	public function only_show_title( $defaults ) {
		$new_array['title'] = 'Vital Title';
		return $new_array;
	}
	
	public function remove_row_actions_post( $actions, $post )
	{
		if($post->post_type == 'da-core-vitals') {
			unset( $actions['clone'] );
			unset( $actions['trash'] );
		}
		
		return $actions;
	}
	
	public function restrict_post_deletion($post_id)
	{
		if( get_post_type($post_id) === 'da-core-vitals' ) {
			wp_die('The post you were trying to delete is protected.');
		}
	}
	
	/**
	 * @return \DentalAdvocacyCore\Core\PostTypes\Vital|null
	 */
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
	
}