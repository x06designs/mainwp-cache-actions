/** The MainWP Dashboard globals this script uses (mainwp.js, mainwp-ui.js, mainwp-popup.js). */

interface Dropdown {
  dropdown(behavior: 'get value'): string;
}

export interface Popup {
  init(data: {
    progressMax: number;
    title: string;
    statusText: string;
    callback: () => void;
  }): void;
  setProgressSite(value: number): void;
}

declare global {
  interface Window {
    ajaxurl: string;
    jQuery: (selector: string) => Dropdown;
    mainwpVars: { bulkManageSitesTaskRunning?: boolean };
    mainwp_confirm(message: string, onConfirm: () => void): void;
    mainwp_secure_data<T extends { action: string }>(data: T): T & { security?: string };
    mainwpPopup(selector: string): Popup;
    dashboard_update_site_status(siteId: string, html: string, isSuccess?: boolean): void;
    dashboard_update_site_hide(siteId: string): void;
    mainwp_forceReload(): void;
  }
}

export const BULK_BUTTON = '#mainwp-do-sites-bulk-actions';
export const BULK_MENU = '#mainwp-sites-bulk-actions-menu';
export const SYNC_MODAL = '#mainwp-sync-sites-modal';

const SITE_CHECKBOXES =
  '#mainwp-manage-sites-body-table .check-column input[type="checkbox"]:checked';
const MODAL_SITES = '.dashboard_wp_id';
const SITE_ID = /^\d+$/;

function siteIds(selector: string): string[] {
  return Array.from(document.querySelectorAll<HTMLInputElement>(selector), (el) => el.value).filter(
    (id) => SITE_ID.test(id),
  );
}

/** Site ids of the checked rows in the Manage Sites table. */
export function selectedSiteIds(): string[] {
  return [...new Set(siteIds(SITE_CHECKBOXES))];
}

/** Site ids listed in the sync modal. */
export function modalSiteIds(): string[] {
  return siteIds(MODAL_SITES);
}
