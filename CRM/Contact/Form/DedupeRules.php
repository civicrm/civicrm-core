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
 * This class generates form components for DedupeRules.
 */
class CRM_Contact_Form_DedupeRules extends CRM_Admin_Form {
  const RULES_COUNT = 5;
  protected $_contactType;
  protected $_fields = [];
  protected $_rgid;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'RuleGroup';
  }

  /**
   * Pre processing.
   */
  public function preProcess() {
    // Ensure user has permission to be here
    if (!CRM_Core_Permission::check('administer dedupe rules')) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }
    $this->_options = CRM_Core_SelectValues::getDedupeRuleTypes();
    $this->_rgid = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

    // check if $contactType is valid
    $contactTypes = civicrm_api3('Contact', 'getOptions', ['field' => "contact_type", 'context' => "validate"]);
    $contactType = CRM_Utils_Request::retrieve('contact_type', 'String', $this, FALSE, 0);
    if (CRM_Utils_Array::value($contactType, $contactTypes['values'])) {
      $this->_contactType = $contactType;
    }
    elseif (!empty($contactType)) {
      throw new CRM_Core_Exception('Contact Type is Not valid');
    }
    if ($this->_rgid) {
      $rgDao = new CRM_Dedupe_DAO_RuleGroup();
      $rgDao->id = $this->_rgid;
      $rgDao->find(TRUE);

      $this->_defaults['threshold'] = $rgDao->threshold;
      $this->_contactType = $rgDao->contact_type;
      $this->_defaults['used'] = $rgDao->used;
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
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addField('title', ['label' => ts('Rule Name')], TRUE);
    $this->addRule('title', ts('A duplicate matching rule with this name already exists. Please select another name.'),
      'objectExists', ['CRM_Dedupe_DAO_RuleGroup', $this->_rgid, 'title']
    );

    $this->addField('used', ['label' => ts('Usage')], TRUE);
    $disabled = [];
    $reserved = $this->addField('is_reserved', ['label' => ts('Reserved?')]);
    if (!empty($this->_defaults['is_reserved'])) {
      $reserved->freeze();
    }

    $attributes = ['class' => 'two'];
    if (!empty($disabled)) {
      $attributes = array_merge($attributes, $disabled);
    }

    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      $this->add('select', "where_$count", ts('Field'),
        [
          NULL => ts('- none -'),
        ] + $this->_fields, FALSE, $disabled
      );
      $this->addField("length_$count", ['entity' => 'Rule', 'name' => 'rule_length'] + $attributes);
      $this->addField("weight_$count", ['entity' => 'Rule', 'name' => 'rule_weight'] + $attributes);
    }

    $this->addField('threshold', ['label' => ts("Weight Threshold to Consider Contacts 'Matching':")] + $attributes);

    $this->assign('contact_type', $this->_contactType);

    $this->addFormRule(['CRM_Contact_Form_DedupeRules', 'formRule'], $this);

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $fieldSelected = FALSE;
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (!empty($fields["where_$count"]) || (isset($self->_defaults['is_reserved']) && !empty($self->_defaults["where_$count"]))) {
        $fieldSelected = TRUE;
        break;
      }
    }
    if (empty($fields['threshold'])) {
      // CRM-20607 - Don't validate the threshold of hard-coded rules
      if (!(CRM_Utils_Array::value('is_reserved', $fields) &&
        CRM_Utils_File::isIncludable("CRM/Dedupe/BAO/QueryBuilder/{$self->_defaultValues['name']}.php"))) {
        $errors['threshold'] = ts('Threshold weight cannot be empty or zero.');
      }
    }

    if (!$fieldSelected) {
      $errors['_qf_default'] = ts('Please select at least one field.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  /**
   * @return array
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();

    //FIXME: Handle logic to replace is_default column by usage
    // reset used column to General (since there can only
    // be one 'Supervised' or 'Unsupervised' rule)
    if ($values['used'] != 'General') {
      $query = "
UPDATE civicrm_dedupe_rule_group
   SET used = 'General'
 WHERE contact_type = %1
   AND used = %2";
      $queryParams = [
        1 => [$this->_contactType, 'String'],
        2 => [$values['used'], 'String'],
      ];

      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $rgDao = new CRM_Dedupe_DAO_RuleGroup();
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $rgDao->id = $this->_rgid;
    }

    $rgDao->title = $values['title'];
    $rgDao->is_reserved = CRM_Utils_Array::value('is_reserved', $values, FALSE);
    $rgDao->used = $values['used'];
    $rgDao->contact_type = $this->_contactType;
    $rgDao->threshold = $values['threshold'];
    $rgDao->save();

    // make sure name is set only during insert
    if ($this->_action & CRM_Core_Action::ADD) {
      // generate name based on title
      $rgDao->name = CRM_Utils_String::titleToVar($values['title']) . "_{$rgDao->id}";
      $rgDao->save();
    }

    // lets skip updating of fields for reserved dedupe group
    if (CRM_Utils_Array::value('is_reserved', $this->_defaults)) {
      CRM_Core_Session::setStatus(ts("The rule '%1' has been saved.", [1 => $rgDao->title]), ts('Saved'), 'success');
      return;
    }

    $ruleDao = new CRM_Dedupe_DAO_Rule();
    $ruleDao->dedupe_rule_group_id = $rgDao->id;
    $ruleDao->delete();
    $ruleDao->free();

    $substrLenghts = [];

    $tables = [];
    $daoObj = new CRM_Core_DAO();
    $database = $daoObj->database();
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (empty($values["where_$count"])) {
        continue;
      }
      list($table, $field) = explode('.', CRM_Utils_Array::value("where_$count", $values));
      $length = !empty($values["length_$count"]) ? CRM_Utils_Array::value("length_$count", $values) : NULL;
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
          $tables[$table] = [];
        }
        $tables[$table][] = $field;
      }

      // CRM-6245: we must pass table/field/length triples to the createIndexes() call below
      if ($length) {
        if (!isset($substrLenghts[$table])) {
          $substrLenghts[$table] = [];
        }

        //CRM-13417 to avoid fatal error "Incorrect prefix key; the used key part isn't a string, the used length is longer than the key part, or the storage engine doesn't support unique prefix keys, 1089"
        $schemaQuery = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = '{$database}' AND
          TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$field}';";
        $dao = CRM_Core_DAO::executeQuery($schemaQuery);

        if ($dao->fetch()) {
          // set the length to null for all the fields where prefix length is not supported. eg. int,tinyint,date,enum etc dataTypes.
          if ($dao->COLUMN_NAME == $field && !in_array($dao->DATA_TYPE, [
                'char',
                'varchar',
                'binary',
                'varbinary',
                'text',
                'blob',
              ])
          ) {
            $length = NULL;
          }
          elseif ($dao->COLUMN_NAME == $field && !empty($dao->CHARACTER_MAXIMUM_LENGTH) && ($length > $dao->CHARACTER_MAXIMUM_LENGTH)) {
            //set the length to CHARACTER_MAXIMUM_LENGTH in case the length provided by the user is greater than the limit
            $length = $dao->CHARACTER_MAXIMUM_LENGTH;
          }
        }
        $substrLenghts[$table][$field] = $length;
      }
    }

    // also create an index for this dedupe rule
    // CRM-3837
    CRM_Utils_Hook::dupeQuery($ruleDao, 'dedupeIndexes', $tables);
    CRM_Core_BAO_SchemaHandler::createIndexes($tables, 'dedupe_index', $substrLenghts);

    //need to clear cache of deduped contacts
    //based on the previous rule
    $cacheKey = "merge {$this->_contactType}_{$this->_rgid}_%";

    CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey);

    CRM_Core_Session::setStatus(ts("The rule '%1' has been saved.", [1 => $rgDao->title]), ts('Saved'), 'success');
  }

}
