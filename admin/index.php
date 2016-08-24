<?php		
		/**
		 * Fires before the admin page gets rendered
		 **/
		do_action( 'auf::admin::before' );

		$filters = get_option( 'auf-filters', array() );

?><div class="wrap">
	<h1><?php _e( 'Advanced User Search', 'auf' ); ?></h1>

	<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'auf-action' => 'create-new-filter' ) ), 'create-new-filter', 'auf-nonce' ) ); ?>"><?php _e( 'Create a new filter', 'auf' ); ?></a>

	<?php if( ! $filters ) : ?>
	<p><?php _e( 'No filters found.', 'auf' ); ?></p>
	<?php else: ?>
	<!-- Maybe later via the table_class -->
	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<th>ID</th><th>Name</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $filters as $filter ): ?>
			<tr>
				<td><a href="<?php echo esc_url( add_query_arg( array( 'ID' => $filter['ID'] ) ) ); ?>" title="<?php echo esc_attr( __( 'Edit filter', 'auf' ) ); ?>"><?php echo $filter['ID']; ?></td>
				<td><?php echo $filter['name']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php endif; ?>

</div><?php		
		/**
		 * Fires after the admin page gets rendered
		 **/
		do_action( 'auf::admin::after' );
?>