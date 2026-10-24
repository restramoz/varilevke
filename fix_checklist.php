<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Convert [ ] to proper HTML checkbox list items
$articles = \App\Models\Article::all();

foreach ($articles as $article) {
    $originalContent = $article->content;
    
    // Pattern: <li>[ ] Text</li> -> <li class="checklist-item"><input type="checkbox" disabled checked><span>Text</span></li>
    // But we need to handle it carefully since the content is already HTML
    
    // First, find all <li>[ ] ...</li> patterns
    $fixedContent = preg_replace_callback(
        '/<li>\s*\[\s*\]\s*(.*?)<\/li>/is',
        function($matches) {
            $text = trim($matches[1]);
            // Remove any trailing whitespace or HTML entities
            $text = trim($text);
            return '<li class="checklist-item"><input type="checkbox" disabled checked aria-hidden="true"><span>' . $text . '</span></li>';
        },
        $originalContent
    );
    
    if ($fixedContent !== $originalContent) {
        echo "Article {$article->id} ({$article->title}): Fixed checklists\n";
        $article->content = $fixedContent;
        $article->save();
    } else {
        echo "Article {$article->id} ({$article->title}): No checklist changes\n";
    }
}

echo "Done.\n";