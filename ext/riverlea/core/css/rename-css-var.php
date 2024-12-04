<?php
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

// Put old var name then some spaces, then new var name.
// Run with: php fix.php from within the css/ dir.
$names = explode("\n", <<<TXT
--crm-alert             --crm-c-alert
--crm-alert-text        --crm-c-alert-text
--crm-amber             --crm-c-amber
--crm-background        --crm-c-background
--crm-background2       --crm-c-background2
--crm-background3       --crm-c-background3
--crm-background4       --crm-c-background4
--crm-background5       --crm-c-background5
--crm-blue              --crm-c-blue
--crm-blue-dark         --crm-c-blue-dark
--crm-blue-darker       --crm-c-blue-darker
--crm-blue-light        --crm-c-blue-light
--crm-dark-teal         --crm-c-dark-teal
--crm-dark-text         --crm-c-dark-text
--crm-darkest           --crm-c-darkest
--crm-divider           --crm-c-divider
--crm-drag-background   --crm-c-drag-background
--crm-focus             --crm-c-focus
--crm-gray-025          --crm-c-gray-025
--crm-gray-050          --crm-c-gray-050
--crm-gray-100          --crm-c-gray-100
--crm-gray-200          --crm-c-gray-200
--crm-gray-300          --crm-c-gray-300
--crm-gray-400          --crm-c-gray-400
--crm-gray-500          --crm-c-gray-500
--crm-gray-600          --crm-c-gray-600
--crm-gray-700          --crm-c-gray-700
--crm-gray-800          --crm-c-gray-800
--crm-gray-900          --crm-c-gray-900
--crm-green             --crm-c-green
--crm-green-dark        --crm-c-green-dark
--crm-green-light       --crm-c-green-light
--crm-inactive          --crm-c-inactive
--crm-info              --crm-c-info
--crm-info-text         --crm-c-info-text
--crm-light-text        --crm-c-light-text
--crm-link              --crm-c-link
--crm-link-hover        --crm-c-link-hover
--crm-page-background   --crm-c-page-background
--crm-primary           --crm-c-primary
--crm-primary-hover     --crm-c-primary-hover
--crm-primary-text      --crm-c-primary-text
--crm-purple            --crm-c-purple
--crm-purple-dark       --crm-c-purple-dark
--crm-red               --crm-c-red
--crm-red-dark          --crm-c-red-dark
--crm-red-light         --crm-c-red-light
--crm-secondary         --crm-c-secondary
--crm-secondary-hover   --crm-c-secondary-hover
--crm-secondary-text    --crm-c-secondary-text
--crm-success           --crm-c-success
--crm-success-text      --crm-c-success-text
--crm-teal              --crm-c-teal
--crm-text              --crm-c-text
--crm-warning           --crm-c-warning
--crm-warning-text      --crm-c-warning-text
--crm-yellow            --crm-c-yellow
--crm-yellow-less-light --crm-c-yellow-less-light
--crm-yellow-light      --crm-c-yellow-light
--crm-amber-light       --crm-c-amber-light
TXT);
$map = [];
foreach ($names as $pair) {
  [$old, $new] = preg_split('/\s+/', $pair);
  $map[$old] = $new;
}
$di = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$it = new RecursiveIteratorIterator($di);
foreach ($it as $filepath) {
  if (pathinfo($filepath, PATHINFO_EXTENSION) == "css") {
    $orig = $css = file_get_contents($filepath);
    foreach ($map as $old => $new) {
      $css = preg_replace("/$old(?!-)/", $new, $css);
    }
    if ($orig !== $css) {
      echo "Updated $filepath\n";
      file_put_contents($filepath, $css);
    }
  }
}
