# Svelte

We use [Svelte Standalone](https://standalone.brenoliradev.com) to build a widget we can inject in any webpage.

The initial stuff in [the `widget` folder](../widget) has been created by performing the steps “[Installation
Guide](https://standalone.brenoliradev.com/install.html)“ steps (<https://standalone.brenoliradev.com/install.html>).

> [!WARNING]
> The “Clean Up“ step has not yet been completed.

All code related to the widget sits in
[`widget/src/_standalone/tidy_feedback`](../widget/src/_standalone/tidy_feedback).

Run `task` (in the root of the project) to see tasks related to the widget.

TLDR: Run

``` shell
task widget:dev
```

to get started.

## Development

A simple API endpoint for reponding to `POST` requests from the feedback form is defined in
[tidy_feedback/+server.js](../widget/src/routes/tidy_feedback/+server.js). _Nothing is saved when sending data to the
endpoint – it is purely used to test getting a response from the API_. The default response has the status code [`201
Created`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/201), but you can request another status
code by adding “respond with «status code»“ to the subject, e.g. “respond with 400“ to get [`400 Bad
Request`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/400).

Examples:

``` console
$ curl http://localhost:3000/tidy_feedback --include --header 'content-type: application/json' \
    --data '{"subject": "My feedback"}'
HTTP/1.1 201 Created
Vary: Origin
content-length: 38
content-type: application/json
Date: Fri, 18 Jul 2025 09:28:01 GMT
Connection: keep-alive
Keep-Alive: timeout=5

{"subject":"My feedback","status":201}

$ curl http://localhost:3000/tidy_feedback --include --header 'content-type: application/json' \
    --data '{"subject": "respond with 400 My feedback"}'
HTTP/1.1 400 Bad Request
Vary: Origin
content-length: 55
content-type: application/json
Date: Fri, 18 Jul 2025 09:28:22 GMT
Connection: keep-alive
Keep-Alive: timeout=5

{"subject":"respond with 400 My feedback","status":400}%
```

## Configuration

The widget reads configuration from the first element matching the CSS selector `[data-tidy-feedback-config]`. The
configuration must be a JSON object, e.g.

``` html
<template data-tidy-feedback-config='{"messageHideDelay": 0}'></template>
```

### All widget configuration options

| Name               | Default value | Description                                                                                                |
|--------------------|---------------|------------------------------------------------------------------------------------------------------------|
| `messageHideDelay` | `0`           | If greater than 0, automatically hide message after this number of milliseconds.}                          |
| `messages`         | `{}`          | Translated messages keyed by original (untranslated) message, e.g. `{"Send feedback": "Indsend feedback"}` |

## Building for production

When done with development, run

``` shell
task assets:build
```

to build the widget for production and copy the resulting Javascript file
([tidy_feedback.min.js](../widget/static/dist/standalone/tidy_feedback.min.js)) to the [`build`
folder](../build/standalone) (which is under git control).
