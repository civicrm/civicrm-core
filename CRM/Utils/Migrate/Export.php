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
class CRM_Utils_Migrate_Export {

  protected $_xml; function __construct() {
    $this->_xml = array(
      'customGroup' => array('data' => NULL,
        'name' => 'CustomGroup',
        'scope' => 'CustomGroups',
        'required' => FALSE,
        'map' => array(),
      ),
      'customField' => array(
        'data' => NULL,
        'name' => 'CustomField',
        'scope' => 'CustomFields',
        'required' => FALSE,
        'map' => array(),
      ),
      'optionGroup' => array(
        'data' => NULL,
        'name' => 'OptionGroup',
        'scope' => 'OptionGroups',
        'required' => FALSE,
        'map' => array(),
      ),
      'relationshipType' => array(
        'data' => NULL,
        'name' => 'RelationshipType',
        'scope' => 'RelationshipTypes',
        'required' => FALSE,
        'map' => array(),
      ),
      'locationType' => array(
        'data' => NULL,
        'name' => 'LocationType',
        'scope' => 'LocationTypes',
        'required' => FALSE,
        'map' => array(),
      ),
      'optionValue' => array(
        'data' => NULL,
        'name' => 'OptionValue',
        'scope' => 'OptionValues',
        'required' => FALSE,
        'map' => array(),
      ),
      'profileGroup' => array(
        'data' => NULL,
        'name' => 'ProfileGroup',
        'scope' => 'ProfileGroups',
        'required' => FALSE,
        'map' => array(),
      ),
      'profileField' => array(
        'data' => NULL,
        'name' => 'ProfileField',
        'scope' => 'ProfileFields',
        'required' => FALSE,
        'map' => array(),
      ),
      'profileJoin' => array(
        'data' => NULL,
        'name' => 'ProfileJoin',
        'scope' => 'ProfileJoins',
        'required' => FALSE,
        'map' => array(),
      ),
      'mappingGroup' => array(
        'data' => NULL,
        'name' => 'MappingGroup',
        'scope' => 'MappingGroups',
        'required' => FALSE,
        'map' => array(),
      ),
      'mappingField' => array(
        'data' => NULL,
        'name' => 'MappingField',
        'scope' => 'MappingFields',
        'required' => FALSE,
        'map' => array(),
      ),
    );
  }

  function run() {
    // fetch the option group / values for
    // activity type and event_type

    $optionGroups = "( 'activity_type', 'event_type', 'mapping_type' )";

    $sql = "
SELECT distinct(g.id), g.*
FROM   civicrm_option_group g
WHERE  g.name IN $optionGroups
";
    $this->fetch('optionGroup',
      'CRM_Core_DAO_OptionGroup',
      $sql,
      array('id', 'name')
    );

    $sql = "
SELECT distinct(g.id), g.*
FROM   civicrm_option_group g,
       civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  f.option_group_id = g.id
AND    f.custom_group_id = cg.id
AND    cg.is_active = 1
";
    $this->fetch('optionGroup',
      'CRM_Core_DAO_OptionGroup',
      $sql,
      array('id', 'name')
    );

    $sql = "
SELECT v.*, g.name as prefix
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
AND    g.name IN $optionGroups
";

    $this->fetch('optionValue',
      'CRM_Core_DAO_OptionValue',
      $sql,
      array('value', 'name', 'prefix'),
      array(array('optionGroup', 'option_group_id', 'option_group_name'))
    );

    $sql = "
SELECT distinct(v.id), v.*, g.name as prefix
FROM   civicrm_option_value v,
       civicrm_option_group g,
       civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  v.option_group_id = g.id
AND    f.option_group_id = g.id
AND    f.custom_group_id = cg.id
AND    cg.is_active = 1
";

    $this->fetch('optionValue',
      'CRM_Core_DAO_OptionValue',
      $sql,
      array('id', 'name', 'prefix'),
      array(array('optionGroup', 'option_group_id', 'option_group_name'))
    );

    $sql = "
SELECT rt.*
FROM   civicrm_relationship_type rt
WHERE  rt.is_active = 1
";
    $this->fetch('relationshipType',
      'CRM_Contact_DAO_RelationshipType',
      $sql,
      array('id', 'name_a_b')
    );


    $sql = "
SELECT lt.*
FROM   civicrm_location_type lt
WHERE  lt.is_active = 1
";
    $this->fetch('locationType',
      'CRM_Core_DAO_LocationType',
      $sql,
      array('id', 'name')
    );


    $sql = "
SELECT cg.*
FROM   civicrm_custom_group cg
WHERE  cg.is_active = 1
";
    $this->fetch('customGroup',
      'CRM_Core_DAO_CustomGroup',
      $sql,
      array('id', 'name')
    );

    $sql = "
SELECT f.*
FROM   civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  f.custom_group_id = cg.id
AND    cg.is_active = 1
";
    $this->fetch('customField',
      'CRM_Core_DAO_CustomField',
      $sql,
      array('id', 'column_name'),
      array(array('optionGroup', 'option_group_id', 'option_group_name'),
        array('customGroup', 'custom_group_id', 'custom_group_name'),
      )
    );

    $this->fetch('profileGroup',
      'CRM_Core_DAO_UFGroup',
      NULL,
      array('id', 'title'),
      NULL
    );

    $this->fetch('profileField',
      'CRM_Core_DAO_UFField',
      NULL,
      NULL,
      array(array('profileGroup', 'uf_group_id', 'profile_group_name'))
    );

    $sql = "
SELECT *
FROM   civicrm_uf_join
WHERE  entity_table IS NULL
AND    entity_id    IS NULL
";
    $this->fetch('profileJoin',
      'CRM_Core_DAO_UFJoin',
      $sql,
      NULL,
      array(array('profileGroup', 'uf_group_id', 'profile_group_name'))
    );

    $this->fetch('mappingGroup',
      'CRM_Core_DAO_Mapping',
      NULL,
      array('id', 'name'),
      array(array('optionValue', 'mapping_type_id', 'mapping_type_name', 'mapping_type'))
    );

    $this->fetch('mappingField',
      'CRM_Core_DAO_MappingField',
      NULL,
      NULL,
      array(array('mappingGroup', 'mapping_id', 'mapping_group_name'),
        array('locationType', 'location_type_id', 'location_type_name'),
        array('relationshipType', 'relationship_type_id', 'relationship_type_name'),
      )
    );

    $buffer = '<?xml version="1.0" encoding="iso-8859-1" ?>';
    $buffer .= "\n\n<CustomData>\n";
    foreach (array_keys($this->_xml) as $key) {
      if (!empty($this->_xml[$key]['data'])) {
        $buffer .= "  <{$this->_xml[$key]['scope']}>\n{$this->_xml[$key]['data']}  </{$this->_xml[$key]['scope']}>\n";
      }
      elseif ($this->_xml[$key]['required']) {
        CRM_Core_Error::fatal("No records in DB for $key");
      }
    }
    $buffer .= "</CustomData>\n";

    CRM_Utils_System::download('CustomGroupData.xml', 'text/plain', $buffer);
  }

  function fetch($groupName, $daoName, $sql = NULL, $map = NULL, $add = NULL) {
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . '.php');

    eval("\$dao = new $daoName( );");
    if ($sql) {
      $dao->query($sql);
    }
    else {
      $dao->find();
    }

    while ($dao->fetch()) {
      $additional = NULL;
      if ($add) {
        foreach ($add as $filter) {
          if (isset($dao->{$filter[1]})) {
            if (isset($filter[3])) {
              $label = $this->_xml[$filter[0]]['map']["{$filter[3]}." . $dao->{$filter[1]}];
            }
            else {
              $label = $this->_xml[$filter[0]]['map'][$dao->{$filter[1]}];
            }
            $additional .= "\n      <{$filter[2]}>{$label}</{$filter[2]}>";
          }
        }
      }
      $this->_xml[$groupName]['data'] .= $this->exportDAO($dao,
        $this->_xml[$groupName]['name'],
        $additional
      );
      if ($map) {
        if (isset($map[2])) {
          $this->_xml[$groupName]['map'][$dao->{$map[2]} . '.' . $dao->{$map[0]}] = $dao->{$map[1]};
        }
        else {
          $this->_xml[$groupName]['map'][$dao->{$map[0]}] = $dao->{$map[1]};
        }
      }
    }
  }

  function exportDAO($object, $objectName, $additional = NULL) {
    $dbFields = &$object->fields();

    $xml = "    <$objectName>";
    foreach ($dbFields as $name => $dontCare) {
      // ignore all ids
      if ($name == 'id' ||
        substr($name, -3, 3) == '_id'
      ) {
        continue;
      }
      if (isset($object->$name) &&
        $object->$name !== NULL
      ) {
        // hack for extends_entity_column_value
        if ($name == 'extends_entity_column_value') {
          if ($object->extends == 'Event' ||
            $object->extends == 'Activity' ||
            $object->extends == 'Relationship'
          ) {
            if ($object->extends == 'Event') {
              $key = 'event_type';
            }
            elseif ($object->extends == 'Activity') {
              $key = 'activity_type';
            }
            elseif ($object->extends == 'Relationship') {
              $key = 'relationship_type';
            }
            $xml .= "\n      <extends_entity_column_value_option_group>$key</extends_entity_column_value_option_group>";
            $types = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              substr($object->$name, 1, -1)
            );
            $value = array();
            foreach ($types as $type) {
              $values[] = $this->_xml['optionValue']['map']["$key.{$type}"];
            }
            $value = implode(',', $values);
            $xml .= "\n      <extends_entity_column_value_option_value>$value</extends_entity_column_value_option_value>";
          }
          else {
            echo "This extension: {$object->extends} is not yet handled";
            exit();
          }
        }
        if ($name == 'field_name') {
          $value = $object->$name;
          // hack for profile field_name
          if (substr($value, 0, 7) == 'custom_') {
            $cfID = substr($value, 7);
            list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($cfID);
            $value = "custom.{$tableName}.{$columnName}";
          }
          $xml .= "\n      <$name>$value</$name>";
        }
        else {
          $value = str_replace(CRM_Core_DAO::VALUE_SEPARATOR,
            ":;:;:;",
            $object->$name
          );
          $xml .= "\n      <$name>$value</$name>";
        }
      }
    }
    if ($additional) {
      $xml .= $additional;
    }
    $xml .= "\n    </$objectName>\n";
    return $xml;
  }
}

