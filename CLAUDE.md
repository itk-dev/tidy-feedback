# Tidy Feedback - Project Guide

## Project description

Tidy feedback is a **dual-framework library** that works as both a
[Drupal module](https://www.drupal.org/docs/user_guide/en/understanding-modules.html) and a
[Symfony bundle](https://symfony.com/doc/current/bundles.html). It provides a user feedback widget that is automatically
injected into every HTML page via a kernel response listener. Users can highlight a page element, fill in a form, and
submit feedback with an auto-captured screenshot.

## Architecture

### Shared trait pattern

The core controller logic lives in `src/Controller/TidyFeedbackControllerTrait.php` — a PHP trait that is consumed by
both the Drupal controller (`drupal/src/Controller/TidyFeedbackController.php`) and the Symfony controller
(`symfony/src/Controller/TidyFeedbackController.php`). This avoids duplicating business logic across frameworks.

### Separate entity manager

Tidy feedback uses its **own Doctrine entity manager**, configured via `TIDY_FEEDBACK_DATABASE_URL`. This keeps feedback
data isolated from the host application's database and avoids conflicts with Drupal's or Symfony's default entity
manager.

### Shadow DOM

The widget renders inside a
[Shadow DOM](https://developer.mozilla.org/en-US/docs/Web/API/Web_components/Using_shadow_DOM) to isolate its styles
from the host page.

## Key directories and files

| Path                       | Description                                      |
|----------------------------|--------------------------------------------------|
| `src/`                     | Shared PHP code (controller trait, entity, helper) |
| `symfony/src/`             | Symfony bundle (controller, bundle class)         |
| `drupal/`                  | Drupal module (controller, routing, info.yml)     |
| `assets/`                  | JavaScript and CSS source files                   |
| `assets/component/`        | Reusable JS components (draggable, region)        |
| `build/`                   | Compiled assets (committed to repo)               |
| `templates/`               | Twig templates (widget, admin list, detail)       |
| `translations/`            | Translation YAML files                            |
| `resources/config/routes/` | Route config templates for installation           |
| `webpack.config.js`        | Webpack Encore configuration                      |
| `Taskfile.yml`             | Task runner commands                              |
| `compose.yaml`             | Docker Compose services                           |

## Build commands

```shell
# Build assets for production (via Docker)
task assets:build

# Or run Encore directly (requires local Node.js)
npx encore dev
npx encore production
```

The compiled `build/` directory is committed to the repository so that consumers do not need a Node.js build step.

## Development environment

```shell
# Start the Symfony dev app with Tidy feedback installed
task app:start

# Open the test page in a browser
task app:open

# Stop the app
task app:stop
```

The test page is available at `/tidy-feedback/test` on the running app.

## Coding standards

```shell
# Apply all coding standards (PHP, JS, Markdown, YAML, Twig, Composer)
task coding-standards:apply

# Check all coding standards
task coding-standards:check
```

Individual standard categories can be run separately, e.g. `task coding-standards:php:apply`.

## Testing

There is currently no automated test suite. Testing is manual via the `/tidy-feedback/test` page in the dev app.

## Translation workflow

```shell
# Extract translation strings and format YAML
task translations:extract
```

Translation files live in `translations/` and use `{domain}.{locale}.yaml` naming.

## Conventions

- **Shadow DOM**: The widget is isolated inside a Shadow DOM — all widget CSS must be scoped accordingly.
- **Compiled `build/` committed**: Always rebuild assets (`task assets:build`) and commit the `build/` directory when
  changing anything in `assets/`.
- **Environment variable prefix**: All configuration uses the `TIDY_FEEDBACK_` prefix.
- **Separate entity manager**: The module manages its own Doctrine connection — do not use the host app's entity
  manager.
- **Dual-framework support**: Changes to controller logic go in the shared trait (`TidyFeedbackControllerTrait`), not
  in the framework-specific controllers.
