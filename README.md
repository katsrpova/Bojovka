# Bojovka

Location-based adventure game inspirovaná klasickými šifrovačkami. Uživatel naplánuje trasu, na ní umístí stanoviště (waypointy) s úkoly, a ostatní pak hru hrají — buď sólo, nebo společně ve skupině s živým žebříčkem.

## Technologie

- **Backend:** PHP 8.2 + Apache (běží v Dockeru)
- **Databáze:** MySQL 8.0
- **Mapy:** [mapy.cz API](https://api.mapy.cz/) (dlaždice + routing), Leaflet.js na frontendu
- **Přihlášení:** Google OAuth2 ([`league/oauth2-google`](https://github.com/thephpleague/oauth2-google) přes Composer) nebo anonymně
- **Ikony:** Font Awesome 6 (CDN)

## Spuštění (lokální vývoj)

Projekt běží jako Docker Compose stack (web + MySQL):

```bash
docker-compose up -d --build
```

Appka pak běží na `http://localhost/`.

### `.env` soubor

Je potřeba vytvořit `.env` v kořeni projektu (není v gitu, obsahuje tajné klíče):

```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost/callback.php
MAPY_CZ_API_KEY=...
DB_HOST=mysql
DB_PORT=3306
DB_NAME=bojovka
DB_USER=bojovka_user
DB_PASS=bojovka_pass
```

`GOOGLE_REDIRECT_URI` musí přesně odpovídat tomu, co je nastavené jako Authorized redirect URI v Google Cloud Console pro daný OAuth klient.

### Databáze

Schéma je v [`database/init.sql`](database/init.sql) — spustí se automaticky při prvním vytvoření MySQL kontejneru (prázdný `mysql_data/`). Pokud se mění schéma u už existující databáze, je potřeba příslušné `ALTER TABLE`/`CREATE TABLE` spustit ručně (např. přes phpMyAdmin nebo `docker exec`).

## Hlavní funkce

- **Přihlášení** — Google účet nebo anonymně (náhodné zvířecí jméno)
- **Procházení her** — dashboard se seznamem vytvořených her
- **Tvorba hry** — umístění waypointů na mapu (mapy.cz), ke každému úkolu otázka + odpověď (textová nebo foto) a volitelný obrázek k zadání
- **Hraní sólo** — GPS odemyká úkoly podle vzdálenosti, textové odpovědi se vyhodnocují automaticky, foto odpovědi si hráč sám potvrzuje
- **Hraní ve skupině** — hostitel vygeneruje kód/QR, ostatní se připojí a hrají společně; živý žebříček; foto odpovědi schvaluje hostitel
- **Přátelé** — adresář uživatelů, okamžité oboustranné přidání
- **Profil** — body, dokončené/vytvořené hry, historie
- **Zpětná vazba** — tlačítko v hlavičce, ukládá se do DB (odeslání e-mailem zatím TODO, viz níže)

## Struktura projektu (klíčové soubory)

| Soubor | Účel |
|---|---|
| `index.html` | Přihlašovací/onboarding stránka |
| `login.php` / `callback.php` | Google OAuth flow |
| `anonymous_login.php` | Anonymní přihlášení |
| `dashboard.php` | Přehled her |
| `create.php` / `savegame.php` | Tvorba hry a její uložení |
| `play.php` / `play.js` | Hraní hry (mapa, úkoly, žebříček) |
| `progress.php` | Ukládání postupu po textové odpovědi |
| `upload_question_image.php` | Upload obrázku k zadání úkolu |
| `submit_photo_answer.php` | Odeslání foto odpovědi hráčem |
| `review_submission.php` | Schvalování foto odpovědí hostitelem |
| `submission_status.php` | Hráč zjišťuje, zda byla jeho fotka schválena |
| `game_code.php` / `join.php` / `qr_share.php` | Kódy/QR pro skupinové hraní |
| `leaderboard.php` | Živý žebříček skupinové hry |
| `profile.php` | Profil, statistiky, přátelé |
| `friends.php` | Přidání/odebrání přítele |
| `feedback.php` | Uložení zpětné vazby |
| `config.php` | Připojení k DB, sdílené pomocné funkce (negituje se, obsahuje načtení `.env`) |
| `database/init.sql` | Databázové schéma |

`game.php` je starý nepoužívaný prototyp (není nikde odkazovaný v navigaci) — ponechán pro referenci, ale neudržovaný.

## Známé otevřené věci

- **iOS Safari neprosí o polohu** na `play.php` — v Brave/Chrome/Google app na stejném telefonu to funguje, jen Safari ne. Nejpravděpodobnější příčina: nastavení webu přímo v Safari (aA menu → Website Settings → Location).
- **Odeslání e-mailu ze zpětné vazby** zatím není zapojené — zprávy se jen ukládají do tabulky `feedback`, odeslání (PHP `mail()` nebo SMTP) se dodělá až na ostrém serveru.
- **Nasazení na `bojovka.fun.cz`** — doména zatím nebyla správně nasměrovaná na webroot (přesměrovávala na jiný web poskytovatele hostingu), potřeba doladit se správcem hostingu.
