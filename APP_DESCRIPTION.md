# Calorix - AI Nutrition & Meal Planning Assistant

## Overview
Calorix is a modern health and fitness application designed to help users track their weight, calculate daily nutritional requirements, and generate personalized meal plans using AI (currently simulated). 

The app features a seamless SPA experience with a robust Laravel backend and a reactive Vue.js frontend.

## High-Level Architecture
- **Backend**: Laravel 12 (API-only)
- **Frontend**: Vue.js 3 + Vite + Tailwind CSS
- **Authentication**: Laravel Sanctum (Stateful SPA Auth)
- **Database**: MySQL (Models: User, UserWeightStat, MealPlan, MealWeek, MealDay, Meal, MealItem)
- **Integration**: OpenAI ready (currently using a simulated generator service)

## Feature Phases
1. **DB Design**: Extended user profiles and nested meal planning schema.
2. **Sanctum Auth**: Registration with automatic BMR/TDEE and macro calculation.
3. **Dashboard & Profile**: Weight history tracking with Chart.js and dynamic profile recalculation.
4. **Meal Planning**: Multi-week plan generation with drill-down navigation (Weeks -> Days -> Meals).
5. **Aesthetics**: Premium dark/light UI with smooth transitions and glassmorphism elements.

## Technical Notes
- **API Base**: `/api`
- **CORS**: Configured for `localhost:82`
- **SPA Mounting**: Mounted in `resources/views/app.blade.php` via `resources/js/app.js`
