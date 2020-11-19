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
 * Class for pledge form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Pledge_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the pledge ids.
   *
   * @var array
   */
  protected $_pledgeIds;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * Common pre-processing.
   *
   * @param CRM_Pledge_Form_Task $form
   */
  public static function preProcessCommon(&$form) {
    $form->_pledgeIds = [];

    $values = $form->controller->exportValues('Search');

    $form->_task = $values['task'];

    $ids = $form->getSelectedIDs($values);

    if (!$ids) {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_PLEDGE
      );
      $query->_distinctComponentClause = ' civicrm_pledge.id';
      $query->_groupByComponentClause = ' GROUP BY civicrm_pledge.id ';

      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->pledge_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_pledge.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedPledges', count($ids));
    }

    $form->_pledgeIds = $form->_componentIds = $ids;
    $form->setNextUrl('pledge');
  }

  /**
   * Given the signer id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_pledgeIds,
      'civicrm_pledge'
    );
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
