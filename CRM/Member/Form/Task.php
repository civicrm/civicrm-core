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

use Civi\Api4\Membership;

/**
 * Class for member form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Member_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the member ids.
   *
   * @var array
   */
  protected $_memberIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   * @throws \CRM_Core_Exception
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
    $form->_memberIds = [];

    $values = $form->getSearchFormValues();

    $form->_task = $values['task'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    if (!array_key_exists($form->_task, $tasks)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $ids = $form->getSelectedIDs($values);

    if (!$ids) {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_MEMBER
      );
      $query->_distinctComponentClause = ' civicrm_membership.id';
      $query->_groupByComponentClause = ' GROUP BY civicrm_membership.id ';
      $result = $query->searchQuery(0, 0, $sortOrder);

      while ($result->fetch()) {
        $ids[] = $result->membership_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_membership.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedMembers', count($ids));
    }

    $form->_memberIds = $form->_componentIds = $ids;
    $form->setNextUrl('member');
  }

  /**
   * Given the membership id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_memberIds,
      'civicrm_membership'
    );
  }

  /**
   * @return array
   */
  protected function getIDS() {
    return $this->_memberIds;
  }

  /**
   * Get the rows form the search, keyed to make the token processor happy.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRows(): array {
    if (empty($this->rows)) {
      // checkPermissions set to false - in case form is bypassing in some way.
      $memberships = Membership::get(FALSE)
        ->addWhere('id', 'IN', $this->getIDs())
        ->setSelect(['id', 'contact_id'])->execute();
      foreach ($memberships as $membership) {
        $this->rows[] = [
          'contact_id' => $membership['contact_id'],
          'membership_id' => $membership['id'],
          'schema' => [
            'contactId' => $membership['contact_id'],
            'membershipId' => $membership['id'],
          ],
        ];
      }
    }
    return $this->rows;
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  public function getTokenSchema(): array {
    return ['membershipId', 'contactId'];
  }

}
