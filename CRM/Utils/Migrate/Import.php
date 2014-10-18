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
 * $Id$
 *
 */
class CRM_Utils_Migrate_Import {
  /**
   *
   */
  function __construct() {
  }

  /**
   * Import custom-data from an XML file
   *
   * @param string $file path to an XML file
   * @throws CRM_Core_Exception
   * @return void;
   */
  function run($file) {
    // read xml file
    $dom = new DomDocument();
    if (! $dom->load($file)) {
      throw new CRM_Core_Exception("Failed to parse XML file \"$file\"");
    }
    $dom->xinclude();
    $xml = simplexml_import_dom($dom);
    return $this->runXmlElement($xml);
  }

  /**
   * Import custom-data from an XML element
   *
   * @param SimpleXMLElement $xml
   * @return void
   */
  function runXmlElement($xml) {
    $idMap = array(
      'custom_group' => array(),
      'option_group' => array(),
    );

    // first create option groups and values if any
    $this->optionGroups($xml, $idMap);
    $this->optionValues($xml, $idMap);

    $this->relationshipTypes($xml);
    $this->contributionTypes($xml);

    // now create custom groups
    $this->customGroups($xml, $idMap);
    $this->customFields($xml, $idMap);

    // now create profile groups
    $this->profileGroups($xml, $idMap);
    $this->profileFields($xml, $idMap);
    $this->profileJoins($xml, $idMap);

    //create DB Template String sample data
    $this->dbTemplateString($xml, $idMap);

    // clean up all caches etc
    CRM_Core_Config::clearDBCache();
  }

  /**
   * @param $dao
   * @param $xml
   * @param bool $save
   * @param null $keyName
   *
   * @return bool
   */
  function copyData(&$dao, &$xml, $save = FALSE, $keyName = NULL) {
    if ($keyName) {
      if (isset($xml->$keyName)) {
        $dao->$keyName = (string ) $xml->$keyName;
        if ($dao->find(TRUE)) {
          CRM_Core_Session::setStatus(ts("Found %1, %2, %3",
            array(
              1 => $keyName,
              2 => $dao->$keyName,
              3 => $dao->__table
            )
          ), '', 'info');
          return FALSE;
        }
      }
    }

    $fields = & $dao->fields();
    foreach ($fields as $name => $dontCare) {
      if (isset($xml->$name)) {
        $value = (string ) $xml->$name;
        $value = str_replace(CRM_Utils_Migrate_Export::XML_VALUE_SEPARATOR,
          CRM_Core_DAO::VALUE_SEPARATOR,
          $value
        );
        $dao->$name = $value;
      }
    }
    if ($save) {
      $dao->save();
    }
    return TRUE;
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function optionGroups(&$xml, &$idMap) {
    foreach ($xml->OptionGroups as $optionGroupsXML) {
      foreach ($optionGroupsXML->OptionGroup as $optionGroupXML) {
        $optionGroup = new CRM_Core_DAO_OptionGroup();
        $this->copyData($optionGroup, $optionGroupXML, TRUE, 'name');
        $idMap['option_group'][$optionGroup->name] = $optionGroup->id;
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function optionValues(&$xml, &$idMap) {
    foreach ($xml->OptionValues as $optionValuesXML) {
      foreach ($optionValuesXML->OptionValue as $optionValueXML) {
        $optionValue = new CRM_Core_DAO_OptionValue();
        $optionValue->option_group_id = $idMap['option_group'][(string ) $optionValueXML->option_group_name];
        $this->copyData($optionValue, $optionValueXML, FALSE, 'label');
        if (!isset($optionValue->value)) {
          $sql = "
SELECT     MAX(ROUND(v.value)) + 1
FROM       civicrm_option_value v
WHERE      v.option_group_id = %1
";
          $params = array(1 => array($optionValue->option_group_id, 'Integer'));
          $optionValue->value = CRM_Core_DAO::singleValueQuery($sql, $params);
        }
        $optionValue->save();
      }
    }
  }

  /**
   * @param $xml
   */
  function relationshipTypes(&$xml) {

    foreach ($xml->RelationshipTypes as $relationshipTypesXML) {
      foreach ($relationshipTypesXML->RelationshipType as $relationshipTypeXML) {
        $relationshipType = new CRM_Contact_DAO_RelationshipType();
        $this->copyData($relationshipType, $relationshipTypeXML, TRUE, 'name_a_b');
      }
    }
  }

  /**
   * @param $xml
   */
  function contributionTypes(&$xml) {

    foreach ($xml->ContributionTypes as $contributionTypesXML) {
      foreach ($contributionTypesXML->ContributionType as $contributionTypeXML) {
        $contributionType = new CRM_Financial_DAO_FinancialType();
        $this->copyData($contributionType, $contributionTypeXML, TRUE, 'name');
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function customGroups(&$xml, &$idMap) {
    foreach ($xml->CustomGroups as $customGroupsXML) {
      foreach ($customGroupsXML->CustomGroup as $customGroupXML) {
        $customGroup = new CRM_Core_DAO_CustomGroup();
        if (!$this->copyData($customGroup, $customGroupXML, TRUE, 'name')) {
          $idMap['custom_group'][$customGroup->name] = $customGroup->id;
          continue;
        }

        $saveAgain = FALSE;
        if (!isset($customGroup->table_name) ||
          empty($customGroup->table_name)
        ) {
          // fix table name
          $customGroup->table_name = "civicrm_value_" . strtolower(CRM_Utils_String::munge($customGroup->title, '_', 32)) . "_{$customGroup->id}";

          $saveAgain = TRUE;
        }

        // fix extends stuff if it exists
        if (isset($customGroupXML->extends_entity_column_value_option_group) &&
          isset($customGroupXML->extends_entity_column_value)) {
          $valueIDs = array();
          $optionValues = explode(",", $customGroupXML->extends_entity_column_value);
          $optValues = implode("','", $optionValues);
          if (trim($customGroup->extends) != 'Participant') {
            if ($customGroup->extends == 'Relationship') {
              foreach ($optionValues as $key => $value) {
                $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_RelationshipType', $value, 'id', 'name_a_b');
                $valueIDs[] = $relTypeId;
              }
            }
            elseif (in_array($customGroup->extends, array('Individual', 'Organization', 'Household'))) {
              $valueIDs = $optionValues;
            }
            else {
              $sql = "
SELECT     v.value
FROM       civicrm_option_value v
INNER JOIN civicrm_option_group g ON g.id = v.option_group_id
WHERE      g.name = %1
AND        v.name IN ('$optValues')
";
              $params = array(
                1 => array(
                  (string ) $customGroupXML->extends_entity_column_value_option_group,
                  'String',
                ),
              );
              $dao = & CRM_Core_DAO::executeQuery($sql, $params);

              while ($dao->fetch()) {
                $valueIDs[] = $dao->value;
              }
            }
            if (!empty($valueIDs)) {
              $customGroup->extends_entity_column_value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                $valueIDs
              ) . CRM_Core_DAO::VALUE_SEPARATOR;

              unset($valueIDs);

              // Note: No need to set extends_entity_column_id here.

              $saveAgain = TRUE;
            }
          }
          else {
            // when custom group extends 'Participant'
            $sql = "
SELECT     v.value
FROM       civicrm_option_value v
INNER JOIN civicrm_option_group g ON g.id = v.option_group_id
WHERE      g.name = 'custom_data_type'
AND        v.name = %1
";
            $params = array(
              1 => array(
                (string ) $customGroupXML->extends_entity_column_value_option_group,
                'String',
              )
            );
            $valueID = (int ) CRM_Core_DAO::singleValueQuery($sql, $params);
            if ($valueID) {
              $customGroup->extends_entity_column_id = $valueID;
            }

            $optionIDs = array();
            switch ($valueID) {
              case 1:
                // ParticipantRole
                $condition = "AND v.name IN ( '{$optValues}' )";
                $optionIDs = CRM_Core_OptionGroup::values('participant_role', FALSE, FALSE, FALSE, $condition, 'name');
                break;

              case 2:
                // ParticipantEventName
                $condition = "( is_template IS NULL OR is_template != 1 ) AND title IN( '{$optValues}' )";
                $optionIDs = CRM_Event_PseudoConstant::event(NULL, FALSE, $condition);
                break;

              case 3:
                // ParticipantEventType
                $condition = "AND v.name IN ( '{$optValues}' )";
                $optionIDs = CRM_Core_OptionGroup::values('event_type', FALSE, FALSE, FALSE, $condition, 'name');
                break;
            }

            if (is_array($optionIDs) && !empty($optionIDs)) {
              $customGroup->extends_entity_column_value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                array_keys($optionIDs)
              ) . CRM_Core_DAO::VALUE_SEPARATOR;

              $saveAgain = TRUE;
            }
          }
        }

        if ($saveAgain) {
          $customGroup->save();
        }

        CRM_Core_BAO_CustomGroup::createTable($customGroup);
        $idMap['custom_group'][$customGroup->name] = $customGroup->id;
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function customFields(&$xml, &$idMap) {
    // Re-index by group id so we can build out the custom fields one table
    // at a time, and then rebuild the table triggers at the end, rather than
    // rebuilding the table triggers after each field is added (which is
    // painfully slow).
    $fields_indexed_by_group_id = array();
    foreach ($xml->CustomFields as $customFieldsXML) {
      $total = count($customFieldsXML->CustomField);
      foreach ($customFieldsXML->CustomField as $customFieldXML) {
        $id = $idMap['custom_group'][(string ) $customFieldXML->custom_group_name];
        $fields_indexed_by_group_id[$id][] = $customFieldXML;
      }
    }
    while (list($group_id, $fields) = each($fields_indexed_by_group_id)) {
      $total = count($fields);
      $count = 0;
      while (list(, $customFieldXML) = each($fields)) {
        $count++;
        $customField = new CRM_Core_DAO_CustomField();
        $customField->custom_group_id = $group_id;
        $skipStore = FALSE;
        if (!$this->copyData($customField, $customFieldXML, FALSE, 'label')) {
          $skipStore = TRUE;
        }

        if (empty($customField->option_group_id) &&
          isset($customFieldXML->option_group_name)
        ) {
          $customField->option_group_id = $idMap['option_group'][(string ) $customFieldXML->option_group_name];
        }
        if ($skipStore) {
          continue;
        }
        $customField->save();

        // Only rebuild the table's trigger on the last field added to avoid un-necessary
        // and slow rebuilds when adding many fields at the same time.
        $triggerRebuild = FALSE;
        if ($count == $total) {
          $triggerRebuild = TRUE;
        }
        $indexExist = FALSE;
        CRM_Core_BAO_CustomField::createField($customField, 'add', $indexExist, $triggerRebuild);
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function dbTemplateString(&$xml, &$idMap) {
    foreach ($xml->Persistent as $persistentXML) {
      foreach ($persistentXML->Persistent as $persistent) {
        $persistentObj = new CRM_Core_DAO_Persistent();

        if ($persistent->is_config == 1) {
          $persistent->data = serialize(explode(',', $persistent->data));
        }
        $this->copyData($persistentObj, $persistent, TRUE, 'context');
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function profileGroups(&$xml, &$idMap) {
    foreach ($xml->ProfileGroups as $profileGroupsXML) {
      foreach ($profileGroupsXML->ProfileGroup as $profileGroupXML) {
        $profileGroup = new CRM_Core_DAO_UFGroup();
        $this->copyData($profileGroup, $profileGroupXML, TRUE, 'title');
        $idMap['profile_group'][$profileGroup->name] = $profileGroup->id;
        $idMap['profile_group'][$profileGroup->title] = $profileGroup->id;
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   *
   * @throws Exception
   */
  function profileFields(&$xml, &$idMap) {
    foreach ($xml->ProfileFields as $profileFieldsXML) {
      foreach ($profileFieldsXML->ProfileField as $profileFieldXML) {
        $profileField = new CRM_Core_DAO_UFField();
        $profileField->uf_group_id = $idMap['profile_group'][(string ) $profileFieldXML->profile_group_name];
        $this->copyData($profileField, $profileFieldXML, FALSE, 'field_name');

        // fix field name
        if (substr($profileField->field_name, 0, 7) == 'custom.') {
          list($dontCare, $tableName, $columnName) = explode('.', $profileField->field_name);
          $sql = "
SELECT     f.id
FROM       civicrm_custom_field f
INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id
WHERE      g.table_name  = %1
AND        f.column_name = %2
";
          $params = array(
            1 => array($tableName, 'String'),
            2 => array($columnName, 'String'),
          );
          $cfID = CRM_Core_DAO::singleValueQuery($sql, $params);
          if (!$cfID) {
            CRM_Core_Error::fatal(ts("Could not find custom field for %1, %2, %3",
              array(
                1 => $profileField->field_name,
                2 => $tableName,
                3 => $columnName
              )
            ) . "<br />");
          }
          $profileField->field_name = "custom_{$cfID}";
        }
        $profileField->save();
      }
    }
  }

  /**
   * @param $xml
   * @param $idMap
   */
  function profileJoins(&$xml, &$idMap) {
    foreach ($xml->ProfileJoins as $profileJoinsXML) {
      foreach ($profileJoinsXML->ProfileJoin as $profileJoinXML) {
        $profileJoin = new CRM_Core_DAO_UFJoin();
        $profileJoin->uf_group_id = $idMap['profile_group'][(string ) $profileJoinXML->profile_group_name];
        $this->copyData($profileJoin, $profileJoinXML, FALSE, 'module');
        $profileJoin->save();
      }
    }
  }
}

