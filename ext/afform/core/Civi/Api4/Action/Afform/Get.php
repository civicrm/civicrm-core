<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Utils\AfformFormatTrait;

/**
 * Class Get
 * @package Civi\Api4\Action\Afform
 */
class Get extends BasicGetAction {

  use AfformFormatTrait;

  public function getRecords() {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    $names = $this->_itemsToGet('name') ?? array_keys($scanner->findFilePaths());

    $values = [];
    foreach ($names as $name) {
      $record = $scanner->getMeta($name);
      $layout = $scanner->findFilePath($name, 'aff.html');
      if ($layout) {
        // FIXME check for file existence+substance+validity
        $html = file_get_contents($layout);
        $record['layout'] = $this->convertHtmlToOutput($html);
      }
      $values[] = $record;
    }

    return $values;
  }

}
