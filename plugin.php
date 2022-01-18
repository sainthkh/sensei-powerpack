<?php
/**
 * @package Sensei-PowerPack
 * @version 1.0.0
 */
/*
Plugin Name: Sensei PowerPack
Plugin URI: 
Description: Sensei PowerPack is a plugin that adds features to Sensei.
Author: sainthkh
Version: 1.0.0
Author URI: 
*/

require_once 'includes/woocommerce.php';
require_once 'includes/restrict-content.php';

// Add Course section to the product page.

add_filter('woocommerce_product_data_tabs', function($tabs) {
	$tabs['additional_info'] = [
		'label' => __('Course', 'sensei-powerpack'),
		'target' => 'spp_course_data',
		'class' => ['hide_if_external'],
		'priority' => 25
	];
	return $tabs;
});

add_action('woocommerce_product_data_panels', function() { ?>
	<div id="spp_course_data" class="panel woocommerce_options_panel hidden"><?php
 		woocommerce_wp_text_input([
			'id' => 'spp_course_id',
			'label' => __('Course ID', 'sensei-powerpack'),
			'wrapper_class' => 'show_if_simple',
		]);
	?></div><?php
});

add_action('woocommerce_process_product_meta', function($post_id) {
	$product = wc_get_product($post_id);
	
	$product->update_meta_data('spp_course_id', sanitize_text_field($_POST['spp_course_id']));
 	
	$product->save();
});

// Show course ID on the courses admin page.

add_filter('post_row_actions', function($actions, $post) {
	if ($post->post_type === 'course') {
		return array_merge( array( 'id' => sprintf( __( 'ID: %d', 'sensei-powerpack' ), $post->ID ) ), $actions );
	}

	return $actions;
}, 100, 2);
