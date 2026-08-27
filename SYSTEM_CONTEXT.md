# SYSTEM CONTEXT — TimTre.vn (SM) "Tìm Trẻ Em Thất Lạc"

> **Purpose of this file:** This is the canonical, machine-indexable ground-truth of the
> *current state* of the TimTre.vn codebase, built for Claude Code to follow throughout
> implementation work. It describes what the system **actually is today** (verified by direct
> code inspection on 2026-08-26), not what the old sprint-0 README claims it is.
>
> **How to read it:** Treat every `file:line` as evidence. Items I could not verify are
> explicitly tagged `UNVERIFIED`. This document intentionally separates *current state*
> (fact) from *upgrade opportunities* (future, each with a safe path + rollback). Do not
> treat an opportunity as a current limitation — context is the truth of today, not a
> ceiling for tomorrow.

---

## 0. Orientation

| Attribute | Value |
|---|---|
| Project | SM — "Website Tìm Kiếm Trẻ Em Thất Lạc" (Find Missing Children), FPT graduation project |
| Repo root | `/d/FPT_EDU/nam_2026/Study/DATN/repo/SM` |
| Primary stack | PHP 8.3 · **Laravel 13** · **Filament v5** (admin) · **Livewire 4** · MySQL 8.0 · Redis (predis) |
| AI subsystem | Python 3.11 **FastAPI + DeepFace (ArcFace)**, standalone microservice on `:8001` |
| Realtime | **Reverb** (websockets) + Pusher PHP SDK; **Horizon** for queues; **Redis** |
| Auth | Session + **Sanctum** (token) + **Socialite** (Google OAuth) + email **OTP** |
| Notifications | Mail (Mailtrap dev), DB, Broadcast, **FCM HTTP v1** push; strategy-pattern registry |
| Observability | **Sentry**, spatie/laravel-activitylog, Pail, Debugbar (dev) |
| App code size | 280 PHP files in `app/` · 51 migrations · 24 Eloquent models · 64 test files |
| AI code size | ~490 LoC Python (`ai-service/main.py`, `face_service.py`, `schemas.py`) |
| Ops docs | 18 markdown runbooks in `docs/` (deployment, security, load-test, etc.) |

**Excluded from context (noise, not part of app logic):** `vendor/`, `node_modules/`,
`ai-service/venv/`, `ai-service/.vite/`, `**/__pycache__/`, `storage/framework/`.

---

## 1. Architecture Overview

```
┌─────────────────────────┐      HTTP/Blade       ┌──────────────────────────┐
│  Browser (Blade+Livewire)│ ───────────────────▶ │  Laravel 13 (app/)       │
│  Public client + admin   │ ◀──── websockets ──── │  - web.php / api.php     │
└─────────────────────────┘      (Reverb)         │  - Filament admin (/admin)│
                                                  │  - Services / Jobs        │
└───────────────┬─────────────────────────────────┘
                │ X-Internal-Key (loopback only)   ┌──────────────────────────┐
                └───────────────────────────────▶ │  AI microservice :8001   │
                                                  │  FastAPI + DeepFace      │
   Laravel queue (face-match/kyc/notifications)   │  /encode /detect-face    │
   ── TriggerFaceMatchJob ── /compare ──────────▶ │  /compare /verify        │
                                                  └──────────────────────────┘
```

**Request flow (typical):** route (`routes/`) → middleware (`app/Http/Middleware/`) →
controller (`app/Http/Controllers/`) → service (`app/Services/`) / job (`app/Jobs/`) →
model (`app/Models/`) → DB (MySQL) + cache (Redis). AI work is offloaded to the Python
service via `FaceMatchService` (`app/Services/FaceMatchService.php`).

---

## 2. Subsystem Deep-Dives

### 2.1 Backend Core
**Purpose:** HTTP entry, routing, middleware, business services, Filament admin.

Key files (verified):
| Path | Role |
|---|---|
| `routes/web.php` (212 LoC) | Public + authenticated client routes |
| `routes/api.php` (99 LoC) | Public API v1 + internal trigger + health |
| `routes/auth.php`, `routes/channels.php`, `routes/console.php` | Auth, broadcast channels, console |
| `app/Http/Middleware/` | `Authenticate`, `EnsureEmailVerified`, `EnsureUserHasRole`, `InternalApiKey` |
| `app/Http/Controllers/` | Client, Api/V1, Auth, Verifier, Filament-free admin controllers |
| `app/Services/` | Domain services (see subsections) |
| `app/Repositories/` | `MissingChildRepository`, `HandoverPointRepository` (repository-light pattern) |
| `app/Filament/Admin/` | 11 Filament resources, pages, widgets (admin panel) |
| `app/Providers/Filament/AdminPanelProvider.php` | Admin panel wiring (path `/admin`, brand "TìmTrẻ.vn Admin") |

**Current state:** Routes are well-organized by middleware group (public / `auth` /
`auth+email.verified` / `role:THIRD_PARTY_VERIFIER` / `signed`). Filament discovers
resources/pages/widgets automatically from `app/Filament/Admin/`. `EnsureUserHasRole`
aborts 403 if `user()->role !== $role` (exact role match, not admin-inclusive).

**Inconsistencies:** README §"Cấu trúc thư mục" lists only `app/Http/Controllers`,
`app/Models`, `app/Providers/Filament`, `app/Notifications` — the real `app/` has 24
subfolders (Services, Repositories, Jobs, Events, Listeners, Policies, Observers,
Livewire, Rules, Support, Traits, etc.). The README is a Sprint-0 skeleton, not current.

**Risks:**
- `EnsureUserHasRole` does exact-match; combined with custom RBAC this is brittle if a
  route needs "admin OR moderator". Verify per-route usage `UNVERIFIED` (no misuse found yet).
- No central API versioning beyond `/api/v1` prefix; adding v2 requires care.

**Upgrade opportunities:**
- *U1 — Formalize service/repository boundaries.* Currently a mix; some logic lives in
  controllers (`MissingController`). **Safe path:** extract controller logic into existing
  `Services/` incrementally, file by file, keeping controllers thin. **Rollback:** revert
  the edited controller (no schema change). Priority: medium.

---

### 2.2 Data Layer & Schema
**Purpose:** Persistence model for the platform.

**Verified table count: 31 tables** (distinct `Schema::create` in `database/migrations/`).
Full list: `activity_log, admin_chat_assignments, age_progression_photos, articles,
chatbot_sessions, clues, comments, escalation_queue, face_match_results, failed_jobs,
fcm_tokens, found_children, handover_points, idempotency_keys, jobs, kyc_verifications,
messages, missing_children, notification_events, notification_recipients, notifications,
password_reset_tokens, personal_access_tokens, photos, provinces, support_ticket_replies,
support_tickets, third_party_verifier_profiles, user_consents, users, volunteer_assignments`.

**INCONSISTENCY (high):** `README.md:100` and `:134` state *"15 bảng được tạo"* and tests
expect exactly 15. Actual = **31**. The README reflects Sprint-0 only. `ProvincesSeeder`
seeds 34 provinces (`README.md:101`) — verify count in `database/seeders/ProvincesSeeder.php`
(`UNVERIFIED` exact number, claimed 34).

**Core entities & relationships (verified):**
- `User` (241 LoC) — `role` enum (`USER/VOLUNTEER/ADMIN/MODERATOR/THIRD_PARTY_VERIFIER`),
  `kyc_status`, `status` (`guarded` against mass-assignment), `SoftDeletes`,
  `LogsActivity` on role/status/kyc_status. HasMany MissingChild/FoundChild/Clue/Message/
  FcmToken/VolunteerAssignment; kycVerification (hasOne); thirdPartyVerifierProfile (hasOne).
- `MissingChild` (417 LoC) — headline entity; hasMany Photos (morph), Clues, Comments;
  hasOne KycVerification; belongsTo Province. Extensive casts/accessors.
- `FoundChild` (128 LoC) — parallel structure to MissingChild.
- `Photo` (56 LoC) — **polymorphic** (`model_type`/`model_id`). Stores `path_original,
  path_thumb, path_thumb_webp, path_display, path_display_webp, path_blurred` (child-safety
  blur), `has_face`, `face_encoding` (encrypted PII), `face_bounding_box` (array),
  `face_crop_path`, `detected_gender`, `ai_status`, `is_primary`, `sort_order`.
- `FaceMatchResult` (101 LoC) — `missing_id, found_id, missing_photo_id, found_photo_id,
  score, is_matched, confidence_level (HIGH≥0.85 / MEDIUM≥0.70 / LOW), source (REALTIME),
  model_used, status (PENDING/CONFIRMED/DISMISSED), confirmed_by/at, notified_at`.
  `confidenceLevelForScore()` reads `config('face_match.*')`.
- `KycVerification` — `front_image_path, back_image_path, selfie_path` (private disk),
  `decrypted_id_number` accessor (encrypted at rest), `review_level`.
- `Clue, Comment, Message, ChatbotSession, NotificationEvent, NotificationRecipient,
  FcmToken, HandoverPoint, ThirdPartyVerifierProfile, VolunteerAssignment,
  AdminChatAssignment, EscalationQueue, Article, SupportTicket(+Reply), UserConsent,
  IdempotencyKey, Province` — supporting entities.

**Full-text search:** README mentions `innodb_ft_min_token_size=2` for Vietnamese search.
`MissingChildSearchService` exists (`app/Services/MissingChildSearchService.php`) — verify
FULLTEXT index presence in migrations `UNVERIFIED` (the migration list shows no explicit
`fulltext` migration name; check `missing_children` table columns). Flag as **unverified**.

**Risks:**
- 31 tables vs documented 15 → onboarding/CI confusion; `phpunit` "15 tables" expectation
  (if any) would fail. Audit tests for hard-coded 15.
- `face_encoding` is encrypted (good) but stored as TEXT — large payloads; ensure indexed
  only by `has_face`/`ai_status`, never on the blob.

**Upgrade opportunities:**
- *U2 — Add composite/foreign-key indexes where joins are hot.* `TriggerFaceMatchJob`
  queries `photos` by `model_type`+`has_face`+`face_encoding` (verified `TriggerFaceMatchJob.php:117`).
  Ensure a composite index exists. **Safe path:** new additive migration
  `ADD INDEX ...`. **Rollback:** `DROP INDEX` in `down()`. Priority: high (perf).
- *U3 — Embedding store for scale* (see 2.3 U7).

---

### 2.3 AI Face-Matching Microservice + Integration  ⭐ crown jewel
**Purpose:** Stateless face encode / detect / compare / verify, supporting missing↔found
matching and eKYC selfie↔ID verification.

**AI service (`ai-service/main.py`, 277 LoC) — verified endpoints:**
| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/health` | `X-Internal-Key` | liveness |
| GET | `/ready` | `X-Internal-Key` | model loaded? |
| POST | `/encode` | `X-Internal-Key` | image → 512-d ArcFace encoding (or 422 no_face) |
| POST | `/detect-face` | `X-Internal-Key` | bbox + base64 crop + detected_gender |
| POST | `/compare` | `X-Internal-Key` | `{encoding_a, encoding_b}` → cosine score + is_matched |
| POST | `/verify` | `X-Internal-Key` | id_document + selfie → score (eKYC) |

**Security posture (verified — strong):**
- `validate_production_settings()` (`main.py:59`) enforces `INTERNAL_KEY` ≥32 chars and
  NOT in a blocklist, and `BIND_HOST` must be loopback (`127.0.0.1/::1/localhost`). Raises
  `RuntimeError` on boot if violated.
- Image validation (`read_validated_image`, `main.py:84`): MIME allowlist jpeg/png → 415;
  >10 MB → 413; PIL `verify()` + decompression-bomb guard → 415; >40 Mpx → 413.
- Request timeout middleware (default 30 s → 408). `X-Internal-Key` checked on every endpoint.
- `requirements.txt` is pinned (deepface, fastapi, uvicorn, pillow, numpy, etc.) — contradicting
  README §"Cài đặt AI" which says `pip install deepface fastapi uvicorn` (incomplete; venv
  already present).

**Face math (verified `face_service.py`):**
- `encode()`: tries detectors `mtcnn,opencv` in order, `DeepFace.represent` (ArcFace),
  returns 512-float embedding.
- `detect_face()`: `DeepFace.extract_faces` → 112×112 JPEG crop base64 + gender via
  `DeepFace.analyze`.
- `compare()`: **manual cosine similarity** in Python (`face_service.py:128`) — the service
  does NOT use DeepFace's built-in verifier. `is_matched = score >= SCORE_THRESHOLD (0.70)`.
- `MAX_CONCURRENT_INFERENCES=1` (semaphore, `main.py:41`) → **single-threaded inference**.

**Laravel integration contract (verified `FaceMatchService.php`, 266 LoC):**
- Base URL `config('face_match.service_url')` (default `http://127.0.0.1:8001`), header
  `X-Internal-Key: config('face_match.internal_key')`.
- Methods: `isHealthy()→/health`, `encode()→/encode`, `detectFace()→/detect-face`,
  `compare()→/compare`, `verifyImages()→/verify`. 422 → no-face; else `AIServiceUnavailableException`.
- `prepareUploadFile()` converts non-jpeg/png (webp/gif/bmp) to JPEG q90 before upload
  (AI service only accepts jpeg/png).
- Internal trigger route `POST /api/internal/face-match/trigger` guarded by `internal.key`
  middleware (`app/Http/Middleware/InternalApiKey.php`, constant-time `hash_equals`) →
  `FaceMatchController@trigger`.
- Config (`config/face_match.php`): `score_threshold=0.70`, `high_confidence_threshold=0.85`,
  `timeout=30`, `health_timeout=3`, `queue='face-match'`, `model='ArcFace'`.

**Matching algorithm (verified `TriggerFaceMatchJob.php`, 129 LoC):**
- On new `Photo` (dispatched from `ProcessPhotoJob` after `encode`), decrypt its encoding
  (`Crypt::decryptString`), then iterate **ALL** opposite-type photos with `has_face=1` AND
  `face_encoding IS NOT NULL` (`targetPhotos()`, `:111`), calling `/compare` for each.
- For each pair: compute `is_matched` (score≥0.70), `confidence_level`, `firstOrNew` on
  `(missing_photo_id, found_photo_id)`, set `status=PENDING`, dispatch `FaceMatchFound` event
  once if matched & not yet notified. `tries=3`, `timeout=120`, `backoff=[60,180,600]`.

**Risks:**
- **Throughput:** `MAX_CONCURRENT_INFERENCES=1` + per-upload O(N) HTTP `/compare` calls.
  Compare is cheap (cosine on already-stored encodings), but the AI service is a single
  worker; under load this serializes. `UNVERIFIED` whether supervisor runs multiple workers.
- **Cold start / model load:** ArcFace weights load lazily; first request after restart is
  slow. `WARMUP_ON_STARTUP` exists but default `false` (`main.py:43`).
- **No embedding reuse across the wire:** encodings live in Laravel DB (encrypted); the AI
  service re-encodes only on `/encode`. Compare could theoretically run in PHP (pure cosine)
  but is kept in the AI service for model-consistency — acceptable design choice.

**Upgrade opportunities:**
- *U4 — Raise AI concurrency safely.* Set `MAX_CONCURRENT_INFERENCES` >1 **only if** RAM
  allows (ArcFace ~2 GB/worker). **Safe path:** env change + supervisor restart + load test
  (`scripts/load-test/ai-queue.js`). **Rollback:** revert env. Priority: medium.
- *U5 — GPU / ONNX-TF-Lite inference.* Cuts per-image latency. **Safe path:** stand up a
  second container on GPU behind same `FACE_MATCH_SERVICE_URL` after validation; keep CPU
  as fallback. **Rollback:** repoint env to CPU instance. Priority: low (depends on VPS).
- *U6 — `/compare-batch` endpoint.* Accept arrays of encodings, compute in one process.
  **Safe path:** add new endpoint, opt-in from `TriggerFaceMatchJob`. **Rollback:** revert job.
  Priority: medium.
- *U7 — Embedding store + ANN index (highest leverage).** Encodings are already persisted
  (encrypted) in `photos.face_encoding`. Add a dedicated, queryable embedding store
  (Redis/Postgres+pgvector/Qdrant) and replace the O(N) loop with k-NN candidate fetch +
  exact re-rank. **Safe path:** new service + feature flag `face_match.use_vector_index`;
  backfill from existing encrypted encodings; keep `/compare` path as fallback. **Rollback:**
  flip flag. Priority: high for scale.
- *U8 — Threshold tuning on child data.** 0.70/0.85 are defaults; validate against a
  children's-face dataset (the `benchmark/` set is LFW-adult, not children). **Safe path:**
  env-driven thresholds already exist — A/B on staging. **Rollback:** revert env. Priority: medium.

---

### 2.4 Frontend / Client
**Purpose:** Public web client + admin UI.

**Verified:** Client is **server-rendered Blade + Livewire**, NOT a separate SPA.
- `resources/views/`: **104 Blade files** organized by feature (`articles, auth, chat,
  components/*, found_children, handover-points, legal, livewire/*, map, missing,
  notifications, pages, pdf, profile, stats, support, verifier`).
- Livewire components (`app/Http/Livewire/` + `app/Livewire/`): `Chat/ChatBox, ChatContainer,
  ChatList`, `MissingChild/ClueForm`, `Missing/HandoverSuggestionCard`, `Volunteer/AssignmentBoard`.
- `extracted_frontend/` and `demo_fontend.zip` are a **static demo prototype** (no
  `package.json`) — NOT the production client. Treat as legacy/reference only.
- Assets built via Vite (`vite.config.js`, `public/build/`, `public/js`, `public/css`).
- Map: `components/map`, `HeatmapController`, `MapController`, `MapDataController` (api),
  `HandoverPointFinderService`. **Map library UNVERIFIED** (likely Leaflet or Google Maps —
  confirm by grepping `resources/` for `leaflet`/`L.marker`/`google.maps`).

**Current state:** Strong componentization by feature; client and admin share Blade
conventions. Legal pages exist (`legal/terms-of-service`, `privacy-policy`,
`child-data-policy`) — compliance surfaced to users.

**Risks:**
- `extracted_frontend/` may mislead a new contributor into thinking it's the live client.
  Recommend archiving/moving it out of the app root.
- Map library unverified — pin it and document for deployment (API keys/CDN).

**Upgrade opportunities:**
- *U9 — Archive `extracted_frontend/`** to `docs/` or remove. **Safe path:** `git mv` to
  `archive/`. **Rollback:** `git mv` back. Priority: low (hygiene).
- *U10 — Confirm & document map provider**, add API-key config + fallback. **Safe path:**
  additive config. Priority: medium.

---

### 2.5 Realtime / Chat / Notifications / Queue
**Purpose:** Live chat, push, async jobs.

**Verified:**
- **Broadcast channels** (`routes/channels.php`): `private-chat.{userId}` (owner only),
  `missing-child.{missingId}` (**returns `true` for everyone — NO auth**),
  `user.{userId}` (owner only), `admin-chat-assignment.{assignmentId}` (assigned admin only).
- **Chat assignment** (`ChatAssignmentService.php`, 74 LoC): least-loaded online admin
  (`last_active_at` within 5 min), with `DB::transaction` + `lockForUpdate` to serialize.
  Model `AdminChatAssignment` (admin_user_id, requester_user_id, status, assigned_at).
  → This is an **improvement over the O-05 spec** which described strict round-robin; the
  implementation is least-loaded balancing (better). Status: **IMPLEMENTED**.
- **Jobs** (`app/Jobs/`): `TriggerFaceMatchJob` (face-match queue), `ProcessPhotoJob`
  (encode/detect), `ProcessKycJob`, `SendFcmNotificationJob`,
  `DispatchMatchConfirmedNotificationsJob`, `DispatchProvinceCaseApprovedNotificationsJob`,
  `HorizonSmokeFailureJob`. Queues per `composer.json` dev script:
  `face-match,kyc,notifications,default`.
- **Notifications** (`config/notification-policies.php`): strategy registry keyed by
  `NotificationEvent::TYPE_*` × recipient role (USER/VOLUNTEER/THIRD_PARTY_VERIFIER/ADMIN/
  MODERATOR). Strategies under `app/Services/Notification/Strategies/`. Delivery via
  `NotificationDeliveryService` + `FcmHttpV1PushSender` (FCM HTTP v1, not legacy).
- **OTP / email:** `OtpService`, `Notifications/OtpVerificationNotification`, Mailtrap dev.
- **Idempotency:** `IdempotencyKey` model + `HasIdempotency` trait + `ClueForm` tests —
  duplicate-submit protection exists.

**Risks (child-safety relevant):**
- **`missing-child.{missingId}` channel has no authorization** (`channels.php:14`). Whatever
  is broadcast there is visible to any subscriber. Audit which Events use it
  (`UNVERIFIED` which event; likely `PostStatusChanged`/`ClueLocationUpdated`). If it carries
  PII or case-status before moderation, this is an info-leak. **Fix before production.**
- Chat messages: `MessageSanitizerService` exists — verify it is applied to ALL chat input
  (including admin side). `UNVERIFIED` coverage.
- No visible dead-letter / failed-job alerting beyond `HorizonSmokeFailureJob` +
  `AIJobFailureNotifier`. Acceptable but thin.

**Upgrade opportunities:**
- *U11 — Authorize `missing-child` channel.* Restrict to participants/admins/verifiers.
  **Safe path:** edit `channels.php` closure (return `auth()->user()` membership check).
  **Rollback:** revert closure. Priority: **high (security/child-safety)**.
- *U12 — Guarantee chat sanitization** everywhere; add a reporting/moderation queue for
  abusive messages. **Safe path:** middleware on chat send + new `escalation_queue` hook
  (table already exists). **Rollback:** remove middleware. Priority: medium.
- *U13 — Failed-job alerting** to Sentry/Slack for `face-match`/`kyc` queues. **Safe path:**
  Horizon `failed_job` webhook + `HorizonSmokeFailureJob`. **Rollback:** config only. Priority: low.

---

### 2.6 Auth / RBAC / KYC / Security / Compliance
**Purpose:** Identity, access control, identity verification, child-safety.

**Verified:**
- **RBAC: custom, not spatie/permission.** `User.role` is a string enum; `role/kyc_status/
  status` are `guarded`. Helpers: `isAdmin()` (ADMIN|MODERATOR), `canAccessPanel()`=isAdmin,
  `canAccessVolunteerFeatures()` = VOLUNTEER + not banned + `kyc_status=VERIFIED`.
  `THIRD_PARTY_VERIFIER` role gates `/verifier/*` (`web.php:206`, `EnsureUserHasRole` or
  `role:` middleware).
- **Auth flows:** `AuthController` (register/login/OTP/reset), `GoogleController` (Socialite),
  `OtpService` (email OTP). Email must be verified for most actions (`EnsureEmailVerified`,
  `web.php:126`).
- **KYC (eKYC) — IMPLEMENTED, not spec-only.** `EkycService` (`347 LoC`):
  - OCR via **FPT.AI** or **VNPT** (config-driven) with **local Tesseract fallback**
    (`vie+eng`, psm 6). Normalizes all providers to one internal contract.
  - Face match selfie↔ID via AI `/verify` (`matchSelfieWithId`).
  - `auto_verifiable = faceScore ≥ 0.85 AND nameMatches AND idNumberMatches`.
  - `idNumbersMatch` uses **constant-time `hash_equals`** — good practice for sensitive PII.
  - `KycVerification` stores images on **private disk**, ID number **encrypted at rest**
    (`decrypted_id_number` accessor). `review_level` field for human review escalation.
  - Flow: `KycController@store` (auth:sanctum, throttle 5/min) → `ProcessKycJob` →
    `EkycService::verify` → status update. Filament `KycVerifications` resource for review.
- **Compliance/Trust/Safety signals present:**
  - `Photo.path_blurred` — blurred variant for safe display pre-moderation.
  - `UserConsent` (false-report liability consent), `legal/*` pages incl. `child-data-policy`.
  - `EscalationQueue` model + `EscalationQueueWidget` (admin) — moderation surfacing.
  - `SecureImage` / `SecureDocument` validation rules (`app/Rules/`) + `SecureUploadFilename`
    + `FilamentUploadSecurityTest` — upload hardening exists.
  - `ActivityLog` on sensitive actions (role/status/kyc/face-match status).
- **SEO:** `SitemapController`, `routes/web.php:33` `/sitemap.xml`, `MissingChild` has
  `seo_fields` (migration `2026_06_15_143148`). Structured-data (JSON-LD) `UNVERIFIED`.

**Risks:**
- Custom RBAC scales poorly if fine-grained permissions are needed later (e.g., "moderator
  can only edit in own province"). Currently coarse role gates.
- KYC auto-verify at 0.85 + name/id match is reasonable, but **Tesseract fallback accuracy
  is low** for Vietnamese ID — only safe as fallback (code already prefers FPT.AI/VNPT).
- `EnsureUserHasRole` exact-match could 403 a MODERATOR on an ADMIN-gated route; audit usage.
- No explicit account-lockout on OTP brute force visible (`UNVERIFIED` in `OtpService`).

**Upgrade opportunities:**
- *U14 — Consider spatie/permission** for granular permissions while keeping the `role` enum
  for top-level gates. **Safe path:** additive package + pivot tables; migrate gradually.
  **Rollback:** remove package + migrations. Priority: low-medium.
- *U15 — Harden OTP:** add attempt caps + short TTL + rate-limit per-IP/account in
  `OtpService`. **Safe path:** additive throttle + cache counter. **Rollback:** revert.
  Priority: high (security).
- *U16 — Enforce blurred-display-until-CONFIRMED** policy in all public photo views; add
  watermark for confirmed matches. **Safe path:** view-layer rule + policy. **Rollback:**
  revert. Priority: medium (child-safety).
- *U17 — JSON-LD structured data** on missing-child pages (critical for search discoverability
  of lost children). **Safe path:** additive Blade partial. **Rollback:** remove partial.
  Priority: medium (impact).

---

### 2.7 DevOps / Config / Deploy / Test / Tooling
**Purpose:** Environment, deployment, quality.

**Verified:**
- **Config:** `config/` has 21 files incl. `face_match, notification-policies, notification,
  legal, reverb, horizon, sanctum, sentry, services, livewire, dompdf`. Env-driven throughout.
- **Tests:** **64 test files** (Feature + Unit) covering face-match, KYC (submit/ policy/
  image-access/ ekyc), chat assignment/isolation, notifications, volunteer policy,
  security rules, chatbot intents, load-test scripts. `phpunit.xml` present. Strong coverage
  for the sensitive subsystems.
- **CI:** `.github/workflows/tests.yml` exists (`UNVERIFIED` exact jobs — likely PHPUnit +
  Pint). No Dockerfile/docker-compose found at root (`UNVERIFIED`) — SAIL present as dev dep.
- **Deploy scaffolding present:** `docs/DEPLOYMENT_RUNBOOK.md`, `docs/AI_SERVICE_DEPLOYMENT.md`,
  `docs/HORIZON_OPERATIONS.md`, `docs/SECURITY_REVIEW.md`, `docs/LOAD_TEST_RESULT.md`,
  `docs/QUERY_OPTIMIZATION.md`, supervisor configs (`supervisor-timtre-ai.conf.example`,
  `-horizon`, `-alert`), `logrotate` example, `scripts/backup.sh`, `scripts/monitoring/`.
  → Deployment is **partially scaffolded**, not fully automated.
- **Local dev:** `composer dev` script runs serve + `queue:work` (4 queues) + vite + reverb +
  AI service (venv). Multi-process, documented.
- **Tooling:** Pint, Debugbar, Pail, Collision, Sail. `benchmark/run_benchmark.py` + LFW CSVs.

**Risks:**
- No containerization for the AI service → VPS deploy of Python is manual/supervisor-based
  (works, but not portable/scale-friendly).
- CI scope `UNVERIFIED` — confirm it actually runs the 64 tests + Pint on PR.
- `.env.example` vs code: flag keys used in code but missing from example (`UNVERIFIED` —
  compare `config/face_match.php` keys against `.env.example`).

**Upgrade opportunities:**
- *U18 — Containerize AI service** (Dockerfile + supervisor-in-container) for reproducible
  VPS deploy + easy GPU swap. **Safe path:** additive Dockerfile + compose; keep supervisor
  path as fallback. **Rollback:** use supervisor path. Priority: medium.
- *U19 — Strengthen CI:** run PHPUnit + Pint + (optional) AI service smoke in GitHub Actions.
  **Safe path:** extend `tests.yml`. **Rollback:** revert workflow. Priority: high (quality gate).
- *U20 — Backup/restore runbook** (DB + private disk photos + embeddings) beyond
  `scripts/backup.sh`. **Safe path:** additive cron + verify-restore drill. **Rollback:** n/a.
  Priority: medium.

---

## 3. Cross-Cutting Inconsistencies (doc vs code) — verified

| # | Doc says | Code says | Severity |
|---|---|---|---|
| C1 | README: "15 bảng" | **31 tables** exist | High (onboarding/CI) |
| C2 | README AI install: `pip install deepface fastapi uvicorn` | `ai-service/requirements.txt` pinned + venv present | Low |
| C3 | README dir structure (4 app/ subfolders) | `app/` has 24 subfolders | Low (doc staleness) |
| C4 | O-05 spec: round-robin chat | Implemented as **least-loaded** assignment | Info (improvement) |
| C5 | README: Laravel 13 (composer `^13.0`) | Matches | OK |
| C6 | "extracted_frontend" implied live | It's a static demo, not the client | Low (hygiene) |

---

## 4. Consolidated Upgrade Opportunities (safe path + rollback)

| ID | Opportunity | Subsystem | Priority | Safe Path | Rollback |
|---|---|---|---|---|---|
| U2 | Hot-path composite indexes (photos queries) | Data | High | Additive migration `ADD INDEX` | `DROP INDEX` in down() |
| U3 | Embedding store (persistent, queryable) | AI/Data | High | New service + feature flag; backfill from encrypted encodings | Flip flag |
| U7 | ANN index (k-NN) replacing O(N) loop | AI | High | `/compare-batch` + vector store behind flag | Revert flag/job |
| U11 | Authorize `missing-child` broadcast channel | Security | **High** | Edit `channels.php` closure | Revert closure |
| U15 | OTP brute-force hardening | Security | High | Additive throttle + cache counter | Revert |
| U19 | CI runs PHPUnit + Pint on PR | DevOps | High | Extend `tests.yml` | Revert workflow |
| U1 | Thin controllers / formal services | Backend | Medium | Incremental extract, file by file | Revert controller |
| U4 | Raise AI concurrency (if RAM allows) | AI | Medium | Env change + load test | Revert env |
| U6 | `/compare-batch` endpoint | AI | Medium | New endpoint, opt-in job | Revert job |
| U8 | Threshold tuning on child data | AI | Medium | Env-driven A/B on staging | Revert env |
| U12 | Chat sanitization + moderation queue | Realtime | Medium | Middleware + escalation hook | Remove middleware |
| U16 | Blurred-display-until-CONFIRMED + watermark | Compliance | Medium | View-layer rule + policy | Revert |
| U17 | JSON-LD structured data | SEO | Medium | Additive Blade partial | Remove partial |
| U10 | Pin/document map provider | Frontend | Medium | Additive config | n/a |
| U5 | GPU/ONNX inference | AI | Low | Second container behind same URL | Repoint env |
| U9 | Archive `extracted_frontend/` | Frontend | Low | `git mv` to archive/ | `git mv` back |
| U13 | Failed-job alerting | Realtime | Low | Horizon webhook | Config only |
| U14 | spatie/permission (granular) | Auth | Low-Med | Additive package + pivot | Remove package |
| U18 | Containerize AI service | DevOps | Medium | Additive Dockerfile | Use supervisor |
| U20 | Backup/restore runbook | DevOps | Medium | Cron + restore drill | n/a |

---

## 5. Self-Review (what was verified vs not)

**Verified by direct inspection (file:line cited):** AI service endpoints/security/math;
`FaceMatchService` contract; `TriggerFaceMatchJob` algorithm; `User` RBAC/KYC helpers;
`ChatAssignmentService` least-loaded; `channels.php` 4 channels (incl. the open one);
`EkycService` OCR+face+constant-time id match; `Photo` polymorphic + blurred variant;
`FaceMatchResult` schema/confidence; 31-table count; Blade+Livewire client (104 files);
Filament admin wiring; notification strategy registry; 64-test suite presence; ops docs.

**UNVERIFIED (needs human/tool confirmation before relying on it):**
- Exact province seed count (claimed 34) and FULLTEXT index existence for Vietnamese search.
- Which specific Event broadcasts on the open `missing-child` channel.
- `OtpService` brute-force/lockout behavior; `MessageSanitizerService` coverage on admin side.
- `.env.example` completeness vs all `config/*` keys.
- GitHub Actions `tests.yml` exact job list; presence of Dockerfile.
- Map library identity (Leaflet vs Google Maps).
- Whether supervisor runs >1 AI worker (concurrency reality).

**Note on method:** This document was produced by **direct code inspection**, not by the
parallel sub-agent scan that was attempted first (those sub-agents failed on a model-auth
error in this environment and returned no usable output). All claims here are backed by the
cited files; the UNVERIFIED list above is explicit rather than guessed.

---

## 6. Open Questions for the Human Owner
1. Is the 31-vs-15 table gap intentional (README just stale) or is a cleanup/migration missing?
2. Should the `missing-child` broadcast channel be locked down before any production traffic?
3. What is the target VPS spec (RAM/CPU/GPU)? Determines U4/U5 feasibility.
4. Is there a children's-face dataset to tune 0.70/0.85 thresholds (U8), since `benchmark/`
   is LFW-adult?
5. Which map provider is in use, and where are its API keys configured?
6. Should `extracted_frontend/` be archived out of the app root (U9)?

---

*Generated 2026-08-26 by direct inspection. Supersedes stale Sprint-0 README claims.
Keep this file in sync with the code: update the relevant subsection when a subsystem changes.*
