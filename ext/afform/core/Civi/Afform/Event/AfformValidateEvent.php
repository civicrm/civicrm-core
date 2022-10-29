<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;

class AfformValidateEvent extends AfformBaseEvent {

  /**
   * @var array
   */
  private $errors = [];

  /**
   * @var array
   */
  private $entityValues;

  /**
   * AfformValidateEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   * @param array $entityValues
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest, array $entityValues) {
    $this->entityValues = $entityValues;
    parent::__construct($afform, $formDataModel, $apiRequest);
  }

  /**
   * @param string $errorMsg
   */
  public function setError(string $errorMsg) {
    $this->errors[] = $errorMsg;
  }

  /**
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * @return array
   */
  public function getEntityValues(): array {
    return $this->entityValues;
  }

}
