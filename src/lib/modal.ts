/**
 * Zugängliches Overlay-Popup: Escape schließt, Klick auf den Hintergrund
 * schließt, Fokus bleibt gefangen und kehrt beim Schließen zu einem
 * bestimmten Element zurück. Sperrt das Scrollen im Hintergrund, solange
 * es offen ist — sonst scrollt die Seite auf Mobil unter dem Popup weg.
 */

const FOKUSSIERBAR =
  'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

export interface Modal {
  open(fokusRueckkehr?: HTMLElement | null): void;
  close(): void;
}

export function initModal(overlay: HTMLElement, onClose?: () => void): Modal {
  const dialog = overlay.querySelector<HTMLElement>('[role="dialog"]');
  let fokusRueckkehr: HTMLElement | null = null;

  function fokussierbareElemente(): HTMLElement[] {
    if (!dialog) return [];
    return Array.from(dialog.querySelectorAll<HTMLElement>(FOKUSSIERBAR));
  }

  function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      return;
    }
    if (e.key !== 'Tab') return;
    const items = fokussierbareElemente();
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  function onBackdropClick(e: MouseEvent) {
    if (e.target === overlay) close();
  }

  function open(fokusZiel?: HTMLElement | null) {
    fokusRueckkehr = fokusZiel || null;
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    overlay.addEventListener('keydown', onKeydown);
    overlay.addEventListener('click', onBackdropClick);
    const items = fokussierbareElemente();
    (items[0] || dialog)?.focus();
  }

  function close() {
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
    overlay.removeEventListener('keydown', onKeydown);
    overlay.removeEventListener('click', onBackdropClick);
    if (onClose) onClose();
    if (fokusRueckkehr) fokusRueckkehr.focus();
  }

  return { open, close };
}
