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
 * This class generates form components for the mailing component preferences.
 */
class CRM_Admin_Form_Preferences_Mailing extends CRM_Admin_Form_Generic {

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (empty($params['write_activity_record'])) {
      // @todo use the setting onToggle & add an action rather than have specific form handling.
      // see logging setting for eg.
      $existingViewOptions = Civi::settings()->get('contact_view_options');

      $displayViewOptions = CRM_Core_OptionGroup::values('contact_view_options', TRUE, FALSE, FALSE, NULL, 'name');
      $displayValue = $displayViewOptions['CiviMail'];

      $viewOptions = explode(CRM_Core_DAO::VALUE_SEPARATOR, $existingViewOptions);

      if (!in_array($displayValue, $viewOptions)) {
        $existingViewOptions .= $displayValue . CRM_Core_DAO::VALUE_SEPARATOR;

        Civi::settings()->set('contact_view_options', $existingViewOptions);
        CRM_Core_Session::setStatus(ts('We have automatically enabled the Mailings tab for the Contact Summary screens
        so that you can view mailings sent to each contact.'), ts('Saved'), 'success');
      }
    }

    parent::postProcess();
  }

}
