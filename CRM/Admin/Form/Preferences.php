<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Base class for settings forms.
 */
class CRM_Admin_Form_Preferences extends CRM_Core_Form {

  use CRM_Admin_Form_SettingTrait;

  protected $_system = FALSE;
  protected $_contactID = NULL;
  public $_action = NULL;

  protected $_checkbox = NULL;

  protected $_varNames = [];

  protected $_config = NULL;

  protected $_params = NULL;

  public function preProcess() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );
    $this->_system = CRM_Utils_Request::retrieve('system', 'Boolean',
      $this, FALSE, TRUE
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'update'
    );
    if (isset($action)) {
      $this->assign('action', $action);
    }

    $session = CRM_Core_Session::singleton();

    $this->_config = new CRM_Core_DAO();

    if ($this->_system) {
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        $this->_contactID = NULL;
      }
      else {
        CRM_Utils_System::fatal('You do not have permission to edit preferences');
      }
      $this->_config->contact_id = NULL;
    }
    else {
      if (!$this->_contactID) {
        $this->_contactID = $session->get('userID');
        if (!$this->_contactID) {
          CRM_Utils_System::fatal('Could not retrieve contact id');
        }
        $this->set('cid', $this->_contactID);
      }
      $this->_config->contact_id = $this->_contactID;
    }

    $this->addFieldsDefinedInSettingsMetadata();
    $settings = Civi::settings();
    // @todo replace this by defining all in settings.
    foreach ($this->_varNames as $groupName => $settingNames) {
      CRM_Core_Error::deprecatedFunctionWarning('deprecated use of preferences form. This will be removed from core soon');
      foreach ($settingNames as $settingName => $options) {
        $this->_config->$settingName = $settings->get($settingName);
      }
    }
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $this->_defaults = [];

    $this->setDefaultsForMetadataDefinedFields();
    foreach ($this->_varNames as $groupName => $settings) {
      CRM_Core_Error::deprecatedFunctionWarning('deprecated use of preferences form. This will be removed from core soon');
      foreach ($settings as $settingName => $settingDetails) {
        $this->_defaults[$settingName] = isset($this->_config->$settingName) ? $this->_config->$settingName : CRM_Utils_Array::value('default', $settingDetails, NULL);
      }
    }

    return $this->_defaults;
  }

  /**
   * @todo deprecate in favour of setting using metadata.
   *
   * @param $defaults
   */
  public function cbsDefaultValues(&$defaults) {

    foreach ($this->_varNames as $groupName => $groupValues) {
      CRM_Core_Error::deprecatedFunctionWarning('deprecated use of preferences form. This will be removed from core soon');
      foreach ($groupValues as $settingName => $fieldValue) {
        if ($fieldValue['html_type'] == 'checkboxes') {
          if (isset($this->_config->$settingName) &&
            $this->_config->$settingName
          ) {
            $value = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              substr($this->_config->$settingName, 1, -1)
            );
            if (!empty($value)) {
              $defaults[$settingName] = [];
              foreach ($value as $n => $v) {
                $defaults[$settingName][$v] = 1;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if (!empty($this->_varNames)) {
      CRM_Core_Error::deprecatedFunctionWarning('deprecated use of preferences form. This will be removed from core soon');
      foreach ($this->_varNames as $groupName => $groupValues) {
        $formName = CRM_Utils_String::titleToVar($groupName);
        $this->assign('formName', $formName);
        $fields = [];
        foreach ($groupValues as $fieldName => $fieldValue) {
          $fields[$fieldName] = $fieldValue;

          switch ($fieldValue['html_type']) {
            case 'text':
              $this->addElement('text',
                $fieldName,
                $fieldValue['title'],
                [
                  'maxlength' => 64,
                  'size' => 32,
                ]
              );
              break;

            case 'textarea':
            case 'checkbox':
              $this->add($fieldValue['html_type'],
                $fieldName,
                $fieldValue['title']
              );
              break;

            case 'radio':
              $options = CRM_Core_OptionGroup::values($fieldName, FALSE, FALSE, TRUE);
              $this->addRadio($fieldName, $fieldValue['title'], $options, NULL, '&nbsp;&nbsp;');
              break;

            case 'YesNo':
              $this->addRadio($fieldName, $fieldValue['title'], [0 => 'No', 1 => 'Yes'], NULL, '&nbsp;&nbsp;');
              break;

            case 'checkboxes':
              $options = array_flip(CRM_Core_OptionGroup::values($fieldName, FALSE, FALSE, TRUE));
              $newOptions = [];
              foreach ($options as $key => $val) {
                $newOptions[$key] = $val;
              }
              $this->addCheckBox($fieldName,
                $fieldValue['title'],
                $newOptions,
                NULL, NULL, NULL, NULL,
                ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
              );
              break;

            case 'select':
              $this->addElement('select',
                $fieldName,
                $fieldValue['title'],
                $fieldValue['option_values'],
                CRM_Utils_Array::value('attributes', $fieldValue)
              );
              break;

            case 'wysiwyg':
              $this->add('wysiwyg', $fieldName, $fieldValue['title'], $fieldValue['attributes']);
              break;

            case 'entity_reference':
              $this->addEntityRef($fieldName, $fieldValue['title'], CRM_Utils_Array::value('options', $fieldValue, []));
          }
        }

        $fields = CRM_Utils_Array::crmArraySortByField($fields, 'weight');
        $this->assign('fields', $fields);
      }
    }

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    if ($this->_action == CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);

    $this->postProcessCommon();
  }

  /**
   * Process the form submission.
   */
  public function postProcessCommon() {
    try {
      $this->saveMetadataDefinedSettings($this->_params);
      $this->filterParamsSetByMetadata($this->_params);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }

    foreach ($this->_varNames as $groupName => $groupValues) {
      foreach ($groupValues as $settingName => $fieldValue) {
        switch ($fieldValue['html_type']) {
          case 'checkboxes':
            if (!empty($this->_params[$settingName]) &&
              is_array($this->_params[$settingName])
            ) {
              $this->_config->$settingName = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                  array_keys($this->_params[$settingName])
                ) . CRM_Core_DAO::VALUE_SEPARATOR;
            }
            else {
              $this->_config->$settingName = NULL;
            }
            break;

          case 'checkbox':
            $this->_config->$settingName = !empty($this->_params[$settingName]) ? 1 : 0;
            break;

          case 'text':
          case 'select':
          case 'radio':
          case 'YesNo':
          case 'entity_reference':
            $this->_config->$settingName = CRM_Utils_Array::value($settingName, $this->_params);
            break;

          case 'textarea':
            $value = CRM_Utils_Array::value($settingName, $this->_params);
            if ($value) {
              $value = trim($value);
              $value = str_replace(["\r\n", "\r"], "\n", $value);
            }
            $this->_config->$settingName = $value;
            break;
        }
      }
    }

    foreach ($this->_varNames as $groupName => $groupValues) {
      foreach ($groupValues as $settingName => $fieldValue) {
        $settingValue = isset($this->_config->$settingName) ? $this->_config->$settingName : NULL;
        Civi::settings()->set($settingName, $settingValue);
      }
    }
    // Update any settings stored in dynamic js
    CRM_Core_Resources::singleton()->resetCacheCode();

    CRM_Core_Session::setStatus(ts('Your changes have been saved.'), ts('Saved'), 'success');
  }

}
