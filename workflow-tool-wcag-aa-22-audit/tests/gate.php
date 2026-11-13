<?php
/** Gate verdicts must agree with the editor report and fail closed. */
declare(strict_types=1);
require __DIR__ . '/stubs.php';
use WorkflowToolWcagAa22Audit\WCAG_Gate as Gate;
use WorkflowToolWcagAa22Audit\WCAG_Audit as Audit;

$clean = '<h2>Hours</h2><p>Open daily from 9 to 5.</p><img src="a.jpg" alt="Visitor center entrance">';
reset_case($clean); $gate = Gate::execute(['post_id'=>1]);
check('pass' === $gate['status'] && [] === $gate['issues'] && [] === $GLOBALS['requests'], 'Clean draft passes without HTTP or spurious issues');

reset_case('<img src="a.jpg">'); $gate = Gate::execute(['post_id'=>1]);
check('fail' === $gate['status'] && 'error' === $gate['issues'][0]['severity'], 'A definite failure is a hard error');
check(str_contains($gate['issues'][0]['message'], 'alt text'), 'Blocked transition includes remediation');
check(!str_contains($gate['issues'][0]['message'], 'How to fix'), 'Findings carry no extra how-to-fix text');
check($gate['score'] === Audit::execute(['post_id'=>1])['score'], 'Gate and on-demand report agree');

reset_case('<!-- wp:heading --><h2>Hours</h2><!-- /wp:heading --><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph --><!-- wp:image --><figure><img src="a.jpg"></figure><!-- /wp:image --></div><!-- /wp:group -->'); $gate = Gate::execute(['post_id'=>1]);

reset_case('<h2>Hours</h2><p><a href="/fees">Click here</a></p>'); $gate = Gate::execute(['post_id'=>1]);
check('warning' === $gate['status'] && 'warning' === $gate['issues'][0]['severity'] && 1 === count($gate['issues']), 'Warnings alone produce acknowledgeable warnings');
reset_case('<h2>Hours</h2><p><a href="/fees">Click here</a></p>', ['fail_on'=>'errors-and-warnings']); $gate = Gate::execute(['post_id'=>1]);
check('fail' === $gate['status'] && 'error' === $gate['issues'][0]['severity'], 'Strict mode turns warnings into a hard failure');

reset_case('<h2>Hours</h2><p><a href="/fees">Click here</a></p>');
$score = Gate::execute(['post_id'=>1])['score'];
reset_case('<h2>Hours</h2><p><a href="/fees">Click here</a></p>', ['min_score'=>$score]); $gate = Gate::execute(['post_id'=>1]);
check('warning' === $gate['status'], 'A score equal to the minimum passes');
reset_case('<h2>Hours</h2><p><a href="/fees">Click here</a></p>', ['min_score'=>$score + 1]); $gate = Gate::execute(['post_id'=>1]);
check('fail' === $gate['status'] && 'error' === $gate['issues'][0]['severity'] && str_contains($gate['issues'][0]['message'], 'below the required minimum of ' . ($score + 1)), 'A score below the minimum blocks as a hard error even with no error findings');
check(Audit::execute(['post_id'=>1])['status'] === 'fail' && $score + 1 === Audit::execute(['post_id'=>1])['min_score'], 'The on-demand report agrees with the gate');
reset_case($clean, ['min_score'=>100]);
check('pass' === Gate::execute(['post_id'=>1])['status'], 'A clean draft meets a minimum of 100');
reset_case($clean, ['min_score'=>'abc']);
check('pass' === Gate::execute(['post_id'=>1])['status'] && 0 === Audit::execute(['post_id'=>1])['min_score'], 'An invalid minimum means no minimum');

reset_case('<img src="a.jpg"><button></button>'); $gate = Gate::execute(['post_id'=>1]);
check(count($gate['issues']) >= 3 && 'wcag_22_aa' === end($gate['issues'])['check_key'] && !str_contains($gate['issues'][0]['message'], $gate['issues'][1]['message']), 'Each finding is its own issue, followed by a summary');

reset_case('<img src="a.jpg">'); $GLOBALS['wcag_post']->post_content = $clean;
check('pass' === Gate::execute(['post_id'=>1])['status'], 'Corrected content is reevaluated, not cached');

reset_case($clean, ['audit_mode'=>'public']);
check('fail' === Gate::execute(['post_id'=>1])['status'], 'Public mode on an unpublished draft fails closed');
reset_case($clean, ['audit_mode'=>'public']); $GLOBALS['wcag_post']->post_status = 'publish'; $GLOBALS['page_response'] = new WP_Error('http', 'down');
check('fail' === Gate::execute(['post_id'=>1])['status'], 'An unreachable public page fails closed');

reset_case($clean); $GLOBALS['allowed'] = false;
check(Gate::execute(['post_id'=>1]) instanceof WP_Error, 'Gate preserves edit permission');
reset_case($clean); $GLOBALS['wcag_post']->post_type = 'attachment';
check(Gate::execute(['post_id'=>1]) instanceof WP_Error, 'Unsupported content fails closed');
reset_case($clean); $GLOBALS['wcag_post']->post_type = 'event';
check('pass' === Gate::execute(['post_id'=>1])['status'], 'Viewable custom post types are supported');
reset_case('<img src="a.jpg">');
check('fail' === Gate::execute(['post_id'=>1, 'status'=>'pass', 'audit_mode'=>'public'])['status'], 'Caller input cannot override the verdict or stored settings');
check(Gate::execute(['post_id'=>'abc']) instanceof WP_Error, 'Invalid post IDs are rejected');

echo 'PASS: ' . (int) $GLOBALS['checks'] . " gate checks\n";
