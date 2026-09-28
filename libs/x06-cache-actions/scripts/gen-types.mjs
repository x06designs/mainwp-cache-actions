#!/usr/bin/env node
/**
 * Run json-schema-to-typescript over includes/**\/*.schema.json, or exit 0
 * with a skip message when the plugin has no schema files (e.g. a pure
 * frontend/theme plugin). Keeps `gen` (and therefore `build`, which depends
 * on it) green for schema-less plugins.
 *
 * Output keeps the documented flat contract `generated/types/<name>.schema.d.ts`
 * (imported as `generated/types/<name>.schema`). Each schema resolves relative
 * `$ref`s from its own directory (the library default), and two schemas with the
 * same file name fail the run instead of silently overwriting each other.
 *
 * @package X06CacheActions
 */

import { existsSync, mkdirSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { basename, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(fileURLToPath(import.meta.url), '..', '..');
const includesDir = join(root, 'includes');

function findSchemaFiles(dir) {
  if (!existsSync(dir)) return [];
  const found = [];
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    const stat = statSync(full);
    if (stat.isDirectory()) {
      found.push(...findSchemaFiles(full));
    } else if (entry.endsWith('.schema.json')) {
      found.push(full);
    }
  }
  return found;
}

const schemaFiles = findSchemaFiles(includesDir);

if (schemaFiles.length === 0) {
  console.log(
    '[gen:types] no *.schema.json found under includes/ — skipping json-schema-to-typescript.',
  );
  process.exit(0);
}

const outDir = join(root, 'generated', 'types');
mkdirSync(outDir, { recursive: true });

const { compileFromFile } = await import('json-schema-to-typescript');

const sources = new Map();
for (const schemaFile of schemaFiles) {
  const fileName = basename(schemaFile).replace(/\.json$/, '.d.ts');
  const previous = sources.get(fileName);
  if (previous) {
    console.error(
      `[gen:types] ${relative(root, schemaFile)} and ${relative(root, previous)} both generate generated/types/${fileName}; rename one schema.`,
    );
    process.exit(1);
  }
  sources.set(fileName, schemaFile);
  const ts = await compileFromFile(schemaFile, { unreachableDefinitions: true });
  writeFileSync(join(outDir, fileName), ts);
}

console.log(
  `[gen:types] generated ${schemaFiles.length} type file(s) from includes/**/*.schema.json.`,
);
