<?php		
		/**
		 * Fires before the admin page gets rendered
		 **/
		do_action( 'auf::admin::before' );

		$filters = get_option( 'auf-filters', array() );

?><div class="wrap">
	<h1><?php _e( 'Advanced User Search', 'auf' ); ?></h1>

	<a class="button" href="<?php echo esc_url( add_query_arg( array( 'new' => 1 ) ) ); ?>"><?php _e( 'Create a new filter' ); ?></a>

	<?php if( ! $filters ) : ?>
	<p><?php _e( 'No filters found.', 'auf' ); ?></p>
	<?php else: ?>

	<table class="table">
		<thead>
		</thead>
		<tbody>
		</tbody>
	</table>

	<?php endif; ?>

</div><?php		
		/**
		 * Fires after the admin page gets rendered
		 **/
		do_action( 'auf::admin::after' );
?><