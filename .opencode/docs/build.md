# Build — Bun bundler

> Toolchain, modes, pipeline, placeholders, release ZIPs, translations.
> Read only when working on the build.

## Toolchain

- Bun (>= 1.3) — package manager, script runner and the bundler runtime
  (`bundler/index.js` modes). `bun.lock` is committed; `package-lock.json`
  is gitignored. Node is not required.
- PHP ≥ 8.0; `wp-cli.phar` (downloaded by `postinstall`, gitignored) for
  translations. There is **no** `tests/` directory — `bun run test` runs
  PHPUnit but there is nothing to test; use `php -l` on changed files.
- Sass, terser, clean-css and prettier run **in-process** (`bundler/tools.mjs`).
- sierotki currently ships **no** SCSS or JS sources (`assets/` has only
  `images/logo.svg`) — the css/js pipeline stages warn "no sources" and
  **skip writing the bundles entirely** (no empty/banner-only files in the
  ZIPs). This is expected; bundles reappear automatically as soon as real
  sources are added.

## Files (`bundler/`)

| File                | Role                                                                                          |
| ------------------- | --------------------------------------------------------------------------------------------- |
| `index.js`          | Modes: `dev` (default) / `compile` / `build` / `build:wporg` / `build:github` / `make-pot` / `test` |
| `config.mjs`        | Paths, names (from `package.json`), placeholder patterns, release copy rules per flavor        |
| `pipelines.mjs`     | CSS (sass → concat → clean-css) and JS (concat → terser) pipelines, prettier pass, clean       |
| `tools.mjs`         | In-process APIs (prettier, sass, terser, clean-css), atomic writes, glob helpers               |
| `makeZip.mjs`       | Staging copy → placeholder replacement → ZIP (archiver, level 9)                               |
| `makePot.mjs`       | wp-cli i18n: `make-pot` → `update-po` (→ `make-mo` for github)                                 |
| `spawnAsync.mjs`    | `child_process.spawn` wrapper with captured output                                             |
| `consoleHelpers.mjs`, `progressBar.mjs` | Logging, timing, build progress bar                                        |

All names derive from `package.json` (`name`, `title`, `version`, `homepage`,
`repository.website`, `requires`, `tested`, `tags`) at runtime.

## Modes (`bun run ...`)

| Command                | Description                                                                                  |
| ---------------------- | -------------------------------------------------------------------------------------------- |
| `bun run dev`          | Watcher: `.scss` → CSS pipeline, `assets/scripts/src/**` → prettier + JS pipeline (200 ms debounce); no ZIP, no i18n |
| `bun run compile`      | One-shot asset compile with source maps (old `grunt` default task)                            |
| `bun run build`        | Both flavors back-to-back: wporg + github (grunt `release` parity, minus the notes checklist) |
| `bun run build:wporg`  | Production → `release/wporg/{name}.zip`                                                       |
| `bun run build:github` | Production + `.mo` → `release/github/{name}.zip`                                              |
| `bun run make-pot`     | Translations only (`.pot` + `.po` update)                                                     |
| `bun run test`         | PHPUnit (no `tests/` dir in this repo — will fail gracefully)                                 |
| `bun run update-libs`  | `git pull` inside the iWorks submodules (`includes/iworks/{options,rate}`) — manual task      |

## Build pipeline (`bun run build:wporg`, 7 steps)

1. **clean** — remove built JS (`assets/scripts/*.js`), built CSS
   (`assets/styles/*.css`, `*.map`), `languages/*.mo`;
2. **prettier** — format sources in place (`assets/scripts/src/**/*.js`,
   `bundler/**/*.{js,mjs}`, root `*.js`) — the build rewrites sources by design;
3. **css** — sass compiles every non-`_` entry under `assets/sass/` →
   `assets/styles/{path}.css`; concat per-variant (`admin/*.css` →
   `{name}-admin.css`, `frontend/settings.css` first + rest →
   `{name}-frontend.css`); clean-css → `.min.css`. **Bundles are written
   only when sources exist** — with no sources the variant is skipped with
   a warning (no empty/banner-only files, no empty staging dirs);
4. **js** — concat `assets/scripts/src/{admin,frontend}/*.js` with the concat
   banner → `{name}-{variant}.js`; terser (`jQuery` reserved) → `.min.js`.
   **Same rule: variants without sources are skipped entirely.**
5. **i18n** — wp-cli `i18n make-pot` → `languages/{name}.pot`, then
   `i18n update-po` per existing `.po`; `make-mo` only in `build:github`;
6. **zip** — staging copy → placeholder replacement → `release/{flavor}/{version}/{name}/`
   → `release/{flavor}/{name}.zip`;
7. done — progress bar + total time; any failure prints `BUILD FAILED`, exit 1.

Banners (grunt parity): concat = `/*! {title} - {version}\n * {website}\n *
Copyright (c) {year}\n * Licensed {license} */`; minified JS uses the same
with `Copyright (c) {year};`. Minified CSS has no banner.

## Placeholders (release copies only)

`AUTHOR_NAME/URI/URL`, `BUILDTIMESTAMP`, `BUILDTIME`,
`IWORKS_RATE_TEXTDOMAIN`, `IWORKS_OPTIONS_TEXTDOMAIN`, `PLUGIN_DESCRIPTION`,
`PLUGIN_GITHUB_WEBSITE`, `PLUGIN_NAME`, `PLUGIN_REQUIRES_PHP`,
`PLUGIN_REQUIRES_WORDPRESS`, `PLUGIN_TESTED_WORDPRESS`, `PLUGIN_TAGLINE`,
`PLUGIN_TAGS`, `PLUGIN_TILL_YEAR`, `PLUGIN_TITLE`, `PLUGIN_URI`, `PLUGIN_URL`,
`PLUGIN_VERSION`, and the `^Version: .*$` header line — sourced from
`package.json` + build time. sierotki uses `homepage` (the stub uses `uri`)
— `config.mjs` reads `pkg.homepage || pkg.uri`.

The version lives **only** in `package.json`.

## Release ZIPs — both flavors must build

- **WordPress.org** (`bun run build:wporg`) → `release/wporg/{version}/{name}/`.
  Base excludes **plus**: asset sources (`assets/sass`, `assets/scripts/src`,
  `assets/scss`, `assets/styles/frontend`, legacy `**/img/src`, `**/js/src`,
  `**/css/{less,sass,src}`), the GitHub self-updater class
  (`includes/iworks/orphans/class-iworks-orphans-github.php`),
  `languages/*.po|*.mo`. `readme.txt` **is** included (required by wp.org).
- **GitHub** (`bun run build:github`) → `release/github/{version}/{name}/`.
  Base excludes plus `readme.txt`; `.po` **are** included and `.mo` compiled
  by `make-mo`. Pairs with the GitHub self-updater class.
- Base excludes (both flavors): `.git*` (directories, submodule `.git`
  pointer files, `.gitignore`, `.gitmodules`), `.github/**`, `.editorconfig`
  (root and submodules), `.env*`,
  `.opencode/**`, `AGENTS.md`, `bundler/**`, `Gruntfile.js`,
  lock files, `wp-cli.phar`, `node_modules`, `package.json`, `postcss.config.js`,
  `webpack.config.js`, `release/`, `.sass-cache`, `**/tests/**`, `LICENSE`,
  `**/*.map`, `README.md`, `contributing.md`, `**/bitbucket-pipelines.yml`.
- Copy rules are ordered and evaluated **first match wins** (grunt semantics) —
  the catch-all `**/*` include must stay last (`config.mjs` `copyRules()`).

## Tests

- `bun run test` → `phpunit --configuration tests/php/phpunit.xml` — but this
  repo has no `tests/` directory, so it fails gracefully. For PHP changes use
  `php -l` per file; do not claim test coverage.
- Dropped with Grunt: jshint (dead tooling), grunt-wp-i18n `makepot`,
  grunt-potomo (replaced by wp-cli `make-mo`), `checktextdomain`.

## Runtime quirks / pitfalls (do not fix without approval)

- The build's prettier step **rewrites source files** — that is intended.
  A diff after `bun run build` containing only formatting in JS sources is
  expected; anything else is not.
- The i18n step **updates the tracked `.po` files in place** — a build diff
  touching `languages/*.po` (timestamps/headers) is normal.
- `bun run make-pot` needs network on the first run (`wp-cli.phar` download);
  `postinstall` retries it on every `bun install`.
- Copy rules: negations must precede the catch-all `**/*` include — reordering
  silently leaks sources into the ZIPs.
- Submodule contents (`includes/iworks/{options,rate}`) ARE shipped in both
  ZIPs — only their `.git` metadata is pruned by `walk()`.
