<?php

namespace Civi\Api4\Traits;

use Civi\Api4\Generic\ArrayRetrievalTrait;

trait AfformCrudTrait {

  use ArrayRetrievalTrait;

  public function getObjects() {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $converter = new \CRM_Afform_ArrayHtml();

    if (count($this->where) === 1 && $this->where[0][0] === 'name' && $this->where[0][1] == '=') {
      $names = [$this->where[0][2]];
    }
    else {
      $names = array_keys($scanner->findFilePaths());
    }

    $values = [];
    foreach ($names as $name) {
      $record = $scanner->getMeta($name);
      $layout = $scanner->findFilePath($name, 'aff.html');
      if ($layout) {
        // FIXME check for file existence+substance+validity
        $record['layout'] = $converter->convertHtmlToArray(file_get_contents($layout));
      }
      $values[] = $record;
    }

    return $this->processArrayData($values);
  }

  /**
   * Write a record as part of a create/update action.
   *
   * @param array $record
   *   The record to write to the DB.
   * @return array
   *   The record after being written to the DB (e.g. including newly assigned "id").
   * @throws \API_Exception
   */
  protected function writeObject($record) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $converter = new \CRM_Afform_ArrayHtml();

    if (empty($record['name']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $record['name'])) {
      throw new \API_Exception("Afform.create: name is a mandatory field. It should use alphanumerics and dashes.");
    }
    $name = $record['name'];

    // FIXME validate all field data.
    $updates = _afform_fields_filter($record);

    // Create or update aff.html.
    if (isset($updates['layout'])) {
      $layoutPath = $scanner->createSiteLocalPath($name, 'aff.html');
      \ CRM_Utils_File::createDir(dirname($layoutPath));
      file_put_contents($layoutPath, $converter->convertArrayToHtml($updates['layout']));
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

    // FIXME if `server_route` changes, then flush the menu cache.
    // FIXME if asset-caching is enabled, then flush the asset cache.

    return $updates;
  }

}
