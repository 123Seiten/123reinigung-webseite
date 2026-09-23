/**
 * Bild-Manifest — die zentrale Stelle, an der jedes Bild der Website
 * eingetragen wird: Dateiname, Alt-Text, Quelle und KI-Kennzeichnung.
 *
 * Neues Bild registrieren:
 *   1. Datei ablegen — Inhaltsbild (wird von Astro optimiert) in
 *      src/assets/bilder/, Hintergrundbild (bereits web-optimiert) in
 *      public/bilder/.
 *   2. Hier einen Eintrag ergänzen (Schlüssel frei wählbar, wird beim
 *      Einbinden über <InhaltsBild eintrag="..."> bzw.
 *      <HintergrundBild eintrag="..."> referenziert).
 *
 * Mehr ist nicht nötig: Alt-Text, KI-Hinweis am Bild und die
 * "Bildquellen"-Auflistung im Impressum aktualisieren sich automatisch.
 */

export interface BildEintrag {
  /** Dateiname inkl. Endung, wie im jeweiligen Ordner abgelegt. */
  datei: string;
  /** Alt-Text für Screenreader/SEO. Bei rein dekorativen Bildern: ''. */
  alt: string;
  /** Kurze, menschenlesbare Bezeichnung für die Bildquellen-Auflistung im Impressum. */
  beschreibung: string;
  /** Quelle des Bildmaterials. */
  quelle: string;
  /** true, wenn das Bild (teilweise) KI-generiert ist — zeigt automatisch den Hinweis "mit KI erstellt" am Bild. */
  kiGeneriert: boolean;
}

export const BILDER = {
  // ---- Fallbeispiele: Vorher-Nachher-Paare, Objektfotos von Langmann ----
  // Alle sechs Bilder liegen als 1200x1200 in src/assets/bilder/ und werden
  // von Astro in WebP umgewandelt. Zufahrt und Terrasse stammen aus
  // Schnappschuessen (576x768) und wurden entrauscht, verdoppelt und
  // nachgeschaerft; der Bildausschnitt ist so gesetzt, dass Vorher und
  // Nachher denselben Bereich zeigen und die Ueberblendung nicht springt.

  zufahrtVorher: {
    datei: 'zufahrt-vorher.jpg',
    alt: 'Gepflasterte Grundstückszufahrt mit grünem Algenbelag zwischen den Steinen, im Hintergrund ein Backsteintor',
    beschreibung: 'Grundstückszufahrt vor der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  zufahrtNachher: {
    datei: 'zufahrt-nachher.jpg',
    alt: 'Dieselbe Zufahrt nach der Reinigung: rotes Pflaster und heller Randstreifen, frei von Bewuchs',
    beschreibung: 'Grundstückszufahrt nach der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },

  terrasseVorher: {
    datei: 'terrasse-vorher.jpg',
    alt: 'Natursteinterrasse mit grau-grünem Schmutzbelag, ein dunkler Pflasterstreifen verläuft quer durch die Fläche',
    beschreibung: 'Natursteinterrasse vor der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  terrasseNachher: {
    datei: 'terrasse-nachher.jpg',
    alt: 'Dieselbe Terrasse nach der Reinigung: goldfarben gemaserter Granit, der Pflasterstreifen hebt sich hell ab',
    beschreibung: 'Natursteinterrasse nach der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },

  treppeVorher: {
    datei: 'treppe-vorher.jpg',
    alt: 'Außentreppe aus Naturstein, die Stufen sind von Algen und Schmutz fast schwarz verfärbt, seitlich ein weißes Geländer',
    beschreibung: 'Natursteintreppe vor der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  treppeNachher: {
    datei: 'treppe-nachher.jpg',
    alt: 'Dieselbe Treppe nach der Reinigung: die Stufen zeigen wieder den warmen, hellen Farbton des Natursteins',
    beschreibung: 'Natursteintreppe nach der Reinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  // ---- Hero-Hintergrund (liegt in public/bilder/, nicht in src/assets/) ----
  // Aufnahme vom Parlamentsauftrag. Gespiegelt und aufgehellt; die abgedeckte
  // Laterne wurde entfernt und die arbeitende Person durch eine generierte
  // Person ersetzt — deshalb kiGeneriert: true.
  heroParlament: {
    datei: 'hero-parlament-v5.webp',
    alt: 'Mitarbeiter im blauen Arbeitsanzug reinigt mit einem Flächenreiniger das Pflaster vor dem Parlament in Wien, im Hintergrund der Pallas-Athene-Brunnen',
    beschreibung: 'Parlament Wien — Pflasterreinigung (Hero-Bild, abgebildete Person ist aus datenschutzrechtlichen Gründen KI-generiert)',
    quelle: 'Langmann Facility Services',
    kiGeneriert: true,
  },

  // Eigene Aufnahme im Hochformat 9:16 fuer schmale Geraete. Dort fuellt das
  // Querformat den Bildausschnitt nicht sinnvoll: Der Mitarbeiter wuerde
  // seitlich herausfallen. Ebenfalls mit ersetzter Person.
  heroParlamentMobil: {
    datei: 'hero-parlament-mobil.webp',
    alt: 'Mitarbeiter im blauen Arbeitsanzug reinigt mit einem Flächenreiniger das Pflaster vor dem Parlament in Wien, dahinter der Pallas-Athene-Brunnen',
    beschreibung: 'Parlament Wien — Pflasterreinigung (Hero-Bild Hochformat, abgebildete Person ist aus datenschutzrechtlichen Gründen KI-generiert)',
    quelle: 'Langmann Facility Services',
    kiGeneriert: true,
  },

  // ---- Referenzobjekte: je ein Objektfoto, 1200x900 (4:3) ----

  refParlament: {
    datei: 'ref-parlament.jpg',
    alt: 'Mitarbeiter mit Reinigungsmaschine vor der Säulenfront des Parlaments in Wien, im Vordergrund die geschwungene Granitmauer',
    beschreibung: 'Parlament Wien — Naturstein- und Pflasterreinigung',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  refOebb: {
    datei: 'ref-oebb.jpg',
    alt: 'Mitarbeiter entfernt ein großflächiges Graffiti vom Seitenblech einer Lokomotive',
    beschreibung: 'ÖBB — Graffitientfernung an Schienenfahrzeugen',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
  // PLATZHALTER — zeigt einen historischen Ornamentboden, stammt aber NICHT
  // vom Objekt Unteres Belvedere. Vor dem Livegang durch das nachgereichte
  // Originalfoto ersetzen, sonst steht ein fremdes Objekt unter der Referenz.
  refBelvedere: {
    datei: 'ref-belvedere-platzhalter.jpg',
    alt: 'Historischer Ornamentboden aus Zementfliesen nach der Aufbereitung, die Musterung zeichnet sich klar ab',
    beschreibung: 'Historischer Ornamentboden (Platzhalterbild)',
    quelle: 'Langmann Facility Services',
    kiGeneriert: false,
  },
} as const satisfies Record<string, BildEintrag>;

export type BildKey = keyof typeof BILDER;

/** Liefert den Manifest-Eintrag zu einem Schlüssel, mit klarer Fehlermeldung bei Tippfehlern. */
export function getBild(eintrag: BildKey): BildEintrag {
  const bild = BILDER[eintrag];
  if (!bild) {
    throw new Error(`Kein Bild-Manifest-Eintrag für "${eintrag}" in src/config/bilder.ts gefunden.`);
  }
  return bild;
}

/** Bekannte Lizenz-/Rechte-Hinweise pro Quelle für die Bildquellen-Auflistung im Impressum. */
export const BILDQUELLEN_LIZENZ: Record<string, string> = {
  Canva: 'Canva-Inhaltslizenz',
};
