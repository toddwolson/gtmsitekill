<?php
/**
 * Plugin Name: Site Kit GTM Network Toggle
 * Description: Network-wide on/off switch for disabling Google Site Kit GTM snippet output across all multisite subsites.
 * Version: 1.0.0
 * Author: Custom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RW_SiteKit_GTM_Network_Toggle {
	const NETWORK_OPTION = 'rw_disable_sitekit_gtm_networkwide';

	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'add_network_page' ] );
			add_action( 'network_admin_edit_rw_sitekit_gtm_toggle', [ $this, 'save_network_page' ] );
		}

		// Override Site Kit's GTM settings on every site when enabled.
		add_filter( 'option_googlesitekit_tagmanager_settings', [ $this, 'maybe_disable_gtm_snippet' ], 999 );
	}

	public function maybe_disable_gtm_snippet( $value ) {
		if ( ! is_multisite() ) {
			return $value;
		}

		$disabled = (bool) get_site_option( self::NETWORK_OPTION, false );

		if ( ! $disabled ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			$value = [];
		}

		// Force Site Kit not to output GTM snippet.
		$value['useSnippet'] = false;

		return $value;
	}

	public function add_network_page() {
		add_submenu_page(
			'settings.php',
			'Site Kit GTM Toggle',
			'Site Kit GTM Toggle',
			'manage_network_options',
			'rw-sitekit-gtm-toggle',
			[ $this, 'render_network_page' ]
		);
	}

	public function render_network_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.' ) );
		}

		$disabled = (bool) get_site_option( self::NETWORK_OPTION, false );
		?>
		<div class="wrap">
			<h1>Site Kit GTM Network Toggle</h1>

			<p>
				This switch disables <strong>Google Site Kit's GTM snippet output</strong> across all subsites.
			</p>

			<p>
				<strong>Current status:</strong>
				<?php echo $disabled ? '<span style="color:#b32d2e;">DISABLED network-wide</span>' : '<span style="color:#008a20;">ENABLED normally</span>'; ?>
			</p>

			<p>
				Note: This only affects GTM injected by <strong>Site Kit</strong>. It will not remove GTM code hardcoded in themes, custom plugins, code snippets, or other tag managers.
			</p>

			<form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=rw_sitekit_gtm_toggle' ) ); ?>">
				<?php wp_nonce_field( 'rw_sitekit_gtm_toggle_action', 'rw_sitekit_gtm_toggle_nonce' ); ?>

				<input type="hidden" name="rw_disable_sitekit_gtm_networkwide" value="0">
				<label>
					<input type="checkbox" name="rw_disable_sitekit_gtm_networkwide" value="1" <?php checked( $disabled ); ?>>
					Disable Site Kit GTM on all subsites
				</label>

				<?php submit_button( 'Save Setting' ); ?>
			</form>
		</div>
		<?php
	}

	public function save_network_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.' ) );
		}

		check_admin_referer( 'rw_sitekit_gtm_toggle_action', 'rw_sitekit_gtm_toggle_nonce' );

		$disabled = ! empty( $_POST['rw_disable_sitekit_gtm_networkwide'] ) ? 1 : 0;
		update_site_option( self::NETWORK_OPTION, $disabled );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'rw-sitekit-gtm-toggle',
					'updated' => 'true',
				],
				network_admin_url( 'settings.php' )
			)
		);
		exit;
	}
}

new RW_SiteKit_GTM_Network_Toggle();