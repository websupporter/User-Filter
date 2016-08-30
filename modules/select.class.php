<?php


	class AUF_SELECT extends AUF_FILTER_ELEMENTS {

		public $name       = 'Select Box';
		public $ID         = 'selectbox';
		public $icon       = 'user-search/assetts/select.png';
		public $icon_small = 'user-search/assetts/select-small.png';
		public $sources    = array(
				                    'meta',
				                    'roles',
				                    'xprofile',
			                 );
		public $types      = array(
			                        'string',
			                        'number',
			                        'xprofile-serialized',
			                 );

		public function enqueue_scripts() {

		}

		/**
		 * Enqueues the admin script
		 * @since 1.0
		 *
		 * @return (void)
		 **/
		public function enqueue_admin_scripts() {
			wp_enqueue_script(  'auf-module-select', __AUF_URL__ . 'modules/select.js', array( 'auf-admin-script' ) );

		}

		/**
		 * Renders the admin interface for this element
		 * @since 1.0
		 *
		 * @param (array) $settings The current settings
		 * @param (array) $modul The current modul
		 * @param (array) $filter The current filter
		 * @param (integer) $index The current modul index
		 *
		 * @return (string) $html The HTML for the admin interface
		 **/
		public function render_admin( $settings, $modul = array(), $filter = array(), $index = 0 ) {
			$html  = '';

			$value = "";
			if ( ! empty( $settings['alloption'] ) )
				$value = $settings['alloption'];

			$html .= '<section>';
			$html .= '<label>' . __( 'Text for the "all"-option', 'auf' ) . '</label>';
			$html .= '<div><input type="text" name="auf[' . $this->ID . '][alloption][]" value="' . esc_attr( $value ) . '" /></div>';
			$html .= '</section>';

			$value = "";
			if ( ! empty( $settings['mode'] ) )
				$value = $settings['mode'];

			$html .= '<section class="' . $this->ID . '-mode">';
			$html .= '<label>' . __( 'Mode', 'auf' ) . '</label>';
			$html .= '<div><select name="auf[' . $this->ID . '][mode][]">';

			$checked = '';
			if( $value == 'auto' )
				$checked='selected="selected"';
			$html .= '<option ' . $checked . ' value="auto">' . __( 'Automatic', 'auf' ) . '</option>';
			$checked = '';
			if( $value == 'individual' )
				$checked='selected="selected"';
			$html .= '<option ' . $checked . ' value="individual">' . __( 'Individual', 'auf' ) . '</option>';
			$html .= '</select></div>';
			$html .= '</section>';
			
			return $html;
		}

		/**
		 * Renders the filter interface for this element
		 * @since 1.0
		 *
		 * @param (array) $settings The current settings
		 * @param (array) $modul The current modul
		 * @param (array) $filter The current filter
		 * @param (integer) $index The current modul index
		 *
		 * @return (string) $html The HTML for the admin interface
		 **/
		function render_element( $settings, $modul = array(), $filter = array(), $index = 0, $current_value ) {
			global $wpdb;

			$html  = '';
			$options = array( array( 'value' => '', 'label' => $settings['alloption'] ) );

			//Autopopulate options when mode is "auto"
			if ( $settings['mode'] == 'auto' ) {

				$source = explode( '::', $modul['source'] );
				if ( 'meta' == $source[0] ) {
					//Metadata

					$sql = 'select meta_value from ' . $wpdb->usermeta . ' where meta_key = %s group by meta_value order by meta_value';
					$sql = $wpdb->prepare( $sql, $source[1] );
					$tmp = $wpdb->get_col( $sql );

					foreach( $tmp as $key => $value ) {
						if( empty( $value ) ) {
							unset( $tmp[ $key ] );
						}
					}

					$options = array_merge( $options, $tmp );
				} elseif ( 'roles' == $source[0] ) {
					//User Roles

					$tmp_roles = auf_get_all_roles();
					$roles = array();
					foreach ( $tmp_roles as $value => $label ) {
						if ( empty( $value ) ) {
							continue;
						}
						$roles[] = array( 
							'value' => $value,
							'label' => $label
						);
					}
					$options = array_merge( $options, $roles );

				} elseif ( 'xprofile' == $source[0] && defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE && bp_is_active( 'xprofile' ) ) {
					//BuddyPress XProfiles

					$values = auf_get_all_xprofile_field_data( $source[1] );
					$xprofile_data = array();
					foreach ( $values as $value ) {
						if ( empty( $value ) ) {
							continue;
						}
						$xprofile_data[] = array( 
							'value' => $value,
							'label' => $value
						);
					}
					$options = array_merge( $options, $xprofile_data );
				}

				/**
				 * Filters the autopopulated options
				 * @since 1.0
				 *
				 * @param (array) $options The options
				 * @param (array) $filter  The current filter
				 * @param (array) $modul   The current modul
				 *
				 * @return (array) $options
				 **/
				$options = apply_filters( 'auf::elements::' . $this->ID . '::options::auto', $options, $modul, $filter );
			}

			$html = '<select name="' . auf_get_the_elements_name() . '" class="' . auf_get_element_classes( $this->ID, 'select' ) . '" id="' . auf_get_the_elements_id() . '">';

			/**
			 * Filters the options
			 * @since 1.0
			 *
			 * @param (array) $options The options
			 * @param (array) $filter  The current filter
			 * @param (array) $modul   The current modul
			 *
			 * @return (array) $options
			 **/
			$options = apply_filters( 'auf::elements::' . $this->ID . '::options', $options, $modul, $filter );
			foreach ( $options as $option ) {
				$selected = '';
				if ( ! is_array( $option ) ) {
					if( $option == $current_value ) {
						$selected = 'selected="selected"';
					}

					$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . $option . '</option>';
				} else {	
					if( $option['value'] == $current_value ) {
						$selected = 'selected="selected"';	
					}			
					$html .= '<option value="' . esc_attr( $option['value'] ) . '" ' . $selected . '>' . $option['label'] . '</option>';
				}
			}
			$html .= '</select>';
			return $html;

		}

		/**
		 * Sanitizes the data which are supposed to be saved
		 * @since 1.0
		 *
		 * @param (array) $fields The fields to be saved
		 *
		 * @return (array|WP_Error) $fields
		  **/
		 function save( $fields ) {
		 	if( ! isset( $fields['alloption'] ) ) {
		 		$fields['alloption'] = '';
		 	}
		 	$fields['alloption'] = sanitize_text_field( $fields['alloption'] );

		 	if ( ! isset( $fields['mode'] ) || ! in_array( $fields['mode'], array( 'auto', 'individual' ) ) ) {
		 		return new WP_Error( 'wrong-mode', __( 'You need to set the mode to "auto" or "individual".', 'auf' ) );
		 	}

			return $fields;
		 }


		/**
		 * Returns the arguments for the User_Query
		 * @since 1.0
		 *
		 * @param (array) $args The query args.
		 *
		 * @return (array) $query The query args for the User_Query
		 **/
		public function query( $args, $modul ) {
			$query = array();
			$source = explode( '::', $modul['source'] );

			if ( $source[0] == 'meta' ) {
				$meta_key = $source[1];
				$query = array(
					'meta_query' => array(
						array(
							'key'   => $meta_key,
							'value' => $args,
						),
					),
				);
			} elseif ( $source[0] == 'roles' ) {
				$query = array( 'role' => $args );
			} elseif ( $source[0] == 'xprofile' && defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE && bp_is_active( 'xprofile' ) ) {
				$field = $source[1];

				//We have to switch a bit between serialized fields and normal fields
				if ( auf_xprofile_fielddata_is_serialized( $field ) ) {
					$query['xprofile_query'] = array(
						array(
							'field'  => $field,
							'value'  => '"' . $args . '"',
							'compare' => 'LIKE'
						),
					);
				} else {
					$query['xprofile_query'] = array(
						array(
							'field'  => $field,
							'value'  => $args,
						),
					);
				}
			}
			return $query;
		}
	}

	new AUF_SELECT();