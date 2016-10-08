<?php
namespace Post_Expiration\inc;

defined( 'ABSPATH' ) or die( 'File cannot be accessed directly' );

class CDevroe_Datepicker {

	public static function init() {

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'wp_admin_plugin_scripts' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'wp_admin_vendor_scripts' ) );
	}


	public static function wp_admin_plugin_scripts() {
			global $pagenow;

		if ( is_admin() && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
			// Only load these scripts on the post editor
			wp_register_style( 'scheduled-post-datepicker', plugins_url( 'css/scheduled-posts-datepicker.css',  __FILE__ ) );
			wp_enqueue_style( 'scheduled-post-datepicker' );

			wp_enqueue_script( 'datepicker', plugins_url( 'js/scheduled-posts-datepicker.js',  __FILE__ ), array( 'jquery' ) );
		}
	}


	public static function wp_admin_vendor_scripts() {
			global $pagenow;

		if ( is_admin() && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
			wp_register_style( 'lib-datepicker', plugins_url( 'lib/datepicker/css/datepicker.css',  __FILE__ ), false, '1.0.0' );
			wp_enqueue_style( 'lib-datepicker' );

			wp_enqueue_script( 'eye', plugins_url( 'lib/datepicker/js/eye.js',  __FILE__ ), array( 'lib-datepicker' ) );
			wp_enqueue_script( 'utils', plugins_url( 'lib/datepicker/js/utils.js',  __FILE__ ), array( 'lib-datepicker' ) );
			wp_enqueue_script( 'layout', plugins_url( 'lib/datepicker/js/layout.js',  __FILE__ ), array( 'lib-datepicker' ) );
			wp_enqueue_script( 'lib-datepicker', plugins_url( 'lib/datepicker/js/datepicker.js',  __FILE__ ), array( 'jquery' ) );

			// Pretty much the Plugin
			// wp_enqueue_script( 'scheduled_post_date_picker_plugin_js', plugins_url() . '/scheduled-posts-date-picker/scheduled-posts-date-picker.js', array( 'scheduled_post_date_picker_layout_js' ), '0.2.1' );
		}
	}
}
