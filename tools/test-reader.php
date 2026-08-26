<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$content = GutenbergService::readableContent(
    'https://www.gutenberg.org/cache/epub/1342/pg1342-images.html',
    1342
);
if (!$content) { echo "FAILED\n"; exit(1); }

echo 'title: ' . ($content['title'] ?: '(none)') . "\n";
echo 'chapters: ' . count($content['chapters']) . "\n";
foreach (array_slice($content['chapters'], 0, 8, true) as $i => $c) {
    $len = mb_strlen(strip_tags($c['html']));
    printf("  %2d %-42s %6d chars\n", $i, mb_substr($c['title'] ?? '(untitled)', 0, 42), $len);
}
$total = 0;
foreach ($content['chapters'] as $c) $total += mb_strlen(strip_tags($c['html']));
echo "total text chars: $total\n";

$bad = 0;
foreach ($content['chapters'] as $c) {
    if (preg_match('#<(script|style|iframe|form|link|meta)\b#i', $c['html'])) $bad++;
}
echo "chapters containing banned tags: $bad\n";
