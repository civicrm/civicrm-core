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
 * This class generates form components for Date Formatting.
 */
class CRM_Admin_Form_Setting_Date extends CRM_Admin_Form_Generic {

  public function preProcess() {
    parent::preProcess();
    // Reload the settings so that the timezone list has the current time
    Civi::settings()->loadValues();
    $this->sections = [
      'calendar' => [
        'title' => ts('Calendar'),
        'icon' => 'fa-calendar',
        'weight' => 10,
      ],
      'input' => [
        'title' => ts('Date Input Fields'),
        'icon' => 'fa-calendar-plus',
        'weight' => 20,
      ],
      'display' => [
        'title' => ts('Date Display'),
        'icon' => 'fa-calendar-check',
        'weight' => 30,
      ],
    ];
  }

}
