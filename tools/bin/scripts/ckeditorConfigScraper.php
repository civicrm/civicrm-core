<?php
/**
 * Scrape all config options from the CKEditor documentation site.
 */
$content = file_get_contents('http://docs.ckeditor.com/?print=/api/CKEDITOR.config');
$matches = $output = [];
preg_match_all("#name expandable'>([^<]+)</a>\s?:\s?(.*)<span.*'short'>([\s\S]*?)</div>#", $content, $matches);
foreach ($matches[1] as $i => $name) {
  $output[] = [
    'id' => $name,
    'type' => strip_tags($matches[2][$i]),
    'description' => str_replace(["\n", '. ...'], [' ', '.'], $matches[3][$i]),
  ];
}
if ($output) {
  $location = str_replace('tools/bin/scripts', '', __DIR__);
  file_put_contents($location . '/js/wysiwyg/ck-options.json', json_encode($output, JSON_PRETTY_PRINT));
}
print "\nTotal: " . count($output) . "\n";
