# Anchor — status

(Formerly "Paperlist"/"Simple ToDo" — renamed to Anchor.) Original task list and rationale:
`C:\Users\Justin\.claude\plans\peaceful-splashing-codd.md`.

## Status: done

Backend and frontend are both complete: board CRUD + sharing + notes, a real Boards overview page,
one consolidated app shell/layout, working Ctrl/Cmd+K search, and full account settings — all
reachable from a single persistent sidebar.

### Backend (`php artisan test` 59/59 passing, `vendor/bin/phpstan analyse` 0 errors)

- UUID primary keys, `priority` field, reorder endpoint, Form Requests/Policies (original plan).
- **Board sharing**: `board_user` pivot (`role`: viewer/editor), `BoardPolicy`/`TaskPolicy`
  (owner/editor/viewer semantics), `BoardShareController`, `tests/Feature/BoardSharingTest.php`.
- **Board rename**: `BoardController::update` (`PATCH boards/{board}`), owner+editor only.
- **Notes**: `Note` model/migration, `NotePolicy` (delegates to board via `Gate`), `NoteController`,
  routes under `boards/{board}/notes` and `notes/{note}`. Included in `BoardResource`.
- **`App\Http\Resources\BoardResource`**: single source of truth for how a board is serialized
  (id/name/icon/tasks/notes/isOwner/role/collaborators) — used by every controller action that
  returns board JSON (`store`/`import`/`update` in `BoardController`, `WorkspaceController`).
  Previously `store()`/`import()` returned bare models missing `notes`/`role`/`collaborators`,
  which crashed the new Boards overview page on a freshly created board — fixed by this resource.
- **Single-board page model**: `WorkspaceController::show()` now returns one `board` (not an array
  of every accessible board) — the sidebar's own board list comes from a separate lightweight
  shared prop (see below), so `Workspace.vue` only ever needs the board currently being viewed.
- **`WorkspaceController::index()`** (`GET boards` → `boards.index`): powers the Boards overview
  page, returns all owned+shared boards via `BoardResource::collection`.
- **`Board::scopeAccessibleBy(User $user)`**: shared query scope (owned ∪ shared-with) used by both
  `WorkspaceController` and the sidebar's shared-prop query.
- **`HandleInertiaRequests::share()`**: adds a `sidebarBoards` prop (id/name/icon/openTasksCount/
  isOwner/role) computed for every authenticated request — this is what the global sidebar renders,
  independent of whichever page is currently showing.

### Frontend

**Layout consolidation** (this session's main ask): there is now exactly one app shell.
`resources/js/components/AppSidebar.vue` was rewritten (Anchor branding, working Ctrl/Cmd+K search
trigger, live "Boards" list from the `sidebarBoards` shared prop, "New board" dialog) and is used
by `Boards.vue`, all Settings pages, **and** `Workspace.vue` (via `AppSidebarLayout`, for
authenticated users — guests still get a minimal bare shell since they only ever have one local
board and aren't part of "the app" yet). Deleted dead code that predates this: `AppHeaderLayout.vue`,
`AppHeader.vue`, `NavMain.vue`, `NavFooter.vue` (all unused once the sidebar was rebranded).

- `resources/js/components/CommandSearch.vue`: Ctrl/Cmd+K quick-switcher, filters `sidebarBoards`
  by name, navigates via `router.visit`.
- `resources/js/components/BoardContent.vue`: the actual "viewing one board" UI (tasks/notes tabs,
  priority, drag-and-drop, board menu with rename/share/delete) — extracted out of `Workspace.vue`
  so it can render inside both the authenticated layout and the guest bare shell without
  duplicating ~250 lines of markup.
- `resources/js/components/CreateBoardDialog.vue` / `RenameBoardDialog.vue` /
  `ShareBoardDialog.vue`: shared dialogs used by `AppSidebar.vue`, `Boards.vue`, and
  `BoardContent.vue` (previously each page hand-rolled its own copy).
- `resources/js/pages/Workspace.vue`: now just holds the guest-vs-authenticated branch and the
  guest local-storage board; ~150 lines instead of ~500.
- Real breadcrumbs (`Boards > {board name}`) via the shared `AppSidebarHeader`, everywhere.
- Profile menu is a dropdown (click the sidebar's user row → Settings / Log out via the existing
  `UserInfo`/`UserMenuContent` components) — no separate icon buttons, no profile photos (avatar
  falls back to initials since `User` has no avatar field).
- App renamed **Paperlist/Simple ToDo → Anchor**: `.env`/`.env.example` `APP_NAME`,
  `config/app.php` default, `AppLogo.vue`, brand tokens unchanged (still `--brand`/
  `--brand-foreground` green).

**Known easy-to-hit gotcha**: any board mutation that goes through the page-local
`request()`/`apiRequest()` AJAX helper (not a full Inertia visit) does **not** automatically refresh
the `sidebarBoards` shared prop. Rename and task add/toggle/delete explicitly call
`router.reload({ only: ['sidebarBoards'] })` after success — if a new mutation type is added that
changes a board's name or task count, it needs the same call or the sidebar/search will show stale
data until the next full navigation.

**Wayfinder regen note**: `php artisan wayfinder:generate` alone drops the `.form()` helpers used
by `Form v-bind="Controller.method.form()"` on the auth/settings pages — always pass `--with-form`
(the Vite plugin does this automatically at build time; only matters if regenerating by hand).

### Verified

- `php artisan test`, `vendor/bin/phpstan analyse`, `npx vue-tsc --noEmit`, `npx eslint .`,
  `npm run build` all clean.
- Full Playwright pass against `https://todo.test`: board CRUD/rename/share/delete from both the
  sidebar and the Boards overview grid, notes CRUD, drag-and-drop persisting across reload, Ctrl+K
  search (including that it reflects a rename made moments earlier — this caught the staleness bug
  above), breadcrumbs, Settings reachable via the profile dropdown with the same sidebar visible,
  dark mode, guest local-board mode, and owner/editor/viewer permission enforcement all confirmed
  working with screenshots. One real bug found and fixed by this pass (the `BoardResource`
  gap above) plus the `sidebarBoards` staleness gap.

## Key files

- `app/Http/Controllers/BoardController.php`, `WorkspaceController.php`, `BoardShareController.php`,
  `NoteController.php`
- `app/Http/Resources/BoardResource.php`
- `app/Http/Middleware/HandleInertiaRequests.php` (`sidebarBoards` shared prop)
- `app/Policies/BoardPolicy.php`, `TaskPolicy.php`, `NotePolicy.php`
- `app/Http/Requests/*.php`
- `app/Models/Board.php` (incl. `scopeAccessibleBy`), `Task.php`, `Note.php`, `User.php`,
  `BoardUser.php`
- `app/Enums/TaskPriority.php`, `BoardRole.php`
- `routes/web.php`
- `resources/js/pages/Workspace.vue`, `Boards.vue`
- `resources/js/components/AppSidebar.vue`, `BoardContent.vue`, `CommandSearch.vue`,
  `CreateBoardDialog.vue`, `RenameBoardDialog.vue`, `ShareBoardDialog.vue`, `AppLogo.vue`
- `resources/js/types/board.ts`, `resources/js/lib/boardApi.ts`
- `resources/css/app.css`
- `tests/Feature/DashboardTest.php`, `BoardSharingTest.php`, `BoardManagementTest.php`, `NoteTest.php`
