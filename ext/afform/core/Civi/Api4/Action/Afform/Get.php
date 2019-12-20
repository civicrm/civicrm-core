<?php

namespace Civi\Api4\Action\Afform;

/**
 * @inheritDoc
 * @package Civi\Api4\Action\Afform
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  use \Civi\Api4\Utils\AfformFormatTrait;

  public function getRecords() {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    $names = $this->_itemsToGet('name') ?? array_keys($scanner->findFilePaths());

    $values = [];
    foreach ($names as $name) {
      $record = $scanner->getMeta($name);
      if ($record && ($this->_isFieldSelected('has_local') || $this->_isFieldSelected('has_base'))) {
        $record = array_merge($record, $scanner->getComputedFields($name));
      }
      $layout = $this->_isFieldSelected('layout') ? $scanner->getLayout($name) : NULL;
      if ($layout !== NULL) {
        // FIXME check for validity?
        $record['layout'] = $this->convertHtmlToOutput($layout);
      }
      $values[] = $record;
    }

    return $values;
  }

}
