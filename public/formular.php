<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Vienna');

// Eine ausgegebene Warnung vor dem JSON würde die Antwort für fetch().json() unlesbar machen.
error_reporting(E_ALL);
ini_set('display_errors', '0');

require __DIR__ . '/lib/phpmailer/Exception.php';
require __DIR__ . '/lib/phpmailer/PHPMailer.php';
require __DIR__ . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

function antwortOk(?string $hinweis = null): never
{
    echo json_encode(['ok' => true, 'hinweis' => $hinweis]);
    exit;
}

function antwortFehler(string $code): never
{
    echo json_encode(['ok' => false, 'fehler' => $code]);
    exit;
}

function feld(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

/** Entfernt Zeilenumbrüche aus Werten, die in Mail-Header (Betreff, Reply-To) fließen. */
function kopfzeileSaeubern(string $wert): string
{
    return trim(str_replace(["\r", "\n", '%0a', '%0d', '%0A', '%0D'], '', $wert));
}

/** Verzeichnis für die Sperrlisten — außerhalb des Dokumentenstamms, wenn beschreibbar. */
function datenVerzeichnis(): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
    $ausserhalb = rtrim(dirname($docRoot), '/\\') . '/formular-daten';
    if ((is_dir($ausserhalb) || @mkdir($ausserhalb, 0700, true)) && is_writable($ausserhalb)) {
        return $ausserhalb;
    }
    return __DIR__;
}

/**
 * Prüft und aktualisiert eine Sperrliste. Gibt true zurück, wenn dieser
 * Aufruf die Grenze überschreitet. Bei Schreibfehlern wird nicht gesperrt —
 * ein echter Interessent soll nie an einer defekten Sperrliste scheitern.
 */
function rateLimitErreicht(string $pfad, string $schluessel, int $maxProStunde): bool
{
    $jetzt = time();
    $handle = @fopen($pfad, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if ($handle) {
            fclose($handle);
        }
        return false;
    }

    $inhalt = stream_get_contents($handle) ?: '';
    $eintraege = [];
    foreach (preg_split('/\r?\n/', $inhalt) as $zeile) {
        $zeile = trim($zeile);
        if ($zeile === '' || !str_contains($zeile, ':')) {
            continue;
        }
        [$k, $t] = explode(':', $zeile, 2);
        $t = (int) $t;
        if ($jetzt - $t < 3600) {
            $eintraege[] = [$k, $t];
        }
    }

    $vorhandene = 0;
    foreach ($eintraege as $eintrag) {
        if ($eintrag[0] === $schluessel) {
            $vorhandene++;
        }
    }
    $eintraege[] = [$schluessel, $jetzt];

    ftruncate($handle, 0);
    rewind($handle);
    foreach ($eintraege as $eintrag) {
        fwrite($handle, $eintrag[0] . ':' . $eintrag[1] . "\n");
    }
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return ($vorhandene + 1) > $maxProStunde;
}

function auftraggeberLabel(string $wert): string
{
    return match ($wert) {
        'privat' => 'Privat',
        'unternehmen' => 'Unternehmen',
        'oeffentlich' => 'Öffentlich',
        default => $wert,
    };
}

function bestehenderBetriebLabel(string $wert): string
{
    return match ($wert) {
        'bestehender-betrieb' => 'Bestehender Betrieb',
        'quereinsteiger' => 'Quereinsteiger:in',
        default => $wert,
    };
}

function startzeitpunktLabel(string $wert): string
{
    return match ($wert) {
        'so-bald-wie-moeglich' => 'So bald wie möglich',
        '3-monate' => 'Innerhalb von 3 Monaten',
        '6-monate' => 'Innerhalb von 6 Monaten',
        'offen' => 'Noch offen',
        default => $wert,
    };
}

/**
 * @param array<string,string> $anhaenge Pfad => Anzeigename
 */
function mailVersenden(
    array $config,
    string $an,
    string $betreff,
    string $text,
    string $replyToAdresse = '',
    string $replyToName = '',
    array $anhaenge = []
): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($config['from'], $config['fromName']);
        $mail->addAddress($an);
        if ($replyToAdresse !== '') {
            $mail->addReplyTo($replyToAdresse, $replyToName);
        }

        $mail->Subject = $betreff;
        $mail->isHTML(false);
        $mail->Body = $text;

        foreach ($anhaenge as $pfad => $anzeigename) {
            $mail->addAttachment($pfad, $anzeigename);
        }

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

// 1 · Nur POST zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    antwortFehler('methode');
}

// Wird bei zu großem Upload von PHP stillschweigend verworfen — $_POST bleibt
// dann leer, obwohl tatsächlich Daten gesendet wurden.
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    antwortFehler('upload');
}

// 2 · Konfiguration laden
$configPfad = __DIR__ . '/mail-config.php';
if (!is_file($configPfad)) {
    antwortFehler('konfiguration');
}
/** @var array<string,mixed> $config */
$config = require $configPfad;

// 3 · Honeypot — befüllt: als Erfolg antworten, aber nichts versenden
if (!empty($_POST['_gotcha'])) {
    antwortOk(null);
}

// Welches Formular
$formular = $_POST['formular'] ?? '';
if (!in_array($formular, ['anfrage', 'franchise'], true)) {
    antwortFehler('formular');
}

// 4 · Zeitprüfung — unter 3 Sekunden gilt als Bot, keine erklärende Meldung
$ts = (float) ($_POST['_ts'] ?? 0);
$jetztMs = microtime(true) * 1000;
if ($ts <= 0 || ($jetztMs - $ts) < 3000) {
    antwortFehler('validierung');
}

// 5 · Sperrlisten
$datenDir = datenVerzeichnis();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$emailRoh = feld('email');
$emailHash = hash('sha256', strtolower($emailRoh));

$ipLimitErreicht = rateLimitErreicht($datenDir . '/_rate-ip.txt', $ip, 3);
$mailLimitErreicht = rateLimitErreicht($datenDir . '/_rate-mail.txt', $emailHash, 2);

$bestaetigungGrund = null;
if ($ipLimitErreicht) {
    $bestaetigungGrund = 'IP-Grenze überschritten, max. 3 pro Stunde';
} elseif ($mailLimitErreicht) {
    $bestaetigungGrund = 'E-Mail-Grenze überschritten, max. 2 pro Stunde';
}
$bestaetigungVersuchen = $bestaetigungGrund === null;
$hinweisCode = $ipLimitErreicht ? 'rate-ip' : ($mailLimitErreicht ? 'rate-mail' : null);

// 6 · Pflichtfelder serverseitig
$name = feld('name');
$email = $emailRoh;
$telefon = feld('telefon');

if ($formular === 'anfrage') {
    $plz = feld('plz');
    $nachricht = feld('nachricht');
    if ($name === '' || $email === '' || $plz === '' || $nachricht === '' || empty($_POST['datenschutz'])) {
        antwortFehler('validierung');
    }
} else {
    $bestehenderBetrieb = feld('bestehender_betrieb');
    $wunschgebiet = feld('wunschgebiet');
    if ($name === '' || $email === '' || $bestehenderBetrieb === '' || $wunschgebiet === '' || empty($_POST['datenschutz'])) {
        antwortFehler('validierung');
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    antwortFehler('validierung');
}

// 7 · Eingaben säubern — alles, was in Betreff oder Reply-To fließt
$name = kopfzeileSaeubern($name);
$email = kopfzeileSaeubern($email);
$telefon = kopfzeileSaeubern($telefon);

$datum = date('d.m.Y');
$uhrzeit = date('H:i');

$tempPfade = [];
$anhaenge = [];

try {
    // 8 · Fotos — nur beim Anfrageformular
    $fotosListeIntern = [];
    $fotosAnzahl = 0;

    if ($formular === 'anfrage' && !empty($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
        $anzahlDateien = count($_FILES['fotos']['name']);
        if ($anzahlDateien > 10) {
            antwortFehler('validierung');
        }

        $erlaubteTypen = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $summe = 0;
        $fotos = [];

        for ($i = 0; $i < $anzahlDateien; $i++) {
            $fehler = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($fehler === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($fehler !== UPLOAD_ERR_OK) {
                antwortFehler('validierung');
            }

            $tmp = $_FILES['fotos']['tmp_name'][$i];
            $groesse = (int) $_FILES['fotos']['size'][$i];
            if ($groesse > 10 * 1024 * 1024) {
                antwortFehler('validierung');
            }
            $summe += $groesse;
            if ($summe > 20 * 1024 * 1024) {
                antwortFehler('validierung');
            }

            $typ = $finfo->file($tmp);
            if (!in_array($typ, $erlaubteTypen, true)) {
                antwortFehler('validierung');
            }

            $fotos[] = ['tmp' => $tmp, 'typ' => $typ, 'name' => $_FILES['fotos']['name'][$i]];
        }

        foreach ($fotos as $index => $foto) {
            $bild = match ($foto['typ']) {
                'image/jpeg' => @imagecreatefromjpeg($foto['tmp']),
                'image/png' => @imagecreatefrompng($foto['tmp']),
                'image/webp' => @imagecreatefromwebp($foto['tmp']),
                default => false,
            };
            // Neukodierung schlägt fehl, wenn der Inhalt trotz erkanntem
            // MIME-Typ kein gültiges Bild ist — typischer Angriffsweg.
            if ($bild === false) {
                antwortFehler('validierung');
            }

            $zielTmp = tempnam(sys_get_temp_dir(), 'formular_foto_');
            imagejpeg($bild, $zielTmp, 88);
            imagedestroy($bild);

            $tempPfade[] = $zielTmp;
            $anzeigename = sprintf('foto-%d.jpg', $index + 1);
            $anhaenge[$zielTmp] = $anzeigename;
            $fotosListeIntern[] = '- ' . $anzeigename . ' (' . $foto['name'] . ')';
            $fotosAnzahl++;
        }
    }

    // 9 · Interne Mail versenden
    if ($formular === 'anfrage') {
        $firma = kopfzeileSaeubern(feld('firma'));
        $ort = kopfzeileSaeubern(feld('ort'));
        $auftraggeberRoh = kopfzeileSaeubern(feld('auftraggeber'));
        $bereicheRoh = array_map('strval', $_POST['bereiche'] ?? []);
        $bereiche = array_map('kopfzeileSaeubern', $bereicheRoh);
        $bereicheAnzeige = $bereiche !== [] ? implode(', ', $bereiche) : 'Allgemein';

        $kontaktZeilen = ['Name:         ' . $name];
        if ($firma !== '') {
            $kontaktZeilen[] = 'Firma:        ' . $firma;
        }
        $kontaktZeilen[] = 'E-Mail:       ' . $email;
        if ($telefon !== '') {
            $kontaktZeilen[] = 'Telefon:      ' . $telefon;
        }
        $kontaktZeilen[] = 'PLZ / Ort:    ' . trim($plz . ' ' . $ort);
        if ($auftraggeberRoh !== '') {
            $kontaktZeilen[] = 'Auftraggeber: ' . auftraggeberLabel($auftraggeberRoh);
        }

        $bereicheListeText = $bereiche !== [] ? implode("\n", array_map(fn($b) => '- ' . $b, $bereiche)) : '- Allgemein';

        $abschnitte = [
            'Neue Anfrage über 123reinigung.at',
            'Eingegangen am ' . $datum . ' um ' . $uhrzeit,
            '',
            'AUSGEWÄHLTE BEREICHE',
            $bereicheListeText,
            '',
            'KONTAKT',
            implode("\n", $kontaktZeilen),
            '',
            'NACHRICHT',
            $nachricht,
        ];

        if ($fotosListeIntern !== []) {
            $abschnitte[] = '';
            $abschnitte[] = 'FOTOS';
            $abschnitte[] = implode("\n", $fotosListeIntern);
        }

        if ($bestaetigungGrund !== null) {
            $abschnitte[] = '';
            $abschnitte[] = 'HINWEIS: Bestätigungsmail an den Absender wurde nicht versendet (' . $bestaetigungGrund . ').';
        }

        $abschnitte[] = '';
        $abschnitte[] = '--';
        $abschnitte[] = 'Antworten Sie direkt auf diese E-Mail — sie geht an ' . $email . '.';

        $internText = implode("\n", $abschnitte);
        $internBetreff = '[Anfrage] ' . $bereicheAnzeige . ' – ' . $plz . ' ' . $ort;

        $internErfolg = mailVersenden(
            $config,
            $config['empfaenger']['anfrage'],
            $internBetreff,
            $internText,
            $email,
            $name,
            $anhaenge
        );
    } else {
        $wunschgebiet = kopfzeileSaeubern($wunschgebiet);
        $bestehenderBetrieb = kopfzeileSaeubern($bestehenderBetrieb);
        $startzeitpunktRoh = kopfzeileSaeubern(feld('startzeitpunkt'));
        $erfahrung = feld('erfahrung');

        $angabenZeilen = [
            'Wunschgebiet:        ' . $wunschgebiet,
            'Bestehender Betrieb: ' . bestehenderBetriebLabel($bestehenderBetrieb),
        ];
        if ($startzeitpunktRoh !== '') {
            $angabenZeilen[] = 'Startzeitpunkt:      ' . startzeitpunktLabel($startzeitpunktRoh);
        }

        $abschnitte = [
            'Neue Franchise-Bewerbung über 123reinigung.at',
            'Eingegangen am ' . $datum . ' um ' . $uhrzeit,
            '',
            'BEWERBER',
            'Name:      ' . $name,
            'E-Mail:    ' . $email,
        ];
        if ($telefon !== '') {
            $abschnitte[] = 'Telefon:   ' . $telefon;
        }
        $abschnitte[] = '';
        $abschnitte[] = 'ANGABEN';
        $abschnitte[] = implode("\n", $angabenZeilen);

        if ($erfahrung !== '') {
            $abschnitte[] = '';
            $abschnitte[] = 'ERFAHRUNG';
            $abschnitte[] = $erfahrung;
        }

        if ($bestaetigungGrund !== null) {
            $abschnitte[] = '';
            $abschnitte[] = 'HINWEIS: Bestätigungsmail an den Absender wurde nicht versendet (' . $bestaetigungGrund . ').';
        }

        $abschnitte[] = '';
        $abschnitte[] = '--';
        $abschnitte[] = 'Antworten Sie direkt auf diese E-Mail — sie geht an ' . $email . '.';

        $internText = implode("\n", $abschnitte);
        $internBetreff = '[Franchise] ' . $wunschgebiet . ' – ' . $name;

        $internErfolg = mailVersenden(
            $config,
            $config['empfaenger']['franchise'],
            $internBetreff,
            $internText,
            $email,
            $name
        );
    }

    if (!$internErfolg) {
        antwortFehler('versand');
    }

    // 10 · Bestätigungsmail — scheitert sie, kein Fehler für den Absender
    if ($bestaetigungVersuchen) {
        if ($formular === 'anfrage') {
            $bestaetigungZeilen = [
                'Guten Tag ' . $name . ',',
                '',
                'vielen herzlichen Dank! Wir bestätigen den Eingang Ihrer Anfrage!',
                'Sehr gern melden wir uns an Werktagen innerhalb von 24 Stunden bei',
                'Ihnen.',
                '',
                'Damit Sie prüfen können, ob alles richtig angekommen ist, hier Ihre',
                'Angaben im Überblick:',
                '',
                'Bereiche:     ' . $bereicheAnzeige,
            ];
            if ($firma !== '') {
                $bestaetigungZeilen[] = 'Firma:        ' . $firma;
            }
            $bestaetigungZeilen[] = 'Name:         ' . $name;
            $bestaetigungZeilen[] = 'E-Mail:       ' . $email;
            if ($telefon !== '') {
                $bestaetigungZeilen[] = 'Telefon:      ' . $telefon;
            }
            $bestaetigungZeilen[] = 'PLZ / Ort:    ' . trim($plz . ' ' . $ort);
            $bestaetigungZeilen[] = 'Fotos:        ' . $fotosAnzahl;
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Ihre Nachricht:';
            $bestaetigungZeilen[] = $nachricht;
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Stimmt etwas nicht oder möchten Sie etwas ergänzen? Antworten Sie';
            $bestaetigungZeilen[] = 'einfach auf diese E-Mail.';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Freundliche Grüße';
            $bestaetigungZeilen[] = 'Ihr Team von 123Reinigung';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = '123Reinigung Franchise GmbH';
            $bestaetigungZeilen[] = 'Wienersdorfer Straße 20-24/M37/12/1';
            $bestaetigungZeilen[] = '2514 Traiskirchen';
            $bestaetigungZeilen[] = 'Telefon: +43 660 7693620';
            $bestaetigungZeilen[] = 'office@123reinigung.at · 123reinigung.at';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Diese E-Mail wurde automatisch erstellt.';

            $bestaetigungErfolg = mailVersenden(
                $config,
                $email,
                'Ihre Anfrage bei 123Reinigung',
                implode("\n", $bestaetigungZeilen),
                $config['antwortAn'],
                '123Reinigung'
            );
            if (!$bestaetigungErfolg) {
                $hinweisCode = 'bestaetigung-fehler';
            }
        } else {
            $bestaetigungZeilen = [
                'Guten Tag ' . $name . ',',
                '',
                'vielen herzlichen Dank für Ihr Interesse an einer Partnerschaft mit',
                '123Reinigung! Wir bestätigen den Eingang Ihrer Anfrage!',
                '',
                'Sehr gern melden wir uns in spätestens drei Werktagen persönlich bei',
                'Ihnen. Da wir Gebiete exklusiv vergeben, nehmen wir uns für jede',
                'Anfrage die nötige Zeit.',
                '',
                'Ihre Angaben im Überblick:',
                '',
                'Name:                ' . $name,
                'E-Mail:              ' . $email,
            ];
            if ($telefon !== '') {
                $bestaetigungZeilen[] = 'Telefon:             ' . $telefon;
            }
            $bestaetigungZeilen[] = 'Wunschgebiet:        ' . $wunschgebiet;
            $bestaetigungZeilen[] = 'Bestehender Betrieb: ' . bestehenderBetriebLabel($bestehenderBetrieb);
            if ($startzeitpunktRoh !== '') {
                $bestaetigungZeilen[] = 'Startzeitpunkt:      ' . startzeitpunktLabel($startzeitpunktRoh);
            }
            if ($erfahrung !== '') {
                $bestaetigungZeilen[] = '';
                $bestaetigungZeilen[] = 'Ihre Angaben zur Erfahrung:';
                $bestaetigungZeilen[] = $erfahrung;
            }
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Bis dahin finden Sie die Konditionen im Detail unter';
            $bestaetigungZeilen[] = 'https://123reinigung.at/franchise/konditionen';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Möchten Sie etwas ergänzen? Antworten Sie einfach auf diese E-Mail.';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Freundliche Grüße';
            $bestaetigungZeilen[] = 'Ihr Team von 123Reinigung';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = '123Reinigung Franchise GmbH';
            $bestaetigungZeilen[] = 'Wienersdorfer Straße 20-24/M37/12/1';
            $bestaetigungZeilen[] = '2514 Traiskirchen';
            $bestaetigungZeilen[] = 'Telefon: +43 660 7693620';
            $bestaetigungZeilen[] = 'office@123reinigung.at · 123reinigung.at';
            $bestaetigungZeilen[] = '';
            $bestaetigungZeilen[] = 'Diese E-Mail wurde automatisch erstellt.';

            $bestaetigungErfolg = mailVersenden(
                $config,
                $email,
                'Ihre Anfrage zur Franchise-Partnerschaft',
                implode("\n", $bestaetigungZeilen),
                $config['antwortAn'],
                '123Reinigung'
            );
            if (!$bestaetigungErfolg) {
                $hinweisCode = 'bestaetigung-fehler';
            }
        }
    }

    // 12 · Antwort
    antwortOk($hinweisCode);
} finally {
    // 11 · Temporäre Dateien löschen — auch im Fehlerfall
    foreach ($tempPfade as $pfad) {
        if (is_file($pfad)) {
            @unlink($pfad);
        }
    }
}
