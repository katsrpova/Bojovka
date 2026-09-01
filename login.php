<?php
// 1. Spuštění session a připojení autoloaderu
// Session je nutná pro uložení bezpečnostního 'state'.
session_start();
require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// --- 2. KONFIGURACE ---
// TYTO HODNOTY MUSÍ BÝT STEJNÉ JAKO V GOOGLE CLOUD CONSOLE 
require 'vendor/autoload.php';
require_once 'config.php';
$provider = new Google([    
    'clientId'     => GOOGLE_CLIENT_ID,
    'clientSecret' => GOOGLE_CLIENT_SECRET,
    'redirectUri'  => GOOGLE_REDIRECT_URI,
]);

// 3. Generování URL pro přesměrování na Google
$authUrl = $provider->getAuthorizationUrl([
    'scope' => ['email', 'profile'],
    'access_type' => 'offline',
    'prompt' => 'select_account',  // Zobrazí výběr účtu
    'login_hint' => ''  // Vymaže předvyplněný email
]);
// 4. Uložení bezpečnostního 'state' do session
// Toto zabraňuje CSRF útokům. Google jej odešle zpět a my ho ověříme v callback.php.
$_SESSION['oauth2state'] = $provider->getState();

// 5. Automatické přesměrování
// Odešle prohlížeč uživatele na URL Googlu, kde proběhne přihlášení.
header('Location: ' . $authUrl);
exit; 
?>