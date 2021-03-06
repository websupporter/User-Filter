<?php
	$auf = false;

	class AUF_FILTER {
		/**
		 * The filter settings array
		 **/
		public $filter = array();
		public $query_args = array();
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
		 * @return (boolean|WP_Error) Returns `true` on success and an WP_Error on failure.
		 **/
		function init( $id ) {
			global $auf;

			$all_filters = get_option( 'auf-filters', array() );
			if ( empty( $all_filters[ $id ] ) ) {
				return new WP_Error( 'filter-not-found', sprintf( __( 'The filter "%s" was not found.', 'auf' ), $id ) );
			}

			$search_can_be_done = true;
			if( ! empty( $all_filters[ $id ]['settings']['only-loggedin'] ) && ! is_user_logged_in() ) {
				$search_can_be_done = false;
			}

			if ( ! $search_can_be_done ) {
				return new WP_Error( 'not-logged-in', sprintf( __( 'You need to be logged in to use this filter.', 'auf' ) ) );
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
			global $wpdb;
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
			$this->query_args = $args;


			//We need to transform the role-query to be able to search more than one role.
			if ( ! empty( $args['role'] ) ) {
				//Move role to meta_query
				$roles = $args['role'];
				unset( $args['role'] );
				if ( ! is_array( $roles ) ) {
					$roles = array( $role );
				}
				$role_query = array( 'relation' => 'OR' );
				foreach ( $roles as $role ) {
					$role_query[] = array(
						'key'     => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
						'value'   => '"' . $role . '"',
						'compare' => 'LIKE',
					);
				}
				$args['meta_query'][] = $role_query;
			}

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
				if ( ! empty( $args['search'] ) ) {
					//'search' is 'search_term' in BP_User_Query
					$args['search_terms'] = $args['search'];
					unset( $args['search'] );
				}

				if ( ! empty( $args['meta_query'] ) ) {
					//BP_User_Query can't query wp_usermeta... Yet.
					add_filter( 'bp_user_query_uid_clauses', array( $this, 'bp_query_uid_clauses' ), 10, 2 );
				}

				if ( ! empty( $this->filter['settings']['only-friends'] ) ) {
					$args['user_id'] = get_current_user_id();
				}

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

			$this->results_raw = $user_query->results;
			$this->total_users = $user_query->total_users;
			$this->did_search = true;
		}

		/**
		 * Filters the BP Query UID clauses to extend it e.g. for meta query
		 * @since 1.0
		 *
		 * @param (array)                $clauses The UID clauses
		 * @param (BP_User_Query Object) $query   The current object
		 *
		 * @return (array) $clauses
		 **/
		function bp_query_uid_clauses( $clauses, $query ) {
			//Extend for meta_query
			if( ! empty( $query->query_vars['meta_query'] ) ) {
				$meta_query = new WP_Meta_Query( $query->query_vars['meta_query'] );
				$meta_clauses = $meta_query->get_sql( 'user', 'u', 'user_id', $this );
				$clauses['select'] .= $meta_clauses['join'];

				//Remove ' AND' and add to 'where' clauge.
				$clauses['where'][] = substr( $meta_clauses['where'], 4 );
			}

			return $clauses;
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
		 * Returns whether the filter has pagination or not
		 * @since 1.0
		 *
		 * @return (boolean)
		 **/
		function has_pagination() {
			if ( empty( $this->query_args ) ) {
				return false;
			}

			if ( defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE ) {
				$per_page = $this->query_args['per_page'];
			} else {
				$per_page = $this->query_args['number'];				
			}

			return ( ( $this->total_users / $per_page ) > 1 ) ? true : false;
		}

		/**
		 * Returns the pagination for the search results
		 * @since 1.0
		 *
		 * @return (string|boolean) The HTML for the pagination, false if no pagination
		 **/
		function the_pagination() {
			if( ! $this->has_pagination() ) {
				return false;
			}

			$defaults = array(
				'element'     => 'li',
				'show_arrows' => true,
				'midsize'     => 3,
			);
			$args = wp_parse_args( $args, $defaults );

			if ( defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE ) {
				$per_page = $this->query_args['per_page'];
				$displayed_page = $this->query_args['page'];
			} else {
				$per_page = $this->query_args['number'];
				$displayed_page = $this->query_args['paged'];				
			}

			$pages = ceil( $this->total_users / $per_page );
			$big = 999999999; // need an unlikely integer
			$link = add_query_arg( array( 'auf-page' => '%#%' ), remove_query_arg( 'auf-page' ) );
			$html = paginate_links( array(
				'base'    => $link,
				'format'  => 'auf-page=%#%',
				'current' => $displayed_page,
				'total'   => $pages,
			) );

			/**
			 * Filters the pagination HTML
			 * @since 1.0
			 *
			 * @param (string) $html    The HTML to output
			 * @param (string) $args    The arguments 
			 * @param (object) $this    This
			 *
			 * @return (string) $html
			 **/
			return apply_filters( 'auf::the_pagination', $html, $args, $this );
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