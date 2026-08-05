# 123reinigung.at

Astro-Projekt, entstanden als Kopie des Schwesterprojekts `123Vorteilswelt_Webseite`.
Ziel: Umbau der Vorlage auf die Marke 123Reinigung.

## Zuerst lesen

| Datei | Inhalt |
|---|---|
| `UMBAU-BRIEFING.md` | **Arbeitsanweisung.** Blocker, Änderungen Datei für Datei, Reihenfolge, offene Punkte |
| `123reinigung-website-struktur-texte.md` | Sitemap, Design-Tokens, sämtliche fertigen Texte, Kachelinhalte, rechtliche Prüfpunkte |
| `../assets/logo/` | Logo als SVG und PNG, Invert- und Weiß-Varianten, OG-Bild |
| `../assets/favicon/` | Favicons, Apple-Touch-Icon, Webmanifest |

Ohne diese vier Quellen ist der Umbau nicht durchführbar. Die Texte sind bereits geschrieben — nicht neu erfinden.

**Pfadhinweis:** Das Briefing entstand, als der Projektordner noch `web/` hieß und die beiden Dokumente eine Ebene darüber lagen. Beide liegen inzwischen hier in der Projektwurzel. Wo im Briefing `../123reinigung-website-struktur-texte.md` steht, ist die Datei im selben Ordner gemeint. Für `../assets/` stimmt die Angabe weiterhin.

## Marke

| | |
|---|---|
| Dunkelblau (Primär) | `#0071BC` |
| Hellblau (Akzent) | `#29ABE2` |
| Schwarz | `#000000` |
| Weiß | `#FFFFFF` |

Keine weiteren Farbtöne. Alles andere wird aus diesen vieren abgeleitet — Transparenzstufen für Flächen, Abdunklung für Hover.

**Kontrastregel:** `#29ABE2` trägt niemals weiße Schrift (2,6:1, fällt durch WCAG AA). Hellblau ist Fläche für Icons, Linien und Hover. Primärbuttons sind `#0071BC` mit Weiß (5,1:1).

**Logo auf blauem Grund:** `logo-full-white.svg` verwenden, nicht `logo-full-invert.svg` — sonst verschwindet der innere Tropfenbogen im gleichfarbigen Hintergrund.

Schriften: Big Shoulders Display für H1/H2 und große Zahlen, Inter für alles andere. Beide bereits im Template vorhanden. Roboto ignorieren.

## Leistungen

Drei Schwerpunkte: **PV-Anlagen**, **Marmor & Naturstein**, **Pflasterflächen**.
Fassade und Glas bleiben im Angebot, aber ohne eigene Unterseiten.

Zielgruppen: Privat, Firmen, öffentliche Auftraggeber. Einsatzgebiet österreichweit.
Anrede: Endkundenbereich „Sie", Franchise-Bereich „du". Innerhalb eines Bereichs nie mischen.

## Vor jedem Livegang prüfen

- `<meta name="robots" content="noindex, nofollow">` in `Layout.astro` entfernen
- Prototyp-Banner (`.proto`) entfernen
- `astro.config.mjs`: `site` und `base` zeigen noch auf das Vorteilswelt-Repository
- Google Fonts lokal hosten statt per CDN (DSGVO)
- Formular versendet nichts und überträgt die Kachelauswahl nicht — siehe Briefing 2.3
- Volltextsuche nach `vorteilswelt`, `139e1c`, `Einkauf`, `Prüfauftrag` — auch in `alt`-Attributen, Dateinamen und Kommentaren

Vollständige Liste in `UMBAU-BRIEFING.md`, Abschnitte 2 und 4.

## Entwicklung

```
npm install
npm run dev
```

Dev-Server im Hintergrund: `astro dev --background`, verwalten mit `astro dev stop`, `astro dev status`, `astro dev logs`.

Astro-Dokumentation: https://docs.astro.build

Weitere Astro-Leitfäden: [Routing](https://docs.astro.build/en/guides/routing/) · [Komponenten](https://docs.astro.build/en/basics/astro-components/) · [Styling](https://docs.astro.build/en/guides/styling/) · [Content Collections](https://docs.astro.build/en/guides/content-collections/)
