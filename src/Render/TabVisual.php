<?php
namespace Syndicate\Render;

use Approach\Render\Attribute;
use Approach\Render\HTML;
use Approach\Render\Node;
use Approach\Render\Stream;
use Stringable;

require_once __DIR__ . '/../../support/lib/vendor/autoload.php';

class TabVisual extends HTML
{
    public function __construct(
        public null|string|Stringable $tag = 'div',
        public null|string|Stringable $id = null,
        null|string|array|Node|Attribute $classes = null,
        public null|array|Attribute $attributes = new Attribute,
        public null|string|Stringable|Stream|self $content = null,
        public array $styles = [],
        public bool $prerender = false,
        public bool $selfContained = false,
        // Specific to Tab
        public null|string|Stringable|Stream $title = '',
        public null|string|Stringable|Stream $icon = '',
    ) {
        $classes[] = 'visual';

        parent::__construct(
            tag: $tag,
            id: $id,
            classes: $classes,
            attributes: $attributes,
            content: $content,
            styles: $styles,
            prerender: $prerender,
            selfContained: $selfContained,
        );

        if ($icon != '') {
            $this->nodes[] = new HTML(tag: 'i', classes: ['bi', ' bi-' . $icon]);
        }
        $this->nodes[] = new HTML(tag: 'p', content: $title);
    }
}