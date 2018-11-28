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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class for mailing form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Mailing_Form_Task extends CRM_Core_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function preProcessCommon(&$form) {
    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = CRM_Utils_Array::value('task', $values);
    $mailingTasks = CRM_Mailing_Task::tasks();
    $form->assign('taskName', CRM_Utils_Array::value('task', $values));

    // ids are mailing event queue ids
    $ids = array();
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }

      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_MAILING
      );

      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->mailing_recipients_id;
      }
      $form->assign('totalSelectedMailingRecipients', $form->get('rowCount'));
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_mailing_recipients.id IN ( ' . implode(',', $ids) . ' ) ';
    }

    // set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    $fragment = 'search';
    if ($form->_action == CRM_Core_Action::ADVANCED) {
      $fragment .= '/advanced';
    }

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   * @param string $backType
   * @param bool $submitOnce
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
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
