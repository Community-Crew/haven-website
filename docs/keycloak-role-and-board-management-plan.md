# Keycloak-synced role management + commission-scoped board positions

Implementation plan for `haven-website`. Nothing in this plan has been built — the repo is untouched (`main`, clean).

## Goal

1. Manage Spatie/Filament roles from Filament with a real write-path back to Keycloak (create/delete a role ⇒ create/delete the matching Keycloak group; assign a user to a role in Filament ⇒ add/remove them from that Keycloak group), instead of the current one-way sync (Keycloak → app, on login only).
2. A structured "board position" concept (Chair, Secretary, Treasurer, commission-specific roles like "Garden Commission Coordinator") that:
   - Drives permissions, scoped **per commission** (a Garden Commission chair must not get Finance Commission chair powers).
   - Can require an NDA to be signed **for that specific position** before its permissions activate; getting a new position means signing a new one. NDA *text* itself is commission-level by default (`Organisation.nda_text`, shared by every `requires_nda` position in it), with an optional per-position override (`BoardPosition.nda_text`) for a function that genuinely needs different wording — see `BoardPosition::ndaText()`.
   - Feeds a public "board members" page.

## Current state (context, not to be changed by this plan except where noted)

- `App\Http\Controllers\Auth\KeycloakAdminController::callback()` is the *only* place Spatie roles are set today: it reads the Keycloak `groups` claim on every admin login, `Role::firstOrCreate()`s each as a flat dash-joined name (`/board/finance` → `board-finance`), and `syncRoles()`s the user. One-way, Keycloak → app.
- `bezhansalleh/filament-shield` is installed and auto-registers a Role resource at `shield/roles` (permission bundles). No Keycloak write-back exists anywhere.
- `App\Models\Organisation` (`organisations` table, `users` belongsToMany) is the existing "commission" concept per your confirmation — reuse it, don't create a new model.
- `memberships` table (from `2026_08_07_223000_create_member_types_and_memberships_tables.php`, already in production) has an unused `board_role` free-text column — replace it, don't leave both.
- "Best-effort external API call, log + don't block on failure" is the pattern to copy for Keycloak calls (see `RoleObserver`/`BoardPositionObserver`/`MembershipObserver`, all built as part of this plan, for the shape). An earlier version of this note pointed at `App\Observers\UserObserver`/`App\Services\DiscordService` as the reference - both removed (Discord integration deferred to later work), the pattern itself is unaffected.
- `config/services.php`'s `keycloak` block only has the OIDC login client (`KEYCLOAK_CLIENT_ID`/`SECRET`) — no admin API credentials exist yet.

## Part A — Keycloak Admin API service

**Infra step (do this in Keycloak's admin console first, not code):** create a second confidential client (separate from the login client), enable its service account, and grant it the `realm-management` client roles `manage-users` (group CRUD + membership) and `query-groups`/`view-users` (listing).

**Code:**
- `.env.example` / `config/services.php`: add `KEYCLOAK_ADMIN_CLIENT_ID` / `KEYCLOAK_ADMIN_CLIENT_SECRET` under the existing `keycloak` config block, reusing `base_url`/`realms`.
- `app/Services/Keycloak/KeycloakAdminService.php` (raw `Http::` calls, shared `throwIfFailed()` via `App\Traits\LogsFailedHttpResponses`):
  - `token(): string` — client_credentials grant against `{base_url}/realms/{realm}/protocol/openid-connect/token`, `Cache::remember()`'d ~50s.
  - `findOrCreateGroupPath(string $path): array` (`{id, name}` of the leaf group) — groups are **hierarchical**, not flat: `path` is slash-separated (e.g. `commission/garden/chair`, `board/chair`), matching the nested layout `KeycloakAdminController::callback()` already parses on the read side. Keycloak has no "create by path" endpoint, so this walks the path segment by segment, resolving-or-creating each one as a child of the last via the group-children endpoints (`GET`/`POST /groups(/{parentId}/children)`); group creation returns 201 with an empty body, so the new id comes from the `Location` header, not JSON.
  - `deleteGroup(string $keycloakGroupId): void` — deletes by id; Keycloak cascades to subgroups, but a now-empty parent (e.g. deleting `chair` under `commission/garden`) is deliberately left in place rather than pruned.
  - `addUserToGroup(string $keycloakUserId, string $keycloakGroupId): void`, `removeUserFromGroup(...)`.

## Part B — Role ↔ Keycloak group linkage

- Migration: nullable unique `keycloak_group_id` string column on Spatie's `roles` table (same pattern as `2026_05_30_174122_add_role_to_reservation_policies_table.php`, which already FKs to `roles` — that migration is a good style reference generally).
- `app/Observers/RoleObserver.php` (mirrors `UserObserver`): `created()` skips if `keycloak_group_id` is already set (Part E's `BoardPositionObserver` provisions hierarchical roles itself, see below); otherwise treats the flat `$role->name` as a single-segment (top-level) path, calls `KeycloakAdminService::findOrCreateGroupPath($role->name)`, and `saveQuietly()`s the returned id back — this covers ad-hoc roles created directly in Filament's Shield resource, which have no commission/board-position hierarchy to express. `deleting()` best-effort `deleteGroup()`. Both log + Filament danger notification on failure, never block the local save.
- Register with `Role::observe(RoleObserver::class)` in `AppServiceProvider::boot()` (Spatie's `Role` is a package model, so it can't use the `#[ObservedBy]` attribute the way `User` does).

## Part C — User ↔ Keycloak group membership, centralized

Build `app/Services/UserRoleSyncService.php` once, reused by both Part C's Filament form and Part E's commission-position logic — don't duplicate the Keycloak diff/add/remove logic in two places:

- `addRole(User $user, Role $role): void` — local `assignRole()`, then if `$user->keycloak_id` and `$role->keycloak_group_id` are both set, best-effort `KeycloakAdminService::addUserToGroup()`.
- `removeRole(User $user, Role $role): void` — mirror, `removeRole()` + `removeUserFromGroup()`.
- Both best-effort on the Keycloak half: try/catch, log + Filament notification on failure, never roll back the local change.

`UserForm` (`app/Filament/Resources/Users/Schemas/UserForm.php`) gets a `Select::make('role_ids')->multiple()->preload()`, dehydrated `false`, manually filled from `$record->roles->pluck('id')` — not `->relationship()` auto-sync, so the diff can go through `UserRoleSyncService` instead of a raw pivot `sync()`. `EditUser`'s `afterSave()` diffs old vs new ids and calls `addRole`/`removeRole` per delta. (No `CreateUser` page exists — users only originate from registration/Keycloak login — so this is edit-only.) If `$user->keycloak_id` is null, skip Keycloak calls silently with an inline note ("no Keycloak account yet").

## Part D — `board_positions` (global + per-commission)

Migration `create_board_positions_table`:
- `name` (string)
- `organisation_id` (nullable FK → `organisations`, cascade/nullOnDelete) — **null = global board position** (Voorzitter, Secretaris, Penningmeester); **set = that commission's own position**, freely named by whoever creates it there. One shared table, scoped by this column, per your confirmation — no separate per-commission admin UI/model.
- `shield_role_id` (nullable FK → `roles`) — see Part E for how this gets populated for commission-scoped positions.
- `sort_order` (unsignedInteger, default 0)
- `requires_nda` (boolean, default false) — see Part F.

Unique constraint on (`name`, `organisation_id`) so the same name can exist once globally and once per commission, not duplicated within one scope.

`app/Models/BoardPosition.php`: `belongsTo(Organisation::class)`, `belongsTo(Role::class, 'shield_role_id')` as `shieldRole()`, `hasMany(Membership::class)`.

Filament: new `BoardPositionResource` (simple CRUD: name, organisation select, sort_order, requires_nda toggle; `shield_role_id` is derived/managed by Part E's logic, not hand-picked here for commission-scoped positions — see below). Nav group `Members`.

**Part D½ — commission creation, added after the fact:** no Filament resource for `Organisation` existed at all before this. Added `CommissionResource` (nav group `Members`, simple CRUD: name, `about`, cover image, `is_commission` toggle) - named after "commission" for the admin-facing concept, but it manages `App\Models\Organisation` rows, unchanged, since that table is also used more broadly (`AgendaItem`/`Reservation`/`User` already reference it, not commission-specifically) - a full model rename was considered and rejected as too large/conceptually wrong for what's really just this one resource's naming. Policy/permissions stay named after the model (`OrganisationPolicy`, `*:Organisation`) per Filament Shield's model-based convention, regardless of the resource class's name.

Added `organisations.is_commission` (boolean, default `true`) to distinguish an actual commission from an external organisation stored in the same table (the landlord Vestide, etc.) - those don't get default positions/roles. `OrganisationObserver::created()` checks this flag first, then (for real commissions) seeds two default `BoardPosition`s, `Voorzitter` and `Lid`, each of which flows straight into Part E's auto-provisioning (organisation-scoped, no `shield_role_id` set yet) - no separate seeding logic needed. Best-effort/logged/notified on failure, same pattern as everywhere else, never blocks the commission itself from saving.

## Part E — Per-commission role scoping

Mechanism: **one auto-generated Spatie role per position+organisation combo** (e.g. `commission-chair-garden`), the same flat-role convention `KeycloakAdminController` already uses for nested Keycloak groups — no Spatie "teams" feature, no new package config.

- For a **global** `BoardPosition` (`organisation_id` null): `shield_role_id` is picked directly by an admin in `BoardPositionResource` from the existing role list (e.g. "Voorzitter" → a "board" role) - `BoardPositionObserver` does nothing in this case.
- For a **commission-scoped** `BoardPosition` (`organisation_id` set, `shield_role_id` not already set): on create, `BoardPositionObserver::created()` computes the **hierarchical Keycloak path** - `commission/{organisation-slug}/{position-slug}` - matching the nested layout `KeycloakAdminController::callback()` already parses on login. It calls `KeycloakAdminService::findOrCreateGroupPath($path)` directly (not via `RoleObserver`, which only ever sees a flat name), then `Role::firstOrCreate(['name' => str_replace('/', '-', $path)], ['keycloak_group_id' => $group['id']])` - the **same flattening** the login callback applies to a Keycloak group path, so a role provisioned here and the same group later re-read from a Keycloak login resolve to the identical flat name instead of drifting into duplicate roles. `keycloak_group_id` has to be passed in `firstOrCreate`'s *creation* values, not set via a follow-up save - `RoleObserver::created()` fires synchronously during that same creation, so its `keycloak_group_id`-already-set guard only pre-empts the flat group it would otherwise create if the id is already present on the model *at creation time*. Set `shield_role_id` to the resulting role. Do this in `BoardPositionObserver::created()`, not inline in the resource, so it also covers `BoardPosition::create()` from tinker/seeders.
- Some commissions have "more admin roles" than others (your words) — this falls out naturally: each commission just creates as many `BoardPosition` rows as it needs (e.g. Garden Commission might have "Coordinator" + "Member", Finance might add "Auditor" too) — nothing hardcoded, no fixed enum.

## Part F — Per-position NDA gating

**Built:**
- Migration `create_board_position_signatures_table`: `user_id` (FK, cascade), `board_position_id` (FK, cascade), `signed_at` (nullable timestamp), `external_reference` (nullable string). **Decided:** NDAs get signed via Google Workspace's built-in eSignature (Docs/Drive, free on the current plan) - but it has **no public API** (confirmed: there's an open Google Issue Tracker feature request for exactly this, google's own acknowledged gap), so it can't be triggered or polled from code. `BoardPositionSignatureResource`'s `mark_signed` action (already built) - an admin sees it's signed in Drive and confirms in Filament - is therefore the actual plan, not a fallback. `external_reference` holds a link/doc id to the signed Drive file for record-keeping. A real API-driven flow (DocuSign, Dropbox Sign, etc.) is a possible future upgrade if the added cost/contract is ever worth trading for automation, not something to build now.
- `app/Models/BoardPositionSignature.php`, simple `belongsTo` both ways. Reciprocal `signatures()` on `BoardPosition` and `boardPositionSignatures()` on `User`.
- `BoardPosition::ndaSatisfiedFor(User $user): bool` — the gating check itself: `true` outright if `! $this->requires_nda`, otherwise whether a `BoardPositionSignature` with non-null `signed_at` exists for that user+position. Pure/standalone, no dependency on `Membership`.
- `nda_text` (nullable text) on both `organisations` and `board_positions`. `BoardPosition::ndaText(): ?string` resolves the effective text - its own if set, else its commission's, else `null` (a global position with no text set has none). This is display-only (for showing the actual NDA content wherever a signature gets recorded) - `ndaSatisfiedFor()`'s gating logic doesn't care what the text says, only whether `signed_at` is set.
- Filament: `BoardPositionSignatureResource` (its own small resource, not an inline action on the membership form — that form doesn't have a `board_position_id` field to hang it off yet, see Part G). `user_id`/`board_position_id` are create-only (they're the unique pair; "moving" a signature by editing them would just orphan it). `signed_at` uses the same mark/unmark-with-a-status-line pattern as `activated_at` in `UserForm` (`mark_signed`/`unmark_signed` actions), not a raw date field.

**Deferred to Part G:** actually calling `ndaSatisfiedFor()` to gate `UserRoleSyncService::addRole()`, and re-running it to grant retroactively when an admin marks a signature signed, both belong in `MembershipObserver` reacting to `Membership.board_position_id` — which doesn't exist until Part G's migration lands. `unmark_signed` deliberately doesn't attempt a revoke for the same reason (nothing to revoke against yet). Building that gating call now would mean querying a column that doesn't exist.

## Part G — `memberships` table changes

Migration `replace_board_role_with_board_position_on_memberships_table`:
- Drop `board_role` (unused free text, confirmed safe to remove).
- Add `board_position_id` (nullable FK → `board_positions`, nullOnDelete).
- Add `is_public` (boolean, default true) — lets an admin keep a position on record without publishing it.
- Add `sort_order` (unsignedInteger, default 0) — org-chart display order among public holders.

`Membership` model: swap `board_role` for `board_position_id` in `$fillable`, add `boardPosition(): BelongsTo`.

`MembershipForm` + `MembershipsRelationManager` (both currently have a `TextInput::make('board_role')` — search for that string, it appears in both places and in `MembershipsTable`): replace with `Select::make('board_position_id')->relationship('boardPosition', 'name', fn ($q) => $q->orderBy('sort_order'))->searchable()->preload()->live()`, plus the new `is_public`/`sort_order` fields (`visible()` only when a position is picked).

Extend the existing `App\Observers\MembershipObserver` (currently only handles `updated()` for status-change emails) to also react to `board_position_id` changes: on the old position (if any), `UserRoleSyncService::removeRole()` for its `shield_role_id`; on the new position (if any), `addRole()` — subject to the Part F NDA check. Also handle status transitioning to `ENDED`/non-open (or the membership being deleted) by removing the role — a user only ever has one open membership at a time (already enforced in `MembershipForm::statusComponents()`), so this is a clean 1:1 swap with no cross-membership conflicts to worry about.

## Part H — Public API

`GET /api/v1/board` → new `App\Http\Controllers\Api\Board\BoardIndexController`, unauthenticated, grouped with the other public reads (`/rooms`, `/agendas`) in `routes/api.php`. Query: `Membership::whereNotNull('board_position_id')->where('is_public', true)->whereIn('status', MembershipStatus::open())->with(['user:id,name', 'boardPosition:id,name,organisation_id'])->orderBy('sort_order')->orderBy('joined_at')->get()`, mapped through a small `App\Http\Resources\Api\BoardMemberResource` (`name`, `title` = position name, maybe `organisation` name if you want commission board members grouped on the public page — decide at build time based on what the frontend page needs). Frontend consumption (an actual public board page in `HavenWebsite-Frontend`) is a separate follow-up, not covered here.

## Suggested build/migration order

1. Part A (Keycloak client set up in console + `KeycloakAdminService`, can be tested standalone with `Http::fake()`).
2. Part B (`roles.keycloak_group_id` + `RoleObserver`).
3. Part C (`UserRoleSyncService` + `UserForm`/`EditUser` wiring) — this alone already delivers "manage roles + Keycloak sync from Filament" for the non-commission case.
4. Part D (`board_positions` table + model + resource) — global positions only first (Voorzitter etc.), skip `organisation_id`/NDA columns initially if you want a smaller first PR.
5. Part E (commission scoping + `BoardPositionObserver` auto-provisioning roles).
6. Part F (NDA signatures + gating).
7. Part G (`memberships` cutover from `board_role`).
8. Part H (public endpoint).

Parts 4-8 can also ship as one PR if you'd rather not stage it — they're tightly coupled (the `memberships` cutover in particular doesn't make sense without `board_positions` existing first).

## Verification

- Pest feature tests: `KeycloakAdminService` via `Http::fake()` (token fetch, group CRUD, membership add/remove, and that a faked failure logs a warning without throwing).
- `RoleObserver` / `BoardPositionObserver` tests: creating a `Role`/commission-scoped `BoardPosition` triggers the faked Keycloak group creation; a faked failure doesn't block the row from persisting.
- `UserRoleSyncService` test: a user with `keycloak_id` set gets the right Keycloak calls on role add/remove; a user without one never triggers a Keycloak call.
- `MembershipObserver` test: changing `board_position_id` grants/revokes the right role; an unsigned-NDA position doesn't grant the role until a `BoardPositionSignature.signed_at` is set, then does.
- `BoardIndexController` test: only `is_public` + currently-open-membership positions show, in `sort_order`.
- Manual: `vendor/bin/sail artisan tinker` to walk through creating a commission `BoardPosition`, assigning a membership to it, marking an NDA signed, and confirming the role/Keycloak group appear at each step; `curl` `/api/v1/board` once seeded.
- `vendor/bin/pint` before calling it done, per repo convention.
