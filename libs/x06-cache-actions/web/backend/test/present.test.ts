import assert from 'node:assert/strict';
import { test } from 'node:test';
import type { ChildResult } from '../../../generated/types/cache-actions.schema';
import { confirmMessage, presentOutcome } from '../present.ts';
import { messages } from './messages.ts';

function result(steps: ChildResult['steps']): ChildResult {
  return { op: 'clear_caches', companion_version: '0.1.0', api_version: 1, steps };
}

test('a result without failed steps is a success with the step summary', () => {
  const status = presentOutcome(messages, {
    kind: 'response',
    response: {
      result: result([
        { step: 'elementor_clear_cache', status: 'done', detail: null },
        { step: 'wpfc_delete_cache', status: 'skipped', detail: 'plugin_inactive' },
      ]),
    },
  });

  assert.equal(status.isSuccess, true);
  assert.match(status.html, /green check icon/);
  assert.match(
    status.html,
    /<span>Elementor cache: done; WP Fastest Cache: skipped \(plugin not active\)<\/span>/,
  );
});

test('one failed step makes the site a failure', () => {
  const status = presentOutcome(messages, {
    kind: 'response',
    response: {
      result: result([
        { step: 'elementor_clear_cache', status: 'done', detail: null },
        { step: 'wpfc_delete_cache', status: 'failed', detail: 'delete_failed' },
      ]),
    },
  });

  assert.equal(status.isSuccess, false);
  assert.match(status.html, /red times icon/);
});

test('an error code shows its label followed by the message', () => {
  const status = presentOutcome(messages, {
    kind: 'response',
    response: { code: 'connection_failed', message: 'cURL error 28' },
  });

  assert.equal(status.isSuccess, false);
  assert.match(status.html, /<span>Unreachable\. cURL error 28<\/span>/);
});

test('text from the child site is escaped', () => {
  const status = presentOutcome(messages, {
    kind: 'response',
    response: { code: 'connection_failed', message: '<img src=x onerror=alert(1)>' },
  });

  assert.doesNotMatch(status.html, /<img/);
  assert.match(status.html, /&lt;img src=x onerror=alert\(1\)&gt;/);
});

test('an unknown step name is shown as is, escaped', () => {
  const status = presentOutcome(messages, {
    kind: 'response',
    response: {
      result: result([
        { step: '<b>new</b>' as 'elementor_clear_cache', status: 'done', detail: null },
      ]),
    },
  });

  assert.match(status.html, /&lt;b&gt;new&lt;\/b&gt;: done/);
});

test('timeouts, network failures and unexpected bodies get distinct messages', () => {
  assert.match(presentOutcome(messages, { kind: 'timeout' }).html, /Timed out\./);
  assert.match(presentOutcome(messages, { kind: 'failed' }).html, /Request failed\./);
  assert.match(
    presentOutcome(messages, { kind: 'unexpected', status: 500 }).html,
    /Unexpected \(HTTP 500\)\./,
  );
});

test('the confirm message names the action and lists its effects, escaped', () => {
  assert.equal(
    confirmMessage(
      { ...messages, effects: { ...messages.effects, sync_library: ['a <b> c'] } },
      'sync_library',
    ),
    '<p>&quot;Sync Elementor library&quot; runs on every selected site:</p><ul><li>a &lt;b&gt; c</li></ul>',
  );
});
