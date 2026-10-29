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
| Kiosk (Tablets) | `/strichlistensystem/kiosk` | Freigeschaltetes Kiosk-Gerät (Cookie), danach PIN der Person |
| Selbstzugriff (Handy) | `/strichlistensystem` | Normaler Portals-Login (E-Mail); vor der ersten Nutzung muss eine PIN gesetzt werden |
| Admin | `/dashboard/admin/strichlistensystem` | Berechtigung `manage strichlistensystem` (Rollen `admin`, `super admin`) und Tutor-Login |

Das Strichlistensystem ist ein Modul wie Registrierung, Zufallsgenerator oder Countdown (`modules`-Eintrag `strichlistensystem`). Ist das Modul inaktiv, leiten alle diese Routen auf die Startseite um (Middleware `ActiveModule`) und die Menüpunkte werden ausgeblendet.

## Einrichtung

1. Migrationen ausführen:
   ```bash
   php artisan migrate
   ```
   Die Migration `add_strichlistensystem_module_and_permission` legt den Modul-Eintrag (inaktiv) und die Berechtigung `manage strichlistensystem` an und gibt sie den Rollen `admin` und `super admin`. Erneutes Seeden ist dafür nicht nötig.
2. Das Modul aktivieren, indem `modules.active` für den Eintrag `strichlistensystem` auf `true` gesetzt wird (im Demo-Modus macht das der `ModuleDemoSeeder`).
3. Optional die Standard-Artikel anlegen (idempotent, legt nur fehlende Artikel an):
   ```bash
   php artisan db:seed --class=ArticleSeeder
   ```
4. Tablets freischalten: Unter **Dashboard → Admin → Strichliste → Kiosk-Geräte** ein Gerät anlegen (z. B. „Tablet Bar“) und mit dem Tablet den QR-Code scannen oder die Pairing-URL einmal im Browser des Tablets öffnen. Der Server speichert dann ein Cookie (400 Tage, nur für `/strichlistensystem/kiosk`) und leitet auf den Kiosk weiter. Danach bleibt das Tablet freigeschaltet, auch nach Reloads.
   - Ohne gültiges Cookie antwortet der Kiosk mit 401.
   - Wird das Gerät gelöscht oder eine neue Pairing-URL erzeugt, ist das Tablet sofort gesperrt und muss mit der neuen URL neu freigeschaltet werden.

## Konfiguration

Die Einstellungen liegen fest in [`config/strichlistensystem.php`](../config/strichlistensystem.php). Es gibt keine `.env`-Variablen mehr: Ein- und Ausschalten läuft über das Modul, die Kiosk-Geräte über das Dashboard.

| Config-Key | Wert | Bedeutung |
|---|---|---|
| `strichlistensystem.kiosk_session_timeout` | `120` | Sekunden ohne Aktivität, nach denen eine am Kiosk angemeldete Person die PIN erneut eingeben muss. |
| `strichlistensystem.pin_max_attempts` | `5` | Maximale falsche PIN-Eingaben pro Person und Minute am Kiosk. |
| `strichlistensystem.kiosk_device_max_failed_pins` | `15` | Maximale falsche PIN-Eingaben aller Personen an einem Kiosk-Gerät. Danach ist das Gerät gesperrt. |
| `strichlistensystem.kiosk_device_lock_seconds` | `600` | Zeitfenster bzw. Sperrdauer in Sekunden für `kiosk_device_max_failed_pins`. |
| `strichlistensystem.trivial_pins` | Liste | PINs, die zu leicht zu erraten sind (z. B. `0000`, `1234`) und im Selbstzugriff nicht gesetzt werden können. |
| `strichlistensystem.self_service_limit_window` | `60` | Zeitfenster in Sekunden, für das die maximale Anzahl eines Artikels im Selbstzugriff gilt. |
| `strichlistensystem.cancel_window` | `60` | Sekunden, in denen eine Buchung storniert werden kann. Der Server akzeptiert zusätzlich 10 Sekunden Toleranz (`ArticleActionLog::CANCEL_GRACE_SECONDS`). |
| `strichlistensystem.icons` | Liste | Font-Awesome-Icons, die für Artikel ausgewählt werden können. Neue Icons müssen zusätzlich in `resources/js/app.js` registriert werden. |

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
2. Ist noch keine PIN gesetzt (oder wurde sie von einem Admin zurückgesetzt), muss zuerst eine PIN festgelegt werden (genau 4 Ziffern, doppelte Eingabe, keine leicht zu erratende PIN wie `0000` oder `1234`). Erst danach kann gebucht werden.
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

- **Artikel anlegen und bearbeiten:** Name (eindeutig, max. 50 Zeichen), Icon, optional ein Bild, Reihenfolge, maximale Anzahl (1–100), Preis (0–1000 €, Eingabe in Euro mit Komma oder Punkt), aktiv.
- **Artikelbilder:**
  - Erlaubt sind PNG, JPG und WebP mit höchstens 2 MB. SVG ist nicht erlaubt, weil es Scripte enthalten kann.
  - Hat ein Artikel ein Bild, wird es am Kiosk, im Selbstzugriff und in der Artikeltabelle statt des Icons angezeigt. Das Icon bleibt Pflicht und dient als Fallback.
  - Das Bild steht ohne farbigen Hintergrund auf der Karte, ein PNG mit transparentem Hintergrund passt deshalb zum hellen und dunklen Design.
  - Der Upload läuft über den Server (Validierung vor dem Speichern) in den Portals-S3 unter `articles/`. Beim Ersetzen, Entfernen („Bild entfernen“) und Löschen des Artikels wird die alte Datei gelöscht.
- **Aktivieren/Deaktivieren:** Deaktivierte Artikel werden am Kiosk und im Selbstzugriff nicht mehr angezeigt und können nicht mehr gebucht werden. Bisherige Buchungen bleiben erhalten und erscheinen weiterhin im Export.
- **Löschen:** nur für Artikel ohne Buchungen (auch stornierte zählen). Artikel mit Buchungen sollten deaktiviert werden.
- **Abrechnung** (`/dashboard/admin/strichlistensystem/billing`, Link „Zur Abrechnung“): Tabelle aller Personen mit Anzahl der Buchungen, Gesamt, Bezahlt und Offen (Suche, Sortierung, Live-Update). Über „Zahlungen“ öffnet sich ein Dialog, in dem ein bezahlter Betrag eingetragen (vorausgefüllt mit dem offenen Betrag, Teilzahlungen möglich) und die letzte Zahlung rückgängig gemacht werden kann.
- **CSV-Export:** `strichlisten_export.csv` mit den Spalten `Nachname;Vorname;Anz_<Artikel>…;Betrag_<Artikel>…;Gesamt;Bezahlt;Offen`. Enthält alle User und alle Artikel (auch deaktivierte), gezählt werden nur nicht stornierte Buchungen. Beträge werden mit dem Preis zum Zeitpunkt der Buchung berechnet und als Dezimalzahl mit Komma ohne Währungszeichen ausgegeben (z. B. `1,50`), damit ein deutsches Excel sie als Zahl erkennt. Kodierung UTF-8 mit BOM, Trennzeichen `;`. Namen, die mit `=`, `+`, `-`, `@`, Tab oder CR beginnen, bekommen ein führendes `'`, damit Excel sie nicht als Formel ausführt.
- **Kiosk-Geräte** (`/dashboard/admin/strichlistensystem/kiosk-devices`, Link „Zu den Kiosk-Geräten“): Tablets anlegen, löschen und eine neue Pairing-URL erzeugen. Pro Gerät werden die Pairing-URL als Text und QR-Code (SVG, erzeugt mit `bacon/bacon-qr-code`) sowie der Zeitpunkt der letzten Nutzung angezeigt.
- **PIN im User-Bearbeiten-Dialog** (User-Verwaltung, Berechtigung `manage users`): Zeigt nur, ob eine PIN gesetzt ist. Admins können die PIN weder sehen noch setzen, sondern nur zurücksetzen (löschen). Der User muss dann im Selbstzugriff eine neue PIN festlegen, bevor er wieder buchen kann.
- **User löschen:** Hat der User in der Strichliste noch einen offenen Betrag (oder ein Guthaben), zeigt der Lösch-Dialog eine Warnung mit dem Betrag. Beim Löschen werden alle Buchungen und Zahlungen des Users mitgelöscht.

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
| [`2026_10_27_141723_add_strichlistensystem_module_and_permission.php`](../database/migrations/2026_10_27_141723_add_strichlistensystem_module_and_permission.php) | Modul-Eintrag `strichlistensystem` (inaktiv) und Berechtigung `manage strichlistensystem` für `admin` und `super admin` |
| [`2026_10_27_142004_hash_pins_on_users_table.php`](../database/migrations/2026_10_27_142004_hash_pins_on_users_table.php) | Vergrößert `users.pin` und hasht bestehende PINs. Rollback löscht alle PINs, da Hashes nicht umkehrbar sind |
| [`2026_10_27_142333_create_strichlistensystem_kiosk_devices_table.php`](../database/migrations/2026_10_27_142333_create_strichlistensystem_kiosk_devices_table.php) | Tabelle `strichlistensystem_kiosk_devices` |
| [`2026_10_29_193015_add_image_to_articles_table.php`](../database/migrations/2026_10_29_193015_add_image_to_articles_table.php) | Spalte `articles.image` (S3-Pfad, `NULL` = kein Bild) |

### Tabellen

**`users`** (bestehende Tabelle, alle personenbezogenen Daten bleiben hier)

| Spalte | Verwendung im Strichlistensystem |
|---|---|
| `pin` | `string`, nullable. Hash der 4-stelligen PIN (Cast `hashed`). `NULL` bedeutet, dass noch keine PIN gesetzt ist. |
| `is_disabled` | Deaktivierte User erscheinen nicht im Kiosk und können nicht buchen. |
| `avatar` | Pfad des Bildes im S3-Bucket von Portals. |
| `course_id` | Studiengang für Anzeige und Filter. |
| Rollen (`spatie/laravel-permission`) | `tutor` und `special` für die Filter am Kiosk. Ersetzen die früheren Spalten `is_tutor` und `is_special`. |

**`articles`**

| Spalte | Typ | Bedeutung |
|---|---|---|
| `name` | `string(50)` | Name des Artikels |
| `icon` | `string(50)` | Font-Awesome-Icon (aus `strichlistensystem.icons`) |
| `image` | `string`, nullable | Pfad des hochgeladenen Bildes im S3-Bucket. Wird statt des Icons angezeigt |
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

**`strichlistensystem_kiosk_devices`** (Tablets, die den Kiosk öffnen dürfen)

| Spalte | Typ | Bedeutung |
|---|---|---|
| `name` | `string(50)` | Name des Geräts, z. B. „Tablet Bar“ |
| `token` | `text` | Zufälliger Token (64 Zeichen), verschlüsselt gespeichert (Cast `encrypted`), damit die Pairing-URL erneut angezeigt werden kann |
| `last_used_at` | `timestamp`, nullable | Letzte Nutzung, wird höchstens einmal pro Minute gespeichert |

## Sicherheit

- **Kiosk:** Middleware `StrichlistensystemKiosk` liest das Cookie `strichlistensystem_kiosk` (`<id>|<token>`, von Laravel verschlüsselt) und vergleicht den Token zeitkonstant (`hash_equals`) mit dem Token des Geräts. Ohne gültiges Cookie antwortet der Kiosk mit 401. Die Pairing-Route ist auf 10 Aufrufe pro Minute begrenzt.
- **Bilder:** Werden nur am Kiosk über `/strichlistensystem/kiosk/user/{user}/avatar` ausgeliefert. Die Route steht hinter dem Kiosk-Cookie und streamt das Bild aus dem Portals-S3 (`Cache-Control: private, max-age=3600`). Es werden keine signierten S3-URLs an den Kiosk ausgegeben.
- **PIN:**
  - Wird als Hash in `users.pin` gespeichert (Cast `hashed`) und mit `Hash::check()` geprüft. Erlaubt sind genau 4 Ziffern, leicht zu erratende PINs (`trivial_pins`) können nicht gesetzt werden.
  - Ist im `User`-Model `$hidden` und `$guarded`. Sie wird nirgends ausgeliefert, auch nicht an die User-Verwaltung (dort nur `has_pin`).
  - Ist vom Audit-Log ausgenommen (`$auditExclude`).
- **PIN-Versuche:**
  - Rate-Limit pro Person (`pin_max_attempts` pro Minute), unabhängig von der IP, da alle Tablets dieselbe IP haben können.
  - Zusätzlich ein Limit pro Kiosk-Gerät über alle Personen (`kiosk_device_max_failed_pins` in `kiosk_device_lock_seconds`). So kann niemand gängige PINs bei vielen Personen durchprobieren. Andere Geräte bleiben nutzbar.
- **Kiosk-Anmeldung:** Gilt nur für die angemeldete Person. Buchungen oder Stornos für andere Personen werden abgelehnt.
- **Selbstzugriff:** Der Portals-Login hat kein Passwort. Die PIN schützt deshalb vor allem die Buchungen am Kiosk.

## Routen

Alle Routen liegen in [`routes/web.php`](../routes/web.php) und stehen hinter der Middleware `ActiveModule:strichlistensystem`.

| Methode | URI | Name |
|---|---|---|
| GET | `/strichlistensystem/article/{article}/image?v=…` | `strichlistensystem.articleImage` (ohne Login und ohne Kiosk-Cookie, `Cache-Control: public, max-age=31536000, immutable`; `v` ändert sich mit jedem neuen Bild) |
| GET | `/strichlistensystem/kiosk/pair/{device}?token=…` | `strichlistensystem.kiosk.pair` |
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
| GET | `/dashboard/admin/strichlistensystem/kiosk-devices` | `dashboard.admin.strichlistensystem.kioskDevices` |
| POST | `/dashboard/admin/strichlistensystem/kiosk-device` | `dashboard.admin.strichlistensystem.storeKioskDevice` |
| POST | `/dashboard/admin/strichlistensystem/kiosk-device/{device}/regenerate-token` | `dashboard.admin.strichlistensystem.regenerateKioskDeviceToken` |
| DELETE | `/dashboard/admin/strichlistensystem/kiosk-device/{device}` | `dashboard.admin.strichlistensystem.deleteKioskDevice` |
| POST | `/dashboard/admin/user/{user}/reset-pin` | `dashboard.admin.resetUserPin` (Berechtigung `manage users`) |

Middleware-Kette:
- Pairing: `ActiveModule` → `throttle:10,1`
- Kiosk: `ActiveModule` → `StrichlistensystemKiosk`
- Selbstzugriff: `ActiveModule` → `Authenticate`
- Admin: `Authenticate` → `IsLoggedInAdmin` → `ActiveModule` → `can:manage strichlistensystem`

## Dateien

### Neu

| Datei | Zweck |
|---|---|
| `config/strichlistensystem.php` | Konfiguration |
| `app/Http/Middleware/StrichlistensystemKiosk.php` | Prüfung des Kiosk-Cookies |
| `app/Models/StrichlistensystemKioskDevice.php` | Kiosk-Gerät mit Token, Cookie-Wert, Pairing-URL und QR-Code |
| `resources/js/pages/Dashboard/Admin/Strichlistensystem/KioskDevices.vue` | Verwaltung der Kiosk-Geräte |
| `app/Http/Controllers/StrichlistensystemKioskController.php` | Kiosk: Übersicht, PIN, Buchen, Storno, Abmelden, Bilder |
| `app/Http/Controllers/StrichlistensystemController.php` | Selbstzugriff: PIN setzen/ändern, Buchen, Storno |
| `app/Http/Controllers/DashboardAdminStrichlistensystemController.php` | Admin: Artikel, Aktivieren/Deaktivieren, Export |
| `app/Models/Article.php` | Artikel (inkl. Scope `active()`, `image_url`, `deleteImage()`, auditiert) |
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
| `resources/js/components/strichlistensystem/ArticleCard.vue` | Artikelkarte mit Bild oder Icon, Mengenregler, Limit-Hinweis und Countdown |
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
| `app/Models/User.php` | Relation `articleActionLogs()`; Methoden `hasPin()`, `isPinValid()`, `canUseStrichlistensystem()`, `remainingArticleAmount()`, `bookableArticles()`, `buyArticle()`, `cancelArticleActionLog()`, `latestArticleActionLogs()`, `articleActionLogsTotal()`, `strichlistensystemBalance()`, `payStrichlistensystem()`, `latestStrichlistensystemPayment()`, `undoStrichlistensystemPayment()`; Relation `strichlistensystemPayments()`; `pin` mit Cast `hashed`, in `$hidden`, `$guarded` und `$auditExclude` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Geteilter Prop `strichlistensystemEnabled` |
| `app/Http/Controllers/AppController.php` | Nach dem Login Weiterleitung zurück zu `/strichlistensystem`, falls die Person von dort kam. Andere gespeicherte Ziele (`url.intended`) bleiben unberührt, damit der Tutor-Login weiter funktioniert. |
| `app/Http/Controllers/DashboardAdminController.php` | `resetUserPin` löscht die PIN eines Users |
| `routes/web.php` | Neue Routen (vor der Catch-all-Route) |
| `resources/js/app.js` | Icons für Artikel und UI registriert |
| `resources/js/layouts/DashboardLayout.vue` | Menüpunkt „Strichliste“ |
| `resources/js/components/app/Navbar.vue` | Admin-Menüpunkt „Strichliste“ (Desktop und Mobil) |
| `resources/js/components/user/EditModal.vue` | PIN-Status und „PIN zurücksetzen“ (nur sichtbar, wenn das Modul aktiv ist) |
| `resources/js/components/user/DeleteModal.vue` | Warnung bei offenem Betrag |
| `app/Helpers/Table/UsersTable.php` | `has_pin` und `strichlistensystem_open` pro User, Live-Update auch bei neuen Buchungen und Zahlungen |
| `database/seeders/ModuleSeeder.php`, `ModuleDemoSeeder.php` | Modul `strichlistensystem` (im Demo-Modus aktiv) |
| `database/factories/UserFactory.php` | PIN `UserFactory::PIN` (`4711`, einmal gehasht), State `withoutPin()` |
| `database/seeders/RoleSeeder.php` | Berechtigung `manage strichlistensystem` für `admin` und `super admin` |
| `database/seeders/DatabaseSeeder.php` | `ArticleSeeder` in den Basis-Seedern |
| `database/seeders/UserDemoSeeder.php` | Demo-Accounts mit PIN `1234` |
| `database/seeders/TutorSeeder.php`, `StudentSeeder.php` | PIN aus CSV nur übernehmen, wenn sie aus genau 4 Ziffern besteht; fehlende Spalte führt nicht mehr zu einem Fehler |
| `database/seeders/students.csv.example` | Spalte `pin` ergänzt |

## Seeder

- `ArticleSeeder` legt an: Bier (8), Radler (8), Cola (8), Spezi (8), Wasser (2), Bacardi (1). Die Zahl in Klammern ist die maximale Anzahl. Existierende Artikel (gleicher Name) werden übersprungen. Die Artikel haben noch keinen Preis, er muss im Admin-Dashboard festgelegt werden.
- `UserDemoSeeder` (Event-Typ `demo`): Die benannten Demo-Accounts (`superadmin@example.com`, `admin@example.com`, `tutor@example.com` usw.) haben die PIN `1234`. Die 800 Demo-Studierenden haben die PIN `4711`.
- `TutorSeeder` liest die PIN aus Spalte 7 von `tutors.csv`, `StudentSeeder` aus Spalte 5 von `students.csv`.

## Tests

```bash
php artisan test --compact tests/Feature/StrichlistensystemKioskTest.php tests/Feature/StrichlistensystemSelfServiceTest.php tests/Feature/DashboardAdminStrichlistensystemTest.php tests/Feature/DashboardAdminStrichlistensystemBillingTest.php
```

Die Tests brauchen eine `phpunit.xml`, die im Repository per `.gitignore` ausgeschlossen ist. Lokal reicht die Standard-Konfiguration aus dem Laravel-Skeleton mit `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `SESSION_DRIVER=array` und `CACHE_STORE=array`.

Abgedeckt sind unter anderem: Kiosk-Geräte und Pairing, Modul an/aus, PIN-Hashing, PIN-Reset, triviale PINs, Sperre pro Gerät, PIN-Prüfung inkl. Rate-Limit, Kiosk-Timeout, Buchen und Stornieren, Limits pro Anmeldung und Zeitfenster, deaktivierte Artikel und User, Bildauslieferung, Admin-Berechtigungen, Artikelverwaltung, Preise (Snapshot pro Buchung, einmalige Übernahme für Buchungen ohne Preis, Ablehnung bei geändertem Preis), Abrechnung und Zahlungen (Teilzahlung, Obergrenze, Rückgängig nur für die letzte Zahlung, Live-Update), CSV-Export mit Beträgen und Formel-Escaping, Warnung beim Löschen und dass die PIN nicht an das Frontend oder die öffentliche API gelangt.

Hinweis: Der bereits vorhandene `tests/Feature/ExampleTest.php` schlägt fehl, weil er keine Datenbank migriert. Das ist unabhängig vom Strichlistensystem.

## Unterschiede zum alten Strichlistensystem

| Alt | Neu |
|---|---|
| Eigene Tabelle `persons` mit Import aus Portals (`/importusers/{password}`) | Direkt die `users`-Tabelle von Portals; kein Import mehr |
| `is_tutor`, `is_special`, `course`-Enum, `img` | Rollen `tutor`/`special`, `course_id`, `avatar` |
| Zugriffsschutz über eigene VPN-Installation (`APP_IS_VPN`) | Freigeschaltete Kiosk-Geräte (Pairing-URL + Cookie) |
| QR-Code mit Auth-Link (`auth_token`) zum Weiterbuchen am Handy | Selbstzugriff über den Portals-Login |
| PIN als `char(4)` mit `pin_change_required` (Branch `pin_feature`) | `pin` als Hash; eine fehlende oder zurückgesetzte PIN muss im Selbstzugriff gesetzt werden |
| CSV-Export über `/exportcsv/{password}`, ISO-8859-1 | Admin-Dashboard mit Berechtigung, UTF-8 mit BOM, gleiche Spalten plus Beträge, Gesamt, Bezahlt und Offen |
| Keine Preise | Preis pro Artikel, Preis-Snapshot pro Buchung, Zahlungen pro Person |
| Telegram-Benachrichtigungen (bereits auskommentiert) | Entfernt |
| Artikel nur über Seeder pflegbar | Artikelverwaltung im Admin-Dashboard inkl. Aktivieren/Deaktivieren |
| `max_order_amount` = Maximum pro einzelner Buchung | Maximum pro Kiosk-Anmeldung bzw. pro Zeitfenster im Selbstzugriff |
| Eigenes Design (Tailwind, Options API) | Portals-Design und -Komponenten (Vue 3 `<script setup>` mit TypeScript, Inertia 3) |
