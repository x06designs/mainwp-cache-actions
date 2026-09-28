import type { CacheActionResponse } from '../../generated/types/cache-actions.schema';
import type { Operation, ScriptConfig } from './config.ts';

export type Outcome =
  | { kind: 'response'; response: CacheActionResponse }
  | { kind: 'unexpected'; status: number }
  | { kind: 'timeout' }
  | { kind: 'failed' };

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isStep(value: unknown): boolean {
  return isRecord(value) && typeof value.step === 'string' && typeof value.status === 'string';
}

/** Accepts the endpoint's body only when it carries a code or a result with steps. */
export function parseResponse(body: unknown): CacheActionResponse | null {
  if (!isRecord(body)) {
    return null;
  }
  if (typeof body.code === 'string') {
    return body as CacheActionResponse;
  }
  const result = body.result;
  if (isRecord(result) && Array.isArray(result.steps) && result.steps.every(isStep)) {
    return body as CacheActionResponse;
  }
  return null;
}

async function readJson(response: Response): Promise<unknown> {
  try {
    return await response.json();
  } catch (error) {
    if (error instanceof SyntaxError) {
      return null;
    }
    throw error;
  }
}

/** Runs one operation on one site. Resolves with every failure as an outcome; never rejects. */
export async function runOperation(
  config: ScriptConfig,
  siteId: string,
  op: Operation,
): Promise<Outcome> {
  const data = window.mainwp_secure_data({ action: config.action, site_id: siteId, op });
  try {
    const response = await fetch(window.ajaxurl, {
      method: 'POST',
      body: new URLSearchParams(data),
      credentials: 'same-origin',
      signal: AbortSignal.timeout(config.timeoutMs),
    });
    const parsed = parseResponse(await readJson(response));
    return parsed
      ? { kind: 'response', response: parsed }
      : { kind: 'unexpected', status: response.status };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'TimeoutError') {
      return { kind: 'timeout' };
    }
    if (error instanceof TypeError) {
      return { kind: 'failed' };
    }
    throw error;
  }
}
