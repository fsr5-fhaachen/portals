# Tabellen: seitenweises Laden, Server-Suche, Server-Sortierung und Live-Update

Alle Tabellen in Portals laden ihre Daten seitenweise beim Scrollen (Infinite Scroll). Suche und Sortierung laufen auf dem Server, und offene Tabellen aktualisieren sich live, indem sie regelmäßig nur die **Änderungen** abfragen. Ziel ist, die Ladezeiten und die übertragene Datenmenge zu minimieren.

Diese Dokumentation beschreibt, wie das System aufgebaut ist, wie das Polling funktioniert und wie man neue Tabellen hinzufügt.

## Inhalt

- [Überblick](#überblick)
- [Request-Parameter](#request-parameter)
- [Backend: die Klasse `Table`](#backend-die-klasse-table)
- [Live-Update (Polling)](#live-update-polling)
- [Frontend: `AppTable`](#frontend-apptable)
- [`usePolling` für eigene Abfragen](#usepolling-für-eigene-abfragen)
- [Anleitung: eine neue Tabelle hinzufügen](#anleitung-eine-neue-tabelle-hinzufügen)
- [Sortier-Hilfen](#sortier-hilfen)
- [Eigene Aktionen in der Tabelle](#eigene-aktionen-in-der-tabelle)
- [Fallstricke](#fallstricke)
- [Vorhandene Tabellen](#vorhandene-tabellen)
- [Tests](#tests)

## Überblick

```
Browser                                        Server
───────                                        ──────
Seite öffnen  ─────────── GET /seite ────────▶ Controller
                                               └─ new XyzTable($request)->scroll()
              ◀── Inertia-Seite mit 50 Zeilen ─   (Seite 1, sortiert, gesucht)

Nach unten scrollen ───── GET /seite?page=2 ──▶ gleicher Controller (Teil-Request,
              ◀── nächste 50 Zeilen ────────────  nur die Tabellen-Prop wird berechnet)

Spalte / Suche ────── GET /seite?sort=…&search=… (Reset auf Seite 1)

alle 2,5 s  ───── GET /seite?changes_since=…&loaded_ids=… ──▶ $table->changesResponse()
              ◀── {"time":…,"rows":[…],"removed_ids":[…],"new_ids":[…]} ──
```

Bausteine:

| Teil | Datei | Aufgabe |
|---|---|---|
| Basisklasse | [`app/Helpers/Table/Table.php`](../app/Helpers/Table/Table.php) | Suche, Sortierung, Seiten, Live-Update |
| Tabellen-Klassen | `app/Helpers/Table/*Table.php` | Beschreiben je eine Tabelle: Abfrage, Zeilen, Suchfelder, Sortierschlüssel |
| Event-Seiten | [`app/Helpers/Table/EventPageTable.php`](../app/Helpers/Table/EventPageTable.php) | Wählt je nach Event-Typ die Gruppen-, Slot- oder Anmeldungstabelle |
| Tabellenkomponente | [`resources/js/components/app/Table.vue`](../resources/js/components/app/Table.vue) (`AppTable`) | Darstellung, Infinite Scroll, Sortierung, Suche, Live-Update |
| Spalten / Sortierwerte | `resources/js/types/table-column.ts`, `table-sort-value.ts` | Spaltendefinition mit `sortKey` |
| Polling | [`resources/js/composables/usePolling.ts`](../resources/js/composables/usePolling.ts) | Periodische Abfragen, pausiert bei unsichtbarem Tab |
| Startzeit | `serverTime` in [`HandleInertiaRequests`](../app/Http/Middleware/HandleInertiaRequests.php) | Startpunkt des Live-Updates |

Technisch basiert das Nachladen auf den eingebauten Funktionen von Inertia v3: `Inertia::scroll()` im Backend und `<InfiniteScroll>` im Frontend. Es werden keine zusätzlichen Pakete benötigt.

## Request-Parameter

Alle Parameter stehen in der URL der Seite. Dadurch übernimmt Inertia sie beim Nachladen automatisch, und Suche und Sortierung überstehen ein Neuladen der Seite.

| Parameter | Bedeutung |
|---|---|
| `page` | Seite des Infinite Scroll (wird von Inertia gesetzt, nicht in der URL angezeigt) |
| `search` | Suchbegriffe. Mehrere Wörter: jedes Wort muss in mindestens einem Suchfeld vorkommen |
| `sort` | Sortierschlüssel aus `sorts()`. Unbekannte Schlüssel werden ignoriert |
| `direction` | `asc` (Standard) oder `desc` |
| `changes_since` | Unix-Zeitstempel. Ist der Parameter gesetzt, liefert der Controller das Live-Update als JSON statt der Seite |
| `loaded_ids` | Kommagetrennte IDs der geladenen Zeilen (für das Live-Update) |

Weitere tabellenspezifische Filter sind möglich (z. B. `course` und `role` am Kiosk, siehe `applyFilters()`).

## Backend: die Klasse `Table`

Eine Tabelle ist eine Klasse, die von `App\Helpers\Table\Table` erbt. Pflicht sind nur zwei Methoden, alles andere ist optional:

| Methode | Pflicht | Zweck |
|---|---|---|
| `query(): Builder` | ja | Basis-Abfrage mit dem Bereich der Tabelle (z. B. „Anmeldungen von Event X“) inklusive `with()` / `withCount()` |
| `row(Model $model): array` | ja | Welche Felder einer Zeile an das Frontend gehen. Nur das Nötige ausliefern! |
| `searchColumns(): array` | nein | Suchfelder, z. B. `['firstname', 'email']`, Relationen als `'user.firstname'` |
| `sorts(): array` | nein | Sortierschlüssel → Spalte (String) oder Closure |
| `applyDefaultSort(Builder)` | nein | Sortierung, wenn keine gewählt ist |
| `applyFilters(Builder)` | nein | Zusätzliche Filter aus dem Request |
| `perPage(): int` | nein | Zeilen pro Seite (Standard `50`) |
| `refreshesAllRows(): bool` | nein | Live-Update liefert immer alle geladenen Zeilen (für Zählwerte, siehe unten) |
| `whereChangedSince(Builder, Carbon)` | nein | Was als „geändert“ gilt (Standard: `updated_at`) |
| `rowVersion(Model): int` | nein | Version einer Zeile (Standard: `updated_at`) |

Öffentliche Methoden für den Controller:

| Methode | Rückgabe |
|---|---|
| `scroll()` | `Inertia::scroll(...)`-Prop mit der aktuellen Seite |
| `wantsChanges()` | `true`, wenn der Request das Live-Update anfragt (`changes_since`) |
| `changesResponse()` | JSON-Antwort des Live-Updates |
| `changes()` | Das Live-Update als Array (z. B. für Tests) |
| `currentSort()` | `[sortKey|null, 'asc'|'desc']` |

### Was intern passiert

```php
// Abfrage mit Bereich, Filtern und Suche (für Seiten UND Live-Update)
protected function filteredQuery(): Builder
{
    $query = $this->query();
    $this->applyFilters($query);
    $this->applySearch($query);

    return $query;
}

// zusätzlich sortiert; der Primärschlüssel kommt immer zuletzt dazu,
// damit die Reihenfolge über Seitengrenzen stabil ist (keine doppelten/fehlenden Zeilen)
protected function sortedQuery(): Builder
{
    $query = $this->filteredQuery();
    // ... Sortierschlüssel aus sorts() oder applyDefaultSort()

    return $query->orderBy($query->getModel()->getQualifiedKeyName());
}

public function scroll(): ScrollProp
{
    return Inertia::scroll(fn () => $this->sortedQuery()
        ->simplePaginate($this->perPage())          // ohne zusätzliche COUNT-Abfrage
        ->through(fn (Model $model) => $this->toRow($model)));
}
```

- **`simplePaginate`** statt `paginate`: Laravel spart die `COUNT(*)`-Abfrage über die ganze Tabelle. Für Infinite Scroll reicht „gibt es eine nächste Seite?“.
- **Die Suche** nutzt `whereLike()`. Groß-/Kleinschreibung wird auf MySQL/MariaDB, PostgreSQL und SQLite gleich ignoriert.
- **`toRow()`** hängt an jede Zeile das Feld `_version` an (siehe [Versionen](#versionen-welche-zeile-gewinnt)).

### Im Controller verwenden

Der gleiche Controller liefert die Seite, die nachgeladenen Seiten und das Live-Update. So gelten automatisch dieselbe Middleware und dieselben Berechtigungen wie für die Seite:

```php
public function users(IlluminateRequest $request): Response|JsonResponse
{
    $usersTable = new UsersTable($request);
    if ($usersTable->wantsChanges()) {
        return $usersTable->changesResponse();
    }

    return Inertia::render('Dashboard/Admin/Users', [
        'roles' => Role::where('name', '!=', 'super admin')->get(),
        'courses' => Course::all(),
        'users' => $usersTable->scroll(),
    ]);
}
```

Beim Nachladen schickt Inertia einen Teil-Request (nur die Prop `users`). Andere Props sollten deshalb als Closure übergeben werden, wenn sie teuer sind, z. B. `'courses' => fn () => Course::all()`. Dann werden sie beim Nachladen nicht berechnet.

## Live-Update (Polling)

### Ablauf

1. Beim Laden der Seite liefert der Server die geteilte Prop **`serverTime`** (Unix-Zeitstempel). Das ist der Startpunkt.
2. `AppTable` fragt alle **2,5 Sekunden** (`liveInterval`) die URL der Seite mit `changes_since` und `loaded_ids` ab. Ist der Browser-Tab ausgeblendet, pausiert das Polling und läuft beim Zurückkehren sofort einmal.
3. Der Server antwortet mit `time`. Das wird der Startpunkt für die nächste Abfrage.

Beispiel einer Abfrage:

```
GET /dashboard/tutor/event/4?sort=lastname&changes_since=1791749634&loaded_ids=2401,2402,2403
Accept: application/json
```

Antwort ohne Änderungen (59 Bytes statt der kompletten Liste):

```json
{ "time": 1791749639, "rows": [], "removed_ids": [], "new_ids": [] }
```

Antwort mit Änderungen:

```json
{
  "time": 1791749642,
  "rows": [
    { "id": 2401, "is_present": 1, "user": { "id": 17, "firstname": "Max", "lastname": "Harber" }, "_version": 1791749641 }
  ],
  "removed_ids": [2403],
  "new_ids": [2650]
}
```

### Was der Server berechnet

```php
public function changes(): array
{
    // die Zeit wird VOR den Abfragen genommen, damit keine Änderung verloren geht
    $time = now()->timestamp;
    $since = Carbon::createFromTimestamp((int) $this->request->query('changes_since'), config('app.timezone'));
    $loadedIds = /* IDs aus loaded_ids, max. 2000 */;

    // geladene Zeilen, die es (mit Suche und Filtern) noch gibt
    $existingIds = $this->filteredQuery()->whereIn($key, $loadedIds)->pluck($key);

    // davon die geänderten (oder alle bei refreshesAllRows())
    $changedQuery = $this->filteredQuery()->whereIn($key, $existingIds);
    if (! $this->refreshesAllRows()) {
        $changedQuery->where(fn ($query) => $this->whereChangedSince($query, $since));
    }

    // neu angelegte Zeilen, die noch nicht geladen sind
    $newIds = $this->filteredQuery()->whereNotIn($key, $loadedIds)
        ->where('created_at', '>=', $since)->limit(100)->pluck($key);

    return [
        'time' => $time,
        'rows' => /* geänderte Zeilen über row() */,
        'removed_ids' => array_diff($loadedIds, $existingIds),
        'new_ids' => $newIds,
    ];
}
```

| Feld | Bedeutung | Reaktion der Tabelle |
|---|---|---|
| `rows` | Geladene Zeilen, die sich seit `changes_since` geändert haben | Zeile wird an Ort und Stelle aktualisiert (nicht neu einsortiert) |
| `removed_ids` | Geladene Zeilen, die es nicht mehr gibt oder die nicht mehr zur Suche bzw. zum Bereich passen | Zeile wird ausgeblendet |
| `new_ids` | Neue Zeilen, die noch nicht geladen sind | Hinweis „Es gibt X neue Einträge. Aktualisieren“ |

Warum Löschungen über `loaded_ids` erkannt werden und nicht über das Audit-Log: Das Audit-Log lässt sich abschalten (`AUDITING_ENABLED`), und es erfasst keine Löschungen per Foreign Key (`ON DELETE CASCADE`). Löscht man z. B. einen User, verschwinden seine Anmeldungen ohne eigenes Audit. Der Abgleich der geladenen IDs erkennt das immer.

### Was als „geändert“ gilt

Standard ist `updated_at >= changes_since`. Hängt eine Zeile von anderen Tabellen ab, muss `whereChangedSince()` erweitert werden. Beispiel Anmeldungen: Der Name kommt vom User. Ändert sich der User, soll die Anmeldung trotzdem aktualisiert werden:

```php
// RegistrationsTable
protected function whereChangedSince(Builder $query, Carbon $since): void
{
    $query->where('registrations.updated_at', '>=', $since)
        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('users.updated_at', '>=', $since));
}
```

Änderungen, die `updated_at` nicht verändern, werden nicht erkannt. Beispiel: Rollen über `syncRoles()` ändern nur die Pivot-Tabelle. Deshalb wird der User danach „angefasst“:

```php
// DashboardAdminController::editUser
$user->syncRoles($roles);

// role changes do not change the user itself, touch it for the live update of the user table
$user->touch();
```

### Zählwerte: `refreshesAllRows()`

Kleine Tabellen mit berechneten Werten (z. B. Teilnehmerzahl per `withCount('registrations')`) ändern sich, ohne dass sich die Zeile selbst ändert. Für sie liefert das Live-Update immer alle geladenen Zeilen. Das lohnt sich nur bei kleinen Tabellen (Events, Gruppen, Slots, Artikel):

```php
// GroupsTable
protected function refreshesAllRows(): bool
{
    return true;
}
```

### Versionen: welche Zeile gewinnt

Eine Zeile kann mehrfach im Browser ankommen: über eine Seite, ein Live-Update, eine eigene Aktion oder nach einem Neuladen. Jede Zeile hat deshalb ein Feld `_version`, Standard ist `updated_at` als Zeitstempel. Die Tabelle zeigt immer die Version mit dem höchsten Wert. Bei `refreshesAllRows()` ist die Version der Abfragezeitpunkt.

Bei abhängigen Daten nimmt man das Maximum:

```php
// RegistrationsTable
protected function rowVersion(Model $model): int
{
    return max((int) $model->updated_at?->timestamp, (int) $model->user?->updated_at?->timestamp);
}
```

### Gleiche Sekunde

Zeitstempel haben Sekundengenauigkeit. Damit keine Änderung verloren geht, die in derselben Sekunde wie die letzte Abfrage passiert ist, vergleicht der Server mit `>=`. Eine Zeile kann deshalb zweimal als geändert gemeldet werden. Das ist harmlos, die Aktualisierung ist idempotent.

### Kosten

Pro Abfrage laufen höchstens drei schlanke Datenbankabfragen über die geladenen IDs bzw. die neuen Zeilen. Übertragen werden nur geänderte Zeilen. Ohne Änderungen sind das ca. 60 Bytes statt der kompletten Tabelle. Beispiel User-Verwaltung: vorher ca. 410 KB alle 2,5 Sekunden.

## Frontend: `AppTable`

`AppTable` ist die einzige Tabellenkomponente. Spalten, Buttons und Zeilenfarben werden wie bisher pro Tabelle definiert. Die Daten liest sie selbst aus der Inertia-Prop, deren Name in `data` steht.

### Props

| Prop | Typ | Standard | Bedeutung |
|---|---|---|---|
| `data` | `string` | – (Pflicht) | Name der Scroll-Prop der Seite, z. B. `"users"` |
| `columns` | `TableColumn[]` | – (Pflicht) | Spalten |
| `idFunction` | `Function` | `(row) => row.id` | ID einer Zeile |
| `functions` | `TableFunction[]` | `[]` | Buttons und Links pro Zeile (`TableButton`, `TableStateButton`, `TableLink`) |
| `sortValues` | `TableSortValue[]` | `[]` | Zusätzliche Sortierungen ohne eigene Spalte („Sortieren nach: …“) |
| `rowClass` | `Function` | `() => ({})` | CSS-Klassen pro Zeile |
| `searchable` | `boolean` | `false` | Zeigt das Suchfeld („Filter“ / „Suche“) |
| `live` | `boolean` | `true` | Live-Update ein/aus |
| `liveInterval` | `number` | `2500` | Intervall des Live-Updates in Millisekunden |

### Über `ref` erreichbare Methoden

| Methode | Zweck |
|---|---|
| `patchRow(id, fields)` | Felder einer geladenen Zeile sofort ändern (nach einer eigenen Aktion) |
| `removeRow(id)` | Geladene Zeile sofort entfernen |
| `refresh()` | Live-Update sofort ausführen |
| `reload(changes?)` | Tabelle ab Seite 1 neu laden, optional mit geänderten Parametern |
| `rows` | Die aktuell angezeigten Zeilen |

### Spalten mit Sortierung

Eine Spalte ist sortierbar, wenn sie einen `sortKey` hat. Der Schlüssel muss in `sorts()` der Tabellen-Klasse existieren. Spalten ohne `sortKey` zeigen kein Sortiersymbol.

```ts
new TableColumn({
  name: "lastName",
  text: "Nachname",
  valueFunction: (registration) => registration.user.lastname,
  sortKey: "lastname", // RegistrationsTable::sorts()['lastname']
});

new TableSortValue({
  name: "presence",
  text: "Anwesenheit",
  sortKey: "is_present",
});
```

Ein Klick auf den Spaltenkopf wechselt zwischen aufsteigend, absteigend und unsortiert. Die Tabelle lädt dann ab Seite 1 neu:

```ts
router.get(window.location.pathname, query, {
  only: [props.data],   // nur die Tabellen-Prop
  reset: [props.data],  // ersetzen statt anhängen
  preserveState: true,
  preserveScroll: true,
  replace: true,
});
```

Die Suche funktioniert genauso, nach 300 ms ohne weitere Eingabe.

### Infinite Scroll

```vue
<InfiniteScroll :data="data" :items-element="`#${tbodyId}`" preserve-url only-next>
  <table>
    <thead>…</thead>
    <tbody :id="tbodyId"> <tr v-for="element in rows">…</tr> </tbody>
  </table>
  <template #loading>Lade weitere Einträge …</template>
</InfiniteScroll>
```

- `items-element` zeigt auf das `<tbody>`. Inertia markiert die Zeilen mit ihrer Seite.
- `preserve-url`: `?page=` wird nicht in die URL geschrieben, nach einem Neuladen beginnt die Liste oben.
- `only-next`: Es wird nur nach unten nachgeladen.

## `usePolling` für eigene Abfragen

Alle periodischen Abfragen (außer den Beamer-Anzeigen) nutzen das Composable `usePolling`. Es wird über `unplugin-auto-import` automatisch importiert.

```ts
usePolling(fetchCourses, 2500);
```

Verhalten:

- Führt die Funktion alle `intervalMs` Millisekunden aus.
- **Pausiert, solange der Browser-Tab ausgeblendet ist** (`document.hidden`), und führt die Funktion beim Zurückkehren sofort aus.
- Überspringt eine Ausführung, wenn die vorherige noch läuft. Langsame Antworten stauen sich so nicht.
- Fängt Fehler ab (Konsole) und stoppt automatisch, wenn die Komponente entfernt wird.
- Gibt `{ run, stop }` zurück. `run()` führt die Abfrage sofort aus.

Die Beamer-Anzeigen (Countdown, Zufallsgenerator, Punktesystem) nutzen bewusst weiter `setInterval`. Sie laufen oft als Browserquelle (z. B. in OBS), wo ein als „unsichtbar“ gemeldeter Tab die Anzeige sonst einfrieren würde.

## Anleitung: eine neue Tabelle hinzufügen

Beispiel: eine Tabelle der Stationen (`stations`) eines Events mit Suche, Sortierung und Live-Update.

### 1. Tabellen-Klasse anlegen

`app/Helpers/Table/StationsTable.php`:

```php
<?php

namespace App\Helpers\Table;

use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Stations of an event.
 */
class StationsTable extends Table
{
    public function __construct(Request $request, protected int $eventId)
    {
        parent::__construct($request);
    }

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return Station::query()
            ->where('stations.event_id', $this->eventId)
            ->withCount('tutors');
    }

    /**
     * {@inheritDoc}
     *
     * @param  Station  $model
     */
    protected function row(Model $model): array
    {
        // nur die Felder, die die Tabelle anzeigt
        return [
            'id' => $model->id,
            'name' => $model->name,
            'tutors_count' => $model->tutors_count,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'name' => 'stations.name',
            'tutors_count' => 'tutors_count',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy('stations.name');
    }

    /**
     * {@inheritDoc}
     */
    protected function refreshesAllRows(): bool
    {
        // die Anzahl der Tutoren ändert sich, ohne dass sich die Station ändert
        return true;
    }
}
```

Hinweise:

- Spalten immer mit Tabellennamen angeben (`stations.name`). Bei Joins und Unterabfragen sind sie sonst mehrdeutig.
- In `row()` nur ausliefern, was die Tabelle anzeigt oder die Buttons brauchen. Das ist der größte Hebel für kleine Datenmengen.
- `refreshesAllRows()` nur bei kleinen Tabellen mit Zählwerten verwenden.

### 2. Im Controller einbinden

Es wird **keine neue Route** benötigt. Die Seite, das Nachladen und das Live-Update laufen über dieselbe Route:

```php
public function stations(Request $request, Event $event): Response|JsonResponse
{
    $stationsTable = new StationsTable($request, $event->id);
    if ($stationsTable->wantsChanges()) {
        return $stationsTable->changesResponse();
    }

    return Inertia::render('Dashboard/Admin/Stations', [
        'event' => $event,
        'stations' => $stationsTable->scroll(),
    ]);
}
```

### 3. Tabellen-Komponente anlegen

`resources/js/components/station/Table.vue` (wird automatisch als `StationTable` registriert):

```vue
<template>
  <AppTable
    data="stations"
    searchable
    :columns="getColumns()"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

function getColumns(): Array<TableColumn> {
  return [
    new TableColumn({
      name: "name",
      text: "Name",
      valueFunction: (station) => station.name,
      sortKey: "name",
    }),
    new TableColumn({
      name: "tutors",
      text: "Tutor*innen",
      valueFunction: (station) => (station.tutors_count ?? 0).toString(),
      sortKey: "tutors_count",
    }),
  ];
}

function getLinks(): Array<TableLink> {
  return [
    new TableLink({
      name: "show",
      text: "Anzeigen",
      linkFunction: (station) => "/dashboard/admin/station/" + station.id,
    }),
  ];
}
</script>
```

`valueFunction` darf HTML zurückgeben (wird per `v-html` gerendert). Werte aus Benutzereingaben deshalb escapen, wie in der Artikel-Tabelle (`escapeHtml`).

### 4. Auf der Seite verwenden

```vue
<template>
  <LayoutDashboardContent>
    <template #title>Stationen</template>
    <CardContainer>
      <StationTable />
    </CardContainer>
  </LayoutDashboardContent>
</template>
```

Die Seite selbst muss die Prop `stations` nicht deklarieren. `AppTable` liest sie direkt über `usePage()`.

### 5. Test schreiben

```php
public function test_stations_are_loaded_page_by_page(): void
{
    // ... 60 Stationen anlegen

    $this->actingAsAdmin()
        ->get(route('dashboard.admin.stations', ['event' => $event->id]))
        ->assertInertia(fn (Assert $page) => $page->has('stations.data', 50));

    $this->actingAsAdmin()
        ->get(route('dashboard.admin.stations', ['event' => $event->id, 'sort' => 'name', 'direction' => 'desc']))
        ->assertOk();

    $changes = $this->actingAsAdmin()
        ->getJson(route('dashboard.admin.stations', ['event' => $event->id, 'changes_since' => 0, 'loaded_ids' => '1,2']))
        ->json();
}
```

Vorlagen für alle Fälle stehen in [`tests/Feature/TableTest.php`](../tests/Feature/TableTest.php).

## Sortier-Hilfen

Die Basisklasse bietet Hilfen für Sortierungen, die auf MySQL/MariaDB, PostgreSQL und SQLite gleich funktionieren.

**Spalte einer anderen Tabelle (Unterabfrage statt Join):**

```php
'firstname' => fn (Builder $query, string $direction) => $query->orderBy(
    User::select('firstname')->whereColumn('users.id', 'registrations.user_id'),
    $direction,
),
```

Unterabfragen vermeiden doppelte Zeilen und mehrdeutige Spalten, die bei Joins entstehen können.

**Boolean-Spalten** (`NULL` zählt als `false`):

```php
// aufsteigend: false vor true
'drinks_alcohol' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'drinks_alcohol', $direction),

// aufsteigend: true zuerst (z. B. "anwesend zuerst")
'is_present' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'is_present', $direction, true),
```

**Mehrere verknüpfte Werte** (z. B. alle Rollen eines Users, alphabetisch aneinandergehängt). Zeilen ohne Werte stehen aufsteigend vorne:

```php
'roles' => fn (Builder $query, string $direction) => $this->orderByConcatenatedValues(
    $query,
    DB::table('roles')
        ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->whereColumn('model_has_roles.model_id', 'users.id')
        ->where('model_has_roles.model_type', User::class),
    'roles.name',
    $direction,
),
```

Intern wird je nach Datenbank `GROUP_CONCAT` (MySQL/MariaDB) bzw. `STRING_AGG` (PostgreSQL) verwendet. Auf SQLite (nur Tests) ist die Reihenfolge innerhalb einer Zeile nicht garantiert.

**`NULL` zuerst, dann Werte** (z. B. Warteschlangenposition):

```php
'queue_position' => fn (Builder $query, string $direction) => $query
    ->orderByRaw('CASE WHEN '.$this->wrap('registrations.queue_position')." IS NULL THEN 0 ELSE 1 END {$direction}")
    ->orderBy('registrations.queue_position', $direction),
```

Mit `$this->wrap()` werden Spaltennamen in rohem SQL korrekt maskiert. Werte dürfen nie direkt in `orderByRaw()` eingesetzt werden. `$direction` ist sicher, weil es immer `asc` oder `desc` ist.

## Eigene Aktionen in der Tabelle

Nach einer eigenen Aktion soll die Zeile sofort aktualisiert werden und nicht erst beim nächsten Polling.

**Aktion per API (`fetch`)** – Zeile direkt ändern oder entfernen:

```ts
const table = ref<{ patchRow: Function; removeRow: Function } | null>(null);

const toggleIsPresent = async (registrationId: number) => {
  const response = await fetch(`/api/registrations/${registrationId}/toggle-is-present`);
  if (response.ok) {
    const data = await response.json();
    table.value?.patchRow(registrationId, { is_present: data.is_present });
  }
};
```

```vue
<AppTable ref="table" data="registrations" … />
```

**Aktion per Inertia mit Weiterleitung** – nur die Meldung neu laden und danach das Live-Update auslösen. So bleibt die Scrollposition erhalten, und die Tabelle springt nicht an den Anfang:

```ts
router.post(`/dashboard/admin/user/${user.id}`, data, {
  only: ["message"],
  preserveState: true,
  preserveScroll: true,
  onSuccess: () => emits("saved"), // → table.value?.refresh()
});
```

**Kleine Tabellen** dürfen nach einer Aktion auch komplett neu geladen werden (`reset`):

```ts
router.post(url, form, { preserveScroll: true, preserveState: true, reset: ["articles"] });
```

## Fallstricke

- **Kein Flex-Container um den horizontalen Scroll-Bereich.** Inertia sucht für das Nachladen den nächsten scrollbaren Vorfahren. Ein `overflow-x-auto`-Element, dessen Elternelement `flex flex-col` ist, wird fälschlich als vertikaler Scroll-Bereich erkannt. Dann lädt die Tabelle sofort alle Seiten. In `AppTable` ist das Elternelement deshalb ein einfaches `<div>`.
- **Teure Props als Closure übergeben.** Beim Nachladen werden nur die angefragten Props berechnet, aber nur, wenn die anderen lazy sind. Die geteilten Props in `HandleInertiaRequests` (`user`, `modules`, `pages`) sind deshalb Closures.
- **Inertia merged nur bei Teil-Requests.** Ein normaler Seitenaufruf (z. B. eine Weiterleitung nach `router.post` ohne `only`) ersetzt die Tabellen-Prop durch Seite 1. Die Tabelle verwirft dabei doppelte Zeilen und behält die neueste Version.
- **Eine Tabelle pro Seite.** Die Parameter (`page`, `sort`, `search`) sind nicht pro Tabelle getrennt. Für mehrere Tabellen auf einer Seite müssten `pageName` (`simplePaginate(pageName: …)`) und die Parameternamen pro Tabelle konfigurierbar gemacht werden.
- **`updated_at` muss sich ändern.** Massen-Updates über den Query-Builder (`DB::table()->update()`) oder Pivot-Änderungen setzen `updated_at` nicht. Danach `touch()` aufrufen oder `whereChangedSince()` erweitern.
- **Reservierte Parameternamen.** `page`, `search`, `sort`, `direction`, `changes_since` und `loaded_ids` nicht für andere Zwecke auf Seiten mit Tabellen verwenden.
- **Maximal 2000 geladene IDs** werden beim Live-Update geprüft (`MAX_LOADED_IDS`), damit die URL nicht zu lang wird. Bei mehr geladenen Zeilen werden Löschungen weiter hinten erst nach einem Neuladen erkannt.
- **Gleiche Zeile, neue Position.** Ändert sich durch ein Live-Update der Sortierwert einer Zeile (z. B. der Nachname), bleibt sie an ihrer Position. Sie springt erst nach einem Neuladen an die richtige Stelle, damit beim Lesen nichts verspringt.

## Vorhandene Tabellen

| Klasse | Seite(n) | Prop | Suche | Live-Update |
|---|---|---|---|---|
| `UsersTable` | Admin → User-Verwaltung | `users` | Vorname, Nachname, E-Mail | geänderte Zeilen |
| `RegistrationsTable` | Admin Anmeldungen, Admin/Tutor Event (Typ `event_registration`), Tutor Slot, Tutor Gruppe | `registrations` | Vorname, Nachname, E-Mail des Users | geänderte Zeilen (inkl. User-Änderungen) |
| `EventsTable` | Tutor-Übersicht | `events` | – | alle Zeilen (Teilnehmerzahl) |
| `GroupsTable` | Admin/Tutor Event (Typ `group_phase`) | `groups` | – | alle Zeilen (Teilnehmerzahl) |
| `SlotsTable` | Admin/Tutor Event (Typ `slot_booking`) | `slots` | – | alle Zeilen (Teilnehmerzahl) |
| `ArticlesTable` | Admin → Strichliste | `articles` | – | alle Zeilen (Buchungsanzahl) |
| `KioskPersonsTable` | Strichliste Kiosk (Kartenansicht, nicht `AppTable`) | `persons` | Vorname, Nachname | – (48 pro Seite, Filter `course` und `role`) |

## Tests

```bash
php artisan test --compact tests/Feature/TableTest.php
```

[`tests/Feature/TableTest.php`](../tests/Feature/TableTest.php) prüft unter anderem:

- **Laden:** Seiten ohne Überschneidung, stabile Reihenfolge über Seitengrenzen hinweg, ungültige Sortierschlüssel werden ignoriert.
- **Suche und Sortierung:** Suche mit mehreren Wörtern, jeder Sortierschlüssel in beide Richtungen.
- **Live-Update:** geänderte, gelöschte und neue Zeilen; Löschungen per Cascade; Rollenwechsel; Beachtung der Suche.
- **Rechte und Bereich:** Berechtigungen, Bereich der Tutor-Slot- und Gruppen-Seiten.
- **Performance:** Beim Nachladen werden die geteilten Props nicht berechnet.
