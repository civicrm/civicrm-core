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
class CRM_Import_Page_Imports extends CRM_Core_Page {

  public function run() {
    $imports = [
      'contact_import' => [
        'label' => ts('Import Contacts'),
        'name' => 'contact_import',
        'url' => CRM_Utils_System::url('civicrm/import/contact', 'reset=1', FALSE, FALSE, TRUE, FALSE, TRUE),
        'no_ts_label' => 'Import Contacts',
      ],
      'multi_record_custom_field_import' => [
        'label' => ts('Import Multi-value Custom Data'),
        'name' => 'multi_record_custom_field_import',
        'url' => CRM_Utils_System::url('civicrm/import/custom', 'reset=1', FALSE, FALSE, TRUE, FALSE, TRUE),
        'no_ts_label' => 'Import Multi-value Custom Data',
      ],
      'activity_import' => [
        'label' => ts('Import Activities'),
        'name' => 'activity_import',
        'url' => CRM_Utils_System::url('civicrm/import/activity', 'reset=1', FALSE, FALSE, TRUE, FALSE, TRUE),
        'no_ts_label' => 'Import Activities',
      ],
    ];
    $event = Civi\Core\Event\GenericHookEvent::create(['imports' => &$imports]);
    Civi::dispatcher()->dispatch('civi.imports', $event);
    $this->assign('imports', $imports);
    parent::run();
    CRM_Utils_System::setTitle('Import Options');
  }

}
