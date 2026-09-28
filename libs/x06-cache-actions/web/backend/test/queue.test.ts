import assert from 'node:assert/strict';
import { test } from 'node:test';
import { runQueue } from '../queue.ts';

async function run(items: number[], concurrency: number) {
  const started: number[] = [];
  let inFlight = 0;
  let maxInFlight = 0;
  await runQueue(items, concurrency, async (item) => {
    started.push(item);
    inFlight += 1;
    maxInFlight = Math.max(maxInFlight, inFlight);
    await new Promise((resolve) => setTimeout(resolve, 5));
    inFlight -= 1;
  });
  return { started, maxInFlight };
}

test('every item runs once, in order, with at most `concurrency` in flight', async () => {
  const { started, maxInFlight } = await run([1, 2, 3, 4, 5, 6, 7], 3);

  assert.deepEqual(started, [1, 2, 3, 4, 5, 6, 7]);
  assert.equal(maxInFlight, 3);
});

test('fewer items than lanes still run in parallel', async () => {
  const { started, maxInFlight } = await run([1, 2], 3);

  assert.deepEqual(started, [1, 2]);
  assert.equal(maxInFlight, 2);
});

test('an empty queue resolves', async () => {
  assert.deepEqual((await run([], 3)).started, []);
});
