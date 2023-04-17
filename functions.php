<?php 
	 add_action( 'wp_enqueue_scripts', 'fitness_figure_enqueue_styles' );
	 function fitness_figure_enqueue_styles() {
 		  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); 
 		  } 
 ?>