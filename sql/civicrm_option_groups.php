<?php

$readOptionGroups = function (): array {
  $files = (array) glob(__DIR__ . '/civicrm_option_groups/*.sqldata.php');
  $result = [];
  foreach ($files as $file) {
    $basename = preg_replace('/\.sqldata\.php$/', '', basename($file));
    $result[$basename] = include $file;
  }
  uasort($result, function(CRM_Core_CodeGen_OptionGroup $a, CRM_Core_CodeGen_OptionGroup $b) {
    if ($a->historicalId === $b->historicalId) {
      return strnatcmp($a->metadata['name'], $b->metadata['name']);
    }
    else {
      return strnatcmp($a->historicalId, $b->historicalId);
    }
  });
  return $result;
};

return $readOptionGroups();
