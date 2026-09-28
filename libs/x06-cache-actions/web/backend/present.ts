import { sprintf } from '@wordpress/i18n';
import type { StepResult } from '../../generated/types/cache-actions.schema';
import type { Messages, Operation } from './config.ts';
import type { Outcome } from './request.ts';

export interface SiteStatus {
  html: string;
  isSuccess: boolean;
}

export const PENDING_ICON = '<i class="clock outline icon"></i>';
export const RUNNING_ICON = '<i class="sync alternate loading icon"></i>';

const ENTITIES: Record<string, string> = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
};

export function escapeHtml(text: string): string {
  return text.replace(/[&<>"']/g, (char) => ENTITIES[char] ?? char);
}

function labelOf<K extends string>(labels: Record<K, string>, key: K): string {
  return Object.hasOwn(labels, key) ? labels[key] : key;
}

/** Status cell: the result text, followed by MainWP's status icon. */
function statusIcon(iconClass: string, text: string): string {
  return `<span>${escapeHtml(text)}</span> <i class="${iconClass} icon" aria-hidden="true"></i>`;
}

function stepLine(i18n: Messages, step: StepResult): string {
  const name = labelOf(i18n.steps, step.step);
  const status = labelOf(i18n.statuses, step.status);
  return step.detail === null
    ? sprintf(i18n.step, name, status)
    : sprintf(i18n.stepDetail, name, status, labelOf(i18n.details, step.detail));
}

function failure(text: string): SiteStatus {
  return { html: statusIcon('red times', text), isSuccess: false };
}

export function presentOutcome(i18n: Messages, outcome: Outcome): SiteStatus {
  switch (outcome.kind) {
    case 'timeout':
      return failure(i18n.timeout);
    case 'failed':
      return failure(i18n.requestFailed);
    case 'unexpected':
      return failure(sprintf(i18n.unexpected, outcome.status));
  }

  const { code, message, result } = outcome.response;
  if (code !== undefined || result === undefined) {
    const text = code === undefined ? i18n.requestFailed : labelOf(i18n.codes, code);
    return failure(message ? `${text} ${message}` : text);
  }

  const text = result.steps.map((step) => stepLine(i18n, step)).join('; ');
  const hasFailed = result.steps.some((step) => step.status === 'failed');
  return hasFailed ? failure(text) : { html: statusIcon('green check', text), isSuccess: true };
}

/** Body of MainWP's confirm modal: what the action does to every selected site. */
export function confirmMessage(i18n: Messages, op: Operation): string {
  const intro = escapeHtml(sprintf(i18n.confirm, i18n.operations[op]));
  const effects = i18n.effects[op].map((effect) => `<li>${escapeHtml(effect)}</li>`).join('');
  return `<p>${intro}</p><ul>${effects}</ul>`;
}
