<?php
/**
 * BuddyPress specific functions
 * @since 1.0
 **/


	/**
	 * Get all available xprofile fields
	 * @since 1.0
	 *
	 * @return (array) $roles
	 **/
	function auf_get_all_xprofile_fields() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			return array();
		}
		global $wpdb;
		$table = $wpdb->prefix . 'bp_xprofile_fields';

		$sql = 'select id,name,type from ' . $table;
		$results = $wpdb->get_results( $sql );

		$map = array(
			'textbox' => 'string',
			'number'  => 'number',
		);

		$xprofile_fields = array();
		foreach ( $results as $result ) {

			if ( empty( $map[ $result->type ] ) ) {
				continue;
			}

			$field = array( 
				'ID'    => $result->id,
				'label' => $result->name,
				'type'  => $map[ $result->type ]
			);

			$xprofile_fields[] = $field;
		}

		/**
		 * Filters all xprofile fields
		 * @since 1.0
		 *
		 * @param (array) $xprofile_fields All available fields
		 *
		 * @return (array) $xprofile_fields
		 **/
		return apply_filters( 'auf::sources::xprofile_fields', $xprofile_fields );
	}

	/**
	 * Get all available xprofile data for a field
	 * @since 1.0
	 *
	 * @param (int) $field_id The ID of the field
	 *
	 * @return (array) $values The values
	 **/
	function auf_get_all_xprofile_field_data( $id ) {
		if ( empty( $id ) || ! bp_is_active( 'xprofile' ) ) {
			return array();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bp_xprofile_data';

		$sql = $wpdb->prepare(
			'select value from ' . $table . ' where field_id = %d group by value',
			$id
		);
		$values = $wpdb->get_col( $sql );

		/**
		 * Filters all xprofile fields
		 * @since 1.0
		 *
		 * @param (array) $values All available data
		 *
		 * @return (array) $values
		 **/
		return apply_filters( 'auf::xprofile::data', $values );
	}