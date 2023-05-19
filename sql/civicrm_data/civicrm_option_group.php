<?php

$readOptionGroups = function (): array {
  $files = (array) glob(__DIR__ . '/civicrm_option_group/*.sqldata.php');
  $result = [];
  foreach ($files as $file) {
    $basename = preg_replace('/\.sqldata\.php$/', '', basename($file));
    $result[$basename] = include $file;
  }
  uasort($result, ['CRM_Core_CodeGen_OptionGroup', 'compare']);
  return $result;
};

return $readOptionGroups();
