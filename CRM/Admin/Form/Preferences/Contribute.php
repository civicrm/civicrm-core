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
 * Contribution, tax and invoicing settings form
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Generic {

  public function preProcess() {
    parent::preProcess();
    // Every Admin_Form_Generic already comes with a 'default' section but giving it a title adds a header.
    // This will collect all settings with no section declared
    $this->sections = [
      'default' => [
        'title' => ts('General'),
        'icon' => 'fa-cash-register',
      ],
      // Javascript on the Contribute.tpl will hide this section if invoicing is disabled
      'invoice' => [
        'title' => ts('Tax and Invoicing'),
        'icon' => 'fa-file-invoice-dollar',
        'doc_url' => [
          'page' => 'user/contributions/invoicing/',
        ],
        'weight' => 10,
      ],
    ];
  }

}
