<?php
/** Automated WCAG 2.2 Level A and AA checks.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/**
 * Deterministic rules mapped to WCAG 2.2 success criteria (https://www.w3.org/TR/WCAG22/).
 *
 * A rule has one of four outcomes: pass (nothing found), fail (with examples),
 * review (a human must decide) and not-applicable. Passing every automated rule
 * is not WCAG conformance: most criteria need human judgement.
 */
final class WCAG_Rules {
	/** Every Level A and AA success criterion in WCAG 2.2: number => [name, level, new in 2.2]. 4.1.1 Parsing is obsolete and removed. */
	public const CRITERIA = array(
		'1.1.1'  => array( 'Non-text Content', 'A', false ),
		'1.2.1'  => array( 'Audio-only and Video-only (Prerecorded)', 'A', false ),
		'1.2.2'  => array( 'Captions (Prerecorded)', 'A', false ),
		'1.2.3'  => array( 'Audio Description or Media Alternative (Prerecorded)', 'A', false ),
		'1.2.4'  => array( 'Captions (Live)', 'AA', false ),
		'1.2.5'  => array( 'Audio Description (Prerecorded)', 'AA', false ),
		'1.3.1'  => array( 'Info and Relationships', 'A', false ),
		'1.3.2'  => array( 'Meaningful Sequence', 'A', false ),
		'1.3.3'  => array( 'Sensory Characteristics', 'A', false ),
		'1.3.4'  => array( 'Orientation', 'AA', false ),
		'1.3.5'  => array( 'Identify Input Purpose', 'AA', false ),
		'1.4.1'  => array( 'Use of Color', 'A', false ),
		'1.4.2'  => array( 'Audio Control', 'A', false ),
		'1.4.3'  => array( 'Contrast (Minimum)', 'AA', false ),
		'1.4.4'  => array( 'Resize Text', 'AA', false ),
		'1.4.5'  => array( 'Images of Text', 'AA', false ),
		'1.4.10' => array( 'Reflow', 'AA', false ),
		'1.4.11' => array( 'Non-text Contrast', 'AA', false ),
		'1.4.12' => array( 'Text Spacing', 'AA', false ),
		'1.4.13' => array( 'Content on Hover or Focus', 'AA', false ),
		'2.1.1'  => array( 'Keyboard', 'A', false ),
		'2.1.2'  => array( 'No Keyboard Trap', 'A', false ),
		'2.1.4'  => array( 'Character Key Shortcuts', 'A', false ),
		'2.2.1'  => array( 'Timing Adjustable', 'A', false ),
		'2.2.2'  => array( 'Pause, Stop, Hide', 'A', false ),
		'2.3.1'  => array( 'Three Flashes or Below Threshold', 'A', false ),
		'2.4.1'  => array( 'Bypass Blocks', 'A', false ),
		'2.4.2'  => array( 'Page Titled', 'A', false ),
		'2.4.3'  => array( 'Focus Order', 'A', false ),
		'2.4.4'  => array( 'Link Purpose (In Context)', 'A', false ),
		'2.4.5'  => array( 'Multiple Ways', 'AA', false ),
		'2.4.6'  => array( 'Headings and Labels', 'AA', false ),
		'2.4.7'  => array( 'Focus Visible', 'AA', false ),
		'2.4.11' => array( 'Focus Not Obscured (Minimum)', 'AA', true ),
		'2.5.1'  => array( 'Pointer Gestures', 'A', false ),
		'2.5.2'  => array( 'Pointer Cancellation', 'A', false ),
		'2.5.3'  => array( 'Label in Name', 'A', false ),
		'2.5.4'  => array( 'Motion Actuation', 'A', false ),
		'2.5.7'  => array( 'Dragging Movements', 'AA', true ),
		'2.5.8'  => array( 'Target Size (Minimum)', 'AA', true ),
		'3.1.1'  => array( 'Language of Page', 'A', false ),
		'3.1.2'  => array( 'Language of Parts', 'AA', false ),
		'3.2.1'  => array( 'On Focus', 'A', false ),
		'3.2.2'  => array( 'On Input', 'A', false ),
		'3.2.3'  => array( 'Consistent Navigation', 'AA', false ),
		'3.2.4'  => array( 'Consistent Identification', 'AA', false ),
		'3.2.6'  => array( 'Consistent Help', 'A', true ),
		'3.3.1'  => array( 'Error Identification', 'A', false ),
		'3.3.2'  => array( 'Labels or Instructions', 'A', false ),
		'3.3.3'  => array( 'Error Suggestion', 'AA', false ),
		'3.3.4'  => array( 'Error Prevention (Legal, Financial, Data)', 'AA', false ),
		'3.3.7'  => array( 'Redundant Entry', 'A', true ),
		'3.3.8'  => array( 'Accessible Authentication (Minimum)', 'AA', true ),
		'4.1.2'  => array( 'Name, Role, Value', 'A', false ),
		'4.1.3'  => array( 'Status Messages', 'A', false ),
	);

	/** Rules that need a full rendered page; saved-content mode reports them as not applicable. */
	public const PAGE_RULES = array( 'html-lang', 'meta-viewport', 'meta-refresh', 'bypass-blocks' );

	private const ARIA_ROLES = array( 'alert', 'alertdialog', 'application', 'article', 'banner', 'blockquote', 'button', 'caption', 'cell', 'checkbox', 'code', 'columnheader', 'combobox', 'comment', 'complementary', 'contentinfo', 'definition', 'deletion', 'dialog', 'directory', 'document', 'emphasis', 'feed', 'figure', 'form', 'generic', 'grid', 'gridcell', 'group', 'heading', 'img', 'image', 'insertion', 'link', 'list', 'listbox', 'listitem', 'log', 'main', 'mark', 'marquee', 'math', 'menu', 'menubar', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'meter', 'navigation', 'none', 'note', 'option', 'paragraph', 'presentation', 'progressbar', 'radio', 'radiogroup', 'region', 'row', 'rowgroup', 'rowheader', 'scrollbar', 'search', 'searchbox', 'separator', 'slider', 'spinbutton', 'status', 'strong', 'subscript', 'suggestion', 'superscript', 'switch', 'tab', 'table', 'tablist', 'tabpanel', 'term', 'textbox', 'time', 'timer', 'toolbar', 'tooltip', 'tree', 'treegrid', 'treeitem', 'doc-abstract', 'doc-acknowledgments', 'doc-afterword', 'doc-appendix', 'doc-backlink', 'doc-biblioentry', 'doc-bibliography', 'doc-biblioref', 'doc-chapter', 'doc-colophon', 'doc-conclusion', 'doc-cover', 'doc-credit', 'doc-credits', 'doc-dedication', 'doc-endnote', 'doc-endnotes', 'doc-epigraph', 'doc-epilogue', 'doc-errata', 'doc-example', 'doc-footnote', 'doc-foreword', 'doc-glossary', 'doc-glossref', 'doc-index', 'doc-introduction', 'doc-noteref', 'doc-notice', 'doc-pagebreak', 'doc-pagefooter', 'doc-pageheader', 'doc-pagelist', 'doc-part', 'doc-preface', 'doc-prologue', 'doc-pullquote', 'doc-qna', 'doc-subtitle', 'doc-tip', 'doc-toc', 'graphics-document', 'graphics-object', 'graphics-symbol' );

	/** HTML autocomplete field names (WCAG 1.3.5 input purposes plus the other valid tokens). */
	private const AUTOCOMPLETE = array( 'name', 'honorific-prefix', 'given-name', 'additional-name', 'family-name', 'honorific-suffix', 'nickname', 'email', 'username', 'new-password', 'current-password', 'one-time-code', 'organization-title', 'organization', 'street-address', 'address-line1', 'address-line2', 'address-line3', 'address-level4', 'address-level3', 'address-level2', 'address-level1', 'country', 'country-name', 'postal-code', 'cc-name', 'cc-given-name', 'cc-additional-name', 'cc-family-name', 'cc-number', 'cc-exp', 'cc-exp-month', 'cc-exp-year', 'cc-csc', 'cc-type', 'transaction-currency', 'transaction-amount', 'language', 'bday', 'bday-day', 'bday-month', 'bday-year', 'sex', 'url', 'photo', 'tel', 'tel-country-code', 'tel-national', 'tel-area-code', 'tel-local', 'tel-local-prefix', 'tel-local-suffix', 'tel-extension', 'impp', 'webauthn' );

	private const GENERIC_LINKS = array( 'click here', 'click', 'here', 'read more', 'more', 'learn more', 'more info', 'more information', 'link', 'this link', 'this', 'details', 'continue', 'go', 'info', 'download', 'see more' );

	private const HEADING_PX = array(
		'h1' => 32.0,
		'h2' => 24.0,
		'h3' => 18.72,
		'h4' => 16.0,
		'h5' => 13.28,
		'h6' => 10.72,
	);

	private const KEYWORD_PX = array(
		'xx-small'  => 9.0,
		'x-small'   => 10.0,
		'small'     => 13.0,
		'medium'    => 16.0,
		'large'     => 18.0,
		'x-large'   => 24.0,
		'xx-large'  => 32.0,
		'xxx-large' => 48.0,
	);

	/** @var array<int, array> */
	private array $results = array();

	/**
	 * @param WCAG_Document $doc     Parsed content or page.
	 * @param array         $context palette, font_sizes, text, background, text_assumed, background_assumed, title.
	 */
	private function __construct( private WCAG_Document $doc, private array $context ) {}

	/**
	 * Run every rule.
	 *
	 * @param WCAG_Document $doc     Parsed content or page.
	 * @param array         $context Theme colors and page facts.
	 * @return array<int, array> Rule results in criterion order.
	 */
	public static function run( WCAG_Document $doc, array $context ): array {
		$rules = new self( $doc, $context );
		$rules->non_text();
		$rules->media();
		$rules->structure();
		$rules->forms();
		$rules->sensory();
		$rules->contrast();
		$rules->page();
		$rules->keyboard();
		$rules->links();
		$rules->pointer();
		$rules->language();
		$rules->input_behavior();
		$rules->authentication();
		$rules->name_role_value();
		usort( $rules->results, static fn( $a, $b ) => version_compare( $a['criteria'][0], $b['criteria'][0] ) );
		return $rules->results;
	}

	/**
	 * Record one rule outcome.
	 *
	 * @param string        $rule     Stable machine slug.
	 * @param string[]      $criteria Success criteria numbers, primary first.
	 * @param string        $label    Editor-facing title.
	 * @param string[]|null $failures Example descriptions; empty passes, null is not applicable.
	 * @param string        $evidence Shown on pass.
	 * @param string        $fix      Shown on failure.
	 * @param string        $severity error for definite failures, warning for likely ones.
	 * @param string        $note     Shown when not applicable.
	 */
	private function add( string $rule, array $criteria, string $label, ?array $failures, string $evidence, string $fix, string $severity = 'error', string $note = '' ): void {
		$status = null === $failures ? 'not-applicable' : ( $failures ? 'fail' : 'pass' );
		$this->results[] = array(
			'rule'     => $rule,
			'criteria' => $criteria,
			'label'    => $label,
			'status'   => $status,
			'severity' => 'fail' === $status ? $severity : 'info',
			'count'    => count( $failures ?? array() ),
			'examples' => array_slice( array_values( array_unique( $failures ?? array() ) ), 0, 5 ),
			'blocks'   => 'fail' === $status ? $this->blocks_of( $failures ) : array(),
			'message'  => match ( $status ) {
				'pass' => $evidence,
				'fail' => $fix . ' ' . self::found( $failures ),
				default => '' !== $note ? $note : 'Nothing on this page to check.',
			},
		);
	}

	/**
	 * Record a finding that automated checks cannot decide.
	 *
	 * @param string   $rule     Stable machine slug.
	 * @param string[] $criteria Success criteria.
	 * @param string   $label    Editor-facing title.
	 * @param string[] $items    Example descriptions; none means nothing to review.
	 * @param string   $message  What the reviewer must confirm.
	 */
	private function review( string $rule, array $criteria, string $label, array $items, string $message ): void {
		$this->results[] = array(
			'rule'     => $rule,
			'criteria' => $criteria,
			'label'    => $label,
			'status'   => $items ? 'review' : 'not-applicable',
			'severity' => 'info',
			'count'    => count( $items ),
			'examples' => array_slice( array_values( array_unique( $items ) ), 0, 5 ),
			'message'  => $items ? $message . ' ' . self::found( $items ) : 'Nothing on this page to review.',
		);
	}

	/** Where the failing elements are edited: post blocks in page order, then patterns, parts and templates; first 5. @param string[] $failures */
	private function blocks_of( array $failures ): array {
		$blocks = $others = array();
		foreach ( array_unique( $failures ) as $failure ) {
			foreach ( $this->doc->blocks_for( $failure ) as $location ) {
				if ( 'block' === $location['type'] ) {
					$blocks[ $location['path'] ] = $location;
				} else {
					$others[ $location['type'] . ':' . $location['id'] ] = $location;
				}
			}
		}
		uksort( $blocks, static fn( $a, $b ) => array_map( 'intval', explode( '.', $a ) ) <=> array_map( 'intval', explode( '.', $b ) ) );
		return array_slice( array_merge( array_values( $blocks ), array_values( $others ) ), 0, 5 );
	}

	private static function found( array $items ): string {
		$unique = array_values( array_unique( $items ) );
		$shown  = array_slice( $unique, 0, 5 );
		$more   = count( $items ) - count( $shown );
		return 'Found ' . count( $items ) . ': ' . implode( '; ', $shown ) . ( $more > 0 ? '; and ' . $more . ' more' : '' ) . '.';
	}

	/** 1.1.1: images, image buttons, image maps and role="img" have text alternatives. */
	private function non_text(): void {
		$images  = $this->doc->elements( '//img' );
		$missing = $poor = array();
		foreach ( $images as $img ) {
			$role = WCAG_Document::role( $img );
			if ( ! $img->hasAttribute( 'alt' ) && '' === $this->doc->name( $img ) && ! in_array( $role, array( 'presentation', 'none' ), true ) ) {
				$missing[] = $this->doc->describe( $img );
				continue;
			}
			$alt = WCAG_Document::normalize( $img->getAttribute( 'alt' ) );
			if ( '' !== $alt && ( preg_match( '/\.(?:jpe?g|png|gif|webp|avif|svg|bmp|tiff?|heic)$/i', $alt ) || preg_match( '/^(?:img|image|dsc|dscn|pxl|photo|screenshot|screen shot)[\s_-]*\d+/i', $alt ) || in_array( strtolower( $alt ), array( 'image', 'picture', 'photo', 'graphic', 'img', 'spacer', 'untitled', 'placeholder', 'icon', 'thumbnail' ), true ) ) ) {
				$poor[] = $this->doc->describe( $img ) . ' (alt "' . WCAG_Document::excerpt( $alt, 40 ) . '")';
			}
		}
		$this->add( 'img-alt', array( '1.1.1' ), 'Images have text alternatives', $images ? $missing : null, count( $images ) . ' image(s) have an alt attribute or accessible name.', 'Add alt text that serves the same purpose as each image. Use an empty alt only for purely decorative images.', 'error', 'No images.' );
		$described = array_filter( $images, static fn( $img ) => '' !== trim( $img->getAttribute( 'alt' ) ) );
		$this->add( 'img-alt-quality', array( '1.1.1' ), 'Alt text is not a file name or placeholder', $described ? $poor : null, 'No alt text looks like a file name or generic placeholder.', 'Replace file names and generic words such as "image" or "photo" with a description of what the image conveys.', 'warning', 'No images with alt text.' );

		$other   = $this->doc->elements( '//input[translate(@type,"IMAGE","image")="image"]|//area[@href]|//*[@role="img" or @role="image"]|//object' );
		$unnamed = array();
		foreach ( $other as $element ) {
			if ( 'img' === strtolower( $element->tagName ) ) {
				continue;
			}
			$name = 'object' === strtolower( $element->tagName ) ? WCAG_Document::normalize( $element->getAttribute( 'aria-label' ) . ' ' . $element->getAttribute( 'title' ) . ' ' . $element->textContent ) : $this->doc->name( $element );
			if ( '' === $name ) {
				$unnamed[] = $this->doc->describe( $element );
			}
		}
		$this->add( 'non-text-name', array( '1.1.1', '4.1.2' ), 'Image buttons, image maps, objects and role="img" elements have names', $other ? $unnamed : null, 'Every image button, image-map area, object and role="img" element has a text alternative.', 'Give each one alt text, aria-label or aria-labelledby. Inline SVGs with role="img" need a <title> or aria-label.', 'error', 'No image buttons, image maps, objects or role="img" elements.' );
	}

	/** 1.2.x and 1.4.2: captions, media alternatives and autoplaying sound. */
	private function media(): void {
		$videos   = $this->doc->elements( '//video' );
		$captions = array();
		$applies  = 0;
		foreach ( $videos as $video ) {
			// A muted, controlless autoplay loop is a background animation with no audio to caption.
			if ( $video->hasAttribute( 'muted' ) && $video->hasAttribute( 'autoplay' ) && ! $video->hasAttribute( 'controls' ) ) {
				continue;
			}
			++$applies;
			$tracks = array_filter( $this->doc->within( './track', $video ), static fn( $t ) => in_array( strtolower( $t->getAttribute( 'kind' ) ), array( 'captions', 'subtitles' ), true ) );
			if ( ! $tracks ) {
				$captions[] = $this->doc->describe( $video );
			}
		}
		$this->add( 'video-captions', array( '1.2.2' ), 'Videos have captions', $applies ? $captions : null, $applies . ' video(s) have a captions or subtitles track.', 'Add a captions track (Video block > Text tracks) to each video with audio, or confirm the video has no audio.', 'error', 'No self-hosted videos with audio.' );

		$embeds = array();
		foreach ( $this->doc->elements( '//iframe|//embed|//audio' ) as $element ) {
			$src = strtolower( $element->getAttribute( 'src' ) );
			if ( 'audio' === strtolower( $element->tagName ) || preg_match( '~(?:youtube(?:-nocookie)?\.com|youtu\.be|vimeo\.com|wistia|brightcove|jwplayer|dailymotion|ted\.com|soundcloud\.com|spotify\.com|podcasts?\.|libsyn|buzzsprout|anchor\.fm|twitch\.tv|facebook\.com/plugins/video)~', $src ) ) {
				$embeds[] = $this->doc->describe( $element );
			}
		}
		$this->review( 'media-alternatives', array( '1.2.1', '1.2.2', '1.2.3', '1.2.5' ), 'Embedded audio and video have alternatives', $embeds, 'Confirm captions for video with sound, a transcript for audio-only content, and audio description (or a full text alternative) where visual information is not spoken.' );

		$autoplay = array();
		foreach ( $this->doc->elements( '//video[@autoplay]|//audio[@autoplay]' ) as $media ) {
			if ( ! $media->hasAttribute( 'muted' ) ) {
				$autoplay[] = $this->doc->describe( $media );
			}
		}
		$playable = $this->doc->elements( '//video|//audio' );
		$this->add( 'autoplay-audio', array( '1.4.2' ), 'Media does not autoplay with sound', $playable ? $autoplay : null, 'No audio or video autoplays with sound.', 'Remove autoplay or add muted, so sound never starts without the user choosing it.', 'error', 'No audio or video.' );
	}

	/** 1.3.1 and 2.4.6: headings, lists and tables convey structure. */
	private function structure(): void {
		$headings = $this->doc->elements( '//h1|//h2|//h3|//h4|//h5|//h6|//*[@role="heading"]' );
		$empty    = $skips = array();
		// In bare saved content the post title is the page's H1; a full page or template supplies its own.
		$page_like = $this->doc->page || ! empty( $this->context['template'] );
		$previous  = $page_like ? 0 : 1;
		foreach ( $headings as $heading ) {
			$tag   = strtolower( $heading->tagName );
			$level = 'heading' === WCAG_Document::role( $heading ) ? max( 1, (int) ( $heading->getAttribute( 'aria-level' ) ?: 2 ) ) : (int) substr( $tag, 1 );
			if ( '' === $this->doc->name( $heading ) ) {
				$empty[] = 'empty H' . $level;
				continue;
			}
			if ( $previous > 0 && $level > $previous + 1 ) {
				$skips[] = 'H' . $previous . ' to H' . $level . ' at "' . WCAG_Document::excerpt( $heading->textContent, 40 ) . '"';
			}
			if ( ! $page_like && 1 === $level ) {
				$skips[] = 'H1 "' . WCAG_Document::excerpt( $heading->textContent, 40 ) . '" in the body (the post title is already the H1)';
			}
			$previous = $level;
		}
		$this->add( 'empty-heading', array( '1.3.1', '2.4.6' ), 'Headings have text', $headings ? $empty : null, count( $headings ) . ' heading(s) contain text.', 'Remove empty headings or give them descriptive text. Use spacing, not empty headings, for layout.', 'error', 'No headings.' );
		$this->add( 'heading-order', array( '1.3.1' ), 'Heading levels are not skipped', $headings ? $skips : null, 'Heading levels descend in order.', 'Use heading levels for structure, not size: follow H2 with H2 or H3, not H4. Change the heading level and restyle it if needed.', 'warning', 'No headings.' );

		$fake = array();
		foreach ( $this->doc->elements( '//p' ) as $paragraph ) {
			$text = WCAG_Document::normalize( $paragraph->textContent );
			if ( '' === $text || str_word_count( $text ) > 12 || preg_match( '/[.,;:!?)"\']$/u', $text ) ) {
				continue;
			}
			$children = array_values( array_filter( iterator_to_array( $paragraph->childNodes ), static fn( $n ) => ! ( $n instanceof \DOMText && '' === trim( $n->textContent ) ) ) );
			$style    = WCAG_Document::style( $paragraph );
			$bold     = 1 === count( $children ) && $children[0] instanceof \DOMElement && in_array( strtolower( $children[0]->tagName ), array( 'strong', 'b' ), true );
			$bold     = $bold || in_array( $style['font-weight'] ?? '', array( 'bold', '600', '700', '800', '900' ), true );
			if ( $bold && $paragraph->nextSibling ) {
				$fake[] = '"' . WCAG_Document::excerpt( $text, 50 ) . '"';
			}
		}
		$paragraphs = $this->doc->elements( '//p' );
		$this->add( 'bold-as-heading', array( '1.3.1' ), 'Bold paragraphs are not used as headings', $paragraphs ? $fake : null, 'No short, fully bold paragraphs that look like headings.', 'If these introduce a section, convert them to Heading blocks so screen-reader users can navigate by them.', 'warning', 'No paragraphs.' );

		$broken = array();
		$lists  = $this->doc->elements( '//li|//ul|//ol' );
		foreach ( $lists as $element ) {
			$tag    = strtolower( $element->tagName );
			$parent = $element->parentNode instanceof \DOMElement ? $element->parentNode : null;
			if ( 'li' === $tag ) {
				if ( ! $parent || ( ! in_array( strtolower( $parent->tagName ), array( 'ul', 'ol', 'menu' ), true ) && 'list' !== WCAG_Document::role( $parent ) ) ) {
					$broken[] = 'list item outside a list: "' . WCAG_Document::excerpt( $element->textContent, 40 ) . '"';
				}
				continue;
			}
			if ( '' !== WCAG_Document::role( $element ) ) {
				continue;
			}
			foreach ( $element->childNodes as $child ) {
				if ( $child instanceof \DOMElement && ! in_array( strtolower( $child->tagName ), array( 'li', 'script', 'template' ), true ) ) {
					$broken[] = '<' . strtolower( $child->tagName ) . '> directly inside <' . $tag . '>';
				}
			}
		}
		$this->add( 'list-structure', array( '1.3.1' ), 'Lists are marked up correctly', $lists ? $broken : null, 'List items are inside lists and lists contain only list items.', 'Keep each list item inside a bulleted or numbered list, and put other content inside list items.', 'error', 'No lists.' );

		$tables  = array();
		$checked = 0;
		foreach ( $this->doc->elements( '//table' ) as $table ) {
			if ( in_array( WCAG_Document::role( $table ), array( 'presentation', 'none' ), true ) ) {
				continue;
			}
			$rows  = array_filter( $this->doc->within( './/tr', $table ), fn( $row ) => $this->closest( $row, 'table' )?->isSameNode( $table ) ?? false );
			$cells = 0;
			foreach ( $rows as $row ) {
				$cells = max( $cells, count( array_filter( iterator_to_array( $row->childNodes ), static fn( $n ) => $n instanceof \DOMElement && in_array( strtolower( $n->tagName ), array( 'td', 'th' ), true ) ) ) );
			}
			if ( count( $rows ) < 2 || $cells < 2 ) {
				continue;
			}
			++$checked;
			$headers = array_filter( $this->doc->within( './/th|.//*[@role="columnheader" or @role="rowheader"]', $table ), fn( $h ) => $this->closest( $h, 'table' )?->isSameNode( $table ) ?? false );
			$caption = '' !== $this->doc->name( $table ) ? ' "' . WCAG_Document::excerpt( $this->doc->name( $table ), 40 ) . '"' : '';
			if ( ! $headers ) {
				$tables[] = 'table' . $caption . ' (' . count( $rows ) . ' rows) has no header cells';
				continue;
			}
			foreach ( $headers as $header ) {
				if ( '' === $this->doc->name( $header ) ) {
					$tables[] = 'table' . $caption . ' has an empty header cell';
					break;
				}
			}
		}
		$this->add( 'table-headers', array( '1.3.1' ), 'Data tables have header cells', $checked ? $tables : null, $checked . ' data table(s) have header cells.', 'Turn on "Header section" in the Table block (or use <th>) so each cell is announced with its column or row header.', 'error', 'No data tables.' );
	}

	/** 1.3.1, 1.3.5, 3.3.2 and 4.1.2: form controls are labelled and declare their purpose. */
	private function forms(): void {
		$controls    = $this->controls();
		$unlabelled  = $placeholder = $purpose = $invalid = array();
		$personal    = 0;
		foreach ( $controls as $control ) {
			$name = $this->doc->name( $control );
			if ( '' === $name ) {
				if ( '' !== trim( $control->getAttribute( 'placeholder' ) ) ) {
					$placeholder[] = $this->doc->describe( $control );
				} else {
					$unlabelled[] = $this->doc->describe( $control );
				}
			}
			if ( 'input' !== strtolower( $control->tagName ) && 'select' !== strtolower( $control->tagName ) && 'textarea' !== strtolower( $control->tagName ) ) {
				continue;
			}
			$autocomplete = strtolower( trim( $control->getAttribute( 'autocomplete' ) ) );
			if ( '' !== $autocomplete ) {
				if ( ! self::valid_autocomplete( $autocomplete ) ) {
					$invalid[] = $this->doc->describe( $control ) . ' autocomplete="' . WCAG_Document::excerpt( $autocomplete, 30 ) . '"';
				}
				++$personal;
				continue;
			}
			$hint = strtolower( self::input_type_of( $control ) . ' ' . $control->getAttribute( 'name' ) . ' ' . $control->getAttribute( 'id' ) );
			if ( preg_match( '/\b(?:email|tel)\b|e-?mail|phone|mobile|first[-_ ]?name|last[-_ ]?name|full[-_ ]?name|surname|given|family|fname|lname|street|address|postal|postcode|zip|city|country|birthday|organi[sz]ation|company|username/', $hint ) ) {
				++$personal;
				$purpose[] = $this->doc->describe( $control );
			}
		}
		$this->add( 'form-label', array( '3.3.2', '1.3.1', '4.1.2' ), 'Form fields have labels', $controls ? $unlabelled : null, count( $controls ) . ' form field(s) have an accessible label.', 'Associate a visible <label> with each field (or use aria-labelledby). A field with no label is announced only as "edit text".', 'error', 'No form fields.' );
		$this->add( 'placeholder-label', array( '3.3.2' ), 'Placeholders are not the only label', $controls ? $placeholder : null, 'No field relies on placeholder text as its only label.', 'Add a persistent visible label. Placeholder text disappears while typing and often fails contrast.', 'warning', 'No form fields.' );
		$this->add( 'autocomplete-valid', array( '1.3.5' ), 'Autocomplete values are valid', $personal ? $invalid : null, 'Every autocomplete attribute uses a valid HTML token.', 'Use a valid autocomplete token such as "email", "given-name" or "postal-code" so browsers and assistive technology can identify the field.', 'error', 'No fields collect personal information.' );
		$this->add( 'autocomplete-purpose', array( '1.3.5' ), 'Personal-information fields declare their purpose', $personal ? $purpose : null, 'Fields that appear to collect personal information declare an autocomplete purpose.', 'If these collect the user\'s own information, add the matching autocomplete attribute (for example autocomplete="email").', 'warning', 'No fields collect personal information.' );
	}

	/** 1.3.3: instructions that rely on shape, size, position or color alone. */
	private function sensory(): void {
		$found = array();
		$body  = $this->doc->elements( '//body' )[0] ?? null;
		$text  = $body ? WCAG_Document::normalize( $this->doc->content_name( $body ) ) : '';
		if ( preg_match_all( '/[^.!?]{0,60}\b(?:(?:click|press|tap|select|choose|use) (?:the )?(?:red|green|blue|yellow|orange|grey|gray|round|square|circular|big|small) (?:button|link|icon|box|text|one)|(?:on|in|to) the (?:left|right)(?:-hand)?(?: side| column| sidebar)?|(?:see|shown|listed|located) (?:above|below)|(?:in|marked (?:in)?) (?:red|green|blue|bold) (?:are|is|text)|(?:red|green) (?:items|fields|text) (?:are|is))\b[^.!?]{0,40}/i', $text, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				$found[] = '"' . WCAG_Document::excerpt( $match, 80 ) . '"';
			}
		}
		$this->review( 'sensory-language', array( '1.3.3', '1.4.1' ), 'Instructions do not rely on shape, position or color alone', $found, 'Confirm these instructions also name the thing (for example "the Submit button") rather than relying only on its color, shape or position.' );
	}

	/** 1.4.3: text with explicitly set colors meets 4.5:1, or 3:1 for large text. */
	private function contrast(): void {
		$definite = $uncertain = array();
		$checked  = 0;
		foreach ( $this->doc->elements( '//body//*[text()[normalize-space()]]' ) as $element ) {
			if ( in_array( strtolower( $element->tagName ), array( 'option', 'title', 'textarea', 'svg', 'text' ), true ) ) {
				continue;
			}
			$colors = $this->resolve_colors( $element );
			if ( null === $colors ) {
				continue;
			}
			++$checked;
			[ $large, $size_known ] = $this->is_large( $element );
			$ratio    = WCAG_Color::ratio( $colors['fg'], $colors['bg'] );
			$required = $large ? 3.0 : 4.5;
			if ( $ratio + 0.0001 >= $required ) {
				continue;
			}
			$finding = '"' . WCAG_Document::excerpt( $this->doc->visible_text( $element ), 40 ) . '" ' . WCAG_Color::hex( $colors['fg'] ) . ' on ' . WCAG_Color::hex( $colors['bg'] ) . ' is ' . number_format( floor( $ratio * 100 ) / 100, 2 ) . ':1, needs ' . ( $large ? '3' : '4.5' ) . ':1' . ( $colors['assumed'] ? ' (' . $colors['assumed'] . ' assumed)' : '' );
			// Under 3:1 fails at any size; otherwise an unknown size or an assumed theme color leaves doubt.
			if ( ! $colors['assumed'] && ( $size_known || $ratio < 3.0 ) ) {
				$definite[] = $finding;
			} else {
				$uncertain[] = $finding;
			}
		}
		$this->add(
			'color-contrast',
			array( '1.4.3' ),
			'Text color contrast',
			$checked ? array_merge( $definite, $uncertain ) : null,
			$checked . ' text element(s) with colors set in the editor meet 4.5:1 (3:1 for large text).',
			'Choose text and background colors with a contrast ratio of at least 4.5:1, or 3:1 for text 24px or larger (18.66px bold).',
			$definite ? 'error' : 'warning',
			'No text has colors set in the editor. Theme stylesheet colors are not evaluated.'
		);
	}

	/** 1.4.4, 2.2.1, 2.4.1, 2.4.2 and 3.1.1: page-level requirements. */
	private function page(): void {
		$page = $this->doc->page;
		$note = 'Checked against the rendered page in public mode; saved content has no page wrapper.';
		if ( $page ) {
			$title = WCAG_Document::normalize( ( $this->doc->elements( '//title', true )[0] ?? null )?->textContent ?? '' );
		} else {
			$title = WCAG_Document::normalize( (string) ( $this->context['title'] ?? '' ) );
		}
		$this->add( 'page-title', array( '2.4.2' ), 'Page has a title', '' === $title || preg_match( '/^(?:untitled|auto draft|new page|page|home|\(no title\))$/i', $title ) ? array( '' === $title ? 'no title' : 'title "' . $title . '"' ) : array(), 'The page title is "' . WCAG_Document::excerpt( $title, 60 ) . '".', 'Give the ' . ( $page ? 'page a descriptive <title>' : 'post a descriptive title; it becomes the page title' ) . '.' );

		$lang = $page ? trim( ( $this->doc->elements( '//html', true )[0] ?? null )?->getAttribute( 'lang' ) ?? '' ) : '';
		$this->add( 'html-lang', array( '3.1.1' ), 'Page language is set', $page ? ( self::valid_lang( $lang ) ? array() : array( '' === $lang ? 'no lang attribute on <html>' : 'invalid lang "' . $lang . '"' ) ) : null, 'The page declares lang="' . $lang . '".', 'The theme must output a valid lang attribute on <html> (language_attributes()). Check Settings > General > Site Language.', 'error', $note );

		$zoom = array();
		foreach ( $page ? $this->doc->elements( '//meta[@name="viewport"]', true ) : array() as $meta ) {
			$content = strtolower( $meta->getAttribute( 'content' ) );
			if ( preg_match( '/user-scalable\s*=\s*(?:no|0)\b/', $content ) ) {
				$zoom[] = 'user-scalable=no';
			}
			if ( preg_match( '/maximum-scale\s*=\s*([\d.]+)/', $content, $match ) && (float) $match[1] < 2 ) {
				$zoom[] = 'maximum-scale=' . $match[1];
			}
		}
		$this->add( 'meta-viewport', array( '1.4.4', '1.4.10' ), 'Zoom is not disabled', $page ? $zoom : null, 'The viewport allows zooming to at least 200%.', 'Remove user-scalable=no and any maximum-scale below 2 from the theme\'s viewport meta tag.', 'error', $note );

		$refresh = array();
		foreach ( $this->doc->elements( '//meta', true ) as $meta ) {
			if ( 'refresh' === strtolower( trim( $meta->getAttribute( 'http-equiv' ) ) ) && preg_match( '/^\s*(\d+)/', $meta->getAttribute( 'content' ), $match ) && (int) $match[1] > 0 ) {
				$refresh[] = 'refreshes after ' . $match[1] . ' seconds';
			}
		}
		$this->add( 'meta-refresh', array( '2.2.1' ), 'Page does not refresh or redirect on a timer', $page || $refresh ? $refresh : null, 'No timed refresh or redirect.', 'Remove the timed meta refresh, or let users turn it off, adjust it or extend it.', 'error', $note );

		$bypass = array();
		if ( $page ) {
			$main = $this->doc->elements( '//main|//*[@role="main"]' );
			$skip = false;
			foreach ( array_slice( $this->doc->elements( '//a[@href]' ), 0, 5 ) as $link ) {
				$href = $link->getAttribute( 'href' );
				if ( str_starts_with( $href, '#' ) && strlen( $href ) > 1 && $this->doc->by_id( substr( $href, 1 ) ) ) {
					$skip = true;
				}
			}
			if ( ! $main && ! $skip ) {
				$bypass[] = 'no <main> landmark and no skip link';
			}
		}
		$this->add( 'bypass-blocks', array( '2.4.1' ), 'Main content can be reached directly', $page ? $bypass : null, 'The page has a main landmark or a skip link.', 'The theme should wrap the main content in <main> and provide a "Skip to content" link.', 'error', $note );
	}

	/** 2.1.1, 2.2.2, 2.4.3 and 2.4.7: keyboard operation, motion and focus. */
	private function keyboard(): void {
		$handlers = $this->doc->elements( '//*[@onclick or @onmousedown or @onmouseup or @ondblclick]' );
		$mouse    = array();
		foreach ( $handlers as $element ) {
			if ( ! WCAG_Document::is_focusable( $element ) ) {
				$mouse[] = $this->doc->describe( $element ) . ' has a click handler but cannot receive keyboard focus';
			} elseif ( ! WCAG_Document::is_interactive( $element ) && '' === WCAG_Document::role( $element ) ) {
				$mouse[] = $this->doc->describe( $element ) . ' is focusable but has no role and may not respond to Enter or Space';
			}
		}
		$this->add( 'keyboard-handlers', array( '2.1.1', '4.1.2' ), 'Click handlers work from the keyboard', $handlers ? $mouse : null, 'Every element with a click handler is a focusable control.', 'Use a real <button> or link for anything clickable. A <div> or <span> with onclick cannot be reached or activated from the keyboard.', 'error', 'No inline click handlers.' );

		$moving = array();
		foreach ( $this->doc->elements( '//marquee|//blink' ) as $element ) {
			$moving[] = '<' . strtolower( $element->tagName ) . '> "' . WCAG_Document::excerpt( $element->textContent, 40 ) . '"';
		}
		foreach ( $this->doc->elements( '//video[@autoplay]' ) as $video ) {
			if ( $video->hasAttribute( 'loop' ) && ! $video->hasAttribute( 'controls' ) ) {
				$moving[] = $this->doc->describe( $video ) . ' loops automatically with no controls to pause it';
			}
		}
		$this->add( 'pause-stop-hide', array( '2.2.2' ), 'Moving content can be paused', $moving, 'No marquee, blink or uncontrollable autoplaying loop.', 'Remove scrolling or blinking text, and give looping autoplay video controls (or stop it within 5 seconds).' );

		$positive = array();
		foreach ( $this->doc->elements( '//*[@tabindex]' ) as $element ) {
			if ( is_numeric( trim( $element->getAttribute( 'tabindex' ) ) ) && (int) $element->getAttribute( 'tabindex' ) > 0 ) {
				$positive[] = $this->doc->describe( $element ) . ' tabindex="' . (int) $element->getAttribute( 'tabindex' ) . '"';
			}
		}
		$this->add( 'tabindex-positive', array( '2.4.3' ), 'Focus order follows the page', $positive, 'No positive tabindex values override the reading order.', 'Remove positive tabindex values and order the content in the DOM instead.', 'warning' );

		$outline = array();
		$focusable = array_filter( $this->doc->elements( '//*' ), array( WCAG_Document::class, 'is_focusable' ) );
		foreach ( $focusable as $element ) {
			$style = WCAG_Document::style( $element );
			if ( preg_match( '/^(?:none|0(?:px)?)\b/', $style['outline'] ?? '' ) || preg_match( '/^(?:none|0(?:px)?)$/', $style['outline-style'] ?? $style['outline-width'] ?? 'x' ) ) {
				$outline[] = $this->doc->describe( $element );
			}
		}
		$this->add( 'focus-visible', array( '2.4.7' ), 'Focus outline is not removed', $focusable ? $outline : null, 'No inline style removes the focus outline.', 'Remove inline outline:none, or provide another clearly visible focus style.', 'warning', 'No focusable elements.' );
	}

	/** 2.4.4, 2.5.3 and 4.1.2: links have names that make sense. */
	private function links(): void {
		$links   = $this->doc->elements( '//a[@href]|//*[@role="link"]' );
		$empty   = $generic = $mismatch = $script = array();
		foreach ( $links as $link ) {
			$name = $this->doc->name( $link );
			$href = strtolower( trim( $link->getAttribute( 'href' ) ) );
			if ( '' === $name ) {
				$empty[] = $this->doc->describe( $link );
				continue;
			}
			if ( in_array( strtolower( trim( $name, " \t.:!>»→…" ) ), self::GENERIC_LINKS, true ) && '' === trim( $link->getAttribute( 'aria-describedby' ) ) ) {
				$generic[] = $this->doc->describe( $link );
			}
			if ( str_starts_with( $href, 'javascript:' ) || ( '#' === $href && $link->hasAttribute( 'onclick' ) ) ) {
				$script[] = $this->doc->describe( $link );
			}
		}
		$this->add( 'link-name', array( '2.4.4', '4.1.2' ), 'Links have text', $links ? $empty : null, count( $links ) . ' link(s) have an accessible name.', 'Give each link text, or alt text on a linked image. An icon-only link needs aria-label.', 'error', 'No links.' );
		$this->add( 'link-purpose', array( '2.4.4' ), 'Link text describes the destination', $links ? $generic : null, 'No links use generic text such as "click here" or "read more".', 'Rewrite generic link text to say where the link goes ("Read the 2026 budget report"). Surrounding sentence context may be enough to pass, so review each one.', 'warning', 'No links.' );
		$this->add( 'link-as-button', array( '2.1.1', '4.1.2' ), 'Links are not used as script buttons', $links ? $script : null, 'No javascript: links or "#" links with click handlers.', 'Use a <button> for actions; links should navigate.', 'warning', 'No links.' );

		foreach ( $this->doc->elements( '//a[@href]|//button|//*[@role="button" or @role="link" or @role="menuitem" or @role="tab"]|//input[@type="submit" or @type="button"]' ) as $control ) {
			$label   = WCAG_Document::normalize( $control->getAttribute( 'aria-label' ) );
			$visible = 'input' === strtolower( $control->tagName ) ? WCAG_Document::normalize( $control->getAttribute( 'value' ) ) : $this->doc->visible_text( $control );
			if ( '' === $label || '' === $visible || ! preg_match( '/\p{L}/u', $visible ) ) {
				continue;
			}
			$simple = static fn( string $s ): string => trim( preg_replace( '/[^\p{L}\p{N}]+/u', ' ', mb_strtolower( $s ) ) ?? '' );
			if ( ! str_contains( $simple( $label ), $simple( $visible ) ) ) {
				$mismatch[] = $this->doc->describe( $control ) . ' is named "' . WCAG_Document::excerpt( $label, 40 ) . '"';
			}
		}
		$labelled = $this->doc->elements( '//*[@aria-label][self::a or self::button or self::input or @role="button" or @role="link"]' );
		$this->add( 'label-in-name', array( '2.5.3' ), 'Accessible names include the visible label', $labelled ? $mismatch : null, 'Every aria-label contains the control\'s visible text.', 'Start each aria-label with the visible text, so speech-input users can say what they see.', 'error', 'No visible controls with aria-label.' );
	}

	/** 2.5.7 and 2.5.8 (new in WCAG 2.2): dragging alternatives and minimum target size. */
	private function pointer(): void {
		$drag = array();
		foreach ( $this->doc->elements( '//*[@draggable="true" or @ondragstart or @ondrag or @ondrop]' ) as $element ) {
			$drag[] = $this->doc->describe( $element );
		}
		$this->review( 'dragging', array( '2.5.7' ), 'Dragging has a single-pointer alternative', $drag, 'Confirm that anything done by dragging can also be done with single clicks or taps (for example up and down buttons).' );

		$small    = array();
		$measured = 0;
		foreach ( $this->doc->elements( '//button|//input[@type="submit" or @type="button" or @type="image" or @type="checkbox" or @type="radio" or @type="reset"]|//*[@role="button"]|//a[@href]' ) as $target ) {
			$size = $this->target_size( $target );
			if ( null === $size ) {
				continue;
			}
			++$measured;
			if ( $size[0] < 24 || $size[1] < 24 ) {
				$small[] = $this->doc->describe( $target ) . ' is ' . self::number( $size[0] ) . '×' . self::number( $size[1] ) . 'px';
			}
		}
		$this->add( 'target-size', array( '2.5.8' ), 'Targets are at least 24×24 pixels', $measured ? $small : null, $measured . ' sized target(s) are at least 24×24 CSS pixels.', 'Make buttons and icon links at least 24×24 pixels, or leave enough space around them that a 24px circle on each does not overlap another target.', 'warning', 'No buttons or icon links with a size set in the content. Theme stylesheet sizes are not evaluated.' );
	}

	/** 3.1.1 and 3.1.2: language attributes are valid. */
	private function language(): void {
		$invalid  = array();
		$elements = $this->doc->elements( '//*[@lang]', true );
		foreach ( $elements as $element ) {
			$lang = trim( $element->getAttribute( 'lang' ) );
			if ( '' !== $lang && ! self::valid_lang( $lang ) ) {
				$invalid[] = $this->doc->describe( $element ) . ' lang="' . WCAG_Document::excerpt( $lang, 20 ) . '"';
			}
		}
		$parts = array_filter( $elements, static fn( $e ) => 'html' !== strtolower( $e->tagName ) );
		$this->add( 'lang-valid', array( '3.1.2' ), 'Language changes use valid codes', $parts ? $invalid : null, count( $parts ) . ' language change(s) use valid language codes.', 'Use a valid BCP 47 language code such as "es", "fr-CA" or "zh-Hans".', 'error', 'No passages are marked as another language. If the post quotes another language, mark it (Text > Language in the block toolbar, or a lang attribute).' );
	}

	/** 3.2.2: changing a setting does not change context without warning. */
	private function input_behavior(): void {
		$change = array();
		foreach ( $this->doc->elements( '//select[@onchange]|//input[@onchange or @oninput]|//form[@onchange]' ) as $element ) {
			$change[] = $this->doc->describe( $element );
		}
		$this->review( 'on-input', array( '3.2.2' ), 'Changing a field does not navigate or submit', $change, 'Confirm these change handlers do not submit a form, open a page or move focus unexpectedly. Use an explicit button instead.' );
	}

	/** 3.3.8 (new in WCAG 2.2): logins allow password managers and paste. */
	private function authentication(): void {
		$passwords = $this->doc->elements( '//input[translate(@type,"PASSWORD","password")="password"]' );
		$blocked   = array();
		foreach ( $passwords as $password ) {
			if ( 'off' === strtolower( trim( $password->getAttribute( 'autocomplete' ) ) ) ) {
				$blocked[] = $this->doc->describe( $password ) . ' has autocomplete="off"';
			}
			foreach ( array( 'onpaste', 'oncopy', 'ondrop' ) as $handler ) {
				if ( $password->hasAttribute( $handler ) ) {
					$blocked[] = $this->doc->describe( $password ) . ' has an ' . $handler . ' handler';
				}
			}
		}
		$this->add( 'accessible-authentication', array( '3.3.8' ), 'Password fields allow password managers and paste', $passwords ? $blocked : null, 'Password fields allow password managers and pasting.', 'Remove autocomplete="off" and paste blocking from password fields (use autocomplete="current-password" or "new-password").', 'error', 'No password fields.' );

		$captcha = array();
		foreach ( $this->doc->elements( '//*[contains(translate(@class,"CAPTCH","captch"),"captcha") or contains(translate(@id,"CAPTCH","captch"),"captcha") or contains(@class,"cf-turnstile") or contains(@src,"recaptcha") or contains(@src,"hcaptcha")]', true ) as $element ) {
			$captcha[] = 'CAPTCHA (' . $this->doc->describe( $element ) . ')';
		}
		$this->review( 'captcha', array( '3.3.8' ), 'CAPTCHAs offer an accessible alternative', $captcha, 'Confirm the CAPTCHA is not a cognitive test (such as transcribing distorted text or solving a puzzle), or that an alternative method is offered.' );
	}

	/** 4.1.2 and 1.3.1: buttons, frames, ARIA roles, references and hidden focus. */
	private function name_role_value(): void {
		$buttons = $this->doc->elements( '//button|//*[@role="button"]|//input[@type="button" or @type="submit" or @type="reset"]' );
		$unnamed = array();
		foreach ( $buttons as $button ) {
			if ( '' === $this->doc->name( $button ) ) {
				$unnamed[] = $this->doc->describe( $button ) . ( '' !== trim( $button->getAttribute( 'class' ) ) ? ' (class "' . WCAG_Document::excerpt( $button->getAttribute( 'class' ), 40 ) . '")' : '' );
			}
		}
		$this->add( 'button-name', array( '4.1.2' ), 'Buttons have names', $buttons ? $unnamed : null, count( $buttons ) . ' button(s) have an accessible name.', 'Give each button visible text or, for icon-only buttons, an aria-label.', 'error', 'No buttons.' );

		$frames   = $this->doc->elements( '//iframe' );
		$untitled = array();
		foreach ( $frames as $frame ) {
			if ( '' === $this->doc->name( $frame ) ) {
				$untitled[] = $this->doc->describe( $frame );
			}
		}
		$this->add( 'frame-title', array( '4.1.2' ), 'Embedded frames have titles', $frames ? $untitled : null, count( $frames ) . ' iframe(s) have a title.', 'Add a title attribute describing each iframe, for example title="Map of the visitor center".', 'error', 'No iframes.' );

		$roles   = $this->doc->elements( '//*[@role]' );
		$invalid = array();
		foreach ( $roles as $element ) {
			$tokens = array_filter( preg_split( '/\s+/', strtolower( trim( $element->getAttribute( 'role' ) ) ) ) ?: array() );
			if ( $tokens && ! array_intersect( $tokens, self::ARIA_ROLES ) ) {
				$invalid[] = $this->doc->describe( $element ) . ' role="' . WCAG_Document::excerpt( implode( ' ', $tokens ), 30 ) . '"';
			}
		}
		$this->add( 'aria-role', array( '4.1.2' ), 'ARIA roles are valid', $roles ? $invalid : null, 'Every role attribute uses a valid WAI-ARIA role.', 'Correct or remove invalid role values; assistive technology ignores them.', 'error', 'No role attributes.' );

		$references = $this->doc->elements( '//*[@aria-labelledby or @aria-describedby]|//label[@for]', true );
		$broken     = array();
		$referenced = array();
		foreach ( $references as $element ) {
			$ids = 'label' === strtolower( $element->tagName ) && $element->hasAttribute( 'for' ) ? array( $element->getAttribute( 'for' ) ) : array();
			$ids = array_merge( $ids, preg_split( '/\s+/', trim( $element->getAttribute( 'aria-labelledby' ) . ' ' . $element->getAttribute( 'aria-describedby' ) ) ) ?: array() );
			foreach ( array_filter( $ids, 'strlen' ) as $id ) {
				$referenced[ $id ] = true;
				if ( 0 === $this->doc->id_count( $id ) ) {
					$broken[] = $this->doc->describe( $element ) . ' points to missing id "' . WCAG_Document::excerpt( $id, 30 ) . '"';
				}
			}
		}
		$this->add( 'aria-references', array( '1.3.1', '4.1.2' ), 'Label and description references exist', $references ? $broken : null, 'Every for, aria-labelledby and aria-describedby reference points to an element on the page.', 'Fix references to ids that do not exist; the label or description is silently lost.', 'error', 'No label or ARIA references.' );

		$duplicates = array();
		foreach ( $this->doc->id_counts() as $id => $count ) {
			if ( $count > 1 && isset( $referenced[ (string) $id ] ) ) {
				$duplicates[] = 'id "' . WCAG_Document::excerpt( (string) $id, 30 ) . '" is used ' . $count . ' times';
			}
		}
		$this->add( 'duplicate-referenced-id', array( '4.1.2', '1.3.1' ), 'Referenced ids are unique', $referenced ? $duplicates : null, 'Every id used by a label or ARIA reference is unique.', 'Make each referenced id unique; otherwise the wrong element supplies the label.', 'error', 'No ids are referenced by labels or ARIA.' );

		$hidden  = $this->doc->elements( '//*[@aria-hidden="true"]', true );
		$focused = array();
		foreach ( $hidden as $container ) {
			foreach ( array_merge( array( $container ), $this->doc->within( './/*', $container ) ) as $element ) {
				if ( WCAG_Document::is_focusable( $element ) && ! $element->hasAttribute( 'inert' ) ) {
					$focused[] = $this->doc->describe( $element );
				}
			}
		}
		$this->add( 'aria-hidden-focus', array( '4.1.2' ), 'Hidden content cannot receive focus', $hidden ? $focused : null, 'Nothing inside aria-hidden="true" can receive keyboard focus.', 'Remove aria-hidden from content that contains links or controls, or add tabindex="-1" to them.', 'error', 'No aria-hidden content.' );
	}

	/** Form controls, including ARIA widgets. */
	private function controls(): array {
		$controls = array();
		foreach ( $this->doc->elements( '//input|//select|//textarea|//*[@role="textbox" or @role="combobox" or @role="listbox" or @role="checkbox" or @role="radio" or @role="switch" or @role="slider" or @role="spinbutton" or @role="searchbox"]' ) as $control ) {
			if ( 'input' === strtolower( $control->tagName ) && in_array( WCAG_Document::input_type( $control ), array( 'hidden', 'submit', 'reset', 'button', 'image' ), true ) ) {
				continue;
			}
			$controls[] = $control;
		}
		return $controls;
	}

	private static function input_type_of( \DOMElement $control ): string {
		return 'input' === strtolower( $control->tagName ) ? WCAG_Document::input_type( $control ) : strtolower( $control->tagName );
	}

	/** HTML autocomplete detail tokens: optional section, optional shipping/billing, optional contact type, field name, optional webauthn. */
	private static function valid_autocomplete( string $value ): bool {
		if ( in_array( $value, array( 'on', 'off' ), true ) ) {
			return true;
		}
		$tokens = preg_split( '/\s+/', $value ) ?: array();
		if ( 'webauthn' === end( $tokens ) && count( $tokens ) > 1 ) {
			array_pop( $tokens );
		}
		$field = array_pop( $tokens );
		if ( ! in_array( $field, self::AUTOCOMPLETE, true ) ) {
			return false;
		}
		if ( $tokens && in_array( end( $tokens ), array( 'home', 'work', 'mobile', 'fax', 'pager' ), true ) ) {
			array_pop( $tokens );
		}
		if ( $tokens && in_array( end( $tokens ), array( 'shipping', 'billing' ), true ) ) {
			array_pop( $tokens );
		}
		if ( $tokens && str_starts_with( (string) end( $tokens ), 'section-' ) ) {
			array_pop( $tokens );
		}
		return ! $tokens;
	}

	/** Well-formed BCP 47 tag: a 2-3 letter language (or i-/x- prefix) and alphanumeric subtags. */
	private static function valid_lang( string $lang ): bool {
		return (bool) preg_match( '/^(?:[a-z]{2,3}|[ix])(?:-[a-z0-9]{1,8})*$/i', $lang );
	}

	private static function number( float $value ): string {
		return rtrim( rtrim( number_format( $value, 1 ), '0' ), '.' );
	}

	private function closest( \DOMElement $element, string $tag ): ?\DOMElement {
		for ( $node = $element->parentNode; $node instanceof \DOMElement; $node = $node->parentNode ) {
			if ( strtolower( $node->tagName ) === $tag ) {
				return $node;
			}
		}
		return null;
	}

	/**
	 * Effective foreground and background for an element's own text, or null when
	 * neither color is set in the content or either cannot be measured.
	 *
	 * @return array{fg: array, bg: array, assumed: string}|null
	 */
	private function resolve_colors( \DOMElement $element ): ?array {
		$fg       = null;
		$layers   = array();
		$bg_done  = false;
		$explicit = false;
		$in_link  = false;
		$link_fg  = true;
		for ( $node = $element; $node instanceof \DOMElement && 'html' !== strtolower( $node->tagName ); $node = $node->parentNode ) {
			$own     = $this->own_colors( $node );
			$is_link = 'a' === strtolower( $node->tagName );
			$in_link = $in_link || $is_link;
			if ( null === $fg ) {
				if ( $own['fg_unknown'] ) {
					return null;
				}
				if ( null !== $own['fg'] ) {
					$fg       = $own['fg'];
					$explicit = true;
					// Theme stylesheets color links; a color inherited from outside the link says nothing about it.
					$link_fg = ! $in_link || $is_link;
				}
			}
			if ( ! $bg_done ) {
				if ( $own['bg_unknown'] ) {
					return null; // A gradient, image or unparseable value: contrast cannot be measured here.
				}
				if ( null !== $own['bg'] && $own['bg'][3] > 0 ) {
					$layers[] = $own['bg'];
					$explicit = true;
					$bg_done  = $own['bg'][3] >= 1;
				}
			}
			if ( null !== $fg && $bg_done ) {
				break;
			}
		}
		if ( ! $explicit || ( $in_link && ( null === $fg || ! $link_fg ) ) ) {
			return null;
		}
		$assumed = array();
		$bg      = null;
		if ( ! $bg_done ) {
			$bg = $this->context['background'];
			if ( ! empty( $this->context['background_assumed'] ) ) {
				$assumed[] = 'white page background';
			}
		}
		foreach ( array_reverse( $layers ) as $layer ) {
			$bg = null === $bg ? $layer : WCAG_Color::over( $layer, $bg );
		}
		if ( null === $fg ) {
			$fg = $this->context['text'];
			if ( ! empty( $this->context['text_assumed'] ) ) {
				$assumed[] = 'black theme text';
			}
		}
		if ( $fg[3] < 1 ) {
			$fg = WCAG_Color::over( $fg, $bg );
		}
		return array(
			'fg'      => $fg,
			'bg'      => $bg,
			'assumed' => implode( ' and ', $assumed ),
		);
	}

	/**
	 * Colors set on this element by preset classes or inline style. WordPress
	 * outputs preset color classes with !important, so they win over inline style.
	 *
	 * @return array{fg: ?array, bg: ?array, fg_unknown: bool, bg_unknown: bool}
	 */
	private function own_colors( \DOMElement $element ): array {
		$palette = $this->context['palette'] ?? array();
		$own     = array(
			'fg'         => null,
			'bg'         => null,
			'fg_unknown' => false,
			'bg_unknown' => false,
		);
		$style   = WCAG_Document::style( $element );
		if ( isset( $style['color'] ) && ! in_array( strtolower( $style['color'] ), array( 'inherit', 'initial', 'unset', 'currentcolor', 'revert' ), true ) ) {
			$own['fg']         = WCAG_Color::parse( $style['color'], $palette );
			$own['fg_unknown'] = null === $own['fg'];
		}
		if ( isset( $style['background-image'] ) && 'none' !== strtolower( $style['background-image'] ) ) {
			$own['bg_unknown'] = true;
		}
		foreach ( array( 'background', 'background-color' ) as $property ) {
			if ( ! isset( $style[ $property ] ) || in_array( strtolower( $style[ $property ] ), array( 'inherit', 'initial', 'unset', 'none', 'revert' ), true ) ) {
				continue;
			}
			$value = $style[ $property ];
			if ( preg_match( '/gradient\(|url\(|image-set\(/i', $value ) ) {
				$own['bg_unknown'] = true;
				continue;
			}
			$parsed = WCAG_Color::parse( $value, $palette );
			if ( null === $parsed && 'background' === $property ) {
				foreach ( preg_split( '/\s+(?![^(]*\))/', $value ) ?: array() as $token ) {
					$parsed = $parsed ?? WCAG_Color::parse( $token, $palette );
				}
			}
			$own['bg']         = $parsed;
			$own['bg_unknown'] = $own['bg_unknown'] || null === $parsed;
		}
		if ( $element->hasAttribute( 'bgcolor' ) && null === $own['bg'] ) {
			$own['bg'] = WCAG_Color::parse( $element->getAttribute( 'bgcolor' ), $palette );
		}
		foreach ( WCAG_Document::classes( $element ) as $class ) {
			if ( preg_match( '/^has-.+-gradient-background$|^has-background-gradient$/', $class ) ) {
				$own['bg_unknown'] = true;
			} elseif ( preg_match( '/^has-(.+)-background-color$/', $class, $match ) ) {
				$own['bg']         = isset( $palette[ $match[1] ] ) ? WCAG_Color::parse( $palette[ $match[1] ], $palette ) : null;
				$own['bg_unknown'] = null === $own['bg'];
			} elseif ( preg_match( '/^has-(.+)-color$/', $class, $match ) && ! in_array( $match[1], array( 'text', 'link', 'background', 'border' ), true ) && ! str_ends_with( $match[1], '-border' ) ) {
				$own['fg']         = isset( $palette[ $match[1] ] ) ? WCAG_Color::parse( $palette[ $match[1] ], $palette ) : null;
				$own['fg_unknown'] = null === $own['fg'];
			}
		}
		return $own;
	}

	/**
	 * WCAG large text: at least 24px, or 18.66px and bold.
	 *
	 * @return array{0: bool, 1: bool} Large, and whether the size is known.
	 */
	private function is_large( \DOMElement $element ): array {
		$size  = null;
		$known = true;
		$bold  = null;
		$sizes = $this->context['font_sizes'] ?? array();
		for ( $node = $element; $node instanceof \DOMElement; $node = $node->parentNode ) {
			$tag   = strtolower( $node->tagName );
			$style = WCAG_Document::style( $node );
			if ( null === $size ) {
				if ( isset( $style['font-size'] ) ) {
					$size  = self::px( $style['font-size'] );
					$known = null !== $size;
					$size ??= 16.0;
				} else {
					foreach ( WCAG_Document::classes( $node ) as $class ) {
						if ( preg_match( '/^has-(.+)-font-size$/', $class, $match ) ) {
							$size  = isset( $sizes[ $match[1] ] ) ? (float) $sizes[ $match[1] ] : 16.0;
							$known = isset( $sizes[ $match[1] ] );
							break;
						}
					}
					if ( null === $size && isset( self::HEADING_PX[ $tag ] ) ) {
						$size = self::HEADING_PX[ $tag ];
					}
				}
			}
			if ( null === $bold ) {
				if ( isset( $style['font-weight'] ) ) {
					$weight = strtolower( $style['font-weight'] );
					$bold   = in_array( $weight, array( 'bold', 'bolder' ), true ) || ( is_numeric( $weight ) && (int) $weight >= 700 );
				} elseif ( in_array( $tag, array( 'strong', 'b', 'th', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
					$bold = true;
				}
			}
			if ( ( null !== $size && null !== $bold ) || 'body' === $tag ) {
				break;
			}
		}
		$size ??= 16.0;
		return array( $size >= 24 || ( $bold && $size >= 18.66 ), $known );
	}

	/** CSS length in pixels, assuming a 16px root; null when relative to unknown context. */
	public static function px( string $value ): ?float {
		$value = strtolower( trim( $value ) );
		if ( isset( self::KEYWORD_PX[ $value ] ) ) {
			return self::KEYWORD_PX[ $value ];
		}
		if ( ! preg_match( '/^(\d*\.?\d+)(px|pt|rem|em|%)?$/', $value, $match ) ) {
			return null;
		}
		$number = (float) $match[1];
		return match ( $match[2] ?? '' ) {
			'px' => $number,
			'pt' => $number * 4 / 3,
			'rem', 'em' => $number * 16,
			'%' => $number * 0.16,
			default => 0.0 === $number ? 0.0 : null,
		};
	}

	/**
	 * A target's width and height from inline styles or the dimensions of the only image it contains.
	 *
	 * @return array{0: float, 1: float}|null
	 */
	private function target_size( \DOMElement $target ): ?array {
		$style  = WCAG_Document::style( $target );
		$width  = isset( $style['width'] ) ? self::px( $style['width'] ) : null;
		$height = isset( $style['height'] ) ? self::px( $style['height'] ) : null;
		if ( null !== $width && null !== $height ) {
			return array( $width, $height );
		}
		$tag = strtolower( $target->tagName );
		if ( 'input' === $tag && 'image' === WCAG_Document::input_type( $target ) ) {
			$image = $target;
		} else {
			// Inline text links are exempt; only links and buttons that are just an image are measured.
			if ( '' !== $this->doc->visible_text( $target ) ) {
				return null;
			}
			$images = $this->doc->within( './/img|.//svg', $target );
			if ( 1 !== count( $images ) ) {
				return null;
			}
			$image = $images[0];
		}
		$style  = WCAG_Document::style( $image );
		$width  = self::px( $style['width'] ?? $image->getAttribute( 'width' ) );
		$height = self::px( $style['height'] ?? $image->getAttribute( 'height' ) );
		if ( null === $width && is_numeric( $image->getAttribute( 'width' ) ) ) {
			$width = (float) $image->getAttribute( 'width' );
		}
		if ( null === $height && is_numeric( $image->getAttribute( 'height' ) ) ) {
			$height = (float) $image->getAttribute( 'height' );
		}
		return null !== $width && null !== $height && $width > 0 && $height > 0 ? array( $width, $height ) : null;
	}
}
