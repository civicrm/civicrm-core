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
class CRM_UF_Form_AdvanceSetting extends CRM_UF_Form_Group {

  /**
   * Build the form object for Advanced Settings.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildAdvanceSetting(&$form) {
    $entityFields = [
      'cancel_button_text',
      'submit_button_text',
    ];
    $form->assign('advancedFieldsConverted', $entityFields);

    // should mapping be enabled for this group
    $form->addElement('advcheckbox', 'is_map', ts('Enable mapping for this profile'));

    // should we allow updates on a exisitng contact
    $form->addElement('select', 'is_update_dupe', ts('What to do upon duplicate match'), [
      // Upside down ordering so that option (2) is the default (it is the less risky and confusion option)
      2 => ts('Allow duplicate contact to be created'),
      1 => ts('Update the matching contact'),
      0 => ts('Issue warning and do not save'),
    ]);
    // we do not have any url checks to allow relative urls
    $form->addElement('text', 'post_url', ts('Redirect URL'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'post_url'));

    $form->add('advcheckbox', 'add_cancel_button', ts('Include Cancel Button'));
    $form->addElement('text', 'cancel_url', ts('Cancel Redirect URL'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'cancel_url'));

    $group = ['' => ts('- select -')] + $form->_group;

    $form->assign('legacyprofiles', function_exists('legacyprofiles_civicrm_config'));
    if (function_exists('legacyprofiles_civicrm_config')) {
      $form->_groupElement = &$form->addElement('select', 'group', ts('Limit listings to a specific Group'), $group);
    }

    $form->addElement('text', 'notify', ts('Notify when profile form is submitted'));
    $form->addElement('select', 'add_contact_to_group', ts('Add contacts to a group'), $group);
    $form->addElement('select', 'is_cms_user', ts('User account registration'), [ts('Disabled'), ts('Enabled, but not required'), ts('Required')]);
    $form->addElement('advcheckbox', 'add_captcha', ts('Include reCAPTCHA'));

    if (function_exists('legacyprofiles_civicrm_config')) {
      // Options for Profile Listings
      $form->addElement('advcheckbox', 'is_edit_link', ts('Include profile edit links in search results'));
      $form->addElement('advcheckbox', 'is_uf_link', ts('Include user account information links in search results'));
      $form->addElement('select', 'is_proximity_search', ts('Proximity Search'), [ts('Disabled'), ts('Optional'), ts('Required')]);
    }
  }

}
