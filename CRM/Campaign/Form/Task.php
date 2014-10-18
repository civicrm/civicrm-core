<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for relationship
 *
 */
class CRM_Campaign_Form_Task extends CRM_Core_Form {

  /**
   * The additional clause that we restrict the search.
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * the task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The array that holds all the contact ids
   *
   * @var array
   */
  public $_contactIds;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * The array that holds all the voter ids
   *
   * @var array
   */
  protected $_voterIds;

  /**
   * build all the data structures needed to build the form
   *
   * @param
   *
   * @return void
   * @access public
   */ function preProcess() {
    $values = $this->controller->exportValues('Search');

    $this->_task   = $values['task'];
    $campaignTasks = CRM_Campaign_Task::tasks();
    $taskName      = CRM_Utils_Array::value($this->_task, $campaignTasks);
    $this->assign('taskName', $taskName);

    $ids = array();
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $qfKey    = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      $cacheKey = "civicrm search {$qfKey}";
      $allCids  = CRM_Core_BAO_PrevNextCache::getSelection($cacheKey, "getall");
      $ids = array_keys($allCids[$cacheKey]);
      $this->assign('totalSelectedVoters', count($ids));
    }

    if (!empty($ids)) {
      $this->_componentClause = 'contact_a.id IN ( ' . implode(',', $ids) . ' ) ';
      $this->assign('totalSelectedVoters', count($ids));
    }
    $this->_voterIds = $this->_contactIds = $this->_componentIds = $ids;

    $this->assign('totalSelectedContacts', count($this->_contactIds));

    //set the context for redirection for any task actions
    $session   = CRM_Core_Session::singleton();
    $qfKey     = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= '&qfKey=' . $qfKey;
    }
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/survey/search', $urlParams));
  }

  /**
   * Given the voter id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = $this->_voterIds;
  }

  /**
   * simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title title of the main button
   * @param string $nextType
   * @param string $backType
   * @param bool $submitOnce
   *
   * @internal param string $type button type for the form after processing
   *
   * @return void
   * @access public
   */
  function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons(array(
        array(
          'type' => $nextType,
          'name' => $title,
          'isDefault' => TRUE,
        ),
        array(
          'type' => $backType,
          'name' => ts('Cancel'),
        ),
      )
    );
  }
}

