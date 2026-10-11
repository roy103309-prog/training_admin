# Website Texts & Facilities: what was added and how to remove it

Added on 2026-10-10. With these two admin pages, the fixed texts and the Facilities cards of the public website (`/site`) can be edited from
**Settings → General Settings → Website Texts / Facilities**. No new folder or database was created.

## What was added

| Type | Item |
|---|---|
| New API files (api/) | `web-texts-get.php`, `web-texts-save.php`, `web-facilities-list.php`, `web-facilities-save.php`, `web-facilities-delete.php` |
| New admin pages (templates/) | `web-texts.html`, `web-facilities.html` |
| Database (training_XXXXXX) | New table `web_facilities` (NEW LIFE block of `database/tenant_schema.sql`, `NL_SCHEMA_VERSION` 6 → 7). Edited texts are rows `text_bn:<key>` / `text_en:<key>` in the existing `web_settings` table. |
| Data | The 9 original facilities were copied into `web_facilities`. Their images stay in `assets/images/site-facility-*`. New facility images go to `uploads/training_XXXXXX/site/`. |

## Changed files

| File | Change |
|---|---|
| `api/web-common.php` | `WEB_TEXT_TABS` (list of editable texts), `webTextDefaults()`, `WEB_FACILITY_ICONS` |
| `api/config/connection.php` | `NL_SCHEMA_VERSION` 7 |
| `assets/js/route-map.js` | routes `/website/texts`, `/website/facilities` |
| `includes/new-sidebar.html` | menu items "Website Texts", "Facilities" |
| `includes/site-bootstrap.php` | `lang()` uses edited texts from `web_settings` |
| `includes/site-lang.php` | new keys: `brand.full`, `nav.certificate/oldCertificate/newCertificate`, `teamSection.*`, `cert.*` (texts that were fixed in the page code) |
| `includes/site-section-facilities.php` | reads `web_facilities` instead of the fixed list |
| `includes/site-section-team.php` | headings use `teamSection.*` keys |
| `includes/site-footer.php` | Programs column = active programs not shown on home (else first 4), copyright uses `brand.full` |
| `includes/site-header.php` | certificate menu uses `nav.*` keys, share/meta tags use Logo & Branding values |
| `site-certificate.php`, `site-new-certificate.php`, `site-program.php` | headings and labels use `cert.*` / `brand.full` keys |

## How to remove it completely

1. Delete the 7 new files listed above.
2. Remove the two routes from `assets/js/route-map.js` and the two menu items from `includes/new-sidebar.html`.
3. Remove `WEB_TEXT_TABS`, `webTextDefaults()` and `WEB_FACILITY_ICONS` from `api/web-common.php`.
4. In `includes/site-bootstrap.php`, make `lang()` only `require` `site-lang.php` again (drop the `text_bn:` / `text_en:` loop).
5. Put a fixed facilities list back in `includes/site-section-facilities.php` (see git history of that file), or keep reading `web_facilities`.
6. Database (optional): `DROP TABLE web_facilities;` and `DELETE FROM web_settings WHERE setting_key LIKE 'text\_%';`
   Then remove the `web_facilities` block from `tenant_schema.sql`.

The new `site-lang.php` keys and the other page changes can stay. They show the same text as before.
Alternative: revert the git commit that added this feature.
