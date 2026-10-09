# CSN Emergency Alert System — Backend Development TODO

This tracks what still needs to be built behind each Blade module. The frontend (Blade + Tailwind + Alpine) is done and reads its data from `@json(...)` variables passed by the controller — everything below is what feeds those variables.

---

## 0. Foundation (do this first — everything else depends on it)

- [ ] `composer require laravel/breeze` (or Fortify/Jetstream) for auth — Guardians, CSN Staff, and Responders all need to log in.
- [ ] `composer require spatie/laravel-permission` — you have three roles (`guardian`, `staff`, `responder`) plus an `admin`/coordinator role for the dashboard itself. Gate each module behind role middleware.
- [ ] Decide on real-time: **Laravel Reverb** (`php artisan install:broadcasting`) is the natural fit since the sidebar already claims "Reverb Live · Connected." Without it, the dashboard is just a page you have to refresh.
- [ ] `composer require spatie/laravel-activitylog` — powers the Settings module's audit log for free instead of hand-rolling one.

---

## 1. Dashboard (`overview.blade.php`)

**Data needed:** nothing new — it's entirely derived from the `emergencies`, `children`, and `activityLog` collections the other modules already produce. No dedicated controller logic required beyond passing those through.

**The map — this is the one part that's currently fake and needs a real API:**

The current markup draws SVG dots at placeholder percentage positions. Replace it with an actual map. Two realistic options:

- **Leaflet + OpenStreetMap** — free, no API key, good default for a government/LGU system with a fixed budget.
- **Google Maps JavaScript API** — nicer visuals and geocoding, but needs a billed API key.

Go with Leaflet unless you specifically need Google's geocoding/Street View. Example swap-in (loaded via the same CDN pattern the rest of the app uses):

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<div id="emergency-map" class="h-80 rounded-lg" wire:ignore x-init="
    const map = L.map($el).setView([14.4793, 121.0198], 14); // Parañaque center
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const markers = {};
    function syncMarkers() {
        emergencies.forEach(e => {
            if (!e.lat || !e.lng) return; // needs real coordinates from the DB
            if (!markers[e.id]) {
                const color = e.status === 'resolved' ? '#10B981' : (e.urgency === 'high' ? '#D9534F' : '#F59E0B');
                markers[e.id] = L.circleMarker([e.lat, e.lng], { radius: 8, color, fillColor: color, fillOpacity: 0.9 })
                    .bindPopup(`&lt;b&gt;${e.child.name}&lt;/b&gt;&lt;br&gt;${e.child.condition}&lt;br&gt;${e.location}`)
                    .addTo(map);
            }
        });
    }
    syncMarkers();
    $watch('emergencies', () => syncMarkers());
"></div>
```

To make this work you need real coordinates, which means:

- [ ] Add `lat` and `lng` decimal columns to the `emergencies` table.
- [ ] Populate them either from a GPS-enabled guardian/staff app, or by geocoding the `location` text field server-side (Leaflet's Nominatim, or Google Geocoding API) when an alert is created.
- [ ] Pass `lat`/`lng` through in the `emergencies` JSON the controller already sends — no new endpoint needed, just extra fields on the existing model.

---

## 2. Emergency Center (`emergency-center.blade.php`)

**Model:** `Emergency`
```
id, type (enum: medical_crisis, seizure_sensory, fire, wandering), urgency (high/low),
status (pending/responding/resolved), child_id (FK), location, lat, lng,
guardian_notes, reported_at, acknowledged_at, dispatched_at, resolved_at
```

**Routes/controller — replace the local-state-only `acknowledge()`, `dispatch()`, `resolve()` in `app.blade.php` with real calls:**
```php
Route::patch('/emergencies/{emergency}/acknowledge', [EmergencyController::class, 'acknowledge']);
Route::patch('/emergencies/{emergency}/dispatch', [EmergencyController::class, 'dispatch']);
Route::patch('/emergencies/{emergency}/resolve', [EmergencyController::class, 'resolve']);
Route::post('/emergencies', [EmergencyController::class, 'store']); // triggered by guardian/staff app, not this dashboard
```

Each Alpine method becomes something like:
```js
async acknowledge(id) {
    const res = await fetch(`/emergencies/${id}/acknowledge`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const updated = await res.json();
    const e = this.emergencies.find(e => e.id === id);
    if (e) Object.assign(e, updated);
}
```

**To-do list:**
- [ ] Build `EmergencyController` with the four actions above.
- [ ] On `store()`, broadcast an `EmergencyRaised` event over Reverb so the dashboard updates without a refresh (this is what should replace the old "Simulate" button conceptually — real alerts come from a mobile app or panic-button device, not this admin dashboard).
- [ ] Decide who can create an emergency: a guardian mobile app, a physical panic button integration, or CSN staff manually logging one from this dashboard (add a "Log Emergency Manually" form if the latter).
- [ ] SMS/push notification to the assigned PDRRMO unit on dispatch — Twilio (`laravel-notification-channels/twilio`) is the standard choice in PH deployments.

---

## 3. Child Profiles (`child-profiles.blade.php`)

**Model:** `ChildProfile`
```
id, name, age, condition, guardian_id (FK to users), emergency_contact,
medical_notes (text), photo_path, created_by, updated_at
```

**Routes:**
```php
Route::resource('children', ChildProfileController::class)->except(['create', 'edit']);
```

**To-do list:**
- [ ] Wire the "Add New Child Profile" form's save button to `POST /children` instead of the current `children.push(...)` (there's already a `// TODO` comment in the Blade file marking exactly where).
- [ ] Add photo upload (`Storage::disk('public')`) — the avatar currently just shows initials; decide if you want real photos.
- [ ] **Privacy:** medical notes and disability info are sensitive personal data under the Philippine Data Privacy Act. Encrypt the `medical_notes` column at rest (`'encrypted'` cast on the model) and restrict read access by role.
- [ ] Server-side search (`WHERE name LIKE ? OR condition LIKE ?`) once the child list is too large to filter client-side — the current `filteredChildren` Alpine getter only works because it assumes the whole list is already loaded.

---

## 4. User Management (`user-management.blade.php`)

**Model:** extend the existing `User` model (from Breeze) with a `role` column, or use `spatie/laravel-permission` roles instead of a plain enum column — the latter scales better if you add sub-permissions later (e.g. "responder can dispatch but not edit children").

**Routes:**
```php
Route::resource('users', UserController::class)->except(['create', 'edit']);
```

**To-do list:**
- [ ] Wire the role tabs (`guardian`/`staff`/`responder`) to actual `role` values on the `users` table.
- [ ] Wire the "Edit" modal's save button to `PATCH /users/{user}` — right now it just closes the modal (`editUser = null`) without persisting anything.
- [ ] Add an invite/onboarding flow for new staff and responders (email invite → they set their own password) rather than an admin typing one in.
- [ ] Status toggle (active/inactive) should probably soft-disable login, not just be a display flag — check `is_active` in your auth guard.

---

## 5. Incident Reports (`incident-reports.blade.php`)

**Model:** `Incident` — mostly a resolved-and-archived view of `Emergency` records, so consider making this a query/scope on `Emergency` (`status = 'resolved'`) rather than a separate table, unless you need incident-specific fields (post-incident summary, follow-up actions) that don't belong on the live emergency record.

**Exports — both buttons currently just show a fake success toast:**
- [ ] PDF: `composer require barryvdh/laravel-dompdf`, then
  ```php
  Route::get('/incident-reports/export-pdf', function () {
      $pdf = Pdf::loadView('reports.incidents-pdf', ['incidents' => Incident::all()]);
      return $pdf->download('incident-report.pdf');
  });
  ```
- [ ] CSV: `composer require league/csv`, then stream a `StreamedResponse` with `League\Csv\Writer`.
- [ ] Point the Alpine `exportMsg` buttons at these routes (`window.location = '/incident-reports/export-pdf'` is enough for a simple download; no fetch needed since it's a file download, not JSON).

---

## 6. Settings & Support (`settings.blade.php`)

**Model:** `Setting` (simple key-value table) for the sound toggles and hotline numbers; `spatie/laravel-activitylog`'s built-in `activity_log` table for the audit log (no custom model needed if you use the package).

**Routes:**
```php
Route::patch('/settings', [SettingController::class, 'update']);
```

**To-do list:**
- [ ] Persist `soundOnHigh`, `soundOnLow`, `hotlinePDRRMO`, `hotlineAmbulance` on save instead of only living in Alpine state (currently lost on refresh).
- [ ] If you adopt `spatie/laravel-activitylog`, wrap the `Emergency`, `ChildProfile`, and `User` models in `LogsActivity` so entries appear automatically instead of being written by hand.
- [ ] ISO 27001 mention in the UI implies you'll eventually need log retention policy + access controls on who can view the audit log — worth scoping with whoever owns compliance before launch.

---

## Suggested build order

1. Auth + roles (Foundation section)
2. `Emergency` + `ChildProfile` models/migrations — everything else references these
3. Emergency Center CRUD + Reverb broadcasting (this is the core value of the app)
4. Dashboard map wiring (needs `lat`/`lng` from step 2)
5. Child Profiles CRUD
6. User Management CRUD
7. Incident Reports exports
8. Settings persistence + audit log

---

## 7. Guardian Mobile App API (`routes/api.php`) — DONE

Built and verified API endpoints for the React Native / Expo Guardian Mobile application:

- [x] **Sanctum API Authentication**: Installed `laravel/sanctum`, added `HasApiTokens` to `User` model, migrated `personal_access_tokens`.
- [x] **Auth Endpoints** (`App\Http\Controllers\Api\AuthController`):
  - `POST /api/auth/register` — self-registration for guardians, returns token and guardian profile.
  - `POST /api/auth/login` — credentials verification, returns token, guardian profile, and child profile.
  - `POST /api/auth/forgot-password` — password reset flow.
  - `GET /api/me` — returns authenticated guardian and formatted child profile.
- [x] **Child Medical Profile** (`App\Http\Controllers\Api\ChildApiController`):
  - `PUT /api/child` — creates/updates child profile with name, age, condition/disability, medical notes, emergency contacts, and updates guardian phone.
- [x] **Emergency SOS Flow** (`App\Http\Controllers\Api\EmergencyApiController`):
  - `POST /api/emergencies` — triggers SOS alert with GPS coordinates, loads child profile, and broadcasts `EmergencyRaised` event to Reverb listeners in real time.
  - `GET /api/emergencies` — lists alert history for the guardian.
  - `GET /api/emergencies/{emergency}` — checks active emergency status and assigned responder details (name, unit, ETA).
  - `PATCH /api/emergencies/{emergency}/location` — live GPS coordinate stream while emergency is active.
  - `POST /api/emergencies/{emergency}/cancel` — false-alarm cancellation flow.
- [x] **Announcements Inbox** (`App\Http\Controllers\Api\AnnouncementApiController`):
  - `GET /api/announcements` — lists broadcasts and announcements from the CSN Center with unread tracking.