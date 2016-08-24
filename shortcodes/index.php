<?php
	/**
	 * Register all the shortcodes
	 **/



	/**
	 * Register the user_filter shortcode
	 **/
	add_shortcode( 'user_filter', 'auf_shortcode_user_filter' );
	function auf_shortcode_user_filter( $attr, $content ) {
		if ( empty( $attr['id'] ) ) {
			return __( 'Please specifiy the filter to display.', 'auf' );
		}

		$filter = auf_get_filter( $attr['id'] );
		if ( is_wp_error( $filter ) ) {
			return $filter->get_error_message();
		}

		ob_start();
		$success = auf_get_template( 'filter' );
		$content = ob_get_contents();
		ob_end_clean();
		if ( is_wp_error( $success ) ) {
			$content = $success->get_error_message();
		}
		return $content;
	}