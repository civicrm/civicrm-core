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
 * This class summarizes the import results.
 *
 * TODO: CRM-11254 - if preProcess and postProcess functions can be reconciled between the 5 child classes,
 * those classes can be removed entirely and this class will not need to be abstract
 */
abstract class CRM_Import_Form_Summary extends CRM_Core_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
    ));
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Summary');
  }

}
