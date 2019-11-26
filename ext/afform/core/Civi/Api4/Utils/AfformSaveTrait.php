<?php

namespace Civi\Api4\Utils;

/**
 * Class AfformSaveTrait
 * @package Civi\Api4\Action\Afform
 */
trait AfformSaveTrait {

  use AfformFormatTrait;

  protected function writeRecord($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    // If no name given, create a unique name based on the title
    if (empty($item['name'])) {
      $item['name'] = _afform_angular_module_name(\CRM_Utils_String::munge($item['title'], '-'));
      $suffix = '';
      while (
        file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, \CRM_Afform_AfformScanner::METADATA_FILE))
        || file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, 'aff.html'))
      ) {
        $suffix++;
      }
      $item['name'] .= $suffix;
    }
    elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $item['name'])) {
      throw new \API_Exception("Afform.{$this->getActionName()}: name should use alphanumerics and dashes.");
    }

    // FIXME validate all field data.
    $updates = _afform_fields_filter($item);

    // Create or update aff.html.
    if (isset($updates['layout'])) {
      $layoutPath = $scanner->createSiteLocalPath($item['name'], 'aff.html');
      \CRM_Utils_File::createDir(dirname($layoutPath));
      file_put_contents($layoutPath, $this->convertInputToHtml($updates['layout']));
      // FIXME check for writability then success. Report errors.
    }

    $orig = NULL;
    $meta = $updates;
    unset($meta['layout']);
    unset($meta['name']);
    if (!empty($meta)) {
      $metaPath = $scanner->createSiteLocalPath($item['name'], \CRM_Afform_AfformScanner::METADATA_FILE);
      if (file_exists($metaPath)) {
        $orig = $scanner->getMeta($item['name']);
      }
      \CRM_Utils_File::createDir(dirname($metaPath));
      file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
      // FIXME check for writability then success. Report errors.
    }

    // We may have changed list of files covered by the cache.
    _afform_clear();

    if (($updates['server_route'] ?? NULL) !== ($orig['server_route'] ?? NULL)) {
      \CRM_Core_Menu::store();
      \CRM_Core_BAO_Navigation::resetNavigation();
    }
    // FIXME if asset-caching is enabled, then flush the asset cache.

    return $updates;
  }

}
