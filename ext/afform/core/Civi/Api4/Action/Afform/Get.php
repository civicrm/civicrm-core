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
      $layout = $scanner->getLayout($name);
      if ($layout !== NULL) {
        // FIXME check for validity?
        $record['layout'] = $this->convertHtmlToOutput($layout);
      }
      $values[] = $record;
    }

    return $values;
  }

}
