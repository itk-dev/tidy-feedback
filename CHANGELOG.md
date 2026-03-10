# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- [PR-28](https://github.com/itk-dev/tidy-feedback/pull/28)
  27: Cleaned up code
- Replaced Bootstrap with CoreUI and modernized SCSS imports (`@import` → `@use`)
- Added Drupal CI workflow and test scripts
- Pinned dev environment to PHP 8.4 / Symfony 7
- Added Docker image pull step to `assets:build` task
- Fixed healthcheck race condition causing `app:start` to fail
- Fixed stale route cache causing 404 on `/tidy-feedback/test` after `app:start`

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
