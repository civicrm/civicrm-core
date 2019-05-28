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
class CRM_Admin_Form_WordReplacements extends CRM_Core_Form {
  protected $_numStrings = 10;

  protected $_stringName = NULL;

  public $unsavedChangesWarn = TRUE;

  /**
   * Pre process function.
   */
  public function preProcess() {
    // This controller was originally written to CRUD $config->locale_custom_strings,
    // but that's no longer the canonical store. Re-sync from canonical store to ensure
    // that we display that latest data. This is inefficient - at some point, we
    // should rewrite this UI.
    CRM_Core_BAO_WordReplacement::rebuild(FALSE);

    $this->_soInstance = CRM_Utils_Array::value('instance', $_GET);
    $this->assign('soInstance', $this->_soInstance);
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    if (!empty($this->_defaults)) {
      return $this->_defaults;
    }

    $this->_defaults = [];

    $config = CRM_Core_Config::singleton();

    $values = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($config->lcMessages);
    $i = 1;

    $enableDisable = [
      1 => 'enabled',
      0 => 'disabled',
    ];

    $cardMatch = ['wildcardMatch', 'exactMatch'];

    foreach ($enableDisable as $key => $val) {
      foreach ($cardMatch as $kc => $vc) {
        if (!empty($values[$val][$vc])) {
          foreach ($values[$val][$vc] as $k => $v) {
            $this->_defaults["enabled"][$i] = $key;
            $this->_defaults["cb"][$i] = $kc;
            $this->_defaults["old"][$i] = $k;
            $this->_defaults["new"][$i] = $v;
            $i++;
          }
        }
      }
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $values = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($config->lcMessages);

    //CRM-14179
    $instances = 0;
    foreach ($values as $valMatchType) {
      foreach ($valMatchType as $valPairs) {
        $instances += count($valPairs);
      }
    }

    if ($instances > 10) {
      $this->_numStrings = $instances;
    }

    $soInstances = range(1, $this->_numStrings, 1);
    $stringOverrideInstances = [];
    if ($this->_soInstance) {
      $soInstances = [$this->_soInstance];
    }
    elseif (!empty($_POST['old'])) {
      $soInstances = $stringOverrideInstances = array_keys($_POST['old']);
    }
    elseif (!empty($this->_defaults) && is_array($this->_defaults)) {
      $stringOverrideInstances = array_keys($this->_defaults['new']);
      if (count($this->_defaults['old']) > count($this->_defaults['new'])) {
        $stringOverrideInstances = array_keys($this->_defaults['old']);
      }
    }
    foreach ($soInstances as $instance) {
      $this->addElement('checkbox', "enabled[$instance]");
      $this->add('text', "old[$instance]", NULL);
      $this->add('text', "new[$instance]", NULL);
      $this->addElement('checkbox', "cb[$instance]");
    }
    $this->assign('numStrings', $this->_numStrings);
    if ($this->_soInstance) {
      return;
    }

    $this->assign('stringOverrideInstances', empty($stringOverrideInstances) ? FALSE : $stringOverrideInstances);

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
    $this->addFormRule(['CRM_Admin_Form_WordReplacements', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = [];

    $oldValues = CRM_Utils_Array::value('old', $values);
    $newValues = CRM_Utils_Array::value('new', $values);
    $enabled = CRM_Utils_Array::value('enabled', $values);
    $exactMatch = CRM_Utils_Array::value('cb', $values);

    foreach ($oldValues as $k => $v) {
      if ($v && !$newValues[$k]) {
        $errors['new[' . $k . ']'] = ts('Please Enter the value for Replacement Word');
      }
      elseif (!$v && $newValues[$k]) {
        $errors['old[' . $k . ']'] = ts('Please Enter the value for Original Word');
      }
      elseif ((empty($newValues[$k]) && empty($oldValues[$k]))
        && (!empty($enabled[$k]) || !empty($exactMatch[$k]))
      ) {
        $errors['old[' . $k . ']'] = ts('Please Enter the value for Original Word');
        $errors['new[' . $k . ']'] = ts('Please Enter the value for Replacement Word');
      }
    }

    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $this->_numStrings = count($params['old']);

    $enabled['exactMatch'] = $enabled['wildcardMatch'] = $disabled['exactMatch'] = $disabled['wildcardMatch'] = [];
    for ($i = 1; $i <= $this->_numStrings; $i++) {
      if (!empty($params['new'][$i]) && !empty($params['old'][$i])) {
        if (isset($params['enabled']) && !empty($params['enabled'][$i])) {
          if (!empty($params['cb']) && !empty($params['cb'][$i])) {
            $enabled['exactMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
          else {
            $enabled['wildcardMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
        }
        else {
          if (isset($params['cb']) && is_array($params['cb']) && array_key_exists($i, $params['cb'])) {
            $disabled['exactMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
          else {
            $disabled['wildcardMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
        }
      }
    }

    $overrides = [
      'enabled' => $enabled,
      'disabled' => $disabled,
    ];

    $config = CRM_Core_Config::singleton();
    CRM_Core_BAO_WordReplacement::setLocaleCustomStrings($config->lcMessages, $overrides);

    // This controller was originally written to CRUD $config->locale_custom_strings,
    // but that's no longer the canonical store. Sync changes to canonical store
    // (civicrm_word_replacement table in the database).
    // This is inefficient - at some point, we should rewrite this UI.
    CRM_Core_BAO_WordReplacement::rebuildWordReplacementTable();

    CRM_Core_Session::setStatus("", ts("Settings Saved"), "success");
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/options/wordreplacements',
      "reset=1"
    ));
  }

}
