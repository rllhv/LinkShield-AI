# 🛡️ LinkShield AI

An AI-powered web platform designed to analyze and investigate suspicious URLs. The system aggregates threat telemetry across multiple intelligence engines in parallel, computes a unified risk score, and utilizes Google Gemini to provide clear, bilingual security briefings.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)
![Status](https://img.shields.io/badge/Status-Completed-success)

> 🎓 **Academic Capstone Project in Cybersecurity**  
> *Developed as a graduation project demonstrating automated threat intelligence analysis, multi-engine integration, and defensive security engineering.*

---

## 🌟 Core Highlights

- **Multi-Engine Threat Aggregation** — Integrates VirusTotal, urlscan.io, and Google Safe Browsing concurrently, alongside automated network intelligence (IP, ASN, and Geolocation).
- **Heuristic Confidence Scoring** — Synthesizes raw detection ratios and engine verdicts into a unified risk rating.
- **AI-Driven Security Analysis** — Employs Google Gemini to transform complex detection metrics into contextual bilingual (Arabic & English) summaries.
- **Safe Sandboxed Inspection** — Renders isolated previews and site captures without exposing the user to live malicious assets.
- **Automated Incident Briefings** — Generates exportable security analysis reports formatted with full Arabic and English support.
- **Security Intel Board** — Moderated community feed for sharing emerging threat findings and advisories.

---

## 🧱 Architecture & Flow
Targeted URL ──▶ Inspection Pipeline
│
┌──────────────┼──────────────┐
▼              ▼              ▼
VirusTotal      urlscan.io    Safe Browsing
│              │              │
└──────────────┼──────────────┘
│
▼
Google Gemini
(Bilingual Threat Intel)
│
▼
Unified Incident Report & UI
---

## 🛠️ Technical Stack

- **Backend:** Laravel 13, PHP 8.3+, Laravel Sanctum
- **Frontend:** Vue 3, TypeScript, Pinia, Tailwind CSS 4, Vite
- **Threat Intelligence:** VirusTotal API, urlscan.io API, Google Safe Browsing API, Google Gemini AI
- **Reporting & Storage:** SQLite, mPDF Engine
