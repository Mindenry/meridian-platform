<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichTextSanitizer
{
    protected ?HtmlSanitizer $sanitizer = null;

    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return $this->sanitizer()->sanitize($html);
    }

    public function allowedTags(): array
    {
        return [
            'p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li',
            'blockquote', 'a', 'figure', 'figcaption', 'img', 'table', 'thead', 'tbody',
            'tr', 'th', 'td', 'hr', 'code', 'pre', 'span',
        ];
    }

    public function plainText(?string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
    }

    public function wordCount(?string $html): int
    {
        $text = $this->plainText($html);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    public function readingTime(?string $html): int
    {
        return max(1, (int) ceil($this->wordCount($html) / 220));
    }

    /**
     * Convert a Tiptap JSON document into safe HTML. Unknown nodes are dropped.
     */
    public function renderTiptapDocument(?array $document): string
    {
        if (! $document || empty($document['content'])) {
            return '';
        }

        return $this->sanitize($this->renderNodes($document['content']));
    }

    protected function renderNodes(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $type = $node['type'] ?? null;
            $content = $node['content'] ?? [];
            $attrs = $node['attrs'] ?? [];
            $text = e($node['text'] ?? '');

            $html .= match ($type) {
                'doc' => $this->renderNodes($content),
                'paragraph' => '<p>'.$this->renderNodes($content).'</p>',
                'heading' => '<h'.($this->headingLevel($attrs['level'] ?? 2)).'>'.$this->renderNodes($content).'</h'.($this->headingLevel($attrs['level'] ?? 2)).'>',
                'bulletList' => '<ul>'.$this->renderNodes($content).'</ul>',
                'orderedList' => '<ol>'.$this->renderNodes($content).'</ol>',
                'listItem' => '<li>'.$this->renderNodes($content).'</li>',
                'blockquote' => '<blockquote>'.$this->renderNodes($content).'</blockquote>',
                'codeBlock' => '<pre><code>'.e($this->plainText($this->renderNodes($content))).'</code></pre>',
                'horizontalRule' => '<hr>',
                'hardBreak' => '<br>',
                'text' => $text,
                'link' => '<a href="'.e($this->safeUrl($attrs['href'] ?? '#')).'" rel="noopener noreferrer">'.$this->renderNodes($content).'</a>',
                'image' => '<img src="'.e($this->safeUrl($attrs['src'] ?? '')).'" alt="'.e($attrs['alt'] ?? '').'">',
                'table' => '<table>'.$this->renderNodes($content).'</table>',
                'tableRow' => '<tr>'.$this->renderNodes($content).'</tr>',
                'tableHeader' => '<th>'.$this->renderNodes($content).'</th>',
                'tableCell' => '<td>'.$this->renderNodes($content).'</td>',
                default => '',
            };
        }

        return $html;
    }

    protected function headingLevel(mixed $level): int
    {
        return in_array((int) $level, [2, 3, 4], true) ? (int) $level : 2;
    }

    public function safeUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || Str::startsWith($url, ['#', '/'])) {
            return $url ?: '#';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true) ? $url : '#';
    }

    protected function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', allowedElements: ['span', 'p', 'figure'])
            ->allowAttribute('target', allowedElements: ['a'])
            ->forceHttpsUrls(false)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowElement('iframe', ['src', 'title', 'loading', 'allowfullscreen'])
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th')
            ->allowElement('td')
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'loading']);

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
