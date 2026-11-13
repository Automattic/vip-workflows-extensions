<?php
/**
 * Plugin Name: Workflow WCAG 2.2 AA Audit
 * Description: Automated WCAG 2.2 Level AA accessibility reports and a publishing gate for VIP Workflows.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * Text Domain: workflow-tool-wcag-aa-22-audit
 *
 * Checks saved post content (including drafts) or the published page against
 * the automatable parts of WCAG 2.2 (https://www.w3.org/TR/WCAG22/) and lists
 * the success criteria that need manual review. No API key, AI service or
 * build step is required. PHP DOM is required.
 *
 * No "Requires Plugins" header: on hosted VIP sites VIP Workflows is delivered as a
 * VIP platform integration rather than a plugin folder, so WordPress's
 * dependency check can never resolve it and would block activation outright.
 * register_tools() below does the equivalent check at runtime instead and
 * no-ops (with an admin notice) if VIP Workflows isn't actually active.
 *
 * @package WorkflowToolWcagAa22Audit
 */

declare( strict_types=1 );

namespace WorkflowToolWcagAa22Audit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-post-tool.php';
require_once __DIR__ . '/includes/class-wcag-color.php';
require_once __DIR__ . '/includes/class-wcag-document.php';
require_once __DIR__ . '/includes/class-wcag-rules.php';
require_once __DIR__ . '/includes/class-wcag-content.php';
require_once __DIR__ . '/includes/class-wcag-audit.php';
require_once __DIR__ . '/includes/class-wcag-gate.php';
require_once __DIR__ . '/includes/class-wcag-settings.php';

// Runtime detection also supports a core supplied as a VIP platform integration.
add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\\register_tools' );

/** Register the detailed report, transition gate and administrator settings ability. */
function register_tools(): void {
	if ( ! function_exists( 'vip_workflows_register_ability' ) || ! class_exists( '\\VIPWorkflows\\Abilities\\AbilitySettings' ) ) {
		return;
	}
	foreach ( array( WCAG_Audit::class, WCAG_Gate::class, WCAG_Settings::class ) as $tool ) {
		$tool::register();
	}
}

add_action(
	'admin_notices',
	static function (): void {
		if ( current_user_can( 'manage_options' ) && ! function_exists( 'vip_workflows_register_ability' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Workflow WCAG 2.2 AA Audit requires an active VIP Workflows integration. Its tools will register when that integration is available.', 'workflow-tool-wcag-aa-22-audit' ) . '</p></div>';
		}
	}
);

/** Scoped presentation for this plugin's results inside the Workflows modal. */
add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		if ( ! function_exists( 'vip_workflows_register_ability' ) ) {
			return;
		}
		wp_enqueue_style( 'workflow-wcag-report', plugins_url( 'assets/wcag-report.css', __FILE__ ), array(), (string) filemtime( __DIR__ . '/assets/wcag-report.css' ) );
		wp_enqueue_script( 'workflow-wcag-report', plugins_url( 'assets/wcag-report.js', __FILE__ ), array( 'wp-data', 'wp-block-editor' ), (string) filemtime( __DIR__ . '/assets/wcag-report.js' ), true );
		wp_add_inline_script( 'workflow-wcag-report', 'window.workflowWcagReport = ' . wp_json_encode( array( 'labels' => array( __( 'WCAG 2.2 AA audit', 'workflow-tool-wcag-aa-22-audit' ), __( 'WCAG 2.2 AA publishing gate', 'workflow-tool-wcag-aa-22-audit' ) ) ) ) . ';', 'before' );
	}
);
