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

}
