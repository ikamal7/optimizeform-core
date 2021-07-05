<?php
# print_r( $module );
if ( optimizeform_core_is_module_active( $module->slug ) ) {
	$settings_url = optimizeform_core_get_module_settings_url( $module->slug );
	if ( $settings_url ) {
		printf(
			'<a class="action-btn manage-btn" href="%s">%s</a>',
			$settings_url,
			__( 'Manage', 'optimizeform-core' )
		);
	}
	printf(
		'<div class="optimizeform-core-switch switch-on optimizeform-core-module-switch" data-slug="%s" data-nonce="%s">
			<span class="switch-label">
				<span class="switch-inner"></span>
				<span class="switch-pointer"></span>
			</span>
		</div>',
		$module->slug,
		wp_create_nonce( 'deactivate-plugin_' . $module->slug )
	);

} elseif ( optimizeform_core_is_module_installed( $module->slug ) ) {
	if ( $module->homepage ) {
		printf(
			'<a class="action-btn learn-btn" href="%s" target="_blank">%s</a>',
			$module->homepage,
			__( 'Learn more', 'optimizeform-core' )
		);
	}
	printf(
		'<div class="optimizeform-core-switch switch-off optimizeform-core-module-switch" data-slug="%s" data-nonce="%s">
			<span class="switch-label">
				<span class="switch-inner"></span>
				<span class="switch-pointer"></span>
			</span>
		</div>',
		$module->slug,
		wp_create_nonce( 'activate-plugin_' . $module->slug )
	);
} elseif ( optimizeform_core_get_license_key() ) {
	if ( $module->homepage ) {
		printf(
			'<a class="action-btn learn-btn" href="%s">%s</a>',
			$module->homepage,
			__( 'Learn more', 'optimizeform-core' )
		);
		printf(
			'<button class="action-btn install-btn" data-slug="%s" data-nonce="%s">%s</button>',
			$module->slug,
			wp_create_nonce( 'updates' ),
			__( 'Install Now', 'optimizeform-core' )
		);
	}
} else {
	if ( $module->homepage ) {
		printf(
			'<a class="action-btn learn-btn" href="%s">%s</a>',
			$module->homepage,
			__( 'Learn more', 'optimizeform-core' )
		);
		_e( 'Activate License', 'optimizeform-core' );
	}
}
