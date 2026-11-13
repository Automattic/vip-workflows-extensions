<?php
/** Saved-content preparation: synced patterns, patterns, template parts, dynamic-block notes and opt-in rendering. */
declare(strict_types=1);
require __DIR__ . '/stubs.php';
use WorkflowToolWcagAa22Audit\WCAG_Audit as Audit;
use WorkflowToolWcagAa22Audit\WCAG_Gate as Gate;

class WP_Block_Patterns_Registry {
 public static array $patterns = [];
 public static function get_instance(): self { return new self(); }
 public function get_registered(string $slug): ?array { return self::$patterns[$slug] ?? null; }
}
class WP_Block_Type {
 public function __construct(public string $title, private bool $dynamic) {}
 public function is_dynamic(): bool { return $this->dynamic; }
}
class WP_Block_Type_Registry {
 public static function get_instance(): self { return new self(); }
 public function get_registered(string $name): ?WP_Block_Type {
  return [
   'core/paragraph'=>new WP_Block_Type('Paragraph', false), 'core/group'=>new WP_Block_Type('Group', false),
   'core/image'=>new WP_Block_Type('Image', false), 'core/query'=>new WP_Block_Type('Query Loop', true),
   'core/post-title'=>new WP_Block_Type('Title', true), 'core/latest-posts'=>new WP_Block_Type('Latest Posts', true),
   'core/block'=>new WP_Block_Type('Pattern', true), 'core/pattern'=>new WP_Block_Type('Pattern placeholder', true),
   'core/template-part'=>new WP_Block_Type('Template Part', true), 'core/navigation'=>new WP_Block_Type('Navigation', true),
  ][$name] ?? null;
 }
}
function get_stylesheet(): string { return 'gov-theme'; }
function get_block_template(string $id, string $type): ?object { return $GLOBALS['parts'][$id] ?? null; }
$GLOBALS['block_theme'] = true;
$GLOBALS['wcag_templates'] = [];
function wp_is_block_theme(): bool { return $GLOBALS['block_theme']; }
function get_page_template_slug(WP_Post $post): string { return $GLOBALS['custom_template'] ?? ''; }
function get_block_templates(array $query, string $type): array {
 $GLOBALS['template_query'] = $query['slug__in'];
 return array_values(array_filter($GLOBALS['wcag_templates'], fn($t) => in_array($t->slug, $query['slug__in'], true)));
}
function tpl(string $slug, string $title, string $content): void { $GLOBALS['wcag_templates'][] = (object) ['slug'=>$slug, 'title'=>$title, 'content'=>$content]; }
function setup_postdata(WP_Post $post): void { $GLOBALS['setup'][] = $post->ID; }
function do_shortcode(string $s): string { return str_replace('[hours]', '<p>Open 9 to 5</p>', $s); }
$GLOBALS['filters'] = [];
function add_filter(string $tag, callable $cb, int $priority = 10, int $args = 1): bool { $GLOBALS['filters'][$tag][] = $cb; return true; }
function remove_filter(string $tag, callable $cb, int $priority = 10): bool { $GLOBALS['filters'][$tag] = array_values(array_filter($GLOBALS['filters'][$tag] ?? [], fn($f) => $f !== $cb)); return true; }
function do_blocks(string $s): string {
 if (!empty($GLOBALS['render_throws'])) { throw new RuntimeException('block exploded'); }
 $GLOBALS['rendered_input'] = $s;
 echo 'stray output';
 $html = '<ul class="wp-block-latest-posts"><li><a href="/news/1"></a></li></ul>';
 if (preg_match('/wp:latest-posts \{"wcagPath":"([0-9.]+)"/', $s, $m)) { foreach ($GLOBALS['filters']['render_block'] ?? [] as $f) { $html = $f($html, ['blockName'=>'core/latest-posts', 'attrs'=>['wcagPath'=>$m[1]]]); } }
 if (str_contains($s, 'wp:template-part {"slug":"header"')) {
  $part = '<header><form class="wp-block-search"><button class="wp-block-search__button"></button></form></header>';
  foreach ($GLOBALS['filters']['render_block'] ?? [] as $f) { $part = $f($part, ['blockName'=>'core/template-part', 'attrs'=>['slug'=>'header', 'theme'=>'gov-theme']]); }
  return $part;
 }
 return str_replace('<!-- wp:latest-posts /-->', $html, preg_replace('/<!-- wp:latest-posts \{[^}]*\} \/-->/', $html, $s));
}
function synced(int $id, string $title, string $content, string $status = 'publish'): void {
 $p = new WP_Post(); $p->ID = $id; $p->post_type = 'wp_block'; $p->post_status = $status; $p->post_title = $title; $p->post_content = $content;
 $GLOBALS['wcag_synced'][$id] = $p;
}
function audit(string $content, array $options = []): array {
 reset_case($content, $options);
 return Audit::execute(['post_id'=>1]);
}
function row(array $report, string $slug): array { foreach ($report['checks'] as $r) { if ($r['rule'] === $slug) { return $r; } } throw new RuntimeException('missing'); }
function note(array $report, string $rule): string { foreach ($report['issues'] as $i) { if ($i['rule'] === $rule) { return $i['message']; } } return ''; }

// Synced patterns (core/block) are inlined from their saved markup.
synced(41, 'Site header', '<!-- wp:image --><figure><img src="logo.png"></figure><!-- /wp:image -->');
$r = audit('<!-- wp:block {"ref":41} /--><!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->');
check('fail' === row($r, 'img-alt')['status'], 'An image inside a synced pattern is checked');
check([['type'=>'synced', 'id'=>'41']] === row($r, 'img-alt')['blocks'], 'A finding inside a synced pattern points at the pattern');
check(str_contains(note($r, 'Included content'), 'synced pattern "Site header" (#41)'), 'The report names the synced pattern it included');
check('' === note($r, 'Content not checked'), 'An expanded synced pattern is not reported as unchecked');
check(['synced pattern "Site header" (#41)'] === $r['included'] && false === $r['rendered'], 'Report fields list included content');

// Nested patterns, cycles, trashed and missing references.
synced(42, 'Footer', '<!-- wp:block {"ref":43} /-->'); synced(43, 'Footer links', '<p><a href="/contact">Contact</a></p><!-- wp:block {"ref":42} /-->');
$r = audit('<!-- wp:block {"ref":42} /-->');
check('pass' === row($r, 'link-name')['status'] && str_contains(note($r, 'Content not found'), 'includes itself'), 'Nested patterns expand and a cycle stops with a note');
synced(44, 'Old', '<img src="x.png">', 'trash');
$r = audit('<!-- wp:block {"ref":44} /--><!-- wp:block {"ref":999} /-->');
check('not-applicable' === row($r, 'img-alt')['status'], 'Trashed synced patterns are not inlined');
check(str_contains(note($r, 'Content not found'), 'synced pattern #44') && str_contains(note($r, 'Content not found'), 'synced pattern #999'), 'Missing and trashed patterns are named once as not found');
check('' === note($r, 'Content not checked'), 'A missing pattern is not also reported as unchecked');

// Registered patterns (core/pattern) and template parts (core/template-part).
WP_Block_Patterns_Registry::$patterns['gov-theme/footer'] = ['content'=>'<!-- wp:paragraph --><p><a href="/privacy">Click here</a></p><!-- /wp:paragraph -->'];
$GLOBALS['parts']['gov-theme//header'] = (object) ['content'=>'<!-- wp:navigation /--><p><a href="/">Home</a></p>'];
$r = audit('<!-- wp:template-part {"slug":"header","tagName":"header"} /--><h2>Hours</h2><!-- wp:pattern {"slug":"gov-theme/footer"} /-->');
check('fail' === row($r, 'link-purpose')['status'], 'Registered pattern content is checked');
check(str_contains(note($r, 'Included content'), 'template part "header"') && str_contains(note($r, 'Included content'), 'pattern "gov-theme/footer"'), 'Template parts and patterns are named');
check(str_contains(note($r, 'Content not checked'), 'Navigation (core/navigation)'), 'A dynamic block inside a template part is named as unchecked');
reset_case(''); $doc = \WorkflowToolWcagAa22Audit\WCAG_Document::load('<header><p>x</p></header>', false);
check(1 === count($doc->elements('//header')), 'Template parts keep their landmark tag');

// Clearer note: names and counts each skipped block; inner blocks of a dynamic block are not counted again.
$r = audit('<!-- wp:query {"queryId":1} --><div><!-- wp:post-title /--></div><!-- /wp:query --><!-- wp:latest-posts /--><!-- wp:latest-posts /--><!-- wp:paragraph --><p>Static</p><!-- /wp:paragraph -->');
$message = note($r, 'Content not checked');
check(str_contains($message, 'Query Loop (core/query)') && str_contains($message, 'Latest Posts (core/latest-posts) ×2'), 'Dynamic blocks are named with counts');
check(!str_contains($message, 'core/post-title') && !str_contains($message, 'Paragraph'), 'Blocks inside a dynamic block and static blocks are not listed');
check(['Query Loop (core/query)'=>1, 'Latest Posts (core/latest-posts)'=>2] === $r['unchecked'], 'Report field lists unchecked blocks');
check('' === note(audit('<!-- wp:paragraph --><p>Static</p><!-- /wp:paragraph -->'), 'Content not checked'), 'No note when everything was checked');

// Opt-in rendering.
$r = audit('<!-- wp:latest-posts /-->[hours]', ['render_blocks'=>true]);
check(true === $r['rendered'] && 'fail' === row($r, 'link-name')['status'], 'Rendered dynamic block output is checked');
check(str_contains(note($r, 'Rendered content'), 'Render dynamic blocks') && '' === note($r, 'Content not checked'), 'Rendering is stated and nothing is reported as unchecked');
check(1 === ($GLOBALS['setup'][0] ?? null), 'The audited post is set up as the current post while rendering');
$GLOBALS['render_throws'] = true;
check(Audit::execute(['post_id'=>1]) instanceof WP_Error, 'A rendering failure is an error, not a pass');
reset_case('<!-- wp:latest-posts /-->', ['render_blocks'=>true]);
check(Gate::execute(['post_id'=>1]) instanceof WP_Error, 'The gate fails closed when rendering fails');
$GLOBALS['render_throws'] = false;
check(false === audit('<!-- wp:latest-posts /-->', ['render_blocks'=>'yes'])['rendered'], 'Only a real true turns rendering on');
ob_start(); audit('<!-- wp:latest-posts /-->', ['render_blocks'=>true]); $stray = ob_get_clean();
check('' === $stray, 'Stray block output is discarded, so it cannot corrupt the REST response');

// Include theme template: the post is checked inside the template WordPress would use.
$GLOBALS['parts']['gov-theme//header'] = (object) ['content'=>'<!-- wp:image --><figure><img src="seal.png"></figure><!-- /wp:image -->'];
$GLOBALS['parts']['gov-theme//footer'] = (object) ['content'=>'<p><a href="/foia">Read more</a></p>'];
$frame = '<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main"} --><main><!-- wp:post-title {"level":1} /--><!-- wp:post-content {"layout":{"type":"constrained"}} /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
tpl('single', 'Single Posts', $frame);
tpl('index', 'Index', '<p>index</p>');
$body = '<!-- wp:heading --><h2>Hours</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Open daily.</p><!-- /wp:paragraph -->';
$r = audit($body);
check('' === $r['template'] && 'not-applicable' === row($r, 'img-alt')['status'], 'Off by default: the template is not included');
$r = audit($body, ['include_template'=>true]);
check('template "Single Posts" (single)' === $r['template'], 'The single template is chosen for a post');
check('fail' === row($r, 'img-alt')['status'] && str_contains(row($r, 'img-alt')['message'], 'seal.png'), 'An image in the template header is checked');
check('fail' === row($r, 'link-purpose')['status'], 'A link in the template footer is checked');
check([['type'=>'part', 'id'=>'gov-theme//header']] === row($r, 'img-alt')['blocks'] && [['type'=>'part', 'id'=>'gov-theme//footer']] === row($r, 'link-purpose')['blocks'], 'Findings in template parts point at the template part');
check(str_contains(note($r, 'Included template'), 'template "Single Posts" (single)'), 'The report names the template');
check(str_contains(note($r, 'Content not checked'), 'core/post-title'), 'Dynamic template blocks are named as unchecked');
check(!str_contains(note($r, 'Content not checked'), 'post-content'), 'The post content placeholder is replaced, not reported');
check(str_contains(row($r, 'img-alt')['message'], 'seal.png') && 'pass' === row($r, 'heading-order')['status'], 'With a template, an H2 after the template H1 is not a skipped level');
$GLOBALS['custom_template'] = 'single-press-release'; tpl('single-press-release', 'Press release', '<!-- wp:post-content /-->');
$r = audit($body, ['include_template'=>true]);
check('template "Press release" (single-press-release)' === $r['template'], 'A custom template assigned to the post wins');
check('single-press-release' === $GLOBALS['template_query'][0] && 'index' === end($GLOBALS['template_query']), 'Hierarchy runs from the custom template to index');
unset($GLOBALS['custom_template']);
reset_case($body, ['include_template'=>true]); $GLOBALS['wcag_post']->post_type = 'page'; $GLOBALS['wcag_post']->post_name = 'about';
\WorkflowToolWcagAa22Audit\WCAG_Audit::execute(['post_id'=>1]);
check(['page-about','page-1','page','singular','index'] === $GLOBALS['template_query'], 'Pages follow the page template hierarchy');
$GLOBALS['wcag_templates'] = []; tpl('index', 'Index', '<!-- wp:template-part {"slug":"footer"} /-->');
$r = audit($body, ['include_template'=>true]);
check('template "Index" (index)' === $r['template'] && str_contains(note($r, 'Included template'), 'no Post Content block') && 'pass' === row($r, 'empty-heading')['status'], 'A template without Post Content still checks the post, appended');
$GLOBALS['block_theme'] = false;
$r = audit($body, ['include_template'=>true]);
check('' === $r['template'] && str_contains(note($r, 'Template not included'), 'not a block theme'), 'Classic themes say the template could not be included');
check('pass' === row($r, 'empty-heading')['status'], 'The post is still audited when no template is found');
$GLOBALS['block_theme'] = true;
$GLOBALS['wcag_templates'] = []; tpl('single', 'Single Posts', $frame);
$r = audit('<!-- wp:latest-posts /-->', ['include_template'=>true, 'render_blocks'=>true]);
check(str_contains($GLOBALS['rendered_input'], 'wp:template-part {"slug":"header"') && str_contains($GLOBALS['rendered_input'], 'wp:post-content'), 'Rendering receives the template, with Post Content left for core to render');
check(true === $r['rendered'] && str_contains(note($r, 'Included template'), 'Single Posts') && !str_contains(note($r, 'Rendered content'), 'not included'), 'Rendering combines with the template');

// Rendered output keeps a marker so the finding can link to its block, and filters are removed afterwards.
$r = audit('<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph --><!-- wp:latest-posts /-->', ['render_blocks'=>true]);
check([['type'=>'block', 'path'=>'1', 'name'=>'core/latest-posts']] === row($r, 'link-name')['blocks'] && [] === $GLOBALS['filters']['render_block'], 'Rendered findings link to their block and the render filter is removed');

// Rendered template: a finding in the header (e.g. the search block) points at the header template part.
$r = audit('<!-- wp:latest-posts /-->', ['include_template'=>true, 'render_blocks'=>true]);
check([['type'=>'part', 'id'=>'gov-theme//header']] === row($r, 'button-name')['blocks'], 'Rendered template findings point at their template part');

echo 'PASS: ' . (int) $GLOBALS['checks'] . " content checks\n";
