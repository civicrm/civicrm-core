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
 * Form helper class for an Demographics object.
 */
class CRM_Contact_Form_Edit_Demographics {

  /**
   * Build the form object elements for Demographics object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   */
  public static function buildQuickForm(&$form) {
    $form->addField('gender_id', ['entity' => 'contact', 'type' => 'Radio', 'allowClear' => TRUE]);

    $form->addField('birth_date', ['entity' => 'contact'], FALSE, FALSE);

    $form->addField('is_deceased', ['entity' => 'contact', 'label' => ts('Contact is Deceased'), 'onclick' => "showDeceasedDate()"]);
    $form->addField('deceased_date', ['entity' => 'contact'], FALSE, FALSE);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {
  }

}
