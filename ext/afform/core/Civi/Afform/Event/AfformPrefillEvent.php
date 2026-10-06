<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\AbstractProcessor;
use Civi\Api4\Utils\CoreUtil;

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

  /**
   * Sets the database ID for the entity instance at the specified index,
   * causing its data to be loaded into the prefill processor.
   *
   * @param int $index
   * @param int|string $entityId
   * @return $this
   */
  public function setEntityId($index, $entityId) {
    $entity = $this->getEntity();
    $idField = !empty($entity['type']) ? CoreUtil::getIdFieldName($entity['type']) : 'id';
    $this->entityIds[$this->entityName][$index][$idField] = $entityId;
    /** @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $this->getApiRequest();
    $apiRequest->loadEntity($entity, [$index => [$idField => $entityId]]);
    return $this;
  }

}
