<?php
namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;

/**
 * Class AfformSubmitEvent
 * @package Civi\Afform\Event
 *
 * Handle submission of an "<af-form>".
 * Listeners ought to take any recognized items from `entityValues`, handle
 * them, and remove them.
 *
 * NOTE: I'm on the fence about whether to expose the arrays or more targeted
 * methods. For the moment, this is only expected to be used internally,
 * so KISS.
 */
class AfformSubmitEvent extends AfformBaseEvent {

  /**
   * @var array
   *   Values to be saved for this entity
   *
   */
  public $values;

  /**
   * @var string
   *   entityType
   */
  public $entityType;

  /**
   * @var string
   *   entityName e.g. Individual1, Activity1,
   */
  public $entityName;

  /**
   * @var array
   *   List of Submitted Entities and their matching ids
   *   $entityIds['Individual1'] = 1;
   */
  public $entityIds;

  /**
   * AfformSubmitEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   * @param array $values
   * @param string $entityType
   * @param string $entityName
   * @param array $entityIds
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest, $values, string $entityType, string $entityName, array $entityIds) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->values = $values;
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds = $entityIds;
  }

}
