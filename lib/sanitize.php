<?php
// Basic HTML sanitizer without external dependencies.
// Keeps a conservative whitelist of tags and attributes.
function sanitize_html($html) {
    if (trim($html) === '') return '';

    // Use DOMDocument to parse HTML fragment
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // Ensure proper encoding
    $html = '<?xml encoding="utf-8" ?><div>' . $html . '</div>';
    $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $body = $doc->getElementsByTagName('div')->item(0);

    $allowedTags = [
        'a'=>['href','title','rel','target'], 'p'=>[], 'br'=>[], 'b'=>[], 'strong'=>[], 'i'=>[], 'em'=>[],
        'ul'=>[], 'ol'=>[], 'li'=>[], 'h1'=>[], 'h2'=>[], 'h3'=>[], 'h4'=>[], 'h5'=>[], 'h6'=>[],
        'img'=>['src','alt','title','width','height'], 'blockquote'=>[], 'pre'=>[], 'code'=>[],
    ];

    // Collect all element nodes in a static array to avoid live NodeList issues
    $elements = [];
    foreach ($doc->getElementsByTagName('*') as $el) {
        $elements[] = $el;
    }

    // Iterate in reverse so removals don't affect upcoming nodes
    for ($i = count($elements) - 1; $i >= 0; $i--) {
        $node = $elements[$i];
        $tag = $node->nodeName;
        if (!array_key_exists($tag, $allowedTags)) {
            // unwrap node
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
            continue;
        }

        // Sanitize attributes
        $allowedAttrs = $allowedTags[$tag];
        if ($node->hasAttributes()) {
            $toRemove = [];
            foreach (iterator_to_array($node->attributes) as $attr) {
                $name = $attr->name;
                $value = $attr->value;
                if (!in_array($name, $allowedAttrs)) {
                    $toRemove[] = $name;
                    continue;
                }
                if ($name === 'href' || $name === 'src') {
                    $v = trim($value);
                    if (preg_match('#^\s*(javascript:|data:)#i', $v)) {
                        $toRemove[] = $name;
                        continue;
                    }
                }
            }
            foreach ($toRemove as $n) $node->removeAttribute($n);
        }
    }

    // Build innerHTML
    $out = '';
    foreach ($body->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }

    libxml_clear_errors();
    return $out;
}
