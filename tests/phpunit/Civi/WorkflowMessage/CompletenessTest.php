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

/**
 * @group headless
 */
class CompletenessTest extends \CiviUnitTestCase {

  /**
   * If a workflow-message is important enough to register in the default data, then we
   * we should have a corresponding class to document it.
   */
  public function testCompleteness() {
    $specNames = array_keys(WorkflowMessage::getWorkflowSpecs());
    $ovNames = \Civi\Api4\OptionGroup::get(0)
      ->addWhere('name', 'LIKE', 'msg_tpl_%')
      ->addJoin('OptionValue AS wf', 'INNER')
      ->addSelect('wf.name')
      ->execute()
      ->column('wf.name');
    $defaultMsgTplNames = array_unique(\Civi\Api4\MessageTemplate::get(0)
      ->addSelect('workflow_name')
      ->addWhere('workflow_name', 'IS NOT NULL')
      ->addWhere('is_default', '=', 1)
      ->execute()
      ->column('workflow_name'));
    $reservedMsgTplNames = array_unique(\Civi\Api4\MessageTemplate::get(0)
      ->addSelect('workflow_name')
      ->addWhere('workflow_name', 'IS NOT NULL')
      ->addWhere('is_reserved', '=', 1)
      ->execute()
      ->column('workflow_name'));

    $this->assertNotEmpty($specNames);
    $this->assertNotEmpty($ovNames);
    $this->assertNotEmpty($defaultMsgTplNames);
    $this->assertNotEmpty($reservedMsgTplNames);

    $this->assertEquals([],
      array_diff($ovNames, $specNames),
      'All standard workflow names in `civicrm_option_value` should have a corresponding class spec. Some option-values lack corresponding classes.'
    );

    $this->assertEquals([],
      array_diff($defaultMsgTplNames, $specNames),
      'All default workflow names `civicrm_msg_template` should have a corresponding class spec. Some message-templates lack corresponding classes.'
    );

    $this->assertEquals([],
      array_diff($reservedMsgTplNames, $specNames),
      'All standard workflow names `civicrm_msg_template` should have a corresponding class spec. Some message-templates lack corresponding classes.'
    );

    $this->assertEquals(['generic'],
      array_diff($specNames, $defaultMsgTplNames),
      'All standard classes should have a default `civicrm_msg_template` (notwithstanding "generic"). Some classes lack a default message-templates.'
    );

    $this->assertEquals(['generic'],
      array_diff($specNames, $reservedMsgTplNames),
      'All standard classes should have a reserved `civicrm_msg_template` (notwithstanding "generic"). Some classes lack a reserved message-templates.'
    );

    // It is permitted to define a class for a new WF without defining new OVs. Therefore, we do not use this assertion:
    //$this->assertEquals(['generic'],
    //  array_diff($specNames, $ovNames),
    //  'All standard classes should have a `civicrm_option_value` (notwithstanding "generic"). Some classes lack corresponding option-values.'
    //);
  }

  public function testNoNewGroups() {
    $grandfatheredGroups = [
      'msg_tpl_workflow_case',
      'msg_tpl_workflow_contribution',
      'msg_tpl_workflow_event',
      'msg_tpl_workflow_friend',
      'msg_tpl_workflow_membership',
      'msg_tpl_workflow_meta',
      'msg_tpl_workflow_petition',
      'msg_tpl_workflow_pledge',
      'msg_tpl_workflow_uf',
      NULL,
    ];
    $specGroups = array_column(WorkflowMessage::getWorkflowSpecs(), 'group');
    $ovGroups = \Civi\Api4\OptionGroup::get(0)
      ->addWhere('name', 'LIKE', 'msg_tpl_%')
      ->addSelect('name')
      ->execute()
      ->column('name');

    $this->assertEquals([], array_diff($specGroups, $grandfatheredGroups), 'Use of group-name has been deprecated.');
    $this->assertEquals([], array_diff($ovGroups, $grandfatheredGroups), 'Use of group-name has been deprecated.');
  }

}
