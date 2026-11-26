<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NavigationAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->normalizeLocale($request->query('locale'));

        $menus = NavigationMenu::forSite($site)->where('locale', $locale)->with([
            'items' => fn ($query) => $query->whereNull('parent_id')->orderBy('sort'),
            'items.children' => fn ($query) => $query->orderBy('sort'),
            'items.children.children',
        ])->get();

        return response()->json([
            'data' => $menus->map(fn (NavigationMenu $menu) => [
                'id' => $menu->id,
                'key' => $menu->key,
                'name' => $menu->name,
                'locale' => $menu->locale,
                'items' => $menu->items->map(fn (NavigationItem $item) => $this->item($item))->values()->all(),
            ])->values()->all(),
            'pages' => Page::forSite($site)->with('translations')->orderBy('sort_order')->get()->map(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->translations->firstWhere('locale', $locale)?->title ?? $page->translations->first()?->title,
                'slug' => $page->translations->firstWhere('locale', $locale)?->slug ?? $page->translations->first()?->slug,
                'status' => $page->status,
            ])->all(),
        ]);
    }

    public function update(Request $request, NavigationMenu $menu): JsonResponse
    {
        abort_unless($menu->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('navigation.manage', $menu->site_id), 403);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'max:60'],
            'items.*.label' => ['required'],
            'items.*.url' => ['nullable', 'string', 'max:1000'],
            'items.*.page_id' => ['nullable', 'integer'],
            'items.*.target' => ['nullable', 'in:_self,_blank'],
            'items.*.is_visible' => ['nullable', 'boolean'],
            'items.*.children' => ['nullable', 'array', 'max:30'],
        ]);

        DB::transaction(function () use ($menu, $data) {
            $menu->update(['name' => $data['name'] ?? $menu->name]);
            $menu->items()->delete();
            $this->createItems($menu, $data['items'], null);
        });

        return response()->json(['message' => 'Navigation saved.']);
    }

    public function storeMenu(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('navigation.manage', $site->id), 403);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'locale' => ['required', 'in:th,en,zh-CN'],
        ]);

        $menu = NavigationMenu::updateOrCreate(
            ['site_id' => $site->id, 'key' => $data['key'], 'locale' => $data['locale']],
            ['name' => $data['name']]
        );

        return response()->json(['data' => ['id' => $menu->id], 'message' => 'Menu created.'], 201);
    }

    protected function createItems(NavigationMenu $menu, array $items, ?int $parentId): void
    {
        foreach (array_values($items) as $index => $item) {
            $label = $item['label'];
            if (is_string($label)) {
                $label = ['th' => $label, 'en' => $label, 'zh-CN' => $label];
            }

            $created = NavigationItem::create([
                'navigation_menu_id' => $menu->id,
                'parent_id' => $parentId,
                'label' => $label,
                'url' => $item['url'] ?? null,
                'page_id' => $item['page_id'] ?? null,
                'target' => $item['target'] ?? '_self',
                'sort' => $index,
                'is_visible' => $item['is_visible'] ?? true,
            ]);

            if (! empty($item['children'])) {
                $this->createItems($menu, $item['children'], $created->id);
            }
        }
    }

    protected function item(NavigationItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'page_id' => $item->page_id,
            'target' => $item->target,
            'sort' => $item->sort,
            'is_visible' => $item->is_visible,
            'children' => $item->children->map(fn (NavigationItem $child) => $this->item($child))->values()->all(),
        ];
    }
}
