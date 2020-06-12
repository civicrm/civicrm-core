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

/**
 * Class CRM_Export_Controller_Standalone
 */
class CRM_Export_Controller_Standalone extends CRM_Core_Controller {

  /**
   * Yet another hardcoded list :(
   *
   * Very similar to the switch statement in CRM_Export_Form_Select::preProcess
   * TODO: Make this extensible for extension export pages.
   *
   * @var string[]
   */
  public static $components = [
    'Contact' => 'Contact',
    'Contribution' => 'Contribute',
    'Membership' => 'Member',
    'Participant' => 'Event',
    'Pledge' => 'Pledge',
    'Case' => 'Case',
    'Grant' => 'Grant',
    'Activity' => 'Activity',
  ];

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {

    parent::__construct($title, $modal);

    $entity = ucfirst(CRM_Utils_Request::retrieve('entity', 'String', $this, TRUE));
    $this->set('entity', $entity);
    $id = explode(',', CRM_Utils_Request::retrieve('id', 'CommaSeparatedIntegers', $this, TRUE));

    // Check permissions
    $perm = civicrm_api3($entity, 'get', [
      'return' => 'id',
      'options' => ['limit' => 0],
      'check_permissions' => 1,
      'id' => ['IN' => $id],
    ]);

    $this->set('id', implode(',', array_keys($perm['values'])));
    if ($entity == 'Contact') {
      $this->set('cids', implode(',', array_keys($perm['values'])));
    }

    // For the benefit of CRM_Export_Form_Select::getQueryMode()
    $queryComponent = $entity === 'Contact' ? 'CONTACTS' : strtoupper(self::$components[$entity]);
    $this->set('component_mode', constant('CRM_Contact_BAO_Query::MODE_' . $queryComponent));

    $this->_stateMachine = new CRM_Export_StateMachine_Standalone($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $this->addActions();

    $dao = CRM_Core_DAO_AllCoreTables::getFullName($entity);
    CRM_Utils_System::setTitle(ts('Export %1', [1 => $dao::getEntityTitle()]));
  }

  /**
   * Export forms are historically tightly coupled to search forms,so this simulates
   * the output of a search form, with an array of checkboxes for each selected entity.
   *
   * @param string $pageName
   * @return array
   */
  public function exportValues($pageName = NULL) {
    $values = parent::exportValues();
    $values['radio_ts'] = 'ts_sel';
    foreach (explode(',', $this->get('id')) as $id) {
      if ($id) {
        $values[CRM_Core_Form::CB_PREFIX . $id] = 1;
      }
    }
    // Set the "task" selector value to Export
    $className = 'CRM_' . self::$components[$this->get('entity')] . '_Task';
    foreach ($className::tasks() as $taskId => $task) {
      $taskForm = (array) $task['class'];
      if ($taskForm[0] == 'CRM_Export_Form_Select') {
        $values['task'] = $taskId;
      }
    }
    return $values;
  }

}
