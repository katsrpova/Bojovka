<?php
// 1. Spuštění session a připojení autoloaderu
// Session je nutná pro uložení bezpečnostního 'state'.
session_start();
require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// --- 2. KONFIGURACE ---
// TYTO HODNOTY MUSÍ BÝT STEJNÉ JAKO V GOOGLE CLOUD CONSOLE 
$provider = new Google([
    'clientId'     => '96149857284-hd9skkhktlkq4s6s7n9dd3k0dbr5bevk.apps.googleusercontent.com ',        
    'clientSecret' => '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',     
    'redirectUri'  => 'http://localhost/bojovka/callback.php', 
]);

// 3. Generování URL pro přesměrování na Google
$authUrl = $provider->getAuthorizationUrl([
    'scope' => [
        'email', // Oprávnění pro získání e-mailové adresy
        'profile' // Oprávnění pro získání jména a profilového obrázku
    ],
    // Důležité: 'offline' požádá o Refresh Token, který potřebuješ pro trvalý přístup
    'access_type' => 'offline', 
    'prompt' => 'consent' // Zajišťuje, že uživatel uvidí obrazovku souhlasu (i když už se přihlásil dříve)
]);

// 4. Uložení bezpečnostního 'state' do session
// Toto zabraňuje CSRF útokům. Google jej odešle zpět a my ho ověříme v callback.php.
$_SESSION['oauth2state'] = $provider->getState();

// 5. Automatické přesměrování
// Odešle prohlížeč uživatele na URL Googlu, kde proběhne přihlášení.
header('Location: ' . $authUrl);
exit; 
?>