<?php
	/**
	 * The filter template file
	 *
	 * @since 1.0
	 * @version 1.0
	 **/
?>

<?php if ( auf_filter_has_moduls() ) : ?>

	<form method="<?php echo esc_attr( auf_get_the_filter_method() ); ?>" method="<?php echo esc_attr( auf_get_the_filter_action() ); ?>">
		<?php auf_init_form(); ?>
		<div class="auf-filter-moduls">
			<?php while ( auf_filter_has_moduls() ) : auf_the_modul(); ?>
				
				<div class="auf-modul" id="modul-<?php echo esc_attr( auf_get_the_modul_id() ); ?>">
					<label for="<?php echo esc_attr( auf_get_the_elements_id() ); ?>"><?php echo auf_get_the_label(); ?></label>
					<?php echo auf_get_the_element(); ?>
				</div>

			<?php endwhile; ?>

			<button><?php _e( 'Search', 'auf' ); ?></button>
		</div>
		<?php if ( auf_search_performed() ) : ?>
		<div class="auf-filter-results">
			<?php if ( auf_filter_has_results() ) : ?>
				<?php while ( auf_filter_has_results() ) : auf_the_result(); ?>
					<?php auf_get_template( 'single-result' ); ?>
				<?php endwhile; ?>
			<?php else : ?>
				<p><?php _e( 'No user has been found.', 'auf' ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</form>

<?php else : ?>

	<p><?php _e( 'No filter specified.', 'auf' ); ?></p>

<?php endif; ?>