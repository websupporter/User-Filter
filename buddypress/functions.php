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
			'textbox'        => 'string',
			'number'         => 'number',
			'checkbox'       => 'xprofile-serialized',
			'selectbox'      => 'string',
			'radio'          => 'string',
			'multiselectbox' => 'xprofile-serialized',
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
	 * Returns whether a field is a field which saves data serialized
	 * @since 1.0
	 *
	 * @param (int) $id The field ID
	 *
	 * @return (boolean)
	 **/
	function auf_xprofile_fielddata_is_serialized( $id ) {
		global $wpdb;
		$serialized_types = array( 'checkbox', 'multiselectbox' );
		$table = $wpdb->prefix . 'bp_xprofile_fields';
		$sql = $wpdb->prepare(
			'select type from ' . $table . ' where id = %d',
			$id
		);		
		$field = $wpdb->get_col( $sql );
		if ( empty( $field[0] ) ) {
			return false;
		}

		$has_options = in_array( $field[0], $serialized_types );
		return $has_options;
	}

	/**
	 * Returns whether a field has options
	 * @since 1.0
	 *
	 * @param (int) $id The field ID
	 *
	 * @return (boolean)
	 **/
	function auf_xprofile_field_has_options( $id ) {
		global $wpdb;
		$types_with_options = array( 'checkbox', 'multiselectbox', 'radio', 'selectbox' );
		$table = $wpdb->prefix . 'bp_xprofile_fields';
		$sql = $wpdb->prepare(
			'select type from ' . $table . ' where id = %d',
			$id
		);		
		$field = $wpdb->get_col( $sql );
		if ( empty( $field[0] ) ) {
			return false;
		}

		$has_options = in_array( $field[0], $types_with_options );
		return $has_options;
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
		
		//We need to identify the field in order to determine where to get the data from.
		//Checkboxes - data is saved as option with the parent_id = $id
		//Textfields - get the data from the bp_xprofile_data
		if ( auf_xprofile_field_has_options( $id ) ) {
			$table = $wpdb->prefix . 'bp_xprofile_fields';
			$sql = $wpdb->prepare(
				'select * from ' . $table . ' where parent_id = %d && type="option"',
				$id
			);
			$results = $wpdb->get_results( $sql );
			$values = array();
			foreach ( $results as $result ) {
				$values[] = $result->name;
			}
		} else {
			$table = $wpdb->prefix . 'bp_xprofile_data';
			$sql = $wpdb->prepare(
				'select value from ' . $table . ' where field_id = %d group by value',
				$id
			);
			$values = $wpdb->get_col( $sql );
		}
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