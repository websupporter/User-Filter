<?php
	
	class AUF_FILTER_ELEMENTS {

		public $name                        = 'Main';
		public $ID                          = false;
		public $icon                        = 'user-search/assetts/icon.png';
		public $icon_small                  = 'user-search/assetts/icon-small.png';
		public $sources                     = array();
		public $types                       = array();
		public $can_handle_multiple_sources = false;

		public function __construct() {
			add_filter( 'auf::elements::get', array( $this, 'register_element' ) );
			add_action( 'auf::elements::enqueue_scripts', array( $this, 'do_enqueue_scripts' ) );
			add_action( 'auf::elements::enqueue_admin_scripts', array( $this, 'do_enqueue_admin_scripts' ) );

			if( method_exists( $this, 'save' ) ) {
				add_filter( 'auf::filter::save::element::' . $this->ID, array( $this, 'save' ) );
			}
		}

		/**
		 * Uses the filter 'auf::elements::get' to register the element
		 * @since 1.0
		 *
		 * @param (array) $elements all elements registered so far
		 *
		 * @return (array) $elements all registered elements plus the new registered element
		 **/
		public function register_element( $elements ) {
			$elements[] = $this;
			return $elements;
		}

		/**
		 * Gets fired with the action 'auf::elements::enqueue_scripts' and enqueues scripts
		 * @since 1.0
		 *
		 * @return (void)
		 **/
		public function do_enqueue_scripts() {
			if ( method_exists( $this, 'enqueue_scripts' ) ) {
				$this->enqueue_scripts();
			}
		}

		/**
		 * Gets fired with the action 'auf::elements::enqueue_admin_scripts' and enqueues admin scripts
		 * @since 1.0
		 *
		 * @return (void)
		 **/
		public function do_enqueue_admin_scripts() {
			if ( method_exists( $this, 'enqueue_admin_scripts' ) ) {
				$this->enqueue_admin_scripts();
			}
		}

		/**
		 * Returns the icon of the element
		 * @since 1.0
		 *
		 * @param (string) $size the size of the icon. Possible values are 'normal', 'small'
		 *
		 * @return (URL) $icon the URL for the icon
		 **/
		public function get_icon( $size = 'normal' ) {
			$icon = $this->icon_small;
			if( $size == 'normal' )
				$icon = $this->icon;

			/**
			 * Filters the icon URL
			 * @since 1.0
			 *
			 * @param (url)    $icon  URL of the icon
			 * @param (string) $size  size of the icon
			 * @param (string) $ID    ID of element
			 * 
			 * @return (url)   $icon
			 **/
			return apply_filters( 'auf::element::get_icon', $icon, $size, $this->ID );
		}

		/**
		 * Get the possible data sources for this element
		 * @since 1.0
		 *
		 * @return (array) $sources the sources
		 **/
		public function get_possible_data_sources() {

			/**
			 * Filters the data sources
			 * @since 1.0
			 *
			 * @param (array)  $sources array of sources
			 * @param (string) $ID    ID of element
			 * 
			 * @return (array) $sources
			 **/
			return apply_filters( 'auf::element::get_possible_data_sources', $this->sources, $this->ID );
		}


		/**
		 * Get the possible data types for this element
		 * @since 1.0
		 *
		 * @return (array) $types the sources
		 **/
		public function get_possible_data_types() {

			/**
			 * Filters the data types
			 * @since 1.0
			 *
			 * @param (array) $types array of types
			 * @param (string) $ID    ID of element
			 * 
			 * @return (array) $types
			 **/
			return apply_filters( 'auf::element::get_possible_data_types', $this->types, $this->ID );
		}


		/**
		 * Collect and return the elements data like ID, types, sources, icon, icon_small, name
		 * @since 1.0
		 *
		 * @return (array) $data the elements data
		 **/
		public function get_element_data() {
			/**
			 * Filters all element data
			 * @since 1.0
			 * 
			 * @param (array) $all_data array of all the data
			 * @param (string) $ID the ID of the element
			 *
			 * @return (array) $all_data
			 **/
			return apply_filters( 'auf::element::get_element_data', array(
				'ID'         => $this->ID,
				'types'      => $this->get_possible_data_types(),
				'sources'    => $this->get_possible_data_sources(),
				'icon'       => $this->get_icon(),
				'icon_small' => $this->get_icon( 'small' ),
				'name'       => $this->name,
			) );
		}

		/**
		 * Render specific HTML for the element
		 * @since 1.0
		 *
		 * @param (string) $screen the screen to render. Possible values 'element' (default), 'admin'
		 * @param (array)  $modul  the current modul
		 * @param (array)  $filter  the current filter
		 * @param (integer)  $index  the current modul index
		 * @param (string)  $current_value  if a search has been performed, the current value of this search
		 *
		 * @return (void)
		 **/
		public function render( $screen = 'element', $modul = array(), $filter = array(), $index = 0, $current_value = "" ) {
			$settings = array();
			if( ! empty( $modul[ $this->ID ] ) )
				$settings = $modul[ $this->ID ];

			if ( $screen == 'element' && method_exists( $this, 'render_element' ) ) {
				return $this->render_element( $settings, $modul, $filter, $index, $current_value );
			}
			if ( $screen == 'admin' && method_exists( $this, 'render_admin' ) ) {
				return $this->render_admin( $settings, $modul, $filter, $index );
			}
		}


		/**
		 * Returns the arguments for the User_Query
		 * @since 1.0
		 *
		 * @param (array) $args The query args.
		 * @param (array) $modul The current modul.
		 *
		 * @return (array) $query The query args for the User_Query
		 **/
		public function query( $args, $modul ) {
			$query = array();
			return $query;
		}


	}