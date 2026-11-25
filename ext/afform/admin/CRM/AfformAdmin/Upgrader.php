<?php
use CRM_AfformAdmin_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_AfformAdmin_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Obsolete upgrade step, no longer does anything
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Applying update 0001');
    return TRUE;
  }

  /**
   * Delete the old afAdminFormSubmissionList if a local copy exists,
   * as it will override the civicrm/admin/afform/submissions path and cause errors.
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_0002(): bool {
    $this->ctx->log->info('Applying update 0002');
    \Civi\Api4\Afform::revert(FALSE)
      ->addWhere('name', '=', 'afAdminFormSubmissionList')
      ->execute();
    return TRUE;
  }

}
