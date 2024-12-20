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

use Civi\Api4\Grant;

/**
 * Class for grant form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Grant_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the grant ids.
   *
   * @var array
   */
  protected $_grantIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
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
    $form->_grantIds = [];

    $values = $form->getSearchFormValues();

    $form->_task = $values['task'] ?? NULL;

    $ids = $form->getSelectedIDs($values);

    // This gets IDs if the action was initiated from SearchKit.
    if (!$ids) {
      $idString = $form->controller->get('id');
      $ids = $idString ? explode(',', $idString) : NULL;
    }

    // We're in a normal search, "All X records" is selected.
    if (!$ids) {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_GRANT
      );
      $query->_distinctComponentClause = ' civicrm_grant.id';
      $query->_groupByComponentClause = ' GROUP BY civicrm_grant.id ';
      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->grant_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_grant.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedGrants', count($ids));
    }

    $form->_grantIds = $form->_componentIds = $ids;

    $form->setNextUrl('grant');
  }

  /**
   * Given the grant id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_grantIds,
      'civicrm_grant'
    );
  }

  /**
   * Simple shell that derived classes can call to add buttons to.
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   * @param string $backType
   *
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
   * Get the rows form the search, keyed to make the token processor happy.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRows(): array {
    if (empty($this->rows)) {
      // checkPermissions set to false - in case form is bypassing in some way.
      $grants = Grant::get(FALSE)
        ->addWhere('id', 'IN', $this->_grantIds)
        ->setSelect(['id', 'contact_id'])->execute();
      foreach ($grants as $grant) {
        $this->rows[] = [
          'contact_id' => $grant['contact_id'],
          'grant_id' => $grant['id'],
          'schema' => [
            'contactId' => $grant['contact_id'],
            'grantId' => $grant['id'],
          ],
        ];
      }
    }
    return $this->rows;
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   */
  public function getTokenSchema(): array {
    return ['grantId', 'contactId'];
  }

}
