<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\AbstractProcessor;
use Civi\Api4\Utils\CoreUtil;

class AfformPrefillEvent extends AfformBaseEvent {
  use AfformEventEntityTrait;

  /**
   * One or more records to be prefilled for this entity.
   * (because of `<af-repeat>` all entities are treated as if they may be multi)
   *
   * @var array
   */
  public $records;

  /**
   * AfformPrefillEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\AbstractProcessor $apiRequest
   * @param string $entityType
   * @param string $entityName
   * @param array $entityIds
   * @param array $records
   */
  public function __construct(array $afform, FormDataModel $formDataModel, AbstractProcessor $apiRequest, string $entityType, string $entityName, array &$entityIds, array &$records = []) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds =& $entityIds;
    $this->records =& $records;
  }

  /**
   * @return array
   */
  public function getRecords(): array {
    return $this->records;
  }

  /**
   * @param array $records
   * @return $this
   */
  public function setRecords(array $records): self {
    $this->records = $records;
    return $this;
  }

  /**
   * Returns the field values for a record index.
   *
   * @param int $index
   * @return array
   */
  public function getValues(int $index = 0): array {
    return $this->records[$index]['fields'] ?? [];
  }

  /**
   * Returns a single field value for a record index.
   *
   * @param string $field
   * @param int $index
   * @return mixed
   */
  public function getValue(string $field, int $index = 0) {
    return $this->records[$index]['fields'][$field] ?? NULL;
  }

  /**
   * Sets a single field value on the entity.
   *
   * If $index is NULL, the field value is set across all existing records
   * for this entity (or on the first record if none exist yet).
   *
   * @param string $field
   * @param mixed $value
   * @param int|null $index
   * @return $this
   */
  public function setValue(string $field, $value, ?int $index = NULL): self {
    if (empty($this->records)) {
      $this->records = [0 => ['fields' => [$field => $value], 'joins' => []]];
      return $this;
    }
    if ($index !== NULL) {
      $this->records[$index]['fields'][$field] = $value;
      $this->records[$index]['joins'] ??= [];
    }
    else {
      foreach ($this->records as $idx => $record) {
        $this->records[$idx]['fields'][$field] = $value;
        $this->records[$idx]['joins'] ??= [];
      }
    }
    return $this;
  }

  /**
   * Sets multiple field values on the entity.
   *
   * If $index is NULL, the field values are merged across all existing records
   * for this entity (or on the first record if none exist yet).
   *
   * @param array $values
   * @param int|null $index
   * @return $this
   */
  public function setValues(array $values, ?int $index = NULL): self {
    if (empty($this->records)) {
      $this->records = [0 => ['fields' => $values, 'joins' => []]];
      return $this;
    }
    if ($index !== NULL) {
      $this->records[$index]['fields'] = array_merge($this->records[$index]['fields'] ?? [], $values);
      $this->records[$index]['joins'] ??= [];
    }
    else {
      foreach ($this->records as $idx => $record) {
        $this->records[$idx]['fields'] = array_merge($this->records[$idx]['fields'] ?? [], $values);
        $this->records[$idx]['joins'] ??= [];
      }
    }
    return $this;
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
