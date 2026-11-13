<?php
/** Each rule passes clean markup, fails the defect it targets and is not applicable when there is nothing to check. */
declare(strict_types=1);
require __DIR__ . '/stubs.php';
use WorkflowToolWcagAa22Audit\WCAG_Color as Color;

$cases = [
 // [rule, passing markup, failing markup, expected failure severity]
 ['img-alt', '<img src="a.jpg" alt="Ranger at the trailhead"><img src="b.jpg" alt="">', '<img src="IMG_1234.jpg">', 'error'],
 ['img-alt-quality', '<img src="a.jpg" alt="Ranger at the trailhead">', '<img src="a.jpg" alt="IMG_1234.jpg">', 'warning'],
 ['non-text-name', '<svg role="img"><title>Map</title></svg><input type="image" src="go.png" alt="Search">', '<input type="image" src="go.png"><span role="img"></span>', 'error'],
 ['video-captions', '<video controls src="v.mp4"><track kind="captions" src="v.vtt"></video>', '<video controls src="v.mp4"></video>', 'error'],
 ['autoplay-audio', '<video autoplay muted src="v.mp4"></video>', '<audio autoplay src="a.mp3"></audio>', 'error'],
 ['empty-heading', '<h2>Hours</h2>', '<h2> </h2>', 'error'],
 ['heading-order', '<h2>Hours</h2><h3>Winter</h3><h2>Fees</h2>', '<h2>Hours</h2><h4>Winter</h4>', 'warning'],
 ['bold-as-heading', '<p><strong>Note:</strong> bring water on every hike.</p><p>More</p>', '<p><strong>Winter hours</strong></p><p>Open daily.</p>', 'warning'],
 ['list-structure', '<ul><li>One</li><li>Two</li></ul>', '<ul><p>One</p></ul>', 'error'],
 ['table-headers', '<table><tr><th>Day</th><th>Hours</th></tr><tr><td>Mon</td><td>9-5</td></tr></table>', '<table><tr><td>Day</td><td>Hours</td></tr><tr><td>Mon</td><td>9-5</td></tr></table>', 'error'],
 ['form-label', '<label for="e">Email</label><input id="e" type="email" autocomplete="email"><label>Name <input name="q" autocomplete="name"></label>', '<input type="text" name="q">', 'error'],
 ['placeholder-label', '<label for="s">Search</label><input id="s" placeholder="Search">', '<input id="s" placeholder="Search">', 'warning'],
 ['autocomplete-valid', '<label>Email <input type="email" autocomplete="billing email"></label>', '<label>Email <input type="email" autocomplete="mail"></label>', 'error'],
 ['autocomplete-purpose', '<label>Email <input type="email" autocomplete="email"></label>', '<label>Email <input type="email" name="email"></label>', 'warning'],
 ['color-contrast', '<p style="color:#595959">Readable grey</p><p class="has-contrast-color has-base-background-color has-text-color has-background">Preset</p>', '<p class="has-muted-color has-text-color">Faint grey</p>', 'error'],
 ['keyboard-handlers', '<button onclick="go()">Go</button>', '<div onclick="go()">Go</div>', 'error'],
 ['pause-stop-hide', '<p>Static</p>', '<marquee>Sale</marquee>', 'error'],
 ['tabindex-positive', '<a href="/x" tabindex="0">X</a>', '<a href="/x" tabindex="3">X</a>', 'warning'],
 ['focus-visible', '<a href="/x">X</a>', '<a href="/x" style="outline: none">X</a>', 'warning'],
 ['link-name', '<a href="/x"><img src="i.png" alt="Home"></a>', '<a href="/x"><img src="i.png" alt=""></a>', 'error'],
 ['link-purpose', '<a href="/fees">Park entrance fees</a>', '<a href="/fees">Click here</a>', 'warning'],
 ['link-as-button', '<a href="/fees">Fees</a>', '<a href="javascript:void(0)">Open</a>', 'warning'],
 ['label-in-name', '<a href="/fees" aria-label="Fees for 2026">Fees</a>', '<a href="/fees" aria-label="Pricing">Fees</a>', 'error'],
 ['target-size', '<a href="/x"><img src="i.png" alt="Home" width="32" height="32"></a>', '<a href="/x"><img src="i.png" alt="Home" width="16" height="16"></a>', 'warning'],
 ['lang-valid', '<p lang="es">Hola</p>', '<p lang="spanish!">Hola</p>', 'error'],
 ['accessible-authentication', '<label>Password <input type="password" autocomplete="current-password"></label>', '<label>Password <input type="password" autocomplete="off"></label>', 'error'],
 ['button-name', '<button>Search</button><button aria-label="Close"><svg></svg></button>', '<button class="icon-close"><svg></svg></button>', 'error'],
 ['frame-title', '<iframe src="https://maps.example/x" title="Map"></iframe>', '<iframe src="https://maps.example/x"></iframe>', 'error'],
 ['aria-role', '<div role="note">Tip</div>', '<div role="tip">Tip</div>', 'error'],
 ['aria-references', '<p id="d">Help</p><input aria-describedby="d" aria-label="Code">', '<input aria-describedby="missing" aria-label="Code">', 'error'],
 ['duplicate-referenced-id', '<label for="a">A</label><input id="a">', '<label for="a">A</label><input id="a"><input id="a">', 'error'],
 ['aria-hidden-focus', '<div aria-hidden="true"><a href="/x" tabindex="-1">X</a></div>', '<div aria-hidden="true"><a href="/x">X</a></div>', 'error'],
];
foreach ($cases as [$slug, $pass, $fail, $severity]) {
 $row = rule($pass, $slug);
 check('pass' === $row['status'], "$slug passes clean markup (got {$row['status']}: {$row['message']})");
 $row = rule($fail, $slug);
 check('fail' === $row['status'] && $severity === $row['severity'], "$slug fails as $severity (got {$row['status']}/{$row['severity']})");
 check(str_contains($row['message'], 'Found '), "$slug failure lists examples");
}
foreach (['img-alt','video-captions','table-headers','form-label','link-name','button-name','frame-title'] as $slug) {
 check('not-applicable' === rule('<p>Plain text only.</p>', $slug)['status'], "$slug is not applicable without its elements");
}

// Review items never pass or fail on their own.
check('review' === rule('<iframe src="https://www.youtube.com/embed/x" title="Tour"></iframe>', 'media-alternatives')['status'], 'Video embeds are flagged for caption review');
check('review' === rule('<p>Click the green button on the right to continue.</p>', 'sensory-language')['status'], 'Sensory-only instructions are flagged for review');
check('review' === rule('<div draggable="true">Card</div>', 'dragging')['status'], 'Dragging (2.5.7) is flagged for review');
check('review' === rule('<div class="g-recaptcha"></div>', 'captcha')['status'], 'CAPTCHA (3.3.8) is flagged for review');

// Contrast specifics.
check(abs(Color::ratio(Color::parse('#777777'), Color::parse('#ffffff')) - 4.48) < 0.01, 'Contrast ratio matches the WCAG formula (#777 on white = 4.48:1)');
check(abs(Color::ratio(Color::parse('rgb(0 0 0)'), Color::parse('hsl(0, 0%, 100%)')) - 21) < 0.001, 'rgb() and hsl() parse');
check('pass' === rule('<h2 style="color:#949494">Large heading</h2>', 'color-contrast')['status'], 'Large text needs only 3:1');
check('fail' === rule('<p style="color:#949494">Small text</p>', 'color-contrast')['status'], 'Normal text needs 4.5:1');
check('not-applicable' === rule('<p style="background-image:url(x.jpg);color:#fff">Over a photo</p>', 'color-contrast')['status'], 'Text over images is not measured');
check(str_starts_with(rule('<p style="color:#595959">Text <a href="/x">link</a></p>', 'color-contrast')['message'], '1 text element'), 'Links are not judged by an inherited paragraph color');
$row = rule('<div style="background-color:#000"><p>Inherited theme text</p></div>', 'color-contrast');
check('fail' === $row['status'] && 'error' === $row['severity'] && str_contains($row['message'], '#111111 on #000000'), 'Theme text color from global styles is used against a block background');
$GLOBALS['styles'] = [];
$row = rule('<div style="background-color:#000"><p>Unknown theme text</p></div>', 'color-contrast');
check('warning' === $row['severity'] && str_contains($row['message'], 'assumed'), 'An assumed theme color downgrades the finding to a warning');
$GLOBALS['styles'] = ['text'=>'var(--wp--preset--color--contrast)','background'=>'var(--wp--preset--color--base)'];
check('warning' === rule('<p class="has-huge-font-size" style="color:#949494">Fluid size</p>', 'color-contrast')['severity'], 'Unknown fluid font size between 3:1 and 4.5:1 is a warning');

// Saved-content specifics.
check(str_contains(rule('<h1>Hours</h1>', 'heading-order')['message'], 'post title is already the H1'), 'An H1 in saved content is flagged');
check('not-applicable' === rule('<p>x</p>', 'html-lang')['status'], 'Page-level rules are not applicable to saved content');
check('not-applicable' === rule('<script>document.write("<img src=x>")</script><p hidden><img src="y.jpg"></p>', 'img-alt')['status'], 'Script and hidden content are ignored');

// Public-page rules.
$good = '<!doctype html><html lang="en-US"><head><title>Hours - Park</title><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><a href="#main">Skip to content</a><main id="main"><h1>Hours</h1></main></body></html>';
foreach (['html-lang','meta-viewport','meta-refresh','bypass-blocks','page-title'] as $slug) {
 check('pass' === rule($good, $slug, true)['status'], "$slug passes a well-formed page");
}
$bad = '<html><head><title></title><meta name="viewport" content="width=device-width, maximum-scale=1, user-scalable=no"><meta http-equiv="refresh" content="30"></head><body><div><h1>Hours</h1></div></body></html>';
foreach (['html-lang','meta-viewport','meta-refresh','bypass-blocks','page-title'] as $slug) {
 check('fail' === rule($bad, $slug, true)['status'], "$slug fails a defective page");
}
check('https://example.test/hours/' === $GLOBALS['requests'][0][0] && 0 === $GLOBALS['requests'][0][1]['redirection'], 'Public mode fetches only the permalink, without following redirects');

echo 'PASS: ' . (int) $GLOBALS['checks'] . " rule checks\n";
