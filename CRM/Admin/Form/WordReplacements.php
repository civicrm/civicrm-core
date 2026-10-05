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
class CRM_Admin_Form_WordReplacements extends CRM_Core_Form {

  /**
   * The "Single object" instance.
   * Used to output a single row of the form (at the specified index)
   *
   * @var null|int
   */
  protected $_soInstance = NULL;

  /**
   * The default number of strings to output
   *
   * @var int
   */
  protected $_numStrings = 10;

  /**
   * Indicate if this form should warn users of unsaved changes
   *
   * @var bool
   */
  public $unsavedChangesWarn = TRUE;

  /**
   * Pre process function.
   */
  public function preProcess() {
    $this->_soInstance = CRM_Utils_Request::retrieve('instance', 'Positive', NULL, FALSE, NULL, 'GET');
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
    $tsLocale = CRM_Core_I18n::getLocale();
    $values = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($tsLocale);
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
    $tsLocale = CRM_Core_I18n::getLocale();
    $values = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($tsLocale);

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

    $oldValues = $values['old'] ?? [];
    $newValues = $values['new'] ?? [];
    $enabled = $values['enabled'] ?? [];
    $exactMatch = $values['cb'] ?? [];

    foreach ($oldValues as $k => $v) {
      $cleanOld = trim(CRM_Utils_String::purifyHTML(htmlspecialchars_decode((string) $v)));
      $cleanNew = trim(CRM_Utils_String::purifyHTML(htmlspecialchars_decode((string) ($newValues[$k] ?? ''))));

      if ($cleanOld && !$cleanNew) {
        $errors['new[' . $k . ']'] = ts('Please Enter the value for Replacement Word');
      }
      elseif (!$cleanOld && $cleanNew) {
        $errors['old[' . $k . ']'] = ts('Please Enter the value for Original Word');
      }
      elseif ((empty($cleanNew) && empty($cleanOld))
        && (!empty($enabled[$k]) || !empty($exactMatch[$k]) || !empty($v) || !empty($newValues[$k]))
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
    $this->_numStrings = count($params['old'] ?? []);
    $tsLocale = CRM_Core_I18n::getLocale();
    $domainID = CRM_Core_Config::domainID();

    $values = [];
    for ($i = 1; $i <= $this->_numStrings; $i++) {
      if (!empty($params['new'][$i]) && !empty($params['old'][$i])) {
        // String may have simple HTML entities (usually 'strong' or 'a'),
        // and will be escaped by QuickForm. To avoid having to whitelist
        // all of old[*] and new[*] patterns, we unescape, check for XSS,
        // and the API itself has whitelisted find_word and replace_word.
        $findWord = trim(CRM_Utils_String::purifyHTML(htmlspecialchars_decode($params['old'][$i])));
        $replaceWord = trim(CRM_Utils_String::purifyHTML(htmlspecialchars_decode($params['new'][$i])));
        if ($findWord !== '' && $replaceWord !== '') {
          $values[] = [
            'find_word' => $findWord,
            'replace_word' => $replaceWord,
            'is_active' => !empty($params['enabled'][$i]),
            'match_type' => !empty($params['cb'][$i]) ? 'exactMatch' : 'wildcardMatch',
          ];
        }
      }
    }

    $where = [
      ['domain_id', '=', $domainID],
      ['language', '=', $tsLocale],
    ];

    if (empty($values)) {
      civicrm_api4('WordReplacement', 'delete', [
        'where' => $where,
      ]);
    }
    else {
      civicrm_api4('WordReplacement', 'replace', [
        'where' => $where,
        'match' => ['find_word', 'domain_id', 'language'],
        'records' => $values,
      ]);
    }

    CRM_Core_BAO_WordReplacement::rebuild();

    CRM_Core_Session::setStatus("", ts("Settings Saved"), "success");
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/options/wordreplacements',
      "reset=1"
    ));
  }

}
