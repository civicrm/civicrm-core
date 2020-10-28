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
    $form->addElement('checkbox', 'is_map', ts('Enable mapping for this profile?'));

    // should we allow updates on a exisitng contact
    $form->addRadio('is_update_dupe', ts('What to do upon duplicate match'), [ts('Issue warning and do not save'), ts('Update the matching contact'), ts('Allow duplicate contact to be created')]);
    // we do not have any url checks to allow relative urls
    $form->addElement('text', 'post_URL', ts('Redirect URL'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'post_URL'));

    $form->add('advcheckbox', 'add_cancel_button', ts('Include Cancel Button?'));
    $form->addElement('text', 'cancel_URL', ts('Cancel Redirect URL'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'cancel_URL'));

    // add select for groups
    $group = ['' => ts('- select -')] + $form->_group;
    $form->_groupElement = &$form->addElement('select', 'group', ts('Limit listings to a specific Group?'), $group);

    //add notify field
    $form->addElement('text', 'notify', ts('Notify when profile form is submitted?'));

    //group where new contacts are directed.
    $form->addElement('select', 'add_contact_to_group', ts('Add contacts to a group?'), $group);

    // add CAPTCHA To this group ?
    $form->addElement('checkbox', 'add_captcha', ts('Include reCAPTCHA?'));

    // should we display an edit link
    $form->addElement('checkbox', 'is_edit_link', ts('Include profile edit links in search results?'));

    // should we display a link to the website profile
    $config = CRM_Core_Config::singleton();
    $form->addElement('checkbox', 'is_uf_link', ts('Include %1 user account information links in search results?', [1 => $config->userFramework]));

    // want to create cms user
    $session = CRM_Core_Session::singleton();
    $cmsId = FALSE;
    if ($form->_cId = $session->get('userID')) {
      $form->_cmsId = TRUE;
    }

    $form->addRadio('is_cms_user', ts('%1 user account registration option?', [1 => $config->userFramework]), [ts('No account create option'), ts('Give option, but not required'), ts('Account creation required')]);

    // options for including Proximity Search in the profile search form
    $form->addRadio('is_proximity_search', ts('Proximity Search'), [ts('None'), ts('Optional'), ts('Required')]);
  }

}
