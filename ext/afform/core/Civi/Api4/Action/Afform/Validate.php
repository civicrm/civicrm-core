<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\Result;

/**
 * Class Validate
 *
 * @package Civi\Api4\Action\Afform
 */
class Validate extends Submit {

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function processForm(Result $result) {
    $this->validate($result);
    if ($result->hasErrors()) {
      $this->setResponseItem('errors', $result->getErrors());
      $this->setResponseItem('is_error', $result->isError());
    }

    return [$this->_response];
  }

}
