<?php

namespace App\Cms\Blocks;

use App\Models\MediaAsset;
use App\Models\Site;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlockRegistry
{
    public const SCHEMA_VERSION = 1;

    public static function definitions(): array
    {
        $theme = self::select('theme', 'Surface', [
            ['value' => 'light', 'label' => 'Ivory'],
            ['value' => 'white', 'label' => 'White'],
            ['value' => 'muted', 'label' => 'Steel'],
            ['value' => 'dark', 'label' => 'Midnight'],
        ], 'light');

        $alignment = self::select('align', 'Alignment', [
            ['value' => 'left', 'label' => 'Left'],
            ['value' => 'center', 'label' => 'Centre'],
        ], 'left');

        $cta = static fn (string $key, string $label, string $url) => [
            'key' => $key,
            'type' => 'link',
            'label' => 'Call to action',
            'default' => ['label' => $label, 'url' => $url, 'style' => $key === 'secondary_cta' ? 'secondary' : 'primary'],
        ];

        return [
            'hero' => [
                'label' => 'Hero',
                'category' => 'Headers',
                'icon' => 'hero',
                'description' => 'Full-height editorial hero with imagery, headline, actions, and technical metadata.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Bangkok · Singapore · Ho Chi Minh City'),
                    self::textarea('headline', 'Headline', 'Engineering progress. Enabling possibility.', 4),
                    self::text('accent_line', 'Accent line', 'Industrial intelligence, delivered.'),
                    self::textarea('body', 'Supporting copy', 'We connect industrial expertise, intelligent systems, and measurable business outcomes.', 4),
                    $cta('primary_cta', 'Discuss a project', '/contact'),
                    $cta('secondary_cta', 'Explore our solutions', '/solutions'),
                    self::image('image', 'Hero image'),
                    self::select('layout', 'Composition', [
                        ['value' => 'panel', 'label' => 'Image panel'],
                        ['value' => 'full', 'label' => 'Full bleed'],
                        ['value' => 'split', 'label' => 'Split'],
                    ], 'panel'),
                    self::toggle('show_scroll', 'Show scroll cue', true),
                    self::repeater('meta', 'Metadata strip', [
                        self::text('label', 'Label'),
                        self::text('value', 'Value'),
                    ], [
                        ['label' => 'Established', 'value' => '2009'],
                        ['label' => 'Specialists', 'value' => '240+'],
                        ['label' => 'Sectors', 'value' => '05'],
                    ]),
                    $theme,
                    $alignment,
                ],
            ],

            'split_hero' => [
                'label' => 'Split hero',
                'category' => 'Headers',
                'icon' => 'split',
                'description' => 'Page header with a headline and a supporting image.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'About Meridian'),
                    self::textarea('headline', 'Headline', 'A Thai engineering partner built for an industrial decade.', 4),
                    self::textarea('body', 'Supporting copy', '', 6),
                    $cta('primary_cta', 'Meet the leadership', '/about#leadership'),
                    self::image('image', 'Image'),
                    self::toggle('reverse', 'Image on the left', false),
                    $theme,
                ],
            ],

            'statement' => [
                'label' => 'Positioning statement',
                'category' => 'Editorial',
                'icon' => 'text',
                'description' => 'Large asymmetric statement with supporting paragraphs.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Who we are'),
                    self::textarea('heading', 'Statement', 'Industrial systems are only valuable when the people running them can trust what happens next.', 5),
                    self::textarea('body', 'Body', '', 10),
                    self::textarea('aside', 'Aside', '', 6),
                    self::text('link_label', 'Link label', 'Our approach'),
                    self::text('link_url', 'Link URL', '/about'),
                    $theme,
                ],
            ],

            'capabilities' => [
                'label' => 'Capability index',
                'category' => 'Services',
                'icon' => 'list',
                'description' => 'Numbered capability rows with hover imagery.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Capabilities'),
                    self::textarea('heading', 'Heading', 'From process audit to operating system.', 3),
                    self::textarea('intro', 'Intro', '', 5),
                    self::repeater('items', 'Capabilities', [
                        self::text('number', 'Number'),
                        self::text('title', 'Title'),
                        self::textarea('description', 'Description', '', 3),
                        self::image('image', 'Hover image'),
                        self::text('url', 'URL'),
                    ], [
                        ['number' => '01', 'title' => 'Industrial automation', 'description' => 'Control architecture, robotics, and line integration engineered for uptime.', 'url' => '/solutions/industrial-automation'],
                        ['number' => '02', 'title' => 'Smart manufacturing', 'description' => 'Traceability, OEE, and quality intelligence built on the plant floor.', 'url' => '/solutions/smart-manufacturing'],
                        ['number' => '03', 'title' => 'Industrial software', 'description' => 'Operational platforms that turn machine data into decisions.', 'url' => '/solutions/industrial-software'],
                        ['number' => '04', 'title' => 'Infrastructure engineering', 'description' => 'Resilient electrical, utility, and facility systems for critical operations.', 'url' => '/solutions/infrastructure-engineering'],
                    ], 4, 8),
                    $theme,
                ],
            ],

            'services_grid' => [
                'label' => 'Solutions grid',
                'category' => 'Services',
                'icon' => 'grid',
                'description' => 'Automatically resolved solution cards from the CMS.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Solutions'),
                    self::textarea('heading', 'Heading', 'Engineering capability, organised around outcomes.', 3),
                    self::textarea('intro', 'Intro', '', 5),
                    self::number('limit', 'Number of solutions', 6, 1, 12),
                    self::select('layout', 'Layout', [
                        ['value' => 'editorial', 'label' => 'Editorial rows'],
                        ['value' => 'grid', 'label' => 'Grid'],
                    ], 'editorial'),
                    $theme,
                ],
            ],

            'featured_project' => [
                'label' => 'Featured project',
                'category' => 'Portfolio',
                'icon' => 'project',
                'description' => 'A single large case study with results.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Selected work'),
                    self::textarea('heading', 'Heading', 'A production line that learns before it stops.', 3),
                    self::text('project_slug', 'Project slug', 'rayong-precision-line'),
                    $theme,
                ],
            ],

            'project_grid' => [
                'label' => 'Project grid',
                'category' => 'Portfolio',
                'icon' => 'grid',
                'description' => 'Case studies with optional industry filtering.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Case studies'),
                    self::textarea('heading', 'Heading', 'Work measured in operating outcomes.', 3),
                    self::text('industry', 'Industry key (optional)', ''),
                    self::number('limit', 'Limit', 6, 1, 12),
                    self::toggle('show_filters', 'Show filters', true),
                    $theme,
                ],
            ],

            'industry_grid' => [
                'label' => 'Industry grid',
                'category' => 'Portfolio',
                'icon' => 'sectors',
                'description' => 'Industries served, resolved from the CMS.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Industries'),
                    self::textarea('heading', 'Heading', 'Sector knowledge, not sector guesswork.', 3),
                    self::textarea('intro', 'Intro', '', 4),
                    self::number('limit', 'Limit', 5, 1, 10),
                    $theme,
                ],
            ],

            'statistics' => [
                'label' => 'Statistics',
                'category' => 'Evidence',
                'icon' => 'chart',
                'description' => 'Illustrative operating metrics with mandatory disclaimer.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'At a glance'),
                    self::textarea('heading', 'Heading', 'Built to be measured.', 2),
                    self::repeater('items', 'Statistics', [
                        self::text('value', 'Value'),
                        self::text('label', 'Label'),
                        self::text('note', 'Note'),
                    ], [
                        ['value' => '240+', 'label' => 'Engineers and specialists', 'note' => 'Illustrative'],
                        ['value' => '06', 'label' => 'Countries served', 'note' => 'Illustrative'],
                        ['value' => '18', 'label' => 'Years of practice', 'note' => 'Illustrative'],
                        ['value' => '99.2%', 'label' => 'Critical-system availability', 'note' => 'Illustrative'],
                    ], 2, 6),
                    self::text('disclaimer', 'Disclaimer', 'Figures are illustrative demonstration data for this fictional brand.'),
                    $theme,
                ],
            ],

            'technology_ecosystem' => [
                'label' => 'Technology ecosystem',
                'category' => 'Evidence',
                'icon' => 'nodes',
                'description' => 'Platforms and standards, without implying vendor partnerships.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Technology'),
                    self::textarea('heading', 'Heading', 'Platforms our teams work with.', 2),
                    self::textarea('note', 'Note', 'Technology references describe working familiarity only. No vendor partnership or certification is implied.', 4),
                    self::repeater('items', 'Platforms', [
                        self::text('name', 'Name'),
                        self::text('category', 'Category'),
                    ], [
                        ['name' => 'Siemens', 'category' => 'Automation'],
                        ['name' => 'Rockwell', 'category' => 'Automation'],
                        ['name' => 'Ignition', 'category' => 'SCADA'],
                        ['name' => 'OPC UA', 'category' => 'Standard'],
                        ['name' => 'MQTT', 'category' => 'Standard'],
                        ['name' => 'PostgreSQL', 'category' => 'Data'],
                    ], 2, 16),
                    $theme,
                ],
            ],

            'latest_insights' => [
                'label' => 'Latest insights',
                'category' => 'Editorial',
                'icon' => 'article',
                'description' => 'Latest published articles, resolved from the CMS.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Insights'),
                    self::textarea('heading', 'Heading', 'Field notes from industrial practice.', 2),
                    self::text('category', 'Category slug (optional)', ''),
                    self::number('limit', 'Limit', 3, 1, 9),
                    $theme,
                ],
            ],

            'article_list' => [
                'label' => 'Article list',
                'category' => 'Editorial',
                'icon' => 'article',
                'description' => 'Configurable article index block.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Library'),
                    self::textarea('heading', 'Heading', 'All insights and announcements.', 2),
                    self::select('type', 'Article type', [
                        ['value' => '', 'label' => 'All types'],
                        ['value' => 'insight', 'label' => 'Insights'],
                        ['value' => 'news', 'label' => 'News'],
                        ['value' => 'technical', 'label' => 'Technical'],
                        ['value' => 'announcement', 'label' => 'Announcements'],
                        ['value' => 'report', 'label' => 'Reports'],
                    ], ''),
                    self::number('limit', 'Limit', 9, 1, 24),
                    $theme,
                ],
            ],

            'cta' => [
                'label' => 'Call to action',
                'category' => 'Conversion',
                'icon' => 'arrow',
                'description' => 'High-contrast conversion band.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Start a conversation'),
                    self::textarea('heading', 'Heading', 'Bring us the operation that has to work.', 3),
                    self::textarea('body', 'Body', 'Tell us what is constrained, what is changing, and what cannot fail. We will come back with a point of view.', 5),
                    $cta('primary_cta', 'Talk to our team', '/contact'),
                    $cta('secondary_cta', 'Download company profile', '/documents/meridian-company-profile.pdf'),
                    self::image('image', 'Background image'),
                    $theme,
                ],
            ],

            'rich_text' => [
                'label' => 'Rich text',
                'category' => 'Editorial',
                'icon' => 'text',
                'description' => 'Sanitised editorial copy with an optional heading.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', ''),
                    self::text('heading', 'Heading', ''),
                    self::richtext('body', 'Body', '<p></p>'),
                    self::select('width', 'Width', [
                        ['value' => 'standard', 'label' => 'Standard'],
                        ['value' => 'wide', 'label' => 'Wide'],
                        ['value' => 'narrow', 'label' => 'Narrow'],
                    ], 'standard'),
                    $theme,
                ],
            ],

            'image' => [
                'label' => 'Image',
                'category' => 'Media',
                'icon' => 'image',
                'description' => 'Single editorial image with optional caption.',
                'fields' => [
                    self::image('image', 'Image'),
                    self::text('caption', 'Caption', ''),
                    self::select('ratio', 'Aspect ratio', [
                        ['value' => '16:9', 'label' => '16:9'],
                        ['value' => '4:3', 'label' => '4:3'],
                        ['value' => '3:2', 'label' => '3:2'],
                        ['value' => '1:1', 'label' => 'Square'],
                        ['value' => '21:9', 'label' => '21:9'],
                    ], '16:9'),
                    self::select('width', 'Width', [
                        ['value' => 'standard', 'label' => 'Standard'],
                        ['value' => 'wide', 'label' => 'Wide'],
                        ['value' => 'full', 'label' => 'Full bleed'],
                    ], 'wide'),
                    $theme,
                ],
            ],

            'image_gallery' => [
                'label' => 'Image gallery',
                'category' => 'Media',
                'icon' => 'images',
                'description' => 'Responsive image set with captions.',
                'fields' => [
                    self::text('heading', 'Heading', ''),
                    self::repeater('items', 'Images', [
                        self::image('image', 'Image'),
                        self::text('caption', 'Caption'),
                    ], [], 0, 12),
                    self::select('columns', 'Columns', [
                        ['value' => '2', 'label' => 'Two'],
                        ['value' => '3', 'label' => 'Three'],
                        ['value' => '4', 'label' => 'Four'],
                    ], '3'),
                    $theme,
                ],
            ],

            'video' => [
                'label' => 'Video',
                'category' => 'Media',
                'icon' => 'play',
                'description' => 'Embedded video from an approved host or an uploaded file.',
                'fields' => [
                    self::text('title', 'Title', ''),
                    self::url('url', 'Video URL', ''),
                    self::image('poster', 'Poster image'),
                    self::text('caption', 'Caption', ''),
                    $theme,
                ],
            ],

            'timeline' => [
                'label' => 'Timeline',
                'category' => 'Editorial',
                'icon' => 'timeline',
                'description' => 'Company milestones in reverse chronological order.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Milestones'),
                    self::textarea('heading', 'Heading', 'A practice built year by year.', 2),
                    self::repeater('items', 'Milestones', [
                        self::text('year', 'Year'),
                        self::text('title', 'Title'),
                        self::textarea('description', 'Description', '', 3),
                    ], [], 0, 20),
                    $theme,
                ],
            ],

            'team_grid' => [
                'label' => 'Leadership grid',
                'category' => 'People',
                'icon' => 'people',
                'description' => 'Leadership team, resolved from the CMS.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Leadership'),
                    self::textarea('heading', 'Heading', 'Accountable at every layer.', 2),
                    self::textarea('intro', 'Intro', '', 4),
                    self::toggle('leadership_only', 'Leadership only', true),
                    self::number('limit', 'Limit', 8, 1, 20),
                    $theme,
                ],
            ],

            'quote' => [
                'label' => 'Quote',
                'category' => 'Editorial',
                'icon' => 'quote',
                'description' => 'A principle or attributed statement. Never presented as a client testimonial.',
                'fields' => [
                    self::textarea('quote', 'Quote', 'The obligation of an engineer is to make complexity legible.', 4),
                    self::text('attribution', 'Attribution', ''),
                    self::text('role', 'Role', ''),
                    $theme,
                ],
            ],

            'mission_values' => [
                'label' => 'Mission and values',
                'category' => 'People',
                'icon' => 'values',
                'description' => 'Mission statement plus numbered principles.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'What guides us'),
                    self::textarea('heading', 'Heading', 'Precision is a culture before it is a metric.', 2),
                    self::textarea('body', 'Body', '', 6),
                    self::repeater('items', 'Values', [
                        self::text('title', 'Title'),
                        self::textarea('description', 'Description', '', 3),
                    ], [], 0, 6),
                    $theme,
                ],
            ],

            'contact_form' => [
                'label' => 'Contact form',
                'category' => 'Conversion',
                'icon' => 'mail',
                'description' => 'Form rendered from a CMS form definition.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Contact'),
                    self::textarea('heading', 'Heading', 'Tell us what you are building.', 2),
                    self::textarea('body', 'Body', '', 4),
                    self::text('form_key', 'Form key', 'contact'),
                    self::repeater('departments', 'Departments', [
                        self::text('name', 'Name'),
                        self::text('email', 'Email'),
                        self::text('description', 'Description'),
                    ], [], 0, 8),
                    $theme,
                ],
            ],

            'contact_cards' => [
                'label' => 'Office cards',
                'category' => 'Conversion',
                'icon' => 'location',
                'description' => 'Offices and contact routes.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Offices'),
                    self::textarea('heading', 'Heading', 'Close to the operation.', 2),
                    self::repeater('items', 'Offices', [
                        self::text('city', 'City'),
                        self::text('address', 'Address'),
                        self::text('phone', 'Phone'),
                        self::text('email', 'Email'),
                        self::text('label', 'Label'),
                    ], [], 0, 6),
                    $theme,
                ],
            ],

            'job_list' => [
                'label' => 'Open roles',
                'category' => 'Careers',
                'icon' => 'briefcase',
                'description' => 'Open positions resolved from the CMS.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Careers'),
                    self::textarea('heading', 'Heading', 'Build the systems others depend on.', 2),
                    self::text('department', 'Department (optional)', ''),
                    self::number('limit', 'Limit', 12, 1, 30),
                    $theme,
                ],
            ],

            'document_list' => [
                'label' => 'Document library',
                'category' => 'Investor relations',
                'icon' => 'document',
                'description' => 'Downloadable documents with illustrative-data labels.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Documents'),
                    self::textarea('heading', 'Heading', 'Corporate and investor documents.', 2),
                    self::select('category', 'Category', [
                        ['value' => '', 'label' => 'All categories'],
                        ['value' => 'annual_report', 'label' => 'Annual reports'],
                        ['value' => 'financial', 'label' => 'Financial documents'],
                        ['value' => 'governance', 'label' => 'Governance'],
                        ['value' => 'corporate', 'label' => 'Corporate'],
                    ], ''),
                    self::number('limit', 'Limit', 12, 1, 30),
                    $theme,
                ],
            ],

            'investor_highlights' => [
                'label' => 'Investor highlights',
                'category' => 'Investor relations',
                'icon' => 'chart',
                'description' => 'Illustrative financial highlights with mandatory disclosure.',
                'fields' => [
                    self::text('eyebrow', 'Eyebrow', 'Financial highlights'),
                    self::textarea('heading', 'Heading', 'Illustrative performance overview.', 2),
                    self::textarea('note', 'Note', 'All figures on this page are illustrative demonstration data for a fictional private company. They are not audited financial statements.', 5),
                    self::number('years', 'Years shown', 3, 1, 6),
                    $theme,
                ],
            ],

            'html_embed' => [
                'label' => 'Safe HTML embed',
                'category' => 'Advanced',
                'icon' => 'code',
                'description' => 'Allow-listed HTML for approved embeds. Scripts and unknown iframes are removed.',
                'fields' => [
                    self::textarea('html', 'HTML', '', 20),
                    $alignment,
                    $theme,
                ],
            ],
        ];
    }

    public static function publicDefinitions(): array
    {
        return collect(self::definitions())
            ->map(fn (array $definition, string $type) => [
                'type' => $type,
                'label' => $definition['label'],
                'category' => $definition['category'],
                'icon' => $definition['icon'],
                'description' => $definition['description'],
                'defaults' => self::defaultsFor($type),
                'fields' => $definition['fields'],
            ])
            ->values()
            ->all();
    }

    public static function defaultsFor(string $type): array
    {
        $definition = self::definitions()[$type] ?? null;
        abort_unless($definition, 422, "Unknown block type [{$type}].");

        $defaults = [];

        foreach ($definition['fields'] as $field) {
            $defaults[$field['key']] = $field['default'] ?? null;
        }

        return $defaults;
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::definitions());
    }

    /**
     * Validate and normalise a full block document. Unknown keys are dropped,
     * rich text is sanitised, and every value is coerced to its declared type.
     *
     * @return array<int, array{block_uid:string,type:string,props:array,responsive:array}>
     *
     * @throws ValidationException
     */
    public static function normalizeBlocks(array $blocks, Site $site): array
    {
        if (count($blocks) > 40) {
            throw ValidationException::withMessages(['blocks' => 'A page may contain at most 40 blocks.']);
        }

        $normalized = [];
        $seen = [];

        foreach (array_values($blocks) as $index => $block) {
            $type = $block['type'] ?? null;

            if (! is_string($type) || ! self::isKnown($type)) {
                throw ValidationException::withMessages([
                    "blocks.{$index}.type" => 'The selected block type is not available.',
                ]);
            }

            $uid = $block['block_uid'] ?? $block['uid'] ?? (string) Str::uuid();
            $uid = Str::limit(preg_replace('/[^a-zA-Z0-9-]/', '', $uid), 36, '');

            if ($uid === '' || isset($seen[$uid])) {
                $uid = (string) Str::uuid();
            }

            $seen[$uid] = true;

            $normalized[] = [
                'block_uid' => $uid,
                'type' => $type,
                'props' => self::normalizeProps($type, (array) ($block['props'] ?? []), $site),
                'is_visible_desktop' => (bool) ($block['responsive']['desktop'] ?? $block['is_visible_desktop'] ?? true),
                'is_visible_tablet' => (bool) ($block['responsive']['tablet'] ?? $block['is_visible_tablet'] ?? true),
                'is_visible_mobile' => (bool) ($block['responsive']['mobile'] ?? $block['is_visible_mobile'] ?? true),
                'responsive' => is_array($block['responsive']['overrides'] ?? $block['responsive'] ?? null)
                    ? array_intersect_key((array) ($block['responsive']['overrides'] ?? $block['responsive']), array_flip(['spacing', 'background', 'alignment']))
                    : null,
            ];
        }

        return $normalized;
    }

    public static function normalizeProps(string $type, array $input, Site $site): array
    {
        $definition = self::definitions()[$type];
        $props = [];

        foreach ($definition['fields'] as $field) {
            $key = $field['key'];
            $value = array_key_exists($key, $input) ? $input[$key] : ($field['default'] ?? null);
            $props[$key] = self::normalizeField($field, $value, "blocks.{$type}.{$key}", $site);
        }

        return $props;
    }

    protected static function normalizeField(array $field, mixed $value, string $path, Site $site): mixed
    {
        if ($value === null || $value === '') {
            if (! empty($field['required'])) {
                throw ValidationException::withMessages([$path => $field['label'].' is required.']);
            }

            return $field['type'] === 'repeater' ? [] : null;
        }

        return match ($field['type']) {
            'text', 'textarea' => self::normalizeText($field, $value, $path),
            'richtext' => self::normalizeRichText($field, $value, $path),
            'number' => self::normalizeNumber($field, $value, $path),
            'toggle' => (bool) $value,
            'select' => self::normalizeSelect($field, $value, $path),
            'url' => self::normalizeUrl($field, $value, $path),
            'link' => self::normalizeLink($field, $value, $path),
            'image' => self::normalizeImage($field, $value, $path, $site),
            'repeater' => self::normalizeRepeater($field, $value, $path, $site),
            default => null,
        };
    }

    protected static function normalizeText(array $field, mixed $value, string $path): string
    {
        if (! is_scalar($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be text.']);
        }

        $text = trim((string) $value);
        $max = $field['max'] ?? 5000;

        if (mb_strlen($text) > $max) {
            throw ValidationException::withMessages([$path => $field['label']." may not exceed {$max} characters."]);
        }

        return $text;
    }

    protected static function normalizeRichText(array $field, mixed $value, string $path): string
    {
        if (! is_string($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be HTML text.']);
        }

        $sanitized = app(RichTextSanitizer::class)->sanitize($value);

        if (mb_strlen($sanitized) > ($field['max'] ?? 80000)) {
            throw ValidationException::withMessages([$path => $field['label'].' is too long.']);
        }

        return $sanitized;
    }

    protected static function normalizeNumber(array $field, mixed $value, string $path): int|float
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be a number.']);
        }

        $number = $value + 0;
        $min = $field['min'] ?? null;
        $max = $field['max'] ?? null;

        if (($min !== null && $number < $min) || ($max !== null && $number > $max)) {
            throw ValidationException::withMessages([
                $path => $field['label']." must be between {$min} and {$max}.",
            ]);
        }

        return $number;
    }

    protected static function normalizeSelect(array $field, mixed $value, string $path): string
    {
        $allowed = array_column($field['options'] ?? [], 'value');

        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([$path => $field['label'].' has an unsupported value.']);
        }

        return (string) $value;
    }

    protected static function normalizeUrl(array $field, mixed $value, string $path): string
    {
        $url = trim((string) $value);

        if (! self::isSafeUrl($url)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be a safe URL.']);
        }

        return $url;
    }

    protected static function normalizeLink(array $field, mixed $value, string $path): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' is invalid.']);
        }

        $label = trim((string) ($value['label'] ?? ''));
        $url = trim((string) ($value['url'] ?? ''));
        $style = in_array(($value['style'] ?? 'primary'), ['primary', 'secondary', 'text'], true)
            ? ($value['style'] ?? 'primary') : 'primary';

        if ($label === '' || mb_strlen($label) > 80) {
            throw ValidationException::withMessages([$path.'.label' => 'The link label is required and may not exceed 80 characters.']);
        }

        if (! self::isSafeUrl($url)) {
            throw ValidationException::withMessages([$path.'.url' => 'The link URL must be internal, https, or mailto.']);
        }

        return ['label' => $label, 'url' => $url, 'style' => $style];
    }

    protected static function normalizeImage(array $field, mixed $value, string $path, Site $site): ?array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be an image.']);
        }

        $mediaId = $value['media_id'] ?? null;
        $url = trim((string) ($value['url'] ?? ''));
        $alt = trim((string) ($value['alt'] ?? ''));

        if ($mediaId) {
            $asset = MediaAsset::forSite($site)->find($mediaId);

            if (! $asset) {
                throw ValidationException::withMessages([$path.'.media_id' => 'The selected media does not belong to this website.']);
            }

            $url = $asset->url;
            $alt = $alt ?: $asset->altFor('en');
        }

        if ($url === '' || ! self::isSafeUrl($url)) {
            throw ValidationException::withMessages([$path.'.url' => 'A valid image URL is required.']);
        }

        return [
            'media_id' => $mediaId ? (int) $mediaId : null,
            'url' => $url,
            'alt' => mb_substr($alt, 0, 240),
        ];
    }

    protected static function normalizeRepeater(array $field, mixed $value, string $path, Site $site): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([$path => $field['label'].' must be a list.']);
        }

        $min = $field['min'] ?? 0;
        $max = $field['max'] ?? 20;

        if (count($value) < $min || count($value) > $max) {
            throw ValidationException::withMessages([
                $path => "{$field['label']} must contain between {$min} and {$max} items.",
            ]);
        }

        $items = [];

        foreach (array_values($value) as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages(["{$path}.{$index}" => 'Each item must be an object.']);
            }

            $normalized = [];

            foreach ($field['fields'] as $subField) {
                $key = $subField['key'];
                $subValue = $item[$key] ?? ($subField['default'] ?? null);
                $normalized[$key] = self::normalizeField($subField, $subValue, "{$path}.{$index}.{$key}", $site);
            }

            $items[] = $normalized;
        }

        return $items;
    }

    public static function isSafeUrl(string $url): bool
    {
        if ($url === '' || ! preg_match('/^(#|\/|https?:\/\/|mailto:|tel:)/i', $url)) {
            return false;
        }

        if (preg_match('/^\s*javascript:/i', $url)) {
            return false;
        }

        return true;
    }

    public static function sanitizeEmbed(?string $html): string
    {
        $html = (string) $html;
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace_callback('/<iframe\b[^>]*src=("|\')([^"\']+)\1[^>]*>/i', function ($match) {
            $host = strtolower((string) parse_url($match[2], PHP_URL_HOST));
            $allowed = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'];

            return in_array($host, $allowed, true) ? $match[0] : '';
        }, $html);

        return app(RichTextSanitizer::class)->sanitize($html);
    }

    protected static function text(string $key, string $label, $default = null, int $max = 255): array
    {
        return compact('key', 'label', 'default', 'max') + ['type' => 'text'];
    }

    protected static function textarea(string $key, string $label, $default = null, int $rows = 4, int $max = 10000): array
    {
        return compact('key', 'label', 'default', 'rows', 'max') + ['type' => 'textarea'];
    }

    protected static function richtext(string $key, string $label, $default = null): array
    {
        return compact('key', 'label', 'default') + ['type' => 'richtext', 'max' => 80000];
    }

    protected static function number(string $key, string $label, $default = 0, $min = null, $max = null): array
    {
        return compact('key', 'label', 'default', 'min', 'max') + ['type' => 'number'];
    }

    protected static function select(string $key, string $label, array $options, $default = null): array
    {
        return compact('key', 'label', 'options', 'default') + ['type' => 'select'];
    }

    protected static function toggle(string $key, string $label, bool $default = false): array
    {
        return compact('key', 'label', 'default') + ['type' => 'toggle'];
    }

    protected static function url(string $key, string $label, string $default = ''): array
    {
        return compact('key', 'label', 'default') + ['type' => 'url'];
    }

    protected static function image(string $key, string $label): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'image', 'default' => null];
    }

    protected static function repeater(string $key, string $label, array $fields, array $default = [], int $min = 0, int $max = 20): array
    {
        return compact('key', 'label', 'fields', 'default', 'min', 'max') + ['type' => 'repeater'];
    }
}
