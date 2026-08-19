# Mail templates

These Blade views use Tailwind utility classes (matching the palette in
HavenWebsite-Frontend's `src/style.css`), but email clients need static,
inlined CSS - no `<style>` blocks, no `var()`. So there are two steps
between editing a view and it actually going out:

1. `compiled-tailwind.css` in this directory is a **committed build
   artifact**, compiled ahead of time with the standalone Tailwind v3 CLI
   (not v4 - v4's default output leans on CSS custom properties for
   theming, which most email clients, Outlook especially, don't resolve).
   There's no Node/npm in this repo and none is added to deploy.yml -
   nothing compiles this at request or deploy time.
2. `App\Mail\Support\RendersInlinedMail` renders the Blade view to HTML,
   then inlines `compiled-tailwind.css` onto every element as a `style=""`
   attribute (via `tijsverkoyen/css-to-inline-styles`) before the Mailable
   sends it.

## Recompiling after changing a view or `tailwind.mail.config.js`

You'll need the Tailwind v3 standalone CLI (not installed in this repo):

```sh
curl -sL https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-linux-x64 -o /tmp/tailwindcss3
chmod +x /tmp/tailwindcss3

/tmp/tailwindcss3 \
  --config tailwind.mail.config.js \
  --input resources/css/mail.css \
  --output resources/views/mail/compiled-tailwind.css \
  --minify
```

Commit the regenerated `compiled-tailwind.css` along with your view change.
