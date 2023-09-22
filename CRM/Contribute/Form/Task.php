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
 * Class for contribute form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Contribute_Form_Task extends CRM_Core_Form_Task {

  use CRM_Contribute_Form_Task_TaskTrait;

  /**
   * The array that holds all the contribution ids.
   *
   * @var array
   */
  protected $_contributionIds;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param \CRM_Contribute_Form_Task $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommon(&$form): void {
    $form->_contributionIds = [];

    $values = $form->getSearchFormValues();

    $form->_task = $values['task'] ?? NULL;

    $ids = $form->getIDs();
    $form->_componentClause = $form->getComponentClause();
    $form->assign('totalSelectedContributions', count($ids));
    $form->_contributionIds = $form->_componentIds = $ids;
    $form->set('contributionIds', $form->_contributionIds);
    $form->setNextUrl('contribute');
  }

  /**
   * Sets contribution Ids for unit test.
   *
   * @param array $contributionIds
   */
  public function setContributionIds(array $contributionIds): void {
    $this->ids = $contributionIds;
  }

  /**
   * Given the contribution id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs(): void {
    if (!$this->isQueryIncludesSoftCredits()) {
      $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent(
        $this->_contributionIds,
        'civicrm_contribution'
      );
    }
  }

  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
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

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  public function getTokenSchema(): array {
    return ['contributionId', 'contactId'];
  }

}
