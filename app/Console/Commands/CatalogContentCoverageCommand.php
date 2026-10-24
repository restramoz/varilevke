<?php

namespace App\Console\Commands;

use App\Models\CatalogContent;
use App\Models\CatalogItem;
use App\Services\Catalog\Content\FieldBlueprint;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;

/**
 * Coverage audit: for every catalog item, which editorial fields exist, what
 * status the content has, and what a human still has to verify. Exportable
 * to docs/SERVICE_CONTENT_COVERAGE.md via --export.
 */
class CatalogContentCoverageCommand extends Command
{
    protected $signature = 'catalog:content:coverage
        {--export : write docs/SERVICE_CONTENT_COVERAGE.md instead of printing summary only}';

    protected $description = 'Audit editorial content coverage across all 272 catalog items';

    public function handle(): int
    {
        $items = CatalogItem::query()
            ->orderBy('category')
            ->orderBy('source_order')
            ->with('content')
            ->get();

        $rows = [];
        $statusCounts = [CatalogContent::STATUS_DRAFT => 0, CatalogContent::STATUS_REVIEW => 0, CatalogContent::STATUS_PUBLISHED => 0, 'without-content' => 0];
        $substantive = 0;

        foreach ($items as $item) {
            $blueprint = FieldBlueprint::definitions($item->category);
            $content = $item->content;

            if ($content === null) {
                $statusCounts['without-content']++;
                $rows[] = [$item->category, $item->source_order, $item->name, '-', 'TANPA KONTEN', implode(', ', array_keys($blueprint))];

                continue;
            }

            $statusCounts[$content->status] = ($statusCounts[$content->status] ?? 0) + 1;

            $missing = [];
            $optionalMissing = [];
            foreach ($blueprint as $key => $def) {
                $present = $key === 'faqs'
                    ? count($content->faqs()) >= 2
                    : ($def['type'] === FieldBlueprint::TYPE_LIST
                        ? count($content->list($key)) >= 3
                        : trim($content->text($key)) !== '');

                if ($present === false) {
                    if (($def['required'] ?? false) === true) {
                        $missing[] = $key;
                    } else {
                        $optionalMissing[] = $key;
                    }
                }
            }

            if ($missing === []) {
                $substantive++;
            }

            $edited = $content->edited_at !== null ? ' (diedit)' : '';
            $missingText = implode(', ', $missing)
                .(! empty($optionalMissing) ? ' [opsional: '.implode(', ', $optionalMissing).']' : '');

            $rows[] = [$item->category, $item->source_order, $item->name, $content->status.$edited, $missing === [] ? 'LENGKAP' : 'PERLU LENGKAPIAN', ltrim($missingText) !== '' ? ltrim($missingText) : '-'];
        }

        $total = $items->count();

        if ((bool) $this->option('export')) {
            $this->export($total, $statusCounts, $substantive, $rows);
            info("Exported docs/SERVICE_CONTENT_COVERAGE.md ({$total} items)");
        } else {
            info("total={$total}, substantive={$substantive}, statuses=".json_encode($statusCounts));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{int|string}>  $rows
     * @param  array<string, int>  $statusCounts
     */
    private function export(int $total, array $statusCounts, int $substantive, array $rows): void
    {
        $lines = [
            '# Laporan Cakupan Konten Katalog (272 item)',
            '',
            'Dihasilkan otomatis oleh `php artisan catalog:content:coverage --export`.',
            '',
            '## Ringkasan',
            '',
            '- Total item katalog: **'.$total.'**',
            '- Konten metodik (semua field wajib terisi): **'.$substantive.'**',
            '- Tanpa konten: **'.($statusCounts['without-content'] ?? 0).'**',
            '- Status konten: draft **'.($statusCounts['draft'] ?? 0).'**, review **'.($statusCounts['review'] ?? 0).'**, published **'.($statusCounts['published'] ?? 0).'**',
            '',
            'Keterangan status: `draft` = berasal dari generator/AI belum ditinjau; `review` = perlu konfirmasi manusia; `published` = sudah disetujui manusia lewat admin.',
            '',
            'Field "format" dan "assessment" kosong untuk semua item: dokumen sumber tidak',
            'menyatakan detail itu, jadi komponen hanya menampilannya bila admin mengisi.',
            '',
            '## Tabel detail (272 item)',
            '',
            '| Kategori | # | Nama resmi | Status | Kelengkapan | Field kosong/perlu cek |',
            '| --- | --- | --- | --- | --- | --- |',
        ];

        foreach ($rows as $row) {
            $escaped = array_map(fn ($value): string => str_replace('|', '\\|', (string) $value), $row);
            $lines[] = '| '.implode(' | ', $escaped).' |';
        }

        file_put_contents(base_path('docs/SERVICE_CONTENT_COVERAGE.md'), implode("\n", $lines)."\n");
    }
}
