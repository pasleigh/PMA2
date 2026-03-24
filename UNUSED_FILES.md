# Unused PHP Files — Review List

Generated: 2026-03-24

These files have no references (include, require, href, form action) anywhere in the codebase.
They are not reachable from any entry point. Review before deleting.

---

## `/pma/staff/admin2/` — 10 files

| File | Notes |
|---|---|
| `change_school_definition_new.php` | Likely superseded by the non-`_new` version |
| `dump2.php` | Dev/diagnostic tool — check if needed for admin maintenance |
| `edit_project_proposals_quick_old.php` | Explicitly marked `_old` — probably safe to delete |
| `edit_project_visibility.php` | Orphaned admin function — check if feature was intentionally removed |
| `fetch_staff_project_list_for_module.php` | Possibly replaced by a different AJAX endpoint |
| `make_staff_names_tables_equivalent.php` | One-off DB maintenance utility |
| `modify_project_entry.php` | Functionality may have been merged elsewhere |
| `push_header_images_to_db.php` | One-off setup/migration tool |
| `upload_assessment_criteria_csv.php` | Likely superseded by the `.xlsx` version |
| `upload_test.php` | Test file — probably safe to delete |

## `/pma/staff/` — 3 files

| File | Notes |
|---|---|
| `config_kmap.php` | School-specific config that is never included anywhere |
| `pdo_table_check.php` | Diagnostic/testing utility |
| `projectmarking_email_feedback.php` | Orphaned email handler — check git history |

## `/pma/staff/proposals_bs/` — 1 file

| File | Notes |
|---|---|
| `test_project_edit.php` | Explicitly named test file — probably safe to delete |

## `/pma/student/` — 1 file

| File | Notes |
|---|---|
| `viewallocationPG.php` | Legacy `PG`-suffixed variant, likely replaced by the non-PG version |

---

## Quick decisions

**Probably safe to delete (named test/old/debug):**
- `upload_test.php`
- `test_project_edit.php`
- `dump2.php`
- `edit_project_proposals_quick_old.php`

**Check git history first (may be intentionally hidden features):**
- `projectmarking_email_feedback.php`
- `modify_project_entry.php`
- `edit_project_visibility.php`
- `fetch_staff_project_list_for_module.php`

**One-off utilities (safe to archive/delete once confirmed no longer needed):**
- `make_staff_names_tables_equivalent.php`
- `push_header_images_to_db.php`
- `upload_assessment_criteria_csv.php`
- `pdo_table_check.php`

**Low priority / verify school usage:**
- `config_kmap.php`
- `viewallocationPG.php`

---

## Caveat

Analysis searched for exact basename matches across all `.php` files.
Files referenced via fully dynamic paths (e.g. `include($dir . '/foo.php')` where the
filename is built at runtime) would be missed. None of the 15 basenames above were
found in dynamic-include patterns, so the list is considered reliable.
