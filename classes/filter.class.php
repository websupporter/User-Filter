<?php
	$auf = false;

	class AUF_FILTER {
		/**
		 * The filter settings array
		 **/
		public $filter = array();
		public $current_modul_index = 0;
		public $current_modul = array();

		public $results = array();
		public $results_raw = array();
		public $current_result_index = 0;
		public $current_result = array();
		public $total_users = 0;
		public $did_search = false;

		/**
		 * Load the filter
		 * @since 1.0
		 *
		 * @param (string) $id The ID of the filter to load
		 *
		 * @return (boolean|WP_Error) Returns `true` on success and an WP_Error if the filter was not found.
		 **/
		function init( $id ) {
			global $auf;

			$all_filters = get_option( 'auf-filters', array() );
			if ( empty( $all_filters[ $id ] ) ) {
				return new WP_Error( 'filter-not-found', sprintf( __( 'The filter "%s" was not found.', 'auf' ), $id ) );
			}

			$this->filter = $all_filters[ $id ];
			$auf = $this;

			//A search has been performed
			if ( isset( $_REQUEST['auf-search'] ) && $_REQUEST['auf-search'] == $this->filter['ID'] ) {
				$this->search();
			}

			return true;
		}

		/**
		 * Conducts the search
		 * @since 1.0
		 *
		 * @return (void)
		 **/
		function search() {
			$args = array();

			//No query-args have been passed
			if ( empty( $_REQUEST['auf'] ) || ! is_array( $_REQUEST['auf'] ) ) {
				return;
			}

			$modul_queries = array();
			$args = array();
			$query_args = $_REQUEST['auf'];
			foreach ( $query_args as $key => $val ) {
				if ( empty( $val ) ) {
					continue;
				}

				if ( empty( $this->filter['moduls'][ $key ] ) ) {
					continue;
				}

				$element = $this->filter['moduls'][ $key ]['element'];
				$element = auf_get_registered_element_by_id( $element );

				if( is_wp_error( $element ) ) {
					continue;
				}

				$modul_queries[] = $element->query( $val, $this->filter['moduls'][ $key ] );
			}
			
			foreach ( $modul_queries as $query ) {
				$args = array_merge_recursive( $args, $query ); //Might need to be redone. Let's see. Works for meta_queries so far.
			}


			if( defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE ) {
				//BP_User_Query Arguments
				$is_buddypress_query = true;

				//Page of results
				if ( empty( $args['page'] ) ) {
					$args['page'] = ( empty( $_REQUEST['auf-page'] ) ) ? 1 : (int) $_REQUEST['auf-page'];
				}

				//Number of results per page
				if ( empty( $args['per_page'] ) ) {
					$args['per_page'] = ( empty( $_REQUEST['auf-per-page'] ) ) ? (int) get_option( 'posts_per_page', 10 ) : (int) $_REQUEST['auf-per-page'];
				}


			} else {
				//WP_User_Query Arguments
				$is_buddypress_query = false;

				//Page of results
				if ( empty( $args['paged'] ) ) {
					$args['paged'] = ( empty( $_REQUEST['auf-page'] ) ) ? 1 : (int) $_REQUEST['auf-page'];
				}

				//Number of results per page
				if ( empty( $args['number'] ) ) {
					$args['number'] = ( empty( $_REQUEST['auf-per-page'] ) ) ? (int) get_option( 'posts_per_page', 10 ) : (int) $_REQUEST['auf-per-page'];
				}

			}

			/**
			 * Filters the user query args
			 * @since 1.0
			 *
			 * @param (array)   $args                The arguments passed to WP_User_Query/BP_User_Query
			 * @param (array)   $filter              The current filter
			 * @param (boolean) $is_buddypress_query Whether the arguments are passed to BP_User_Query or not
			 *
			 * @return (array) $args
			 **/
			$args = apply_filters( 'auf::search::args', $args, $this->filter, $is_buddypress_query );

			//Do the search
			if ( ! $is_buddypress_query ) {
				//Normal User Query
				$user_query = new WP_User_Query( $args );

				//The results of WP_User_Query differ slightly from the results of the BP_User_Query
				//We need to harmonize them.
				//The raw results will be saved in $this->results_raw. 
				if ( is_array( $user_query->results ) ) {
					foreach ( $user_query->results as $result ) {
						$this->results[] = $result->data;
					}
				}
			} else {
				//BuddyPress User Query
				$user_query = new BP_User_Query( $args );

				//BP_User_Query puts the User ID as the key of the array.
				//We need to harmonize this.
				//The raw results will be saved in $this->results_raw.
				if ( is_array( $user_query->results ) ) {
					foreach ($user_query->results as $result ) {
						$this->results[] = $result;
					}
				}	
			}
			#echo '<pre>';print_r( $user_query );echo '</pre>';
			$this->results_raw = $user_query->results;
			$this->total_users = $user_query->total_users;

			$this->did_search = true;
		}

		/**
		 * Returns the method of the filter
		 * @since 1.0
		 *
		 * @return (string) the method
		 **/
		function get_method() {
			return "GET";
		}

		/**
		 * Returns the form action of the filter
		 * @since 1.0
		 *
		 * @return (string) the action
		 **/
		function get_action() {
			return "";
		}

		/**
		 * Returns whether the filter has moduls to display or not
		 * @since 1.0
		 *
		 * @return (boolean)
		 **/
		function has_moduls() {
			return ( count( $this->filter['moduls'] ) > $this->current_modul_index ) ? true : false;
		}

		/**
		 * Returns whether the filter has results to display or not
		 * @since 1.0
		 *
		 * @return (boolean)
		 **/
		function has_results() {
			return ( count( $this->results ) > $this->current_result_index ) ? true : false;
		}

		/**
		 * Initialize the current module
		 * @since 1.0
		 *
		 * @return (boolean)
		 **/
		function the_modul() {
			$this->current_modul = $this->filter['moduls'][ $this->current_modul_index ];
			$this->current_modul_index++;
		}

		/**
		 * Inititalize the current result
		 * @since 1.0
		 *
		 * @return (boolean)
		 **/
		function the_result() {
			$this->current_result = $this->results[ $this->current_result_index ];
			$this->current_result_index++;
		}

		/**
		 * Returns the ID of the current modul
		 * @since 1.0
		 *
		 * @return (integer)
		 **/
		function get_the_modul_id() {
			return $this->current_modul_index - 1;
		}

		/**
		 * Returns the label of the current modul
		 * @since 1.0
		 *
		 * @return (string) $label the current label
		 **/
		function get_the_label() {
			if ( ! empty( $this->current_modul['label'] ) )
				return $this->current_modul['label'];
			return '';
		}

		/**
		 * Returns the ID of the filter
		 * @since 1.0
		 *
		 * @return (string) The filter ID
		 **/
		function get_the_filter_id() {
			if ( ! empty( $this->filter['ID'] ) )
				return $this->filter['ID'];
			return '';
		}

		/**
		 * Returns the element of the current modul
		 * @since 1.0
		 *
		 * @return (string) The HTML element of the current modul
		 **/
		function get_the_element() {
			$element = auf_get_registered_element_by_id( $this->current_modul['element'] );
			if ( is_wp_error( $element ) ) {
					return;
			}

			$current_value = '';
			if ( isset( $_REQUEST['auf-search'] ) && $_REQUEST['auf-search'] == $this->filter['ID'] && isset( $_REQUEST['auf'][ $this->get_the_modul_id() ] ) )
				$current_value = $_REQUEST['auf'][ $this->get_the_modul_id() ];

			return $element->render( 'element', $this->current_modul, $this->filter, $this->get_the_modul_id(), $current_value );
		}
	}