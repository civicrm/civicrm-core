<?php
namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\AbstractProcessor;

/**
 * Dispatched once after all entities of a submission are saved and committed,
 * by Afform.submit and by Afform.process for a stored submission.
 *
 * Event name: 'civi.afform.submitted'
 *
 * Work that must not be undone by a rollback, such as starting a payment, belongs here
 * rather than in 'civi.afform.submit'.
 */
class AfformSubmittedEvent extends AfformBaseEvent {

  private array $entityIds;

  private array $records;

  public function __construct(array $afform, FormDataModel $formDataModel, AbstractProcessor $apiRequest, array $entityIds, array $records) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->entityIds = $entityIds;
    $this->records = $records;
  }

  /**
   * Ids of the saved records of one form entity, keyed by record index.
   *
   * @param string $entityName
   *   E.g. 'Individual1'.
   * @return array
   *   E.g. `[0 => ['id' => 123, 'joins' => [...]]]`
   */
  public function getEntityIds(string $entityName): array {
    return $this->entityIds[$entityName] ?? [];
  }

  /**
   * Records of one form entity as the 'civi.afform.submit' listeners left them, keyed by index.
   *
   * @param string $entityName
   * @return array
   *   E.g. `[0 => ['fields' => [...], 'joins' => [...]]]`
   */
  public function getRecords(string $entityName): array {
    return $this->records[$entityName] ?? [];
  }

}
