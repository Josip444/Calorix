---
name: calorix-fullstack-implementation
overview: Implement the Calorix nutrition and meal-planning app as a Laravel 12 + Vue SPA with Sanctum-authenticated API, including database schema, auth, dashboard, profile, and AI-driven meal plan generation.
todos:
  - id: db-migrations-and-models
    content: Design and implement all required migrations and Eloquent models/relationships for users, weight stats, and meal planning domain.
    status: pending
  - id: sanctum-auth-api
    content: Install Sanctum and build register/login/logout/me endpoints with calorie/macro calculation at registration.
    status: pending
  - id: dashboard-profile-api
    content: Implement dashboard and profile APIs, including weight entry handling and macro recalculation on profile changes.
    status: pending
  - id: meal-plan-generation-api
    content: Implement meal plan CRUD and OpenAI-based generation flow with JSON parsing into MealPlan/Week/Day/Meal/Item records.
    status: pending
  - id: vue-spa-and-tabs
    content: Set up Vue SPA with auth routes and dashboard/profile/meal-plan tabs, wiring to backend APIs.
    status: pending
  - id: root-app-description-file
    content: Create a concise root-level app description file documenting architecture and feature phases.
    status: pending
isProject: false
---

## High-Level Architecture

- **Backend (Laravel 12)**: JSON API only (no Blade-based app UI), using Sanctum for SPA authentication, MySQL via Docker, all domain changes via migrations and Eloquent models.
- **Frontend (Vue + Vite SPA)**: Single-page app mounted from `resources/js/app.js`, using Vue Router and a central `Dashboard` layout with tabs: `Dashboard`, `Profile`, `Meal Plans`.
- **API Boundary**: SPA talks only to `/api/*` endpoints with Sanctum CSRF/session-based tokens; backend returns JSON resources for auth, dashboard, profile, and meal plans.
- **Docker**: Reuse existing `app`, `mysql`, `nginx` services; run frontend dev/build via Vite in the same container or host, serving built assets through Laravel Vite integration.
- **Documentation**: Add a concise app description file in the project root to capture scope and phases for future reference.

## Step 1 — Database Design & Migrations (PHASE 1)

- **1.1 Replace/extend default `users` table**
  - Update existing `create_users_table` migration in `[database/migrations]` (or add a new dedicated migration if preserving the default is required) to align with the required fields:
    - Add columns: `first_name`, `last_name`, `sex` (enum or constrained string), `age` (int), `height_cm` (int), `start_weight_kg` (decimal 5,2), `current_weight_kg` (decimal 5,2), `goal_weight_kg` (decimal 5,2 nullable), `activity_level` (enum or string constrained to required values), `goal_type` (enum or string constrained), `meals_per_day` (int), `allergies_text` (text nullable), `daily_calories_target` (int nullable), `protein_g_target` (int nullable), `carbs_g_target` (int nullable), `fats_g_target` (int nullable).
    - Ensure `email` is unique and keep `password` plus timestamps.
    - Prefer Laravel `enum` columns where reasonable (e.g. `sex`, `activity_level`, `goal_type`), or string + validation if enums feel too rigid.
- **1.2 Create `user_weight_stats` table**
  - New migration `[database/migrations/xxxx_xx_xx_create_user_weight_stats_table.php]` with:
    - `id`, `user_id` (foreignId, constrained to `users`, cascade on delete), `date` (date), `weight` (decimal 5,2), timestamps.
- **1.3 Create meal-planning tables**
  - `meal_plans` table: `user_id` (FK), `start_date`, `end_date`, `daily_calories_target`, `protein_g_target`, `carbs_g_target`, `fats_g_target`, `source` (string, e.g. `openai`), `status` (enum/string: `generated`, `failed`, `generating`), timestamps.
  - `meal_weeks` table: `meal_plan_id` (FK), `week_number` (tiny int 1–4), `start_date`, `end_date`, timestamps.
  - `meal_days` table: `meal_week_id` (FK), `day_number` (tiny int 1–7), `date`, timestamps.
  - `meals` table: `meal_day_id` (FK), `meal_type` (enum/string: `breakfast`, `lunch`, `dinner`, `snack`), `total_calories`, `total_protein_g`, `total_carbs_g`, `total_fats_g`, `instructions_text` (text), timestamps.
  - `meal_items` table: `meal_id` (FK), `food_name`, `quantity`, `unit` (enum/string: `g`, `ml`, `piece`), `calories`, `protein_g`, `carbs_g`, `fats_g`, timestamps.
- **1.4 Define Eloquent models and relationships**
  - Create models `UserWeightStat`, `MealPlan`, `MealWeek`, `MealDay`, `Meal`, `MealItem` in `app/Models`.
  - Implement relationships as specified:
    - `User` → `hasMany(UserWeightStat::class)` and `hasMany(MealPlan::class)`.
    - `MealPlan` → `belongsTo(User::class)`, `hasMany(MealWeek::class)`.
    - `MealWeek` → `belongsTo(MealPlan::class)`, `hasMany(MealDay::class)`.
    - `MealDay` → `belongsTo(MealWeek::class)`, `hasMany(Meal::class)`.
    - `Meal` → `belongsTo(MealDay::class)`, `hasMany(MealItem::class)`.
    - `MealItem` → `belongsTo(Meal::class)`.
- **1.5 Run and verify migrations (later execution)**
  - Plan to run `php artisan migrate` inside the `app` container and check MySQL via phpMyAdmin to confirm schema matches the spec.

## Step 2 — Sanctum-Based Authentication API (PHASE 2)

- **2.1 Install and configure Sanctum**
  - Require Sanctum via Composer; publish its config and migration.
  - Configure `config/auth.php` to add an `api` guard using Sanctum if needed, and register Sanctum middleware in `bootstrap/app.php` (e.g. for `api` group).
  - Enable SPA auth pattern (CSRF cookie + session-based tokens) appropriate for Laravel + Vue SPA.
- **2.2 Define API routes for auth**
  - Create `routes/api.php` and wire it in `bootstrap/app.php` via `withRouting(api: ...)`.
  - Add routes:
    - `POST /register` → `Auth\RegisterController` or `Auth\RegisteredUserController`-style class.
    - `POST /login` → `Auth\LoginController` or similar.
    - `POST /logout` → token/session revocation.
    - `GET /me` → return authenticated user including macro targets.
- **2.3 Implement registration logic with validation and calorie/macro calculation**
  - In the register controller:
    - Validate all fields according to spec (required, email unique, password confirmed, numeric constraints, enums for `activity_level`, `goal_type`, `sex`).
    - Compute BMR using start weight, height, age, and sex.
    - Apply activity multiplier to derive TDEE.
    - Adjust calories according to goal: `lose`, `gain`, `build`, `maintain`.
    - Derive macro targets:
      - Protein grams from goal-type-specific multiplier × weight.
      - Fat calories as 25–30% of total calories (pick a consistent percentage, e.g. 30%, document it in code comments), convert to grams.
      - Carbs use the remainder calories, converted to grams.
    - Persist user with all calculated target fields in the `users` table.
    - Create initial Sanctum token / session and return JSON with user + macro targets.
- **2.4 Implement login and user response shape**
  - In login controller:
    - Validate email and password.
    - Attempt auth; on success, create Sanctum token (if using token-based) or rely on session with CSRF; return standardized JSON: user info, macro targets, tokens where applicable.
    - On failure, return proper validation/401 responses.

## Step 3 — Dashboard API & Logic (PHASE 3)

- **3.1 Define dashboard-related routes**
  - Under `routes/api.php`, within Sanctum-authenticated middleware group, add:
    - `GET /dashboard` → `DashboardController@show`.
    - `POST /weight-entry` → `DashboardController@storeWeight`.
    - `GET /weight-history` → `DashboardController@history`.
- **3.2 Implement dashboard controller**
  - `show`:
    - Return JSON payload with: `start_weight_kg`, `current_weight_kg`, `goal_weight_kg`, `daily_calories_target`, `protein_g_target`, `carbs_g_target`, `fats_g_target` from the authenticated user.
  - `storeWeight`:
    - Validate `date` and `weight`.
    - Insert a `UserWeightStat` row for the user.
    - Update `current_weight_kg` on the `users` table.
    - Return the updated user and the newly created weight stat.
  - `history`:
    - Return all weight stats for the user ordered by date (suitable for charting), along with their dates.

## Step 4 — Profile API & Recalculation Logic (PHASE 4)

- **4.1 Profile routes**
  - Add authenticated routes:
    - `GET /profile` → `ProfileController@show`.
    - `PUT /profile` → `ProfileController@update`.
- **4.2 Implement profile fetch**
  - `show` returns full editable profile data: name fields, sex, age, height, current/goal weight, activity level, goal type, meals per day, allergies, plus current targets.
- **4.3 Implement profile update with auto-recalculation**
  - In `update`:
    - Validate input (similar rules as registration, but allow partial updates where appropriate).
    - Detect whether any of: `current_weight_kg` (or equivalent weight field), `activity_level`, `goal_type`, `height_cm`, `age` changed.
    - If any changed, recompute BMR, TDEE, goal adjustment, and macro distribution using the same formulae as registration.
    - Persist new values and updated targets into `users`.
    - Return updated user JSON.

## Step 5 — Meal Plan Domain & OpenAI Integration (PHASE 5)

- **5.1 Meal plan APIs**
  - Under authenticated routes, define:
    - `GET /meal-plans` → list user’s meal plans with key fields and `status`.
    - `POST /meal-plans` (`Generate New Plan`) → trigger generation flow.
    - `GET /meal-plans/{mealPlan}` → show high-level plan structure (weeks/dates/status).
    - `GET /meal-plans/{mealPlan}/weeks/{week}` → show days for a week.
    - `GET /meal-plans/{mealPlan}/days/{day}` → show meals for a day.
    - `GET /meals/{meal}` → show meal with items and macro breakdown.
- **5.2 OpenAI integration service**
  - Create a service class, e.g. `App\Services\MealPlanGenerator`, that:
    - Accepts user parameters: `daily_calories_target`, macro targets, `meals_per_day`, `allergies_text`, `goal_type`.
    - Constructs a prompt instructing OpenAI to return strictly valid JSON in the required structure: 4 weeks, each with 7 days, each with `meals_per_day` meals, each meal with items and totals.
    - Calls OpenAI API using an HTTP client (`Http`/`Guzzle`) and parses the JSON.
    - Handles validation of the returned structure (fail fast if malformed).
- **5.3 Meal plan generation flow**
  - In the `POST /meal-plans` controller:
    - Create `MealPlan` with `status = 'generating'` and copy in target macros from the user.
    - Option A: for initial implementation, run the OpenAI call synchronously in the request; Option B (future): dispatch a job.
    - Call `MealPlanGenerator`; on success:
      - Persist `MealWeeks`, `MealDays`, `Meals`, and `MealItems` using the parsed JSON.
      - Update `MealPlan` to `status = 'generated'` and set `start_date`/`end_date` based on the generated content.
    - On failure:
      - Update `MealPlan` to `status = 'failed'` and return appropriate error JSON.
- **5.4 Query/serialization layer**
  - Implement Eloquent relationships and eager loading for nested retrieval, so that endpoints can return nested structures efficiently (e.g. `MealPlan::with('weeks.days.meals.items')`).
  - Consider using dedicated API resource classes (e.g. `MealPlanResource`, `MealResource`) to shape responses consistently.

## Step 6 — Vue SPA Frontend Structure

- **6.1 Install and bootstrap Vue**
  - Add `vue`, `vue-router`, and optionally a state library (e.g. Pinia) to `package.json`.
  - Update `resources/js/app.js` to create a Vue app, configure router, and mount to a new `#app` element.
  - Create a base Blade layout (e.g. `resources/views/app.blade.php`) that contains `<div id="app"></div>` and `@vite` assets, and point main web route (`/`) to this SPA view.
- **6.2 Routing & layout**
  - Define Vue Router routes for:
    - `/login`, `/register`.
    - `/dashboard` (default authenticated view) with tabs for `Dashboard`, `Profile`, `Meal Plans`.
  - Implement an auth layout that checks for user auth state and redirects to login if unauthenticated.
- **6.3 Auth UI**
  - **Register**:
    - Multi-step, tab-based form collecting all required registration data.
    - Persist partial state across steps and submit as a single JSON payload to `POST /register`.
    - Display server validation errors inline per field.
  - **Login**:
    - Simple form posting to `POST /login`; on success, store auth state (token/session) and user data, then redirect to `/dashboard`.
    - Configure Axios to send cookies/CSRF for Sanctum.

## Step 7 — Dashboard, Profile & Meal Plan UI

- **7.1 Dashboard tab**
  - Call `GET /dashboard` to display metrics: start weight, current weight, goal weight, and macro targets.
  - Call `GET /weight-history` to fetch history and render a Chart.js line chart (X-axis: date, Y-axis: weight).
  - Implement an "Add Weight" form (date + weight) posting to `POST /weight-entry`; on success, refresh history and dashboard stats.
  - Render a simple table/list of full weight history beneath the chart.
- **7.2 Profile tab**
  - Fetch profile data via `GET /profile` and display editable fields: basic info, weight, activity level, goal, meals per day, allergies.
  - On submit (`PUT /profile`), optimistically update UI and show new macro targets after recalculation.
  - Handle validation errors gracefully and display recalculated targets inline.
- **7.3 Meal Plan tab**
  - Show a button "Generate New Plan" calling `POST /meal-plans` and then polling or refreshing the `GET /meal-plans` list to pick up `generated`/`failed`/`generating` statuses.
  - Render a list of meal plans with status chips and key dates.
  - When a plan is opened, show nested views:
    - Week list → Day list → Meal list → Meal details with items and macro breakdown.
  - Reuse Chart.js or simple tables to show macro breakdown per day/meal where useful.

## Step 8 — App Description File & Documentation

- **8.1 Add root-level description file**
  - Create an `APP_DESCRIPTION.md` (or similar) in the project root summarizing:
    - Overall product goal (Calorix calorie and meal-planning assistant).
    - High-level architecture (Laravel 12 API + Vue SPA + Sanctum + MySQL + Docker).
    - The five functional phases (DB, Auth, Dashboard, Profile, Meal Plans).
  - Keep this document short but precise so it can be referenced for future decisions.

## Step 9 — Verification & Quality Gates

- **9.1 Backend verification**
  - Add basic feature tests for auth, dashboard, profile update recalculations, and meal plan creation (at least for success paths and a couple of validation failures).
  - Run `php artisan test` in the Docker `app` container to ensure all tests pass.
- **9.2 Frontend verification**
  - Add lightweight component or e2e-style tests (if using a test runner like Vitest/Cypress later) or at minimum manual test scripts for critical flows: register → login → dashboard → weight entry → profile update → meal plan generation.
- **9.3 Docker / environment checks**
  - Confirm the full stack runs via `docker-compose up` with migrations applied, SPA loaded from `/`, and all API endpoints reachable through nginx.

