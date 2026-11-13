# Workflow WCAG 2.2 AA Audit

Automated accessibility checks for editors, mapped to the Level A and AA success criteria of [WCAG 2.2](https://www.w3.org/TR/WCAG22/). It reports what failed with examples and fixes, flags what a person must review, and can gate a VIP Workflows transition on the result.

**This is not a conformance claim.** Automated checks catch a minority of accessibility problems. Every report lists the success criteria it cannot test, and even criteria with checks need human judgement: the tool can find a missing alt attribute but not whether existing alt text is accurate.

## Install and use

1. VIP Workflows must be active (on this site it is a VIP platform integration). The plugin needs WordPress 7.0+, PHP 8.2+ and the PHP DOM extension. No API key or AI service is involved.
2. Activate **Workflow WCAG 2.2 AA Audit**.
3. In the Workflows Tools screen, enable **WCAG 2.2 AA audit** and its command-palette visibility, then pick an **Audit mode**, **Fail on** and **Minimum WCAG 2.2 AA readiness score** setting.
4. Save a post, open the command palette (⌘K or Ctrl+K) and choose **WCAG 2.2 AA audit**. Fix what failed, save and rerun. Unsaved edits are not inspected.

## Settings

| Setting | Values | Effect |
| --- | --- | --- |
| Audit mode | `saved-content` (default) | Checks the saved post, including drafts. No HTTP request. Use this for publishing gates. |
| | `public` | Fetches the published permalink as an anonymous visitor and checks the whole page, including theme markup. |
| Render dynamic blocks | off (default) | Saved-content mode only. Synced patterns, patterns and template parts are checked from their saved markup; other dynamic blocks are named as not checked. |
| | on | The post is rendered with `do_blocks()` and `do_shortcode()` first, so Query Loops, Navigation and plugin blocks are checked too. |
| Include theme template | off (default) | Saved-content mode only. |
| | on | Block themes: the post is checked inside the template WordPress would use for it, with the site header, footer and other template parts. |
| Minimum score | `0` (default, off) | Adjustable 0 to 100. The audit and the gate fail when the readiness score is below it, even with no error findings. Equal passes. |
| Fail on | `errors` (default) | Definite failures fail the audit and block the gate. Likely problems are warnings. |
| | `errors-and-warnings` | Warnings block too. |

## What it checks

| Criterion | Checks | Severity |
| --- | --- | --- |
| 1.1.1 Non-text Content | Images have alt; alt is not a file name or "image"; image buttons, image maps, objects and `role="img"` have names | error / warning |
| 1.2.1–1.2.5 Media | Self-hosted video has a captions track; YouTube, Vimeo, podcast and audio embeds flagged for caption, transcript and audio-description review | error / review |
| 1.3.1 Info and Relationships | Empty headings, skipped heading levels (an H1 in the body counts), bold paragraphs used as headings, broken lists, data tables without header cells, missing or duplicate referenced ids | error / warning |
| 1.3.3 Sensory Characteristics | Instructions such as "click the green button on the right" | review |
| 1.3.5 Identify Input Purpose | Invalid `autocomplete` tokens; personal-information fields without one | error / warning |
| 1.4.2 Audio Control | Media that autoplays unmuted | error |
| 1.4.3 Contrast (Minimum) | Text with colors set in the editor (inline styles and theme palette classes) meets 4.5:1, or 3:1 for large text | error / warning |
| 1.4.4 Resize Text | Viewport disables zoom (public) | error |
| 2.1.1 Keyboard | Click handlers on elements that cannot be focused; `javascript:` links | error / warning |
| 2.2.1 Timing Adjustable | Timed meta refresh (public) | error |
| 2.2.2 Pause, Stop, Hide | `marquee`, `blink`, looping autoplay video without controls | error |
| 2.4.1 Bypass Blocks | `<main>` landmark or skip link (public) | error |
| 2.4.2 Page Titled | Post title, or the rendered `<title>` (public) | error |
| 2.4.3 Focus Order | Positive `tabindex` | warning |
| 2.4.4 Link Purpose | Links without names; generic text such as "click here" or "read more" | error / warning |
| 2.4.7 Focus Visible | Inline `outline: none` on focusable elements | warning |
| 2.5.3 Label in Name | `aria-label` that does not contain the visible text | error |
| 2.5.7 Dragging Movements (new in 2.2) | Draggable elements | review |
| 2.5.8 Target Size (Minimum) (new in 2.2) | Buttons and icon-only links sized under 24×24px in the content | warning |
| 3.1.1 / 3.1.2 Language | Page `lang` (public); valid language codes on passages | error |
| 3.2.2 On Input | Change handlers on fields and selects | review |
| 3.3.2 Labels or Instructions | Fields without labels; placeholder-only labels | error / warning |
| 3.3.8 Accessible Authentication (new in 2.2) | Password fields that block password managers or paste; CAPTCHAs | error / review |
| 4.1.2 Name, Role, Value | Buttons and iframes without names, invalid ARIA roles, focusable content inside `aria-hidden` | error |

4.1.1 Parsing is not checked: WCAG 2.2 removed it.

**Errors** are definite failures. **Warnings** are likely failures that need a person to confirm, such as generic link text, which can pass when the surrounding sentence gives context. **Review** items never pass or fail. Each report also lists the criteria this tool cannot test, including the new 2.4.11 Focus Not Obscured, 3.2.6 Consistent Help and 3.3.7 Redundant Entry.

The score is the share of applicable automated checks that passed. It is for tracking progress; the pass/warning/fail status decides the gate, and the **Minimum score** setting adds a floor: a score below it fails the audit and blocks the gate.

### Color contrast

Only text whose color or background is set in the content is measured: inline styles, Gutenberg palette classes (resolved through the theme's `theme.json` palette) and `var(--wp--preset--color--*)` values. The theme's default text and background colors come from Global Styles. If Global Styles has no colors, the check assumes black text on white and reports any resulting failure as a warning. Text over gradients or images, and colors from theme stylesheets, are not measured. Fluid (`clamp()`) font sizes are treated as normal-sized text, so findings between 3:1 and 4.5:1 for them are warnings.

## What saved-content mode includes

Saved-content mode reads the post as stored, so dynamic blocks are placeholders such as `<!-- wp:block {"ref":123} /-->` until something renders them.

**Expanded without rendering (always):**
- **Synced patterns** (`core/block`): read from the `wp_block` post they point to.
- **Registered patterns** (`core/pattern`): read from the theme's pattern registry.
- **Template parts** (`core/template-part`), such as a header or footer placed in the post: read from the theme or the Site Editor. Each is wrapped in its `tagName` (for example `<header>`), so it keeps its landmark.

These are inlined recursively, up to 8 levels, and a pattern that includes itself is stopped. Each report lists what was included. A trashed or missing reference is named as not found and isn't checked.

**Everything else** (Query Loop, Navigation, Latest Posts, plugin blocks, shortcodes) runs PHP when the page is viewed. The report names each one with a count, for example "Query Loop (core/query), Latest Posts (core/latest-posts) ×2". Blocks inside a dynamic block are its output, so they aren't listed again.

**With Render dynamic blocks on**, the post is rendered the way the front end renders it, minus the theme template. All of the above is included without any expansion step. This runs block and shortcode code on every audit and gate check, so it is slower and depends on those blocks behaving outside a normal page view. Any output a block prints directly is discarded. If rendering throws an error, the audit returns an error and the gate blocks the transition rather than passing.

### Include theme template

By default only the post itself is checked, not the site header, footer and navigation the theme template wraps around it. With **Include theme template** on (block themes only), saved-content mode:

1. Finds the template WordPress would use for this post today: the custom template assigned to it, then `single-{type}-{slug}`, `single-{type}` and `single` for posts (`page-{slug}`, `page-{id}` and `page` for pages), then `singular` and `index`. Templates edited in the Site Editor are used over the theme's files.
2. Puts the post where the template's Post Content block is. If the template has none, the post is added after it.
3. Checks the result, expanding the template's header, footer and other template parts like any others.

The report names the template used. With an H1 in the template (usually Post Title), headings in the post are measured from it rather than from an assumed title.

Template blocks such as Navigation, Post Title and Site Logo are dynamic. They're listed as not checked unless **Render dynamic blocks** is also on, in which case the template is rendered the way the front end renders it. The page `<head>` (title, language, viewport) is still only checked in public mode. With a classic theme, or when no template matches, the report says the template couldn't be included and checks the post alone.

## Use as a publishing gate

The plugin registers three abilities: `workflow-tool-wcag-aa-22-audit/wcag-audit` (the on-demand report), `workflow-tool-wcag-aa-22-audit/wcag-gate` (transition eligible) and `workflow-tool-wcag-aa-22-audit/wcag-settings` (administrator-only settings over Secure MCP or REST).

1. Configure **WCAG 2.2 AA audit** with `saved-content` mode. The gate reads the same settings, so there is one policy.
2. Enable **WCAG 2.2 AA publishing gate** and add it to **Required tools** on the transitions you want gated.
3. The gate reruns the audit on the saved post every time and ignores anything passed to it.

The Transition blocked dialog shows plain text, and long findings wrap inside each box. A blocking result returns one `error` issue per finding, plus a final summary `error`, which **Acknowledge warnings** cannot override. With `Fail on: errors`, warnings alone return one `warning` issue per finding that editors can acknowledge. If the gate cannot run (permission, unsupported post type, unreachable public page), the transition is blocked.

By default VIP Workflows lets administrators bypass required tools (`bypass_tool_check_roles`), so the gate applies to editors, authors and other non-bypass roles.

## Limitations

- Without rendering, synced patterns are checked as saved, so pattern overrides set on a particular instance aren't applied.
- No JavaScript runs and no stylesheets are loaded. Anything injected or styled client-side or by CSS files is not seen. Keyboard traps, focus order in practice, reflow, hover content and screen-reader output need manual testing with a keyboard and a screen reader.
- Public mode uses WordPress safe HTTP, which refuses local and private-network hosts. Use saved-content mode on local or private staging sites.
- The heuristics (generic link text, file-name alt text, sensory instructions) are tuned for English.
- The report styling targets VIP Workflows modal class names, copied from the AEO audit. Recheck it after Workflows updates; the report still works unstyled.

## Verification

From this directory, with PHP 8.2+ and DOM:

```sh
php tests/rules.php
php tests/content.php
php tests/gate.php
php tests/bootstrap.php
php tests/bootstrap.php --without-core
```

These use WordPress stubs and need no site. Not yet tested on a WordPress site with VIP Workflows.

## Provenance and support

Built in October 2026 on the same pattern as Workflow AEO Audit. Unsupported example code, not part of the VIP Workflows product or any support agreement. GPL-2.0-or-later.
