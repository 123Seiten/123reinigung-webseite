/**
 * Markenkonfiguration — die zentrale Stelle für alle marken-spezifischen
 * Identitäts-Werte dieser Website.
 *
 * Für einen Markenwechsel (Schwestermarke):
 *   1. Diese Datei anpassen (Name, Domain, Claim, E-Mail, Farben).
 *   2. public/logo.png durch das neue Logo ersetzen (gleicher Dateiname
 *      oder logo.src unten anpassen).
 *   3. Die Farbwerte unten MÜSSEN identisch mit den CSS-Custom-Properties
 *      in src/styles/global.css (:root-Block) gepflegt werden — Astro
 *      unterstützt keine dynamische Werte-Injektion in global importierte
 *      Stylesheets, daher sind die Farben hier die dokumentierte
 *      Referenz, die eigentliche CSS-Variable steht in global.css.
 */
export const brand = {
  name: '123Reinigung',
  domain: '123reinigung.at',
  tagline: 'Aus Leidenschaft für Sauberkeit',
  email: 'office@123reinigung.at',

  /**
   * Firmendaten für Impressum, Footer und Datenschutz.
   * Einzige Quelle — nirgends sonst hart eintragen.
   */
  unternehmen: {
    firmenwortlaut: '123Reinigung Franchise GmbH',
    geschaeftsfuehrung: 'Stefan Langmann',
    /** Berufsbezeichnung nach § 5 ECG, samt verleihendem Staat. */
    berufsbezeichnung: 'Denkmal-, Fassaden- und Gebäudereiniger',
    verleihungsstaat: 'Österreich',

    strasse: 'Wienersdorfer Straße 20-24/M37/12/1',
    plz: '2514',
    ort: 'Traiskirchen',
    land: 'Österreich',

    /** Maschinenlesbar für tel:-Links */
    telefon: '+436607693620',
    /** Lesbar für die Anzeige */
    telefonAnzeige: '+43 660 7693620',

    unternehmensgegenstand:
      'Gebäudereinigung mit Schwerpunkt auf Photovoltaikanlagen, Marmor- und Natursteinpflege sowie Pflasterflächen',

    /** Behörde gemäß ECG */
    aufsichtsbehoerde: 'Bezirkshauptmannschaft Baden',

    // --- Werden nachgereicht, siehe UMBAU-BRIEFING.md ---
    firmenbuchnummer: '',
    firmenbuchgericht: '',
    uid: '',
    kammer: '',
  },

  logo: {
    /** Relativ zu public/, ohne führenden Slash (Basis-Pfad wird von Logo.astro ergänzt). */
    src: 'assets/logo/logo-full.svg',
  },

  /**
   * Referenzwerte — müssen mit src/styles/global.css :root übereinstimmen.
   */
  colors: {
    brand: '#0071BC',
    brandDark: '#005B96',
    brandTint: '#EBF7FC',
    brandOnDark: '#29ABE2',
  },
} as const;
