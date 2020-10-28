<?php
namespace Civi\Afform\Event;

use Symfony\Component\EventDispatcher\Event;

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
class AfformSubmitEvent extends Event {

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
   * AfformSubmitEvent constructor.
   * @param $entityDefns
   * @param array $entityValues
   */
  public function __construct($entityDefns, array $entityValues) {
    $this->entityDefns = $entityDefns;
    $this->entityValues = $entityValues;
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
