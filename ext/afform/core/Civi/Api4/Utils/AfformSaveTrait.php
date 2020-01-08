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
      $prefix = !empty($item['join']) ? "afjoin-{$item['join']}" : !empty($item['block']) ? 'afblock-' . str_replace('*', 'all', $item['block']) : 'afform';
      $item['name'] = _afform_angular_module_name($prefix . '-' . \CRM_Utils_String::munge($item['title'], '-'));
      $suffix = '';
      while (
        file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, \CRM_Afform_AfformScanner::METADATA_FILE))
        || file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, 'aff.html'))
      ) {
        $suffix++;
      }
      $item['name'] .= $suffix;
      $orig = NULL;
    }
    elseif (!preg_match('/^[a-zA-Z][-_a-zA-Z0-9]*$/', $item['name'])) {
      throw new \API_Exception("Afform.{$this->getActionName()}: name should begin with a letter and only contain alphanumerics underscores and dashes.");
    }
    else {
      // Fetch existing metadata
      $fields = \Civi\Api4\Afform::getfields()->setCheckPermissions(FALSE)->setAction('create')->addSelect('name')->execute()->column('name');
      unset($fields[array_search('layout', $fields)]);
      $orig = \Civi\Api4\Afform::get()->setCheckPermissions(FALSE)->addWhere('name', '=', $item['name'])->setSelect($fields)->execute()->first();
    }

    // FIXME validate all field data.
    $item = _afform_fields_filter($item);

    // Create or update aff.html.
    if (isset($item['layout'])) {
      $layoutPath = $scanner->createSiteLocalPath($item['name'], 'aff.html');
      \CRM_Utils_File::createDir(dirname($layoutPath));
      file_put_contents($layoutPath, $this->convertInputToHtml($item['layout']));
      // FIXME check for writability then success. Report errors.
    }

    $meta = $item + (array) $orig;
    unset($meta['layout'], $meta['name']);
    if (!empty($meta)) {
      $metaPath = $scanner->createSiteLocalPath($item['name'], \CRM_Afform_AfformScanner::METADATA_FILE);
      \CRM_Utils_File::createDir(dirname($metaPath));
      file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
      // FIXME check for writability then success. Report errors.
    }

    // We may have changed list of files covered by the cache.
    _afform_clear();

    $isChanged = function($field) use ($item, $orig) {
      return ($item[$field] ?? NULL) !== ($orig[$field] ?? NULL);
    };
    // Right now, permission-checks are completely on-demand.
    if ($isChanged('server_route') /* || $isChanged('permission') */) {
      \CRM_Core_Menu::store();
      \CRM_Core_BAO_Navigation::resetNavigation();
    }
    // FIXME if asset-caching is enabled, then flush the asset cache.

    $item['module_name'] = _afform_angular_module_name($item['name'], 'camel');
    $item['directive_name'] = _afform_angular_module_name($item['name'], 'dash');
    return $meta + $item;
  }

}
