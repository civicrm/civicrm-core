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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @param \CRM_Core_Form_Task $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommon(&$form) {
    $values = $form->getSearchFormValues();

    $form->_task = $values['task'] ?? NULL;

    // ids are mailing event queue ids
    $ids = $form->getSelectedIDs($values);

    if (!$ids) {
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
    $this->addButtons([
      [
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ],
      [
        'type' => $backType,
        'name' => ts('Cancel'),
      ],
    ]);
  }

}
