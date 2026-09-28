#!/usr/bin/env node
/**
 * Run a PHP script with whichever PHP this machine actually has.
 *
 * Local development can keep PHP in a Lando container, so a bare `php`
 * is absent on most dev machines — while CI images have `php` and no `lando`.
 * A hardcoded `php` breaks every local `nx build`; a hardcoded `lando php`
 * breaks CI. So probe: prefer host `php` (faster, no container round-trip),
 * fall back to `lando php`.
 *
 * Usage: node scripts/run-php.mjs <script.php> [args...]
 */
import { spawnSync } from 'node:child_process';

const args = process.argv.slice(2);
if (args.length === 0) {
  console.error('usage: node scripts/run-php.mjs <script.php> [args...]');
  process.exit(2);
}

// `shell: true` on Windows so PATHEXT resolves php.bat / lando.exe.
const shell = process.platform === 'win32';
const available = (cmd, probe) => spawnSync(cmd, probe, { stdio: 'ignore', shell }).status === 0;

const runners = [
  { cmd: 'php', prefix: [], probe: ['--version'] },
  { cmd: 'lando', prefix: ['php'], probe: ['version'] },
];

const runner = runners.find((r) => available(r.cmd, r.probe));
if (!runner) {
  console.error(
    'No PHP available: neither `php` nor `lando` is on PATH.\n' +
      'Install PHP 8.3 on the host or start a Lando environment for this repository.',
  );
  process.exit(1);
}

const { status } = spawnSync(runner.cmd, [...runner.prefix, ...args], {
  stdio: 'inherit',
  shell,
});
process.exit(status ?? 1);
