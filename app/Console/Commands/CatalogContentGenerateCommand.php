<?php

namespace App\Console\Commands;

use App\Models\CatalogContent;
use App\Models\CatalogItem;
use App\Services\Catalog\Content\ContentGenerator;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

/**
 * Generate baseline editorial content for catalog items. Safe by design:
 * rows that exist (especially human-edited ones) are left untouched unless
 * --refresh is passed explicitly.
 */
class CatalogContentGenerateCommand extends Command
{
    protected $signature = 'catalog:content:generate
        {--category= : pelatihan or jasa (default: both)}
        {--refresh : regenerate even when content already exists}
        {--status= : initial status for freshly created rows (draft|review|published, default: review)}';

    protected $description = 'Baseline editorial content for catalog items (idempotent, never overwrites human edits)';

    public function handle(ContentGenerator $generator): int
    {
        $status = $this->option('status');

        if ($status !== null && ! in_array(
            (string) $status,
            [CatalogContent::STATUS_DRAFT, CatalogContent::STATUS_REVIEW, CatalogContent::STATUS_PUBLISHED],
            true
        )) {
            warning('--status must be draft, review, or published.');

            return self::FAILURE;
        }

        $categories = (array) ($this->option('category') ?: [CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA]);
        $categories = array_values(array_intersect($categories, [CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA]));

        if ($categories === []) {
            warning('No valid category given.');

            return self::FAILURE;
        }

        $counts = ['created' => 0, 'regenerated' => 0, 'skipped-existing' => 0, 'skipped-human-edited' => 0];

        foreach ($categories as $category) {
            $items = CatalogItem::query()->category($category)->orderBy('source_order')->get();

            foreach ($items as $item) {
                $result = $generator->generateFor($item, $status, (bool) $this->option('refresh'));
                $counts[$result['action']]++;
            }

            $this->line("{$category}: processed ".$items->count().' items');
        }

        info("created={$counts['created']}, regenerated={$counts['regenerated']}, skipped-existing={$counts['skipped-existing']}, skipped-human-edited={$counts['skipped-human-edited']}");

        return self::SUCCESS;
    }
}
