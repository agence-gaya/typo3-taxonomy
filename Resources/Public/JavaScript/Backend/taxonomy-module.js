import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';
import Modal from '@typo3/backend/modal.js';
import Notification from '@typo3/backend/notification.js';
import Sortable from 'sortablejs';
import { SeverityEnum } from '@typo3/backend/enum/severity.js';

class TaxonomyModule {
  constructor() {
    DocumentService.ready().then(() => {
      this.initializeDeleteConfirmation();
      this.initializeTermSorting();
    });
  }

  initializeDeleteConfirmation() {
    document.querySelectorAll('.t3js-taxonomy-delete').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const modal = Modal.confirm(
          button.dataset.title,
          button.dataset.message,
          SeverityEnum.warning,
          [
            {
              text: 'Cancel',
              active: true,
              btnClass: 'btn-default',
              name: 'cancel',
            },
            {
              text: 'Delete',
              btnClass: 'btn-warning',
              name: 'delete',
            },
          ],
        );
        modal.addEventListener('button.clicked', (modalEvent) => {
          if (modalEvent.target.name === 'delete') {
            window.location.href = button.href;
          }
          modal.hideModal();
        });
      });
    });
  }

  initializeTermSorting() {
    const table = document.querySelector('.taxonomy-terms[data-can-sort="1"]');
    if (table === null) {
      return;
    }
    const tbody = table.querySelector('tbody');
    if (tbody === null) {
      return;
    }

    new Sortable(tbody, {
      handle: '.taxonomy-drag-handle',
      animation: 150,
      onEnd: (event) => this.persistTermSorting(table, event),
    });
  }

  persistTermSorting(table, event) {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const movedRow = event.item;
    const pointerX = event.originalEvent?.clientX || null;
    const items = rows.map((row, index) => {
      const previousRow = rows[index - 1] || null;
      const previousDepth = previousRow === null ? 0 : parseInt(previousRow.dataset.depth || '0', 10);
      let currentDepth = parseInt(row.dataset.depth || '0', 10);
      let parent = 0;

      if (row === movedRow && pointerX !== null) {
        const titleCell = row.querySelector('.taxonomy-term-title');
        const baseX = table.getBoundingClientRect().left + 64;
        currentDepth = Math.max(0, Math.round((pointerX - baseX) / 24));
        currentDepth = Math.min(currentDepth, previousDepth + 1);
        if (titleCell !== null) {
          titleCell.style.paddingLeft = `${currentDepth * 24}px`;
        }
      }

      row.dataset.depth = currentDepth.toString();
      row.dataset.parent = '0';
      row.querySelector('.taxonomy-term-title').style.paddingLeft = `${currentDepth * 24}px`;

      for (let i = index - 1; i >= 0; i--) {
        const candidate = rows[i];
        const candidateDepth = parseInt(candidate.dataset.depth || '0', 10);
        if (candidateDepth === currentDepth - 1) {
          parent = parseInt(candidate.dataset.uid, 10);
          break;
        }
      }

      row.dataset.parent = parent.toString();

      return {
        uid: parseInt(row.dataset.uid, 10),
        parent,
      };
    });

    new AjaxRequest(table.dataset.sortUrl)
      .post({ items })
      .then(async (response) => response.resolve())
      .then((result) => {
        if (!result.success) {
          Notification.error(result.message || 'Unable to save the term order.');
        }
      })
      .catch(() => {
        Notification.error('Unable to save the term order.');
      });
  }
}

export default new TaxonomyModule();
