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
Built with Grunt: sources contain build-time placeholders
(`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) replaced at build time by
`grunt-replace`; version lives only in `package.json`.

## Docs map — read only what the task needs

| Task | Read first |
| --- | --- |
| Plugin structure, loading flow, conventions | `../wordpress-plugin-stub/.opencode/docs/project.md` |
| Grunt build, placeholders, ZIPs, translations (.pot/.po/.mo) | `Gruntfile.js` (this repo has no local build doc yet) |
| Core class, integrations, options | `includes/iworks/class-iworks-orphan.php` header comments + `readme.txt` FAQ |

- This repo has no local `.opencode/docs` yet — stub docs apply only where
  concepts are shared (iworks_options framework, class style).
- When a change alters documented behavior, update the corresponding doc.

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
  `PLUGIN_TILL_YEAR`, `BUILDTIMESTAMP`) are replaced by Grunt during build;
  never hardcode real values in sources.
- Submodules (`includes/iworks/options`, `includes/iworks/rate`) are shared
  iWorks modules — never edit them in this repo; upstream changes go to
  their own repositories.
- Do not fix known typos/pitfalls without approval (see below).

## Known pitfalls (do not "fix" without approval)

- Legacy filter `iworks_orphan_therms` (typo) must stay — backward compatibility.
- `mnf_builder_herlper()` typo in `class-iworks-orphan.php` — private, harmless.
- `grunt release` references a `build:wporg` subtask that is not registered;
  use `grunt build` + `grunt build:github` instead.
- Protected tags order in `class-iworks-orphan.php` (`$protected_tags`) is
  important: `script`/`style` must come after `iframe`/`svg` or nested tags
  will break.
- Replacement runs at display time only — never write replacements back to
  the database.

## Commands

`grunt` (default: clean temp) · `grunt js` (concat + uglify) ·
`grunt css` (sass + cssmin) · `grunt i18n` (checktextdomain + makepot) ·
`grunt build` (wp.org ZIP) · `grunt build:github` (GitHub ZIP + potomo) ·
`grunt test` (phpunit + jshint). Requires PHP ≥ 8.0, WordPress ≥ 6.0.
Planned: conversion from Grunt to the bun bundler — out of scope for now.
