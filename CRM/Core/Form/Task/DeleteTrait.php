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
 * Common functionality for deleting entities in task forms.
 */
trait CRM_Core_Form_Task_DeleteTrait {

  /**
   * Are we operating in "single mode", i.e. deleting one specific entity?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess(): void {
    $permissionModule = $this->getPermissionModule();
    if ($permissionModule && !CRM_Core_Permission::checkActionPermission($permissionModule, CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $title = $this->getDeleteTitle();
    CRM_Utils_System::setTitle($title);
    $this->addDefaultButtons($title, 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess(): void {
    $deleted = $failed = 0;
    foreach ($this->getIDs() as $id) {
      if ($this->deleteRecord($id)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      CRM_Core_Session::setStatus($this->getSuccessMessage($deleted), ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

  /**
   * Get permission module name (e.g. 'CiviMember', 'CiviPledge', 'CiviCase').
   * Return NULL if no permission check is required in preProcess.
   */
  protected function getPermissionModule(): ?string {
    return NULL;
  }

  /**
   * Get the title for the delete form / button.
   */
  protected function getDeleteTitle(): string {
    $titlePlural = Civi::entity($this->getDefaultEntity())->getMeta('title_plural');
    return ts('Delete %1', [1 => $titlePlural]);
  }

  /**
   * Get success status message.
   */
  protected function getSuccessMessage(int $deleted): string {
    $entity = Civi::entity($this->getDefaultEntity());
    $title = strtolower($entity->getMeta('title'));
    $titlePlural = strtolower($entity->getMeta('title_plural'));
    return ts('%count %1 deleted.', [
      'count' => $deleted,
      'plural' => '%count %2 deleted.',
      1 => $title,
      2 => $titlePlural,
    ]);
  }

  /**
   * Get default entity name for the form.
   * @return string
   */
  abstract public function getDefaultEntity();

  /**
   * Delete a single record by ID.
   *
   * @param int|array $id
   * @return bool
   */
  abstract protected function deleteRecord($id): bool;

  /**
   * Get IDs of records to delete.
   *
   * @return array
   */
  abstract protected function getIDs(): array;

}
