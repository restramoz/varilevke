<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for catalog entries. Source-defined values come from the
 * manifest; when an admin edit disagrees with the source, the next
 * `catalog:import` reports the conflict instead of overwriting admin work.
 */
class CatalogItemController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog.index', [
            'items' => CatalogItem::query()
                ->orderBy('category')
                ->orderBy('source_order')
                ->withCount('articles')
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.form', [
            'item' => new CatalogItem,
            'categories' => [CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $nextOrder = (int) (CatalogItem::query()->where('category', $data['category'])->max('source_order') ?: 0) + 1;
        $data['source_order'] = $nextOrder;
        $data['slug'] = Slug::unique($data['name'], $data['category'].'-'.$nextOrder,
            fn (string $candidate): bool => CatalogItem::query()->where('slug', $candidate)->exists());

        CatalogItem::query()->create($data);

        return redirect()->route('admin.catalog.index')->with('status', 'Catalog item created.');
    }

    public function edit(CatalogItem $item): View
    {
        return view('admin.catalog.form', [
            'item' => $item,
            'categories' => [CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA],
        ]);
    }

    public function update(Request $request, CatalogItem $item): RedirectResponse
    {
        $item->forceFill($this->validated($request, $item) + ['slug' => $item->slug])->save();

        return back()->with('status', 'Catalog item updated. Run `php artisan catalog:import` to verify against the source.');
    }

    public function destroy(CatalogItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('admin.catalog.index')->with('status', 'Catalog item deleted. `catalog:import` will report it again on the next run, reported as missing rather than silently re-added.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CatalogItem $existing): array
    {
        $nameRule = ['required', 'string', 'min:2', 'max:255'];

        $nameRule[] = $existing !== null
            ? Rule::unique('catalog_items', 'name')->ignore($existing->id)->where('category', (string) ($request->input('category') ?: $existing->category))
            : Rule::unique('catalog_items', 'name')->where(fn ($q) => $q->where('category', (string) $request->input('category')));

        return $request->validate([
            'name' => $nameRule,
            // Reclassification of manifest items requires human review: admins
            // may flip category deliberately, and the importer will then
            // surface the category mismatch instead of reverting the choice.
            'category' => ['required', Rule::in([CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA])],
            'review_required' => ['sometimes', 'boolean'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
