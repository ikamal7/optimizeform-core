<?php
if ( empty( $modules ) ) {
	echo '<div class="settings-error error"><p>' . __( 'Unable to load modules from our server. Please try later or contact support.' ) . '</p></div>';
}

?>
<div class="optimizeform-core-modules">
	<?php foreach ( $modules as $module ) : ?>
		<div class="module-card">
			<div class="header">
				<img src="<?php echo $module->icons->{'1x'}; ?>" class="module-icon" />
			</div>
			<div class="main">
				<div class="title"><?php echo $module->name; ?></div>
				<div class="desc">
					<?php echo $module->short_description; ?>
				</div>
			</div>
			<?php if ( ! empty( $module->last_updated ) ) : ?>
				<div class="last-updated">
					<strong><?php _e( 'Last Updated:', 'optimizeform-core' ); ?></strong>
					<?php
						/* translators: %s: Human-readable time difference. */
						printf( __( '%s ago' ), human_time_diff( strtotime( $module->last_updated ) ) );
					?>
				</div>
			<?php endif; ?>
			<div class="footer">
				<?php include 'module-footer.php'; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php include 'support-card.php'; ?>

</div>
