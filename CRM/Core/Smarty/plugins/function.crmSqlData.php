<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Evaluate a `*.sqldata.php` file.
 *
 * @param array $params
 *   'file' => Glob expression pointing to 0+ files
 *   'exclude' => Regex of files to exclude
 * @param CRM_Core_Smarty $smarty
 * @return string
 *   The generated SQL
 * @internal
 */
function smarty_function_crmSqlData($params, &$smarty) {
  CRM_Core_Smarty_UserContentPolicy::assertTagAllowed('crmSqlData', is_callable([$smarty, 'getSmarty']) ? $smarty->getSmarty() : $smarty);
  // In theory, there's nothing actually wrong with running in secure more. We just don't need it.
  // If that changes, then be sure to double-check that the file-name sanitization is good.

  $civicrmDir = dirname(__DIR__, 4);
  $files = glob($civicrmDir . DIRECTORY_SEPARATOR . $params['file']);
  if (!empty($params['exclude'])) {
    $files = preg_grep($params['exclude'], $files, PREG_GREP_INVERT);
  }
  foreach ($files as $file) {
    if (!CRM_Utils_File::isChildPath($civicrmDir . DIRECTORY_SEPARATOR . 'sql', $file) || !str_ends_with($file, '.sqldata.php') ||!file_exists($file)) {
      throw new \CRM_Core_Exception("Invalid sqldata file: $file");
    }
  }

  $items = [];
  $classes = [];
  foreach ($files as $file) {
    /** @var CRM_Core_CodeGen_AbstractSqlData $sqlData */
    $sqlData = include $file;
    $items[] = $sqlData;
    $classes[get_class($sqlData)] = 1;
  }

  if (count($items) > 1) {
    if (count($classes) > 1) {
      throw new \CRM_Core_Exception("Can only batch-load sqldata files with same type. (Batch: " . $params['file'] . ')');
    }
    uasort($items, [array_keys($classes)[0], 'compare']);
  }

  $result = '';
  foreach ($items as $item) {
    $result .= $item->toSQL();
  }
  return $result;
}
