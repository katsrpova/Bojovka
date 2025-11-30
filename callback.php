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
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Chyba zabezpečení: Neplatný stav (state). Možný CSRF útok.');
} else {
    try {
        // 4. Výměna kódu za Access Token
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // 5. Získání informací o uživateli (Resource Owner)
        $user = $provider->getResourceOwner($token);
        
        // OPRAVA: Správný způsob získání dat z Google provideru
        $userData = $user->toArray();
        
        $userId = $user->getId();
        $userName = $userData['name'] ?? 'Uživatel';
        $userEmail = $userData['email'] ?? '';

        // --- ÚSPĚŠNÉ PŘIHLÁŠENÍ ---
        echo '<h2>✅ Úspěšně přihlášen! Vítej, ' . htmlspecialchars($userName) . '!</h2>';
        echo '<p>ID uživatele: <strong>' . htmlspecialchars($userId) . '</strong></p>';
        echo '<p>E-mail: <strong>' . htmlspecialchars($userEmail) . '</strong></p>';

        // ZDE ZAČÍNÁ TVÁ LOGIKA:
        // 1. Zkontroluj, zda uživatel s tímto ID/e-mailem existuje v tvé databázi.
        // 2. Pokud ne, vytvoř nový účet.
        // 3. Vytvoř lokální session pro uživatele
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        
        // 4. Přesměruj uživatele na hlavní stránku hry (odkomentuj až budeš mít stránku):
        // header('Location: game.php');
        // exit;

        // Pro testování - zobraz všechna dostupná data
        echo '<h3>Dostupná data uživatele:</h3>';
        echo '<pre>' . print_r($userData, true) . '</pre>';

    } catch (IdentityProviderException $e) {
        // Zpracování chyb od Google
        echo '<h2>Chyba při komunikaci s Googlem:</h2>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<p><a href="index.html">Zkusit znovu</a></p>';
        exit;
    }
}
?>