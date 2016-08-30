<?php
$user = auf_get_current_result();
if ( ! is_wp_error( $user ) ) :

	if ( defined( 'AUF_BUDDYPRESS_IS_ACTIVE' ) && AUF_BUDDYPRESS_IS_ACTIVE ) {
		$link = bp_core_get_user_domain( $user->ID );
	} else {
		$link = get_author_posts_url( $user->ID );
	}
?>


<?php echo get_avatar( $user->ID ); ?>
<p>
	<a href="<?php echo esc_url( $link ); ?>" title="<?php printf( __( 'Userprofile of %s', 'auf' ), esc_attr( $user->user_nicename ) ); ?>">
		<?php echo $user->user_nicename; ?>
	</a>
</p>
<?php endif; ?>