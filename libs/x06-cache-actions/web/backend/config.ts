import type { CacheActionResponse, StepResult } from '../../generated/types/cache-actions.schema';

export type Operation = NonNullable<CacheActionResponse['op']>;
export type Code = NonNullable<CacheActionResponse['code']>;
export type Detail = NonNullable<StepResult['detail']>;

export interface Messages {
  operations: Record<Operation, string>;
  confirm: string;
  effects: Record<Operation, string[]>;
  progress: string;
  codes: Record<Code, string>;
  requestFailed: string;
  timeout: string;
  unexpected: string;
  steps: Record<StepResult['step'], string>;
  statuses: Record<StepResult['status'], string>;
  details: Record<Detail, string>;
  step: string;
  stepDetail: string;
}

/** Printed by ManageSitesAssets before the bundle. */
export interface ScriptConfig {
  action: string;
  prefix: string;
  concurrency: number;
  timeoutMs: number;
  i18n: Messages;
}

declare global {
  interface Window {
    x06CacheActions?: ScriptConfig;
  }
}

export function readConfig(): ScriptConfig {
  const config = window.x06CacheActions;
  if (!config) {
    throw new Error('X06 Cache Actions: window.x06CacheActions is missing.');
  }
  return config;
}

/** The operation behind a bulk-action value, or null for MainWP's own actions. */
export function operationOf(config: ScriptConfig, value: string): Operation | null {
  if (!value.startsWith(config.prefix)) {
    return null;
  }
  const op = value.slice(config.prefix.length);
  return Object.hasOwn(config.i18n.operations, op) ? (op as Operation) : null;
}
