<?php
$user = auf_get_current_result();
if ( ! is_wp_error( $user ) ) :
?>

<?php echo get_avatar( $user->ID ); ?>
<p>
	<?php echo $user->user_nicename; ?>
</p>
<?php endif; ?>