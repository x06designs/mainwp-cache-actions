import assert from 'node:assert/strict';
import { afterEach, beforeEach, test } from 'node:test';
import { runOperation } from '../request.ts';
import { config } from './messages.ts';

const realFetch = globalThis.fetch;
let requests: { url: string; init: RequestInit }[] = [];

function respondWith(handler: () => Promise<Response>): void {
  globalThis.fetch = ((url: string, init: RequestInit) => {
    requests.push({ url, init });
    return handler();
  }) as typeof fetch;
}

beforeEach(() => {
  requests = [];
  Object.assign(globalThis, {
    window: {
      ajaxurl: '/wp/wp-admin/admin-ajax.php',
      mainwp_secure_data: <T extends object>(data: T) => ({ ...data, security: 'nonce' }),
    },
  });
});

afterEach(() => {
  globalThis.fetch = realFetch;
});

test('posts the op for one site with the nonce and a timeout signal', async () => {
  respondWith(async () =>
    Response.json({ site_id: 2, op: 'sync_library', code: 'companion_missing' }),
  );

  const outcome = await runOperation(config, '2', 'sync_library');

  assert.deepEqual(outcome, {
    kind: 'response',
    response: { site_id: 2, op: 'sync_library', code: 'companion_missing' },
  });
  const [request] = requests;
  assert.equal(request?.url, '/wp/wp-admin/admin-ajax.php');
  assert.equal(request?.init.method, 'POST');
  assert.equal(
    String(request?.init.body),
    'action=x06_cache_actions_run&site_id=2&op=sync_library&security=nonce',
  );
  assert.ok(request?.init.signal instanceof AbortSignal);
});

test('a timeout is reported as a timeout', async () => {
  respondWith(() => Promise.reject(new DOMException('slow', 'TimeoutError')));

  assert.deepEqual(await runOperation(config, '2', 'clear_caches'), { kind: 'timeout' });
});

test('a network failure is reported as failed', async () => {
  respondWith(() => Promise.reject(new TypeError('Failed to fetch')));

  assert.deepEqual(await runOperation(config, '2', 'clear_caches'), { kind: 'failed' });
});

test('a body that is not JSON or not a known shape is unexpected, with the status', async () => {
  respondWith(async () => new Response('-1', { status: 403 }));
  assert.deepEqual(await runOperation(config, '2', 'clear_caches'), {
    kind: 'unexpected',
    status: 403,
  });

  respondWith(async () => Response.json({ result: { steps: [{ step: 1 }] } }));
  assert.deepEqual(await runOperation(config, '2', 'clear_caches'), {
    kind: 'unexpected',
    status: 200,
  });
});

test('other errors are not swallowed', async () => {
  respondWith(() => Promise.reject(new RangeError('bug')));

  await assert.rejects(runOperation(config, '2', 'clear_caches'), RangeError);
});
