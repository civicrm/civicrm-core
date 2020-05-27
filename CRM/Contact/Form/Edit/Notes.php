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
class CRM_Contact_Form_Edit_Notes {

  /**
   * Build form elements.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->applyFilter('__ALL__', 'trim');
    $form->addField('subject', ['entity' => 'note', 'size' => '60']);
    $form->addField('note', ['entity' => 'note', 'rows' => 3]);
  }

}
