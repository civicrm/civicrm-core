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

use Civi\Test\ExampleDataInterface;

/**
 * Helper class for defining WorkflowMessage example-data.
 *
 * By convention, you should name this class relative to the target workflow, as in:
 * - Workflow Name: case_activity
 * - Workflow Class: CRM_Case_WorkflowMessage_CaseActivity
 * - Example Data: CRM_Case_WorkflowMessage_CaseActivity_Foo
 * - Example Name: workflow/case_activity/foo
 */
abstract class WorkflowMessageExample implements ExampleDataInterface {

  /**
   * Name of the workflow for which we are providing example data.
   *
   * @var string
   *   Ex: 'CRM_Case_WorkflowMessage_CaseActivity'
   */
  protected $wfClass;

  /**
   * Name of the workflow for which we are providing example data.
   *
   * @var string|null
   *   Ex: 'case_activity'
   */
  protected $wfName;

  /**
   * Set the workflow name.
   *
   * The workflow name is the value in civicrm_message_template.workflow.
   *
   * @param string $workflowName
   */
  public function setWorkflowName(string $workflowName): void {
    $this->wfName = $workflowName;
  }

  /**
   * Name for this example specifically.
   *
   * @var string
   */
  protected $exName;

  /**
   * Get the example name.
   *
   * @return string
   */
  public function getExampleName(): string {
    return $this->exName;
  }

  /**
   * WorkflowMessageExample constructor.
   */
  public function __construct() {
    if (!preg_match(';^(.*)[_\\\]([a-zA-Z0-9]+)$;', static::class, $m)) {
      throw new \RuntimeException("Failed to parse class: " . static::class);
    }
    $this->wfClass = $m[1];
    $this->wfName = array_search($m[1], \Civi\WorkflowMessage\WorkflowMessage::getWorkflowNameClassMap());
    $this->exName = $m[2];
  }

  /**
   * Get an example, merge/extend it with more data, and return the extended
   * variant.
   *
   * @param array $base
   *   Baseline data to build upon.
   * @param array $overrides
   *   Additional data to recursively add.
   *
   * @return array
   *   The result of merging the original example with the $overrides.
   */
  public function extend($base, $overrides = []) {
    \CRM_Utils_Array::extend($base, $overrides);
    return $base;
  }

  protected function toArray(\Civi\WorkflowMessage\WorkflowMessageInterface $wfMsg) {
    return [
      'workflow' => $this->wfName,
      'modelProps' => $wfMsg->export('modelProps'),
    ];
  }

}
