# Ai-offerte — buro_deBom

## Stack
- Laravel 13 + Inertia.js + Vue 3 + Tailwind v4
- MySQL (lokaal en productie), database naam: `ai_offerte`

## Conventies
- Veldnamen in het Nederlands (bijv. `voornaam`, `aangemaakt_op`)
- Vue components in `resources/js/Pages/` (pagina's) en `resources/js/Components/` (herbruikbaar)
- Tailwind klassen gebruiken, geen inline styles
- Geen hardcoded credentials — altijd via `.env`

## Commando's
- `npm run dev` — start Vite dev server
- `npm run build` — bouw assets voor productie
- `npm run lint` — ESLint controleren
- `npm run format` — Prettier formatteren
- `php artisan migrate` — database migraties uitvoeren
- `php artisan serve` — Laravel dev server starten

## Omgeving
- PHP 8.2+, Node 24, Composer
- `.env` bevat lokale configuratie (nooit committen)
