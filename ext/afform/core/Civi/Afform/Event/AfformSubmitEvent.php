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
   *   List of definitions of the entities.
   *   $entityDefns['spouse'] = ['type' => 'Individual'];
   */
  public $entityDefns;

  /**
   * @var array
   *   List of submitted entities to save.
   *   $entityValues['Contact']['spouse'] = ['first_name' => 'Optimus Prime'];
   */
  public $entityValues;

  /**
   * @var array
   *   List of Submitted Entities and their matching ids
   *   $entityIds['Individual1'] = 1;
   */
  public $entityIds;

  public $entityWeights;

  public $entityMapping;

  /**
   * AfformSubmitEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   * @param array $entityDefns
   * @param array $entityValues
   * @param array $entityIds
   * @param array $entityWeights
   * @param array $entityMapping
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest, $entityDefns, array $entityValues, array $entityIds, array $entityWeights, array $entityMapping) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->entityDefns = $entityDefns;
    $this->entityValues = $entityValues;
    $this->entityIds = $entityIds;
    $this->entityWeights = $entityWeights;
    $this->entityMapping = $entityMapping;
  }

  /**
   * List of entity types which need processing.
   *
   * @return array
   *   Ex: ['Contact', 'Activity']
   */
  public function getTypes() {
    return array_keys($this->entityValues);
  }

}
