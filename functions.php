<?php
	/**
	 * Get the filter settings by ID
	 * @since 1.0
	 *
	 * @param (string) $id The ID of the filter
	 * 
	 * @return (array|WP_Error) $filter The filter settings or an WP_Error if the filter was not found.
	 **/
	function auf_get_filter_settings( $id ) {
		$all_filters = get_option( 'auf-filters', array() );
		if ( empty( $all_filters[ $id ] ) ) {
			return new WP_Error( 'filter-not-found', sprintf( __( 'The filter "%s" was not found.', 'auf' ), $id ) );
		}

		return $all_filters[ $id ];
	}

	/**
	 * Get a filter by ID
	 * @since 1.0
	 *
	 * @param (string) $id The ID of the filter
	 * 
	 * @return (Filter object|WP_Error) $filter The filter or an WP_Error if the filter was not found.
	 **/
	function auf_get_filter( $id ) {
		$filter = new AUF_FILTER();
		$success = $filter->init( $id );
		if ( is_wp_error( $success ) ) {
			return $success;
		}

		return $filter;
	}

	/**
	 * Retrieves all registered elements
	 * @since 1.0
	 *
	 * @return (array) $elements
	 **/
	function auf_get_registered_elements() {
		$elements = array();
		return apply_filters( 'auf::elements::get', $elements );
	}

	/**
	 * Get an element by ID
	 * @since 1.0
	 *
	 * @param (string) ID the ID of the registered element
	 *
	 * @return (element|WP_Error)
	 **/
	function auf_get_registered_element_by_id( $id ) {
		$elements = auf_get_registered_elements();
		foreach ( $elements as $key => $val ) {
			if ( $val->ID == $id ) {
				return $val;
			}
		}
		return new WP_Error( 'element-not-found', sprintf( __( 'The element "%s" was not found.', 'auf' ), $id ) );
	}

	/**
	 * Get all available sources for the filter
	 *
	 * @since 1.0
	 *
	 * @return (array) $sources
	 **/
	function auf_sources() {
		$sources = array();
		$sources['meta'] = array(
			'ID'      => 'meta',
			'label'   => __( 'Custom fields', 'auf' ),
			'values'  => auf_get_all_usermeta(),
		);

		/**
		 * Filters the available sources for a filter
		 * @since 1.0
		 *
		 * @param (array) $sources The sources array
		 *
		 * @return (array) $sources
		 **/
		return apply_filters( 'auf::sources::all', $sources );
	}

	/**
	 * Get all avaliable user custom fields
	 * @since 1.0
	 *
	 * @return (array) $keys
	 **/
	function auf_get_all_usermeta() {
		global $wpdb;		
		
		$keys = array();
		$sql = '
		SELECT 
			meta_key, 
			meta_value
		FROM 
			' . $wpdb->usermeta . '
		GROUP BY 
			meta_key
		';
		$data = $wpdb->get_results( $sql );
		foreach ( $data as $key => $val ) {
			$meta_value = maybe_serialize( $val->meta_value );
			$meta_key = $val->meta_key;
			$add_key = false;
			if ( is_string( $meta_value ) || is_numeric( $meta_value ) && ! is_protected_meta( $meta_key ) ) {
				$add_key = true;
			}

			/**
			 * Filters whether a meta key should be added to the list of available meta keys or not
			 * @since 1.0
			 *
			 * @param (boolean) $add_key the boolean for decision
			 * @param (string)  $meta_key the key in question
			 * @param (mixed)   $meta_value a meta value saved with this key
			 *
			 * @return (boolean) $add_key
			 **/
			$add_key = apply_filters( 'auf::sources::meta::add_key', $add_key, $meta_key, $meta_value );

			if ( $add_key ) {

				//Set a default label for every meta key
				$label = explode( '_', $meta_key );
				foreach ( $label as $key => $label_string ) {
					$label[ $key ] = ucfirst( $label_string );
				}
				$label = implode( ' ', $label );
				$label = explode( '-', $label );
				foreach ( $label as $key => $label_string ) {
					$label[ $key ] = ucfirst( $label_string );
				}
				$label = implode( ' ', $label );

				//Set the type. Default is string
				$type = 'string';
				if ( is_numeric( $meta_value ) ) {
					$type = 'number';
				}

				$key = array( 
					'ID'    => $meta_key,
					'label' => $label,
					'type'  => $type,
				);

				/**
			 	* Filters the key information of a single key
			 	* @since 1.0
			 	*
			 	* @param (array) $key the key information
			 	* @param (mixed) $meta_value a meta value saved with this key
			 	*
			 	* @return (array) $key
			 	**/
				$keys[] = apply_filters( 'auf::sources::meta::key::' . $meta_key, $key, $meta_value );
			}
		}

		/**
		 * Filters all available meta keys
		 * @since 1.0
		 *
		 * @param (array) $keys All available meta keys
		 *
		 * @return (array) $keys
		 **/
		return apply_filters( 'auf::sources::meta::allkeys', $keys );
	}

	/**
	 * Save a filter
	 * @since 1.0
	 *
	 * @param (array) $post The filter to save. Basically its the $_POST array
	 *
	 * @return (boolean|WP_Error) Returns `true` on success or an WP_Error
	 **/

	function auf_save_filter( $data ) {

		$standard_fields = array( 
			'key', 
			'label', 
			'source', 
		);

		if ( empty( $data['auf'] ) ) {
				new WP_Error( 'required-field-missing', sprintf( __( 'The required field "%s" is missing.', 'auf' ), 'auf' ) );
		}

		$filter_raw = $data['auf'];
		foreach ( $standard_fields as $field ) {
			if ( ! is_array( $filter_raw[ $field ] ) || count( $filter_raw[ $field ] ) != count( $filter_raw[ 'key' ] ) ) {
				new WP_Error( 'required-field-missing', sprintf( __( 'The required field "%s" is missing or not complete.', 'auf' ), $field ) );
			}
		}

		$save_filter = array();

		//General Filter settings
		$save_filter['ID'] = sanitize_text_field( $data['ID'] );
		$save_filter['name'] = sanitize_text_field( $data['name'] );


		/*
			The index of a modul can vary from the general element_index, 
			because we can use different modules for each element.

			We use $element_indexes to keep track of differences
		*/
		$element_indexes = array();

		//Loop through the moduls.
		foreach ( $filter_raw['key'] as $modul_index => $element ) {
			$element = auf_get_registered_element_by_id( $element );
			if ( is_wp_error( $element ) ) {
				wp_die( $element->get_error_message() );
			}

			$modul['element'] = $element->ID;
			$modul['label']   = sanitize_text_field( $filter_raw['label'][ $modul_index ] );
			$modul['source']  = sanitize_text_field( $filter_raw['source'][ $modul_index ] );

			if ( ! empty( $filter_raw[ $element->ID ] ) ) {
				if( ! isset( $element_indexes[ $element->ID] ) )
					$element_indexes[ $element->ID] = 0;

				$element_specific_settings = $filter_raw[ $element->ID ];

				$element_modul_specific_settings = array();
				foreach ( $element_specific_settings as $name => $values ) {
					if ( ! is_array( $values ) || empty( $values[ $element_indexes[ $element->ID ] ] ) )
						continue;
					$element_modul_specific_settings[ $name ] = $values[ $element_indexes[ $element->ID ] ];
				}
			
				/**
			 	* Filters the element specific settings
			 	* @since 1.0
			 	*
			 	* @param (array) $element_specific_settings The element specific POST data
			 	*
			 	* @return(array|WP_Error) $element_specific_settings
			 	**/
				$modul[ $element->ID ] = apply_filters( 'auf::filter::save::element::' . $element->ID, $element_modul_specific_settings );
				if ( is_wp_error( $modul[ $element->ID ] ) ) {
					wp_die( $modul[ $element->ID ]->get_error_message() );
				}

				$element_indexes[ $element->ID]++;
			}

			$save_filter['moduls'][ $modul_index ] = $modul;
		}

		/**
		 * Fires before the filter has been saved
		 * @since 1.0
		 *
		 * @param (array) $save_filter The filter, which will be saved.
		 **/
		do_action( 'auf::filter::save::before', $save_filter );

		$all_filters = get_option( 'auf-filters', array() );

		/**
		 * Filters the single filter before it gets saved.
		 * @since 1.0
		 *
		 * @param (array) $save_filter The filter, which will be saved.
		 * @param (array) $filter_raw  The raw filter data.
		 *
		 * @return (array) $save_filter
		 **/
		$all_filters[ $save_filter['ID'] ] = apply_filters( 'auf::filter::save', $save_filter, $filter_raw );
		update_option( 'auf-filters', $all_filters );

		/**
		 * Fires after the filter has been saved
		 * @since 1.0
		 *
		 * @param (array) $save_filter The saved filter.
		 **/
		do_action( 'auf::filter::save::after', $save_filter );

		return true;
	}

	/**
	 * Return the filter method of the current filter
	 * @since 1.0
	 *
	 * @return (string) the method
	 **/
	function auf_get_the_filter_method() {
		global $auf;
		return $auf->get_method();
	}


	/**
	 * Return the form action of the current filter
	 * @since 1.0
	 *
	 * @return (string) the action
	 **/
	function auf_get_the_filter_action() {
		global $auf;
		return $auf->get_action();
	}

	/**
	 * Returns if the current filter has moduls
	 * @since 1.0
	 *
	 * @return (boolean) 
	 **/
	function auf_filter_has_moduls() {
		global $auf;
		return $auf->has_moduls();
	}

	/**
	 * Returns if a search has been performed
	 * @since 1.0
	 * 
	 * @return (boolean)
	 **/
	function auf_search_performed() {
		global $auf;
		return $auf->did_search;
	}

	/**
	 * Returns if the current filter has results
	 * @since 1.0
	 *
	 * @return (boolean) 
	 **/
	function auf_filter_has_results() {
		global $auf;
		return $auf->has_results();
	}

	/**
	 * Sets the next modul
	 * @since 1.0
	 *
	 * @return (void) 
	 **/
	function auf_the_modul() {
		global $auf;
		$auf->the_modul();
	}

	/**
	 * Sets the next result
	 * @since 1.0
	 *
	 * @return (void) 
	 **/
	function auf_the_result() {
		global $auf;
		$auf->the_result();
	}

	/**
	 * Sets the ID of the current modul
	 * @since 1.0
	 *
	 * @return (integer) The ID 
	 **/
	function auf_get_the_modul_id() {
		global $auf;
		return $auf->get_the_modul_id();
	}

	/**
	 * Get the label of the current modul
	 * @since 1.0
	 *
	 * @return (string) The label 
	 **/
	function auf_get_the_label() {
		global $auf;
		return $auf->get_the_label();
	}

	/**
	 * Get the element of the current modul
	 * @since 1.0
	 *
	 * @return (html) The HTML element 
	 **/
	function auf_get_the_element() {
		global $auf;
		return $auf->get_the_element();
	}


	/**
	 * Get the elements name of the current modul
	 * @since 1.0
	 * 
	 * @param (boolean) $is_array whether the element is for arrays or not (Default: `false`)
	 *
	 * @return (string) The name 
	 **/
	function auf_get_the_elements_name( $is_array = false ) {
		$current_id = auf_get_the_modul_id();
		$prefix = 'auf[';
		$postfix = ']';

		if ( $is_array ) {
			$postfix .= '[]';
		}
		return $prefix . $current_id . $postfix;
	}


	/**
	 * Get the elements ID of the current modul
	 * @since 1.0
	 * 
	 * @return (string) The ID 
	 **/
	function auf_get_the_elements_id() {		
		$current_id = auf_get_the_modul_id();
		$prefix = 'auf-element-';

		return $prefix . $current_id;
	}

	/**
	 * Initializes the <form> for the filter
	 *
	 * @since 1.0
	 *
	 * @return (void)
	 **/
	function auf_init_form() {
		?>
		<input type="hidden" name="auf-search" value="<?php echo esc_attr( auf_get_the_filter_id() ); ?>" />
		<?php
	}


	/**
	 * Returns the ID of the current filter
	 *
	 * @since 1.0
	 *
	 * @return (string) The ID of the current filter.
	 **/
	function auf_get_the_filter_id() {
		global $auf;
		return $auf->get_the_filter_id();
	}

	/**
	 * Renders HTML based on a template file. 
	 * The template file is searched in the order child-theme > theme > plugin
	 * @since 1.0
	 *
	 * @param (string) template name
	 *
	 * @return (boolean|WP_Error) returns `true` when template was found or a WP_Error object.
	 **/
	function auf_get_template( $template ) {

		//Search the child theme for the template file
		$template_file = get_stylesheet_directory() . '/advanced-user-filter/' . $template . '.php';
		if( file_exists( $template_file ) ) {
			require $template_file;
			return true;
		}

		//Search the theme for the template file
		$template_file = get_template_directory() . '/advanced-user-filter/' . $template . '.php';
		if( get_template_directory() != get_stylesheet_directory() && file_exists( $template_file ) ) {
			require $template_file;
			return true;
		}

		//Search the plugin for the template file
		$template_file = __AUF_PATH__ . '/templates/' . $template . '.php';
		if( file_exists( $template_file ) ) {
			require $template_file;
			return true;
		}

		return new WP_Error( 'template-not-found', sprintf( __( 'The template "%s" was not found.', 'auf' ), $template ) );
	}
	/**
	 * Returns the current result
	 * @since 1.0
	 *
	 * @return (WP_User|WP_Error) The WP_User object of the current result or a WP_Error object if no result present.
	 **/
	function auf_get_current_result() {
		global $auf;
		if( empty( $auf->current_result ) )
			return new WP_Error( 'no-result', __( 'No result found.', 'auf' ) );

		return $auf->current_result;
	}