# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Added cache pruning for stale translation cache entries with 1-week TTL
- Added rounded corners and border when widget is dragged away from window edge, removed when pushed against edge
- Removed unused `subject`, `status`, and `category` fields from Item entity, admin templates, and translations
- Fixed highlighted region losing its dashed border after submitting feedback
- Fixed cancel button hover making text invisible by using a darker hover background and aligned transition timings
- Added "View" action column to admin feedback list replacing the subject link
- Added animated conic-gradient border on the "Click an element to select" message during select mode
- Fixed start button remaining visible when form or select-mode message is open
- Split start button into two-part pill: count badge (opens items list) and "+" button (starts feedback form)
- Replaced SCSS and CoreUI/Bootstrap with plain CSS to reduce build complexity and bundle size
- Renamed admin entry point from `app` to `admin`
- Fixed overly-broad button styling that affected all buttons on the page
- Added click-to-select element targeting for feedback region
- Simplified feedback form to email + description only; subject is now auto-generated from page title
- Added localStorage caching for email field so it's prefilled on repeat visits
- Cleaned up test page: removed debug styles, test button, stale commented-out code, and fixed typo
- Added optional caching for translations and Twig compilation via `TIDY_FEEDBACK_CACHE_DIR`
- Added collapsible list of existing feedback items in widget form
- Added feedback count badge on start button when feedback exists for the current page
- Added `/check` endpoint returning feedback count for a given URL
- Documented widget setup, query string parameters, disabling, viewing feedback, and access control
- Fixed path traversal vulnerability in asset serving
- Fixed Drupal asset route not matching nested paths (e.g. `icons/favicon.png`)
- Fixed basic auth users never being parsed from `TIDY_FEEDBACK_USERS` env var
- Added inline style fallback on message element for when CSS hasn't loaded yet
- [PR-28](https://github.com/itk-dev/tidy-feedback/pull/28)
  27: Cleaned up code
- Replaced Bootstrap with CoreUI and modernized SCSS imports (`@import` → `@use`)
- Pinned dev environment to PHP 8.4 / Symfony 7
- Added Docker image pull step to `assets:build` task
- Fixed healthcheck race condition causing `app:start` to fail
- Fixed stale route cache causing 404 on `/tidy-feedback/test` after `app:start`
- Excluded Drupal 11 + PHP 8.3 from CI matrix (lazy loading proxies require PHP 8.4)

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
