<form method="post" action="">
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="license_key"><?php _e( 'License Key', 'optimizeform-core' ); ?></label>
				</th>
				<td>
					<?php if ( ! empty( $license_key ) ) : ?>
						<input name="license_key" type="text" id="license_key" value="<?php echo esc_attr( $license_key ); ?>" class="regular-text" disabled />
						<p class="description">
							<?php _e( 'Deactivating license from this site will disable auto-update for all of OptimizeForm plugins.' ); ?>
						</p>
						<p class="submit">
							<button type="submit" name="action" value="optimizeform_core_remove_license" class="button button-primary"><?php _e( 'Deactivate License', 'optimizeform-core' ); ?></button>
						</p>
					<?php else: ?>
						<input name="license_key" type="text" id="license_key" value="" class="regular-text" required />
						<p class="submit">
							<button type="submit" name="action" value="optimizeform_core_set_license" class="button button-primary"><?php _e( 'Activate License', 'optimizeform-core' ); ?></button>
						</p>
					<?php endif; ?>
					<?php wp_nonce_field( 'license-form' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</form>
