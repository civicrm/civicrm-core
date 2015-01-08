<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: Display.php 45499 2013-02-08 12:31:05Z kurund $
 *
 */

/**r
 * This class generates form components for the display preferences
 *
 */
class CRM_Admin_Form_Preferences_Display extends CRM_Admin_Form_Preferences {
  function preProcess() {
    CRM_Utils_System::setTitle(ts('Settings - Display Preferences'));

    $this->_varNames = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME =>
      array(
        'contact_view_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Viewing Contacts'),
          'weight' => 1,
        ),
        'contact_smart_group_display' => array(
          'html_type' => 'radio',
          'title' => ts('Viewing Smart Groups'),
          'weight' => 2,
        ),
        'contact_edit_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Editing Contacts'),
          'weight' => 3,
        ),
        'advanced_search_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Contact Search'),
          'weight' => 4,
        ),
        'activity_assignee_notification' => array(
          'html_type' => 'checkbox',
          'title' => ts('Notify Activity Assignees'),
          'weight' => 5,
        ),
        'activity_assignee_notification_ics' => array(
          'html_type' => 'checkbox',
          'title' => ts('Include ICal Invite to Activity Assignees'),
          'weight' => 6,
        ),
        'contact_ajax_check_similar' => array(
          'html_type' => 'checkbox',
          'title' => ts('Check for Similar Contacts'),
          'weight' => 7,
        ),
        'user_dashboard_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Contact Dashboard'),
          'weight' => 8,
        ),
        'display_name_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Individual Display Name Format'),
          'weight' => 9,
        ),
        'sort_name_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Individual Sort Name Format'),
          'weight' => 10,
        ),
        'editor_id' => array(
          'html_type' => NULL,
          'weight' => 11,
        ),
        'ajaxPopupsEnabled' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable Popup Forms'),
          'weight' => 12,
        ),
      ),
    );

    parent::preProcess();
  }

  /**
   * @return array
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    parent::cbsDefaultValues($defaults);

    if ($this->_config->editor_id) {
      $defaults['editor_id'] = $this->_config->editor_id;
    }
    if (empty($this->_config->display_name_format)) {
      $defaults['display_name_format'] =
        "{contact.individual_prefix}{ }{contact.first_name}{ }{contact.last_name}{ }{contact.individual_suffix}";
    }
    else {
      $defaults['display_name_format'] = $this->_config->display_name_format;
    }

    if (empty($this->_config->sort_name_format)) {
      $defaults['sort_name_format'] = "{contact.last_name}{, }{contact.first_name}";
    }
    else {
      $defaults['sort_name_format'] = $this->_config->sort_name_format;
    }

    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal == '1' && module_exists("wysiwyg")) {
      $defaults['wysiwyg_input_format'] = variable_get('civicrm_wysiwyg_input_format', 0);
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $wysiwyg_options = array('' => ts('Textarea')) + CRM_Core_OptionGroup::values('wysiwyg_editor');

    $config = CRM_Core_Config::singleton();
    $extra = array();

    //if not using Joomla, remove Joomla default editor option
    if ($config->userFramework != 'Joomla') {
      unset($wysiwyg_options[3]);
    }

    $drupal_wysiwyg = FALSE;
    if (!$config->userSystem->is_drupal || !module_exists("wysiwyg")) {
      unset($wysiwyg_options[4]);
    }
    else {
      $extra['onchange'] = '
      if (this.value==4) {
        cj("#crm-preferences-display-form-block-wysiwyg_input_format").show();
      }
      else {
        cj("#crm-preferences-display-form-block-wysiwyg_input_format").hide()
      }';

      $formats           = filter_formats();
      $format_options    = array();
      foreach ($formats as $id => $format) {
        $format_options[$id] = $format->name;
      }
      $drupal_wysiwyg = TRUE;
    }
    $this->addElement('select', 'editor_id', ts('WYSIWYG Editor'), $wysiwyg_options, $extra);

    if ($drupal_wysiwyg) {
      $this->addElement('select', 'wysiwyg_input_format', ts('Input Format'), $format_options, NULL);
    }

    $editOptions = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 0');
    $this->assign('editOptions', $editOptions);

    $contactBlocks = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 1');
    $this->assign('contactBlocks', $contactBlocks);

    $nameFields = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 2');
    $this->assign('nameFields', $nameFields);

    $this->addElement('hidden', 'contact_edit_preferences', NULL, array('id' => 'contact_edit_preferences'));

    parent::buildQuickForm();
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);

    if (!empty($this->_params['contact_edit_preferences'])) {
      $preferenceWeights = explode(',', $this->_params['contact_edit_preferences']);
      foreach ($preferenceWeights as $key => $val) {
        if (!$val) {
          unset($preferenceWeights[$key]);
        }
      }
      $opGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'contact_edit_options', 'id', 'name');
      CRM_Core_BAO_OptionValue::updateOptionWeights($opGroupId, array_flip($preferenceWeights));
    }

    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal == '1' && module_exists("wysiwyg")) {
      variable_set('civicrm_wysiwyg_input_format', $this->_params['wysiwyg_input_format']);
    }

    $this->_config->editor_id = $this->_params['editor_id'];

    // set default editor to session if changed
    $session = CRM_Core_Session::singleton();
    $session->set('defaultWysiwygEditor', $this->_params['editor_id']);

    $this->postProcessCommon();
  }
  //end of function
}

