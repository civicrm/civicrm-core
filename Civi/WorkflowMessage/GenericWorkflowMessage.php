<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\WorkflowMessage;

use Civi\Schema\Traits\MagicGetterSetterTrait;
use Civi\WorkflowMessage\Traits\FinalHelperTrait;
use Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait;

/**
 * Generic base-class for describing the inputs for a workflow email template.
 *
 * @method $this setContactId(int|null $contactId)
 * @method int|null getContactId()
 */
class GenericWorkflowMessage implements WorkflowMessageInterface {

  // Implement getFields(), import(), export(), validate() - All methods based on inspecting class properties (`ReflectionClass`).
  // Define stub methods exportExtraTokenContext(), exportExtraTplParams(), etc.
  use ReflectiveWorkflowTrait;

  // Implement __call() - Public and protected properties are automatically given a default getter/setter. These may be overridden/customized.
  use MagicGetterSetterTrait;

  // Implement assertValid(), renderTemplate(), sendTemplate() - Sugary stub methods that delegate to real APIs.
  use FinalHelperTrait;

  /**
   * WorkflowMessage constructor.
   *
   * @param array $imports
   *   List of values to import.
   *   Ex: ['tplParams' => [...tplValues...], 'tokenContext' => [...tokenData...]]
   *   Ex: ['modelProps' => [...classProperties...]]
   */
  public function __construct(array $imports = []) {
    WorkflowMessage::importAll($this, $imports);
  }

  /**
   * The contact receiving this message.
   *
   * @var int
   * @scope tokenContext
   */
  protected $contactId;

}
