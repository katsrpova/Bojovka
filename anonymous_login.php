<?php
session_start();

// Seznam zvířat pro náhodné jméno
$animals = [
    'Lev', 'Tygr', 'Medvěd', 'Vlk', 'Liška', 'Orel', 'Sokol', 'Jestřáb',
    'Delfín', 'Žralok', 'Panther', 'Gepard', 'Puma', 'Rys', 'Krokodýl',
    'Drak', 'Fénix', 'Jednorožec', 'Grif', 'Pegas', 'Sova', 'Krkavec',
    'Kobra', 'Python', 'Býk', 'Mustang', 'Jaguar', 'Kondor', 'Havran'
];

// Vygeneruj náhodné číslo (pro jedinečnost)
$randomNumber = rand(1000, 9999);

// Vyber náhodné zvíře
$randomAnimal = $animals[array_rand($animals)];

// Vytvoř anonymní jméno
$anonymousName = "Anonymní " . $randomAnimal;

// Vytvoř jedinečné ID (kombinace timestamp a náhodného čísla)
$anonymousId = "anon_" . time() . "_" . $randomNumber;

// Ulož do session
$_SESSION['user_id'] = $anonymousId;
$_SESSION['user_name'] = $anonymousName;
$_SESSION['user_email'] = '';
$_SESSION['is_anonymous'] = true;

// Přesměruj na dashboard
header('Location: dashboard.php');
exit;
?>