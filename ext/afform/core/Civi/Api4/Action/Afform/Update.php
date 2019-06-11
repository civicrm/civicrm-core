<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\BasicUpdateAction;
use Civi\Api4\Utils\AfformFormatTrait;

/**
 * Class Update
 * @package Civi\Api4\Action\Afform
 */
class Update extends BasicUpdateAction {

  use AfformFormatTrait;

  protected function writeRecord($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    if (empty($item['name']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $item['name'])) {
      throw new \API_Exception("Afform.create: name is a mandatory field. It should use alphanumerics and dashes.");
    }
    $name = $item['name'];

    // FIXME validate all field data.
    $updates = _afform_fields_filter($item);

    // Create or update aff.html.
    if (isset($updates['layout'])) {
      $layoutPath = $scanner->createSiteLocalPath($name, 'aff.html');
      \ CRM_Utils_File::createDir(dirname($layoutPath));
      file_put_contents($layoutPath, $this->convertInputToHtml($updates['layout']));
      // FIXME check for writability then success. Report errors.
    }

    // Create or update *.aff.json.
    $orig = \Civi\Api4\Afform::get()
      ->setCheckPermissions($this->getCheckPermissions())
      ->addWhere('name', '=', $name)
      ->execute();

    if (isset($orig[0])) {
      $meta = _afform_fields_filter(array_merge($orig[0], $updates));
    }
    else {
      $meta = $updates;
    }
    unset($meta['layout']);
    unset($meta['name']);
    if (!empty($meta)) {
      $metaPath = $scanner->createSiteLocalPath($name, \CRM_Afform_AfformScanner::METADATA_FILE);
      // printf("[%s] Update meta %s: %s\n", $name, $metaPath, print_R(['updates'=>$updates, 'meta'=>$meta], 1));
      \CRM_Utils_File::createDir(dirname($metaPath));
      file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
      // FIXME check for writability then success. Report errors.
    }

    // We may have changed list of files covered by the cache.
    $scanner->clear();

    if (isset($updates['server_route']) && $updates['server_route'] !== $orig[0]['server_route']) {
      \CRM_Core_Menu::store();
      \CRM_Core_BAO_Navigation::resetNavigation();
    }
    // FIXME if `server_route` changes, then flush the menu cache.
    // FIXME if asset-caching is enabled, then flush the asset cache.

    return $updates;
  }

}
