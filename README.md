# HaatKatha

An AI-assisted digital platform for local artisans, built as a Final Year
project (BSc Software, Gauhati University). Artisans list handmade products;
AI helps them write descriptions, translate Assamese ↔ English, and suggest
keywords. Customers browse, search, and send enquiries directly to artisans.

This is the **first working version (MVP)**, matching the scope in the
project synopsis (Section 15). Payments, delivery, and advanced AI features
are intentionally left for next semester (Section 18).

## Stack

PHP (procedural, no framework) · MySQL · Bootstrap 5 · vanilla JavaScript ·
Gemini API for AI features.

## Project structure

The codebase is kept deliberately small and flat rather than split into a
deep MVC tree — easier to explain in a viva, easier to keep track of.

```
haatkatha/
├── config.php          # DB connection, session, API key loading
├── functions.php       # shared helpers, auth guards, AI calls
├── header.php / footer.php   # shared page chrome
├── auth.php             # register + login + logout
├── index.php             # public catalogue: browse / search / filter
├── product.php           # single product page + artisan story + enquiry form
├── dashboard.php         # artisan area: profile + product CRUD + AI buttons
├── admin.php              # admin area: approve products/artisans, manage users/categories
├── ai_action.php          # small AJAX endpoint the dashboard calls for AI features
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── uploads/          # product photos land here
└── database/schema.sql
```

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and
   **MySQL** from the control panel.
2. Copy this whole `haatkatha` folder into `htdocs/` (e.g.
   `C:\xampp\htdocs\haatkatha` on Windows).
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create nothing
   manually — just go to the **Import** tab and import
   `database/schema.sql`. This creates the `haatkatha` database, all
   tables, starter categories, and a seeded admin account.
4. Get a free Gemini API key at <https://aistudio.google.com/app/apikey>.
   Copy `api_key.txt.example` to `api_key.txt` and paste your key inside
   (this file is git-ignored, so your key stays local).
5. Visit `http://localhost/haatkatha/index.php` in your browser.

**Demo admin login:** `admin@haatkatha.local` / `Admin@123`
(change this password once you're in — there's no "change password" screen
yet, so for now just update the `password` column via phpMyAdmin with a new
`password_hash()` value, or add that screen yourself as a next step).

## How each role works

- **Customer** — browses `index.php`, opens a product, sends an enquiry.
  No login required for browsing; enquiries are simple name+contact forms.
- **Artisan** — signs up with role "Artisan", then uses `dashboard.php` to
  fill in their profile/story and add products. New/edited products go in
  as `pending` until the admin approves them.
- **Admin** — logs in and uses `admin.php` to approve/reject products,
  approve artisan profiles, block misbehaving users, and manage categories.

## AI features (Section 8 of the synopsis)

All three live behind buttons on the "Add/Edit product" form in the artisan
dashboard, calling `ai_action.php` which talks to the Gemini API:

- **Generate with AI** — writes a draft description from name + material.
- **Translate to Assamese** — translates whatever is in the description box.
- **Suggest keywords with AI** — proposes search keywords for the listing.

Every AI call is also logged to the `ai_logs` table for later review.

If `api_key.txt` is empty, the buttons will show a friendly "AI is not
configured yet" message instead of failing silently.

## Known limitations (intentional, see synopsis Section 17)

- No payments or delivery integration yet.
- No password-reset flow yet.
- Image uploads aren't resized/validated beyond a basic extension check —
  fine for a college project demo, worth hardening before any real deploy.
- Translation currently only supports English ↔ Assamese text fields, not
  a full bilingual UI.

## Suggested next steps

1. Import the schema and get the site running locally end to end.
2. Add a couple of test artisans and products so the catalogue isn't empty.
3. Test the AI buttons with your Gemini key.
4. Push this repo to GitHub (see below) and start working week-by-week
   through the timeline in the synopsis (Section 19).

## Git / GitHub

This folder is already a Git repository with an initial commit. To push it
to your own GitHub:

```bash
git remote add origin https://github.com/<your-username>/haatkatha.git
git branch -M main
git push -u origin main
```
