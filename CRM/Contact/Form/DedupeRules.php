<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for DedupeRules
 *
 */
class CRM_Contact_Form_DedupeRules extends CRM_Admin_Form {
  CONST RULES_COUNT = 5;
  protected $_contactType;
  protected $_defaults = array();
  protected $_fields = array();
  protected $_rgid;

  /**
   * Function to pre processing
   *
   * @return None
   * @access public
   */ 
  function preProcess() {
    // Ensure user has permission to be here
    if (!CRM_Core_Permission::check('administer dedupe rules')) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }
    $this->_options = array(ts('Unsupervised'), ts('Supervised'), ts('General'));
    $this->_rgid = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $this->_contactType = CRM_Utils_Request::retrieve('contact_type', 'String', $this, FALSE, 0);
    if ($this->_rgid) {
      $rgDao = new CRM_Dedupe_DAO_RuleGroup();
      $rgDao->id = $this->_rgid;
      $rgDao->find(TRUE);

      $this->_defaults['threshold'] = $rgDao->threshold;
      $this->_contactType = $rgDao->contact_type;
      $this->_defaults['used'] = CRM_Utils_Array::key($rgDao->used, $this->_options);
      $this->_defaults['title'] = $rgDao->title;
      $this->_defaults['name'] = $rgDao->name;
      $this->_defaults['is_reserved'] = $rgDao->is_reserved;
      $this->assign('isReserved', $rgDao->is_reserved);
      $this->assign('ruleName', $rgDao->name);
      $ruleDao = new CRM_Dedupe_DAO_Rule();
      $ruleDao->dedupe_rule_group_id = $this->_rgid;
      $ruleDao->find();
      $count = 0;
      while ($ruleDao->fetch()) {
        $this->_defaults["where_$count"] = "{$ruleDao->rule_table}.{$ruleDao->rule_field}";
        $this->_defaults["length_$count"] = $ruleDao->rule_length;
        $this->_defaults["weight_$count"] = $ruleDao->rule_weight;
        $count++;
      }
    }
    $supported = CRM_Dedupe_BAO_RuleGroup::supportedFields($this->_contactType);
    if (is_array($supported)) {
      foreach ($supported as $table => $fields) {
        foreach ($fields as $field => $title) {
          $this->_fields["$table.$field"] = $title;
        }
      }
    }
    asort($this->_fields);
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $foo = CRM_Core_DAO::getAttribute('CRM_Dedupe_DAO_Rule', 'title');

    $this->add('text', 'title', ts('Rule Name'), array('maxlength' => 255, 'class' => 'huge'), TRUE);
    $this->addRule('title', ts('A duplicate matching rule with this name already exists. Please select another name.'),
      'objectExists', array('CRM_Dedupe_DAO_RuleGroup', $this->_rgid, 'title')
    );

    $this->addRadio('used', ts('Usage'), $this->_options);

    $disabled = array();
    $reserved = $this->add('checkbox', 'is_reserved', ts('Reserved?'));
    if (CRM_Utils_Array::value('is_reserved', $this->_defaults)) {
      $reserved->freeze();
      $disabled = array('disabled' => TRUE);
    }

    $attributes = array('class' => 'two');
    if (!empty($disabled)) {
      $attributes = array_merge($attributes, $disabled);
    }

    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      $this->add('select', "where_$count", ts('Field'),
        array(
          NULL => ts('- none -')) + $this->_fields, FALSE, $disabled
      );
      $this->add('text', "length_$count", ts('Length'), $attributes);
      $this->add('text', "weight_$count", ts('Weight'), $attributes);
    }

    $this->add('text', 'threshold', ts("Weight Threshold to Consider Contacts 'Matching':"), $attributes);
    $this->addButtons(array(
        array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE),
        array('type' => 'cancel', 'name' => ts('Cancel')),
      ));

    $this->assign('contact_type', $this->_contactType);

    $this->addFormRule(array('CRM_Contact_Form_DedupeRules', 'formRule'), $this);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    if (CRM_Utils_Array::value('is_reserved', $fields)) {
      return TRUE;
    }

    $fieldSelected = FALSE;
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (CRM_Utils_Array::value("where_$count", $fields)) {
        $fieldSelected = TRUE;
        break;
      }
    }

    if (!$fieldSelected) {
      $errors['_qf_default'] = ts('Please select at least one field.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $values = $this->exportValues();
    
    //FIXME: Handle logic to replace is_default column by usage
    $used = CRM_Utils_Array::value('used', $values, FALSE);
    // reset used column to General (since there can only
    // be one 'Supervised' or 'Unsupervised' rule)
    if ($this->_options[$used] != 'General') {
      $query = "
UPDATE civicrm_dedupe_rule_group 
   SET used = 'General'
 WHERE contact_type = %1 
   AND used = %2";
      $queryParams = array(1 => array($this->_contactType, 'String'),
        2 => array($this->_options[$used], 'String'),
      );

      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $rgDao = new CRM_Dedupe_DAO_RuleGroup();
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $rgDao->id = $this->_rgid;
    }

    $rgDao->title        = $values['title'];
    $rgDao->is_reserved  = CRM_Utils_Array::value('is_reserved', $values, FALSE);
    $rgDao->used         = $this->_options[$values['used']];
    $rgDao->contact_type = $this->_contactType;
    $rgDao->threshold    = $values['threshold'];
    $rgDao->save();

    // make sure name is set only during insert
    if ($this->_action & CRM_Core_Action::ADD) {
      // generate name based on title
      $rgDao->name = CRM_Utils_String::titleToVar($values['title']) . "_{$rgDao->id}";
      $rgDao->save();
    }

    // lets skip updating of fields for reserved dedupe group
    if ($rgDao->is_reserved) {
      CRM_Core_Session::setStatus(ts("The rule '%1' has been saved.", array(1 => $rgDao->title)), ts('Saved'), 'success');
      return;
    }

    $ruleDao = new CRM_Dedupe_DAO_Rule();
    $ruleDao->dedupe_rule_group_id = $rgDao->id;
    $ruleDao->delete();
    $ruleDao->free();

    $substrLenghts = array();

    $tables = array();
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (!CRM_Utils_Array::value("where_$count", $values)) {
        continue;
      }
      list($table, $field) = explode('.', CRM_Utils_Array::value("where_$count", $values));
      $length = CRM_Utils_Array::value("length_$count", $values) ? CRM_Utils_Array::value("length_$count", $values) : NULL;
      $weight = $values["weight_$count"];
      if ($table and $field) {
        $ruleDao = new CRM_Dedupe_DAO_Rule();
        $ruleDao->dedupe_rule_group_id = $rgDao->id;
        $ruleDao->rule_table = $table;
        $ruleDao->rule_field = $field;
        $ruleDao->rule_length = $length;
        $ruleDao->rule_weight = $weight;
        $ruleDao->save();
        $ruleDao->free();

        if (!array_key_exists($table, $tables)) {
          $tables[$table] = array();
        }
        $tables[$table][] = $field;
      }

      // CRM-6245: we must pass table/field/length triples to the createIndexes() call below
      if ($length) {
        if (!isset($substrLenghts[$table])) {
          $substrLenghts[$table] = array();
        }
        $substrLenghts[$table][$field] = $length;
      }
    }

    // also create an index for this dedupe rule
    // CRM-3837
    CRM_Core_BAO_SchemaHandler::createIndexes($tables, 'dedupe_index', $substrLenghts);

    //need to clear cache of deduped contacts
    //based on the previous rule
    $cacheKey = "merge {$this->_contactType}_{$this->_rgid}_%";

    CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey);

    CRM_Core_Session::setStatus(ts("The rule '%1' has been saved.", array(1 => $rgDao->title)), ts('Saved'), 'success');
  }
}

