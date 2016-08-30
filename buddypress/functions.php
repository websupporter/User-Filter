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
			'number'         => 'number',
			'textbox'        => 'string',
			'textarea'       => 'string',
			'selectbox'      => 'string',
			'radio'          => 'string',
			'url'            => 'string',
			'datebox'        => 'date',
			'multiselectbox' => 'xprofile-serialized',
			'checkbox'       => 'xprofile-serialized',
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
		if ( ! bp_is_active( 'xprofile' ) ) {
			return false;
		}

		$serialized_types = array( 'checkbox', 'multiselectbox' );
		$field = xprofile_get_field( $id );
		
		if ( null === $field ) {
			return false;
		}		

		$is_serialized = in_array( $field->type, $serialized_types );
		return $is_serialized;
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
		if ( ! bp_is_active( 'xprofile' ) ) {
			return false;
		}

		$types_with_options = array( 'radio', 'selectbox', 'checkbox', 'multiselectbox' );
		$field = xprofile_get_field( $id );		
		if ( null === $field ) {
			return false;
		}		

		$has_options = in_array( $field->type, $types_with_options );
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