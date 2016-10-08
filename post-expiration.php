<?php
/*
Plugin Name: Post Expiration
Description: Wordpress enhancements specific to this single site.
Version: 1.3
Author: ptb
Author URI: http://paul.barthmaier.rocks
*/
namespace Post_Expiration;

defined( 'ABSPATH' ) or die( 'File cannot be accessed directly' );


// Autoloader will let us call classes directly rather than requiring the files first
require_once( 'autoload.php' );


// Set code to run on activation and deactivation
function activate() {
	Activator::activate();
}
function deactivate() {
	Deactivator::deactivate();
}
register_activation_hook( __FILE__, '\\' . __NAMESPACE__ . '\\activate' );
register_deactivation_hook( __FILE__, '\\' . __NAMESPACE__ . '\\deactivate' );


inc\Add_Post_Expiration::init();
inc\CDevroe_Datepicker::init();
