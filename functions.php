<?php 

require_once get_stylesheet_directory() . '/includes/user-roles.php';
require_once get_stylesheet_directory() . '/includes/register-form.php';
require_once get_stylesheet_directory() . '/includes/login-form.php';
require_once get_stylesheet_directory() . '/includes/registration-handler.php';

	 add_action( 'wp_enqueue_scripts', 'fitness_figure_enqueue_styles' );
	 function fitness_figure_enqueue_styles() {
 		  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); 
 		  } 
 ?>