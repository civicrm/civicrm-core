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
   * AfformValidateEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest) {
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
   * @deprecated
   * @return array
   */
  public function getEntityValues(): array {
    \CRM_Core_Error::deprecatedFunctionWarning("getSubmittedValues");
    return $this->getSubmittedValues();
  }

  /**
   * Get submitted values for all entities on the form
   * @return array
   */
  public function getSubmittedValues() {
    return $this->getApiRequest()->getSubmittedValues();
  }

}
