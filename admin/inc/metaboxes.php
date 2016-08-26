<?php

/**
 * Add the metaboxes to the filter editor
 * @since 1.0
 **/
add_action( 'add_meta_boxes', 'auf_add_meta_boxes' );
function auf_add_meta_boxes() {
	if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], array( 'auf-index' ) ) ) {
		return;
	}

	if ( ! empty( $_GET['ID'] ) ) {
		add_meta_box( 'auf-filter', __( 'Filter', 'auf' ), 'auf_metaboxes_filter', 'auf-single', 'normal' );
		add_meta_box( 'auf-select-elements', __( 'Elements', 'auf' ), 'auf_metaboxes_select_elements', 'auf-single', 'side' );
		add_meta_box( 'auf-save-filter', __( 'Save', 'auf' ), 'auf_metaboxes_save_filter', 'auf-single', 'side' );	
	}
}

/**
 * Render the save filter meta box to the filter editor
 * @since 1.0
 **/
function auf_metaboxes_save_filter( $filter ) {
	?>
	<button class="button primary"><?php _e( 'Save' ); ?></button>
	<?php
}


/**
 * Render the elements meta box to the filter editor
 * @since 1.0
 **/
function auf_metaboxes_select_elements( $filter ) {
	$elements = auf_get_registered_elements();
	?>

	<ul class="auf-list auf-js-draggable">
		<?php
		foreach( $elements as $element ) :
			if( ! $element->ID )
				continue;
			?>
			<li 
				class="" 
				data-element="<?php echo esc_attr( $element->ID ); ?>" 
				>
				<img src="<?php echo plugin_dir_url( '' ) . $element->get_icon(); ?>" alt="<?php echo esc_attr( $element->name ); ?>">
				<span>
					<?php echo $element->name; ?>
				</span>
			</li>
		<?php
		endforeach;
		?>
	</ul>
	<?php
}


/**
 * Render the filter box to the filter editor
 * @since 1.0
 **/
function auf_metaboxes_filter( $filter ) {
	$elements = auf_get_registered_elements();
	?>
	<ul id="auf-filter-area" class="auf-list auf-collapsable auf-js-droppable auf-js-sortable">
		<?php 
		if ( isset( $filter['moduls'] ) && count( $filter['moduls'] ) > 0 ) { 
			foreach ( $filter['moduls'] as $index => $modul ) { 
				$current_element = false;
				foreach ( $elements as $element ) {
					if ( $element->ID == $modul['element'] )
						$current_element = $element;
				}

				if ( ! $current_element ) {
					echo '<li>' . sprintf( __( 'Element "%s" not registered.', 'auf' ), $modul['element'] ) . '</li>';
					continue;
				}
			?>
		<li data-element="<?php echo esc_attr( $modul['element'] ); ?>" class="closed">
			<input type="hidden" class="auf-element-id"  name="auf[key][]" value="<?php echo esc_attr( $modul['element'] ); ?>">
			<header>
				<button aria-expanded="false" class="handlediv button-link" type="button">
					<span class="screen-reader-text"><?php _e( 'Open and close the element.', 'auf' ); ?></span>
					<span aria-hidden="true" class="toggle-indicator"></span>
				</button>
				<h3>
					<span class="type">
						<?php echo $current_element->name; ?>
					</span>:
					<span class="label">
						<?php echo $modul['label']; ?>						
					</span>
				</h3>
			</header>
			<div class="auf-js-collapse-body">
				<section data-type="label">
					<label><?php _e( 'Label', 'auf' ); ?>:</label>
					<div>
						<input type="text" name="auf[label][]" value="<?php echo esc_attr( $modul['label'] ); ?>">
					</div>
				</section>
				<section data-type="source">
					<label><?php _e( 'Source', 'auf' ); ?>:</label>
					<div>
						<select name="auf[source][]" data-selected="<?php echo esc_attr( $modul['source'] ); ?>"></select>
					</div>
				</section>
				<div class="element-area">
					<?php echo $current_element->render( 'admin', $modul, $filter, $index, $modul[ $current_element->ID ] ); ?>
				</div>
				<footer>
					<button class="auf-delete button"><?php _e( 'Delete modul', 'auf' ); ?></button>
				</footer>
			</div>
		</li>
		<?php 
			}
		}
		?>
	</ul>

	<script type="text/template" id="tmpl-filter-element">
		<li data-element="" class="closed">
			<input type="hidden" class="auf-element-id"  name="auf[key][]" value="">
			<header>
				<button aria-expanded="false" class="handlediv button-link" type="button">
					<span class="screen-reader-text"><?php _e( 'Open and close the element.', 'auf' ); ?></span>
					<span aria-hidden="true" class="toggle-indicator"></span>
				</button>
				<h3>
					<span class="type">
						
					</span>:
					<span class="label">
						
					</span>
				</h3>
			</header>
			<div class="auf-js-collapse-body">
				<section data-type="label">
					<label><?php _e( 'Label', 'auf' ); ?>:</label>
					<div>
						<input type="text" name="auf[label][]" value="">
					</div>
				</section>
				<section data-type="source">
					<label><?php _e( 'Source', 'auf' ); ?>:</label>
					<div>
						<select name="auf[source][]" data-selected=""></select>
					</div>
				</section>
				<div class="element-area"></div>
				<footer>
					<button class="auf-delete button"><?php _e( 'Delete modul', 'auf' ); ?></button>
				</footer>
			</div>
		</li>
	</script>

	<?php 
	foreach ( $elements as $element ) :
	?>
	<script type="text/template" id="tmpl-filter-element-<?php echo $element->ID; ?>">
		<?php echo $element->render( 'admin' ); ?>
	</script>

	<?php endforeach; 
}