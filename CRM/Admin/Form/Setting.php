<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components generic to CiviCRM settings.
 */
class CRM_Admin_Form_Setting extends CRM_Core_Form {

  protected $_settings = array();

  /**
   * Set default values for the form.
   *
   * Default values are retrieved from the database.
   */
  public function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = array();
      $formArray = array('Component', 'Localization');
      $formMode = FALSE;
      if (in_array($this->_name, $formArray)) {
        $formMode = TRUE;
      }

      CRM_Core_BAO_ConfigSetting::retrieve($this->_defaults);

      // we can handle all the ones defined in the metadata here. Others to be converted
      foreach ($this->_settings as $setting => $group) {
        $this->_defaults[$setting] = civicrm_api('setting', 'getvalue', array(
            'version' => 3,
            'name' => $setting,
            'group' => $group,
          )
        );
      }

      $this->_defaults['contact_autocomplete_options'] = self::getAutocompleteContactSearch();
      $this->_defaults['contact_reference_options'] = self::getAutocompleteContactReference();
      $this->_defaults['enableSSL'] = Civi::settings()->get('enableSSL');
      $this->_defaults['verifySSL'] = Civi::settings()->get('verifySSL');
      $this->_defaults['enableComponents'] = Civi::settings()->get('enable_components');
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
    $args = func_get_args();
    $check = reset($args);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $descriptions = array();
    foreach ($this->_settings as $setting => $group) {
      $settingMetaData = civicrm_api('setting', 'getfields', array('version' => 3, 'name' => $setting));
      $props = $settingMetaData['values'][$setting];
      if (isset($props['quick_form_type'])) {
        if (isset($props['pseudoconstant'])) {
          $options = civicrm_api3('Setting', 'getoptions', array(
            'field' => $setting,
          ));
        }
        else {
          $options = NULL;
        }

        $add = 'add' . $props['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add(
            $props['html_type'],
            $setting,
            ts($props['title']),
            ($options !== NULL) ? $options['values'] : CRM_Utils_Array::value('html_attributes', $props, array()),
            ($options !== NULL) ? CRM_Utils_Array::value('html_attributes', $props, array()) : NULL
          );
        }
        elseif ($add == 'addSelect') {
          $this->addElement('select', $setting, ts($props['title']), $options['values'], CRM_Utils_Array::value('html_attributes', $props));
        }
        elseif ($add == 'addCheckBox') {
          $this->addCheckBox($setting, ts($props['title']), $options['values'], NULL, CRM_Utils_Array::value('html_attributes', $props), NULL, NULL, array('&nbsp;&nbsp;'));
        }
        elseif ($add == 'addChainSelect') {
          $this->addChainSelect($setting, array(
            'label' => ts($props['title']),
          ));
        }
        elseif ($add == 'addMonthDay') {
          $this->add('date', $setting, ts($props['title']), CRM_Core_SelectValues::date(NULL, 'M d'));
        }
        else {
          $this->$add($setting, ts($props['title']));
        }
        // Migrate to using an array as easier in smart...
        $descriptions[$setting] = ts($props['description']);
        $this->assign("{$setting}_description", ts($props['description']));
        if ($setting == 'max_attachments') {
          //temp hack @todo fix to get from metadata
          $this->addRule('max_attachments', ts('Value should be a positive number'), 'positiveInteger');
        }
        if ($setting == 'maxFileSize') {
          //temp hack
          $this->addRule('maxFileSize', ts('Value should be a positive number'), 'positiveInteger');
        }

      }
    }
    $this->assign('setting_descriptions', $descriptions);
  }

  /**
   * Get default entity.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Setting';
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    self::commonProcess($params);
  }

  /**
   * Common Process.
   *
   * @todo Document what I do.
   *
   * @param array $params
   */
  public function commonProcess(&$params) {

    // save autocomplete search options
    if (!empty($params['contact_autocomplete_options'])) {
      Civi::settings()->set('contact_autocomplete_options',
        CRM_Utils_Array::implodePadded(array_keys($params['contact_autocomplete_options'])));
      unset($params['contact_autocomplete_options']);
    }

    // save autocomplete contact reference options
    if (!empty($params['contact_reference_options'])) {
      Civi::settings()->set('contact_reference_options',
        CRM_Utils_Array::implodePadded(array_keys($params['contact_reference_options'])));
      unset($params['contact_reference_options']);
    }

    // save components to be enabled
    if (array_key_exists('enableComponents', $params)) {
      civicrm_api3('setting', 'create', array(
        'enable_components' => $params['enableComponents'],
      ));
      unset($params['enableComponents']);
    }

    // verify ssl peer option
    if (isset($params['verifySSL'])) {
      Civi::settings()->set('verifySSL', $params['verifySSL']);
      unset($params['verifySSL']);
    }

    // force secure URLs
    if (isset($params['enableSSL'])) {
      Civi::settings()->set('enableSSL', $params['enableSSL']);
      unset($params['enableSSL']);
    }
    $settings = array_intersect_key($params, $this->_settings);
    $result = civicrm_api('setting', 'create', $settings + array('version' => 3));
    foreach ($settings as $setting => $settingGroup) {
      //@todo array_diff this
      unset($params[$setting]);
    }
    if (!empty($result['error_message'])) {
      CRM_Core_Session::setStatus($result['error_message'], ts('Save Failed'), 'error');
    }

    //CRM_Core_BAO_ConfigSetting::create($params);
    $params = CRM_Core_BAO_ConfigSetting::filterSkipVars($params);
    if (!empty($params)) {
      CRM_Core_Error::fatal('Unrecognized setting. This may be a config field which has not been properly migrated to a setting. (' . implode(', ', array_keys($params)) . ')');
    }

    CRM_Core_Config::clearDBCache();
    CRM_Utils_System::flushCache();
    CRM_Core_Resources::singleton()->resetCacheCode();

    CRM_Core_Session::setStatus(" ", ts('Changes Saved'), "success");
  }

  public function rebuildMenu() {
    // ensure config is set with new values
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    // rebuild menu items
    CRM_Core_Menu::store();

    // also delete the IDS file so we can write a new correct one on next load
    $configFile = $config->uploadDir . 'Config.IDS.ini';
    @unlink($configFile);
  }

  /**
   * Ugh, this shouldn't exist.
   *
   * Get the selected values of "contact_reference_options" formatted for checkboxes.
   *
   * @return array
   */
  public static function getAutocompleteContactReference() {
    $cRlist = array_flip(CRM_Core_OptionGroup::values('contact_reference_options',
      FALSE, FALSE, TRUE, NULL, 'name'
    ));
    $cRlistEnabled = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_reference_options'
    );
    $cRSearchFields = array();
    if (!empty($cRlist) && !empty($cRlistEnabled)) {
      $cRSearchFields = array_combine($cRlist, $cRlistEnabled);
    }
    return array(
      '1' => 1,
    ) + $cRSearchFields;
  }

  /**
   * Ugh, this shouldn't exist.
   *
   * Get the selected values of "contact_autocomplete_options" formatted for checkboxes.
   *
   * @return array
   */
  public static function getAutocompleteContactSearch() {
    $list = array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options',
      FALSE, FALSE, TRUE, NULL, 'name'
    ));
    $listEnabled = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_autocomplete_options'
    );
    $autoSearchFields = array();
    if (!empty($list) && !empty($listEnabled)) {
      $autoSearchFields = array_combine($list, $listEnabled);
    }
    //Set defaults for autocomplete and contact reference options
    return array(
      '1' => 1,
    ) + $autoSearchFields;
  }

}
