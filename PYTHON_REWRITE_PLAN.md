# PMA2 Python/Django Rewrite Plan

## Framework Recommendation: Django

Django is the right choice here. Its built-in admin panel replaces most of `admin2/`, its ORM handles the data model, its auth system replaces the current two-tier role system, and its template engine + class-based views map cleanly to the current PHP structure. FastAPI/Flask would require building all of that from scratch.

---

## The Biggest Design Problem to Solve First

The current schema uses **dynamic table names** (`assess_[code]_[year]_p3_m2`, etc.) — one set of tables per module per year. This is a significant anti-pattern that makes queries complex and migrations impossible. The rewrite's single biggest win is normalising this into a proper relational model:

```
Module          (replaces projects_setup + dynamic table families)
  ├─ AssessmentPart    (parts 1–5, weights, dates, max marks)
  ├─ Student           (replaces students_[x])
  │   └─ ProjectAllocation  (replaces allocated_[x], with supervisor/marker FKs)
  ├─ AssessmentQuestion     (replaces assessqs_[x]_p[n])
  ├─ Mark                   (replaces assess_[x]_p[n]_m[n] — one row per student/part/marker)
  │   └─ QuestionResponse   (per-criterion score + feedback)
  ├─ MarkTotal              (replaces assess_total_[x])
  └─ FeedbackRecord         (replaces feedback_records_[x])

Staff           (replaces staff_names)
  └─ ModuleRole  (staff ↔ module with role: marker, module_admin, supervisor)

AdminUser       (replaces admin_users — super admin only, use Django's built-in)
AssessmentCriteria  (global rubric, currently separate table)
```

---

## Phase Plan

### Phase 1 — Architecture & Setup (1–2 days)
- Finalise data model (review edge cases: third markers, numeric vs rubric parts, programme associations)
- Set up Django project: apps (`core`, `modules`, `assessment`, `students`, `staff`, `exports`)
- Configure PostgreSQL (upgrade from MySQL — better Django integration, proper enum support)
- Set up dev environment, linting (ruff), testing (pytest-django), pre-commit hooks

### Phase 2 — Models & Database (3–5 days)
- Write all Django models with proper constraints, indexes, `__str__` methods
- Write and test migrations
- Add `django-guardian` or a custom `Role`/`Permission` model for per-module RBAC

### Phase 3 — Authentication & Authorisation (2–3 days)
- Integrate with existing auth mechanism (server `REMOTE_USER` if still needed, or move to Django's session auth with proper login page)
- Custom permission decorators: `@require_module_access`, `@require_module_admin`, `@require_super_admin`
- Django middleware for school/module context (replaces the current cookie/session school selection)

### Phase 4 — Core Staff-Facing Views (5–8 days)
- Module selection dashboard (`projectmarking_select_bs.php`)
- Marking list table (`projectmarking_bs.php`) — DataTables-compatible JSON endpoint
- Individual assessment forms (`assess_minor_part_questions_bs.php` and side-by-side variants)
- Student/project detail and edit (`modify_student_project_details.php`)
- Overall feedback summary (`show_overall_mark_feedback_pdf.php`)

### Phase 5 — Assessment Workflow (4–6 days)
- Mark entry + auto-save (replace the current form POST with htmx or fetch API)
- Mark locking logic
- Third-marker determination
- Mark aggregation / total calculation
- Numeric vs rubric-based parts

### Phase 6 — Outputs (3–5 days)
- PDF generation: **WeasyPrint** (renders HTML templates to PDF — much easier to maintain than mPDF since you write normal HTML/CSS)
- Email with PDF attachments (Django's `EmailMessage`, configurable SMTP backend)
- Excel export (`openpyxl`)
- ZIP download of bulk PDFs

### Phase 7 — Admin Panel (3–4 days)
- Customise Django's built-in admin for: module setup, staff management, assessment criteria, admin users
- Write custom admin views for the things Django admin can't express well (e.g. the bulk "lock parts" page, mark upload from xlsx)
- This replaces most of `admin2/`

### Phase 8 — Proposals Module (2–3 days)
- Port `proposals_bs/` — the proposals workflow is relatively self-contained

### Phase 9 — Data Migration (3–5 days)
- Write a one-shot migration script that reads the existing MySQL dynamic tables and writes to the new PostgreSQL schema
- This is the highest-risk phase — needs careful mapping of the per-module table families to normalised rows
- Run in parallel, keeping the old system live until verified

### Phase 10 — Testing & Deployment (3–5 days)
- Integration tests for the assessment workflow (marking, locking, PDF generation, email)
- Security review: CSP headers, rate limiting on auth, audit logging for mark changes
- Deployment: gunicorn + nginx, or a platform like Railway/Render if self-hosted isn't required
- Side-by-side comparison of marks/outputs from old and new system

---

## Security Improvements the Rewrite Enables

| Current | New |
|---|---|
| No SQL injection risk but raw string queries in places | Django ORM with parameterised queries throughout |
| CSRF token manually implemented | Django's CSRF middleware — automatic |
| Passwords not in scope (server auth) | Proper Django session auth if moving off server auth |
| No audit log for mark changes | Add `django-simple-history` — every mark change is logged |
| No rate limiting | Django middleware or nginx rate limiting on auth endpoints |
| `error_reporting(E_ALL)` leaking stack traces | Django DEBUG=False in production, Sentry for error capture |
| Dynamic table names prevent prepared statement use in some places | Fully normalised schema, no dynamic table names |

---

## Tech Stack Summary

| Layer | Choice | Reason |
|---|---|---|
| Framework | Django 5.x | MVT = MVC, built-in admin, auth, ORM |
| Database | PostgreSQL | Better than MySQL for Django, proper JSONB if needed |
| PDF | WeasyPrint | HTML→PDF, templates reuse existing Bootstrap markup |
| Excel | openpyxl | Mature, actively maintained |
| Email | Django EmailMessage | Built-in, configurable backends |
| Frontend | Bootstrap 5 + htmx | htmx replaces jQuery AJAX patterns with less JS |
| RBAC | Custom Role model + django-guardian | Per-module permissions |
| Audit log | django-simple-history | Mark change history |
| Testing | pytest-django | Standard |
| Deployment | gunicorn + nginx | Standard Django production setup |

---

## Estimated Total Effort

Roughly **6–10 weeks** of focused development for one developer, or 3–5 weeks with two. The assessment workflow (Phases 4–5) and data migration (Phase 9) are the hardest parts — everything else is relatively mechanical.

---

## What Claude Can Help With

Most of it:

- **Models**: Write the full Django model file from the current schema analysis — ready to run
- **Views**: Class-based views for every current PHP page, including the DataTables JSON endpoints
- **Templates**: Convert the existing BS5 HTML into Django templates (the markup is already clean after the migration work)
- **Forms**: Django ModelForms for assessment entry, student editing, module setup
- **Permissions**: The permission decorator/mixin pattern and module-role model
- **WeasyPrint PDFs**: HTML templates that render identically to the current mPDF output
- **Data migration script**: The trickiest part — a management command that reads the old MySQL dynamic tables and populates the new schema
- **Admin customisation**: `ModelAdmin` classes, custom actions, inline models
- **Tests**: pytest fixtures and test cases for the marking workflow

### Where human input is needed more than Claude's:
- Decisions about **which features to keep vs drop** (some of `admin2/` functionality may be unused)
- **Deployment environment** specifics (where this runs, what auth mechanism replaces `REMOTE_USER`)
- **Acceptance testing** — verifying that calculated marks match the old system for real data

---

## Suggested Starting Point

Begin with Phase 2 — the models. Everything else (views, forms, migrations, admin, data migration) flows from getting the data model right. Once the models are agreed, the rest of the work becomes largely mechanical.
