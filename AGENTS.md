# AGENTS.md — Orphans (sierotki)

> Working rules only. Technical knowledge lives in topic docs — read on demand, never preload.

## What the project is

The "Orphans" (sierotki) WordPress plugin — a typography plugin that replaces
the space after short words ("orphans") with a non-breaking space at display
time; it never modifies stored content. Structure: main file `sierotki.php`
+ core class (`includes/iworks/class-iworks-orphan.php`) + options config
(`etc/options.php`, iworks_options format) + term lists (`etc/terms-*.txt`)
+ builder integrations (`includes/iworks/integrations/`) + vendored iWorks
modules (`includes/iworks/options`, `includes/iworks/rate` — git submodules).
Built with a bun bundler (`bundler/` → `release/` ZIPs, both wporg and
github flavors): sources contain build-time placeholders
(`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) replaced at build time; the
version lives only in `package.json`.

## Docs map — read only what the task needs

| Task | Read first |
| --- | --- |
| Bun bundler, build, ZIPs, placeholders, translations (.pot/.po/.mo) | `.opencode/docs/build.md` |
| Loading flow, replacement engine, options, integrations, conventions | `.opencode/docs/project.md` |
| Core class, integrations, options internals | `includes/iworks/class-iworks-orphan.php` header comments + `readme.txt` FAQ |

- When a change alters documented behavior, update the corresponding doc in
  the same task.

## Evergreen rules (every task)

- All documentation and README in English.
- `readme.txt` changelog is edited only when the user explicitly asks
  (desired format when asked: `= X.Y.Z - YYYY-MM-DD =` bullets, English,
  concise).
- Never commit, push, amend or create PRs without an explicit request.
- Naming: kebab-case files (`class-iworks-orphans-{name}.php`); PHP classes
  `iworks_orphans_` prefix (`iWorks_Orphans_` style for integration classes),
  snake_case, lowercase.
- Version lives only in `package.json` — placeholders (`PLUGIN_VERSION`,
  `PLUGIN_TILL_YEAR`, `BUILDTIMESTAMP`) are replaced by the bundler during
  build; never hardcode real values in sources.
- Submodules (`includes/iworks/options`, `includes/iworks/rate`) are shared
  iWorks modules — never edit them in this repo; upstream changes go to
  their own repositories.
- Do not fix known typos/pitfalls without approval (see below).

## Known pitfalls (do not "fix" without approval)

- Legacy filter `iworks_orphan_therms` (typo) must stay — backward compatibility.
- `mnf_builder_herlper()` typo in `class-iworks-orphan.php` — private, harmless.
- Protected tags order in `class-iworks-orphan.php` (`$protected_tags`) is
  important: `script`/`style` must come after `iframe`/`svg` or nested tags
  will break.
- Replacement runs at display time only — never write replacements back to
  the database.
- The build's prettier step rewrites JS sources in place (formatting-only
  diffs after a build are expected); the i18n step updates tracked
  `languages/*.po` files (timestamp diffs are normal).

## Commands

`bun run dev` (watcher) ·
`bun run build` (both ZIPs: github + wporg, then Plugin Check on the wporg
release) · `bun run build:wporg` (wp.org ZIP) · `bun run build:github`
(GitHub ZIP + .mo) · `bun run make-pot`
(translations) · `bun run update-libs` (git pull in iWorks submodules) ·
`bun run test` (phpunit; no tests/ dir in this repo —
use `php -l` for PHP changes). Requires PHP ≥ 8.0, WordPress ≥ 6.0.
