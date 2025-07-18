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

## Building for production

When done with development, run

``` shell
task assets:build
```

to build the widget for production and copy the resulting Javascript file
([tidy_feedback.min.js](../widget/static/dist/standalone/tidy_feedback.min.js)) to the [`build`
folder](../build/standalone) (which is under git control).
