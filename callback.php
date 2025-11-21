<?php
// 1. Spuštění session a připojení autoloaderu
session_start();
require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

// --- 2. KONFIGURACE ---
// Musí být stejné jako v login.php
$provider = new Google([
    'clientId'     => '96149857284-hd9skkhktlkq4s6s7n9dd3k0dbr5bevk.apps.googleusercontent.com',      
    'clientSecret' => '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',    
    'redirectUri'  => 'http://localhost/bojovka/callback.php', 
]);

// 3. Kontrola autorizačního kódu a stavu (State)

// Pokud chybí 'code', uživatel přihlášení zrušil nebo došlo k chybě.
if (!isset($_GET['code'])) {
    echo '<h2>Přihlášení Googlem bylo zrušeno nebo nastala chyba.</h2>';
    echo '<p><a href="index.html">Zkusit znovu</a></p>';
    exit;
}

// Bezpečnostní kontrola 'state' (CSRF ochrana)
// Ověřujeme, že token, který jsme poslali Googlu, se vrátil beze změny.
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Chyba zabezpečení: Neplatný stav (state). Možný CSRF útok.');
} else {
    try {
        // 4. Výměna kódu za Access Token
        // Knihovna odešle kód a tvůj Client Secret zpět Googlu, aby získala tokeny.
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // 5. Získání informací o uživateli (Resource Owner)
        $user = $provider->getResourceOwner($token);

        // --- ÚSPĚŠNÉ PŘIHLÁŠENÍ ---
        echo '<h2>✅ Úspěšně přihlášen! Vítej, ' . $user->getName() . '!</h2>';
        echo '<p>ID uživatele: <strong>' . $user->getId() . '</strong></p>';
        echo '<p>E-mail: <strong>' . $user->getEmail() . '</strong></p>';

        // ZDE ZAČÍNÁ TVÁ LOGIKA:
        // 1. Zkontroluj, zda uživatel s tímto ID/e-mailem existuje v tvé databázi.
        // 2. Pokud ne, vytvoř nový účet.
        // 3. Vytvoř lokální session pro uživatele (např. $_SESSION['user_id'] = $user->getId();)
        // 4. Přesměruj uživatele na hlavní stránku hry.
        
        // --- INFORMACE O TOKENS (POUZE PRO DEBUG, NIKDY NEZOBRAZUJ VEŘEJNĚ!) ---
        echo '<h3>Tokeny (Bezpečnostní informace – neukazovat uživateli):</h3>';
        echo '<p>Access Token: ' . $token->getToken() . '</p>';
        echo '<p>Refresh Token: ' . $token->getRefreshToken() . '</p>';
        echo '<p>Token vyprší: ' . date('Y-m-d H:i:s', $token->getExpires()) . '</p>';

        // Příklad přesměrování na hlavní stránku po zpracování:
        // header('Location: /hlavni-stranka-hry.php');
        // exit;

    } catch (IdentityProviderException $e) {
        // Zpracování chyb od Google (např. neplatný kód)
        echo '<h2>Chyba při komunikaci s Googlem:</h2>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<p><a href="index.html">Zkusit znovu</a></p>';
        exit;
    }
}
?>