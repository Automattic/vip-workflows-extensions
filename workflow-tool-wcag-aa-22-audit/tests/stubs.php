<?php
/** WordPress and VIP Workflows boundaries stubbed for standalone tests. */
declare(strict_types=1);
namespace VIPWorkflows\Abilities {
 class AbilitySettings {
  public static array $options = [];
  public static array $extra = [];
  public static bool $write_ok = true;
  public static function get_instance(): self { return new self(); }
  public function get_options(string $id): array { return self::$options; }
  public function get(string $id): array { return array_merge(['enabled'=>true,'options'=>self::$options], self::$extra[$id] ?? []); }
  public function update(string $id, array $values): bool { if (!self::$write_ok) { return false; } self::$options = array_merge(self::$options, $values['options'] ?? []); unset($values['options']); self::$extra[$id] = array_merge(self::$extra[$id] ?? [], $values); return true; }
 }
}
namespace {
 if (!defined('ABSPATH')) { define('ABSPATH', __DIR__); }
 class WP_Error { public function __construct(public string $code, public string $message, public mixed $data = null) {} }
 class WP_Post { public int $ID = 1; public string $post_type = 'post'; public string $post_status = 'draft'; public string $post_password = ''; public string $post_title = 'Visitor center hours'; public string $post_content = ''; public string $post_excerpt = ''; public string $post_name = 'hours'; }
 $GLOBALS['allowed'] = true;
 $GLOBALS['palette'] = ['base'=>'#ffffff','contrast'=>'#111111','muted'=>'#999999','primary'=>'#1e40af'];
 $GLOBALS['styles'] = ['text'=>'var(--wp--preset--color--contrast)','background'=>'var(--wp--preset--color--base)'];
 $GLOBALS['requests'] = [];
 function __(string $s, string $d = ''): string { return $s; }
 function current_user_can(string $cap, int $id = 0): bool { return $GLOBALS['allowed']; }
 function get_post(int $id): ?WP_Post { return 1 === $id ? $GLOBALS['wcag_post'] : ($GLOBALS['wcag_synced'][$id] ?? null); }
 function get_permalink(int $id): string { return 'https://example.test/hours/'; }
 function is_post_type_viewable(string $type): bool { return in_array($type, ['post','page','event'], true); }
 function admin_url(string $path = ''): string { return 'https://example.test/wp-admin/' . $path; }
function home_url(string $path = '/'): string { return 'https://example.test/'; }
 function wp_parse_url(string $url, int $component = -1): mixed {
  // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Standalone stub.
  return parse_url($url, $component);
 }
 function is_wp_error(mixed $v): bool { return $v instanceof WP_Error; }
 function strip_shortcodes(string $s): string { return $s; }
 function wp_strip_all_tags(string $s): string {
  // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- Standalone WordPress test double removes script/style contents before stripping tags.
  return strip_tags(preg_replace('@<(script|style)[^>]*?>.*?</\1>@si', '', $s));
 }
 function wp_json_encode(mixed $v, int $flags = 0): string {
  // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone stub.
  return json_encode($v, $flags);
 }
 function wp_get_global_settings(array $path): array {
  if (['color','palette'] === $path) { return ['theme'=>array_map(fn($slug,$color)=>['slug'=>$slug,'color'=>$color], array_keys($GLOBALS['palette']), $GLOBALS['palette'])]; }
  return ['theme'=>[['slug'=>'huge','size'=>'clamp(2rem, 4vw, 3rem)'],['slug'=>'large','size'=>'1.5rem']]];
 }
 function wp_get_global_styles(array $path): array { return $GLOBALS['styles']; }
 function wp_remote_retrieve_response_code(array $r): int { return $r['code']; }
 function wp_remote_retrieve_body(array $r): string { return $r['body']; }
 function wp_remote_retrieve_header(array $r, string $name): mixed { return $r['headers'][$name] ?? ''; }
 function wp_safe_remote_get(string $url, array $options): array|WP_Error { $GLOBALS['requests'][] = [$url, $options]; return $GLOBALS['page_response']; }
 require __DIR__ . '/../includes/class-post-tool.php';
 require __DIR__ . '/../includes/class-wcag-color.php';
 require __DIR__ . '/../includes/class-wcag-document.php';
 require __DIR__ . '/../includes/class-wcag-rules.php';
 require __DIR__ . '/../includes/class-wcag-content.php';
 require __DIR__ . '/../includes/class-wcag-audit.php';
 require __DIR__ . '/../includes/class-wcag-gate.php';
 require __DIR__ . '/../includes/class-wcag-settings.php';
 $GLOBALS['checks'] = 0;
 function check(bool $ok, string $label): void {
  ++$GLOBALS['checks'];
  if (!$ok) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only assertion label is a literal supplied by these tests, never HTML output.
   throw new \RuntimeException('FAIL: ' . $label);
  }
 }
 function reset_case(string $content = '', array $options = []): void {
  $GLOBALS['wcag_post'] = new WP_Post();
  $GLOBALS['wcag_post']->post_content = $content;
  $GLOBALS['allowed'] = true;
  $GLOBALS['requests'] = [];
  \VIPWorkflows\Abilities\AbilitySettings::$options = $options;
 }
 /** Run the saved-content audit and return one rule's result row. */
 function rule(string $html, string $slug, bool $page = false): array {
  if ($page) {
   reset_case('', ['audit_mode'=>'public']);
   $GLOBALS['wcag_post']->post_status = 'publish';
   $GLOBALS['page_response'] = ['code'=>200,'body'=>$html,'headers'=>['content-type'=>'text/html; charset=UTF-8']];
  } else {
   reset_case($html);
  }
  $report = \WorkflowToolWcagAa22Audit\WCAG_Audit::execute(['post_id'=>1]);
  foreach ($report['checks'] as $row) { if ($row['rule'] === $slug) { return $row; } }
  // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only test failure naming a rule slug supplied by these tests.
  throw new RuntimeException('No rule ' . $slug);
 }
}
