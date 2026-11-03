#!/usr/bin/env bun
/**
 * Bundler entry point — replaces the old Grunt pipeline.
 *
 * Modes:
 *   dev (default) — watcher, compile on change (no ZIP, no i18n)
 *   compile       — one-shot asset compile (old `grunt` default task)
 *   build         — both flavors: build:wporg + build:github
 *   build:wporg   — production build → release/wporg/{name}.zip
 *   build:github  — production build + .mo → release/github/{name}.zip
 *   make-pot      — translations only (.pot + .po update)
 *   test          — PHPUnit (tests/php/phpunit.xml)
 */

import { dirname, join, resolve } from "node:path";
import { watch } from "node:fs";
import { executionTime, log } from "./consoleHelpers.mjs";
import { ProgressBar } from "./progressBar.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";
import {
  buildCSS,
  buildJS,
  formatSources,
  cleanCompiled,
} from "./pipelines.mjs";
import { prettierFile } from "./tools.mjs";
import { makeZip } from "./makeZip.mjs";
import { makePot } from "./makePot.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Friendly error printer — one-liners, no stack traces.
 *
 * @param {*} error Thrown error.
 */
function printError(error) {
  const message = error?.friendly
    ? error.message
    : error?.stack || String(error);
  log(message, "e");
}

/**
 * One-shot asset compile (CSS + JS).
 *
 * @param {object} options
 * @param {boolean} options.maps Write source maps.
 * @param {boolean} options.strip Strip sourceMappingURL comments.
 * @param {boolean} options.format Run the prettier pass first.
 */
async function compileAssets({
  maps = false,
  strip = true,
  format = false,
} = {}) {
  if (format) {
    await formatSources();
  }
  await buildCSS({ maps, strip });
  await buildJS();
}

/**
 * Production build for one flavor.
 *
 * @param {string} flavor "wporg" or "github".
 */
async function build(flavor) {
  const start = Date.now();
  try {
    const steps = 7;
    const bar = new ProgressBar(steps);
    bar.tick("clean");
    await cleanCompiled();
    bar.tick("prettier");
    await formatSources();
    bar.tick("css");
    await buildCSS({ maps: false, strip: true });
    bar.tick("js");
    await buildJS();
    bar.tick("i18n");
    await makePot(flavor === "github");
    bar.tick("zip");
    await makeZip(flavor);
    bar.tick("done");
    log(
      `BUILD OK (${flavor}) in ${((Date.now() - start) / 1000).toFixed(2)}s`,
      "s",
    );
  } catch (error) {
    log("BUILD FAILED", "e");
    printError(error);
    process.exit(1);
  }
}

/**
 * Dev watcher — compile changed assets, no ZIP, no i18n.
 */
function dev() {
  const exitAfterMs = Bun.env.DEV_EXIT_AFTER_MS || "";
  if (exitAfterMs) {
    setTimeout(() => {
      log(`dev — exiting after ${exitAfterMs} ms (DEV_EXIT_AFTER_MS)`, "i");
      process.exit(0);
    }, Number(exitAfterMs));
  }
  log(
    "dev — watching for changes (no auto browser reload, refresh manually)",
    "i",
  );

  let timer = null;
  const queue = new Set();

  const processQueue = async () => {
    timer = null;
    const changed = [...queue];
    queue.clear();
    const scss = changed.some((f) => f.endsWith(".scss"));
    const js = changed.some(
      (f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
    );
    if (!scss && !js) {
      return;
    }
    try {
      if (js) {
        for (const file of changed.filter(
          (f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
        )) {
          await prettierFile(join(root, file));
        }
      }
      await compileAssets({ maps: true, strip: false });
      log(`dev — rebuilt (${changed.length} changed)`, "s");
    } catch (error) {
      printError(error);
    }
  };

  watch(root, { recursive: true }, (event, filename) => {
    if (!filename) {
      return;
    }
    const rel = filename.replace(/\\/g, "/");
    const relevant =
      (rel.startsWith("assets/sass/") && rel.endsWith(".scss")) ||
      (rel.startsWith("assets/scripts/src/") && rel.endsWith(".js"));
    if (!relevant) {
      return;
    }
    queue.add(rel);
    if (!timer) {
      timer = setTimeout(processQueue, 200);
    }
  });
}

/**
 * PHPUnit (grunt `test` parity, jshint dropped).
 */
async function test() {
  const result = await spawnAsync(
    "phpunit",
    ["--configuration", "tests/php/phpunit.xml", "--colors=always"],
    "PHPUnit",
    false,
  );
  if (result.stdout.trim()) {
    console.log(result.stdout.trim());
  }
  if (result.code !== 0) {
    log(
      "If phpunit is not installed, set up a WordPress test environment (see tests/php/bootstrap.php).",
      "w",
    );
    process.exit(result.code);
  }
  log("PHPUnit passed", "s");
}

// ─── Mode dispatch ────────────────────────────────────────────────────────────

const mode = process.argv[2] || "dev";

const modes = {
  dev: () => dev(),
  compile: () =>
    executionTime("compile", () =>
      compileAssets({ maps: true, strip: false, format: false }),
    ),
  build: async () => {
    await build("wporg");
    await build("github");
  },
  "build:wporg": () => build("wporg"),
  "build:github": () => build("github"),
  "make-pot": () => executionTime("make-pot", () => makePot(false)),
  test: () => test(),
};

if (!modes[mode]) {
  log(
    `Unknown mode "${mode}". Available: ${Object.keys(modes).join(", ")}`,
    "e",
  );
  process.exit(1);
}

try {
  await modes[mode]();
} catch (error) {
  printError(error);
  process.exit(1);
}
