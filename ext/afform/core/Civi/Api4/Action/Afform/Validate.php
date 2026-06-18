<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Validate
 * @package Civi\Api4\Action\Afform
 */
class Validate extends Submit {

  protected function processForm() {
    $errors = $this->validate();
    if ($errors) {
      $this->setResponseItem('errors', $errors);
      $this->setResponseItem('is_error', TRUE);
    }

    return [$this->_response];
  }

}
