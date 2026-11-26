<?php

use App\Http\Controllers\Api\V1\Admin\AccessAdminController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsAdminController;
use App\Http\Controllers\Api\V1\Admin\ArticleAdminController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\CaseStudyAdminController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DocumentsAdminController;
use App\Http\Controllers\Api\V1\Admin\FormsAdminController;
use App\Http\Controllers\Api\V1\Admin\IndustryAdminController;
use App\Http\Controllers\Api\V1\Admin\JobsAdminController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\NavigationAdminController;
use App\Http\Controllers\Api\V1\Admin\PagesController;
use App\Http\Controllers\Api\V1\Admin\SeoAdminController;
use App\Http\Controllers\Api\V1\Admin\SettingsAdminController;
use App\Http\Controllers\Api\V1\Admin\SitesAdminController;
use App\Http\Controllers\Api\V1\Admin\SolutionAdminController;
use App\Http\Controllers\Api\V1\Admin\TeamAdminController;
use App\Http\Controllers\Api\V1\Admin\WorkflowAdminController;
use App\Http\Controllers\Api\V1\PublicApi\PublicAnalyticsController;
use App\Http\Controllers\Api\V1\PublicApi\PublicCollectionController;
use App\Http\Controllers\Api\V1\PublicApi\PublicFormController;
use App\Http\Controllers\Api\V1\PublicApi\PublicJobApplicationController;
use App\Http\Controllers\Api\V1\PublicApi\PublicPageController;
use App\Http\Controllers\Api\V1\PublicApi\PublicSearchController;
use App\Http\Controllers\Api\V1\PublicApi\PublicSiteController;
use App\Http\Controllers\Api\V1\PublicApi\PublicSitemapController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        try {
            DB::select('select 1');
            $database = 'ok';
        } catch (Throwable $e) {
            $database = 'unavailable';
        }

        return response()->json([
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'database' => $database,
            'version' => '1.0.0',
            'time' => now()->toIso8601String(),
        ], $database === 'ok' ? 200 : 503);
    });

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me'])->middleware(['site']);
        Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::get('/auth/notifications', [AuthController::class, 'notifications']);
        Route::post('/auth/notifications/read-all', [AuthController::class, 'markAllNotifications']);
        Route::post('/auth/notifications/{id}/read', [AuthController::class, 'markNotification']);
    });

    Route::prefix('public')->middleware(['site', 'locale'])->group(function () {
        Route::get('/bootstrap', [PublicSiteController::class, 'bootstrap']);
        Route::get('/contact', [PublicSiteController::class, 'contact']);
        Route::get('/investor', [PublicSiteController::class, 'investor']);

        Route::get('/home', [PublicPageController::class, 'home']);
        Route::get('/pages/{slug}', [PublicPageController::class, 'show']);
        Route::get('/resolve', [PublicPageController::class, 'resolve']);
        Route::get('/preview/{token}', [PublicPageController::class, 'preview']);

        Route::get('/solutions', [PublicCollectionController::class, 'solutions']);
        Route::get('/solutions/{slug}', [PublicCollectionController::class, 'solution']);
        Route::get('/industries', [PublicCollectionController::class, 'industries']);
        Route::get('/industries/{slug}', [PublicCollectionController::class, 'industry']);
        Route::get('/case-studies', [PublicCollectionController::class, 'caseStudies']);
        Route::get('/case-studies/{slug}', [PublicCollectionController::class, 'caseStudy']);
        Route::get('/articles', [PublicCollectionController::class, 'articles']);
        Route::get('/articles/{slug}', [PublicCollectionController::class, 'article']);
        Route::get('/careers', [PublicCollectionController::class, 'careers']);
        Route::get('/careers/{slug}', [PublicCollectionController::class, 'job']);
        Route::get('/documents', [PublicCollectionController::class, 'documents']);
        Route::get('/team', [PublicCollectionController::class, 'team']);

        Route::get('/search', [PublicSearchController::class, 'search'])->middleware('throttle:search');
        Route::get('/search/suggestions', [PublicSearchController::class, 'suggestions'])->middleware('throttle:search');
        Route::get('/sitemap', [PublicSitemapController::class, 'locale']);
        Route::get('/sitemap-index', [PublicSitemapController::class, 'index']);

        Route::post('/forms/{key}/submissions', [PublicFormController::class, 'submit'])->middleware('throttle:public-forms');
        Route::post('/jobs/{slug}/applications', [PublicJobApplicationController::class, 'store'])->middleware('throttle:public-forms');
        Route::post('/analytics/events', [PublicAnalyticsController::class, 'store'])->middleware('throttle:api');
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'site', 'site.access'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:pages.view');
        Route::get('/analytics', [AnalyticsAdminController::class, 'index'])->middleware('permission:analytics.view');
        Route::get('/block-types', [PagesController::class, 'blockTypes'])->middleware('permission:pages.view');

        Route::get('/sites', [SitesAdminController::class, 'index'])->middleware('permission:sites.view');
        Route::post('/sites', [SitesAdminController::class, 'store'])->middleware('permission:sites.manage');
        Route::get('/sites/{site}', [SitesAdminController::class, 'show'])->middleware('permission:sites.view');
        Route::patch('/sites/{site}', [SitesAdminController::class, 'update'])->middleware('permission:settings.manage');

        Route::get('/pages', [PagesController::class, 'index'])->middleware('permission:pages.view');
        Route::post('/pages', [PagesController::class, 'store'])->middleware('permission:pages.create');
        Route::post('/pages/bulk', [PagesController::class, 'bulk']);
        Route::get('/pages/{page}', [PagesController::class, 'show'])->middleware('permission:pages.view');
        Route::patch('/pages/{page}', [PagesController::class, 'update']);
        Route::delete('/pages/{page}', [PagesController::class, 'destroy'])->middleware('permission:pages.delete');
        Route::post('/pages/{page}/duplicate', [PagesController::class, 'duplicate']);
        Route::post('/pages/{page}/translations', [PagesController::class, 'addTranslation']);
        Route::put('/pages/{page}/blocks', [PagesController::class, 'blocks']);
        Route::put('/pages/{page}/seo', [PagesController::class, 'seo']);
        Route::get('/pages/{page}/revisions', [PagesController::class, 'revisions'])->middleware('permission:pages.view');
        Route::post('/pages/{page}/revisions/{revision}/restore', [PagesController::class, 'restoreRevision']);
        Route::post('/pages/{page}/preview-token', [PagesController::class, 'previewToken'])->middleware('permission:pages.view');
        Route::post('/pages/{page}/submit', [PagesController::class, 'submit']);
        Route::post('/pages/{page}/approve', [PagesController::class, 'approve'])->middleware('permission:pages.approve');
        Route::post('/pages/{page}/request-changes', [PagesController::class, 'requestChanges'])->middleware('permission:pages.approve');
        Route::post('/pages/{page}/publish', [PagesController::class, 'publish'])->middleware('permission:pages.publish');
        Route::post('/pages/{page}/schedule', [PagesController::class, 'schedule'])->middleware('permission:pages.publish');
        Route::post('/pages/{page}/cancel-schedule', [PagesController::class, 'cancelSchedule'])->middleware('permission:pages.publish');
        Route::post('/pages/{page}/unpublish', [PagesController::class, 'unpublish'])->middleware('permission:pages.publish');

        Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.view');
        Route::post('/media', [MediaController::class, 'store'])->middleware(['permission:media.upload', 'throttle:uploads']);
        Route::get('/media/{asset}', [MediaController::class, 'show'])->middleware('permission:media.view');
        Route::patch('/media/{asset}', [MediaController::class, 'update'])->middleware('permission:media.edit');
        Route::post('/media/folders', [MediaController::class, 'createFolder'])->middleware('permission:media.edit');
        Route::get('/media/{asset}/usage', [MediaController::class, 'usage'])->middleware('permission:media.view');
        Route::delete('/media/{asset}', [MediaController::class, 'destroy'])->middleware('permission:media.delete');

        Route::get('/articles', [ArticleAdminController::class, 'index'])->middleware('permission:articles.view');
        Route::post('/articles', [ArticleAdminController::class, 'store'])->middleware('permission:articles.create');
        Route::get('/articles/categories', [ArticleAdminController::class, 'categories'])->middleware('permission:articles.view');
        Route::post('/articles/categories', [ArticleAdminController::class, 'storeCategory'])->middleware('permission:articles.create');
        Route::get('/articles/{article}', [ArticleAdminController::class, 'show'])->middleware('permission:articles.view');
        Route::patch('/articles/{article}', [ArticleAdminController::class, 'update']);
        Route::delete('/articles/{article}', [ArticleAdminController::class, 'destroy'])->middleware('permission:articles.delete');

        Route::get('/case-studies', [CaseStudyAdminController::class, 'index'])->middleware('permission:case_studies.view');
        Route::post('/case-studies', [CaseStudyAdminController::class, 'store'])->middleware('permission:case_studies.manage');
        Route::get('/case-studies/{caseStudy}', [CaseStudyAdminController::class, 'show'])->middleware('permission:case_studies.view');
        Route::patch('/case-studies/{caseStudy}', [CaseStudyAdminController::class, 'update'])->middleware('permission:case_studies.manage');
        Route::delete('/case-studies/{caseStudy}', [CaseStudyAdminController::class, 'destroy'])->middleware('permission:case_studies.manage');

        Route::get('/solutions', [SolutionAdminController::class, 'index'])->middleware('permission:solutions.view');
        Route::get('/solutions/{solution}', [SolutionAdminController::class, 'show'])->middleware('permission:solutions.view');
        Route::patch('/solutions/{solution}', [SolutionAdminController::class, 'update'])->middleware('permission:solutions.manage');

        Route::get('/industries', [IndustryAdminController::class, 'index'])->middleware('permission:industries.view');
        Route::get('/industries/{industry}', [IndustryAdminController::class, 'show'])->middleware('permission:industries.view');
        Route::patch('/industries/{industry}', [IndustryAdminController::class, 'update'])->middleware('permission:industries.manage');

        Route::get('/jobs', [JobsAdminController::class, 'index'])->middleware('permission:jobs.view');
        Route::post('/jobs', [JobsAdminController::class, 'store'])->middleware('permission:jobs.manage');
        Route::get('/jobs/{job}', [JobsAdminController::class, 'show'])->middleware('permission:jobs.view');
        Route::patch('/jobs/{job}', [JobsAdminController::class, 'update'])->middleware('permission:jobs.manage');
        Route::delete('/jobs/{job}', [JobsAdminController::class, 'destroy'])->middleware('permission:jobs.manage');
        Route::get('/careers/applications', [JobsAdminController::class, 'applications'])->middleware('permission:applications.view');
        Route::get('/careers/applications/{application}', [JobsAdminController::class, 'application'])->middleware('permission:applications.view');
        Route::patch('/careers/applications/{application}', [JobsAdminController::class, 'updateApplication'])->middleware('permission:applications.manage');

        Route::get('/documents', [DocumentsAdminController::class, 'index'])->middleware('permission:documents.view');
        Route::post('/documents', [DocumentsAdminController::class, 'store'])->middleware('permission:documents.manage');
        Route::patch('/documents/{document}', [DocumentsAdminController::class, 'update'])->middleware('permission:documents.manage');
        Route::delete('/documents/{document}', [DocumentsAdminController::class, 'destroy'])->middleware('permission:documents.manage');

        Route::get('/team', [TeamAdminController::class, 'index'])->middleware('permission:users.view');
        Route::post('/team', [TeamAdminController::class, 'store'])->middleware('permission:users.manage');
        Route::patch('/team/{teamMember}', [TeamAdminController::class, 'update'])->middleware('permission:users.manage');
        Route::delete('/team/{teamMember}', [TeamAdminController::class, 'destroy'])->middleware('permission:users.manage');

        Route::get('/navigation', [NavigationAdminController::class, 'index'])->middleware('permission:navigation.manage');
        Route::post('/navigation', [NavigationAdminController::class, 'storeMenu'])->middleware('permission:navigation.manage');
        Route::put('/navigation/{menu}', [NavigationAdminController::class, 'update'])->middleware('permission:navigation.manage');

        Route::get('/forms', [FormsAdminController::class, 'index'])->middleware('permission:forms.view');
        Route::get('/forms/{form}', [FormsAdminController::class, 'show'])->middleware('permission:forms.view');
        Route::patch('/forms/{form}', [FormsAdminController::class, 'update'])->middleware('permission:forms.manage');
        Route::get('/submissions', [FormsAdminController::class, 'submissions'])->middleware('permission:forms.view');
        Route::get('/submissions/export', [FormsAdminController::class, 'exportSubmissions'])->middleware('permission:leads.export');
        Route::get('/submissions/{submission}', [FormsAdminController::class, 'submission'])->middleware('permission:forms.view');
        Route::patch('/submissions/{submission}', [FormsAdminController::class, 'updateSubmission'])->middleware('permission:leads.manage');

        Route::get('/leads', [FormsAdminController::class, 'leads'])->middleware('permission:leads.view');
        Route::get('/leads/export', [FormsAdminController::class, 'exportLeads'])->middleware('permission:leads.export');
        Route::get('/leads/{lead}', [FormsAdminController::class, 'lead'])->middleware('permission:leads.view');
        Route::patch('/leads/{lead}', [FormsAdminController::class, 'updateLead'])->middleware('permission:leads.manage');
        Route::post('/leads/{lead}/activities', [FormsAdminController::class, 'addActivity'])->middleware('permission:leads.manage');
        Route::delete('/leads/{lead}', [FormsAdminController::class, 'destroyLead'])->middleware('permission:leads.manage');

        Route::get('/seo/health', [SeoAdminController::class, 'health'])->middleware('permission:seo.view');
        Route::post('/seo/audit', [SeoAdminController::class, 'audit'])->middleware('permission:seo.manage');
        Route::get('/seo/issues', [SeoAdminController::class, 'issues'])->middleware('permission:seo.view');
        Route::patch('/seo/issues/{issue}', [SeoAdminController::class, 'updateIssue'])->middleware('permission:seo.manage');
        Route::get('/seo/sitemap', [SeoAdminController::class, 'sitemap'])->middleware('permission:seo.view');
        Route::get('/redirects', [SeoAdminController::class, 'redirects'])->middleware('permission:seo.view');
        Route::post('/redirects', [SeoAdminController::class, 'storeRedirect'])->middleware('permission:redirects.manage');
        Route::patch('/redirects/{redirect}', [SeoAdminController::class, 'updateRedirect'])->middleware('permission:redirects.manage');
        Route::delete('/redirects/{redirect}', [SeoAdminController::class, 'destroyRedirect'])->middleware('permission:redirects.manage');

        Route::get('/users', [AccessAdminController::class, 'users'])->middleware('permission:users.view');
        Route::post('/users/invite', [AccessAdminController::class, 'invite'])->middleware('permission:users.manage');
        Route::patch('/users/{user}', [AccessAdminController::class, 'update'])->middleware('permission:users.manage');
        Route::get('/roles', [AccessAdminController::class, 'roles'])->middleware('permission:roles.view');
        Route::get('/audit-logs', [AccessAdminController::class, 'auditLogs'])->middleware('permission:audit.view');

        Route::get('/settings', [SettingsAdminController::class, 'show'])->middleware('permission:settings.manage');
        Route::patch('/settings', [SettingsAdminController::class, 'update'])->middleware('permission:settings.manage');
        Route::put('/integrations/{provider}', [SettingsAdminController::class, 'updateIntegration'])->middleware('permission:integrations.manage');
        Route::post('/integrations/{provider}/test', [SettingsAdminController::class, 'testIntegration'])->middleware('permission:integrations.manage');

        Route::get('/workflows', [WorkflowAdminController::class, 'index'])->middleware('permission:workflow.view');
        Route::patch('/workflows/definitions/{definition}', [WorkflowAdminController::class, 'updateDefinition'])->middleware('permission:workflow.manage');
    });
});
