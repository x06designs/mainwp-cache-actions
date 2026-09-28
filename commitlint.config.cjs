module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      ['feat', 'fix', 'perf', 'docs', 'refactor', 'test', 'build', 'ci', 'chore', 'revert'],
    ],
    'scope-enum': [
      2,
      'always',
      [
        'cache-actions',
        'cache-actions-child',
        'dashboard',
        'e2e',
        'lando',
        'tooling',
        'deps',
        'release',
        'ci',
        'repo',
      ],
    ],
    'subject-case': [0],
    'header-max-length': [2, 'always', 72],
  },
};
