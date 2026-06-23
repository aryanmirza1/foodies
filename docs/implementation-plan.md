# Foodies Implementation Plan

Foodies is a mobile-first, app-like PWA for one restaurant or home food business. It is not a marketplace: customers browse one menu, add items to cart, and later complete checkout for pickup or delivery.

The product must stay white-label. Restaurant identity, colors, homepage text, banners, categories, menu items, pricing, images, payment details, policies, contact information, and operational settings should come from the backend database and admin screens rather than hardcoded frontend content.

## Stack

- Backend: Laravel API
- Auth: Laravel Sanctum prepared for later phases
- Database: MySQL or MariaDB
- Frontend: React, Vite, TypeScript
- Styling: Tailwind CSS
- App behavior: PWA
- Mobile packaging: Capacitor-ready structure
- Hosting target: Hostinger later

## Current Phase 1 Scope

- Create `backend/` Laravel app.
- Create `frontend/` React PWA app.
- Keep the old PHP app untouched as reference.
- Add dynamic settings, home, banners, categories, and menu item tables.
- Seed a usable sample restaurant/menu.
- Add public API endpoints for the frontend.
- Build a mobile customer shell with home, menu, item details, cart, orders placeholder, and profile placeholder.
- Build a mobile admin shell placeholder.
- Support local cart state in `localStorage`.

## Later Phases

Phase 2 should add customer auth, checkout, manual payment methods, payment proof upload, and order creation.

Phase 3 should add customer order tracking, notifications, profile editing, saved addresses, help/support, and privacy/security screens.

Phase 4 should add admin order review, payment approval/rejection, status updates, and notification creation.

Phase 5 should add admin CRUD for menu, categories, banners, branding, payment settings, delivery/pickup, opening hours, policies, and staff permissions.

Phase 6 should add print/PDF workflows, deployment hardening, offline polish, and Capacitor Android/iOS packaging.
