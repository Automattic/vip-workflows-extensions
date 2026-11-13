<?php
/** WCAG 2.2 AA validator ability.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Read-only, deterministic accessibility audit of a saved post or its public permalink. */
final class WCAG_Audit extends Post_Tool {
	public const ID          = 'workflow-tool-wcag-aa-22-audit/wcag-audit';
	public const MODES       = array( 'saved-content', 'public' );
	public const STRICTNESS  = array( 'errors', 'errors-and-warnings' );
	private const HTML_LIMIT = 3145728;

	/** Register with the Workflows report and settings interfaces. */
	public static function register(): void {
		vip_workflows_register_ability(
			self::ID,
			array(
				'label'               => __( 'WCAG 2.2 AA audit', 'workflow-tool-wcag-aa-22-audit' ),
				'description'         => __( 'Automated WCAG 2.2 Level A and AA checks: text alternatives, captions, headings, lists, tables, form labels, color contrast, keyboard access, link text, target size, language, ARIA and authentication. Saved-content mode checks the saved post, including drafts; public mode also checks the rendered page. Lists the success criteria that need manual review. Save edits before running.', 'workflow-tool-wcag-aa-22-audit' ),
				'category'            => 'vip-workflows',
				'input_schema'        => self::input_schema(),
				'output_schema'       => array(
					'type'       => 'object',
					'required'   => array( 'status', 'summary', 'score', 'audit_complete', 'checks', 'issues', 'blockers' ),
					'properties' => array(
						'status'             => array(
							'type' => 'string',
							'enum' => array( 'pass', 'warning', 'fail' ),
						),
						'summary'            => array( 'type' => 'string' ),
						'score'              => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 100,
						),
						'audit_complete'     => array( 'type' => 'boolean' ),
						'audit_mode'         => array(
							'type' => 'string',
							'enum' => self::MODES,
						),
						'fail_on'            => array(
							'type' => 'string',
							'enum' => self::STRICTNESS,
						),
						'min_score'          => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 100,
						),
						'conformance_target' => array( 'type' => 'string' ),
						'url'                => array( 'type' => 'string' ),
						'edit_url'           => array( 'type' => 'string' ),
						'checked_at'         => array( 'type' => 'string' ),
						'rubric_version'     => array( 'type' => 'string' ),
						'rendered'           => array( 'type' => 'boolean' ),
						'template'           => array( 'type' => 'string' ),
						'included'           => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'unchecked'          => array( 'type' => 'object' ),
						'counts'             => array( 'type' => 'object' ),
						'criteria'           => array( 'type' => 'object' ),
						'manual_review'      => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'blockers'           => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'checks'             => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'issues'             => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'required'   => array( 'rule', 'message', 'severity' ),
								'properties' => array(
									'rule'     => array( 'type' => 'string' ),
									'status'   => array(
										'type' => 'string',
										'enum' => array( 'passed', 'failed' ),
									),
									'message'  => array( 'type' => 'string' ),
									'severity' => array(
										'type' => 'string',
										'enum' => array( 'error', 'warning', 'info' ),
									),
								),
							),
						),
					),
				),
				'permission_callback' => array( self::class, 'can_execute' ),
				'execute_callback'    => array( self::class, 'execute' ),
				'meta'                => array(
					'type'                => 'validator',
					'result_type'         => 'report',
					'icon'                => 'search',
					'show_in_rest'        => true,
					'mcp'                 => array( 'public' => true ),
					'show_in_commands'    => true,
					'supports'            => array( 'workflow' ),
					'transition_eligible' => false,
					'settings_schema'     => array(
						'audit_mode' => array(
							'type'        => 'string',
							'enum'        => self::MODES,
							'default'     => 'saved-content',
							'label'       => __( 'Audit mode', 'workflow-tool-wcag-aa-22-audit' ),
							'description' => __( 'saved-content: check the saved post, including drafts, with no HTTP request; use this for publishing gates. public: check the full published page as anonymous visitors receive it, including theme markup (page language, zoom, skip links).', 'workflow-tool-wcag-aa-22-audit' ),
						),
						'render_blocks' => array(
							'type'        => 'boolean',
							'default'     => false,
							'label'       => __( 'Render dynamic blocks', 'workflow-tool-wcag-aa-22-audit' ),
							'description' => __( 'Saved-content mode only. Off: synced patterns, patterns and template parts are checked from their saved markup, and other dynamic blocks (Query Loop, Navigation, plugin blocks) are listed as not checked. On: the post is rendered with do_blocks() and do_shortcode() before checking, so their output is included. Rendering runs block and shortcode code on every audit and gate check.', 'workflow-tool-wcag-aa-22-audit' ),
						),
						'include_template' => array(
							'type'        => 'boolean',
							'default'     => false,
							'label'       => __( 'Include theme template', 'workflow-tool-wcag-aa-22-audit' ),
							'description' => __( 'Saved-content mode, block themes only. Checks the post inside the block template WordPress would use for it (custom template, single, page, singular or index), including the header, footer and other template parts. Combine with Render dynamic blocks to also check Navigation and other dynamic blocks in the template.', 'workflow-tool-wcag-aa-22-audit' ),
						),
						'min_score'  => array(
							'type'        => 'integer',
							'default'     => 0,
							'minimum'     => 0,
							'maximum'     => 100,
							'label'       => __( 'Minimum WCAG 2.2 AA readiness score', 'workflow-tool-wcag-aa-22-audit' ),
							'description' => __( 'The audit and the publishing gate fail when the readiness score (the share of applicable automated checks that passed, 0-100) is below this number, even if no individual check is an error. 0 turns the minimum off. The score is not a measure of conformance.', 'workflow-tool-wcag-aa-22-audit' ),
						),
						'fail_on'    => array(
							'type'        => 'string',
							'enum'        => self::STRICTNESS,
							'default'     => 'errors',
							'label'       => __( 'Fail on', 'workflow-tool-wcag-aa-22-audit' ),
							'description' => __( 'errors: only definite failures (such as missing alt text or insufficient contrast) fail the audit and block the gate; likely problems are reported as warnings. errors-and-warnings: warnings block too.', 'workflow-tool-wcag-aa-22-audit' ),
						),
					),
					'annotations'         => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/** Execute only against this site's saved post or its permalink; never accept an arbitrary URL or HTML. */
	public static function execute( array $input ): array|\WP_Error {
		$permission = self::can_execute( $input );
		if ( true !== $permission ) {
			return $permission;
		}
		$post = get_post( self::post_id( $input ) );
		if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) || ! is_post_type_viewable( $post->post_type ) || 'attachment' === $post->post_type ) {
			return new \WP_Error( 'wcag_unsupported', __( 'Save a post or page before running the accessibility audit.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		if ( ! class_exists( '\DOMDocument' ) ) {
			return new \WP_Error( 'wcag_dom_unavailable', __( 'The PHP DOM extension is required for the accessibility audit.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		$options = self::options(
			self::ID,
			array(
				'audit_mode'    => 'saved-content',
				'fail_on'       => 'errors',
				'min_score'     => 0,
				'render_blocks'    => false,
				'include_template' => false,
			)
		);
		$mode    = in_array( $options['audit_mode'], self::MODES, true ) ? $options['audit_mode'] : 'saved-content';
		$report  = array(
			'status'             => 'fail',
			'summary'            => '',
			'score'              => 0,
			'audit_complete'     => false,
			'audit_mode'         => $mode,
			'fail_on'            => in_array( $options['fail_on'], self::STRICTNESS, true ) ? $options['fail_on'] : 'errors',
			'min_score'          => is_numeric( $options['min_score'] ) ? max( 0, min( 100, (int) $options['min_score'] ) ) : 0,
			'conformance_target' => 'WCAG 2.2 Level AA',
			'url'                => (string) get_permalink( $post->ID ),
			'edit_url'           => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
			'checked_at'         => gmdate( 'c' ),
			'rubric_version'     => '1.1',
			'rendered'           => false,
			'template'           => '',
			'included'           => array(),
			'unchecked'          => array(),
			'counts'             => array(),
			'criteria'           => array(),
			'manual_review'      => array(),
			'checks'             => array(),
			'issues'             => array(),
			'blockers'           => array(),
		);
		$context = self::context( $post );

		if ( 'public' !== $mode ) {
			if ( strlen( $post->post_content ) > self::HTML_LIMIT ) {
				return new \WP_Error( 'wcag_too_large', __( 'Saved content larger than 3 MiB cannot be audited.', 'workflow-tool-wcag-aa-22-audit' ) );
			}
			$source = WCAG_Content::prepare( $post, true === $options['render_blocks'], true === $options['include_template'] );
			if ( is_wp_error( $source ) ) {
				return $source;
			}
			$report['rendered']  = $source['rendered'];
			$report['template']  = $source['template'];
			$context['template'] = '' !== $source['template'];
			$report['included']  = $source['included'];
			$report['unchecked'] = $source['unchecked'];
			return self::finish( $report, WCAG_Rules::run( WCAG_Document::load( $source['html'], false ), $context ), $source['notes'] );
		}

		$public = 'publish' === $post->post_status && '' === $post->post_password;
		if ( ! $public ) {
			return self::incomplete( $report, 'public-post', 'Published and public', 'Public mode audits the published page. Publish the post (without a password), or switch the audit to saved-content mode for drafts and publishing gates.' );
		}
		$response = self::fetch( $report['url'] );
		$ok       = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
		$body     = $ok ? wp_remote_retrieve_body( $response ) : '';
		$type     = $ok ? strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) ) : '';
		if ( ! $ok || ! str_contains( $type, 'text/html' ) || '' === trim( $body ) || strlen( $body ) > self::HTML_LIMIT ) {
			return self::incomplete( $report, 'public-http', 'Public page response', 'The anonymous permalink must return HTTP 200 HTML within 3 seconds and 3 MiB. Check redirects, login or edge restrictions, network failures and page size. WordPress safe HTTP refuses local and private-network hosts, so use saved-content mode there.' );
		}
		return self::finish( $report, WCAG_Rules::run( WCAG_Document::load( $body, true ), $context ), array() );
	}

	/**
	 * Theme colors and font sizes the editor's preset classes refer to.
	 *
	 * @param \WP_Post $post Post being audited.
	 */
	private static function context( \WP_Post $post ): array {
		$palette = array();
		$sizes   = array(
			'small'    => 13.0,
			'medium'   => 20.0,
			'large'    => 36.0,
			'x-large'  => 42.0,
			'xx-large' => 48.0,
		);
		if ( function_exists( 'wp_get_global_settings' ) ) {
			foreach ( (array) wp_get_global_settings( array( 'color', 'palette' ) ) as $origin ) {
				foreach ( (array) $origin as $entry ) {
					if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && is_string( $entry['color'] ) ) {
						$palette[ $entry['slug'] ] = $entry['color'];
					}
				}
			}
			foreach ( (array) wp_get_global_settings( array( 'typography', 'fontSizes' ) ) as $origin ) {
				foreach ( (array) $origin as $entry ) {
					if ( is_array( $entry ) && isset( $entry['slug'], $entry['size'] ) && is_string( $entry['size'] ) ) {
						$px = WCAG_Rules::px( $entry['size'] );
						if ( null === $px ) {
							unset( $sizes[ $entry['slug'] ] ); // Fluid clamp() sizes: unknown, so contrast findings are reported as warnings.
						} else {
							$sizes[ $entry['slug'] ] = $px;
						}
					}
				}
			}
		}
		$text = $background = null;
		if ( function_exists( 'wp_get_global_styles' ) ) {
			$styles     = (array) wp_get_global_styles( array( 'color' ) );
			$text       = is_string( $styles['text'] ?? null ) ? WCAG_Color::parse( $styles['text'], $palette ) : null;
			$background = is_string( $styles['background'] ?? null ) ? WCAG_Color::parse( $styles['background'], $palette ) : null;
		}
		return array(
			'title'              => self::plain_text( $post->post_title ),
			'palette'            => $palette,
			'font_sizes'         => $sizes,
			'text'               => $text && $text[3] >= 1 ? $text : array( 0.0, 0.0, 0.0, 1.0 ),
			'text_assumed'       => ! ( $text && $text[3] >= 1 ),
			'background'         => $background && $background[3] >= 1 ? $background : array( 255.0, 255.0, 255.0, 1.0 ),
			'background_assumed' => ! ( $background && $background[3] >= 1 ),
		);
	}

	/** Same-origin, anonymous bounded request. Redirects never escape this origin. */
	private static function fetch( string $url ): array|\WP_Error {
		$target = wp_parse_url( $url );
		$home   = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $target ) || ! is_array( $home ) || isset( $target['user'] ) || isset( $target['pass'] ) ||
			! in_array( $target['scheme'] ?? '', array( 'http', 'https' ), true ) ||
			strtolower( $target['host'] ?? '' ) !== strtolower( $home['host'] ?? '' ) ||
			( $target['scheme'] ?? '' ) !== ( $home['scheme'] ?? '' ) ||
			( $target['port'] ?? null ) !== ( $home['port'] ?? null ) ) {
			return new \WP_Error( 'wcag_unsafe_url', 'The audit requires a permalink on the current site origin.' );
		}
		return wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 3,
				'redirection'         => 0,
				'limit_response_size' => self::HTML_LIMIT + 1,
				'cookies'             => array(),
				'user-agent'          => 'WPVIP-WCAG-Audit/1.0',
				'headers'             => array( 'Accept' => 'text/html' ),
			)
		);
	}

	/** A public audit that could not inspect the page fails closed. */
	private static function incomplete( array $report, string $rule, string $label, string $message ): array {
		$report['blockers'][] = $rule;
		$report['issues'][]   = array(
			'rule'     => $label,
			'status'   => 'failed',
			'severity' => 'error',
			'message'  => $message,
		);
		$report['summary']    = 'WCAG 2.2 AA audit incomplete: the public page could not be checked. Fail.';
		return $report;
	}

	/**
	 * Turn rule results into the Workflows report: verdict, score, per-criterion status and ordered issues.
	 *
	 * @param array    $report  Report being built.
	 * @param array    $results Rule results from WCAG_Rules::run().
	 * @param string[] $notes   Extra informational notes.
	 */
	private static function finish( array $report, array $results, array $notes ): array {
		$counts = array(
			'passed'         => 0,
			'failed'         => 0,
			'errors'         => 0,
			'warnings'       => 0,
			'review'         => 0,
			'not_applicable' => 0,
		);
		$strict = 'errors-and-warnings' === $report['fail_on'];
		$fails  = $reviews = $passes = $skipped = array();
		$status = array();
		foreach ( $results as $result ) {
			$name = self::criteria_label( $result['criteria'] ) . ': ' . $result['label'];
			switch ( $result['status'] ) {
				case 'fail':
					++$counts['failed'];
					++$counts[ 'error' === $result['severity'] ? 'errors' : 'warnings' ];
					if ( 'error' === $result['severity'] || $strict ) {
						$report['blockers'][] = $result['rule'];
					}
					$fails[ 'error' === $result['severity'] ? 0 : 1 ][] = array(
						'rule'     => $name,
						'status'   => 'failed',
						'severity' => $result['severity'],
						'blocks'   => $result['blocks'],
						'message'  => $result['message'],
					);
					break;
				case 'review':
					++$counts['review'];
					$reviews[] = array(
						'rule'     => $name,
						'severity' => 'info',
						'message'  => 'Needs manual review. ' . $result['message'],
					);
					break;
				case 'pass':
					++$counts['passed'];
					$passes[] = array(
						'rule'     => $name,
						'status'   => 'passed',
						'severity' => 'info',
						'message'  => $result['message'],
					);
					break;
				default:
					++$counts['not_applicable'];
					$skipped[] = $result['label'];
			}
			foreach ( $result['criteria'] as $criterion ) {
				$status[ $criterion ][] = $result['status'];
			}
		}

		$manual = array();
		foreach ( WCAG_Rules::CRITERIA as $number => [ $title, $level, $new ] ) {
			$seen = $status[ $number ] ?? array();
			$state = match ( true ) {
				in_array( 'fail', $seen, true ) => 'failed',
				in_array( 'review', $seen, true ) => 'needs-review',
				in_array( 'pass', $seen, true ) => 'automated-checks-passed',
				(bool) $seen => 'not-applicable',
				default => 'manual-only',
			};
			$report['criteria'][ $number ] = array(
				'name'   => $title,
				'level'  => $level,
				'new'    => $new,
				'status' => $state,
			);
			if ( 'manual-only' === $state ) {
				$manual[] = $number . ' ' . $title . ( $new ? ' (new in 2.2)' : '' );
			}
		}
		$report['manual_review'] = $manual;
		$report['counts']        = $counts;
		$report['checks']        = $results;
		$scored                  = $counts['passed'] + $counts['failed'];
		$report['score']         = $scored > 0 ? (int) round( 100 * $counts['passed'] / $scored ) : 100;
		$report['audit_complete'] = true;
		$below = $report['score'] < $report['min_score'];
		if ( $below ) {
			$report['blockers'][] = 'min-score';
			$fails[0][]           = array(
				'rule'     => 'WCAG 2.2 AA readiness score',
				'status'   => 'failed',
				'severity' => 'error',
				'message'  => sprintf( 'The readiness score is %d, below the required minimum of %d. Fix the failed checks listed here (or review the warnings) and run the audit again.', $report['score'], $report['min_score'] ),
			);
		}
		$report['status']        = $report['blockers'] ? 'fail' : ( $counts['failed'] ? 'warning' : 'pass' );

		// Coverage notes come before passes: they qualify what a pass means.
		$coverage = array_map(
			static fn( array $note ): array => array(
				'rule'     => $note['rule'],
				'severity' => 'info',
				'message'  => $note['message'],
			),
			$notes
		);
		$issues   = array_merge( $fails[0] ?? array(), $fails[1] ?? array(), $coverage, $reviews, $passes );
		if ( $skipped ) {
			$issues[] = array(
				'rule'     => 'Not applicable',
				'severity' => 'info',
				'message'  => 'Nothing to check for: ' . implode( '; ', $skipped ) . '.' . ( 'public' !== $report['audit_mode'] ? ' Page-level checks (page language, zoom, timed refresh, skip links) run in public mode.' : '' ),
			);
		}
		$issues[] = array(
			'rule'     => 'Manual review required',
			'severity' => 'info',
			'message'  => count( $manual ) . ' WCAG 2.2 A/AA success criteria have no automated check here and need a person to check them: ' . implode( '; ', $manual ) . '. Every criterion with automated checks also needs human judgement (for example, whether alt text is accurate).',
		);
		$report['issues'] = $issues;

		$report['summary'] = sprintf(
			'WCAG 2.2 AA automated checks (%s): %d passed, %d failed (%d error%s, %d warning%s), %d to review; score %d/100. %s %d criteria need manual review. Automated checks cannot confirm conformance.',
			'public' === $report['audit_mode'] ? 'public page' : 'saved content',
			$counts['passed'],
			$counts['failed'],
			$counts['errors'],
			1 === $counts['errors'] ? '' : 's',
			$counts['warnings'],
			1 === $counts['warnings'] ? '' : 's',
			$counts['review'],
			$report['score'],
			match ( $report['status'] ) {
				'pass' => 'Pass.',
				'warning' => 'Pass with warnings: review them before publishing.',
				default => 'Fail: fix the ' . ( $strict ? 'errors and warnings' : 'errors' ) . ', save and rerun.',
			},
			count( $manual )
		);
		if ( $below ) {
			$report['summary'] = sprintf( 'Score %d is below the required minimum of %d. ', $report['score'], $report['min_score'] ) . $report['summary'];
		}
		return $report;
	}

	/** "1.1.1 Non-text Content (A)" plus any secondary criteria numbers. */
	private static function criteria_label( array $criteria ): string {
		$primary = $criteria[0];
		$meta    = WCAG_Rules::CRITERIA[ $primary ] ?? array( '', '', false );
		$label   = $primary . ' ' . $meta[0] . ' (' . $meta[1] . ')';
		return count( $criteria ) > 1 ? $label . ' + ' . implode( ', ', array_slice( $criteria, 1 ) ) : $label;
	}
}
