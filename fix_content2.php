<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// The malformed sequence is UTF-8: c3 a2 e2 80 a0 e2 80 99
// Which represents: â (c3a2) † (e280a0) ' (e28099)
// This is a double-encoded → (e2 86 92)
$malformed = "\xc3\xa2\xe2\x80\xa0\xe2\x80\x99";
$correct = "\xe2\x86\x92"; // →

echo "Malformed: " . bin2hex($malformed) . "\n";
echo "Correct: " . bin2hex($correct) . "\n";

$article = \App\Models\Article::find(7);
$originalContent = $article->content;
$fixedContent = str_replace($malformed, $correct, $originalContent);

if ($fixedContent !== $originalContent) {
    echo "Article 7: Content changed, updating...\n";
    $article->content = $fixedContent;
    $article->save();
    echo "Saved.\n";
} else {
    echo "Article 7: No changes needed.\n";
}

// Also fix article 10
$article10 = \App\Models\Article::find(10);
$originalContent10 = $article10->content;
$fixedContent10 = str_replace($malformed, $correct, $originalContent10);

if ($fixedContent10 !== $originalContent10) {
    echo "Article 10: Content changed, updating...\n";
    $article10->content = $fixedContent10;
    $article10->save();
    echo "Saved.\n";
} else {
    echo "Article 10: No changes needed.\n";
}