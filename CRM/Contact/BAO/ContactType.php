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

use Civi\Api4\ContactType;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @return CRM_Contact_DAO_ContactType|null
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
   *
   * @throws \API_Exception
   */
  public static function isActive($contactType) {
    $contact = self::contactTypeInfo();
    return array_key_exists($contactType, $contact);
  }

  /**
   * Retrieve basic contact type information.
   *
   * @todo - call getAllContactTypes & return filtered results.
   *
   * @param bool $includeInactive
   *
   * @return array
   *   Array of basic contact types information.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function basicTypeInfo($includeInactive = FALSE) {
    $cacheKey = 'CRM_CT_BTI_' . (int) $includeInactive;
    if (!Civi::cache('contactTypes')->has($cacheKey)) {
      $contactType = ContactType::get(FALSE)->setSelect(['*'])->addWhere('parent_id', 'IS NULL');
      if ($includeInactive === FALSE) {
        $contactType->addWhere('is_active', '=', 1);
      }
      Civi::cache('contactTypes')->set($cacheKey, (array) $contactType->execute()->indexBy('name'));
    }
    return Civi::cache('contactTypes')->get($cacheKey);
  }

  /**
   * Retrieve all basic contact types.
   *
   * @param bool $all
   *
   * @return array
   *   Array of basic contact types
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function basicTypes($all = FALSE) {
    return array_keys(self::basicTypeInfo($all));
  }

  /**
   * @param bool $all
   * @param string $key
   *
   * @return array
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function basicTypePairs($all = FALSE, $key = 'name') {
    $subtypes = self::basicTypeInfo($all);

    $pairs = [];
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
   * @param bool $all
   *
   * @return array
   *   Array of sub type information, subset of getAllContactTypes.
   *
   * @throws \API_Exception
   */
  public static function subTypeInfo($contactType = NULL, $all = FALSE) {
    $contactTypes = self::getAllContactTypes();
    foreach ($contactTypes as $index => $type) {
      if (empty($type['parent']) ||
        (!$all && !$type['is_active'])
        || ($contactType && $type['parent'] !== $contactType)
      ) {
        unset($contactTypes[$index]);
      }
    }
    return $contactTypes;
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
   * @throws \API_Exception
   */
  public static function subTypes($contactType = NULL, $all = FALSE, $columnName = 'name', $ignoreCache = FALSE) {
    if ($columnName === 'name') {
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

    $pairs = [];
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
   * @todo deprecate calling this with $all = TRUE in favour of getAllContactTypes
   * & ideally add getActiveContactTypes & call that from this fully
   * deprecated function.
   *
   * @param bool $all
   *
   * @return array
   *   Array of basic types + all subtypes.
   * @throws \API_Exception
   */
  public static function contactTypeInfo($all = FALSE) {
    $contactTypes = self::getAllContactTypes();
    if (!$all) {
      foreach ($contactTypes as $index => $value) {
        if (!$value['is_active']) {
          unset($contactTypes[$index]);
        }
      }
    }
    return $contactTypes;
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
   * @throws \API_Exception
   */
  public static function contactTypePairs($all = FALSE, $typeName = NULL, $delimiter = NULL) {
    $types = self::contactTypeInfo($all);

    if ($typeName && !is_array($typeName)) {
      $typeName = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($typeName, CRM_Core_DAO::VALUE_SEPARATOR));
    }

    $pairs = [];
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
    // @todo - use Cache class - ie like Civi::cache('contactTypes')
    static $_cache = NULL;

    if ($_cache === NULL) {
      $_cache = [];
    }

    // @todo - call getAllContactTypes & return filtered results.
    $argString = $all ? 'CRM_CT_GSE_1' : 'CRM_CT_GSE_0';
    $argString .= $isSeparator ? '_1' : '_0';
    $argString .= $separator;
    $argString = CRM_Utils_Cache::cleanKey($argString);
    if (!array_key_exists($argString, $_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $_cache[$argString] = $cache->get($argString);

      if (!$_cache[$argString]) {
        $_cache[$argString] = [];

        $sql = '
SELECT    c.name as child_name , c.label as child_label , c.id as child_id,
          p.name as parent_name, p.label as parent_label, p.id as parent_id
FROM      civicrm_contact_type c
LEFT JOIN civicrm_contact_type p ON ( c.parent_id = p.id )
WHERE     ( c.name IS NOT NULL )
';

        if ($all === FALSE) {
          $sql .= '
AND   c.is_active = 1
AND   ( p.is_active = 1 OR p.id IS NULL )
';
        }
        $sql .= " ORDER BY c.id";

        $values = [];
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
            $values[$pName] = [];
          }
          $values[$pName][] = ['key' => $key, 'label' => $label];
        }

        $selectElements = [];
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
   * @param array|string $subType contact subType.
   * @return array|string
   *   basicTypes.
   */
  public static function getBasicType($subType) {
    // @todo - use Cache class - ie like Civi::cache('contactTypes')
    static $_cache = NULL;
    if ($_cache === NULL) {
      $_cache = [];
    }

    $isArray = TRUE;
    if ($subType && !is_array($subType)) {
      $subType = [$subType];
      $isArray = FALSE;
    }
    $argString = implode('_', $subType);

    if (!array_key_exists($argString, $_cache)) {
      $_cache[$argString] = [];

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
    $shortCuts = [];
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = self::getSelectElements(FALSE, TRUE, $separator);
    foreach ($contactTypes as $key => $value) {
      if ($key) {
        $typeValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $key);
        $cType = $typeValue['0'] ?? NULL;
        $typeUrl = 'ct=' . $cType;
        if ($csType = CRM_Utils_Array::value('1', $typeValue)) {
          $typeUrl .= "&cst=$csType";
        }
        $shortCut = [
          'path' => 'civicrm/contact/add',
          'query' => "$typeUrl&reset=1",
          'ref' => "new-$value",
          'title' => $value,
        ];
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

    $params = ['id' => $contactTypeId];
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
      $sql = '
DELETE
FROM civicrm_navigation
WHERE name = %1';
      $params = [1 => ["New $name", 'String']];
      CRM_Core_DAO::executeQuery($sql, $params);
      CRM_Core_BAO_Navigation::resetNavigation();
      Civi::cache('contactTypes')->clear();
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
   * @throws \CRM_Core_Exception
   */
  public static function add($params) {

    // label or name
    if (empty($params['id']) && empty($params['label'])) {
      // @todo consider throwing exception instead.
      return NULL;
    }
    if (empty($params['id']) && empty($params['name'])) {
      $params['name'] = ucfirst(CRM_Utils_String::munge($params['label']));
    }
    if (!empty($params['parent_id']) &&
      !CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $params['parent_id'])
    ) {
      return NULL;
    }

    $contactType = new CRM_Contact_DAO_ContactType();
    $contactType->copyValues($params);
    $contactType->id = $params['id'] ?? NULL;

    $contactType->save();
    if ($contactType->find(TRUE)) {
      $contactName = $contactType->name;
      $contact = ucfirst($contactType->label);
      $active = $contactType->is_active;
    }

    if (!empty($params['id'])) {
      $newParams = [
        'label' => ts("New %1", [1 => $contact]),
        'is_active' => $contactType->is_active,
      ];
      CRM_Core_BAO_Navigation::processUpdate(['name' => "New $contactName"], $newParams);
    }
    else {
      $name = self::getBasicType($contactName);
      if (!$name) {
        return NULL;
      }
      $value = ['name' => "New $name"];
      CRM_Core_BAO_Navigation::retrieve($value, $navinfo);
      $navigation = [
        'label' => ts("New %1", [1 => $contact]),
        'name' => "New $contactName",
        'url' => "civicrm/contact/add?ct=$name&cst=$contactName&reset=1",
        'permission' => 'add contacts',
        'parent_id' => $navinfo['id'],
        'is_active' => $active,
      ];
      CRM_Core_BAO_Navigation::add($navigation);
    }
    CRM_Core_BAO_Navigation::resetNavigation();
    Civi::cache('contactTypes')->clear();

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
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    $params = ['id' => $id];
    self::retrieve($params, $contactinfo);
    $params = ['name' => "New $contactinfo[name]"];
    $newParams = ['is_active' => $is_active];
    CRM_Core_BAO_Navigation::processUpdate($params, $newParams);
    CRM_Core_BAO_Navigation::resetNavigation();
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_ContactType', $id,
      'is_active', $is_active
    );
  }

  /**
   * @param string $typeName
   *
   * @return string
   * @throws \API_Exception
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
   * @throws \CRM_Core_Exception
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
   * @param $contactType
   * @param array $subtypeSet
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @todo what does this function do?
   */
  public static function getSubtypeCustomPair($contactType, $subtypeSet = []) {
    if (empty($subtypeSet)) {
      return $subtypeSet;
    }

    $customSet = $subTypeClause = [];
    foreach ($subtypeSet as $subtype) {
      $subtype = CRM_Utils_Type::escape($subtype, 'String');
      $subtype = CRM_Core_DAO::VALUE_SEPARATOR . $subtype . CRM_Core_DAO::VALUE_SEPARATOR;
      $subTypeClause[] = "extends_entity_column_value LIKE '%{$subtype}%' ";
    }
    $query = 'SELECT table_name
FROM civicrm_custom_group
WHERE extends = %1 AND ' . implode(" OR ", $subTypeClause);
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contactType, 'String']]);
    while ($dao->fetch()) {
      $customSet[] = $dao->table_name;
    }
    return array_unique($customSet);
  }

  /**
   * Function that does something.
   *
   * @param int $contactID
   * @param string $contactType
   * @param array $oldSubtypeSet
   * @param array $newSubtypeSet
   *
   * @return bool
   * @throws \CRM_Core_Exception
   *
   * @todo what does this function do?
   */
  public  static function deleteCustomSetForSubtypeMigration(
    $contactID,
    $contactType,
    $oldSubtypeSet = [],
    $newSubtypeSet = []
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
   * @param array $subtypesToPreserve
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function deleteCustomRowsOfSubtype($gID, $subtypes = [], $subtypesToPreserve = []) {
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

    $subtypeClause = [];
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function deleteCustomRowsForEntityID($customTable, $entityID) {
    $customTable = CRM_Utils_Type::escape($customTable, 'String');
    $query = "DELETE FROM {$customTable} WHERE entity_id = %1";
    return CRM_Core_DAO::singleValueQuery($query, [1 => [$entityID, 'Integer']]);
  }

  /**
   * Get all contact types, leveraging caching.
   *
   * @return array
   *
   * @throws \API_Exception
   */
  protected static function getAllContactTypes() {
    if (!Civi::cache('contactTypes')->has('all')) {
      $contactTypes = (array) ContactType::get(FALSE)
        ->setSelect(['id', 'name', 'label', 'description', 'is_active', 'is_reserved', 'image_URL', 'parent_id', 'parent_id:name', 'parent_id:label'])
        ->execute()->indexBy('name');

      foreach ($contactTypes as $id => $contactType) {
        $contactTypes[$id]['parent'] = $contactType['parent_id:name'];
        $contactTypes[$id]['parent_label'] = $contactType['parent_id:label'];
        unset($contactTypes[$id]['parent_id:name'], $contactTypes[$id]['parent_id:label']);
      }
      Civi::cache('contactTypes')->set('all', $contactTypes);
    }
    $contactTypes = Civi::cache('contactTypes')->get('all');
    return $contactTypes;
  }

}
