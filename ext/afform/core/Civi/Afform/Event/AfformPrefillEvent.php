<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\AbstractProcessor;

class AfformPrefillEvent extends AfformBaseEvent {
  use AfformEventEntityTrait;

  /**
   * AfformPrefillEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\AbstractProcessor $apiRequest
   * @param string $entityType
   * @param string $entityName
   * @param array $entityIds
   */
  public function __construct(array $afform, FormDataModel $formDataModel, AbstractProcessor $apiRequest, string $entityType, string $entityName, array &$entityIds) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds =& $entityIds;
  }

}
