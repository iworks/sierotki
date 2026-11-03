/**
 * In-process tool calls — prettier, sass, terser and clean-css APIs.
 * All dist/source writes are atomic (temp + rename); tool failures are
 * rethrown as friendly one-line errors.
 */

import { dirname, join, relative, resolve } from "node:path";
import {
  mkdirSync,
  renameSync,
  writeFileSync,
  existsSync,
  readdirSync,
  rmSync,
} from "node:fs";
import { fileURLToPath } from "node:url";
import * as sass from "sass";
import { minify } from "terser";
import CleanCSS from "clean-css";
import * as prettier from "prettier";
import { log } from "./consoleHelpers.mjs";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");

/**
 * Atomic write: temp file + rename.
 *
 * @param {string} file    Destination path.
 * @param {string} content Text content.
 */
export function writeFileAtomic(file, content) {
  mkdirSync(dirname(file), { recursive: true });
  const tmp = `${file}.tmp`;
  writeFileSync(tmp, content);
  renameSync(tmp, file);
}

/**
 * Friendly error: tool — file\nmessage (no stack trace noise).
 *
 * @param {string} tool Tool name.
 * @param {string} file File being processed.
 * @param {*}      error Thrown error.
 */
function friendly(tool, file, error) {
  const message =
    error && error.formatted
      ? error.formatted
      : error?.message || String(error);
  const err = new Error(
    `${tool} — ${relative(root, file) || file}\n${message}`,
  );
  err.friendly = true;
  throw err;
}

/**
 * Prettier — format a single file in place.
 *
 * @param {string} file File path.
 */
export async function prettierFile(file) {
  try {
    const source = await Bun.file(file).text();
    const formatted = await prettier.format(source, { filepath: file });
    if (formatted !== source) {
      writeFileAtomic(file, formatted);
      log(`prettier — ${relative(root, file)}`, "i");
    }
  } catch (error) {
    friendly("prettier", file, error);
  }
}

/**
 * Prettier — format every JS source file (glob pattern, relative to root).
 *
 * @param {string[]} patterns Glob patterns.
 */
export async function prettierSources(patterns) {
  for (const pattern of patterns) {
    const glob = new Bun.Glob(pattern);
    for await (const file of glob.scan({ cwd: root, onlyFiles: true })) {
      await prettierFile(join(root, file));
    }
  }
}

/**
 * Sass — compile one entry file.
 *
 * @param {string}  file  Entry .scss path (absolute or cwd-relative).
 * @param {boolean} maps  Write .map files.
 * @return {{css: string, mapPath: string|null}} Compiled CSS and written map path.
 */
export function compileSCSS(file, maps = false) {
  const outRel = relative(join(root, "assets/sass"), file).replace(
    /\.scss$/,
    ".css",
  );
  const outPath = join(root, "assets/styles", outRel);
  try {
    const result = sass.compile(
      file,
      maps ? { sourceMap: true, sourceMapIncludeSources: true } : {},
    );
    let css = result.css;
    let mapPath = null;
    if (maps && result.sourceMap) {
      mapPath = `${outPath}.map`;
      writeFileAtomic(mapPath, JSON.stringify(result.sourceMap));
      css += `/*# sourceMappingURL=${outRel}.map */\n`;
    }
    writeFileAtomic(outPath, css);
    return { css, mapPath };
  } catch (error) {
    friendly("sass", file, error);
  }
}

/**
 * Compile all SCSS entry points (non-partials) in parallel.
 *
 * @param {boolean} maps Write source maps (dev mode).
 * @return {string[]} Compiled CSS file paths.
 */
export async function compileSCSSEntries(maps = false) {
  const sassDir = join(root, "assets/sass");
  const entries = [];
  const walk = (dir) => {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
      const full = join(dir, entry.name);
      if (entry.isDirectory()) {
        walk(full);
      } else if (entry.name.endsWith(".scss") && !entry.name.startsWith("_")) {
        entries.push(full);
      }
    }
  };
  if (existsSync(sassDir)) {
    walk(sassDir);
  }
  if (!entries.length) {
    log("sass — no SCSS entry points found in assets/sass", "w");
    return [];
  }
  await Promise.all(entries.map((file) => compileSCSS(file, maps)));
  return entries.map((file) =>
    join(
      root,
      "assets/styles",
      relative(sassDir, file).replace(/\.scss$/, ".css"),
    ),
  );
}

/**
 * Concatenate files with an optional banner and separator.
 *
 * @param {string[]} files  File paths in order.
 * @param {string}   banner Banner prepended to the output.
 * @param {string}   [outFile] When given, write the result atomically.
 * @return {string} Concatenated content.
 */
export async function concatFiles(files, banner = "", outFile = null) {
  const parts = [];
  for (const file of files) {
    parts.push(await Bun.file(file).text());
  }
  const content = banner + parts.join("\n") + (parts.length ? "\n" : "");
  if (outFile) {
    writeFileAtomic(outFile, content);
  }
  return content;
}

/**
 * Terser — minify JS content and prepend the uglify-style banner.
 *
 * @param {string} code   JS source.
 * @param {string} banner Banner prepended to the minified output.
 * @return {string} Minified code.
 */
export async function minifyJS(code, banner = "") {
  try {
    const result = await minify(code, {
      mangle: { reserved: ["jQuery"] },
      format: { comments: false },
    });
    return banner + result.code;
  } catch (error) {
    friendly("terser", "inline", error);
  }
}

/**
 * clean-css — minify CSS content.
 *
 * @param {string} css CSS source.
 * @return {string} Minified CSS.
 */
export function minifyCSS(css) {
  try {
    const result = new CleanCSS({
      level: 1,
      mergeIntoShorthands: false,
    }).minify(css);
    if (result.errors && result.errors.length) {
      friendly("clean-css", "inline", result.errors[0]);
    }
    return result.styles;
  } catch (error) {
    friendly("clean-css", "inline", error);
  }
}

/**
 * Remove files matching a glob pattern (relative to root).
 *
 * @param {string} pattern Glob pattern.
 */
export async function cleanGlob(pattern) {
  const glob = new Bun.Glob(pattern);
  for await (const file of glob.scan({ cwd: root, onlyFiles: true })) {
    rmSync(join(root, file));
  }
}
