<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Services\Catalog\CatalogImporter;
use App\Services\Catalog\CatalogImportException;
use App\Services\Catalog\CatalogManifest;
use App\Services\Catalog\CatalogManifestException;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class CatalogImportCommand extends Command
{
    protected $signature = 'catalog:import
        {--manifest= : Path to catalog-manifest.json (defaults to config/catalog.php)}';

    protected $description = 'Import the pelatihan/jasa catalog from the source manifest (idempotent, non-destructive)';

    public function handle(): int
    {
        $path = (string) ($this->option('manifest') ?: config('catalog.manifest_path'));

        try {
            $manifest = CatalogManifest::fromFile($path);
        } catch (CatalogManifestException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $counts = $manifest->counts();
        $this->info("Manifest loaded: pelatihan={$counts['pelatihan']}, jasa={$counts['jasa']}, total={$counts['total']}");

        try {
            $report = (new CatalogImporter($manifest))->import();
        } catch (CatalogImportException $e) {
            error($e->getMessage());

            foreach ($e->conflicts as $conflict) {
                $this->line(' - '.$conflict);
            }

            error('No database changes were committed.');

            return self::FAILURE;
        } catch (\Throwable $e) {
            error($e->getMessage());
            error('No database changes were committed.');

            return self::FAILURE;
        }

        info($report->summary());

        $latest = CatalogItem::query()->latest('source_order')->first();
        $counts = $latest === null ? ['pelatihan' => 0, 'jasa' => 0, 'total' => 0] : null;

        $this->table(
            ['category', 'rows'],
            collect([CatalogItem::CATEGORY_PELATIHAN, CatalogItem::CATEGORY_JASA])
                ->map(fn (string $c): array => [$c, CatalogItem::query()->category($c)->count()])
        );

        return self::SUCCESS;
    }
}
