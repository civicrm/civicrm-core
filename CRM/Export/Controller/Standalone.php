<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Export_Controller_Standalone
 */
class CRM_Export_Controller_Standalone extends CRM_Core_Controller {

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
    $perm = civicrm_api3($entity, 'get', array(
      'return' => 'id',
      'options' => array('limit' => 0),
      'check_permissions' => 1,
      'id' => array('IN' => $id),
    ));

    $this->set('id', implode(',', array_keys($perm['values'])));
    if ($entity == 'Contact') {
      $this->set('cids', implode(',', array_keys($perm['values'])));
    }

    $this->_stateMachine = new CRM_Export_StateMachine_Standalone($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $this->addActions();
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
    $className = 'CRM_' . $this->get('entity') . '_Task';
    foreach ($className::tasks() as $taskId => $task) {
      $taskForm = (array) $task['class'];
      if ($taskForm[0] == 'CRM_Export_Form_Select') {
        $values['task'] = $taskId;
      }
    }
    return $values;
  }

}
