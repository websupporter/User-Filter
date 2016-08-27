<?php
	class AUF_INPUT extends AUF_FILTER_ELEMENTS {

		public $name       = 'Text Field';
		public $ID         = 'input';
		public $icon       = 'user-search/assetts/input.png';
		public $icon_small = 'user-search/assetts/input-small.png';
		public $sources    = array(
				                    'meta',
				                    'xprofile',
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
			wp_enqueue_script(  'auf-module-select', __AUF_URL__ . 'modules/input.js', array( 'auf-admin-script' ) );

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
			return '';
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
			//We should define a type depending on the source type.
			$html = '<input name="' . auf_get_the_elements_name() . '" class="' . auf_get_element_classes( $this->ID, 'input' ) . '" id="' . auf_get_the_elements_id() . '" value="' . esc_attr( $current_value ) . '">';
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
							'key'     => $meta_key,
							'value'   => $args,
							'compare' => 'LIKE',
						),
					),
				);

				if( AUF_BUDDYPRESS_IS_ACTIVE ) {
					add_filter( 'bp_user_query_uid_clauses', array( $this, 'bp_query_uid_clauses' ), 10, 2 );

					$query = array(
						'meta_query-' . $this->ID => array(
							array(
								'key'   => $meta_key,
								'value' => $args,
							),
						),
					);
				}
			} elseif ( $source[0] == 'xprofile' && defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE && bp_is_active( 'xprofile' ) ) {$field = $source[1];
				$query['xprofile_query'] = array(
					array(
						'field'   => $field,
						'value'   => $args,
						'compare' => 'LIKE',
					),
				);			
			}
			return $query;
		}

		/**
		 * Filters the BP Query UID clauses to extend it e.g. to find roles
		 * @since 1.0
		 *
		 * @param (array)                $clauses The UID clauses
		 * @param (BP_User_Query Object) $query   The current object
		 *
		 * @return (array) $clauses
		 **/
		function bp_query_uid_clauses( $clauses, $query ) {
			global $wpdb;

			//Extend for meta_query
			if( ! empty( $query->query_vars['meta_query-' . $this->ID ] ) ) {
				$meta_queries = $query->query_vars['meta_query-' . $this->ID ];
				foreach ( $meta_queries as $meta_query ) {
					$sql = $wpdb->prepare(
						 'u.user_id IN ( SELECT user_id from ' . $wpdb->prefix . 'usermeta where meta_key = "' 
						 	. $meta_query['key'] . '" && meta_value LIKE %s )',
						'%' . $meta_query['value'] . '%'
					);
					$clauses['where'][] = $sql;
				}

			}

			return $clauses;
		}
	}

	new AUF_INPUT();