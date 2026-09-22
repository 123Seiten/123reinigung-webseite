<?php
/**
 * Vorlage. Zum Verwenden kopieren, Passwort eintragen, als
 * public/mail-config.php speichern und per FTP nach /123reinigung.at/ laden.
 * public/mail-config.php gehört NICHT ins Repository.
 */
return [
    'host'     => 'w0214e71.kasserver.com',
    'port'     => 465,
    'secure'   => 'ssl',
    'username' => 'website@123reinigung.at',
    'password' => 'HIER_DAS_PASSWORT_EINTRAGEN',
    'from'     => 'website@123reinigung.at',
    'fromName' => '123Reinigung',

    'empfaenger' => [
        'anfrage'   => 'anfrage@123reinigung.at',
        'franchise' => 'franchise@123reinigung.at',
    ],
    'antwortAn' => 'office@123reinigung.at',
];
