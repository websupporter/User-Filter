<?php
	
	/**
	 * Load admin styles and scripts
	 * @since 1.0
	 **/
	add_action( 'admin_enqueue_scripts', 'auf_admin_enqueue_scripts' );
	function auf_admin_enqueue_scripts( $hook ) {
		if ( $hook != 'settings_page_auf-index' ) {
			return;
		}

		wp_enqueue_script( 'auf-admin-script', __AUF_URL__ . 'admin/script.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable' ) );

		$elements = auf_get_registered_elements();
		$data = array();
		foreach ( $elements as $element ) {
			$data[ $element->ID ] = $element->get_element_data();
		}
		wp_localize_script( 'auf-admin-script', 'aufElements', $data );

		if ( ! empty( $_GET['ID'] ) ) {
			wp_localize_script( 'auf-admin-script', 'aufSources', auf_sources() );
			do_action( 'auf::elements::enqueue_admin_scripts' );
		}
		wp_enqueue_style( 'auf-admin-style', __AUF_URL__ . 'admin/style.css' );
	}

	/**
	 * Load the admin menu
	 * @since 1.0
	 **/
	add_action( 'admin_menu', 'wp_sf_adminpage' );
	function wp_sf_adminpage() {
		add_submenu_page( 'options-general.php', __( 'User Filter', 'auf' ), __( 'User Filter', 'auf' ), 'manage_options', 'auf-index', 'auf_admin_output_index' );
	}

	/**
	 * Render the admin pages
	 * @since 1.0
	 **/
	function auf_admin_output_index() {		
		if ( ! empty( $_GET['ID'] ) ) {
			require_once( dirname( __FILE__ ) . '/single.php' );
		} else {
			require_once( dirname( __FILE__ ) . '/index.php' );
		}
	}

	/**
	 * Save the filter data
	 * @since 1.0
	 **/
	add_action( 'admin_init', 'auf_admin_init' );
	function auf_admin_init() {

		if ( ! isset( $_POST['auf-action'] ) || $_POST['auf-action'] != 'save-filter' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You are not allowed to edit user filters.', 'auf' ) );
		}

		if ( empty( $_POST['ID'] ) || ! wp_verify_nonce( $_POST['auf-nonce'], 'filter-' . $_POST['ID'] ) ) {
			wp_die( __( 'You are not allowed to edit user filters.', 'auf' ) );
		}

		$success = auf_save_filter( $_POST );

		if ( is_wp_error( $success ) ) {
			wp_die( $success->get_error_message() );
		}

		$url = add_query_arg( array( 'updated' => 1 ) );
		wp_redirect( $url );

	}