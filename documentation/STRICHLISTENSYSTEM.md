# Strichlistensystem

Das Strichlistensystem (früher eigenes Repository `fsr5-fhaachen/strichlistensystem`) ist in Portals integriert. Personen buchen Getränke und Snacks entweder an einem Tablet im Kiosk-Modus oder selbst mit dem eigenen Handy. Jeder Artikel hat einen Preis. Die Kosten sehen die Personen am Kiosk und im Selbstzugriff, Zahlungen werden im Admin-Dashboard eingetragen und alles wird als CSV exportiert.

Grundlage der Integration war der Branch `pin_feature` des alten Repositorys (Laravel 11, Inertia 1). Der Code wurde nicht kopiert, sondern nach den Konventionen von Portals neu geschrieben (Laravel 13, Inertia 3, Vue 3 mit TypeScript, vorhandene Portals-Komponenten).

## Inhalt

- [Überblick](#überblick)
- [Einrichtung](#einrichtung)
- [Konfiguration](#konfiguration)
- [Abläufe](#abläufe)
- [Preise und Abrechnung](#preise-und-abrechnung)
- [Datenbank](#datenbank)
- [Sicherheit](#sicherheit)
- [Routen](#routen)
- [Dateien](#dateien)
- [Änderungen an bestehendem Code](#änderungen-an-bestehendem-code)
- [Seeder](#seeder)
- [Tests](#tests)
- [Unterschiede zum alten Strichlistensystem](#unterschiede-zum-alten-strichlistensystem)

## Überblick

| Bereich | URL | Zugriff |
|---|---|---|
| Kiosk (Tablets) | `/strichlistensystem/kiosk` | Bearer-Token im Header bei jedem Request, danach PIN der Person |
| Selbstzugriff (Handy) | `/strichlistensystem` | Normaler Portals-Login (E-Mail); vor der ersten Nutzung muss eine PIN gesetzt werden |
| Admin | `/dashboard/admin/strichlistensystem` | Berechtigung `manage strichlistensystem` (Rollen `admin`, `super admin`) und Tutor-Login |

Ist das Strichlistensystem deaktiviert, antworten alle diese Routen mit 404 und die Menüpunkte werden ausgeblendet.

## Einrichtung

1. Konfiguration in der `.env` setzen (siehe [Konfiguration](#konfiguration)), mindestens:
   ```env
   STRICHLISTENSYSTEM_ENABLED=true
   STRICHLISTENSYSTEM_KIOSK_TOKEN=<langer zufälliger Wert>
   ```
   Einen Token erzeugen, z. B. mit:
   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```
2. Migrationen ausführen:
   ```bash
   php artisan migrate
   ```
3. Auf bestehenden Datenbanken den `RoleSeeder` einmal erneut ausführen, damit `admin` und `super admin` die neue Berechtigung `manage strichlistensystem` erhalten (der Seeder ist idempotent):
   ```bash
   php artisan db:seed --class=RoleSeeder
   ```
4. Optional die Standard-Artikel anlegen (ebenfalls idempotent, legt nur fehlende Artikel an):
   ```bash
   php artisan db:seed --class=ArticleSeeder
   ```
5. Kiosk-Browser auf den Tablets so einrichten, dass er bei **allen** Requests (Seitenaufrufe, Hintergrund-Requests von Inertia, POST-Requests und Bilder) den Header sendet:
   ```
   Authorization: Bearer <STRICHLISTENSYSTEM_KIOSK_TOKEN>
   ```
   Alternativ kann ein vorgeschalteter Reverse-Proxy den Header setzen. Fehlt der Header oder ist er falsch, antwortet der Server mit 401.

Bei gecachter Konfiguration nach Änderungen an der `.env` `php artisan config:clear` bzw. `php artisan config:cache` ausführen.

## Konfiguration

Alle Einstellungen liegen in [`config/strichlistensystem.php`](../config/strichlistensystem.php) und werden über die `.env` gesetzt. Die Vorlage steht in [`.env.example`](../.env.example).

| Variable | Config-Key | Standard | Bedeutung |
|---|---|---|---|
| `STRICHLISTENSYSTEM_ENABLED` | `strichlistensystem.enabled` | `false` | Schaltet das gesamte Strichlistensystem (Kiosk, Selbstzugriff, Admin, Menüpunkte) ein oder aus. |
| `STRICHLISTENSYSTEM_KIOSK_TOKEN` | `strichlistensystem.kiosk_token` | leer | Bearer-Token für den Kiosk. Ist er leer, ist der Kiosk nicht erreichbar. |
| `STRICHLISTENSYSTEM_KIOSK_SESSION_TIMEOUT` | `strichlistensystem.kiosk_session_timeout` | `120` | Sekunden ohne Aktivität, nach denen eine am Kiosk angemeldete Person die PIN erneut eingeben muss. |
| `STRICHLISTENSYSTEM_PIN_MAX_ATTEMPTS` | `strichlistensystem.pin_max_attempts` | `5` | Maximale falsche PIN-Eingaben pro Person und Minute am Kiosk. |
| `STRICHLISTENSYSTEM_SELF_SERVICE_LIMIT_WINDOW` | `strichlistensystem.self_service_limit_window` | `60` | Zeitfenster in Sekunden, für das die maximale Anzahl eines Artikels im Selbstzugriff gilt. |
| `STRICHLISTENSYSTEM_CANCEL_WINDOW` | `strichlistensystem.cancel_window` | `60` | Sekunden, in denen eine Buchung storniert werden kann. Der Server akzeptiert zusätzlich 10 Sekunden Toleranz (`ArticleActionLog::CANCEL_GRACE_SECONDS`). |
| – | `strichlistensystem.icons` | Liste | Font-Awesome-Icons, die für Artikel ausgewählt werden können. Neue Icons müssen zusätzlich in `resources/js/app.js` registriert werden. |

Verfügbare Icons: `beer-mug-empty`, `lemon`, `wine-bottle`, `wine-glass`, `glass-water`, `bottle-water`, `faucet`, `martini-glass-citrus`, `whiskey-glass`, `champagne-glasses`, `mug-hot`, `mug-saucer`, `cookie`, `pizza-slice`, `hotdog`, `ice-cream`.

Unabhängig vom Strichlistensystem ist der Schlüssel für die öffentliche API `PUBLIC_API_SECRET` (ohne `APP_`-Präfix). Der alte Name `APP_PUBLIC_API_SECRET` wird nicht mehr gelesen.

## Abläufe

### Kiosk

1. Die Übersicht `/strichlistensystem/kiosk` zeigt alle nicht deaktivierten User mit Bild, Name und Studiengang. Gefiltert werden kann nach Studiengang, Tutor*innen (Rolle `tutor`), Special (Rolle `special`) und per Suche.
2. Beim Antippen einer Person öffnet sich ein PIN-Dialog. Ohne Eingabe schließt er sich nach 30 Sekunden.
   - Hat die Person noch keine PIN, wird ein Hinweis auf den Selbstzugriff angezeigt. Am Kiosk kann keine PIN gesetzt werden.
3. Nach korrekter PIN wird die Person in der Session als angemeldet gespeichert (`strichlistensystem.kiosk` mit `user_id`, `unlocked_at`, `last_activity`). Die Personenseite zeigt die Artikel und die letzten 20 Buchungen.
4. Die Anmeldung endet durch:
   - „Fertig & abmelden“,
   - den Countdown auf der Seite (40 Sekunden, nach jeder Aktion 30 Sekunden),
   - Inaktivität länger als `kiosk_session_timeout` (wird serverseitig geprüft),
   - den Aufruf der Übersicht.

### Selbstzugriff

1. Die Person meldet sich mit der E-Mail bei Portals an. Wer vorher `/strichlistensystem` aufgerufen hat, landet nach dem Login wieder dort.
2. Ist noch keine PIN gesetzt, muss zuerst eine PIN festgelegt werden (genau 4 Ziffern, doppelte Eingabe). Erst danach kann gebucht werden.
3. Auf der Seite können Artikel gebucht, Buchungen storniert und die PIN geändert werden.
4. Der Menüpunkt „Strichliste“ erscheint für alle eingeloggten User in der Navigation.

### Buchungen und Limits

- Gebucht werden kann nur, wenn der User nicht deaktiviert ist (`users.is_disabled`) und eine PIN hat.
- Pro Buchung wird je Stück ein Eintrag in `article_action_logs` angelegt. Jeder Eintrag speichert den Preis des Artikels zum Zeitpunkt der Buchung (siehe [Preise und Abrechnung](#preise-und-abrechnung)).
- Das Feld **Maximale Anzahl** eines Artikels (`articles.max_order_amount`) wirkt so:
  - **Kiosk:** maximale Anzahl **pro Anmeldung**. Gezählt werden alle Buchungen des Artikels seit der PIN-Eingabe (`unlocked_at`).
  - **Selbstzugriff:** maximale Anzahl **pro Zeitfenster** (`self_service_limit_window`, gleitend). Die Seite zeigt einen Countdown und lädt die Artikel nach Ablauf automatisch neu.
  - Stornierte Buchungen zählen nicht mit.
  - Der Mengenregler geht nur bis zur noch verfügbaren Menge. Ist das Limit erreicht, zeigt die Karte einen Hinweis und „Buchen“ ist gesperrt.
  - Der Server prüft das Limit selbst in einer Transaktion mit Sperre auf den User (`User::buyArticle()`). Doppelklicks oder direkte Requests können das Limit nicht überschreiten.
- Nach jeder Buchung ist der „Buchen“-Button der Karte für 3 Sekunden gesperrt.
- Stornieren ist innerhalb von `cancel_window` Sekunden möglich, nur für eigene und noch nicht stornierte Buchungen. Storniert wird per Soft Delete (`deleted_at`).

### Admin

Unter `/dashboard/admin/strichlistensystem` (Menüpunkt „Strichliste“ im roten Admin-Bereich):

- **Artikel anlegen und bearbeiten:** Name (eindeutig, max. 50 Zeichen), Icon, Reihenfolge, maximale Anzahl (1–100), Preis (0–1000 €, Eingabe in Euro mit Komma oder Punkt), aktiv.
- **Aktivieren/Deaktivieren:** Deaktivierte Artikel werden am Kiosk und im Selbstzugriff nicht mehr angezeigt und können nicht mehr gebucht werden. Bisherige Buchungen bleiben erhalten und erscheinen weiterhin im Export.
- **Löschen:** nur für Artikel ohne Buchungen (auch stornierte zählen). Artikel mit Buchungen sollten deaktiviert werden.
- **Abrechnung** (`/dashboard/admin/strichlistensystem/billing`, Link „Zur Abrechnung“): Tabelle aller Personen mit Anzahl der Buchungen, Gesamt, Bezahlt und Offen (Suche, Sortierung, Live-Update). Über „Zahlungen“ öffnet sich ein Dialog, in dem ein bezahlter Betrag eingetragen (vorausgefüllt mit dem offenen Betrag, Teilzahlungen möglich) und die letzte Zahlung rückgängig gemacht werden kann.
- **CSV-Export:** `strichlisten_export.csv` mit den Spalten `Nachname;Vorname;Anz_<Artikel>…;Betrag_<Artikel>…;Gesamt;Bezahlt;Offen`. Enthält alle User und alle Artikel (auch deaktivierte), gezählt werden nur nicht stornierte Buchungen. Beträge werden mit dem Preis zum Zeitpunkt der Buchung berechnet und als Dezimalzahl mit Komma ohne Währungszeichen ausgegeben (z. B. `1,50`), damit ein deutsches Excel sie als Zahl erkennt. Kodierung UTF-8 mit BOM, Trennzeichen `;`.
- **PIN im User-Bearbeiten-Dialog** (User-Verwaltung, Berechtigung `manage users`): PIN anzeigen, setzen oder durch ein leeres Feld entfernen.

## Preise und Abrechnung

### Preise

- Preise werden überall als ganze Cent gespeichert und übertragen (`articles.price`, `article_action_logs.price`, `strichlistensystem_payments.amount`). Das Frontend rechnet nur für die Anzeige und Eingabe in Euro um (`resources/js/composables/usePrice.ts`), das Backend formatiert mit `App\Helpers\Price`.
- **Preis pro Buchung (Snapshot):** Beim Buchen wird der aktuelle Preis des Artikels in die Buchung kopiert. Spätere Preisänderungen ändern bestehende Buchungen nicht. Summen, Abrechnung und Export rechnen immer mit dem Preis der Buchung.
- **Buchungen ohne Preis:** Bestehende Artikel haben nach der Migration noch keinen Preis (`NULL`) und können weiter gebucht werden. Diese Buchungen haben ebenfalls keinen Preis. Sobald der Admin den Preis eines Artikels zum ersten Mal speichert, erhalten alle Buchungen dieses Artikels ohne Preis (auch stornierte) einmalig diesen Preis (`Article::applyPriceToBookingsWithoutPrice()`). Danach ist ein Preis Pflicht und kann nicht mehr entfernt werden, es entstehen also keine neuen Buchungen ohne Preis. Bis dahin zählen sie in keiner Summe mit. Darauf weisen die Kosten-Übersicht, das Zahlungs-Dialogfenster und die Abrechnungsseite hin.
- **Preisänderung während der Buchung:** Das Frontend sendet beim Buchen den angezeigten Preis mit (`price`). Weicht er vom aktuellen Preis ab, wird die Buchung abgelehnt („Der Preis von … hat sich geändert“). Die Seite lädt dann den neuen Preis. `User::buyArticle()` sperrt dafür den Artikel (`lockForUpdate`), und das Speichern eines Artikels sperrt ihn ebenfalls. Eine parallele Preisänderung liegt so immer vollständig vor oder nach einer Buchung.

### Anzeige

- **Artikelkarte:** Preis pro Stück und Preis der gewählten Menge (z. B. `2 × 1,50 € = 3,00 €`). Ohne Preis steht dort „Preis noch nicht festgelegt“.
- **Kosten-Übersicht** (`BalanceCard`): „Noch zu bezahlen“ (bzw. „Guthaben“, falls mehr bezahlt als gebucht wurde), „Insgesamt ausgegeben“ und „Bereits bezahlt“. Am Kiosk kommt „Diese Anmeldung“ dazu, die Summe aller Buchungen seit der PIN-Eingabe.
- **Letzte Buchungen:** Preis jeder Buchung. Bei stornierten Buchungen ist er durchgestrichen.

### Zahlungen

- Gesamt = Summe der Preise aller nicht stornierten Buchungen. Bezahlt = Summe aller nicht rückgängig gemachten Zahlungen. Offen = Gesamt − Bezahlt (`User::strichlistensystemBalance()`).
- Eine Zahlung muss zwischen 0,01 € und dem offenen Betrag liegen. Der Server prüft das in einer Transaktion mit Sperre auf den User (`User::payStrichlistensystem()`), sodass parallele Zahlungen den offenen Betrag nicht überschreiten können.
- Gespeichert wird, wer die Zahlung eingetragen hat (`created_by_id`). Zahlungen werden auditiert.
- Nur die jeweils letzte Zahlung einer Person kann rückgängig gemacht werden (Soft Delete). Danach ist die vorherige Zahlung die letzte. Rückgängig gemachte Zahlungen bleiben im Dialog durchgestrichen sichtbar.
- Wird nach einer Zahlung noch eine Buchung storniert, kann der offene Betrag negativ werden. Das wird als Guthaben angezeigt, im Export als negativer Wert bei `Offen`.

## Datenbank

### Migrationen

| Datei | Inhalt |
|---|---|
| [`2026_10_11_181156_change_pin_to_string_on_users_table.php`](../database/migrations/2026_10_11_181156_change_pin_to_string_on_users_table.php) | Wandelt `users.pin` von `integer` in `string(4)` um. Bestehende Werte werden mit führenden Nullen aufgefüllt (`123` → `0123`), ungültige Werte (negativ oder mehr als 4 Stellen) werden `NULL`. Funktioniert über eine temporäre Spalte, damit es auf MySQL/MariaDB, PostgreSQL und SQLite gleich läuft. Rollback wandelt zurück in `integer`. |
| [`2026_10_11_181203_create_articles_table.php`](../database/migrations/2026_10_11_181203_create_articles_table.php) | Tabelle `articles` |
| [`2026_10_11_181206_create_article_action_logs_table.php`](../database/migrations/2026_10_11_181206_create_article_action_logs_table.php) | Tabelle `article_action_logs` |
| [`2026_10_11_185307_add_is_active_to_articles_table.php`](../database/migrations/2026_10_11_185307_add_is_active_to_articles_table.php) | Spalte `articles.is_active` |
| [`2026_10_21_105252_add_price_to_articles_table.php`](../database/migrations/2026_10_21_105252_add_price_to_articles_table.php) | Spalte `articles.price` (Cent, `NULL` = noch kein Preis) |
| [`2026_10_21_105253_add_price_to_article_action_logs_table.php`](../database/migrations/2026_10_21_105253_add_price_to_article_action_logs_table.php) | Spalte `article_action_logs.price` (Preis zum Zeitpunkt der Buchung). Bestehende Buchungen bekommen `NULL` |
| [`2026_10_21_105254_create_strichlistensystem_payments_table.php`](../database/migrations/2026_10_21_105254_create_strichlistensystem_payments_table.php) | Tabelle `strichlistensystem_payments` |

### Tabellen

**`users`** (bestehende Tabelle, alle personenbezogenen Daten bleiben hier)

| Spalte | Verwendung im Strichlistensystem |
|---|---|
| `pin` | `string(4)`, nullable. 4-stellige PIN im Klartext. `NULL` bedeutet, dass noch keine PIN gesetzt ist. |
| `is_disabled` | Deaktivierte User erscheinen nicht im Kiosk und können nicht buchen. |
| `avatar` | Pfad des Bildes im S3-Bucket von Portals. |
| `course_id` | Studiengang für Anzeige und Filter. |
| Rollen (`spatie/laravel-permission`) | `tutor` und `special` für die Filter am Kiosk. Ersetzen die früheren Spalten `is_tutor` und `is_special`. |

**`articles`**

| Spalte | Typ | Bedeutung |
|---|---|---|
| `name` | `string(50)` | Name des Artikels |
| `icon` | `string(50)` | Font-Awesome-Icon (aus `strichlistensystem.icons`) |
| `show_order` | `integer` | Sortierung (aufsteigend) |
| `max_order_amount` | `integer` | Maximale Anzahl pro Kiosk-Anmeldung bzw. pro Zeitfenster im Selbstzugriff |
| `price` | `unsigned integer`, nullable | Preis in Cent. `NULL` bedeutet, dass noch kein Preis festgelegt ist |
| `is_active` | `boolean` | Deaktivierte Artikel sind nicht sichtbar und nicht buchbar |

**`article_action_logs`** (eine Zeile pro gebuchtem Stück)

| Spalte | Typ | Bedeutung |
|---|---|---|
| `user_id` | FK `users` | Person; beim Löschen des Users werden die Buchungen mitgelöscht (`cascade`) |
| `article_id` | FK `articles` | Artikel; Löschen eines gebuchten Artikels wird verhindert (`restrict`) |
| `price` | `unsigned integer`, nullable | Preis in Cent zum Zeitpunkt der Buchung. `NULL` bei Buchungen, bevor der Artikel einen Preis hatte |
| `ip` | `string(45)` | IP-Adresse der Buchung (wird nicht an das Frontend ausgeliefert) |
| `created_at` | `timestamp` | Buchungszeitpunkt, Grundlage für Limits und Stornofrist |
| `deleted_at` | `timestamp` | Storno (Soft Delete) |

Index auf `(user_id, created_at)`.

**`strichlistensystem_payments`** (Zahlungen)

| Spalte | Typ | Bedeutung |
|---|---|---|
| `user_id` | FK `users` | Person, die bezahlt hat; beim Löschen des Users werden die Zahlungen mitgelöscht (`cascade`) |
| `created_by_id` | FK `users`, nullable | Admin, der die Zahlung eingetragen hat (`NULL`, wenn der Admin gelöscht wurde) |
| `amount` | `unsigned integer` | Betrag in Cent |
| `deleted_at` | `timestamp` | Zahlung rückgängig gemacht (Soft Delete) |

## Sicherheit

- **Kiosk:** Middleware `StrichlistensystemKiosk` vergleicht den Bearer-Token zeitkonstant (`hash_equals`) mit `kiosk_token`. Ohne konfigurierten Token ist der Kiosk gesperrt.
- **Bilder:** Werden nur am Kiosk über `/strichlistensystem/kiosk/user/{user}/avatar` ausgeliefert. Die Route steht hinter dem Bearer-Token und streamt das Bild aus dem Portals-S3 (`Cache-Control: private, max-age=3600`). Es werden keine signierten S3-URLs an den Kiosk ausgegeben.
- **PIN:**
  - Liegt im Klartext in `users.pin` (bewusste Entscheidung). Erlaubt sind genau 4 Ziffern.
  - Ist im `User`-Model `$hidden`. Sie wird nicht über die geteilten Inertia-Props, die Kiosk-Personenliste oder die öffentliche API `/api/v1/users` ausgeliefert.
  - Nur `/api/users` für die User-Verwaltung (Berechtigung `manage users`) macht sie sichtbar.
  - Ist vom Audit-Log ausgenommen (`$auditExclude`).
- **PIN-Versuche:** Rate-Limit pro Person (`pin_max_attempts` pro Minute), unabhängig von der IP, da alle Tablets dieselbe IP haben können.
- **Kiosk-Anmeldung:** Gilt nur für die angemeldete Person. Buchungen oder Stornos für andere Personen werden abgelehnt.
- **Selbstzugriff:** Der Portals-Login hat kein Passwort. Die PIN schützt deshalb vor allem die Buchungen am Kiosk.

## Routen

Alle Routen liegen in [`routes/web.php`](../routes/web.php) und stehen hinter der Middleware `StrichlistensystemEnabled`.

| Methode | URI | Name |
|---|---|---|
| GET | `/strichlistensystem/kiosk` | `strichlistensystem.kiosk.index` |
| POST | `/strichlistensystem/kiosk/logout` | `strichlistensystem.kiosk.logout` |
| GET | `/strichlistensystem/kiosk/user/{user}` | `strichlistensystem.kiosk.show` |
| GET | `/strichlistensystem/kiosk/user/{user}/avatar` | `strichlistensystem.kiosk.avatar` |
| POST | `/strichlistensystem/kiosk/user/{user}/pin` | `strichlistensystem.kiosk.verifyPin` |
| POST | `/strichlistensystem/kiosk/user/{user}/buy/{article}` | `strichlistensystem.kiosk.buy` |
| POST | `/strichlistensystem/kiosk/user/{user}/cancel/{articleActionLog}` | `strichlistensystem.kiosk.cancel` |
| GET | `/strichlistensystem` | `strichlistensystem.index` |
| POST | `/strichlistensystem/pin` | `strichlistensystem.updatePin` |
| POST | `/strichlistensystem/buy/{article}` | `strichlistensystem.buy` |
| POST | `/strichlistensystem/cancel/{articleActionLog}` | `strichlistensystem.cancel` |
| GET | `/dashboard/admin/strichlistensystem` | `dashboard.admin.strichlistensystem.index` |
| GET | `/dashboard/admin/strichlistensystem/export` | `dashboard.admin.strichlistensystem.export` |
| POST | `/dashboard/admin/strichlistensystem/article` | `dashboard.admin.strichlistensystem.storeArticle` |
| POST | `/dashboard/admin/strichlistensystem/article/{article}` | `dashboard.admin.strichlistensystem.updateArticle` |
| POST | `/dashboard/admin/strichlistensystem/article/{article}/toggle-active` | `dashboard.admin.strichlistensystem.toggleArticleActive` |
| DELETE | `/dashboard/admin/strichlistensystem/article/{article}` | `dashboard.admin.strichlistensystem.deleteArticle` |
| GET | `/dashboard/admin/strichlistensystem/billing` | `dashboard.admin.strichlistensystem.billing` |
| GET | `/dashboard/admin/strichlistensystem/billing/user/{user}` | `dashboard.admin.strichlistensystem.billingUser` (JSON für den Zahlungs-Dialog) |
| POST | `/dashboard/admin/strichlistensystem/billing/user/{user}/payment` | `dashboard.admin.strichlistensystem.storePayment` |
| DELETE | `/dashboard/admin/strichlistensystem/billing/payment/{payment}` | `dashboard.admin.strichlistensystem.deletePayment` |

Middleware-Kette:
- Kiosk: `StrichlistensystemEnabled` → `StrichlistensystemKiosk`
- Selbstzugriff: `StrichlistensystemEnabled` → `Authenticate`
- Admin: `Authenticate` → `IsLoggedInAdmin` → `StrichlistensystemEnabled` → `can:manage strichlistensystem`

## Dateien

### Neu

| Datei | Zweck |
|---|---|
| `config/strichlistensystem.php` | Konfiguration |
| `app/Http/Middleware/StrichlistensystemEnabled.php` | 404, wenn deaktiviert |
| `app/Http/Middleware/StrichlistensystemKiosk.php` | Prüfung des Bearer-Tokens |
| `app/Http/Controllers/StrichlistensystemKioskController.php` | Kiosk: Übersicht, PIN, Buchen, Storno, Abmelden, Bilder |
| `app/Http/Controllers/StrichlistensystemController.php` | Selbstzugriff: PIN setzen/ändern, Buchen, Storno |
| `app/Http/Controllers/DashboardAdminStrichlistensystemController.php` | Admin: Artikel, Aktivieren/Deaktivieren, Export |
| `app/Models/Article.php` | Artikel (inkl. Scope `active()`, auditiert) |
| `app/Models/ArticleActionLog.php` | Buchung (Soft Delete, `cancel_until`, `isCancellable()`) |
| `database/factories/ArticleFactory.php`, `ArticleActionLogFactory.php` | Factories (State `inactive()`) |
| `database/seeders/ArticleSeeder.php` | Standard-Artikel |
| `resources/js/pages/Strichlistensystem/Kiosk/Index.vue` | Kiosk-Übersicht mit PIN-Dialog |
| `resources/js/pages/Strichlistensystem/Kiosk/Show.vue` | Kiosk-Personenseite |
| `resources/js/pages/Strichlistensystem/Index.vue` | Selbstzugriff (nutzt `DashboardLayout`) |
| `resources/js/pages/Dashboard/Admin/Strichlistensystem/Index.vue` | Admin-Seite (Artikel) |
| `resources/js/pages/Dashboard/Admin/Strichlistensystem/Billing.vue` | Admin-Seite Abrechnung |
| `resources/js/components/strichlistensystem/BalanceCard.vue` | Kosten-Übersicht am Kiosk und im Selbstzugriff |
| `resources/js/components/strichlistensystem/PaymentModal.vue` | Dialog zum Eintragen und Rückgängigmachen von Zahlungen |
| `resources/js/composables/usePrice.ts` | Formatierung und Eingabe von Preisen (Cent ↔ Euro) |
| `app/Models/StrichlistensystemPayment.php` | Zahlung (Soft Delete, auditiert) |
| `app/Helpers/Price.php` | Formatierung von Cent-Beträgen (`1,50` bzw. `1,50 €`) |
| `app/Helpers/Table/StrichlistensystemBillingTable.php` | Tabelle der Abrechnung (Summen per Unterabfrage, Live-Update auch bei neuen Buchungen und Zahlungen) |
| `resources/js/components/strichlistensystem/ArticleCard.vue` | Artikelkarte mit Mengenregler, Limit-Hinweis und Countdown |
| `resources/js/components/strichlistensystem/ActionLogList.vue` | Liste der letzten Buchungen mit Storno |
| `resources/js/components/strichlistensystem/PersonCard.vue` | Personenkarte am Kiosk |
| `resources/js/components/strichlistensystem/PinPad.vue` | Ziffernblock für die PIN-Eingabe |
| `resources/js/components/strichlistensystem/PinForm.vue` | Formular zum Setzen/Ändern der PIN |
| `resources/js/types/article.d.ts`, `article-action-log.d.ts`, `strichlistensystem-person.d.ts` | TypeScript-Typen |
| `tests/Feature/StrichlistensystemKioskTest.php`, `StrichlistensystemSelfServiceTest.php`, `DashboardAdminStrichlistensystemTest.php`, `DashboardAdminStrichlistensystemBillingTest.php` | Feature-Tests |
| `phpunit.xml` | Lokale PHPUnit-Konfiguration (SQLite in-memory). Steht in `.gitignore` und wird nicht eingecheckt, siehe [Tests](#tests). |

Die Komponenten werden über `unplugin-vue-components` automatisch als `StrichlistensystemArticleCard` usw. registriert. Alle Seiten nutzen die vorhandenen Portals-Komponenten (`CardBase`, `AppButton`, `UiH2`, `UiMessage`, `AppTable`, FormKit, Headless UI).

## Änderungen an bestehendem Code

| Datei | Änderung |
|---|---|
| `app/Models/User.php` | Relation `articleActionLogs()`; Methoden `hasPin()`, `isPinValid()`, `canUseStrichlistensystem()`, `remainingArticleAmount()`, `bookableArticles()`, `buyArticle()`, `cancelArticleActionLog()`, `latestArticleActionLogs()`, `articleActionLogsTotal()`, `strichlistensystemBalance()`, `payStrichlistensystem()`, `latestStrichlistensystemPayment()`, `undoStrichlistensystemPayment()`; Relation `strichlistensystemPayments()`; `pin` in `$hidden` und `$auditExclude` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Geteilter Prop `strichlistensystemEnabled` |
| `app/Http/Controllers/AppController.php` | Nach dem Login Weiterleitung zurück zu `/strichlistensystem`, falls die Person von dort kam. Andere gespeicherte Ziele (`url.intended`) bleiben unberührt, damit der Tutor-Login weiter funktioniert. |
| `app/Http/Controllers/DashboardAdminController.php` | `editUser` validiert und speichert `pin` (leer = entfernen) |
| `app/Http/Controllers/Api/ApiController.php` | `/api/users` liefert `pin` für die User-Verwaltung aus |
| `routes/web.php` | Neue Routen (vor der Catch-all-Route) |
| `resources/js/app.js` | Icons für Artikel und UI registriert |
| `resources/js/layouts/DashboardLayout.vue` | Menüpunkt „Strichliste“ |
| `resources/js/components/app/Navbar.vue` | Admin-Menüpunkt „Strichliste“ (Desktop und Mobil) |
| `resources/js/components/user/EditModal.vue` | PIN-Feld (nur sichtbar, wenn das Strichlistensystem aktiv ist) |
| `resources/js/types/user.d.ts` | `pin` als `string \| null` |
| `database/factories/UserFactory.php` | Zufällige 4-stellige PIN, State `withoutPin()` |
| `database/seeders/RoleSeeder.php` | Berechtigung `manage strichlistensystem` für `admin` und `super admin` |
| `database/seeders/DatabaseSeeder.php` | `ArticleSeeder` in den Basis-Seedern |
| `database/seeders/UserDemoSeeder.php` | Demo-Accounts mit PIN `1234` |
| `database/seeders/TutorSeeder.php`, `StudentSeeder.php` | PIN aus CSV nur übernehmen, wenn sie aus genau 4 Ziffern besteht; fehlende Spalte führt nicht mehr zu einem Fehler |
| `database/seeders/students.csv.example` | Spalte `pin` ergänzt |
| `.env.example` | Variablen des Strichlistensystems |

## Seeder

- `ArticleSeeder` legt an: Bier (8), Radler (8), Cola (8), Spezi (8), Wasser (2), Bacardi (1). Die Zahl in Klammern ist die maximale Anzahl. Existierende Artikel (gleicher Name) werden übersprungen. Die Artikel haben noch keinen Preis, er muss im Admin-Dashboard festgelegt werden.
- `UserDemoSeeder` (Event-Typ `demo`): Die benannten Demo-Accounts (`superadmin@example.com`, `admin@example.com`, `tutor@example.com` usw.) haben die PIN `1234`. Die 800 Demo-Studierenden bekommen zufällige PINs.
- `TutorSeeder` liest die PIN aus Spalte 7 von `tutors.csv`, `StudentSeeder` aus Spalte 5 von `students.csv`.

## Tests

```bash
php artisan test --compact tests/Feature/StrichlistensystemKioskTest.php tests/Feature/StrichlistensystemSelfServiceTest.php tests/Feature/DashboardAdminStrichlistensystemTest.php tests/Feature/DashboardAdminStrichlistensystemBillingTest.php
```

Die Tests brauchen eine `phpunit.xml`, die im Repository per `.gitignore` ausgeschlossen ist. Lokal reicht die Standard-Konfiguration aus dem Laravel-Skeleton mit `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `SESSION_DRIVER=array` und `CACHE_STORE=array`.

Abgedeckt sind unter anderem: Bearer-Token, Ein/Aus-Schalter, PIN-Prüfung inkl. Rate-Limit, Kiosk-Timeout, Buchen und Stornieren, Limits pro Anmeldung und Zeitfenster, deaktivierte Artikel und User, Bildauslieferung, Admin-Berechtigungen, Artikelverwaltung, Preise (Snapshot pro Buchung, einmalige Übernahme für Buchungen ohne Preis, Ablehnung bei geändertem Preis), Abrechnung und Zahlungen (Teilzahlung, Obergrenze, Rückgängig nur für die letzte Zahlung, Live-Update), CSV-Export mit Beträgen und dass die PIN nicht an das Frontend oder die öffentliche API gelangt.

Hinweis: Der bereits vorhandene `tests/Feature/ExampleTest.php` schlägt fehl, weil er keine Datenbank migriert. Das ist unabhängig vom Strichlistensystem.

## Unterschiede zum alten Strichlistensystem

| Alt | Neu |
|---|---|
| Eigene Tabelle `persons` mit Import aus Portals (`/importusers/{password}`) | Direkt die `users`-Tabelle von Portals; kein Import mehr |
| `is_tutor`, `is_special`, `course`-Enum, `img` | Rollen `tutor`/`special`, `course_id`, `avatar` |
| Zugriffsschutz über eigene VPN-Installation (`APP_IS_VPN`) | Bearer-Token für den Kiosk |
| QR-Code mit Auth-Link (`auth_token`) zum Weiterbuchen am Handy | Selbstzugriff über den Portals-Login |
| PIN als `char(4)` mit `pin_change_required` (Branch `pin_feature`) | `pin` als `string(4)`; eine fehlende PIN muss im Selbstzugriff gesetzt werden |
| CSV-Export über `/exportcsv/{password}`, ISO-8859-1 | Admin-Dashboard mit Berechtigung, UTF-8 mit BOM, gleiche Spalten plus Beträge, Gesamt, Bezahlt und Offen |
| Keine Preise | Preis pro Artikel, Preis-Snapshot pro Buchung, Zahlungen pro Person |
| Telegram-Benachrichtigungen (bereits auskommentiert) | Entfernt |
| Artikel nur über Seeder pflegbar | Artikelverwaltung im Admin-Dashboard inkl. Aktivieren/Deaktivieren |
| `max_order_amount` = Maximum pro einzelner Buchung | Maximum pro Kiosk-Anmeldung bzw. pro Zeitfenster im Selbstzugriff |
| Eigenes Design (Tailwind, Options API) | Portals-Design und -Komponenten (Vue 3 `<script setup>` mit TypeScript, Inertia 3) |
