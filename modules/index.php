<?php

	class AUF_SELECT extends AUF_FILTER_ELEMENTS {

		public $name       = 'Select Box';
		public $ID         = 'select-box';
		public $icon       = 'advanced-user-search/assetts/select.png';
		public $icon_small = 'advanced-user-search/assetts/select-small.png';
		public $sources    = array(
				                    'meta',
			                 );
		public $types      = array(
			                        'string',
			                        'number',
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

			//autopopulate options when mode is "auto"
			if ( $settings['mode'] == 'auto' ) {
				$source = explode( '::', $modul['source'] );
				if ( 'meta' == $source[0] ) {
					$sql = 'select meta_value from ' . $wpdb->usermeta . ' where meta_key = %s group by meta_value order by meta_value';
					$sql = $wpdb->prepare( $sql, $source[1] );
					$options = array_merge( $options, $wpdb->get_col( $sql ) );
				}
			}

			$html = '<select name="' . auf_get_the_elements_name() . '" id="' . auf_get_the_elements_id() . '">';
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
			
			$meta_key = explode( '::', $modul['source'] );
			$meta_key = $meta_key[1];

			$query = array(
				'meta_query' => array(
					array(
						'key'   => $meta_key,
						'value' => $args,
					),
				),
			);
			return $query;
		}
	}

	new AUF_SELECT();
?>