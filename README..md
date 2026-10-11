\# 🛡️ LinkShield AI





An open-source web app that investigates suspicious URLs. It queries several threat-intelligence services in parallel, combines their results into a trust score, and uses Google Gemini to explain the findings in plain English and Arabic.



!\[PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php\&logoColor=white)

!\[Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel\&logoColor=white)

!\[Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs\&logoColor=white)

!\[License](https://img.shields.io/badge/License-MIT-blue)



> 🎓 Graduation capstone project in Cybersecurity.



<!--

Add screenshots to docs/screenshots/ and uncomment this block:



<p align="center">

&#x20; <img src="docs/screenshots/home.png" width="48%" alt="Scan result" />

&#x20; <img src="docs/screenshots/report.png" width="48%" alt="PDF report" />

</p>

\-->



\## ✨ Features



\- \*\*Multi-engine URL analysis\*\* — runs \[VirusTotal](https://www.virustotal.com), \[urlscan.io](https://urlscan.io) and \[Google Safe Browsing](https://developers.google.com/safe-browsing) concurrently, plus an IP / ASN / country lookup for the target host.

\- \*\*Trust score\*\* — derived from the VirusTotal detection counts and Safe Browsing matches.

\- \*\*AI summary\*\* — Gemini produces a short, two-language (English + Arabic) explanation of the results.

\- \*\*Live preview \& screenshot\*\* — site screenshot from urlscan.io (with a fallback), and an embedded preview where the target site allows framing.

\- \*\*PDF security briefings\*\* — downloadable reports in English or Arabic, rendered with mPDF and proper Arabic text shaping.

\- \*\*Accounts\*\* — cookie-based SPA authentication (Laravel Sanctum). Anonymous scans can be linked to your account after you sign in.

\- \*\*Community "Intel" feed\*\* — users submit stories, threats, advice and knowledge posts (optionally anonymous, with an image). Admins review and approve them.

\- \*\*Admin dashboard\*\* — platform statistics, user management, post moderation.

\- \*\*Bilingual UI\*\* — English and Arabic with full RTL support.

\- \*\*7-day result cache\*\* — repeated scans of the same URL reuse recent results to save API quota.



\## 🧱 How it works



```

&#x20;URL ─▶ ScanController ─▶ SecurityInvestigationService

&#x20;                             │  (run in parallel)

&#x20;                             ├─ VirusTotal          → detection counts

&#x20;                             ├─ urlscan.io          → screenshot / page info

&#x20;                             ├─ Google Safe Browsing→ threat matches

&#x20;                             └─ ip-api.com          → IP, ASN, location

&#x20;                             │

&#x20;                             ▼

&#x20;                       Gemini → bilingual summary

&#x20;                             │

&#x20;                             ▼

&#x20;                 stored in DB (cached 7 days) → JSON → Vue SPA / PDF report

```



If an API key is missing, only that provider is skipped — the rest of the app keeps working (see \[API keys](#-api-keys)).



\## 🛠️ Tech stack



| Layer | Technology |

|---|---|

| Backend | Laravel 13, PHP 8.3+, Laravel Sanctum |

| Frontend | Vue 3, TypeScript, Pinia, Vue Router, Tailwind CSS 4, Vite |

| Database | SQLite by default (any Laravel-supported DB works) |

| PDF | mPDF (Arabic shaping) |

| Intelligence | VirusTotal, urlscan.io, Google Safe Browsing, Google Gemini |



\## 🚀 Getting started



\### Requirements



\- PHP 8.3+ and \[Composer](https://getcomposer.org)

\- Node.js 20.19+ (or 22+) and npm

\- SQLite (or MySQL / PostgreSQL)



\### Installation



```bash

git clone https://github.com/rllhv/LinkShield-AI.git

cd LinkShield-AI



composer install

npm install



cp .env.example .env

php artisan key:generate

touch database/database.sqlite      # skip if you use MySQL / PostgreSQL

```



Open `.env` and set at least:



```ini

ADMIN\_DEFAULT\_PASSWORD=choose-a-strong-password   # required for seeding

VIRUSTOTAL\_API\_KEY=...

URLSCAN\_API\_KEY=...

SAFE\_BROWSING\_API\_KEY=...

GEMINI\_API\_KEY=...

```



Then create the tables and the root admin account:



```bash

php artisan migrate

php artisan db:seed

```



\### Run in development



```bash

\# terminal 1

php artisan serve

\# terminal 2

npm run dev

```



Open <http://localhost:8000>. Sign in as the admin with the email from `ADMIN\_DEFAULT\_EMAIL` (default `admin@example.com`) and the password you chose.



> The seeder \*\*refuses to run without `ADMIN\_DEFAULT\_PASSWORD`\*\* — there is no built-in default password.



Shortcut: `composer dev` starts the server, queue worker, log viewer and Vite together.



\### Production build



```bash

npm run build

```



Before deploying, set `APP\_ENV=production`, `APP\_DEBUG=false`, serve over HTTPS with `SESSION\_SECURE\_COOKIE=true`, and set `FRONTEND\_URL` / `SANCTUM\_STATEFUL\_DOMAINS` to your domain. See also \[Known limitations](#-known-limitations).



\## 🔑 API keys



All keys are read from `.env` and are \*\*never\*\* committed. Each provider is optional:



| Variable | Service | If missing |

|---|---|---|

| `VIRUSTOTAL\_API\_KEY` | VirusTotal (free tier is rate-limited) | Detection counts are skipped |

| `URLSCAN\_API\_KEY` | urlscan.io | Falls back to a thumbnail screenshot service |

| `SAFE\_BROWSING\_API\_KEY` | Google Safe Browsing (Google Cloud Console) | Safe Browsing check is skipped |

| `GEMINI\_API\_KEY` | Google AI Studio | The app shows "AI analysis unavailable" |



\## 🔌 API overview



| Method | Endpoint | Auth | Description |

|---|---|---|---|

| `POST` | `/api/scan` | – | Scan a URL |

| `GET` | `/api/history` | – | Public feed of anonymous scans |

| `GET` | `/api/stats` | – | Aggregate statistics |

| `GET` | `/api/intel` | – | Approved community posts |

| `POST` | `/api/register`, `/api/login` | – | Create account / sign in |

| `GET` | `/api/my-scans`, `/api/my-intel` | user | Your own scans and posts |

| `POST` | `/api/intel` | user | Submit a post (pending approval) |

| `PUT` | `/api/user/profile`, `/api/user/password` | user | Account settings |

| `GET` | `/api/admin/\*` | admin | Stats, users, pending posts |

| `PATCH` | `/api/admin/posts/{id}/evaluate` | admin | Approve / reject a post |

| `GET` | `/report/download/{id}?lang=en\\|ar` | owner / anonymous | PDF briefing |



\## 🔒 Privacy notice



Scanning a URL sends it to third-party services: VirusTotal, urlscan.io (submitted as \*unlisted\*), Google Safe Browsing, ip-api.com, and Google Gemini (together with the scan results). A thumbnail service may be used as a screenshot fallback. \*\*Do not scan URLs that contain secrets\*\* (password-reset links, tokens, private invitations). Anonymous scans appear in the public history feed; scans made while signed in stay private to your account.



\## ⚠️ Known limitations



This is an educational project and has not been hardened for large-scale public deployment:



\- \*\*No rate limiting\*\* on `POST /api/scan`. Add Laravel's `throttle` middleware before exposing it publicly, otherwise anyone can exhaust your API quotas.

\- \*\*PDF reports of anonymous scans\*\* are reachable by their sequential ID.

\- \*\*`ip-api.com`\*\* is queried over plain HTTP (the free tier does not support HTTPS).

\- \*\*Gemini integration\*\* contains a local-development DNS workaround with fixed Google IP addresses, and a hard-coded model fallback list that may need updating. Review both before deploying.

\- \*\*The trust score is heuristic.\*\* A high score does not guarantee a site is safe, and a new malicious site may not be known to any engine yet.

\- \*\*Tests:\*\* the suite currently contains only Laravel's default example tests.



Found a security issue? Please open a GitHub issue (without exploit details) or contact the maintainer privately.



\## 📁 Project structure



```

app/Http/Controllers/   Scan, Auth, Post, Profile, Admin, Report

app/Services/           SecurityInvestigationService (all external APIs)

resources/js/           Vue SPA (views, components, Pinia stores, translations)

resources/views/        PDF report template

database/               migrations, seeder

routes/                 api.php, web.php

```



\## 📄 License



Released under the \[MIT License](LICENSE).

