<?php

namespace App\Mail\Support;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Renders a mail Blade view to HTML with the compiled Tailwind CSS
 * (resources/views/mail/compiled-tailwind.css) inlined onto each element as
 * a style="" attribute. Most email clients ignore <style> blocks or don't
 * support external stylesheets at all, so inlining is required regardless
 * of using Tailwind - see resources/views/mail/README.md for how the CSS
 * is compiled.
 */
trait RendersInlinedMail
{
    protected function renderInlined(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();

        $css = file_get_contents(resource_path('views/mail/compiled-tailwind.css'));

        $inlined = (new CssToInlineStyles)->convert($html, $css);

        // CssToInlineStyles round-trips the HTML through DOMDocument, which
        // lowercases every attribute name - fine for HTML, but SVG attributes
        // are case-sensitive (viewBox != viewbox) and browsers won't correct
        // it back the way an HTML5 parser would for inline SVG. Restore the
        // ones the mail layout actually uses.
        return str_replace(
            ['viewbox=', 'preserveaspectratio='],
            ['viewBox=', 'preserveAspectRatio='],
            $inlined
        );
    }
}
