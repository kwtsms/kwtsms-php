# Changelog

All notable changes to `kwtsms/kwtsms-php` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.8.1] 2026-11-06

### Changed

- Smaller Packagist download: library files only.

---

## [1.8.0] 2026-11-05

### Added
- Client identifies itself with its name and version; optional `$app_name` constructor argument.

### Changed
- Package author and license holder are now kwtSMS.
- Bug fixes.

---

## [1.7.0] 2026-03-18

### Changed
- Packagist package renamed from `kwtsms/kwtsms` to `kwtsms/kwtsms-php`. Update your `composer.json`; the PHP namespace (`KwtSMS\`) is unchanged.

---

## [1.6.0] 2026-03-17

### Added
- Early credential check in `send()`.

### Changed
- Bug fixes.

---

## [1.5.0] 2026-03-13

### Changed
- Bug fixes.

---

## [1.4.0] 2026-03-13

### Removed
- `bin/kwtsms` CLI tool, now [kwtsms-cli](https://github.com/boxlinknet/kwtsms-cli). Remove the `"bin"` entry from `composer.json`.

---

## [1.3.0] 2026-03-13

### Added
- Country-specific phone validation (60+ countries).
- Trunk prefix handling.

---

## [1.2.0] 2026-03-05

### Added
- `status()` and `dlr()` methods.
- CI, security policy and README badges.

---

## [1.1.0] 2026-03-05

### Added
- Production OTP example and per-example guides.

### Changed
- Wider emoji cleaning.
- Messages with no usable text are caught before any API call.
- Maintenance.

---

## [1.0.0] 2026-03-05

### Added
- Initial release.
- Full API coverage: send, verify, balance, validate, sender IDs, coverage.
- Credentials from environment or `.env` file.
- Phone normalization, deduplication and message cleaning.
- Auto-batching for bulk sends and retry when the queue is full.
- All kwtSMS error codes mapped to action messages.
- Request logging with password masking.
- PHP 7.4+, no external dependencies.
- Test suite, examples and MIT License.

---

## Version History

| Version | Date | PHP | Notable Change |
|---------|------|-----|----------------|
| 1.7.0 | 2026-03-18 | 7.4+ | Packagist rename: kwtsms/kwtsms to kwtsms/kwtsms-php |
| 1.6.0 | 2026-03-17 | 7.4+ | Early credential check in send() |
| 1.5.0 | 2026-03-13 | 7.4+ | Bug fixes. |
| 1.4.0 | 2026-03-13 | 7.4+ | CLI tool moved to kwtsms-cli |
| 1.3.0 | 2026-03-13 | 7.4+ | Country-specific phone validation (60+ countries), trunk-zero stripping |
| 1.2.0 | 2026-03-05 | 7.4+ | status(), dlr(), CI, badges, SECURITY.md |
| 1.1.0 | 2026-03-05 | 7.4+ | Production OTP example, extended emoji ranges |
| 1.0.0 | 2026-03-05 | 7.4+ | Initial release |

---

[Unreleased]: https://github.com/kwtsms/kwtsms-php/compare/v1.7.0...HEAD
[1.7.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/boxlinknet/kwtsms-php/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/boxlinknet/kwtsms-php/releases/tag/v1.0.0
