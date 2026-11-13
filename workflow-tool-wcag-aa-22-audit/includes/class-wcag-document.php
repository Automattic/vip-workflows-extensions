<?php
/** DOM loading and accessible-name helpers for the WCAG audit.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Offline, bounded HTML inspection. Never fetches subresources or runs scripts. */
final class WCAG_Document {
	/** Elements whose role takes its accessible name from content. */
	private const NAME_FROM_CONTENT = array( 'a', 'button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'summary', 'legend', 'caption', 'th', 'td', 'option' );
	private const ROLES_FROM_CONTENT = array( 'button', 'link', 'heading', 'tab', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'option', 'cell', 'columnheader', 'rowheader', 'checkbox', 'radio', 'switch', 'tooltip', 'treeitem', 'gridcell', 'row' );

	public \DOMXPath $xpath;
	/** @var array<string, \DOMElement[]> */
	private array $ids = array();
	/** @var array<string, array<string, array<string, string>>> Description => editor locations it was found in. */
	private array $blocks = array();

	/**
	 * @param \DOMDocument $dom  Parsed document.
	 * @param bool         $page Whether this is a full rendered page rather than a saved-content fragment.
	 */
	private function __construct( public \DOMDocument $dom, public bool $page ) {
		$this->xpath = new \DOMXPath( $dom );
		// Inert content never reaches users as rendered markup, so it must not pass or fail checks.
		foreach ( $this->xpath->query( '//script|//style|//template|//noscript' ) as $node ) {
			$node->parentNode?->removeChild( $node );
		}
		foreach ( $this->xpath->query( '//*[@id]' ) as $element ) {
			$this->ids[ $element->getAttribute( 'id' ) ][] = $element;
		}
	}

	/**
	 * Parse saved block content (a fragment) or a full rendered page.
	 *
	 * @param string $html Markup.
	 * @param bool   $page Full page.
	 */
	public static function load( string $html, bool $page ): self {
		$dom      = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		try {
			$markup = $page && '' !== trim( $html ) ? $html : '<html><body>' . ( '' === trim( $html ) ? ' ' : $html ) . '</body></html>';
			$dom->loadHTML( '<?xml encoding="UTF-8">' . $markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		return new self( $dom, $page );
	}

	/**
	 * Elements matching an XPath query, excluding those not exposed to users.
	 *
	 * @param string $query          XPath.
	 * @param bool   $include_hidden Keep hidden elements.
	 * @return \DOMElement[]
	 */
	public function elements( string $query, bool $include_hidden = false ): array {
		$found = array();
		foreach ( $this->xpath->query( $query ) ?: array() as $node ) {
			if ( $node instanceof \DOMElement && ( $include_hidden || ! $this->is_hidden( $node ) ) ) {
				$found[] = $node;
			}
		}
		return $found;
	}

	/**
	 * Descendants of one element matching a relative XPath query, hidden ones included.
	 *
	 * @param string      $query   Relative XPath, such as ".//tr".
	 * @param \DOMElement $context Context element.
	 * @return \DOMElement[]
	 */
	public function within( string $query, \DOMElement $context ): array {
		return array_values( array_filter( iterator_to_array( $this->xpath->query( $query, $context ) ?: array() ), static fn( $node ) => $node instanceof \DOMElement ) );
	}

	public function by_id( string $id ): ?\DOMElement {
		return $this->ids[ $id ][0] ?? null;
	}

	public function id_count( string $id ): int {
		return count( $this->ids[ $id ] ?? array() );
	}

	/** @return array<string, int> Every id and how often it occurs. */
	public function id_counts(): array {
		return array_map( 'count', $this->ids );
	}

	/** Hidden from everyone, including assistive technology, through this element or an ancestor. */
	public function is_hidden( \DOMElement $element ): bool {
		for ( $node = $element; $node instanceof \DOMElement; $node = $node->parentNode ) {
			if ( $this->hidden_self( $node ) ) {
				return true;
			}
		}
		return false;
	}

	/** Hidden by this element's own attributes. */
	public function hidden_self( \DOMElement $element ): bool {
		if ( $element->hasAttribute( 'hidden' ) || 'true' === strtolower( trim( $element->getAttribute( 'aria-hidden' ) ) ) ) {
			return true;
		}
		if ( 'input' === strtolower( $element->tagName ) && 'hidden' === self::input_type( $element ) ) {
			return true;
		}
		$style = self::style( $element );
		return 'none' === ( $style['display'] ?? '' ) || in_array( $style['visibility'] ?? '', array( 'hidden', 'collapse' ), true );
	}

	/**
	 * Parse an inline style attribute into lowercase property => value pairs.
	 *
	 * @return array<string, string>
	 */
	public static function style( \DOMElement $element ): array {
		$styles = array();
		foreach ( explode( ';', $element->getAttribute( 'style' ) ) as $declaration ) {
			if ( ! str_contains( $declaration, ':' ) ) {
				continue;
			}
			[ $property, $value ] = array_map( 'trim', explode( ':', $declaration, 2 ) );
			if ( '' !== $property && '' !== $value ) {
				$styles[ strtolower( $property ) ] = trim( preg_replace( '/\s*!important\s*$/i', '', $value ) ?? $value );
			}
		}
		return $styles;
	}

	/** @return string[] Class tokens. */
	public static function classes( \DOMElement $element ): array {
		return array_values( array_filter( preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) ) ?: array() ) );
	}

	/** Explicit first role token, lowercased. */
	public static function role( \DOMElement $element ): string {
		return strtolower( (string) strtok( trim( $element->getAttribute( 'role' ) ), " \t\n" ) );
	}

	/** Normalized input type; unknown or missing types are text. */
	public static function input_type( \DOMElement $element ): string {
		$type = strtolower( trim( $element->getAttribute( 'type' ) ) );
		return '' === $type ? 'text' : $type;
	}

	/** Reachable with the Tab key. */
	public static function is_focusable( \DOMElement $element ): bool {
		if ( $element->hasAttribute( 'disabled' ) ) {
			return false;
		}
		$tabindex = trim( $element->getAttribute( 'tabindex' ) );
		if ( '' !== $tabindex && is_numeric( $tabindex ) ) {
			return (int) $tabindex >= 0;
		}
		$tag = strtolower( $element->tagName );
		return match ( $tag ) {
			'a', 'area' => $element->hasAttribute( 'href' ),
			'button', 'select', 'textarea', 'summary', 'iframe' => true,
			'input' => 'hidden' !== self::input_type( $element ),
			'audio', 'video' => $element->hasAttribute( 'controls' ),
			default => in_array( strtolower( $element->getAttribute( 'contenteditable' ) ), array( '', 'true', 'plaintext-only' ), true ) && $element->hasAttribute( 'contenteditable' ),
		};
	}

	/** Natively interactive, whatever its tabindex. */
	public static function is_interactive( \DOMElement $element ): bool {
		$tag = strtolower( $element->tagName );
		return in_array( $tag, array( 'button', 'select', 'textarea', 'summary', 'details' ), true )
			|| ( 'input' === $tag && 'hidden' !== self::input_type( $element ) )
			|| ( in_array( $tag, array( 'a', 'area' ), true ) && $element->hasAttribute( 'href' ) );
	}

	/**
	 * A simplified accessible-name computation (W3C accname): aria-labelledby,
	 * aria-label, native label or alternative, content for roles that allow it, then title.
	 */
	public function name( \DOMElement $element ): string {
		$tag = strtolower( $element->tagName );
		$ids = trim( $element->getAttribute( 'aria-labelledby' ) );
		if ( '' !== $ids ) {
			$parts = array();
			foreach ( preg_split( '/\s+/', $ids ) ?: array() as $id ) {
				$reference = $this->by_id( $id );
				if ( $reference ) {
					$parts[] = $this->content_name( $reference, null, 0, true );
				}
			}
			$name = self::normalize( implode( ' ', $parts ) );
			if ( '' !== $name ) {
				return $name;
			}
		}
		$label = self::normalize( $element->getAttribute( 'aria-label' ) );
		if ( '' !== $label ) {
			return $label;
		}
		if ( in_array( $tag, array( 'img', 'area' ), true ) || ( 'input' === $tag && 'image' === self::input_type( $element ) ) ) {
			$alt = self::normalize( $element->getAttribute( 'alt' ) );
			if ( '' !== $alt ) {
				return $alt;
			}
		}
		if ( in_array( $tag, array( 'input', 'select', 'textarea' ), true ) ) {
			$type = self::input_type( $element );
			if ( 'input' === $tag && in_array( $type, array( 'submit', 'reset', 'button' ), true ) ) {
				$value = self::normalize( $element->getAttribute( 'value' ) );
				if ( '' !== $value ) {
					return $value;
				}
				if ( 'button' !== $type ) {
					return 'submit' === $type ? 'Submit' : 'Reset';
				}
			}
			$labels = self::normalize( implode( ' ', $this->labels( $element ) ) );
			if ( '' !== $labels ) {
				return $labels;
			}
		}
		if ( 'svg' === $tag ) {
			foreach ( $element->childNodes as $child ) {
				if ( $child instanceof \DOMElement && 'title' === strtolower( $child->tagName ) && '' !== self::normalize( $child->textContent ) ) {
					return self::normalize( $child->textContent );
				}
			}
		}
		if ( 'fieldset' === $tag || 'figure' === $tag || 'table' === $tag ) {
			$caption = array( 'fieldset' => 'legend', 'figure' => 'figcaption', 'table' => 'caption' )[ $tag ];
			foreach ( $element->childNodes as $child ) {
				if ( $child instanceof \DOMElement && $caption === strtolower( $child->tagName ) ) {
					return self::normalize( $this->content_name( $child ) );
				}
			}
		}
		if ( in_array( $tag, self::NAME_FROM_CONTENT, true ) || in_array( self::role( $element ), self::ROLES_FROM_CONTENT, true ) ) {
			$text = self::normalize( $this->content_name( $element ) );
			if ( '' !== $text ) {
				return $text;
			}
		}
		return self::normalize( $element->getAttribute( 'title' ) );
	}

	/**
	 * Text of <label> elements associated by for/id or by wrapping.
	 *
	 * @return string[]
	 */
	public function labels( \DOMElement $control ): array {
		$labels = array();
		$id     = $control->getAttribute( 'id' );
		if ( '' !== $id ) {
			foreach ( $this->elements( '//label[@for]', true ) as $label ) {
				if ( $label->getAttribute( 'for' ) === $id ) {
					$labels[] = $this->content_name( $label, $control );
				}
			}
		}
		for ( $node = $control->parentNode; $node instanceof \DOMElement; $node = $node->parentNode ) {
			if ( 'label' === strtolower( $node->tagName ) ) {
				$labels[] = $this->content_name( $node, $control );
				break;
			}
		}
		return array_filter( array_map( array( self::class, 'normalize' ), $labels ) );
	}

	/**
	 * Name contributed by an element's subtree: text, image alternatives and nested labels.
	 *
	 * @param \DOMNode         $node         Subtree root.
	 * @param \DOMElement|null $skip         A control whose own subtree is excluded (the labelled control).
	 * @param int              $depth        Recursion guard.
	 * @param bool             $referenced   Referenced by aria-labelledby, where hidden content still counts.
	 */
	public function content_name( \DOMNode $node, ?\DOMElement $skip = null, int $depth = 0, bool $referenced = false ): string {
		if ( $depth > 32 ) {
			return '';
		}
		$text = '';
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof \DOMText ) {
				$text .= $child->textContent;
				continue;
			}
			if ( ! $child instanceof \DOMElement || $child === $skip || ( ! $referenced && $this->hidden_self( $child ) ) ) {
				continue;
			}
			$tag   = strtolower( $child->tagName );
			$label = self::normalize( $child->getAttribute( 'aria-label' ) );
			if ( '' !== $label ) {
				$text .= ' ' . $label . ' ';
			} elseif ( 'img' === $tag || ( 'input' === $tag && 'image' === self::input_type( $child ) ) ) {
				$text .= ' ' . $child->getAttribute( 'alt' ) . ' ';
			} elseif ( 'svg' === $tag ) {
				$text .= ' ' . $this->name( $child ) . ' ';
			} elseif ( in_array( $tag, array( 'select', 'textarea' ), true ) || ( 'input' === $tag && ! in_array( self::input_type( $child ), array( 'submit', 'reset', 'button' ), true ) ) ) {
				continue;
			} else {
				$block = in_array( $tag, array( 'br', 'p', 'div', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'td', 'th', 'tr' ), true );
				$text .= ( $block ? ' ' : '' ) . $this->content_name( $child, $skip, $depth + 1, $referenced ) . ( $block ? ' ' : '' );
			}
		}
		return $text;
	}

	/** Visible text only, for comparisons such as label-in-name. */
	public function visible_text( \DOMElement $element ): string {
		$text = '';
		foreach ( $element->childNodes as $child ) {
			if ( $child instanceof \DOMText ) {
				$text .= $child->textContent;
			} elseif ( $child instanceof \DOMElement && ! $this->hidden_self( $child ) && ! in_array( strtolower( $child->tagName ), array( 'img', 'svg' ), true ) ) {
				$text .= ' ' . $this->visible_text( $child ) . ' ';
			}
		}
		return self::normalize( $text );
	}

	/**
	 * Editor locations of the elements a finding is about. Rules often add detail after describe()
	 * (such as a class name), so a finding matches every description it contains.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function blocks_for( string $finding ): array {
		$found = array();
		foreach ( $this->blocks as $description => $locations ) {
			if ( '' !== $description && str_contains( $finding, (string) $description ) ) {
				$found += $locations;
			}
		}
		return array_values( $found );
	}

	/**
	 * Where an element is edited, from the markers WCAG_Content leaves: the nearest block of the post,
	 * else the synced pattern, template part or template around it. Null in public mode.
	 *
	 * @return array<string, string>|null
	 */
	private function location_of( \DOMElement $element ): ?array {
		for ( $node = $element; $node instanceof \DOMElement; $node = $node->parentNode ) {
			$before = $node->previousSibling;
			while ( $before instanceof \DOMText && '' === trim( $before->textContent ) ) {
				$before = $before->previousSibling;
			}
			if ( $before instanceof \DOMComment && preg_match( '/^wcag-block:([0-9.]+):(\S+)$/', $before->data, $found ) ) {
				return array( 'type' => 'block', 'path' => $found[1], 'name' => $found[2] );
			}
			$depth = 0;
			for ( $sibling = $node->previousSibling; null !== $sibling; $sibling = $sibling->previousSibling ) {
				if ( ! $sibling instanceof \DOMComment ) {
					continue;
				}
				if ( 'wcag-close' === $sibling->data ) {
					++$depth;
				} elseif ( preg_match( '/^wcag-open:(template|part|synced):(.*)$/s', $sibling->data, $found ) ) {
					if ( 0 === $depth ) {
						return array( 'type' => $found[1], 'id' => rawurldecode( $found[2] ) );
					}
					--$depth;
				}
			}
		}
		return null;
	}

	/** A short, editor-readable description of an element for issue examples. */
	public function describe( \DOMElement $element ): string {
		$description = $this->describe_element( $element );
		$location    = $this->page ? null : $this->location_of( $element );
		if ( $location ) {
			$this->blocks[ $description ][ $location['type'] . ':' . ( $location['path'] ?? $location['id'] ) ] = $location;
		}
		return $description;
	}

	private function describe_element( \DOMElement $element ): string {
		$tag = strtolower( $element->tagName );
		if ( in_array( $tag, array( 'img', 'video', 'audio', 'iframe', 'embed', 'source' ), true ) ) {
			$src  = $element->getAttribute( 'src' );
			$path = (string) wp_parse_url( $src, PHP_URL_PATH );
			$file = '' !== $path ? basename( $path ) : '';
			if ( 'iframe' === $tag ) {
				$host = (string) wp_parse_url( $src, PHP_URL_HOST );
				return 'iframe' . ( '' !== $host ? ' from ' . $host : '' );
			}
			return ( 'img' === $tag ? 'image' : $tag ) . ( '' !== $file ? ' "' . self::excerpt( $file, 50 ) . '"' : '' );
		}
		$text = self::excerpt( $this->visible_text( $element ) );
		if ( 'a' === $tag ) {
			$href = trim( $element->getAttribute( 'href' ) );
			return 'link' . ( '' !== $text ? ' "' . $text . '"' : '' ) . ( '' !== $href ? ' to ' . self::excerpt( $href, 50 ) : '' );
		}
		$what = $tag;
		if ( 'input' === $tag ) {
			$what = self::input_type( $element ) . ' field';
			foreach ( array( 'name', 'id', 'placeholder' ) as $attribute ) {
				if ( '' !== trim( $element->getAttribute( $attribute ) ) ) {
					return $what . ' (' . $attribute . ' "' . self::excerpt( $element->getAttribute( $attribute ), 40 ) . '")';
				}
			}
			return $what;
		}
		return '<' . $what . '>' . ( '' !== $text ? ' "' . $text . '"' : '' );
	}

	public static function normalize( string $text ): string {
		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ?? '' );
	}

	public static function excerpt( string $text, int $length = 60 ): string {
		$text = self::normalize( $text );
		return mb_strlen( $text ) > $length ? rtrim( mb_substr( $text, 0, $length - 1 ) ) . '…' : $text;
	}
}
