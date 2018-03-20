<?php
/**
Plugin Name: W3 Votify
Version: 1.0.3
Author: W3Extensions
Description: Add upvote/downvote buttons to your WordPress posts.
Contributors: bookbinder
Tags: social bookmarking, ajax, voting, points, rating, trending, upvote, downvote
Requires at least: 4.7
Tested up to: 5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once("inc/vote.php");

function w3vx_activation(){
	if(function_exists("w3vx_create_user_votes_table")){
		w3vx_create_user_votes_table();
	}
}

register_activation_hook( __FILE__, 'w3vx_activation' );



function w3vx_get_plugin_directory(){
	$directory = array();
	
	$directory['path'] = trailingslashit( plugin_dir_path( __FILE__ ) );
	$directory['url'] = plugin_dir_url( __FILE__ );
	return $directory;
}

function w3vx_scripts() {
	$pluginDirectory = w3vx_get_plugin_directory();
	
	#CSS
	wp_register_style( 'w3vx',  $pluginDirectory['url'].'assets/css/app.css', array(), 1 );
	wp_enqueue_style('w3vx');
	
	#JS
	wp_register_script( "w3vx", $pluginDirectory['url'].'assets/js/app.js', array("jquery"), null, true );

	wp_enqueue_script('jquery');
	wp_enqueue_script('w3vx');

	wp_localize_script( 'w3vx', 'w3vx_ajax', array(
		'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php',
	) );	
}

add_action('wp_enqueue_scripts', 'w3vx_scripts', 100);
