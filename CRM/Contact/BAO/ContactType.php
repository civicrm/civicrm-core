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
class CRM_Contact_BAO_ContactType extends CRM_Contact_DAO_ContactType {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contact_BAO_ContactType|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $contactType = new CRM_Contact_DAO_ContactType();
    $contactType->copyValues($params);
    if ($contactType->find(TRUE)) {
      CRM_Core_DAO::storeValues($contactType, $defaults);
      return $contactType;
    }
    return NULL;
  }

  /**
   * Is this contact type active.
   *
   * @param string $contactType
   *
   * @return bool
   */
  public static function isActive($contactType) {
    $contact = self::contactTypeInfo(FALSE);
    $active = array_key_exists($contactType, $contact) ? TRUE : FALSE;
    return $active;
  }

  /**
   * Retrieve basic contact type information.
   *
   * @param bool $all
   *
   * @return array
   *   Array of basic contact types information.
   */
  public static function basicTypeInfo($all = FALSE) {
    static $_cache = NULL;

    if ($_cache === NULL) {
      $_cache = array();
    }

    $argString = $all ? 'CRM_CT_BTI_1' : 'CRM_CT_BTI_0';
    if (!array_key_exists($argString, $_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $_cache[$argString] = $cache->get($argString);
      if (!$_cache[$argString]) {
        $sql = "
SELECT *
FROM   civicrm_contact_type
WHERE  parent_id IS NULL
";
        if ($all === FALSE) {
          $sql .= " AND is_active = 1";
        }

        $dao = CRM_Core_DAO::executeQuery($sql,
          CRM_Core_DAO::$_nullArray,
          FALSE,
          'CRM_Contact_DAO_ContactType'
        );
        while ($dao->fetch()) {
          $value = array();
          CRM_Core_DAO::storeValues($dao, $value);
          $_cache[$argString][$dao->name] = $value;
        }

        $cache->set($argString, $_cache[$argString]);
      }
    }
    return $_cache[$argString];
  }

  /**
   * Retrieve all basic contact types.
   *
   * @param bool $all
   *
   * @return array
   *   Array of basic contact types
   */
  public static function basicTypes($all = FALSE) {
    return array_keys(self::basicTypeInfo($all));
  }

  /**
   * @param bool $all
   * @param string $key
   *
   * @return array
   */
  public static function basicTypePairs($all = FALSE, $key = 'name') {
    $subtypes = self::basicTypeInfo($all);

    $pairs = array();
    foreach ($subtypes as $name => $info) {
      $index = ($key == 'name') ? $name : $info[$key];
      $pairs[$index] = $info['label'];
    }
    return $pairs;
  }

  /**
   * Retrieve all subtypes Information.
   *
   * @param array $contactType
   *   ..
   * @param bool $all
   * @param bool $ignoreCache
   * @param bool $reset
   *
   * @return array
   *   Array of sub type information
   */
  public static function subTypeInfo($contactType = NULL, $all = FALSE, $ignoreCache = FALSE, $reset = FALSE) {
    static $_cache = NULL;

    if ($reset === TRUE) {
      $_cache = NULL;
    }

    if ($_cache === NULL) {
      $_cache = array();
    }
    if ($contactType && !is_array($contactType)) {
      $contactType = array($contactType);
    }

    $argString = $all ? 'CRM_CT_STI_1_' : 'CRM_CT_STI_0_';
    if (!empty($contactType)) {
      $argString .= implode('_', $contactType);
    }

    if ((!array_key_exists($argString, $_cache)) || $ignoreCache) {
      $cache = CRM_Utils_Cache::singleton();
      $_cache[$argString] = $cache->get($argString);
      if (!$_cache[$argString] || $ignoreCache) {
        $_cache[$argString] = array();

        $ctWHERE = '';
        if (!empty($contactType)) {
          $ctWHERE = " AND parent.name IN ('" . implode("','", $contactType) . "')";
        }

        $sql = "
SELECT subtype.*, parent.name as parent, parent.label as parent_label
FROM   civicrm_contact_type subtype
INNER JOIN civicrm_contact_type parent ON subtype.parent_id = parent.id
WHERE  subtype.name IS NOT NULL AND subtype.parent_id IS NOT NULL {$ctWHERE}
";
        if ($all === FALSE) {
          $sql .= " AND subtype.is_active = 1 AND parent.is_active = 1 ORDER BY parent.id";
        }
        $dao = CRM_Core_DAO::executeQuery($sql, array(),
          FALSE, 'CRM_Contact_DAO_ContactType'
        );
        while ($dao->fetch()) {
          $value = array();
          CRM_Core_DAO::storeValues($dao, $value);
          $value['parent'] = $dao->parent;
          $value['parent_label'] = $dao->parent_label;
          $_cache[$argString][$dao->name] = $value;
        }

        $cache->set($argString, $_cache[$argString]);
      }
    }
    return $_cache[$argString];
  }

  /**
   *
   *   retrieve all subtypes
   *
   * @param array $contactType
   *   ..
   * @param bool $all
   * @param string $columnName
   * @param bool $ignoreCache
   *
   * @return array
   *   all subtypes OR list of subtypes associated to
   *   a given basic contact type
   */
  public static function subTypes($contactType = NULL, $all = FALSE, $columnName = 'name', $ignoreCache = FALSE) {
    if ($columnName == 'name') {
      return array_keys(self::subTypeInfo($contactType, $all, $ignoreCache));
    }
    else {
      return array_values(self::subTypePairs($contactType, FALSE, NULL, $ignoreCache));
    }
  }

  /**
   *
   * retrieve subtype pairs with name as 'subtype-name' and 'label' as value
   *
   * @param array $contactType
   * @param bool $all
   * @param string $labelPrefix
   * @param bool $ignoreCache
   *
   * @return array
   *   list of subtypes with name as 'subtype-name' and 'label' as value
   */
  public static function subTypePairs($contactType = NULL, $all = FALSE, $labelPrefix = '- ', $ignoreCache = FALSE) {
    $subtypes = self::subTypeInfo($contactType, $all, $ignoreCache);

    $pairs = array();
    foreach ($subtypes as $name => $info) {
      $pairs[$name] = $labelPrefix . $info['label'];
    }
    return $pairs;
  }

  /**
   *
   * retrieve list of all types i.e basic + subtypes.
   *
   * @param bool $all
   *
   * @return array
   *   Array of basic types + all subtypes.
   */
  public static function contactTypes($all = FALSE) {
    return array_keys(self::contactTypeInfo($all));
  }

  /**
   * Retrieve info array about all types i.e basic + subtypes.
   *
   * @param bool $all
   * @param bool $reset
   *
   * @return array
   *   Array of basic types + all subtypes.
   */
  public static function contactTypeInfo($all = FALSE, $reset = FALSE) {
    static $_cache = NULL;

    if ($reset === TRUE) {
      $_cache = NULL;
    }

    if ($_cache === NULL) {
      $_cache = array();
    }

    $argString = $all ? 'CRM_CT_CTI_1' : 'CRM_CT_CTI_0';
    if (!array_key_exists($argString, $_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $_cache[$argString] = $cache->get($argString);
      if (!$_cache[$argString]) {
        $_cache[$argString] = array();

        $sql = "
SELECT type.*, parent.name as parent, parent.label as parent_label
FROM      civicrm_contact_type type
LEFT JOIN civicrm_contact_type parent ON type.parent_id = parent.id
WHERE  type.name IS NOT NULL
";
        if ($all === FALSE) {
          $sql .= " AND type.is_active = 1";
        }

        $dao = CRM_Core_DAO::executeQuery($sql,
          array(),
          FALSE,
          'CRM_Contact_DAO_ContactType'
        );
        while ($dao->fetch()) {
          $value = array();
          CRM_Core_DAO::storeValues($dao, $value);
          if (array_key_exists('parent_id', $value)) {
            $value['parent'] = $dao->parent;
            $value['parent_label'] = $dao->parent_label;
          }
          $_cache[$argString][$dao->name] = $value;
        }

        $cache->set($argString, $_cache[$argString]);
      }
    }

    return $_cache[$argString];
  }

  /**
   * Retrieve basic type pairs with name as 'built-in name' and 'label' as value.
   *
   * @param bool $all
   * @param null $typeName
   * @param null $delimiter
   *
   * @return array
   *   Array of basictypes with name as 'built-in name' and 'label' as value
   */
  public static function contactTypePairs($all = FALSE, $typeName = NULL, $delimiter = NULL) {
    $types = self::contactTypeInfo($all);

    if ($typeName && !is_array($typeName)) {
      $typeName = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($typeName, CRM_Core_DAO::VALUE_SEPARATOR));
    }

    $pairs = array();
    if ($typeName) {
      foreach ($typeName as $type) {
        if (array_key_exists($type, $types)) {
          $pairs[$type] = $types[$type]['label'];
        }
      }
    }
    else {
      foreach ($types as $name => $info) {
        $pairs[$name] = $info['label'];
      }
    }

    return !$delimiter ? $pairs : implode($delimiter, $pairs);
  }

  /**
   * Get a list of elements for select box.
   * Note that this used to default to using the hex(01) character - which results in an invalid character being used in form fields
   * which was not handled well be anything that loaded & resaved the html (outside core)
   * The use of this separator is now explicit in the calling functions as a step towards it's removal
   *
   * @param bool $all
   * @param bool $isSeparator
   * @param string $separator
   *
   * @return mixed
   */
  public  static function getSelectElements(
    $all = FALSE,
    $isSeparator = TRUE,
    $separator = '__'
  ) {
    static $_cache = NULL;

    if ($_cache === NULL) {
      $_cache = array();
    }

    $argString = $all ? 'CRM_CT_GSE_1' : 'CRM_CT_GSE_0';
    $argString .= $isSeparator ? '_1' : '_0';
    $argString .= $separator;
    if (!array_key_exists($argString, $_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $_cache[$argString] = $cache->get($argString);

      if (!$_cache[$argString]) {
        $_cache[$argString] = array();

        $sql = "
SELECT    c.name as child_name , c.label as child_label , c.id as child_id,
          p.name as parent_name, p.label as parent_label, p.id as parent_id
FROM      civicrm_contact_type c
LEFT JOIN civicrm_contact_type p ON ( c.parent_id = p.id )
WHERE     ( c.name IS NOT NULL )
";

        if ($all === FALSE) {
          $sql .= "
AND   c.is_active = 1
AND   ( p.is_active = 1 OR p.id IS NULL )
";
        }
        $sql .= " ORDER BY c.id";

        $values = array();
        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
          if (!empty($dao->parent_id)) {
            $key = $isSeparator ? $dao->parent_name . $separator . $dao->child_name : $dao->child_name;
            $label = "- {$dao->child_label}";
            $pName = $dao->parent_name;
          }
          else {
            $key = $dao->child_name;
            $label = $dao->child_label;
            $pName = $dao->child_name;
          }

          if (!isset($values[$pName])) {
            $values[$pName] = array();
          }
          $values[$pName][] = array('key' => $key, 'label' => $label);
        }

        $selectElements = array();
        foreach ($values as $pName => $elements) {
          foreach ($elements as $element) {
            $selectElements[$element['key']] = $element['label'];
          }
        }
        $_cache[$argString] = $selectElements;

        $cache->set($argString, $_cache[$argString]);
      }
    }
    return $_cache[$argString];
  }

  /**
   * Check if a given type is a subtype.
   *
   * @param string $subType
   *   Contact subType.
   * @param bool $ignoreCache
   *
   * @return bool
   *   true if subType, false otherwise.
   */
  public static function isaSubType($subType, $ignoreCache = FALSE) {
    return in_array($subType, self::subTypes(NULL, TRUE, 'name', $ignoreCache));
  }

  /**
   * Retrieve the basic contact type associated with given subType.
   *
   * @param array /string $subType contact subType.
   * @return array/string of basicTypes.
   */
  public static function getBasicType($subType) {
    static $_cache = NULL;
    if ($_cache === NULL) {
      $_cache = array();
    }

    $isArray = TRUE;
    if ($subType && !is_array($subType)) {
      $subType = array($subType);
      $isArray = FALSE;
    }
    $argString = implode('_', $subType);

    if (!array_key_exists($argString, $_cache)) {
      $_cache[$argString] = array();

      $sql = "
SELECT subtype.name as contact_subtype, type.name as contact_type
FROM   civicrm_contact_type subtype
INNER JOIN civicrm_contact_type type ON ( subtype.parent_id = type.id )
WHERE  subtype.name IN ('" . implode("','", $subType) . "' )";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        if (!$isArray) {
          $_cache[$argString] = $dao->contact_type;
          break;
        }
        $_cache[$argString][$dao->contact_subtype] = $dao->contact_type;
      }
    }
    return $_cache[$argString];
  }

  /**
   * Suppress all subtypes present in given array.
   *
   * @param array $subTypes
   *   Contact subTypes.
   * @param bool $ignoreCache
   *
   * @return array
   *   Array of suppressed subTypes.
   */
  public static function suppressSubTypes(&$subTypes, $ignoreCache = FALSE) {
    $subTypes = array_diff($subTypes, self::subTypes(NULL, TRUE, 'name', $ignoreCache));
    return $subTypes;
  }

  /**
   * Verify if a given subtype is associated with a given basic contact type.
   *
   * @param string $subType
   *   Contact subType.
   * @param string $contactType
   *   Contact Type.
   * @param bool $ignoreCache
   * @param string $columnName
   *
   * @return bool
   *   true if contact extends, false otherwise.
   */
  public static function isExtendsContactType($subType, $contactType, $ignoreCache = FALSE, $columnName = 'name') {
    $subType = (array) CRM_Utils_Array::explodePadded($subType);
    $subtypeList = self::subTypes($contactType, TRUE, $columnName, $ignoreCache);
    $intersection = array_intersect($subType, $subtypeList);
    return $subType == $intersection;
  }

  /**
   * Create shortcuts menu for contactTypes.
   *
   * @return array
   *   of contactTypes
   */
  public static function getCreateNewList() {
    $shortCuts = array();
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = self::getSelectElements(FALSE, TRUE, $separator);
    foreach ($contactTypes as $key => $value) {
      if ($key) {
        $typeValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $key);
        $cType = CRM_Utils_Array::value('0', $typeValue);
        $typeUrl = 'ct=' . $cType;
        if ($csType = CRM_Utils_Array::value('1', $typeValue)) {
          $typeUrl .= "&cst=$csType";
        }
        $shortCut = array(
          'path' => 'civicrm/contact/add',
          'query' => "$typeUrl&reset=1",
          'ref' => "new-$value",
          'title' => $value,
        );
        if ($csType = CRM_Utils_Array::value('1', $typeValue)) {
          $shortCuts[$cType]['shortCuts'][] = $shortCut;
        }
        else {
          $shortCuts[$cType] = $shortCut;
        }
      }
    }
    return $shortCuts;
  }

  /**
   * Delete Contact SubTypes.
   *
   * @param int $contactTypeId
   *   ID of the Contact Subtype to be deleted.
   *
   * @return bool
   */
  public static function del($contactTypeId) {

    if (!$contactTypeId) {
      return FALSE;
    }

    $params = array('id' => $contactTypeId);
    self::retrieve($params, $typeInfo);
    $name = $typeInfo['name'];
    // check if any custom group
    $custom = new CRM_Core_DAO_CustomGroup();
    $custom->whereAdd("extends_entity_column_value LIKE '%" .
      CRM_Core_DAO::VALUE_SEPARATOR .
      $name .
      CRM_Core_DAO::VALUE_SEPARATOR . "%'"
    );
    if ($custom->find()) {
      return FALSE;
    }

    // remove subtype for existing contacts
    $sql = "
UPDATE civicrm_contact SET contact_sub_type = NULL
WHERE contact_sub_type = '$name'";
    CRM_Core_DAO::executeQuery($sql);

    // remove subtype from contact type table
    $contactType = new CRM_Contact_DAO_ContactType();
    $contactType->id = $contactTypeId;
    $contactType->delete();

    // remove navigation entry if any
    if ($name) {
      $sql = "
DELETE
FROM civicrm_navigation
WHERE name = %1";
      $params = array(1 => array("New $name", 'String'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      CRM_Core_BAO_Navigation::resetNavigation();
    }
    return TRUE;
  }

  /**
   * Add or update Contact SubTypes.
   *
   * @param array $params
   *   An assoc array of name/value pairs.
   *
   * @return object|void
   */
  public static function add(&$params) {

    // label or name
    if (empty($params['id']) && empty($params['label'])) {
      return NULL;
    }
    if (!empty($params['parent_id']) &&
      !CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $params['parent_id'])
    ) {
      return NULL;
    }

    $contactType = new CRM_Contact_DAO_ContactType();
    $contactType->copyValues($params);
    $contactType->id = CRM_Utils_Array::value('id', $params);
    $contactType->is_active = CRM_Utils_Array::value('is_active', $params, 0);

    $contactType->save();
    if ($contactType->find(TRUE)) {
      $contactName = $contactType->name;
      $contact = ucfirst($contactType->label);
      $active = $contactType->is_active;
    }

    if (!empty($params['id'])) {
      $params = array('name' => "New $contactName");
      $newParams = array(
        'label' => "New $contact",
        'is_active' => $active,
      );
      CRM_Core_BAO_Navigation::processUpdate($params, $newParams);
    }
    else {
      $name = self::getBasicType($contactName);
      if (!$name) {
        return;
      }
      $value = array('name' => "New $name");
      CRM_Core_BAO_Navigation::retrieve($value, $navinfo);
      $navigation = array(
        'label' => "New $contact",
        'name' => "New $contactName",
        'url' => "civicrm/contact/add?ct=$name&cst=$contactName&reset=1",
        'permission' => 'add contacts',
        'parent_id' => $navinfo['id'],
        'is_active' => $active,
      );
      CRM_Core_BAO_Navigation::add($navigation);
    }
    CRM_Core_BAO_Navigation::resetNavigation();

    // reset the cache after adding
    self::subTypeInfo(NULL, FALSE, FALSE, TRUE);

    return $contactType;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    $params = array('id' => $id);
    self::retrieve($params, $contactinfo);
    $params = array('name' => "New $contactinfo[name]");
    $newParams = array('is_active' => $is_active);
    CRM_Core_BAO_Navigation::processUpdate($params, $newParams);
    CRM_Core_BAO_Navigation::resetNavigation();
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_ContactType', $id,
      'is_active', $is_active
    );
  }

  /**
   * @param string $typeName
   *
   * @return mixed
   */
  public static function getLabel($typeName) {
    $types = self::contactTypeInfo(TRUE);

    if (array_key_exists($typeName, $types)) {
      return $types[$typeName]['label'];
    }
    return $typeName;
  }

  /**
   * Check whether allow to change any contact's subtype
   * on the basis of custom data and relationship of specific subtype
   * currently used in contact/edit form amd in import validation
   *
   * @param int $contactId
   *   Contact id.
   * @param string $subType
   *   Subtype.
   *
   * @return bool
   */
  public static function isAllowEdit($contactId, $subType = NULL) {

    if (!$contactId) {
      return TRUE;
    }

    if (empty($subType)) {
      $subType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactId,
        'contact_sub_type'
      );
    }

    if (self::hasCustomData($subType, $contactId) || self::hasRelationships($contactId, $subType)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $contactType
   * @param int $contactId
   *
   * @return bool
   */
  public static function hasCustomData($contactType, $contactId = NULL) {
    $subTypeClause = '';

    if (self::isaSubType($contactType)) {
      $subType = $contactType;
      $contactType = self::getBasicType($subType);

      // check for empty custom data which extends subtype
      $subTypeValue = CRM_Core_DAO::VALUE_SEPARATOR . $subType . CRM_Core_DAO::VALUE_SEPARATOR;
      $subTypeClause = " AND extends_entity_column_value LIKE '%{$subTypeValue}%' ";
    }
    $query = "SELECT table_name FROM civicrm_custom_group WHERE extends = '{$contactType}' {$subTypeClause}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $sql = "SELECT count(id) FROM {$dao->table_name}";
      if ($contactId) {
        $sql .= " WHERE entity_id = {$contactId}";
      }
      $sql .= " LIMIT 1";

      $customDataCount = CRM_Core_DAO::singleValueQuery($sql);
      if (!empty($customDataCount)) {
        $dao->free();
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @todo what does this function do?
   * @param int $contactId
   * @param $contactType
   *
   * @return bool
   */
  public static function hasRelationships($contactId, $contactType) {
    $subTypeClause = NULL;
    if (self::isaSubType($contactType)) {
      $subType = $contactType;
      $contactType = self::getBasicType($subType);
      $subTypeClause = " AND ( ( crt.contact_type_a = '{$contactType}' AND crt.contact_sub_type_a = '{$subType}') OR
                                     ( crt.contact_type_b = '{$contactType}' AND crt.contact_sub_type_b = '{$subType}')  ) ";
    }
    else {
      $subTypeClause = " AND ( crt.contact_type_a = '{$contactType}' OR crt.contact_type_b = '{$contactType}' ) ";
    }

    // check relationships for
    $relationshipQuery = "
SELECT count(cr.id) FROM civicrm_relationship cr
INNER JOIN civicrm_relationship_type crt ON
( cr.relationship_type_id = crt.id {$subTypeClause} )
WHERE ( cr.contact_id_a = {$contactId} OR cr.contact_id_b = {$contactId} )
LIMIT 1";

    $relationshipCount = CRM_Core_DAO::singleValueQuery($relationshipQuery);

    if (!empty($relationshipCount)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @todo what does this function do?
   * @param $contactType
   * @param array $subtypeSet
   *
   * @return array
   */
  public static function getSubtypeCustomPair($contactType, $subtypeSet = array()) {
    if (empty($subtypeSet)) {
      return $subtypeSet;
    }

    $customSet = $subTypeClause = array();
    foreach ($subtypeSet as $subtype) {
      $subtype = CRM_Utils_Type::escape($subtype, 'String');
      $subType = CRM_Core_DAO::VALUE_SEPARATOR . $subtype . CRM_Core_DAO::VALUE_SEPARATOR;
      $subTypeClause[] = "extends_entity_column_value LIKE '%{$subtype}%' ";
    }
    $query = "SELECT table_name
FROM civicrm_custom_group
WHERE extends = %1 AND " . implode(" OR ", $subTypeClause);
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($contactType, 'String')));
    while ($dao->fetch()) {
      $customSet[] = $dao->table_name;
    }
    return array_unique($customSet);
  }

  /**
   * Function that does something.
   * @todo what does this function do?
   *
   * @param int $contactID
   * @param $contactType
   * @param array $oldSubtypeSet
   * @param array $newSubtypeSet
   *
   * @return bool
   */
  public  static function deleteCustomSetForSubtypeMigration(
    $contactID,
    $contactType,
    $oldSubtypeSet = array(),
    $newSubtypeSet = array()
  ) {
    $oldCustomSet = self::getSubtypeCustomPair($contactType, $oldSubtypeSet);
    $newCustomSet = self::getSubtypeCustomPair($contactType, $newSubtypeSet);

    $customToBeRemoved = array_diff($oldCustomSet, $newCustomSet);
    foreach ($customToBeRemoved as $customTable) {
      self::deleteCustomRowsForEntityID($customTable, $contactID);
    }
    return TRUE;
  }

  /**
   * Delete content / rows of a custom table specific to a subtype for a given custom-group.
   * This function currently works for contact subtypes only and could be later improved / genralized
   * to work for other subtypes as well.
   *
   * @param int $gID
   *   Custom group id.
   * @param array $subtypes
   *   List of subtypes related to which entry is to be removed.
   *
   * @return bool
   */
  public static function deleteCustomRowsOfSubtype($gID, $subtypes = array(), $subtypesToPreserve = array()) {
    if (!$gID or empty($subtypes)) {
      return FALSE;
    }

    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $gID, 'table_name');

    // drop triggers CRM-13587
    CRM_Core_DAO::dropTriggers($tableName);

    foreach ($subtypesToPreserve as $subtypeToPreserve) {
      $subtypeToPreserve = CRM_Utils_Type::escape($subtypeToPreserve, 'String');
      $subtypesToPreserveClause[] = "(civicrm_contact.contact_sub_type NOT LIKE '%" . CRM_Core_DAO::VALUE_SEPARATOR . $subtypeToPreserve . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
    }
    $subtypesToPreserveClause = implode(' AND ', $subtypesToPreserveClause);

    $subtypeClause = array();
    foreach ($subtypes as $subtype) {
      $subtype = CRM_Utils_Type::escape($subtype, 'String');
      $subtypeClause[] = "( civicrm_contact.contact_sub_type LIKE '%" . CRM_Core_DAO::VALUE_SEPARATOR . $subtype . CRM_Core_DAO::VALUE_SEPARATOR . "%'"
                            . " AND " . $subtypesToPreserveClause . ")";
    }
    $subtypeClause = implode(' OR ', $subtypeClause);

    $query = "DELETE custom.*
FROM {$tableName} custom
INNER JOIN civicrm_contact ON civicrm_contact.id = custom.entity_id
WHERE ($subtypeClause)";

    CRM_Core_DAO::singleValueQuery($query);

    // rebuild triggers CRM-13587
    CRM_Core_DAO::triggerRebuild($tableName);
  }

  /**
   * Delete content / rows of a custom table specific entity-id for a given custom-group table.
   *
   * @param int $customTable
   *   Custom table name.
   * @param int $entityID
   *   Entity id.
   *
   * @return null|string
   */
  public static function deleteCustomRowsForEntityID($customTable, $entityID) {
    $customTable = CRM_Utils_Type::escape($customTable, 'String');
    $query = "DELETE FROM {$customTable} WHERE entity_id = %1";
    return CRM_Core_DAO::singleValueQuery($query, array(1 => array($entityID, 'Integer')));
  }

}
