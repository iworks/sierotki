/**
 * Asset pipelines — CSS (sass → concat → clean-css) and JS (concat → terser).
 * Output paths and file names mirror the old Grunt pipeline exactly
 * (the PHP code enqueues these files — never change them).
 */

import { dirname, join, resolve } from "node:path";
import { existsSync, readdirSync } from "node:fs";
import { log } from "./consoleHelpers.mjs";
import { config, bundleName } from "./config.mjs";
import {
  compileSCSSEntries,
  concatFiles,
  minifyCSS,
  minifyJS,
  writeFileAtomic,
  prettierSources,
  cleanGlob,
} from "./tools.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Banner prepended to concatenated bundles (grunt `concat` template).
 *
 * @return {string}
 */
function concatBanner() {
  return [
    `/*! ${config.title} - ${config.version}`,
    ` * ${config.website}`,
    ` * Copyright (c) ${config.buildYear}`,
    ` * Licensed ${config.license} */`,
    "",
  ].join("\n");
}

/**
 * Banner prepended to minified bundles (grunt `uglify` template — note the `;`).
 *
 * @return {string}
 */
function minBanner() {
  return [
    `/*! ${config.title} - ${config.version}`,
    ` * ${config.website}`,
    ` * Copyright (c) ${config.buildYear};`,
    ` * Licensed ${config.license} */`,
    "",
  ].join("\n");
}

/**
 * Strip sourceMappingURL comments (build mode — no maps are written).
 *
 * @param {string} css CSS content.
 * @return {string}
 */
function stripMapComments(css) {
  return css.replace(/\/\*# sourceMappingURL=[^*]*\*\/\n?/g, "");
}

/**
 * List files in a directory (no recursion), sorted, filtered by extension.
 *
 * @param {string}   dir Absolute directory.
 * @param {string[]} extensions Allowed extensions (with dot).
 * @return {string[]} Absolute file paths.
 */
function listFiles(dir, extensions) {
  if (!existsSync(dir)) {
    return [];
  }
  return readdirSync(dir, { withFileTypes: true })
    .filter((e) => e.isFile() && extensions.includes(ext(e.name)))
    .map((e) => join(dir, e.name))
    .sort();
}

function ext(name) {
  const idx = name.lastIndexOf(".");
  return idx === -1 ? "" : name.slice(idx);
}

/**
 * CSS pipeline: compile SCSS entries → concat per-variant → minify.
 *
 * @param {object} options
 * @param {boolean} options.maps   Write source maps (dev mode).
 * @param {boolean} options.strip  Strip sourceMappingURL comments.
 */
export async function buildCSS({ maps = false, strip = false } = {}) {
  const compiled = await compileSCSSEntries(maps);

  // Admin: concat everything under assets/styles/admin/.
  const adminCssDir = join(root, config.stylesDir, "admin");
  const adminFiles = listFiles(adminCssDir, [".css"]);
  let adminCss = adminFiles.length ? await concatFiles(adminFiles) : "";
  if (strip) {
    adminCss = stripMapComments(adminCss);
  }
  writeFileAtomic(
    join(root, config.stylesDir, `${bundleName("admin")}.css`),
    adminCss,
  );

  // Frontend: settings.css first, then the rest of assets/styles/frontend/.
  const frontendDir = join(root, config.stylesDir, "frontend");
  let frontendCss = "";
  if (existsSync(frontendDir)) {
    const all = listFiles(frontendDir, [".css"]);
    const settings = all.filter((f) => f.endsWith("settings.css"));
    const rest = all.filter((f) => !f.endsWith("settings.css"));
    frontendCss = await concatFiles([...settings, ...rest]);
  } else {
    log(
      "css — assets/styles/frontend/ not found, frontend bundle is empty",
      "w",
    );
  }
  if (strip) {
    frontendCss = stripMapComments(frontendCss);
  }
  writeFileAtomic(
    join(root, config.stylesDir, `${bundleName("frontend")}.css`),
    frontendCss,
  );

  // Minify the top-level bundles (grunt cssmin: no banner in minified CSS).
  for (const variant of ["admin", "frontend"]) {
    const cssFile = join(root, config.stylesDir, `${bundleName(variant)}.css`);
    const min = minifyCSS(await Bun.file(cssFile).text());
    writeFileAtomic(cssFile.replace(/\.css$/, ".min.css"), min);
  }
  log(
    `css — ${compiled.length} SCSS entries → admin${existsSync(frontendDir) ? " + frontend" : " (frontend empty)"} bundles minified`,
    "s",
  );
}

/**
 * JS pipeline: concat sources per-variant → terser minify.
 */
export async function buildJS() {
  for (const variant of ["admin", "frontend"]) {
    const srcDir = join(root, config.jsSrcDir, variant);
    const files = listFiles(srcDir, [".js"]);
    if (!files.length) {
      log(
        `js — assets/scripts/src/${variant}/ has no sources, ${variant} bundle is banner-only`,
        "w",
      );
    }
    const concat = await concatFiles(files, concatBanner());
    const outFile = join(root, config.scriptsDir, `${bundleName(variant)}.js`);
    writeFileAtomic(outFile, concat);
    const min = await minifyJS(concat, minBanner());
    writeFileAtomic(outFile.replace(/\.js$/, ".min.js"), min);
  }
  log("js — admin + frontend bundles concatenated and minified", "s");
}

/**
 * Prettier pass over the JS/JSON sources (build pipelines run it first).
 */
export async function formatSources() {
  await prettierSources([
    "assets/scripts/src/**/*.js",
    "bundler/**/*.js",
    "bundler/**/*.mjs",
    "*.js",
  ]);
  log("prettier — sources formatted", "s");
}

/**
 * Remove built files (grunt `clean:compiled` semantics).
 */
export async function cleanCompiled() {
  await cleanGlob("assets/scripts/*.js");
  await cleanGlob("assets/styles/*.css");
  await cleanGlob("assets/styles/**/*.css");
  await cleanGlob("assets/styles/**/*.map");
  await cleanGlob("languages/*.mo");
  log("clean — built JS/CSS and languages/*.mo removed", "i");
}
