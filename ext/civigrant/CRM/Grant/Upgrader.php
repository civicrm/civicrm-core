<?php
use CRM_Grant_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Grant_Upgrader extends CRM_Extension_Upgrader_Base {

  public function install() {
    // Ensure option group exists (in case OptionGroup_grant_status.mgd.php hasn't loaded yet)
    \Civi\Api4\OptionGroup::save(FALSE)
      ->addRecord([
        'name' => 'grant_status',
        'title' => E::ts('Grant status'),
      ])
      ->setMatch(['name'])
      ->execute();

    // Create unmanaged option values. They will not be updated by the system ever,
    // but they will be deleted on uninstall because the option group is a managed entity.
    \Civi\Api4\OptionValue::save(FALSE)
      ->setDefaults([
        'option_group_id.name' => 'grant_status',
      ])
      ->setRecords([
        ['value' => 1, 'name' => 'Submitted', 'label' => E::ts('Submitted'), 'is_default' => TRUE],
        ['value' => 2, 'name' => 'Eligible', 'label' => E::ts('Eligible')],
        ['value' => 3, 'name' => 'Ineligible', 'label' => E::ts('Ineligible')],
        ['value' => 4, 'name' => 'Paid', 'label' => E::ts('Paid')],
        ['value' => 5, 'name' => 'Awaiting Information', 'label' => E::ts('Awaiting Information')],
        ['value' => 6, 'name' => 'Withdrawn', 'label' => E::ts('Withdrawn')],
        ['value' => 7, 'name' => 'Approved for Payment', 'label' => E::ts('Approved for Payment')],
      ])
      ->setMatch(['option_group_id', 'name'])
      ->execute();
  }

  public function upgrade_1001(): bool {
    $this->ctx->log->info('Applying Update 1001 - fixing database column for grant_report_received to default to 0 and be required');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_grant SET grant_report_received = 0 WHERE grant_report_received IS NULL");
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_grant CHANGE `grant_report_received` `grant_report_received` tinyint NOT NULL DEFAULT 0 COMMENT 'Yes/No field stating whether grant report was received by donor.'");
    return TRUE;
  }

}
