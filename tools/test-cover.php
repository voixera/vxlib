<?php
$svg = file_get_contents('http://127.0.0.1:8011/cover.php?t=Pride+%26+Prejudice&a=Jane+Austen');
$doc = new DOMDocument();
echo 'valid-xml: ', $doc->loadXML($svg) ? 'YES' : 'NO', "\n";
echo 'bytes: ', strlen((string)$svg), "\n";
foreach (libxml_get_errors() as $err) echo '  libxml: ', trim($err->message), "\n";
