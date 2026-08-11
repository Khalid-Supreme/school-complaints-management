# File Upload Hardening

Hardening of the complaint-attachment upload path (`POST /api/complaints/{complaint}/attachments`).
Every rule below is enforced server-side; the browser's `accept` attribute is
informational only and is not a security control.

## Policy location

All upload security values live in **`config/uploads.php`** and are consumed by a
single centralized rule (`App\Support\ValidationRules::attachment()`), so the
Form Request and any future upload features share one policy.

## Validation

The `attachment` field is validated with:

| Check | Rule | Notes |
| --- | --- | --- |
| Required file | `required`, `file` | Rejects non-file and empty uploads. |
| Max size | `max:{max_size_kb}` | 10 MB by default (`UPLOADS_MAX_SIZE_KB`). |
| Extension allowlist | `extensions:{allowed_extensions}` | jpg jpeg png gif webp pdf doc docx txt. |
| MIME allowlist | `mimetypes:{allowed_mimes}` | Matched against the **server-detected** MIME (`getMimeType()`, finfo on the temp file), never the client `Content-Type`. A file renamed from `shell.php` to `evil.php.jpg` is rejected because its detected MIME (`text/x-php`) is not allowed. |
| Image safety | `App\Rules\SafeImage` | Only evaluated when the detected MIME starts with `image/`. Runs `getimagesize()` on the file bytes; rejects corrupt/non-decodable images and images whose width, height or total pixel count exceed `config/uploads.php` caps (anti decompression-bomb). |

Order matters: the size, extension and MIME checks run before the image rule.

## Rejections

- **Executables / scripts** — excluded by both the extension and MIME allowlists;
  also caught by the MIME detector even when disguised with an image extension.
- **Archives** (`zip`, `tar`, `gz`, `7z`, `rar`, …) — not in the allowlist, so
  rejected unless explicitly whitelisted in `config/uploads.php`.
- **Oversized files** — rejected by `max:`.
- **Corrupt / oversized images** — rejected by `SafeImage`.

## Storage & naming

- Files are persisted with Laravel Storage on the configured disk
  (`uploads.attachments.disk`, default `local` → `storage/app/private`).
- The **original client filename is never used for the storage path**. Each file
  is stored via `storeAs()` under a cryptographically random name
  (`Str::random(40)` = 80 hex chars) with its extension derived from the
  **detected** MIME type (`MIME_EXTENSIONS` map), never from the client name.
- The client filename is retained only in `original_filename`, sanitized
  (control bytes stripped, max 255 chars) and used solely for display and the
  download response's `Content-Disposition`.

## Direct execution is not possible

- Files live **outside the public webroot** (`storage/app/private`).
  The `local` disk's `serve`/URL features are not used for these files and there
  is no symlink from `public/storage` to this disk.
- Files are served only through the authenticated `GET /complaints/{complaint}/attachments/{attachment}/download`
  controller, which sends an `attachment`-style `response()->download()`.
- The stored filename is random and extension-whitelisted; even a bypass attempt
  would not place scripts under the webroot.

## Download behavior

`AttachmentController::download` resolves the path on the configured disk,
verifies the attachment belongs to the complaint, authorizes the viewer, and
streams the file with the *sanitized* original name as the download filename.

## Tests

`tests/Feature/FileUploadTest.php` (8 cases) covers the matrix:

- valid PDF accepted and stored under a random name (path never contains the original name)
- valid PNG accepted
- archive (`.zip`) rejected
- oversize upload rejected
- renamed executable (`evil.php.jpg`) rejected via detected MIME
- corrupt image rejected
- image whose dimensions exceed the configured caps rejected
- unknown/binary MIME rejected

Run: `php artisan test --filter=FileUploadTest`

## Server-side configuration notes

- `post_max_size` and `upload_max_filesize` in `php.ini` must be >= the 10 MB
  app cap, otherwise uploads fail before app validation.
- If the disk is ever changed to S3 in production, urls must stay private and
  downloads must continue to route through the controller.