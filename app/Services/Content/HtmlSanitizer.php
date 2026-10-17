<?php

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Single source of truth for the HTMLPurifier policy applied to rich-text content.
 * Used when content is saved (so stored HTML is already safe) and when it is rendered.
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function purify(string $html): string
    {
        return self::purifier()->purify($html);
    }

    /**
     * Sanitize a body for storage. Plain text (no tags) is kept verbatim so that
     * structured bodies (e.g. "key: value" lines, JSON) and paragraph formatting
     * done at render time keep working.
     */
    public static function forStorage(?string $body): ?string
    {
        if ($body === null || $body === '' || strip_tags($body) === $body) {
            return $body;
        }

        return self::purify($body);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'walhi-html5-content');
        $config->set('HTML.DefinitionRev', 3);
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
        ]);
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,ul,ol,li,h2,h3,h4,h5,blockquote,a[href|title|target],img[src|alt|width|height|style|class],table,thead,tbody,tr,th,td,figure,figcaption,hr,span,div');

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('figure', 'Block', 'Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
