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

namespace Civi\Api4\Action\WorkflowMessage;

use Civi\Api4\Event\ValidateValuesEvent;
use Civi\WorkflowMessage\WorkflowMessage;

/**
 * Render a message.
 *
 * @method $this setValues(array $rows) Set the list of records to be rendered.
 * @method array getValues()
 * @method $this setMessageTemplate(array|null $fragments) Set of messages to be rendered.
 * @method array|null getMessageTemplate()
 * @method $this setMessageTemplateId(int|null $id) Set of messages to be rendered.
 * @method int|null getMessageTemplateId()
 * @method $this setWorkflow(string $workflow)
 * @method string getWorkflow()
 * @method $this setErrorLevel(string $workflow)
 * @method string getErrorLevel()
 */
class Render extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Abort if the validator finds any issues at this error level.
   *
   * @var string
   * @options error,warning,info
   */
  protected $errorLevel = 'error';

  /**
   * Symbolic name of the workflow step for which we need a message.
   * @var string
   * @required
   */
  protected $workflow;

  /**
   * @var array
   */
  protected $values = [];

  /**
   * Load and render a specific message template (by ID).
   *
   * @var int|null
   */
  protected $messageTemplateId;

  /**
   * Use a draft message template.
   *
   * @var array|null
   *   - `subject`: Message template (eg `Hello {contact.first_name}!`)
   *   - `text`: Message template (eg `Hello {contact.first_name}!`)
   *   - `html`: Message template (eg `<p>Hello {contact.first_name}!</p>`)
   */
  protected $messageTemplate;

  /**
   * @var \Civi\WorkflowMessage\WorkflowMessageInterface
   */
  protected $_model;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->validateValues();
    $r = \CRM_Core_BAO_MessageTemplate::renderTemplate([
      'model' => $this->_model,
      'messageTemplate' => $this->getMessageTemplate(),
      'messageTemplateId' => $this->getMessageTemplateId(),
      'language' => $this->getLanguage(),
    ]);
    $result[] = \CRM_Utils_Array::subset($r, ['subject', 'html', 'text']);
  }

  /**
   * The token-processor supports a range of context parameters. We enforce different security rules for each kind of input.
   *
   * Broadly, this distinguishes between a few values:
   * - Autoloaded data (e.g. 'contactId', 'activityId'). We need to ensure that the specific records are visible and extant.
   * - Inputted data (e.g. 'contact'). We merely ensure that these are type-correct.
   * - Prohibited/extended options, e.g. 'smarty'
   */
  protected function validateValues() {
    $rows = [$this->getValues()];
    $e = new ValidateValuesEvent($this, $rows, new \CRM_Utils_LazyArray(function () use ($rows) {
      return array_map(
        function ($row) {
          return ['old' => NULL, 'new' => $row];
        },
        $rows
      );
    }));
    $this->onValidateValues($e);
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

  protected function onValidateValues(ValidateValuesEvent $e) {
    $errorWeightMap = \CRM_Core_Error_Log::getMap();
    $errorWeight = $errorWeightMap[$this->getErrorLevel()];

    if (count($e->records) !== 1) {
      throw new \CRM_Core_Exception("Expected exactly one record to validate");
    }
    foreach ($e->records as $recordKey => $record) {
      /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $w */
      $w = $this->_model = WorkflowMessage::create($this->getWorkflow(), [
        'modelProps' => $record,
      ]);
      $fields = $w->getFields();

      $unknowns = array_diff(array_keys($record), array_keys($fields));
      foreach ($unknowns as $fieldName) {
        $e->addError($recordKey, $fieldName, 'unknown_field', ts('Unknown field (%1). Templates may only be executed with supported fields.', [
          1 => $fieldName,
        ]));
      }

      // Merge intrinsic validations
      foreach ($w->validate() as $issue) {
        if ($errorWeightMap[$issue['severity']] < $errorWeight) {
          $e->addError($recordKey, $issue['fields'], $issue['name'], $issue['message']);
        }
      }

      // Add checks which don't fit in WFM::validate
      foreach ($fields as $fieldName => $fieldSpec) {
        $fieldValue = $record[$fieldName] ?? NULL;
        if ($fieldSpec->getFkEntity() && !empty($fieldValue)) {
          if (!empty($params['check_permissions']) && !\Civi\Api4\Utils\CoreUtil::checkAccessDelegated($fieldSpec->getFkEntity(), 'get', ['id' => $fieldValue], CRM_Core_Session::getLoggedInContactID() ?: 0)) {
            $e->addError($recordKey, $fieldName, 'nonexistent_id', ts('Referenced record does not exist or is not visible (%1).', [
              1 => $this->getWorkflow() . '::' . $fieldName,
            ]));
          }
        }
      }
    }
  }

  public function fields() {
    return [];
    // We don't currently get the name of the workflow. But if we do...
    //$item = \Civi\WorkflowMessage\WorkflowMessage::create($this->workflow);
    ///** @var \Civi\WorkflowMessage\FieldSpec[] $fields */
    //$fields = $item->getFields();
    //$array = [];
    //foreach ($fields as $name => $field) {
    //  $array[$name] = $field->toArray();
    //}
    //return $array;
  }

}
