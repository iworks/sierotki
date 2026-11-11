/**
 * Source formatting and cleanup — this plugin ships no CSS/JS of its own,
 * so there are no asset pipelines (removed with 3.5.0).
 */

import { log } from "./consoleHelpers.mjs";
import { prettierSources, cleanGlob } from "./tools.mjs";

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
 * Remove built files (i18n only — this plugin has no CSS/JS pipelines).
 */
export async function cleanCompiled() {
  await cleanGlob("languages/*.mo");
  log("clean — languages/*.mo removed", "i");
}
