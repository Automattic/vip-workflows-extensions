<?php
/** Narrow Secure MCP settings adapter for the WCAG validator.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Manage this one tool without allowing arbitrary WordPress option writes. */
final class WCAG_Settings {
	public const ID   = 'workflow-tool-wcag-aa-22-audit/wcag-settings';
	private const KEYS = array( 'audit_mode', 'fail_on', 'min_score', 'render_blocks', 'include_template', 'enabled', 'show_in_commands' );

	/** Register outside the Workflows tool list: this is an administrator operation. */
	public static function register(): void {
		wp_register_ability(
			self::ID,
			array(
				'label'               => __( 'WCAG 2.2 AA audit settings', 'workflow-tool-wcag-aa-22-audit' ),
				'description'         => __( 'Read or update only the WCAG 2.2 AA audit settings on the current site. Requires manage_options and a matching site_url. Omit settings to read.', 'workflow-tool-wcag-aa-22-audit' ),
				'category'            => 'vip-workflows',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'site_url' ),
					'additionalProperties' => false,
					'properties'           => array(
						'site_url' => array( 'type' => 'string' ),
						'settings' => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'audit_mode'       => array(
									'type' => 'string',
									'enum' => WCAG_Audit::MODES,
								),
								'fail_on'          => array(
									'type' => 'string',
									'enum' => WCAG_Audit::STRICTNESS,
								),
								'min_score'        => array(
									'type'    => 'integer',
									'minimum' => 0,
									'maximum' => 100,
								),
								'render_blocks'    => array( 'type' => 'boolean' ),
								'include_template' => array( 'type' => 'boolean' ),
								'enabled'          => array( 'type' => 'boolean' ),
								'show_in_commands' => array( 'type' => 'boolean' ),
							),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'site_url' => array( 'type' => 'string' ),
						'ability'  => array( 'type' => 'string' ),
						'settings' => array( 'type' => 'object' ),
						'updated'  => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'execute_callback'    => array( self::class, 'execute' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/** Validate again for callers using the PHP callback directly. */
	public static function execute( array $input ): array|\WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'wcag_settings_forbidden', 'Only an administrator can configure the WCAG 2.2 AA audit.' );
		}
		$url = rtrim( home_url( '/' ), '/' );
		if ( ! isset( $input['site_url'] ) || ! is_string( $input['site_url'] ) || rtrim( $input['site_url'], '/' ) !== $url ) {
			return new \WP_Error( 'wcag_settings_wrong_site', 'site_url must match the current site.' );
		}
		$settings = $input['settings'] ?? array();
		if ( ! is_array( $settings ) || array_diff( array_keys( $settings ), self::KEYS ) ) {
			return new \WP_Error( 'wcag_settings_invalid', 'Only the documented WCAG audit settings may be updated.' );
		}
		foreach ( $settings as $key => $value ) {
			$valid = match ( $key ) {
				'audit_mode' => in_array( $value, WCAG_Audit::MODES, true ),
				'fail_on' => in_array( $value, WCAG_Audit::STRICTNESS, true ),
				'min_score' => is_int( $value ) && $value >= 0 && $value <= 100,
				default => is_bool( $value ),
			};
			if ( ! $valid ) {
				return new \WP_Error( 'wcag_settings_invalid', 'Use audit_mode saved-content or public, fail_on errors or errors-and-warnings, min_score an integer from 0 to 100, and booleans for render_blocks, include_template, enabled and show_in_commands.' );
			}
		}
		$manager = \VIPWorkflows\Abilities\AbilitySettings::get_instance();
		$before  = $manager->get( WCAG_Audit::ID );
		$changes = array();
		foreach ( $settings as $key => $value ) {
			if ( in_array( $key, array( 'enabled', 'show_in_commands' ), true ) ) {
				if ( ( $before[ $key ] ?? null ) !== $value ) {
					$changes[ $key ] = $value;
				}
			} elseif ( ( $before['options'][ $key ] ?? null ) !== $value ) {
				$changes['options'][ $key ] = $value;
			}
		}
		if ( $changes && ! $manager->update( WCAG_Audit::ID, $changes ) ) {
			return new \WP_Error( 'wcag_settings_write_failed', 'WCAG audit settings could not be saved. Read settings again before retrying.' );
		}
		return array(
			'site_url' => $url,
			'ability'  => WCAG_Audit::ID,
			'settings' => $manager->get( WCAG_Audit::ID ),
			'updated'  => ! empty( $changes ),
		);
	}
}
