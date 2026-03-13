# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Auto-focus on description textarea after selecting an element
- 20px padding around highlight region so small elements aren't obscured by corner handles
- Keyboard shortcuts: Shift+C to start feedback, Ctrl/Cmd+Enter to submit, Escape to cancel
- Rounded corners and border when widget is dragged away from window edge, removed when pushed against edge
- Cache pruning for stale translation cache entries with 1-week TTL
- "View" action column to admin feedback list replacing the subject link
- Animated conic-gradient border on the "Click an element to select" message during select mode
- Split start button into two-part pill: count badge (opens items list) and "+" button (starts feedback form)
- Click-to-select element targeting for feedback region
- localStorage caching for email field so it's prefilled on repeat visits
- Optional caching for translations and Twig compilation via `TIDY_FEEDBACK_CACHE_DIR`
- Collapsible list of existing feedback items in widget form
- Feedback count badge on start button when feedback exists for the current page
- `/check` endpoint returning feedback count for a given URL
- Inline style fallback on message element for when CSS hasn't loaded yet
- Docker image pull step to `assets:build` task

### Changed

- Simplified feedback form to email + description only; subject is now auto-generated from page title
- Replaced SCSS and CoreUI/Bootstrap with plain CSS to reduce build complexity and bundle size
- Renamed admin entry point from `app` to `admin`
- Replaced Bootstrap with CoreUI and modernized SCSS imports (`@import` → `@use`)
- Pinned dev environment to PHP 8.4 / Symfony 7
- Excluded Drupal 11 + PHP 8.3 from CI matrix (lazy loading proxies require PHP 8.4)
- Documented widget setup, query string parameters, disabling, viewing feedback, and access control
- [PR-28](https://github.com/itk-dev/tidy-feedback/pull/28)
  27: Cleaned up code
- Cleaned up test page: removed debug styles, test button, stale commented-out code, and fixed typo

### Fixed

- Cached email not appearing after submitting first feedback item
- Highlighted region losing its dashed border after submitting feedback
- Cancel button hover making text invisible by using a darker hover background and aligned transition timings
- Start button remaining visible when form or select-mode message is open
- Overly-broad button styling that affected all buttons on the page
- Path traversal vulnerability in asset serving
- Drupal asset route not matching nested paths (e.g. `icons/favicon.png`)
- Basic auth users never being parsed from `TIDY_FEEDBACK_USERS` env var
- Healthcheck race condition causing `app:start` to fail
- Stale route cache causing 404 on `/tidy-feedback/test` after `app:start`

### Removed

- Unused `subject`, `status`, and `category` fields from Item entity, admin templates, and translations

## [1.0.0] - 2025-07-10

- [PR-16](https://github.com/itk-dev/tidy-feedback/pull/16)
  Fix duplicated subject
- [PR-13](https://github.com/itk-dev/tidy-feedback/pull/13)
  Styling feedback details page
- [PR-12](https://github.com/itk-dev/tidy-feedback/pull/12)
  Cleaned up and improved
- [PR-11](https://github.com/itk-dev/tidy-feedback/pull/11)
  10: Changed TIDY_FEEDBACK_DISABLE_PATTERN default value
- [PR-7](https://github.com/itk-dev/tidy-feedback/pull/7)
  Cleaned up. Improved image handling.
- [PR-3](https://github.com/itk-dev/tidy-feedback/pull/3)
  Added translations and standalone app
- [PR-5](https://github.com/itk-dev/tidy-feedback/pull/5)
  Add styling. Make region and form draggable

[Unreleased]: https://github.com/itk-dev/tidy_feedback/compare/1.0.0...HEAD
[1.0.0]: https://github.com/itk-dev/tidy_feedback/releases/tag/1.0.0
