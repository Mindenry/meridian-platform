<?php

namespace App\Cms;

class PermissionRegistry
{
    /**
     * Canonical permission catalogue. Kept in code so a fresh database and a
     * running database always agree on the available permission surface.
     */
    public static function permissions(): array
    {
        return [
            ['slug' => 'sites.view', 'name' => 'View websites', 'group' => 'Websites'],
            ['slug' => 'sites.manage', 'name' => 'Manage websites', 'group' => 'Websites'],
            ['slug' => 'users.view', 'name' => 'View team', 'group' => 'Team & access'],
            ['slug' => 'users.manage', 'name' => 'Manage team and roles', 'group' => 'Team & access'],
            ['slug' => 'roles.view', 'name' => 'View roles', 'group' => 'Team & access'],
            ['slug' => 'roles.manage', 'name' => 'Manage roles', 'group' => 'Team & access'],

            ['slug' => 'pages.view', 'name' => 'View pages', 'group' => 'Pages'],
            ['slug' => 'pages.create', 'name' => 'Create pages', 'group' => 'Pages'],
            ['slug' => 'pages.edit_own', 'name' => 'Edit own pages', 'group' => 'Pages'],
            ['slug' => 'pages.edit_all', 'name' => 'Edit all pages', 'group' => 'Pages'],
            ['slug' => 'pages.delete', 'name' => 'Delete pages', 'group' => 'Pages'],
            ['slug' => 'pages.publish', 'name' => 'Publish pages', 'group' => 'Pages'],
            ['slug' => 'pages.approve', 'name' => 'Approve pages', 'group' => 'Pages'],

            ['slug' => 'articles.view', 'name' => 'View articles', 'group' => 'Content'],
            ['slug' => 'articles.create', 'name' => 'Create articles', 'group' => 'Content'],
            ['slug' => 'articles.edit_own', 'name' => 'Edit own articles', 'group' => 'Content'],
            ['slug' => 'articles.edit_all', 'name' => 'Edit all articles', 'group' => 'Content'],
            ['slug' => 'articles.delete', 'name' => 'Delete articles', 'group' => 'Content'],
            ['slug' => 'articles.publish', 'name' => 'Publish articles', 'group' => 'Content'],
            ['slug' => 'articles.approve', 'name' => 'Approve articles', 'group' => 'Content'],

            ['slug' => 'case_studies.view', 'name' => 'View case studies', 'group' => 'Content'],
            ['slug' => 'case_studies.manage', 'name' => 'Manage case studies', 'group' => 'Content'],
            ['slug' => 'case_studies.publish', 'name' => 'Publish case studies', 'group' => 'Content'],
            ['slug' => 'solutions.view', 'name' => 'View solutions', 'group' => 'Content'],
            ['slug' => 'solutions.manage', 'name' => 'Manage solutions', 'group' => 'Content'],
            ['slug' => 'industries.view', 'name' => 'View industries', 'group' => 'Content'],
            ['slug' => 'industries.manage', 'name' => 'Manage industries', 'group' => 'Content'],
            ['slug' => 'jobs.view', 'name' => 'View job openings', 'group' => 'Careers'],
            ['slug' => 'jobs.manage', 'name' => 'Manage job openings', 'group' => 'Careers'],
            ['slug' => 'applications.view', 'name' => 'View applications', 'group' => 'Careers'],
            ['slug' => 'applications.manage', 'name' => 'Manage applications', 'group' => 'Careers'],
            ['slug' => 'documents.view', 'name' => 'View documents', 'group' => 'Investor relations'],
            ['slug' => 'documents.manage', 'name' => 'Manage documents', 'group' => 'Investor relations'],

            ['slug' => 'media.view', 'name' => 'View media', 'group' => 'Media'],
            ['slug' => 'media.upload', 'name' => 'Upload media', 'group' => 'Media'],
            ['slug' => 'media.edit', 'name' => 'Edit media metadata', 'group' => 'Media'],
            ['slug' => 'media.delete', 'name' => 'Delete media', 'group' => 'Media'],
            ['slug' => 'navigation.manage', 'name' => 'Manage navigation', 'group' => 'Navigation'],

            ['slug' => 'forms.view', 'name' => 'View forms', 'group' => 'Forms & leads'],
            ['slug' => 'forms.manage', 'name' => 'Manage forms', 'group' => 'Forms & leads'],
            ['slug' => 'leads.view', 'name' => 'View leads', 'group' => 'Forms & leads'],
            ['slug' => 'leads.manage', 'name' => 'Manage leads', 'group' => 'Forms & leads'],
            ['slug' => 'leads.export', 'name' => 'Export leads', 'group' => 'Forms & leads'],

            ['slug' => 'seo.view', 'name' => 'View SEO health', 'group' => 'SEO'],
            ['slug' => 'seo.manage', 'name' => 'Manage SEO metadata', 'group' => 'SEO'],
            ['slug' => 'redirects.manage', 'name' => 'Manage redirects', 'group' => 'SEO'],
            ['slug' => 'analytics.view', 'name' => 'View analytics', 'group' => 'Analytics'],
            ['slug' => 'workflow.view', 'name' => 'View workflow', 'group' => 'Workflow'],
            ['slug' => 'workflow.manage', 'name' => 'Manage workflow rules', 'group' => 'Workflow'],
            ['slug' => 'settings.manage', 'name' => 'Manage site settings', 'group' => 'Settings'],
            ['slug' => 'integrations.manage', 'name' => 'Manage integrations', 'group' => 'Settings'],
            ['slug' => 'audit.view', 'name' => 'View audit log', 'group' => 'Settings'],
        ];
    }

    public static function rolePresets(): array
    {
        return [
            'super_admin' => [
                'name' => 'Super Administrator',
                'description' => 'Full control of every website and global configuration.',
                'level' => 100,
                'permissions' => '*',
            ],
            'site_admin' => [
                'name' => 'Site Administrator',
                'description' => 'Full control of the assigned website.',
                'level' => 90,
                'permissions' => [
                    'sites.view', 'users.view', 'users.manage', 'roles.view',
                    'pages.view', 'pages.create', 'pages.edit_all', 'pages.delete', 'pages.publish', 'pages.approve',
                    'articles.view', 'articles.create', 'articles.edit_all', 'articles.delete', 'articles.publish', 'articles.approve',
                    'case_studies.view', 'case_studies.manage', 'case_studies.publish',
                    'solutions.view', 'solutions.manage', 'industries.view', 'industries.manage',
                    'jobs.view', 'jobs.manage', 'applications.view', 'applications.manage',
                    'documents.view', 'documents.manage',
                    'media.view', 'media.upload', 'media.edit', 'media.delete',
                    'navigation.manage', 'forms.view', 'forms.manage', 'leads.view', 'leads.manage', 'leads.export',
                    'seo.view', 'seo.manage', 'redirects.manage', 'analytics.view',
                    'workflow.view', 'workflow.manage', 'settings.manage', 'integrations.manage', 'audit.view',
                ],
            ],
            'marketing_manager' => [
                'name' => 'Marketing Manager',
                'description' => 'Leads content, campaigns, and the publishing calendar.',
                'level' => 70,
                'permissions' => [
                    'sites.view', 'users.view', 'roles.view',
                    'pages.view', 'pages.create', 'pages.edit_all', 'pages.delete', 'pages.publish', 'pages.approve',
                    'articles.view', 'articles.create', 'articles.edit_all', 'articles.delete', 'articles.publish', 'articles.approve',
                    'case_studies.view', 'case_studies.manage', 'case_studies.publish',
                    'solutions.view', 'solutions.manage', 'industries.view', 'industries.manage',
                    'jobs.view', 'documents.view',
                    'media.view', 'media.upload', 'media.edit', 'media.delete', 'navigation.manage',
                    'forms.view', 'forms.manage', 'leads.view', 'leads.manage', 'leads.export',
                    'seo.view', 'seo.manage', 'analytics.view', 'workflow.view',
                ],
            ],
            'content_editor' => [
                'name' => 'Content Editor',
                'description' => 'Writes and edits content, then submits it for review.',
                'level' => 40,
                'permissions' => [
                    'sites.view', 'pages.view', 'pages.create', 'pages.edit_own',
                    'articles.view', 'articles.create', 'articles.edit_own',
                    'case_studies.view', 'case_studies.manage',
                    'solutions.view', 'industries.view', 'media.view', 'media.upload',
                    'forms.view', 'seo.view', 'workflow.view',
                ],
            ],
            'content_reviewer' => [
                'name' => 'Content Reviewer',
                'description' => 'Reviews editorial quality and approves publishing requests.',
                'level' => 50,
                'permissions' => [
                    'sites.view', 'pages.view', 'pages.edit_all', 'pages.approve',
                    'articles.view', 'articles.edit_all', 'articles.approve',
                    'case_studies.view', 'solutions.view', 'industries.view',
                    'media.view', 'seo.view', 'workflow.view', 'audit.view',
                ],
            ],
            'seo_specialist' => [
                'name' => 'SEO Specialist',
                'description' => 'Owns metadata, redirects, crawl health, and search performance.',
                'level' => 50,
                'permissions' => [
                    'sites.view', 'pages.view', 'pages.edit_all', 'articles.view', 'articles.edit_all',
                    'case_studies.view', 'solutions.view', 'industries.view', 'media.view', 'media.edit',
                    'seo.view', 'seo.manage', 'redirects.manage', 'analytics.view', 'workflow.view',
                ],
            ],
            'analyst' => [
                'name' => 'Analyst',
                'description' => 'Read-only access to performance and pipeline reporting.',
                'level' => 30,
                'permissions' => [
                    'sites.view', 'pages.view', 'articles.view', 'case_studies.view',
                    'solutions.view', 'industries.view', 'leads.view', 'seo.view', 'analytics.view',
                ],
            ],
            'careers_manager' => [
                'name' => 'Careers Manager',
                'description' => 'Manages job openings and candidate applications.',
                'level' => 40,
                'permissions' => [
                    'sites.view', 'jobs.view', 'jobs.manage', 'applications.view', 'applications.manage',
                    'media.view', 'media.upload', 'leads.view', 'leads.manage',
                ],
            ],
        ];
    }

    public static function allSlugs(): array
    {
        return array_column(self::permissions(), 'slug');
    }

    public static function forRole(string $slug): array
    {
        $preset = self::rolePresets()[$slug] ?? null;

        if (! $preset) {
            return [];
        }

        return $preset['permissions'] === '*' ? self::allSlugs() : $preset['permissions'];
    }
}
