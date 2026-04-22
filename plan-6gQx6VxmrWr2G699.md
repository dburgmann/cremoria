# Plan: Refactor Cremoria from Kohana to Laravel

## Context

Cremoria is a browser-based strategy game built on Kohana 2.x — a PHP framework that has been unmaintained since ~2013. The game features resource management, building construction, unit recruitment, technology research, army movements with a tick-based combat system, espionage, and a messaging system. The codebase is small and well-structured, which makes this a tractable migration.

This plan covers every architectural decision for the migration to Laravel 11 (current stable), with opinionated recommendations throughout.

---

## 1. Architecture Review

### 1a. Overall System Design

The current architecture has three distinct layers that map cleanly to Laravel conventions:

| Kohana Layer | What it is | Laravel equivalent |
|---|---|---|
| Controllers (`Login_Controller`, `Construction_Controller`, etc.) | HTTP request handlers | `app/Http/Controllers/` |
| Models (`User_Model`, `Town_Model`, etc. — Kohana ORM) | Active Record objects | Eloquent models in `app/Models/` |
| Libraries (`Battle`, `ConstructionHandler`, `ProductionHandler`, etc.) | Domain service objects | `app/Services/Game/` |
| Views (plain PHP templates) | HTML output | Blade templates in `resources/views/` |
| Tick Controllers (`TickEconomy`, `TickMovement`, `TickScore`) | Cron-driven batch jobs | Artisan commands + Laravel Scheduler |
| IniORM | In-memory config/ini data store | `GameDataRepository` backed by PHP config arrays |

**Recommendation:** adopt a clear `app/Services/Game/` layer for all game logic (Battle, Army, Espionage, handlers), keeping HTTP controllers thin. This mirrors what the Kohana libraries already do and keeps the game engine independently testable.

### 1b. Kohana-Specific Patterns to Replace

| Kohana construct | Laravel replacement |
|---|---|
| `Auth::instance()`, `Auth_User_Model`, `roles`/`roles_users` tables | Laravel Breeze auth scaffold + `spatie/laravel-permission` for roles |
| `ORM::factory('model', $id)` | `Model::find($id)` or `Model::findOrFail($id)` |
| `ORM::factory('model')->where(...)->find()` | Eloquent query builder |
| `$this->input->post('key')` | `$request->input('key')` / `$request->validated()` |
| `url::redirect('path')` | `return redirect()->route('name')` |
| `new View('name')` with `$view->var = ...` | `return view('name', ['var' => ...])` / Blade |
| `Kohana::config('crmGame.key')` / `kohana::config(...)` | `config('crmgame.key')` |
| `eval(Kohana::config('crmGame.formula'))` | Named PHP methods in `GameFormulas` class — **remove `eval` entirely** |
| `IniORM` (custom `.ini` file reader) | `GameDataRepository` backed by PHP config arrays |
| Tick controllers called via HTTP | `php artisan game:tick-economy` etc. (Artisan commands) |
| `Session::instance()` | `session()` helper / `$request->session()` |
| `Kohana::lang(...)` | Laravel `__()` / `Lang::get()` |
| `valid::email()`, `valid::alpha()` | Form Request validation |
| `Captcha` class | `mews/captcha` package or hCaptcha |
| `include_once('/var/www/web289/html/bugfix.php')` | Delete permanently |

### 1c. Security Architecture

The current codebase has several security concerns that must be addressed during migration (not deferred):

- **Raw SQL with string interpolation** throughout `TickEconomy`, `TickMovement`, `TickScore` — trivially injectable if any input ever reaches those queries. Laravel's query builder / Eloquent with parameter binding eliminates this entirely.
- **`eval()` for formulas** (`goldIncomeFormula`, `buildingCostFormula`, etc.) — `eval` is a code execution vector. Replace with explicit PHP methods.
- **Password hashing** — Kohana uses SHA1 + salt pattern; migrate to Bcrypt via `Hash::make()` / `Hash::check()`. Existing password hashes must be handled via a migration path (re-hash on first login).
- **CSRF** — Kohana has no automatic CSRF protection. Laravel applies it to all POST/PUT/PATCH/DELETE routes by default.
- **Auth middleware** — replace `ControllerProtection` constructor check with Laravel `auth` middleware on route groups.
- **Tick HTTP exposure** — tick controllers are currently publicly accessible via HTTP. Any user can trigger a tick by hitting the URL. Move to Artisan commands run by the scheduler.

---

## 2. Migration Strategy

### Recommended Approach: Fresh Laravel App + Full Port

Do not try to run Kohana and Laravel in the same project or use an incremental file-by-file migration. The framework bootstrapping is incompatible and the codebase is small enough (~20 controllers, ~10 libraries, ~10 models) to port cleanly in a focused effort.

**Recommended sequence:**

1. Scaffold a fresh Laravel 11 app in `/cremoria-laravel` alongside the existing Kohana app (same DB).
2. Write Eloquent migrations + models.
3. Port domain services (Battle, Army, Espionage, handlers) as pure PHP classes with zero framework dependency — they become independently unit-testable immediately.
4. Implement `GameDataRepository` to replace `IniORM`.
5. Implement `GameFormulas` to replace `eval()` formulas.
6. Port HTTP controllers and routes.
7. Port views to Blade.
8. Port tick controllers to Artisan commands.
9. Handle auth migration (password re-hash path).
10. Update Docker config.

---

## 3. Detailed Component Plans

### 3a. Eloquent Models

**Current models:** User, Town, Building (composite PK), Unit (composite PK), Tech (composite PK), UnitProduction, Movement, Troop, Message, Role, UserToken.

The composite-PK tables (`buildings`, `units`, `techs`) use `(id, town_id)` or `(id, user_id)` as their primary key. Eloquent doesn't natively support composite PKs. Model them as `BuildingLevel`, `UnitStock`, `TechLevel` with no auto-increment PK and use `updateOrInsert()` for upsert operations. For reads, `where(['id' => x, 'town_id' => y])->first()` works fine.

```php
// app/Models/Town.php
class Town extends Model {
    protected $fillable = [
        'user_id', 'name', 'x', 'y',
        'gold', 'wood', 'stone', 'iron',
        'gold_inc', 'wood_inc', 'stone_inc', 'iron_inc', 'iron_inc_max',
        'building_time', 'current_building', 'current_bld_pts', 'building_pts',
        'unit_cap_used', 'unit_cap', 'mov_cap', 'mov_cap_used',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function buildingLevels(): HasMany { return $this->hasMany(BuildingLevel::class); }
    public function unitStocks(): HasMany { return $this->hasMany(UnitStock::class); }
    public function unitProductions(): HasMany { return $this->hasMany(UnitProduction::class); }

    public function quantityOfBuilding(int $buildingId): int {
        return $this->buildingLevels->where('id', $buildingId)->first()?->quantity ?? 0;
    }

    public function quantityOfUnit(int $unitId): int {
        return $this->unitStocks->where('unit_id', $unitId)->first()?->quantity ?? 0;
    }
}
```

**Column naming:** the existing schema uses camelCase column names (`goldInc`, `buildingTime`, `unitCapUsed`, etc.). Options: (A) keep camelCase in DB and override `$casts` / `$snakeAttributes` in models, or (B) write a migration that renames columns to snake_case and update all code. Recommendation: rename to snake_case during the migration — it is the Laravel convention and avoids confusion for anyone working on the codebase in the future.

**Auth:** install `laravel/breeze` (minimal auth scaffold). Keep the `roles` / `roles_users` tables and install `spatie/laravel-permission`, which maps to exactly this schema. Replace the two-role system (`login`, `unverified`) with Spatie roles.

### 3b. GameDataRepository (replacing IniORM)

`IniORM` reads `.ini` files into a static cache and provides `find($id)` / `findAll()`. Replace it with PHP config files and a simple repository class.

```
config/
  game/
    buildings.php   (array keyed by id, converted from buildings.ini)
    units.php       (converted from units.ini)
    techs.php       (converted from techs.ini)
    game.php        (constants — replaces crmGame.php, without formula strings)
    items.php       (replaces crmItems.php)
```

```php
// app/Repositories/GameDataRepository.php
class GameDataRepository {
    public function building(int $id): ?array { return config("game.buildings.$id"); }
    public function allBuildings(): array { return config('game.buildings'); }
    public function unit(int $id): ?array { return config("game.units.$id"); }
    public function allUnits(): array { return config('game.units'); }
    public function tech(int $id): ?array { return config("game.techs.$id"); }
    public function allTechs(): array { return config('game.techs'); }
}
```

This is simpler than IniORM, trivially cacheable with `config:cache`, and testable without file I/O. Bind it as a singleton in `AppServiceProvider`.

### 3c. Formula Replacement (removing eval)

The `crmGame.php` config stores PHP code strings like `'return 50 * $no * pow(1.05, $no);'` that are executed with `eval()`. Replace each formula with a named method in a `GameFormulas` class:

```php
// app/Services/Game/GameFormulas.php
class GameFormulas {
    public static function goldIncome(int $buildingLevel): int {
        return (int) ceil(50 * $buildingLevel * pow(1.05, $buildingLevel));
    }
    public static function woodIncome(int $buildingLevel, int $ironInc, float $ironToWood): int {
        return (int) ceil(50 * $buildingLevel * pow(1.05, $buildingLevel)) - (int)($ironToWood * $ironInc);
    }
    public static function stoneIncome(int $buildingLevel, int $ironInc, float $ironToStone): int {
        return (int) ceil(50 * $buildingLevel * pow(1.05, $buildingLevel)) - (int)($ironToStone * $ironInc);
    }
    public static function ironIncomeMax(int $buildingLevel): int {
        return (int) ceil(50 * $buildingLevel * pow(1.05, $buildingLevel));
    }
    public static function unitCap(int $buildingLevel): int {
        return 5 * $buildingLevel;
    }
    public static function buildingCostMultiplier(int $existingQuantity): float {
        return pow(1.25, $existingQuantity); // existing qty already 0-indexed at call site
    }
    public static function buildingTime(int $baseTime, int $totalResourceCost, int $divider): int {
        return (int) ceil($baseTime + ($totalResourceCost / $divider));
    }
    public static function researchCostMultiplier(int $currentLevel): float {
        return pow(1.35, $currentLevel - 1);
    }
    public static function researchTime(float $baseTime, int $labs, int $level): int {
        return (int) ceil($baseTime * pow(0.95, $labs) * $level);
    }
    public static function movementDuration(float $distance, int $armySpeed): int {
        return (int) ceil((1 + $distance) * 100 / $armySpeed);
    }
    public static function unitDamage(int $ap): int {
        return $ap * 10;
    }
    public static function espionageSuccessChance(int $victimPower, int $doerPower): float {
        return 50 + (($victimPower - $doerPower) * 5);
    }
    public static function points(int $gold, int $wood, int $stone, int $iron): int {
        return (int) ceil(($gold * 2 + $iron * 3 + $wood + $stone) / 100);
    }
    public static function movRecogTime(int $outpostCount): int {
        return 3 + $outpostCount;
    }
}
```

Every `eval(kohana::config('...'))` becomes an explicit, readable, testable method call.

### 3d. Domain Services

Port these to `app/Services/Game/` as pure PHP classes:

| New file | From |
|---|---|
| `BattleService.php` | `Battle.php` |
| `Army.php` (abstract) | `Army.php` |
| `AttackerArmy.php` | `AttackerArmy.php` |
| `DefenderArmy.php` | `DefenderArmy.php` |
| `BattleReportService.php` | `BattleReport.php` |
| `EspionageService.php` | `Espionage.php` |
| `ConstructionService.php` | `ConstructionHandler.php` |
| `RecruitmentService.php` | `RecruitmentHandler.php` |
| `ResearchService.php` | `ResearchHandler.php` |
| `ExistenceCreatorService.php` | `ExistenceCreator.php` |

**Key changes during port:**

- Remove all `ORM::factory()` calls — accept Eloquent models as constructor arguments instead.
- Remove all `kohana::config()` calls — inject `GameDataRepository` and call `GameFormulas` methods.
- Remove all `eval()` calls — use `GameFormulas` methods.
- `BattleReport.php` generates raw German HTML inline. Port the HTML to a Blade partial (`resources/views/game/reports/battle-report.blade.php`) rendered with `view(...)->render()` in `BattleReportService`.
- `Espionage::getReport()` calls `new View('espionageReportSuccess')` — port to `view('game.reports.espionage-success', [...])`.

### 3e. HTTP Controllers and Routes

Replace the Kohana controller hierarchy:

```
ControllerCremoria (adds JS/CSS)
  └── ControllerProtection (auth check)
        └── ControllerIngame (loads user + activeTown)
```

In Laravel this becomes:
- A shared Blade layout handles JS/CSS asset inclusion.
- Auth enforcement via `->middleware('auth')` on route groups.
- A `GameController` base class that child controllers extend, loading user and activeTown in a constructor middleware.

```php
// app/Http/Controllers/GameController.php
abstract class GameController extends Controller {
    protected User $user;
    protected Town $activeTown;

    public function __construct() {
        $this->middleware(function ($request, $next) {
            $this->user = User::with(['techs', 'towns.buildingLevels', 'towns.unitStocks'])->find(auth()->id());
            $townId = session('active_town_id', $this->user->towns->first()->id);
            $this->activeTown = $this->user->towns->where('id', $townId)->first()
                ?? $this->user->towns->first(); // fallback if session is stale
            session(['active_town_id' => $this->activeTown->id]);
            View::share([
                'activeTown' => $this->activeTown,
                'user'       => $this->user,
                'unreadMessages' => $this->user->receivedMessages()->where('unread', true)->count(),
            ]);
            return $next($request);
        });
    }
}
```

Note the ownership enforcement: `$this->user->towns->where('id', $townId)->first()` only returns a town if it belongs to the current user, unlike the original which loads any town by ID from the DB.

**Routes:**

```php
// routes/web.php
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.submit');
    Route::get('/verify/{code}', [VerifyController::class, 'verify'])->name('verify');
    Route::get('/reset', [ResetController::class, 'index'])->name('reset');
    Route::post('/reset', [ResetController::class, 'reset'])->name('reset.submit');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::post('/town/set', [TownController::class, 'setActive'])->name('town.set');
    Route::get('/construction', [ConstructionController::class, 'index'])->name('construction');
    Route::post('/construction/{buildingId}', [ConstructionController::class, 'produce'])->name('construction.produce');
    Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment');
    Route::post('/recruitment', [RecruitmentController::class, 'produce'])->name('recruitment.produce');
    Route::get('/research', [ResearchController::class, 'index'])->name('research');
    Route::post('/research/{techId}', [ResearchController::class, 'produce'])->name('research.produce');
    Route::get('/movements', [MovementsController::class, 'index'])->name('movements.index');
    Route::get('/movements/{x}/{y}', [MovementsController::class, 'index'])->name('movements.prefill');
    Route::post('/movements', [MovementsController::class, 'store'])->name('movements.store');
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');
    Route::get('/economy', [EconomyController::class, 'index'])->name('economy');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/highscore', [HighscoreController::class, 'index'])->name('highscore');
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::get('/details/{x}/{y}', [DetailsController::class, 'index'])->name('details');
});
```

**Form Requests** replace all inline validation:

```php
// app/Http/Requests/GenerateMovementRequest.php
class GenerateMovementRequest extends FormRequest {
    public function rules(): array {
        return [
            'destX'       => ['required', 'integer', 'min:0', 'max:' . (config('game.game.map_size') - 1)],
            'destY'       => ['required', 'integer', 'min:0', 'max:' . (config('game.game.map_size') - 1)],
            'action'      => ['required', 'integer', Rule::in(array_keys(config('game.game.movement_types_view')))],
            'units'       => ['required', 'array'],
            'units.*'     => ['integer', 'min:0'],
            'gold'        => ['nullable', 'integer', 'min:0'],
            'wood'        => ['nullable', 'integer', 'min:0'],
            'stone'       => ['nullable', 'integer', 'min:0'],
            'iron'        => ['nullable', 'integer', 'min:0'],
        ];
    }
}

// app/Http/Requests/LoginRequest.php
class LoginRequest extends FormRequest {
    public function rules(): array {
        return [
            'name'     => ['required', 'string', 'alpha'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }
}

// app/Http/Requests/RegisterRequest.php
class RegisterRequest extends FormRequest {
    public function rules(): array {
        return [
            'name'        => ['required', 'string', 'alpha', 'unique:users,username'],
            'password'    => ['required', 'string', 'confirmed', 'min:6'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'captcha'     => ['required', 'captcha'],
        ];
    }
}
```

### 3f. Tick System → Artisan Commands

The tick controllers are currently HTTP-accessible, which means anyone can trigger a game tick by visiting the URL. This is a fundamental security flaw. In Laravel, replace with Artisan commands:

```php
// app/Console/Commands/TickEconomy.php
class TickEconomy extends Command {
    protected $signature   = 'game:tick-economy';
    protected $description = 'Economy tick: resources, buildings, units, research';

    public function handle(): int {
        DB::transaction(function () {
            $this->updateResources();
            $this->completeBuildings();
            $this->completeResearch();
            $this->completeUnitProduction();
        });
        return Command::SUCCESS;
    }

    private function updateResources(): void {
        Town::query()->update([
            'gold'          => DB::raw('gold + gold_inc'),
            'wood'          => DB::raw('wood + wood_inc'),
            'stone'         => DB::raw('stone + stone_inc'),
            'iron'          => DB::raw('iron + iron_inc'),
            'building_time' => DB::raw('building_time - 1'),
        ]);
        User::query()->update([
            'research_time' => DB::raw('research_time - 1'),
        ]);
    }
    // ...
}

// routes/console.php
Schedule::command('game:tick-economy')->everyMinute();
Schedule::command('game:tick-movement')->everyMinute();
Schedule::command('game:tick-score')->hourly();
```

All raw SQL in tick logic is replaced with Eloquent's `update()` with `DB::raw()` for column-based arithmetic, which is both safe and readable.

### 3g. Active Town Session Handling

`ControllerIngame` stores the active town ID in the session and loads it on every request. The `setTown` action verifies ownership via a PHP loop through the user's loaded towns. Port to:

```php
// app/Http/Controllers/TownController.php
public function setActive(Request $request): RedirectResponse {
    $townId = $request->input('townId');
    $town   = auth()->user()->towns()->findOrFail($townId); // DB-level ownership check
    session(['active_town_id' => $town->id]);
    return redirect()->back();
}
```

### 3h. Password Migration

Kohana used SHA1 with a configurable salt pattern. Laravel uses Bcrypt. Write a helper that verifies the old hash, then re-hashes on first successful login:

```php
// app/Services/Auth/KohanaPasswordVerifier.php
class KohanaPasswordVerifier {
    // Replicates Kohana's salt_pattern from application/config/auth.php
    public function verify(string $plaintext, string $kohanaHash): bool {
        // ... reconstruct salt from hash using salt_pattern config, then sha1(salt + password)
    }
}

// app/Http/Controllers/LoginController.php
public function login(LoginRequest $request): RedirectResponse {
    $user = User::where('username', $request->name)->first();
    if (!$user) {
        return back()->withErrors(['name' => __('auth.failed')]);
    }
    if (Hash::check($request->password, $user->password)) {
        // Already on Bcrypt — normal path
        Auth::login($user, $request->boolean('remember'));
    } elseif (app(KohanaPasswordVerifier::class)->verify($request->password, $user->password)) {
        // Old Kohana hash — re-hash and login
        $user->update(['password' => Hash::make($request->password)]);
        Auth::login($user, $request->boolean('remember'));
    } else {
        return back()->withErrors(['name' => __('auth.failed')]);
    }
    return redirect()->intended(route('construction'));
}
```

### 3i. Views → Blade

```
resources/views/
  layouts/
    app.blade.php          (public pages: login, register)
    game.blade.php         (authenticated: resource bar + nav)
  auth/
    login.blade.php
    register.blade.php
    verify.blade.php
    reset.blade.php
  game/
    construction.blade.php
    recruitment.blade.php
    research.blade.php
    movements.blade.php
    overview.blade.php
    economy.blade.php
    map.blade.php
    highscore.blade.php
    messages/
      index.blade.php
    details.blade.php
  partials/
    resource-bar.blade.php
    nav-in.blade.php
    nav-out.blade.php
  reports/
    battle-report.blade.php
    espionage-success.blade.php
    espionage-failure.blade.php
```

German hardcoded strings from `BattleReport.php` move to `lang/de/game.php`:

```php
// lang/de/game.php
return [
    'battle_report'    => 'Kampfbericht',
    'attacker'         => 'Angreifer',
    'defender'         => 'Verteidiger',
    'draw'             => 'Unentschieden',
    'losses'           => 'Verluste',
    'winner'           => 'Gewinner',
    'loot'             => 'Beute',
    // ...
];
```

---

## 4. Code Quality Issues Found in Existing Code

### 4a. eval() for Formulas (Critical)

**Files:** `ConstructionHandler.php:63,81`, `ResearchHandler.php:88,94,104,111`, `ProductionHandler.php:44`, `Army.php:83`, `Espionage.php:34`, `TickEconomy.php:132,143,157,168,179`.

`eval()` executes arbitrary PHP. Even though the strings come from config files, this is undebuggable, untestable, and invisible to static analysis. Fix: `GameFormulas` class (see section 3c).

### 4b. Raw SQL Injection (Critical)

**Files:** `TickEconomy.php` throughout, `TickMovement.php` throughout, `TickScore.php:23–26`.

IDs and calculated values are interpolated directly into SQL strings. While most values currently come from the DB, this is dangerous practice. Fix: Eloquent / query builder parameter binding throughout.

### 4c. DRY Violation: calculateObject / calculateArray Duality

**Files:** `ConstructionHandler.php:61-92`, `ResearchHandler.php:84-115`, `RecruitmentHandler.php:110-128`.

Each `ProductionHandler` subclass must implement both `calculateObject` and `calculateArray` doing identical logic on two different data structures. Fix: standardize to PHP arrays throughout; remove the object/array duality. Each handler implements one `calculate(array $item, int $quantity): array` method.

### 4d. Missing Ownership Check on Town Selection

**File:** `ControllerIngame.php:39-43`

`setTown` checks ownership via a PHP loop over loaded towns, then calls `ORM::factory('town', $townId)` which would load any town regardless. Fix: `$this->user->towns()->findOrFail($townId)` enforces ownership at the DB level.

### 4e. `ExistenceCreator::setCoordinates` Bug — Missing `$`

**File:** `ExistenceCreator.php:41`

`if($coord->x == $x && $coord->y == y)` — bare `y` is a PHP constant/string `"y"`, always falsy. The coordinate uniqueness check never works; two players can be placed on the same coordinates. Fix: `$coord->y == $y`.

### 4f. `ConstructionHandler` Cost Calculation Bug — Wrong Resource Sum

**Files:** `ConstructionHandler.php:70`, `ConstructionHandler.php:88-89`

`$sum = ($item->gold + $item->wood + $item->wood + $item->wood)` — `$item->wood` is added three times; `$item->stone` and `$item->iron` are omitted. Building time is calculated incorrectly. Both `calculateObject` and `calculateArray` have this same bug. Fix: `$sum = $item->gold + $item->wood + $item->stone + $item->iron`.

### 4g. `BattleReport` Hardcoded German Strings

**File:** `BattleReport.php:35-36, 97-110`

Report titles and labels are hardcoded in German despite the existence of `i18n/en_US/` and `i18n/de_DE/` directories. Fix: move to `lang/de/game.php`.

### 4h. `Espionage::getSpioPower` Undefined Variable Bug

**File:** `Espionage.php:73-74`

```php
$town = $town->quantityOfBuilding($bldId);  // overwrites $town
$no   = ($townNo > $no)? $townNo : $no;     // $townNo is never defined
```

`$town` (the loop variable) is overwritten with an integer, then `$townNo` (undefined) is used instead. This throws a notice and always returns 0, making all espionage power 0. Fix: use distinct variable names: `$level = $town->quantityOfBuilding($bldId); $no = max($no, $level);`.

### 4i. Attack on Empty Coordinates — Fatal Error Risk

**File:** `TickMovement.php:162-165`

```php
$res = $this->db->query("SELECT user_id, id FROM towns WHERE x = ... AND y = ...");
$deffer = $res[0]->user_id;  // fatal if $res is empty
```

If a movement targets coordinates that have no town (e.g. town was deleted between movement creation and arrival), this causes a fatal error. Fix: check `$res->count() > 0` before accessing `$res[0]`.

### 4j. `register.php` — User Created Before Town Setup

**File:** `register.php:57-64`

`$newUser->save()` saves the user but `ExistenceCreator::createNewExistence()` is never called in the register controller — the creation class exists but is not wired up. New registrations create a user with no town, causing a fatal error on their first ingame page load (`$this->user->towns[0]` at line 26 of `ControllerIngame`). Fix: call `(new ExistenceCreatorService)->createNewExistence($user)` immediately after the user and their `login` role are assigned.

---

## 5. Test Plan

The Kohana codebase has zero tests. Laravel provides full testing infrastructure from day one.

### 5a. Unit Tests (Domain Services — no framework dependency)

**`GameFormulas`:**
- Each formula: verify correct output for known inputs.
- Edge cases: `$level = 0`, `$level = 1`, very large values.
- `buildingTime` with zero base time and zero resources.
- `espionageSuccessChance` when powers are equal (returns 50).

**`BattleService` / `Army`:**
- Attacker wins when defender has 1 HP unit vs. high-AP attacker.
- Defender wins when attacker can't deal sufficient damage in 3 rounds.
- Draw scenario (difD exactly 0.5).
- `determineWinner` when `$startPowerDef == 1` (guard clause returns ATTACKERWINS).
- `pillage`: resources capped at what defender has; overflow redistributed to next resource.
- `Army::attack`: skips dead units (unset array slots) correctly.
- `Army::defend`: removes unit on <= 0 HP; marks army defeated when all dead.
- `Army::getPower`: correct HP sum including the +1 offset.
- `Army::getCargoCapacity`: sums `cp` across surviving units.
- Empty army (no units): `isDefeated()` true from the start.

**`ConstructionService`:**
- `produce()` blocks if `town->current_building != null`.
- `produce()` blocks if insufficient resources.
- `produce()` blocks if item not in allowed list.
- `produce()` deducts correct resources and sets `current_building` / `building_time`.
- `checkAllowed()`: tech requirements gate items.
- `checkAllowed()`: building requirements gate items.
- `checkAllowed()`: item with all requirements met is included.

**`RecruitmentService`:**
- `produce()` respects `unit_cap`.
- `produce()` with multiple unit types: each deducted and queued separately.
- Existing production queue entry found and quantity incremented (not duplicated).

**`ResearchService`:**
- Blocks if `labs == 0`.
- Blocks if already researching.
- Repeatable tech costs scale correctly with `researchCostMultiplier`.
- Non-repeatable tech already owned is rejected.

**`EspionageService`:**
- `isSuccessful()` returns true when randomized chance passes.
- Spy power and discover power calculated correctly (after fixing `$townNo` bug).

**`GameDataRepository`:**
- `building(1)` returns correct Goldmine data (name, costs, etc.).
- `building(999)` returns null.
- `allBuildings()` returns all 11 entries.
- `unit` and `tech` equivalents.

### 5b. Feature Tests (HTTP)

```php
// tests/Feature/Auth/LoginTest.php
it('redirects authenticated user to construction on login')
it('rejects wrong credentials and shows error')
it('logs in user with old Kohana SHA1 hash and re-hashes to Bcrypt')
it('remembers user when remember checkbox checked')

// tests/Feature/Auth/RegisterTest.php
it('creates user with unverified role and sends activation email')
it('calls ExistenceCreatorService to create initial town')
it('rejects duplicate username')
it('rejects duplicate email')
it('rejects mismatched passwords')
it('rejects invalid captcha')
it('rejects empty name or password')

// tests/Feature/Auth/VerifyTest.php
it('promotes user from unverified to login role with valid code')
it('rejects invalid verification code')

// tests/Feature/Game/ConstructionTest.php
it('shows allowed buildings based on user techs and buildings')
it('initiates building and deducts resources when valid')
it('rejects build when already constructing')
it('rejects build when insufficient resources')
it('rejects build with unknown buildingId')
it('shows current building in progress')

// tests/Feature/Game/MovementsTest.php
it('creates movement with valid units, coordinates and cargo')
it('rejects movement with no units selected')
it('rejects movement with coordinates outside map bounds')
it('rejects movement to coordinates with no town')
it('rejects movement when cargo exceeds army capacity')
it('rejects movement when cargo exceeds town resources')
it('rejects movement when movement cap exhausted')
it('rejects movement with invalid action type')

// tests/Feature/Game/TownTest.php
it('sets active town when town belongs to user')
it('rejects setting active town belonging to another user')

// tests/Feature/Tick/TickEconomyTest.php
it('increments gold, wood, stone, iron by respective income rates each tick')
it('decrements building_time and research_time by 1 each tick')
it('completes building when building_time reaches 0 and updates buildings table')
it('triggers gold income recalculation when goldBld completes')
it('triggers wood income recalculation when woodBld completes')
it('triggers stone income recalculation when stoneBld completes')
it('triggers iron income max recalculation when ironBld completes')
it('updates unit_cap when unitBld completes')
it('updates mov_cap when movCapBld completes')
it('nulls out current_building and building_time after completion')
it('completes research and inserts into techs with correct quantity')
it('nulls out current_research and research_time after completion')
it('completes unit production and credits units to town')
it('deletes unit_production row after completion')
it('does not double-complete a building if tick runs twice before clean')

// tests/Feature/Tick/TickMovementTest.php
it('decrements arrival by 1 each tick')
it('handles transfer: delivers resources to destination town')
it('handles transfer: delivers troops to destination town')
it('handles transport: delivers resources and creates return movement')
it('handles attack: runs battle, sends reports to both parties')
it('handles attack: attacker wins — pillages resources and creates return movement')
it('handles attack: attacker loses — no return movement created')
it('handles attack: destination town not found — does not panic')
it('handles espionage: sends spy report to doer, creates return')
it('handles return: delivers troops and increments mov_cap_used')
it('deletes handled movements after processing')
it('deletes troops after movement is handled')

// tests/Feature/Tick/TickScoreTest.php
it('updates building_pts from sum of town building_pts for each user')
it('updates total_pts as research_pts + building_pts')
it('handles user with no towns (zero building pts)')
```

### 5c. Missing Edge Cases to Add

- `TickMovement::handleAttack` when destination has no town (currently causes fatal error — add `Town::where(...)->firstOrFail()` and catch / log).
- `ExistenceCreator::setCoordinates` when map is full (infinite loop risk) — add a safety counter after 1,000 attempts, throw an exception.
- `setTown` with a `townId` belonging to another user (must be rejected at DB level).
- `TickEconomy::updateTowns` with `building_time` already negative (idempotency check — should not double-complete).
- Registration with empty `username` but non-empty `email` (each field in isolation).
- Movement with `quantity = 0` units in the POST data (`formatUnitPostData` filters these; test the filter directly).
- Research of an already-owned non-repeatable tech (must be rejected).

---

## 6. Performance Review

### 6a. N+1: `ProductionHandler::__construct` Relations

**File:** `ProductionHandler.php:30-36`

`$this->user->techs` and `$this->town->buildings` are lazy-loaded. In the Laravel port, eager-load in `GameController`:

```php
User::with(['techs', 'towns.buildingLevels', 'towns.unitStocks', 'towns.unitProductions'])->find(auth()->id());
```

### 6b. N+1: `TickMovement::handleMovements`

**File:** `TickMovement.php:46-67`

Per-movement DB queries for troops and destination town. Fix: eager-load with `Movement::with('troops')->where('arrival', 0)->get()`, then batch destination town lookups with `Town::whereIn('x', $destXs)->...`.

### 6c. `TickScore` Per-Row UPDATE Loop

**File:** `TickScore.php:22-25`

Fires one `UPDATE users` per user. Fix with a single join-update:

```sql
UPDATE users u
JOIN (
  SELECT user_id, SUM(building_pts) AS bld
  FROM towns GROUP BY user_id
) t ON u.id = t.user_id
SET u.building_pts = t.bld,
    u.total_pts    = u.research_pts + t.bld;
```

### 6d. `ExistenceCreator::setCoordinates` O(n) Memory Scan

**File:** `ExistenceCreator.php:31-46`

Loads all town coordinates into PHP memory and loops to check uniqueness. Fix: `Town::where(['x' => $x, 'y' => $y])->exists()` — O(1) indexed DB lookup.

### 6e. Config Caching

`IniORM` loads `.ini` files per-request (with a static cache that resets between requests in FPM). The `GameDataRepository` backed by PHP config arrays is compiled into opcache by `php artisan config:cache`, costing zero I/O at runtime.

---

## 7. Migration Checklist

```
Phase 1 — Foundation
[ ] Scaffold Laravel 11 app
[ ] Configure .env (DB, mail, app key)
[ ] Write database migrations (match existing schema, rename camelCase columns to snake_case)
[ ] Write Eloquent models with relationships
[ ] Install spatie/laravel-permission, configure roles (login, unverified)
[ ] Convert .ini files to PHP config arrays (config/game/)
[ ] Implement GameDataRepository, bind as singleton
[ ] Implement GameFormulas class (all eval() formulas as explicit methods)
[ ] Write unit tests for GameFormulas and GameDataRepository

Phase 2 — Domain Services
[ ] Port Army (abstract), AttackerArmy, DefenderArmy
[ ] Port BattleService, BattleReportService (with Blade report view)
[ ] Port EspionageService (fix $townNo bug)
[ ] Port ConstructionService (fix cost formula bug)
[ ] Port RecruitmentService
[ ] Port ResearchService
[ ] Port ExistenceCreatorService (fix $y bug, add map-full guard)
[ ] Write unit tests for all domain services

Phase 3 — HTTP Layer
[ ] Blade layouts (app.blade.php, game.blade.php)
[ ] Port auth controllers (Login, Logout, Register, Verify, Reset)
[ ] Implement KohanaPasswordVerifier + re-hash-on-login path
[ ] Implement GameController base class
[ ] Implement Form Request classes for all POST routes
[ ] Port all ingame controllers
[ ] Wire ExistenceCreatorService into Register flow
[ ] Define named routes

Phase 4 — Views
[ ] Port all views to Blade
[ ] Move German game strings to lang/de/game.php
[ ] Migrate JS assets (jQuery, fancybox, movements.js, messages.js)

Phase 5 — Tick System
[ ] Implement TickEconomy Artisan command (Eloquent, no eval, no raw SQL)
[ ] Implement TickMovement Artisan command (eager loading, no raw SQL)
[ ] Implement TickScore Artisan command (single join-update)
[ ] Configure Laravel Scheduler in routes/console.php
[ ] Write feature tests for all tick commands

Phase 6 — Infrastructure
[ ] Update docker-compose.yml (PHP 8.3 + Nginx/FPM image)
[ ] Update Dockerfile
[ ] Remove bugfix.php and its include
[ ] Add php artisan config:cache and route:cache to production startup
[ ] End-to-end test pass covering all game flows
```

---

## Open Questions

1. **Password migration approach:** should existing player passwords be re-hashed silently on first login (users notice nothing, but the Kohana hash verification code must stay until all players have logged in once), or should all users be forced to reset their password at cutover (cleaner, but disruptive if the player base is active)?

2. **Game language:** the UI and battle reports are currently in German. Should the Laravel port maintain German-only, or should proper i18n be set up with German as default and English as a secondary option? This determines how much effort goes into the lang file structure and whether translated strings need to be written for both locales.

3. **Captcha:** the existing captcha uses a custom Kohana library. Should the replacement use a self-hosted image captcha (`mews/captcha`), or an external service like hCaptcha or Cloudflare Turnstile? External services require considering user privacy (GDPR) if players are EU-based.
