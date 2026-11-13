<?php
/**
 * A transition-safe view of the same audit used by the editor report.
 *
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Gate issues contain only verdicts, never successful report rows. */
final class WCAG_Gate extends Post_Tool {
	public const ID = 'workflow-tool-wcag-aa-22-audit/wcag-gate';

	/** Register separately so transition consumers cannot misread the detailed report. */
	public static function register(): void {
		vip_workflows_register_ability(
			self::ID,
			array(
				'label'               => __( 'WCAG 2.2 AA publishing gate', 'workflow-tool-wcag-aa-22-audit' ),
				'description'         => __( 'Block a workflow transition while the automated WCAG 2.2 AA audit finds errors. Uses the WCAG 2.2 AA audit settings and rechecks the saved post each time. Run WCAG 2.2 AA audit on demand for the full checklist.', 'workflow-tool-wcag-aa-22-audit' ),
				'category'            => 'vip-workflows',
				// Transitions send only post_id, and Workflows expects transition tools to reject anything else.
				'input_schema'        => array_merge( self::input_schema(), array( 'additionalProperties' => false ) ),
				'output_schema'       => array(
					'type'       => 'object',
					'required'   => array( 'status', 'summary', 'score', 'issues' ),
					'properties' => array(
						'status'  => array( 'type' => 'string', 'enum' => array( 'pass', 'warning', 'fail' ) ),
						'summary' => array( 'type' => 'string' ),
						'score'   => array( 'type' => 'integer' ),
						'issues'  => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
					),
				),
				'permission_callback' => array( self::class, 'can_execute' ),
				'execute_callback'    => array( self::class, 'execute' ),
				'meta'                => array(
					'type'                => 'validator',
					'result_type'         => 'report',
					'icon'                => 'shield',
					'show_in_rest'        => true,
					'show_in_commands'    => false,
					'supports'            => array( 'workflow' ),
					'transition_eligible' => true,
					'annotations'         => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			)
		);
	}

	/**
	 * Recompute the audit; never trust an earlier report, input or UI state.
	 * Each blocking finding becomes its own error (a hard failure that cannot be acknowledged),
	 * followed by one summary error; each non-blocking warning becomes its own acknowledgeable warning.
	 */
	public static function execute( array $input ): array|\WP_Error {
		$report = WCAG_Audit::execute( $input );
		if ( is_wp_error( $report ) ) {
			return $report;
		}
		$passed   = $report['audit_complete'] && empty( $report['blockers'] ) && 'fail' !== $report['status'];
		$blocking = $advisory = array();
		foreach ( $report['issues'] as $issue ) {
			if ( 'failed' !== ( $issue['status'] ?? '' ) ) {
				continue;
			}
			if ( 'error' === $issue['severity'] || ! $passed ) {
				$blocking[] = $issue['rule'] . ': ' . $issue['message'];
			} else {
				$advisory[] = $issue['rule'] . ': ' . $issue['message'];
			}
		}
		// One issue per finding so the editor lists each on its own line.
		$issues = array();
		foreach ( $blocking as $i => $message ) {
			$issues[] = array(
				'check_key' => 'wcag_22_aa_' . ( $i + 1 ),
				'rule'      => 'WCAG 2.2 AA',
				'status'    => 'failed',
				'severity'  => 'error',
				'message'   => $message,
			);
		}
		if ( ! $passed ) {
			// Always at least one error, even when the audit failed closed without a finding.
			$issues[] = array(
				'check_key' => 'wcag_22_aa',
				'rule'      => 'WCAG 2.2 AA',
				'status'    => 'failed',
				'severity'  => 'error',
				'message'   => $report['summary'] . ' Save corrections and run WCAG 2.2 AA audit for the full checklist, then retry the transition.',
			);
		} else {
			foreach ( $advisory as $i => $message ) {
				$issues[] = array(
					'check_key' => 'wcag_22_aa_warning_' . ( $i + 1 ),
					'rule'      => 'WCAG 2.2 AA warnings',
					'status'    => 'failed',
					'severity'  => 'warning',
					'message'   => $message,
				);
			}
		}
		return array(
			'status'  => $passed ? ( $advisory ? 'warning' : 'pass' ) : 'fail',
			'summary' => $report['summary'],
			'score'   => $report['score'],
			'issues'  => $issues,
		);
	}
}
