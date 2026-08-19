// Tailwind config used only to compile resources/views/mail/compiled-tailwind.css
// (see resources/css/mail.css) - kept separate from any future app-wide
// Tailwind config since email HTML needs static, inlineable CSS (no custom
// properties/var(), which Tailwind v4's default output relies on and most
// email clients don't resolve reliably), so this stays pinned to v3-style
// static output on purpose. Compiled with the standalone Tailwind v3 CLI,
// see resources/views/mail/README.md for the exact command.
module.exports = {
  content: ['./resources/views/mail/**/*.blade.php'],
  corePlugins: {
    preflight: false,
    // Tailwind's color utilities normally emit a --tw-*-opacity custom
    // property + var() fallback so /NN opacity modifiers work. Most email
    // clients don't resolve CSS custom properties (Outlook's Word engine
    // in particular), so these are disabled to get plain static color
    // declarations instead - the compiled CSS must be fully inlineable
    // with no var() left for anything to fail to resolve.
    textOpacity: false,
    backgroundOpacity: false,
    borderOpacity: false,
  },
  theme: {
    extend: {
      // Mirrors the frontend's design tokens (HavenWebsite-Frontend/src/style.css
      // @theme block) so mail matches the app's look. Kept as a literal copy
      // rather than a shared source, since the two repos don't share tooling.
      colors: {
        'haven-white': '#f3e7d5',
        'haven-pink': '#ffada0',
        'haven-yellow': '#ffbf38',
        'haven-light-blue': '#95d3de',
        'haven-black': '#000000',
        'haven-red': '#f05737',
        'haven-green': '#305248',
        'haven-blue': '#091d4b',
      },
    },
  },
};
