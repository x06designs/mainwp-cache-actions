const path = require('node:path');

const libOf = (file) => {
  const parts = path.relative(process.cwd(), file).split(path.sep);
  return parts[0] === 'libs' && parts.length > 2 ? parts[1] : null;
};

// lint-staged splits long file lists into chunks (Windows command-line limit) and calls a task
// function once per chunk. Two gate runs for the same lib would race on its vendor/ and the
// shared test database, so each lib is gated once per lint-staged run.
const gatedLibs = new Set();

module.exports = {
  '*.{js,cjs,mjs,ts,tsx,json}': 'biome check --write --no-errors-on-unmatched',
  'libs/*/**/{*.php,*.neon.dist,*.xml.dist,*.schema.json,*.ts,*.tsx,composer.json,composer.lock,package.json,php-gates.versions}':
    (files) => {
      const libs = [...new Set(files.map(libOf).filter(Boolean))].filter(
        (lib) => !gatedLibs.has(lib),
      );
      for (const lib of libs) {
        gatedLibs.add(lib);
      }
      return libs.map((lib) => `bash scripts/lib-gates.sh ${lib}`);
    },
};
