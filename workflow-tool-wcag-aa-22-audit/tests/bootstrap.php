<?php
/** Standalone plugin registration, settings and dependency-guard checks. */
declare(strict_types=1);

$without = in_array('--without-core', $argv, true);
function add_action(string $name, mixed $callback): void { $GLOBALS['hooks'][$name][] = $callback; }
if (!$without) {
 require __DIR__ . '/stubs.php';
 function vip_workflows_register_ability(string $id, array $args): void { $GLOBALS['registered'][$id] = $args; }
 function wp_register_ability(string $id, array $args): void { $GLOBALS['registered'][$id] = $args; }
} else {
 define('ABSPATH', __DIR__);
}
require __DIR__ . '/../workflow-tool-wcag-aa-22-audit.php';
foreach ($GLOBALS['hooks']['wp_abilities_api_init'] as $callback) { $callback(); }
if ($without) {
 if (!empty($GLOBALS['registered'])) { throw new RuntimeException('Must not register without Workflows.'); }
 echo "PASS: missing dependency guard\n";
 exit;
}
use WorkflowToolWcagAa22Audit\WCAG_Settings as Settings;
use VIPWorkflows\Abilities\AbilitySettings as Store;
$reg = $GLOBALS['registered'];
$audit = 'workflow-tool-wcag-aa-22-audit/wcag-audit';
$gate = 'workflow-tool-wcag-aa-22-audit/wcag-gate';
check(3 === count($reg) && isset($reg[$audit], $reg[$gate], $reg['workflow-tool-wcag-aa-22-audit/wcag-settings']), 'Report, gate and settings register under namespaced IDs');
check('vip-workflows' === $reg[$audit]['category'] && 'validator' === $reg[$audit]['meta']['type'] && 'report' === $reg[$audit]['meta']['result_type'], 'Report declares category, type and result_type');
check(false === $reg[$audit]['meta']['transition_eligible'] && $reg[$audit]['meta']['show_in_commands'], 'Report is on demand and in the command palette');
check($reg[$gate]['meta']['transition_eligible'] && false === $reg[$gate]['input_schema']['additionalProperties'], 'Gate is transition eligible and rejects extra input');
check(!isset($reg[$audit]['input_schema']['additionalProperties']), 'Report input accepts the options the editor sends alongside post_id');
check(['saved-content','public'] === $reg[$audit]['meta']['settings_schema']['audit_mode']['enum'] && ['errors','errors-and-warnings'] === $reg[$audit]['meta']['settings_schema']['fail_on']['enum'], 'Settings are selects');

$GLOBALS['allowed'] = true; Store::$options = [];
$out = Settings::execute(['site_url'=>'https://example.test', 'settings'=>['fail_on'=>'errors-and-warnings']]);
check(true === $out['updated'] && 'errors-and-warnings' === Store::$options['fail_on'], 'Administrator can update the strictness');
$out = Settings::execute(['site_url'=>'https://example.test', 'settings'=>['render_blocks'=>true]]);
check(true === Store::$options['render_blocks'] && !isset(Store::$extra['workflow-tool-wcag-aa-22-audit/wcag-audit']['render_blocks']), 'Render dynamic blocks is stored as a tool option');
check(Settings::execute(['site_url'=>'https://example.test', 'settings'=>['render_blocks'=>'yes']]) instanceof WP_Error, 'Render dynamic blocks must be a boolean');
check(false === $reg[$audit]['meta']['settings_schema']['render_blocks']['default'], 'Rendering is off by default');
check(false === $reg[$audit]['meta']['settings_schema']['include_template']['default'], 'Template inclusion is off by default');
check(Settings::execute(['site_url'=>'https://example.test', 'settings'=>['include_template'=>true]])['updated'] && true === Store::$options['include_template'], 'Include theme template is a settable tool option');
check(Settings::execute(['site_url'=>'https://other.test']) instanceof WP_Error, 'Settings refuse a different site');
check(Settings::execute(['site_url'=>'https://example.test', 'settings'=>['fail_on'=>'never']]) instanceof WP_Error, 'Settings reject invalid values');
$out = Settings::execute(['site_url'=>'https://example.test', 'settings'=>['min_score'=>85]]);
check(true === $out['updated'] && 85 === Store::$options['min_score'], 'Administrator can set the minimum score');
foreach ([101, -1, '90', true, 1.5] as $bad) { check(Settings::execute(['site_url'=>'https://example.test', 'settings'=>['min_score'=>$bad]]) instanceof WP_Error, 'Settings reject minimum score ' . json_encode($bad)); }
check(0 === $reg[$audit]['meta']['settings_schema']['min_score']['default'] && 100 === $reg[$audit]['meta']['settings_schema']['min_score']['maximum'], 'Minimum score is an adjustable 0-100 setting, off by default');
check(Settings::execute(['site_url'=>'https://example.test', 'settings'=>['blogname'=>'x']]) instanceof WP_Error, 'Settings reject unrelated options');
$GLOBALS['allowed'] = false;
check(Settings::execute(['site_url'=>'https://example.test']) instanceof WP_Error, 'Settings require manage_options');
echo 'PASS: ' . (int) $GLOBALS['checks'] . " registration and settings checks\n";
