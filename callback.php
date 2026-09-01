<?php
// 1. Spuštění session a připojení autoloaderu
session_start();
require 'vendor/autoload.php';
require_once 'config.php';  // ← přidej toto

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

$provider = new Google([
    'clientId'     => GOOGLE_CLIENT_ID,     
    'clientSecret' => GOOGLE_CLIENT_SECRET, 
    'redirectUri'  => GOOGLE_REDIRECT_URI,  
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

        // Založ uživatele v DB, pokud ještě neexistuje, jinak aktualizuj jméno/email
        upsertUser($userId, $userName, $userEmail ?: null, false);

        // Vytvoř lokální session pro uživatele
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;

        header('Location: dashboard.php');
        exit;

    } catch (IdentityProviderException $e) {
        // Zpracování chyb od Google
        echo '<h2>Chyba při komunikaci s Googlem:</h2>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<p><a href="index.html">Zkusit znovu</a></p>';
        exit;
    } catch (PDOException $e) {
        echo '<h2>Chyba při ukládání uživatele do databáze:</h2>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<p><a href="index.html">Zkusit znovu</a></p>';
        exit;
    }
}
?>