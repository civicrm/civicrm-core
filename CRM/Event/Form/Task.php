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

use Civi\Api4\Participant;

/**
 * Class for event form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Event_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the participant ids.
   *
   * @var array
   */
  protected $_participantIds;

  /**
   * Rows to act on.
   *
   * Each row will have a participant ID & a contact ID using
   * the keys the token processor expects.
   *
   * @var array
   */
  protected $rows = [];

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form_Task $form
   */
  public static function preProcessCommon(&$form) {
    $form->_participantIds = [];

    $values = $form->getSearchFormValues();

    $form->_task = $values['task'];
    $tasks = CRM_Event_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
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
        CRM_Contact_BAO_Query::MODE_EVENT
      );
      $query->_distinctComponentClause = "civicrm_participant.id";
      $query->_groupByComponentClause = " GROUP BY civicrm_participant.id ";
      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->participant_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_participant.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedParticipants', count($ids));
    }

    $form->_participantIds = $form->_componentIds = $ids;

    $form->setNextUrl('event');
  }

  /**
   * Get the participant IDs.
   *
   * @return array
   */
  public function getIDs(): array {
    return $this->_participantIds;
  }

  /**
   * Given the participant id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs(): void {
    $this->_contactIds = $this->getContactIDs();
  }

  /**
   * Get the relevant contact IDs.
   *
   * @return array
   */
  protected function getContactIDs(): array {
    if (isset($this->_contactIds)) {
      return $this->_contactIds;
    }
    foreach ($this->getRows() as $row) {
      $this->_contactIds[] = $row['contact_id'];
    }
    return $this->_contactIds;
  }

  /**
   * Simple shell that derived classes can call to add buttons to.
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   * @param string $backType
   * @param bool $submitOnce
   *
   * @return void
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
      $participants = Participant::get(FALSE)
        ->addWhere('id', 'IN', $this->getIDs())
        ->setSelect(['id', 'contact_id'])->execute();
      foreach ($participants as $participant) {
        $this->rows[] = [
          'contact_id' => $participant['contact_id'],
          'participant_id' => $participant['id'],
          'schema' => [
            'contactId' => $participant['contact_id'],
            'participantId' => $participant['id'],
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
    return ['participantId', 'contactId', 'eventId'];
  }

}
