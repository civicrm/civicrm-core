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
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_Url extends CRM_Admin_Form_Generic {

  public function preProcess(): void {
    parent::preProcess();
    $this->sections = [
      'location' => [
        'title' => ts('Locations'),
        'icon' => 'fa-sitemap',
        'weight' => 10,
      ],
      'style' => [
        'title' => ts('Styles'),
        'icon' => 'fa-paint-roller',
        'weight' => 20,
      ],
      'security' => [
        'title' => ts('Security'),
        'icon' => 'fa-lock',
        'weight' => 30,
      ],
      'advanced' => [
        'title' => ts('Advanced'),
        'icon' => 'fa-wrench',
        'weight' => 50,
      ],
    ];
  }

}
