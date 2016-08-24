<?php
	$filter = auf_get_filter_settings( $_GET['ID'] );
	if( ! is_wp_error( $filter ) ):
?>
<div class="wrap">


<?php
require_once( dirname( __FILE__ ) . '/inc/metaboxes.php' );
do_action( 'add_meta_boxes' );
wp_enqueue_script('common');
wp_enqueue_script('wp-lists');
wp_enqueue_script('postbox');
?>

<form method="post">
	<p><a href="?page=auf-index"><?php _e( 'Filters overview', 'auf' ); ?></a> &raquo;</p>
	<h1><?php _e( 'Edit Filter', 'auf' ); ?></h1>
	<input type="hidden" name="ID" value="<?php echo esc_attr( $filter['ID'] ); ?>">
	<input type="hidden" name="auf-action" value="save-filter">
	<?php wp_nonce_field( 'filter-' . $filter['ID'], 'auf-nonce' ); ?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content" style="position: relative;">

				<div id="titlediv">
					<div id="titlewrap">
						<input id="title" type="text" autocomplete="off" spellcheck="true" value="<?php echo esc_attr( $filter['name'] ); ?>" size="30" name="name">
					</div>
				</div>

				<?php do_meta_boxes( 'auf-single', 'normal', $filter ); ?>
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( 'auf-single', 'side', $filter ); ?>
			</div>
		</div>
	</div>
</form>
</div>

<?php else: ?>
	<div class="wrap">
		<h1><?php printf( __( 'Filter "%s" not found.', 'auf' ), $_GET['ID'] ); ?></h1>
	</div>
<?php endif; ?>