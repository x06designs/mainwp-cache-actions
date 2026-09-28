import assert from 'node:assert/strict';
import { test } from 'node:test';
import { operationOf } from '../config.ts';
import { selectedSiteIds } from '../mainwp.ts';
import { config } from './messages.ts';

test('selected site ids are numeric and unique', () => {
  Object.assign(globalThis, {
    document: {
      querySelectorAll: () => [{ value: '5' }, { value: 'on' }, { value: '5' }, { value: '12' }],
    },
  });

  assert.deepEqual(selectedSiteIds(), ['5', '12']);
});

test('only the prefixed bulk-action values map to an operation', () => {
  assert.equal(operationOf(config, 'x06_cache_actions_clear_caches'), 'clear_caches');
  assert.equal(operationOf(config, 'x06_cache_actions_bogus'), null);
  assert.equal(operationOf(config, 'clear_caches'), null);
  assert.equal(operationOf(config, 'sync'), null);
});
