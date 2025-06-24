# Tidy feedback

## Installation

``` shell
composer require itk-dev/tidy_feedback
```

### Drupal

``` shell
drush pm:install tidy_feedback
drush tidy-feedback:doctrine:schema-update
```

### Symfony

Create `config/routes/tidy_feedback.yaml`:

``` yaml
#config/routes/tidy_feedback.yaml
tidy_feedback:
  resource: "@TidyFeedbackBundle/config/routes.php"
  prefix: /tidy-feedback
```

> [!NOTE]
> You can use any `prefix`, but for consistency with the Drupal version of Tidy feedback you should use `/tidy-feedback`.

If [Symfony Flex](https://symfony.com/doc/current/setup/flex.html) hasn't already done so, you must enable the
TidyFeedbackBundle bundle:

``` php
// config/bundles.php
return [
    …,
    ItkDev\TidyFeedbackBundle\TidyFeedbackBundle::class => ['all' => true],
];
```

## Configuration

We need a [Doctrine database
URL](https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url)
in the environment variable `TIDY_FEEDBACK_DATABASE_URL`, e.g.

``` dotenv
# .env
# See https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url for details.
TIDY_FEEDBACK_DATABASE_URL="pdo-sqlite:////app/tidy-feedback.sqlite"
```

As an alternative for Drupal you can set `TIDY_FEEDBACK_DATABASE_URL` in `settings.local.php`:

``` php
# web/sites/default/settings.local.php
putenv('TIDY_FEEDBACK_DATABASE_URL=pdo-sqlite:////app/tidy-feedback.sqlite');
```

After installation and configuration, open `/tidy-feedback` on your site and enjoy!

## Development

* composer psr-4 stuff
* composer `"type"` stuff

### Twig

We use a watered-down instance of Twig with only `trans` and `path`.
