<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 */

/**
 * This class generates form components generic to CiviCRM settings
 */
class CRM_Admin_Form_Setting extends CRM_Core_Form {

  protected $_defaults;
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

      CRM_Core_Config_Defaults::setValues($this->_defaults, $formMode);

      $list = array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options',
        FALSE, FALSE, TRUE, NULL, 'name'
      ));

      $cRlist = array_flip(CRM_Core_OptionGroup::values('contact_reference_options',
        FALSE, FALSE, TRUE, NULL, 'name'
      ));

      $listEnabled = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options'
      );
      $cRlistEnabled = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_reference_options'
      );

      $autoSearchFields = array();
      if (!empty($list) && !empty($listEnabled)) {
        $autoSearchFields = array_combine($list, $listEnabled);
      }

      $cRSearchFields = array();
      if (!empty($cRlist) && !empty($cRlistEnabled)) {
        $cRSearchFields = array_combine($cRlist, $cRlistEnabled);
      }

      //Set defaults for autocomplete and contact reference options
      $this->_defaults['autocompleteContactSearch'] = array(
        '1' => 1,
      ) + $autoSearchFields;
      $this->_defaults['autocompleteContactReference'] = array(
        '1' => 1,
      ) + $cRSearchFields;

      // we can handle all the ones defined in the metadata here. Others to be converted
      foreach ($this->_settings as $setting => $group) {
        $settingMetaData = civicrm_api('setting', 'getfields', array('version' => 3, 'name' => $setting));
        $this->_defaults[$setting] = civicrm_api('setting', 'getvalue', array(
            'version' => 3,
            'name' => $setting,
            'group' => $group,
            'default_value' => CRM_Utils_Array::value('default', $settingMetaData['values'][$setting]),
          )
        );
      }

      $this->_defaults['enableSSL'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'enableSSL', NULL, 0);
      $this->_defaults['verifySSL'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL', NULL, 1);
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

    foreach ($this->_settings as $setting => $group) {
      $settingMetaData = civicrm_api('setting', 'getfields', array('version' => 3, 'name' => $setting));
      $props = $settingMetaData['values'][$setting];
      if (isset($props['quick_form_type'])) {
        $add = 'add' . $props['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add(
            $props['html_type'],
            $setting,
            ts($props['title']),
            CRM_Utils_Array::value($props['html_type'] == 'select' ? 'option_values' : 'html_attributes', $props, array()),
            $props['html_type'] == 'select' ? CRM_Utils_Array::value('html_attributes', $props) : NULL
          );
        }
        else {
          $this->$add($setting, ts($props['title']));
        }
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
    if (!empty($params['autocompleteContactSearch'])) {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_keys($params['autocompleteContactSearch'])
        ) . CRM_Core_DAO::VALUE_SEPARATOR;

      CRM_Core_BAO_Setting::setItem($value,
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options'
      );

      unset($params['autocompleteContactSearch']);
    }

    // save autocomplete contact reference options
    if (!empty($params['autocompleteContactReference'])) {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_keys($params['autocompleteContactReference'])
        ) . CRM_Core_DAO::VALUE_SEPARATOR;

      CRM_Core_BAO_Setting::setItem($value,
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_reference_options'
      );

      unset($params['autocompleteContactReference']);
    }

    // save components to be enabled
    if (array_key_exists('enableComponents', $params)) {
      civicrm_api3('setting', 'create', array(
        'enable_components' => $params['enableComponents'],
      ));
      unset($params['enableComponents']);
    }

    // save checksum timeout
    if (!empty($params['checksumTimeout'])) {
      CRM_Core_BAO_Setting::setItem($params['checksumTimeout'],
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'checksum_timeout'
      );
    }

    // update time for date formats when global time is changed
    if (!empty($params['timeInputFormat'])) {
      $query = "
UPDATE civicrm_preferences_date
SET    time_format = %1
WHERE  time_format IS NOT NULL
AND    time_format <> ''
";
      $sqlParams = array(1 => array($params['timeInputFormat'], 'String'));
      CRM_Core_DAO::executeQuery($query, $sqlParams);
    }

    // verify ssl peer option
    if (isset($params['verifySSL'])) {
      CRM_Core_BAO_Setting::setItem($params['verifySSL'],
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'verifySSL'
      );
      unset($params['verifySSL']);
    }

    // force secure URLs
    if (isset($params['enableSSL'])) {
      CRM_Core_BAO_Setting::setItem($params['enableSSL'],
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'enableSSL'
      );
      unset($params['enableSSL']);
    }
    $settings = array_intersect_key($params, $this->_settings);
    $result = civicrm_api('setting', 'create', $settings + array('version' => 3));
    foreach ($settings as $setting => $settingGroup) {
      //@todo array_diff this
      unset($params[$setting]);
    }
    CRM_Core_BAO_ConfigSetting::create($params);

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

}
