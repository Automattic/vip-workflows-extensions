<?php
/** Saved-content preparation: referenced patterns, template parts and optional rendering.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/**
 * Builds the markup that saved-content mode audits.
 *
 * By default it inlines the saved markup of synced patterns (core/block),
 * registered patterns (core/pattern) and template parts (core/template-part)
 * without running any block PHP, and names every dynamic block it could not
 * check. With rendering turned on it runs do_blocks() and do_shortcode() instead.
 */
final class WCAG_Content {
	private const LIMIT     = 3145728;
	private const MAX_DEPTH = 8;
	/** Opening, closing or self-closing block delimiter comment, as parse_blocks() reads them. */
	private const DELIMITER  = '/<!--\s+(\/)?wp:([a-z][a-z0-9_-]*(?:\/[a-z][a-z0-9_-]*)?)\s+(\{(?:(?!-->).)*?\}\s+)?(\/)?-->/s';
	private const REFERENCES = array( 'core/block', 'core/pattern', 'core/template-part' );
	/** Used only when the block registry is unavailable. */
	private const DYNAMIC = array( 'core/query', 'core/navigation', 'core/comments', 'core/post-template' );
	private const PART_TAGS = array( 'header', 'footer', 'main', 'section', 'article', 'aside', 'div' );

	/**
	 * Markup to audit and the notes that explain what it covers.
	 *
	 * @param \WP_Post $post     Saved post.
	 * @param bool     $render   Render dynamic blocks and shortcodes.
	 * @param bool     $template Place the post inside the block template WordPress would use for it.
	 * @return array{html: string, notes: array<int, array{rule: string, message: string}>, included: string[], unchecked: array<string, int>, rendered: bool, template: string}|\WP_Error
	 */
	public static function prepare( \WP_Post $post, bool $render, bool $template = false ): array|\WP_Error {
		$marked = self::mark_blocks( $post->post_content, $render );
		$markup = $marked;
		$label  = '';
		$frame  = array();
		$placed = false;
		if ( $template ) {
			$found = self::template( $post );
			if ( null === $found ) {
				$frame[] = array(
					'rule'    => 'Template not included',
					'message' => '"Include theme template" is on, but no block template was found for this post: the theme is not a block theme, or no template matches. The site header and footer are only checked in public mode after publishing.',
				);
			} else {
				$label   = $found['label'];
				$markup  = self::source( 'template', $found['id'], self::frame( $found['content'], $marked, $render, $placed ) );
				$frame[] = array(
					'rule'    => 'Included template',
					'message' => 'Checked the post inside the ' . $label . ', the template WordPress would use for it now, with its header, footer and other template parts.' . ( $placed ? '' : ' The template has no Post Content block, so the post was added after it.' ) . ' The page <head> (title, language and viewport) is only checked in public mode.',
				);
			}
		}

		if ( $render ) {
			$html = self::render( $post, $markup, $marked );
			if ( is_wp_error( $html ) ) {
				return $html;
			}
			return array(
				'html'      => $html,
				'notes'     => array_merge(
					$frame,
					array(
						array(
							'rule'    => 'Rendered content',
							'message' => 'Dynamic blocks, synced patterns, template parts and shortcodes were rendered for this audit ("Render dynamic blocks" is on).' . ( '' === $label ? ' The theme template around the post is not included; turn on "Include theme template", or run public mode after publishing, to check the site header and footer.' : '' ),
						),
					)
				),
				'included'  => array(),
				'unchecked' => array(),
				'rendered'  => true,
				'template'  => $label,
			);
		}

		$included = $missing = array();
		$html     = self::expand( $markup, array(), 0, $included, $missing );
		if ( strlen( $html ) > self::LIMIT ) {
			return new \WP_Error( 'wcag_too_large', __( 'Saved content, including its patterns and template parts, is larger than 3 MiB and cannot be audited.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		$notes = $frame;
		if ( $included ) {
			$notes[] = array(
				'rule'    => 'Included content',
				'message' => 'Checked the saved markup of ' . self::listing( array_count_values( $included ) ) . '.',
			);
		}
		if ( $missing ) {
			$notes[] = array(
				'rule'    => 'Content not found',
				'message' => 'Could not load ' . self::listing( array_count_values( $missing ) ) . ', so it was not checked. Check that it still exists and is not in the trash.',
			);
		}
		$unchecked = self::dynamic_blocks( $html );
		$codes     = function_exists( 'get_shortcode_regex' ) ? (int) preg_match_all( '/' . get_shortcode_regex() . '/', $html ) : 0;
		if ( $codes ) {
			$unchecked[ 'shortcode' ] = $codes;
		}
		if ( $unchecked ) {
			$notes[] = array(
				'rule'    => 'Content not checked',
				'message' => 'Not checked because it is generated when the page is viewed: ' . self::listing( $unchecked ) . '. Turn on "Render dynamic blocks" in the audit settings to include it in drafts, or run public mode after publishing.',
			);
		}
		return array(
			'html'      => $html,
			'notes'     => $notes,
			'included'  => array_values( array_unique( $included ) ),
			'unchecked' => $unchecked,
			'rendered'  => false,
			'template'  => $label,
		);
	}

	/**
	 * Bracket markup with comments naming where it is edited (template, template part or synced
	 * pattern), so a finding inside it can link to that editor. Inert HTML comments.
	 */
	public static function source( string $type, string $id, string $markup ): string {
		return '<!--wcag-open:' . $type . ':' . strtr( $id, array( '%' => '%25', '-' => '%2D' ) ) . '-->' . $markup . '<!--wcag-close-->';
	}

	/**
	 * Leave a marker comment after every block's opening delimiter naming the block's position
	 * in the post (for example "2.0" is the first inner block of the third block), so a finding
	 * can be traced to the block the editor can select. Markers are inert HTML comments.
	 *
	 * @param string $content Saved post markup.
	 * @param bool   $as_attr Rendering follows: carry the position as a block attribute instead, which the render_block filter turns into the marker.
	 */
	public static function mark_blocks( string $content, bool $as_attr = false ): string {
		$counts = array( 0 );
		$path   = array();
		$marked = preg_replace_callback(
			self::DELIMITER,
			static function ( array $match ) use ( &$counts, &$path, $as_attr ): string {
				if ( '' !== $match[1] ) {
					array_pop( $path );
					array_pop( $counts );
					return $match[0];
				}
				$level  = count( $counts ) - 1;
				$here   = implode( '.', array_merge( $path, array( $counts[ $level ] ) ) );
				++$counts[ $level ];
				if ( empty( $match[4] ) ) {
					$path[]   = $counts[ $level ] - 1;
					$counts[] = 0;
				}
				if ( $as_attr ) {
					$attributes = json_decode( trim( $match[3] ?? '' ) ?: '{}', true );
					$attributes = is_array( $attributes ) ? $attributes : array();
					$attributes['wcagPath'] = $here;
					return '<!-- wp:' . $match[2] . ' ' . wp_json_encode( $attributes ) . ( empty( $match[4] ) ? ' -->' : ' /-->' );
				}
				return $match[0] . '<!--wcag-block:' . $here . ':' . self::name( $match[2] ) . '-->';
			},
			$content
		);
		return $marked ?? $content;
	}

	/**
	 * The block template WordPress would use for this post today, following the template hierarchy.
	 *
	 * @param \WP_Post $post Saved post.
	 * @return array{id: string, content: string, label: string}|null Null for classic themes or when nothing matches.
	 */
	private static function template( \WP_Post $post ): ?array {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() || ! function_exists( 'get_block_templates' ) ) {
			return null;
		}
		$slugs  = array();
		$custom = function_exists( 'get_page_template_slug' ) ? (string) get_page_template_slug( $post ) : '';
		if ( '' !== $custom && 'default' !== $custom ) {
			$slugs[] = $custom;
		}
		$name = (string) $post->post_name;
		if ( 'page' === $post->post_type ) {
			array_push( $slugs, '' !== $name ? 'page-' . $name : '', 'page-' . $post->ID, 'page' );
		} else {
			array_push( $slugs, '' !== $name ? 'single-' . $post->post_type . '-' . $name : '', 'single-' . $post->post_type, 'single' );
		}
		array_push( $slugs, 'singular', 'index' );
		$slugs     = array_values( array_unique( array_filter( $slugs ) ) );
		$templates = array();
		foreach ( (array) get_block_templates( array( 'slug__in' => $slugs ), 'wp_template' ) as $candidate ) {
			if ( is_object( $candidate ) && isset( $candidate->slug, $candidate->content ) && is_string( $candidate->content ) ) {
				$templates[ $candidate->slug ] ??= $candidate;
			}
		}
		foreach ( $slugs as $slug ) {
			if ( isset( $templates[ $slug ] ) ) {
				$title = is_string( $templates[ $slug ]->title ?? null ) ? trim( $templates[ $slug ]->title ) : '';
				return array(
					'id'      => ( is_string( $templates[ $slug ]->theme ?? null ) && '' !== $templates[ $slug ]->theme ? $templates[ $slug ]->theme : ( function_exists( 'get_stylesheet' ) ? get_stylesheet() : '' ) ) . '//' . $slug,
					'content' => $templates[ $slug ]->content,
					'label'   => 'template ' . ( '' !== $title ? '"' . $title . '" ' : '' ) . '(' . $slug . ')',
				);
			}
		}
		return null;
	}

	/**
	 * Put the post where the template's Post Content block is. Rendering keeps the block,
	 * which renders the post itself; without rendering the saved post markup replaces it.
	 *
	 * @param string    $template Template markup.
	 * @param string    $content  Saved post markup.
	 * @param bool      $render   Rendering will follow.
	 * @param bool|null $placed   Set to whether a Post Content block was found.
	 */
	private static function frame( string $template, string $content, bool $render, ?bool &$placed ): string {
		$pattern = '/<!--\s+wp:(?:core\/)?post-content(?:\s+\{(?:(?!-->).)*?\})?\s+(?:\/-->|-->.*?<!--\s+\/wp:(?:core\/)?post-content\s+-->)/s';
		$placed  = (bool) preg_match( $pattern, $template );
		if ( ! $placed ) {
			return $template . "\n" . $content;
		}
		if ( $render ) {
			return $template;
		}
		return preg_replace_callback( $pattern, static fn(): string => $content, $template, 1 ) ?? $template;
	}

	/**
	 * Replace self-closing reference blocks with the saved markup they point to, recursively.
	 *
	 * @param string              $html     Block markup.
	 * @param array<string, bool> $seen     References already being expanded (cycle guard).
	 * @param int                 $depth    Nesting depth.
	 * @param string[]            $included Labels of expanded references.
	 * @param string[]            $missing  Labels of references that could not be loaded.
	 */
	private static function expand( string $html, array $seen, int $depth, array &$included, array &$missing ): string {
		$expanded = preg_replace_callback(
			self::DELIMITER,
			static function ( array $match ) use ( $seen, $depth, &$included, &$missing ): string {
				$name = self::name( $match[2] );
				if ( '' !== $match[1] || empty( $match[4] ) || ! in_array( $name, self::REFERENCES, true ) ) {
					return $match[0];
				}
				$attributes = json_decode( trim( $match[3] ?? '' ) ?: '{}', true );
				[ $key, $label, $content ] = self::resolve( $name, is_array( $attributes ) ? $attributes : array() );
				if ( null === $content ) {
					$missing[] = $label; // Reported once as not found, not again as unchecked.
					return '';
				}
				if ( isset( $seen[ $key ] ) || $depth >= self::MAX_DEPTH ) {
					$missing[] = $label . ' (it includes itself or is nested too deeply)';
					return '';
				}
				$included[] = $label;
				$inner      = self::expand( $content, $seen + array( $key => true ), $depth + 1, $included, $missing );
				if ( 'core/block' === $name ) {
					return self::source( 'synced', (string) ( $attributes['ref'] ?? '' ), $inner );
				}
				if ( 'core/template-part' !== $name ) {
					return $inner;
				}
				// Template parts render inside their tagName, which gives headers and footers their landmark roles.
				$tag = strtolower( (string) ( $attributes['tagName'] ?? 'div' ) );
				$tag = in_array( $tag, self::PART_TAGS, true ) ? $tag : 'div';
				return self::source( 'part', substr( $key, 5 ), '<' . $tag . '>' . $inner . '</' . $tag . '>' );
			},
			$html
		);
		return $expanded ?? $html;
	}

	/**
	 * Load a reference block's saved markup.
	 *
	 * @param string $name       Block name.
	 * @param array  $attributes Block attributes.
	 * @return array{0: string, 1: string, 2: ?string} Cycle key, editor-facing label, markup or null.
	 */
	private static function resolve( string $name, array $attributes ): array {
		if ( 'core/block' === $name ) {
			$ref     = is_numeric( $attributes['ref'] ?? null ) ? (int) $attributes['ref'] : 0;
			$pattern = $ref > 0 ? get_post( $ref ) : null;
			if ( $pattern instanceof \WP_Post && 'wp_block' === $pattern->post_type && 'trash' !== $pattern->post_status ) {
				$title = trim( $pattern->post_title );
				return array( 'block:' . $ref, 'synced pattern ' . ( '' !== $title ? '"' . $title . '" ' : '' ) . '(#' . $ref . ')', $pattern->post_content );
			}
			return array( 'block:' . $ref, 'synced pattern #' . $ref, null );
		}
		$slug = is_string( $attributes['slug'] ?? null ) ? $attributes['slug'] : '';
		if ( 'core/pattern' === $name ) {
			$registered = '' !== $slug && class_exists( '\WP_Block_Patterns_Registry' ) ? \WP_Block_Patterns_Registry::get_instance()->get_registered( $slug ) : null;
			$content    = is_array( $registered ) && is_string( $registered['content'] ?? null ) ? $registered['content'] : null;
			return array( 'pattern:' . $slug, 'pattern "' . $slug . '"', $content );
		}
		$theme    = is_string( $attributes['theme'] ?? null ) && '' !== $attributes['theme'] ? $attributes['theme'] : ( function_exists( 'get_stylesheet' ) ? get_stylesheet() : '' );
		$template = '' !== $slug && function_exists( 'get_block_template' ) ? get_block_template( $theme . '//' . $slug, 'wp_template_part' ) : null;
		$content  = is_object( $template ) && isset( $template->content ) && is_string( $template->content ) ? $template->content : null;
		return array( 'part:' . $theme . '//' . $slug, 'template part "' . $slug . '"', $content );
	}

	/**
	 * Top-level dynamic blocks left in the markup; blocks inside a dynamic block are its output, not counted again.
	 *
	 * @param string $html Block markup.
	 * @return array<string, int> Label => count.
	 */
	public static function dynamic_blocks( string $html ): array {
		$found  = array();
		$stack  = array();
		$inside = 0;
		preg_match_all( self::DELIMITER, $html, $tokens, PREG_SET_ORDER );
		foreach ( $tokens as $token ) {
			if ( '' !== $token[1] ) {
				if ( true === array_pop( $stack ) ) {
					--$inside;
				}
				continue;
			}
			$name         = self::name( $token[2] );
			$self_closing = ! empty( $token[4] );
			$dynamic      = self::is_dynamic( $name, $self_closing );
			if ( $dynamic && 0 === $inside ) {
				$label           = self::block_label( $name );
				$found[ $label ] = ( $found[ $label ] ?? 0 ) + 1;
			}
			if ( ! $self_closing ) {
				$stack[] = $dynamic;
				$inside += $dynamic ? 1 : 0;
			}
		}
		return $found;
	}

	private static function is_dynamic( string $name, bool $self_closing ): bool {
		if ( class_exists( '\WP_Block_Type_Registry' ) ) {
			$type = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
			if ( $type ) {
				return (bool) $type->is_dynamic();
			}
		}
		// An unregistered block with no saved markup has nothing to check either way.
		return $self_closing || in_array( $name, self::DYNAMIC, true );
	}

	public static function block_label( string $name ): string {
		$title = '';
		if ( class_exists( '\WP_Block_Type_Registry' ) ) {
			$type  = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
			$title = $type && is_string( $type->title ?? null ) ? $type->title : '';
		}
		$label = match ( $name ) {
			'core/block' => 'Synced pattern',
			'core/pattern' => 'Pattern',
			'core/template-part' => 'Template part',
			default => $title,
		};
		return '' !== $label ? $label . ' (' . $name . ')' : $name;
	}

	/** Block names without a namespace are core blocks. */
	private static function name( string $name ): string {
		return str_contains( $name, '/' ) ? $name : 'core/' . $name;
	}

	/** "Query Loop (core/query) ×2, shortcode ×1". */
	private static function listing( array $counts ): string {
		$items = array();
		foreach ( $counts as $label => $count ) {
			$items[] = $label . ( $count > 1 ? ' ×' . $count : '' );
		}
		return implode( ', ', $items );
	}

	/**
	 * Render the saved post (or its template) the way the front end does.
	 * Output buffering keeps a block that echoes from corrupting the REST response.
	 */
	private static function render( \WP_Post $post, string $markup, string $marked ): string|\WP_Error {
		if ( ! function_exists( 'do_blocks' ) ) {
			return new \WP_Error( 'wcag_render_unavailable', __( 'Block rendering is not available.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		$previous = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Blocks read the current post; restored in finally.
		setup_postdata( $post );
		// Rendered blocks carry no delimiters, so each one is preceded by a marker naming its position in the post.
		$mark = static function ( string $html, array $block ): string {
			if ( '' === trim( $html ) ) {
				return $html;
			}
			$attributes = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( 'core/template-part' === ( $block['blockName'] ?? '' ) && is_string( $attributes['slug'] ?? null ) ) {
				$theme = is_string( $attributes['theme'] ?? null ) && '' !== $attributes['theme'] ? $attributes['theme'] : ( function_exists( 'get_stylesheet' ) ? get_stylesheet() : '' );
				$html  = self::source( 'part', $theme . '//' . $attributes['slug'], $html );
			} elseif ( 'core/block' === ( $block['blockName'] ?? '' ) && is_numeric( $attributes['ref'] ?? null ) ) {
				$html = self::source( 'synced', (string) (int) $attributes['ref'], $html );
			}
			$path = $attributes['wcagPath'] ?? null;
			return is_string( $path ) ? '<!--wcag-block:' . $path . ':' . ( $block['blockName'] ?? '' ) . '-->' . $html : $html;
		};
		// A template's Post Content block loads the saved post itself; hand it the marked copy.
		$swap = static fn( string $content ): string => $content === $post->post_content ? $marked : $content;
		add_filter( 'render_block', $mark, 10, 2 );
		add_filter( 'the_content', $swap, 1 );
		ob_start();
		try {
			$html = do_shortcode( do_blocks( $markup ) );
		} catch ( \Throwable $error ) {
			return new \WP_Error( 'wcag_render_failed', __( 'A block or shortcode failed to render, so the audit could not run. Turn off "Render dynamic blocks" or fix the failing block.', 'workflow-tool-wcag-aa-22-audit' ) );
		} finally {
			ob_end_clean();
			remove_filter( 'render_block', $mark, 10 );
			remove_filter( 'the_content', $swap, 1 );
			$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the caller's post.
			if ( $previous instanceof \WP_Post ) {
				setup_postdata( $previous );
			}
		}
		if ( strlen( $html ) > self::LIMIT ) {
			return new \WP_Error( 'wcag_too_large', __( 'Rendered content is larger than 3 MiB and cannot be audited.', 'workflow-tool-wcag-aa-22-audit' ) );
		}
		return $html;
	}
}
