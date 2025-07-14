# Svelte

``` shell
npx sv create my-app
# Press Enter four times
cd my-app
git init && git add -A && git commit -m "Initial commit"
# https://standalone.brenoliradev.com/install.html
npm install -D svelte-standalone@latest
```

``` shell
npm --prefix my-app run dev -- --port 3000
open http://localhost:3000/tidy_feedback
```

``` shell
npx standalone build --all --production
```
