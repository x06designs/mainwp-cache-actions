import { type Operation, operationOf, readConfig } from './config.ts';
import { BULK_BUTTON, BULK_MENU, modalSiteIds, SYNC_MODAL, selectedSiteIds } from './mainwp.ts';
import { confirmMessage, PENDING_ICON, presentOutcome, RUNNING_ICON } from './present.ts';
import { runQueue } from './queue.ts';
import { runOperation } from './request.ts';

const config = readConfig();

async function run(op: Operation, siteIds: readonly string[]): Promise<void> {
  window.mainwpVars.bulkManageSitesTaskRunning = true;

  const selected = new Set(siteIds);
  for (const id of modalSiteIds()) {
    if (selected.has(id)) {
      window.dashboard_update_site_status(id, PENDING_ICON);
    } else {
      window.dashboard_update_site_hide(id);
    }
  }

  const popup = window.mainwpPopup(SYNC_MODAL);
  popup.init({
    progressMax: siteIds.length,
    title: config.i18n.operations[op],
    statusText: config.i18n.progress,
    callback: () => {
      window.mainwpVars.bulkManageSitesTaskRunning = false;
      window.mainwp_forceReload();
    },
  });

  let finished = 0;
  await runQueue(siteIds, config.concurrency, async (id) => {
    window.dashboard_update_site_status(id, RUNNING_ICON);
    const status = presentOutcome(config.i18n, await runOperation(config, id, op));
    window.dashboard_update_site_status(id, status.html, status.isSuccess);
    finished += 1;
    popup.setProgressSite(finished);
  });
}

function start(op: Operation): void {
  if (window.mainwpVars.bulkManageSitesTaskRunning) {
    return;
  }
  const siteIds = selectedSiteIds();
  if (siteIds.length === 0) {
    return;
  }
  window.mainwp_confirm(confirmMessage(config.i18n, op), () => {
    void run(op, siteIds);
  });
}

document.addEventListener('click', (event) => {
  if (!(event.target instanceof Element) || !event.target.closest(BULK_BUTTON)) {
    return;
  }
  const op = operationOf(config, window.jQuery(BULK_MENU).dropdown('get value'));
  if (op) {
    start(op);
  }
});
