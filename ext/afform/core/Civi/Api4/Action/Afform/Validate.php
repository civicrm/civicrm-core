<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Validate
 * @package Civi\Api4\Action\Afform
 */
class Validate extends Submit {

  protected function processForm() {
    $errors = $this->validate();
    if ($errors->hasErrors()) {
      $this->setResponseItem('errors', $errors->getErrors());
      $this->setResponseItem('is_error', $errors->isError());
    }

    return [$this->_response];
  }

}
