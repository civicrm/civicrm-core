<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  protected function processForm() {
    return \CRM_Utils_Array::makeNonAssociative($this->_entityValues, 'name', 'values');
  }

}
