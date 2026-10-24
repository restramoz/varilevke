<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$article = \App\Models\Article::find(7);
$fixedContent = str_replace("â†'", "→", $article->content);
$fixedContent = str_replace("&rarr;", "→", $fixedContent);

if ($fixedContent !== $article->content) {
    echo "Content changed, updating...\n";
    $article->content = $fixedContent;
    $article->save();
    echo "Saved.\n";
} else {
    echo "No changes needed.\n";
}

// Also fix article 10
$article10 = \App\Models\Article::find(10);
$fixedContent10 = str_replace("â†'", "→", $article10->content);
$fixedContent10 = str_replace("&rarr;", "→", $fixedContent10);

if ($fixedContent10 !== $article10->content) {
    echo "Article 10 content changed, updating...\n";
    $article10->content = $fixedContent10;
    $article10->save();
    echo "Saved.\n";
} else {
    echo "Article 10 no changes needed.\n";
}