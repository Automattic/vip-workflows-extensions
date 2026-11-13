<?php
/**
 * Shared post access and input handling.
 *
 * @package WorkflowToolWcagAa22Audit
 */

declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Common contract for tools that operate on saved posts. */
abstract class Post_Tool {
	/** Schema shared by both editor and transition invocations. The editor also sends the tool's options, so extra properties are allowed and ignored. */
	public static function input_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		);
	}

	/**
	 * Check access to the specific post, including direct callback invocations.
	 *
	 * @param array $input Ability input.
	 */
	public static function can_execute( array $input ): bool|\WP_Error {
		$id = self::post_id( $input );
		if ( null === $id ) {
			return new \WP_Error( 'wcag_invalid_post', __( 'A positive integer post_id is required.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			return new \WP_Error( 'wcag_forbidden', __( 'You cannot edit this post.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		if ( ! get_post( $id ) instanceof \WP_Post ) {
			return new \WP_Error( 'wcag_missing_post', __( 'The post could not be found.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		return true;
	}

	/**
	 * The requested post ID. Workflows transitions send an integer; a REST GET
	 * run sends the same schema-valid value as a digit string.
	 *
	 * @param array $input Ability input.
	 */
	protected static function post_id( array $input ): ?int {
		$id = $input['post_id'] ?? null;
		if ( is_string( $id ) && ctype_digit( $id ) ) {
			$id = (int) $id;
		}
		return is_int( $id ) && $id >= 1 ? $id : null;
	}

	/**
	 * Read stored options, falling back to explicit defaults.
	 *
	 * @param string $id       Ability ID.
	 * @param array  $defaults Default options.
	 */
	protected static function options( string $id, array $defaults ): array {
		return array_replace( $defaults, \VIPWorkflows\Abilities\AbilitySettings::get_instance()->get_options( $id ) );
	}

	/**
	 * Plain text without rendering shortcodes or dynamic blocks.
	 *
	 * @param string $content Stored content.
	 */
	public static function plain_text( string $content ): string {
		$content = strip_shortcodes( $content );
		$content = preg_replace( '/<\/(?:p|div|h[1-6]|li|blockquote)>|<br\s*\/?>/i', ' ', $content );
		$content = html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', $content ) ?? '' );
	}
}
