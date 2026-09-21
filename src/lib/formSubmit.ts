/**
 * Gemeinsame Versandlogik für alle Formulare der Seite
 * (siehe UMBAU-BRIEFING.md, Abschnitt 5a).
 *
 * PUBLIC_FORM_ENDPOINT leer     → Testbetrieb, kein Netzwerkaufruf.
 * PUBLIC_FORM_ENDPOINT gesetzt  → echter POST via FormData/fetch, Gegenstelle
 *                                  ist public/formular.php.
 *
 * Jedes Formular, das dieses Modul nutzt, braucht ein verstecktes
 * Honeypot-Feld namens "_gotcha" — ist es befüllt, gilt der Versuch als
 * Spam und wird still verworfen.
 */

export type SubmitResult =
  | { status: 'spam' }
  | { status: 'demo' }
  | { status: 'ok'; hinweis: string | null }
  | { status: 'error'; error: unknown };

interface FormularAntwort {
  ok: boolean;
  hinweis?: string | null;
  fehler?: string;
}

export function getFormEndpoint(): string {
  return (import.meta.env.PUBLIC_FORM_ENDPOINT as string | undefined) || '';
}

export async function submitForm(form: HTMLFormElement): Promise<SubmitResult> {
  const honeypot = form.querySelector<HTMLInputElement>('input[name="_gotcha"]');
  if (honeypot && honeypot.value) {
    return { status: 'spam' };
  }

  const endpoint = getFormEndpoint();
  const data = new FormData(form);
  data.delete('_gotcha');

  if (!endpoint) {
    console.info('[Attrappe] Es wurde nichts versendet.', Object.fromEntries(data as any));
    return { status: 'demo' };
  }

  try {
    const res = await fetch(endpoint, { method: 'POST', body: data });
    let antwort: FormularAntwort | null = null;
    try {
      antwort = await res.json();
    } catch {
      antwort = null;
    }

    if (!res.ok || !antwort || antwort.ok !== true) {
      throw new Error(antwort?.fehler || 'HTTP ' + res.status);
    }

    return { status: 'ok', hinweis: antwort.hinweis ?? null };
  } catch (error) {
    return { status: 'error', error };
  }
}
