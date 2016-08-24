<?php
	/**
	 * Plugin Name: Advanced User Filter
	 * Author: David Remer / websupporter
	 **/

	define( '__AUF_PATH__', dirname( __FILE__ ) . '/' );
	define( '__AUF_URL__', plugins_url( '/', __FILE__ ) );

	/**
	 * Initialize the plugin
	 **/
	add_action( 'plugins_loaded', 'auf_init' );
	function auf_init() {
		
		if ( is_admin() ) {
			require_once( __AUF_PATH__ . 'admin/admin.php' );
		}

		do_action( 'auf_init' );
	}

	//Load basic functions
	require_once( __AUF_PATH__ . 'functions.php' );

	//Load the filter class
	require_once( __AUF_PATH__ . 'classes/filter.class.php' );

	//Load the module mainframe
	require_once( __AUF_PATH__ . 'modules/main.php' );
	
	//Load the standard modules
	require_once( __AUF_PATH__ . 'modules/index.php' );
	
	//Load the standard modules
	require_once( __AUF_PATH__ . 'shortcodes/index.php' );
	