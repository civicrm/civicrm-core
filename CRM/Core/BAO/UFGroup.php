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

/**
 * UF group BAO class.
 */
class CRM_Core_BAO_UFGroup extends CRM_Core_DAO_UFGroup implements \Civi\Core\HookInterface {

  const PUBLIC_VISIBILITY = 1,
    ADMIN_VISIBILITY = 2,
    LISTINGS_VISIBILITY = 4;

  /**
   * Cache the match clause used in this transaction.
   *
   * @var string
   */
  public static $_matchFields = NULL;

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference) an assoc array to hold the flattened values.
   *
   * @return object
   *   CRM_Core_DAO_UFGroup object
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_UFGroup', $params, $defaults);
  }

  /**
   * Retrieve the first non-generic contact type
   *
   * @param int $id
   *   Id of uf_group.
   *
   * @return string
   *   contact type
   */
  public static function getContactType($id) {

    $validTypes = array_filter(array_keys(CRM_Core_SelectValues::contactType()));
    $validSubTypes = CRM_Contact_BAO_ContactType::subTypeInfo();

    $typesParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $id, 'group_type'));
    $types = explode(',', $typesParts[0]);

    $cType = NULL;
    foreach ($types as $type) {
      if (in_array($type, $validTypes)) {
        $cType = $type;
      }
      elseif (array_key_exists($type, $validSubTypes)) {
        $cType = $validSubTypes[$type]['parent'] ?? NULL;
      }
      if ($cType) {
        break;
      }
    }

    return $cType;
  }

  /**
   * Get the form title.
   *
   * @param int $id
   *   Id of uf_form.
   *
   * @return string
   *   title
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $id, 'title');
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
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $id, 'is_active', $is_active);
  }

  /**
   * Get all the registration fields.
   *
   * @param int $action
   *   What action are we doing.
   * @param int $mode
   *   Mode.
   *
   * @param string $ctype
   *
   * @return array
   *   the fields that are needed for registration
   *
   * @throws \Exception
   */
  public static function getRegistrationFields($action, $mode, $ctype = NULL) {
    if ($mode & CRM_Profile_Form::MODE_REGISTER) {
      $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('User Registration');
    }
    else {
      $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('Profile');
    }

    if (!is_array($ufGroups)) {
      return FALSE;
    }

    $fields = [];

    foreach ($ufGroups as $id => $title) {
      if ($ctype) {
        $fieldType = CRM_Core_BAO_UFField::getProfileType($id);
        if (($fieldType != 'Contact') &&
          ($fieldType != $ctype) &&
          !CRM_Contact_BAO_ContactType::isExtendsContactType($fieldType, $ctype)
        ) {
          continue;
        }
        if (CRM_Contact_BAO_ContactType::isaSubType($fieldType)) {
          $profileSubType = $fieldType;
        }
      }

      $subset = self::getFields($id, TRUE, $action,
        NULL, NULL, FALSE, NULL, TRUE, $ctype
      );

      // we do not allow duplicates. the first field is the winner
      foreach ($subset as $name => $field) {
        if (empty($fields[$name])) {
          $fields[$name] = $field;
        }
      }
    }

    return $fields;
  }

  /**
   * Get all the listing fields.
   *
   * @param int $action
   *   What action are we doing.
   * @param int $visibility
   *   Visibility of fields we are interested in.
   * @param bool $considerSelector
   *   Whether to consider the in_selector parameter.
   * @param array $ufGroupIds
   * @param bool $searchable
   *
   * @param null $restrict
   * @param bool $skipPermission
   * @param int $permissionType
   *
   * @return array
   *   the fields that are listings related
   *
   * @throws \Exception
   */
  public static function getListingFields(
    $action,
    $visibility,
    $considerSelector = FALSE,
    $ufGroupIds = NULL,
    $searchable = NULL,
    $restrict = NULL,
    $skipPermission = FALSE,
    $permissionType = CRM_Core_Permission::SEARCH
  ) {
    if ($ufGroupIds) {
      $subset = self::getFields($ufGroupIds, FALSE, $action,
        $visibility, $searchable,
        FALSE, $restrict,
        $skipPermission,
        NULL,
        $permissionType
      );
      if ($considerSelector) {
        // drop the fields not meant for the selector
        foreach ($subset as $name => $field) {
          if (!$field['in_selector']) {
            unset($subset[$name]);
          }
        }
      }
      $fields = $subset;
    }
    else {
      $ufGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');

      $fields = [];
      foreach ($ufGroups as $id => $title) {
        $subset = self::getFields($id, FALSE, $action,
          $visibility, $searchable,
          FALSE, $restrict,
          $skipPermission,
          NULL,
          $permissionType
        );
        if ($considerSelector) {
          // drop the fields not meant for the selector
          foreach ($subset as $name => $field) {
            if (!$field['in_selector']) {
              unset($subset[$name]);
            }
          }
        }
        $fields = array_merge($fields, $subset);
      }
    }
    return $fields;
  }

  /**
   * Get all the fields that belong to the group with the name title,
   * and format for use with buildProfile. This is the SQL analog of
   * formatUFFields().
   *
   * @param int $id
   *   The id of the UF group or ids of ufgroup.
   * @param bool|int $register are we interested in registration fields
   * @param int $action
   *   What action are we doing.
   * @param int $visibility
   *   Visibility of fields we are interested in.
   * @param bool $searchable
   * @param bool $showAll
   * @param string $restrict
   *   Should we restrict based on a specified profile type.
   * @param bool $skipPermission
   * @param null $ctype
   * @param int $permissionType
   * @param string $orderBy
   * @param bool $orderProfiles
   * @param bool $eventProfile
   * @return array
   *   The fields that belong to this ufgroup(s)
   *
   * @throws \CRM_Core_Exception
   */
  public static function getFields(
    $id,
    $register = FALSE,
    $action = NULL,
    $visibility = NULL,
    $searchable = FALSE,
    $showAll = FALSE,
    $restrict = NULL,
    $skipPermission = FALSE,
    $ctype = NULL,
    $permissionType = CRM_Core_Permission::CREATE,
    $orderBy = 'field_name',
    $orderProfiles = FALSE,
    $eventProfile = FALSE
  ) {
    if (!is_array($id)) {
      $id = CRM_Utils_Type::escape($id, 'Positive');
      $profileIds = [$id];
    }
    else {
      $profileIds = $id;
    }

    $gids = implode(',', $profileIds);
    $params = [];
    if ($restrict) {
      $query = "SELECT g.* from civicrm_uf_group g
                LEFT JOIN civicrm_uf_join j ON (j.uf_group_id = g.id)
                WHERE g.id IN ( {$gids} )
                AND ((j.uf_group_id IN ( {$gids} ) AND j.module = %1) OR g.is_reserved = 1 )
                ";
      $params = [1 => [$restrict, 'String']];
    }
    else {
      $query = "SELECT g.* from civicrm_uf_group g WHERE g.id IN ( {$gids} ) ";
    }

    if (!$showAll) {
      $query .= " AND g.is_active = 1";
    }

    $checkPermission = [
      [
        'administer CiviCRM',
        'manage event profiles',
      ],
    ];
    if ($eventProfile && CRM_Core_Permission::check($checkPermission)) {
      $skipPermission = TRUE;
    }

    // add permissioning for profiles only if not registration
    if (!$skipPermission) {
      $permissionClause = CRM_Core_Permission::ufGroupClause($permissionType, 'g.');
      $query .= " AND $permissionClause ";
    }

    if ($orderProfiles and count($profileIds) > 1) {
      $query .= " ORDER BY FIELD(  g.id, {$gids} )";
    }
    $group = CRM_Core_DAO::executeQuery($query, $params);
    $fields = [];
    $validGroup = FALSE;

    while ($group->fetch()) {
      $validGroup = TRUE;
      $query = self::createUFFieldQuery($group->id, $searchable, $showAll, $visibility, $orderBy);
      $field = CRM_Core_DAO::executeQuery($query);

      $importableFields = self::getProfileFieldMetadata($showAll);
      [$customFields, $addressCustomFields] = self::getCustomFields($ctype, $skipPermission ? FALSE : $permissionType);

      while ($field->fetch()) {
        [$name, $formattedField] = self::formatUFField($group, $field, $customFields, $addressCustomFields, $importableFields, $permissionType);
        if ($formattedField !== NULL) {
          $fields[$name] = $formattedField;
        }
      }
    }

    if (empty($fields) && !$validGroup) {
      throw new CRM_Core_Exception(ts('The requested Profile (gid=%1) is disabled OR it is not configured to be used for \'Profile\' listings in its Settings OR there is no Profile with that ID OR you do not have permission to access this profile. Please contact the site administrator if you need assistance.',
        [1 => implode(',', $profileIds)]
      ));
    }
    else {
      self::reformatProfileFields($fields);
    }

    return $fields;
  }

  /**
   * Format a list of UFFields for use with buildProfile. This is the in-memory analog
   * of getFields().
   *
   * @param array $groupArr
   *   (mimic CRM_UF_DAO_UFGroup).
   * @param array $fieldArrs
   *   List of fields (each mimics CRM_UF_DAO_UFField).
   * @param bool $visibility
   *   Visibility of fields we are interested in.
   * @param bool $searchable
   * @param bool $showAll
   * @param null $ctype
   * @param int $permissionType
   *
   * @return array
   * @see self::getFields
   */
  public static function formatUFFields(
    $groupArr,
    $fieldArrs,
    $visibility = NULL,
    $searchable = NULL,
    $showAll = FALSE,
    $ctype = NULL,
    $permissionType = CRM_Core_Permission::CREATE
  ) {
    // $group = new CRM_Core_DAO_UFGroup();
    // $group->copyValues($groupArr); // no... converts string('') to string('null')
    $group = (object) $groupArr;

    // Refactoring note: The $fieldArrs here may be slightly different than the $ufFields
    // used by calculateGroupType, but I don't think the missing fields matter, and -- if
    // they did -- the obvious fix would produce mutual recursion.
    $ufGroupType = self::_calculateGroupType($fieldArrs);
    $profileType = CRM_Core_BAO_UFField::calculateProfileType(implode(',', $ufGroupType));
    $contactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileTypeByGroupType(implode(',', $ufGroupType));
    $importableFields = self::getImportableFields($showAll, $profileType, $contactActivityProfile);
    [$customFields, $addressCustomFields] = self::getCustomFields($ctype, $permissionType);

    $formattedFields = [];
    foreach ($fieldArrs as $fieldArr) {
      $field = (object) $fieldArr;
      if (!self::filterUFField($field, $searchable, $showAll, $visibility)) {
        continue;
      }

      [$name, $formattedField] = self::formatUFField($group, $field, $customFields, $addressCustomFields, $importableFields, $permissionType);
      if ($formattedField !== NULL) {
        $formattedFields[$name] = $formattedField;
      }
    }
    return $formattedFields;
  }

  /**
   * Prepare a field for rendering with CRM_Core_BAO_UFGroup::buildProfile.
   *
   * @param CRM_Core_DAO_UFGroup|CRM_Core_DAO $group
   * @param CRM_Core_DAO_UFField|CRM_Core_DAO $field
   * @param array $customFields
   * @param array $addressCustomFields
   * @param array $importableFields
   * @param int $permissionType
   *   Eg CRM_Core_Permission::CREATE.
   *
   * @return array
   */
  protected static function formatUFField(
    $group,
    $field,
    $customFields,
    $addressCustomFields,
    $importableFields,
    $permissionType = CRM_Core_Permission::CREATE
  ) {
    $name = $field->field_name;
    $title = $field->label;

    $addressCustom = FALSE;
    if (in_array($permissionType, [CRM_Core_Permission::CREATE, CRM_Core_Permission::EDIT]) &&
      array_key_exists($field->field_name, $addressCustomFields)
    ) {
      $addressCustom = TRUE;
      $name = "address_{$name}";
    }
    if ($field->field_name == 'url') {
      $name .= "-{$field->website_type_id}";
    }
    elseif (!empty($field->location_type_id)) {
      $name .= "-{$field->location_type_id}";
    }
    else {
      $locationFields = self::getLocationFields();
      if (in_array($field->field_name, $locationFields) || $addressCustom) {
        $name .= '-Primary';
      }
    }

    if (isset($field->phone_type_id)) {
      $name .= "-{$field->phone_type_id}";
    }
    $fieldMetaData = CRM_Utils_Array::value($name, $importableFields, ($importableFields[$field->field_name] ?? []));

    // No lie: this is bizarre; why do we need to mix so many UFGroup properties into UFFields?
    // I guess to make field self sufficient with all the required data and avoid additional calls
    $formattedField = [
      'name' => $name,
      'groupTitle' => $group->title,
      'groupName' => $group->name,
      'groupDisplayTitle' => (!empty($group->frontend_title)) ? $group->frontend_title : $group->title,
      'groupHelpPre' => empty($group->help_pre) ? '' : $group->help_pre,
      'groupHelpPost' => empty($group->help_post) ? '' : $group->help_post,
      'title' => $title,
      'where' => CRM_Utils_Array::value('where', CRM_Utils_Array::value($field->field_name, $importableFields)),
      'attributes' => CRM_Core_DAO::makeAttribute(CRM_Utils_Array::value($field->field_name, $importableFields)),
      'is_required' => $field->is_required,
      'is_view' => $field->is_view,
      'help_pre' => $field->help_pre,
      'help_post' => $field->help_post,
      'visibility' => $field->visibility,
      'in_selector' => $field->in_selector,
      'rule' => CRM_Utils_Array::value('rule', CRM_Utils_Array::value($field->field_name, $importableFields)),
      'location_type_id' => $field->location_type_id ?? NULL,
      'website_type_id' => $field->website_type_id ?? NULL,
      'phone_type_id' => $field->phone_type_id ?? NULL,
      'group_id' => $group->id,
      'add_to_group_id' => $group->add_to_group_id ?? NULL,
      'add_captcha' => $group->add_captcha ?? NULL,
      'field_type' => $field->field_type,
      'field_id' => $field->id,
      'pseudoconstant' => CRM_Utils_Array::value(
        'pseudoconstant',
        CRM_Utils_Array::value($field->field_name, $importableFields)
      ),
      // obsolete this when we remove the name / dbName discrepancy with gender/suffix/prefix
      'dbName' => CRM_Utils_Array::value(
        'dbName',
        CRM_Utils_Array::value($field->field_name, $importableFields)
      ),
      'skipDisplay' => 0,
      'data_type' => CRM_Utils_Type::getDataTypeFromFieldMetadata($fieldMetaData),
      'bao' => $fieldMetaData['bao'] ?? NULL,
      'html_type' => $fieldMetaData['html']['type'] ?? NULL,
    ];

    $formattedField = CRM_Utils_Date::addDateMetadataToField($fieldMetaData, $formattedField);

    //adding custom field property
    if (substr($field->field_name, 0, 6) == 'custom' ||
      substr($field->field_name, 0, 14) === 'address_custom'
    ) {
      // if field is not present in customFields, that means the user
      // DOES NOT HAVE permission to access that field
      if (array_key_exists($field->field_name, $customFields)) {
        $formattedField['serialize'] = !empty($customFields[$field->field_name]['serialize']);
        $formattedField['is_search_range'] = $customFields[$field->field_name]['is_search_range'];
        // fix for CRM-1994
        $formattedField['options_per_line'] = $customFields[$field->field_name]['options_per_line'];
        $formattedField['html_type'] = $customFields[$field->field_name]['html_type'];

        if (CRM_Utils_Array::value('html_type', $formattedField) == 'Select Date') {
          $formattedField['date_format'] = $customFields[$field->field_name]['date_format'];
          $formattedField['time_format'] = $customFields[$field->field_name]['time_format'];
          $formattedField['is_datetime_field'] = TRUE;
          $formattedField['smarty_view_format'] = CRM_Utils_Date::getDateFieldViewFormat($formattedField['date_format']);
        }

        $formattedField['is_multi_summary'] = $field->is_multi_summary;
        return [$name, $formattedField];
      }
      else {
        $formattedField = NULL;
        return [$name, $formattedField];
      }
    }
    return [$name, $formattedField];
  }

  /**
   * Create a query to find all visible UFFields in a UFGroup.
   *
   * This is the SQL-variant of checkUFFieldDisplayable().
   *
   * @param int $groupId
   * @param bool $searchable
   * @param bool $showAll
   * @param int $visibility
   * @param string $orderBy
   *   Comma-delimited list of SQL columns.
   *
   * @return string
   */
  protected static function createUFFieldQuery($groupId, $searchable, $showAll, $visibility, $orderBy) {
    $where = " WHERE uf_group_id = {$groupId}";

    if ($searchable) {
      $where .= " AND is_searchable = 1";
    }

    if (!$showAll) {
      $where .= " AND is_active = 1";
    }

    if ($visibility) {
      $clause = [];
      if ($visibility & self::PUBLIC_VISIBILITY) {
        $clause[] = 'visibility = "Public Pages"';
      }
      if ($visibility & self::ADMIN_VISIBILITY) {
        $clause[] = 'visibility = "User and User Admin Only"';
      }
      if ($visibility & self::LISTINGS_VISIBILITY) {
        $clause[] = 'visibility = "Public Pages and Listings"';
      }
      if (!empty($clause)) {
        $where .= ' AND ( ' . implode(' OR ', $clause) . ' ) ';
      }
    }

    $query = "SELECT * FROM civicrm_uf_field $where ORDER BY weight";
    if ($orderBy) {
      $query .= ", " . $orderBy;
      return $query;
    }
    return $query;
  }

  /**
   * Create a query to find all visible UFFields in a UFGroup.
   *
   * This is the PHP in-memory variant of createUFFieldQuery().
   *
   * @param CRM_Core_DAO_UFField|CRM_Core_DAO $field
   * @param bool $searchable
   * @param bool $showAll
   * @param int $visibility
   *
   * @return bool
   *   TRUE if field is displayable
   */
  protected static function filterUFField($field, $searchable, $showAll, $visibility) {
    if ($searchable && $field->is_searchable != 1) {
      return FALSE;
    }

    if (!$showAll && $field->is_active != 1) {
      return FALSE;
    }

    if ($visibility) {
      $allowedVisibilities = [];
      if ($visibility & self::PUBLIC_VISIBILITY) {
        $allowedVisibilities[] = 'Public Pages';
      }
      if ($visibility & self::ADMIN_VISIBILITY) {
        $allowedVisibilities[] = 'User and User Admin Only';
      }
      if ($visibility & self::LISTINGS_VISIBILITY) {
        $allowedVisibilities[] = 'Public Pages and Listings';
      }
      // !empty($allowedVisibilities) seems silly to me, but it is equivalent to the pre-existing SQL
      if (!empty($allowedVisibilities) && !in_array($field->visibility, $allowedVisibilities)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get a list of filtered field metadata.
   *
   * @param $showAll
   * @param $profileType
   * @param $contactActivityProfile
   * @param bool $filterMode
   *   Filter mode means you are using importable fields for filtering rather than just getting metadata.
   *   With filter mode = FALSE BOTH activity fields and component fields are returned.
   *   I can't see why you would ever want to use this function in filter mode as the component fields are
   *   still unfiltered. However, I feel scared enough to leave it as it is. I have marked this function as
   *   deprecated and am recommending the wrapper 'getProfileFieldMetadata' in order to try to
   *   send this confusion to history.
   *
   * @return array
   * @deprecated use getProfileFieldMetadata
   *
   */
  protected static function getImportableFields($showAll, $profileType, $contactActivityProfile, $filterMode = TRUE) {
    if (!$showAll) {
      $importableFields = CRM_Contact_BAO_Contact::importableFields('All', FALSE, FALSE, FALSE, TRUE, TRUE);
    }
    else {
      $importableFields = CRM_Contact_BAO_Contact::importableFields('All', FALSE, TRUE, FALSE, TRUE, TRUE);
    }

    $activityFields = CRM_Activity_BAO_Activity::getProfileFields();
    $componentFields = CRM_Core_Component::getQueryFields();
    if ($filterMode == TRUE) {
      if ($profileType == 'Activity' || $contactActivityProfile) {
        $importableFields = array_merge($importableFields, $activityFields);
      }
      else {
        $importableFields = array_merge($importableFields, $componentFields);
      }
    }
    else {
      $importableFields = array_merge($importableFields, $activityFields, $componentFields);
    }

    $importableFields['group']['title'] = ts('Group(s)');
    $importableFields['group']['where'] = NULL;
    $importableFields['tag']['title'] = ts('Tag(s)');
    $importableFields['tag']['where'] = NULL;
    return $importableFields;
  }

  /**
   * Get the metadata for all potential profile fields.
   *
   * @param bool $isIncludeInactive
   *   Should disabled fields be included.
   *
   * @return array
   *   Field metadata for all fields that might potentially be in a profile.
   */
  protected static function getProfileFieldMetadata($isIncludeInactive) {
    return self::getImportableFields($isIncludeInactive, NULL, NULL, NULL, TRUE);
  }

  /**
   * Get the fields relating to locations.
   *
   * @return array
   */
  public static function getLocationFields() {
    static $locationFields = [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'postal_code_suffix',
      'geo_code_1',
      'geo_code_2',
      'state_province',
      'country',
      'county',
      'phone',
      'phone_and_ext',
      'email',
      'im',
      'address_name',
      'phone_ext',
    ];
    return $locationFields;
  }

  /**
   * @param $ctype
   * @param int|bool $checkPermission
   * @return mixed
   */
  protected static function getCustomFields($ctype, $checkPermission = CRM_Core_Permission::VIEW) {
    // Only Edit and View is supported in ACL for custom field.
    if ($checkPermission == CRM_Core_Permission::CREATE) {
      $checkPermission = CRM_Core_Permission::EDIT;
    }
    // Make the cache user specific by adding the ID to the key.
    $contactId = CRM_Core_Session::getLoggedInContactID();
    $cacheKey = 'uf_group_custom_fields_' . $ctype . '_' . $contactId . '_' . (int) $checkPermission;
    if (!Civi::cache('metadata')->has($cacheKey)) {
      $customFields = CRM_Core_BAO_CustomField::getFieldsForImport($ctype, FALSE, FALSE, FALSE, $checkPermission, TRUE);

      // hack to add custom data for components
      $components = ['Contribution', 'Participant', 'Membership', 'Activity', 'Case'];
      foreach ($components as $value) {
        $customFields = array_merge($customFields, CRM_Core_BAO_CustomField::getFieldsForImport($value));
      }
      $addressCustomFields = CRM_Core_BAO_CustomField::getFieldsForImport('Address');
      $customFields = array_merge($customFields, $addressCustomFields);
      Civi::cache('metadata')->set($cacheKey, [$customFields, $addressCustomFields]);
    }
    return Civi::cache('metadata')->get($cacheKey);
  }

  /**
   * Check the data validity.
   *
   * @param int $userID
   *   The user id that we are actually editing.
   * @param string $name
   *   The machine-name of the group we are interested in.
   * @param bool $register
   * @param int $action
   *   The action of the form.
   *
   * @pram  boolean $register is this the registrtion form
   * @return bool
   *   true if form is valid
   */
  public static function isValid($userID, $name, $register = FALSE, $action = NULL) {
    if ($register) {
      $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Dynamic',
        ts('Dynamic Form Creator'),
        $action
      );
      $controller->set('id', $userID);
      $controller->set('register', 1);
      $controller->process();
      return $controller->validate();
    }
    else {
      // make sure we have a valid group
      $group = new CRM_Core_DAO_UFGroup();

      $group->name = $name;

      if ($group->find(TRUE) && $userID) {
        $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Dynamic', ts('Dynamic Form Creator'), $action);
        $controller->set('gid', $group->id);
        $controller->set('id', $userID);
        $controller->set('register', 0);
        $controller->process();
        return $controller->validate();
      }
      return TRUE;
    }
  }

  /**
   * Get the html for the form that represents this particular group.
   *
   * @param int $userID
   *   The user id that we are actually editing.
   * @param string $title
   *   The title of the group we are interested in.
   * @param int $action
   *   The action of the form.
   * @param bool $register
   *   Is this the registration form.
   * @param bool $reset
   *   Should we reset the form?.
   * @param int $profileID
   *   Do we have the profile ID?.
   *
   * @param bool $doNotProcess
   * @param null $ctype
   *
   * @return string
   *   the html for the form on success, otherwise empty string
   */
  public static function getEditHTML(
    $userID,
    $title,
    $action = NULL,
    $register = FALSE,
    $reset = FALSE,
    $profileID = NULL,
    $doNotProcess = FALSE,
    $ctype = NULL
  ) {

    if ($register) {
      $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Dynamic',
        ts('Dynamic Form Creator'),
        $action
      );
      if ($reset || $doNotProcess) {
        // hack to make sure we do not process this form
        $oldQFDefault = CRM_Utils_Array::value('_qf_default',
          $_POST
        );
        unset($_POST['_qf_default']);
        unset($_REQUEST['_qf_default']);
        if ($reset) {
          $controller->reset();
        }
      }

      $controller->set('id', $userID);
      $controller->set('register', 1);
      $controller->set('skipPermission', 1);
      $controller->set('ctype', $ctype);
      $controller->process();
      if ($doNotProcess || !empty($_POST)) {
        $controller->validate();
      }
      $controller->setEmbedded(TRUE);

      //CRM-5839 - though we want to process form, get the control back.
      $controller->setSkipRedirection(!$doNotProcess);

      $controller->run();

      // we are done processing so restore the POST/REQUEST vars
      if (($reset || $doNotProcess) && $oldQFDefault) {
        $_POST['_qf_default'] = $_REQUEST['_qf_default'] = $oldQFDefault;
      }

      $template = CRM_Core_Smarty::singleton();

      // Hide CRM error messages if they are displayed using drupal form_set_error.
      if (!empty($_POST)) {
        $template->assign('suppressForm', TRUE);
      }

      return trim($template->fetch('CRM/Profile/Form/Dynamic.tpl'));
    }
    else {
      if (!$profileID) {
        // make sure we have a valid group
        $group = new CRM_Core_DAO_UFGroup();

        $group->title = $title;

        if ($group->find(TRUE)) {
          $profileID = $group->id;
        }
      }

      if ($profileID) {
        // make sure profileID and ctype match if ctype exists
        if ($ctype) {
          $profileType = CRM_Core_BAO_UFField::getProfileType($profileID);
          if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
            $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
          }

          if (($profileType != 'Contact') && ($profileType != $ctype)) {
            return NULL;
          }
        }

        $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Dynamic',
          ts('Dynamic Form Creator'),
          $action
        );
        if ($reset) {
          $controller->reset();
        }
        $controller->set('gid', $profileID);
        $controller->set('id', $userID);
        $controller->set('register', 0);
        $controller->set('skipPermission', 1);
        if ($ctype) {
          $controller->set('ctype', $ctype);
        }
        $controller->process();
        $controller->setEmbedded(TRUE);

        //CRM-5846 - give the control back to drupal.
        $controller->setSkipRedirection(!$doNotProcess);
        $controller->run();

        $template = CRM_Core_Smarty::singleton();

        // Hide CRM error messages if they are set by the CMS.
        if (!empty($_POST)) {
          $supressForm = CRM_Core_Config::singleton()->userSystem->suppressProfileFormErrors();
          $template->assign('suppressForm', $supressForm);
        }

        $templateFile = "CRM/Profile/Form/{$profileID}/Dynamic.tpl";
        if (!$template->template_exists($templateFile)) {
          $templateFile = 'CRM/Profile/Form/Dynamic.tpl';
        }
        return trim($template->fetch($templateFile));
      }
      else {
        $userEmail = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);

        // if post not empty then only proceed
        if (!empty($_POST)) {
          // get the new email
          $config = CRM_Core_Config::singleton();
          $email = $_POST['mail'] ?? NULL;

          if (CRM_Utils_Rule::email($email) && ($email != $userEmail[1])) {
            CRM_Core_BAO_UFMatch::updateContactEmail($userID, $email);
          }
        }
      }
    }
    return '';
  }

  /**
   * Given a contact id and a field set, return the values from the db.
   *
   * @param int $cid
   * @param array $fields
   *   The profile fields of interest.
   * @param array $values
   *   The values for the above fields.
   * @param bool $searchable
   *   Searchable or not.
   * @param array $componentWhere
   *   Component condition.
   * @param bool $absolute
   *   Return urls in absolute form (useful when sending an email).
   * @param null $additionalWhereClause
   *
   * @return null|array
   */
  public static function getValues(
    $cid, &$fields, &$values,
    $searchable = TRUE, $componentWhere = NULL,
    $absolute = FALSE, $additionalWhereClause = NULL
  ) {
    if (empty($cid) && empty($componentWhere)) {
      return NULL;
    }

    // get the contact details (hier)
    $returnProperties = CRM_Contact_BAO_Contact::makeHierReturnProperties($fields);
    $params = $cid ? [['contact_id', '=', $cid, 0, 0]] : [];

    // add conditions specified by components. eg partcipant_id etc
    if (!empty($componentWhere)) {
      $params = array_merge($params, $componentWhere);
    }

    $query = new CRM_Contact_BAO_Query($params, $returnProperties, $fields);

    $details = $query->searchQuery(0, 0, NULL, FALSE, FALSE,
      FALSE, FALSE, FALSE, $additionalWhereClause);
    while ($details->fetch()) {
      if (!$details) {
        return;
      }
    }
    $query->convertToPseudoNames($details);

    $locationTypes = CRM_Core_BAO_Address::buildOptions('location_type_id', 'validate');
    $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');
    $websiteTypes = CRM_Core_DAO_Website::buildOptions('website_type_id');

    $multipleFields = ['url'];

    //start of code to set the default values
    foreach ($fields as $name => $field) {
      // fix for CRM-3962
      if ($name == 'id') {
        $name = 'contact_id';
      }

      // skip fields that should not be displayed separately
      if (!empty($field['skipDisplay'])) {
        continue;
      }

      // Create a unique, non-empty index for each field.
      $index = $field['title'];
      if ($index === '') {
        $index = ' ';
      }
      while (array_key_exists($index, $values)) {
        $index .= ' ';
      }

      $params[$index] = $values[$index] = '';
      $customFieldName = NULL;
      // hack for CRM-665
      if (isset($details->$name) || $name == 'group' || $name == 'tag') {
        // to handle gender / suffix / prefix
        if (in_array(substr($name, 0, -3), ['gender', 'prefix', 'suffix'])) {
          $params[$index] = $details->$name;
          $values[$index] = $details->$name;
        }
        elseif (in_array($name, CRM_Contact_BAO_Contact::$_greetingTypes)) {
          $dname = $name . '_display';
          $values[$index] = $details->$dname;
          $name = $name . '_id';
          $params[$index] = $details->$name;
        }
        elseif (in_array($name, [
          'state_province',
          'country',
          'county',
        ])) {
          $values[$index] = $details->$name;
          $idx = $name . '_id';
          $params[$index] = $details->$idx;
        }
        elseif ($name === 'preferred_language') {
          $params[$index] = $details->$name;
          $values[$index] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'preferred_language', $details->$name);
        }
        elseif ($name == 'group') {
          $groups = CRM_Contact_BAO_GroupContact::getContactGroup($cid, 'Added', NULL, FALSE, TRUE);
          $title = $ids = [];

          foreach ($groups as $g) {
            // CRM-8362: User and User Admin visibility groups should be included in display if user has
            // VIEW permission on that group
            $groupPerm = CRM_Contact_BAO_Group::checkPermission($g['group_id'], TRUE);

            if ($g['visibility'] != 'User and User Admin Only' ||
              CRM_Utils_Array::key(CRM_Core_Permission::VIEW, $groupPerm)
            ) {
              $title[] = $g['title'];
              if ($g['visibility'] == 'Public Pages') {
                $ids[] = $g['group_id'];
              }
            }
          }
          $values[$index] = implode(', ', $title);
          $params[$index] = implode(',', $ids);
        }
        elseif ($name == 'tag') {
          $entityTags = CRM_Core_BAO_EntityTag::getTag($cid);
          $allTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);
          $title = [];
          foreach ($entityTags as $tagId) {
            $title[] = $allTags[$tagId];
          }
          $values[$index] = implode(', ', $title);
          $params[$index] = implode(',', $entityTags);
        }
        elseif ($name == 'activity_status_id') {
          $activityStatus = CRM_Core_PseudoConstant::activityStatus();
          $values[$index] = $activityStatus[$details->$name];
          $params[$index] = $details->$name;
        }
        elseif ($name == 'activity_date_time') {
          $values[$index] = CRM_Utils_Date::customFormat($details->$name);
          $params[$index] = $details->$name;
        }
        elseif ($name == 'contact_sub_type') {
          $contactSubTypeNames = explode(CRM_Core_DAO::VALUE_SEPARATOR, $details->$name);
          if (!empty($contactSubTypeNames)) {
            $contactSubTypeLabels = [];
            // get all contact subtypes
            $allContactSubTypes = CRM_Contact_BAO_ContactType::subTypeInfo();
            // build contact subtype labels array
            foreach ($contactSubTypeNames as $cstName) {
              if ($cstName) {
                $contactSubTypeLabels[] = $allContactSubTypes[$cstName]['label'];
              }
            }
            $values[$index] = implode(',', $contactSubTypeLabels);
          }

          $params[$index] = $details->$name;
        }
        else {
          if (substr($name, 0, 7) === 'do_not_' || substr($name, 0, 3) === 'is_') {
            if ($details->$name) {
              $values[$index] = '[ x ]';
            }
          }
          else {
            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name)) {
              $htmlType = $field['html_type'];

              // field_type is only set when we are retrieving profile values
              // when sending email, we call the same function to get custom field
              // values etc, i.e. emulating a profile
              $fieldType = $field['field_type'] ?? NULL;

              if ($htmlType == 'File') {
                $entityId = $cid;
                if (!$cid &&
                  $fieldType == 'Activity' && !empty($componentWhere[0][2])
                ) {
                  $entityId = $componentWhere[0][2];
                }

                $fileURL = CRM_Core_BAO_CustomField::getFileURL($entityId,
                  $cfID,
                  NULL,
                  $absolute,
                  $additionalWhereClause
                );
                $params[$index] = $values[$index] = $fileURL['file_url'];
              }
              else {
                $customVal = NULL;
                if (isset($dao) && property_exists($dao, 'data_type') &&
                  ($dao->data_type == 'Int' ||
                    $dao->data_type == 'Boolean'
                  )
                ) {
                  $customVal = (int ) ($details->{$name});
                }
                elseif (isset($dao) && property_exists($dao, 'data_type')
                  && $dao->data_type == 'Float'
                ) {
                  $customVal = (float ) ($details->{$name});
                }
                elseif (!CRM_Utils_System::isNull(explode(CRM_Core_DAO::VALUE_SEPARATOR,
                  $details->{$name}
                ))
                ) {
                  $customVal = $details->{$name};
                }

                //CRM-4582
                if (CRM_Utils_System::isNull($customVal)) {
                  continue;
                }

                $params[$index] = $customVal;
                $values[$index] = CRM_Core_BAO_CustomField::displayValue($customVal, $cfID);
                if ($field['data_type'] == 'ContactReference') {
                  $params[$index] = $values[$index];
                }
                if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
                  $cfID, 'is_search_range'
                )
                ) {
                  $customFieldName = "{$name}_from";
                }
              }
            }
            elseif ($name == 'image_URL') {
              [$width, $height] = getimagesize(CRM_Utils_String::unstupifyUrl($details->$name));
              [$thumbWidth, $thumbHeight] = CRM_Contact_BAO_Contact::getThumbSize($width, $height);

              $image_URL = '<img src="' . $details->$name . '" height= ' . $thumbHeight . ' width= ' . $thumbWidth . '  />';
              $values[$index] = "<a href='#' onclick='contactImagePopUp(\"{$details->$name}\", {$width}, {$height});'>{$image_URL}</a>";
            }
            elseif (in_array($name, [
              'birth_date',
              'deceased_date',
            ])) {
              // @todo this set should be determined from metadata, not hard-coded.
              $values[$index] = CRM_Utils_Date::customFormat($details->$name);
              $params[$index] = CRM_Utils_Date::isoToMysql($details->$name);
            }
            else {
              $dao = '';
              if ($index == 'Campaign') {
                $dao = 'CRM_Campaign_DAO_Campaign';
              }
              elseif ($index == 'Contribution Page') {
                $dao = 'CRM_Contribute_DAO_ContributionPage';
              }
              if ($dao) {
                $value = CRM_Core_DAO::getFieldValue($dao, $details->$name, 'title');
              }
              else {
                $value = $details->$name;
              }
              $values[$index] = $value;
            }
          }
        }
      }
      elseif (strpos($name, '-') !== FALSE) {
        [$fieldName, $id, $type] = CRM_Utils_System::explode('-', $name, 3);

        if (!in_array($fieldName, $multipleFields)) {
          if ($id == 'Primary') {
            // fix for CRM-1543
            // not sure why we'd every use Primary location type id
            // we need to fix the source if we are using it
            // $locationTypeName = CRM_Contact_BAO_Contact::getPrimaryLocationType( $cid );
            $locationTypeName = 1;
          }
          else {
            $locationTypeName = $locationTypes[$id] ?? NULL;
          }

          if (!$locationTypeName) {
            continue;
          }

          $detailName = "{$locationTypeName}-{$fieldName}";
          $detailName = str_replace(' ', '_', $detailName);

          if (in_array($fieldName, [
            'phone',
            'im',
            'email',
            'openid',
          ])) {
            if ($type) {
              $detailName .= "-{$type}";
            }
          }

          if (in_array($fieldName, [
            'state_province',
            'country',
            'county',
          ])) {
            $values[$index] = $details->$detailName;
            $idx = $detailName . '_id';
            $params[$index] = $details->$idx;
          }
          elseif ($fieldName == 'im') {
            $providerId = $detailName . '-provider_id';
            if (isset($imProviders[$details->$providerId])) {
              $values[$index] = $details->$detailName . " (" . $imProviders[$details->$providerId] . ")";
            }
            else {
              $values[$index] = $details->$detailName;
            }
            $params[$index] = $details->$detailName;
          }
          elseif ($fieldName == 'phone') {
            $phoneExtField = str_replace('phone', 'phone_ext', $detailName);
            if (isset($details->$phoneExtField)) {
              $values[$index] = $details->$detailName . " (" . $details->$phoneExtField . ")";
            }
            else {
              $values[$index] = $details->$detailName;
            }
            $params[$index] = $details->$detailName;
          }
          else {
            $values[$index] = $params[$index] = $details->$detailName;
          }
        }
        else {
          $detailName = "website-{$id}-{$fieldName}";
          $url = CRM_Utils_System::fixURL($details->$detailName);
          if ($details->$detailName) {
            $websiteTypeId = "website-{$id}-website_type_id";
            $websiteType = $websiteTypes[$details->$websiteTypeId];
            $values[$index] = "<a href=\"$url\">{$details->$detailName} ( {$websiteType} )</a>";
          }
          else {
            $values[$index] = '';
          }
        }
      }

      if ((CRM_Utils_Array::value('visibility', $field) == 'Public Pages and Listings') &&
        CRM_Core_Permission::check('profile listings and forms')
      ) {

        if (CRM_Utils_System::isNull($params[$index])) {
          $params[$index] = $values[$index];
        }
        if (!isset($params[$index])) {
          continue;
        }
        if (!$customFieldName) {
          $fieldName = $field['name'];
        }
        else {
          $fieldName = $customFieldName;
        }

        $url = NULL;
        if (CRM_Core_BAO_CustomField::getKeyID($field['name'])) {
          $htmlType = $field['html_type'];
          if ($htmlType == 'Link') {
            $url = $params[$index];
          }
          elseif (!empty($field['serialize'])) {
            $valSeparator = CRM_Core_DAO::VALUE_SEPARATOR;
            $selectedOptions = explode($valSeparator, $params[$index]);

            foreach ($selectedOptions as $key => $multiOption) {
              if ($multiOption) {
                $url[] = CRM_Utils_System::url('civicrm/profile',
                  'reset=1&force=1&gid=' . $field['group_id'] . '&' .
                  urlencode($fieldName) .
                  '=' .
                  urlencode($multiOption)
                );
              }
            }
          }
          else {
            $url = CRM_Utils_System::url('civicrm/profile',
              'reset=1&force=1&gid=' . $field['group_id'] . '&' .
              urlencode($fieldName) .
              '=' .
              urlencode($params[$index])
            );
          }
        }
        else {
          $url = CRM_Utils_System::url('civicrm/profile',
            'reset=1&force=1&gid=' . $field['group_id'] . '&' .
            urlencode($fieldName) .
            '=' .
            urlencode($params[$index])
          );
        }

        if ($url &&
          !empty($values[$index]) &&
          $searchable
        ) {

          if (is_array($url) && !empty($url)) {
            $links = [];
            $eachMultiValue = explode(', ', $values[$index]);
            foreach ($eachMultiValue as $key => $valueLabel) {
              $links[] = '<a href="' . $url[$key] . '">' . $valueLabel . '</a>';
            }
            $values[$index] = implode(', ', $links);
          }
          else {
            $values[$index] = '<a href="' . $url . '">' . $values[$index] . '</a>';
          }
        }
      }
    }
  }

  /**
   * Check if profile Group used by any module.
   *
   * @param int $id
   *   Profile Id.
   *
   * @return bool
   *
   */
  public static function usedByModule($id) {
    //check whether this group is used by any module(check uf join records)
    $sql = "SELECT id
                 FROM civicrm_uf_join
                 WHERE civicrm_uf_join.uf_group_id=$id";

    $dao = new CRM_Core_DAO();
    $dao->query($sql);
    if ($dao->fetch()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Delete the profile Group.
   *
   * @param int $id
   *   Profile Id.
   *
   * @return bool
   *
   * @deprecated
   */
  public static function del($id) {
    return (bool) static::deleteRecord(['id' => $id]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete' && $event->id) {
      // Check whether this group contains any profile fields
      $profileField = new CRM_Core_DAO_UFField();
      $profileField->uf_group_id = $event->id;
      $profileField->find();
      while ($profileField->fetch()) {
        CRM_Core_BAO_UFField::deleteRecord(['id' => $profileField->id]);
      }

      // Delete records from uf join table
      // Should probably use a deleteRecord rather than direct delete
      $ufJoin = new CRM_Core_DAO_UFJoin();
      $ufJoin->uf_group_id = $event->id;
      $ufJoin->delete();
    }
  }

  /**
   * Add the UF Group.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Deprecated array.
   *
   *
   * @return object
   */
  public static function add(&$params, $ids = []) {
    if (empty($params['id']) && !empty($ids['ufgroup'])) {
      $params['id'] = $ids['ufgroup'];
      CRM_Core_Error::deprecatedWarning('ids parameter is deprecated');
    }

    // Convert parameter names but don't overwrite existing data on updates
    // unless explicitly specified. And allow setting to null, so use
    // array_key_exists. i.e. we need to treat missing and empty separately.
    if (array_key_exists('group', $params)) {
      $params['limit_listings_group_id'] = $params['group'];
    }
    if (array_key_exists('add_contact_to_group', $params)) {
      $params['add_to_group_id'] = $params['add_contact_to_group'];
    }

    //CRM-15427
    if (!empty($params['group_type']) && is_array($params['group_type'])) {
      $params['group_type'] = implode(',', $params['group_type']);
    }

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'UFGroup', ($params['id'] ?? NULL), $params);

    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->copyValues($params);

    $ufGroupID = CRM_Utils_Array::value('ufgroup', $ids, CRM_Utils_Array::value('id', $params));
    if (!$ufGroupID && empty($params['name'])) {
      $ufGroup->name = CRM_Utils_String::munge($ufGroup->title, '_', 56);
    }
    $ufGroup->id = $ufGroupID;

    $ufGroup->save();

    if (!$ufGroupID && empty($params['name'])) {
      $ufGroup->name = $ufGroup->name . "_{$ufGroup->id}";
      $ufGroup->save();
    }

    CRM_Utils_Hook::post($hook, 'UFGroup', $ufGroup->id, $ufGroup);

    return $ufGroup;
  }

  /**
   * Make uf join entries for an uf group.
   *
   * @param int $weight
   * @param array $groupTypes
   *   An assoc array of name/value pairs.
   * @param int $ufGroupId
   *   Ufgroup id.
   */
  public static function createUFJoin($weight, $groupTypes, $ufGroupId) {

    // get ufjoin records for uf group
    $ufGroupRecord = CRM_Core_BAO_UFGroup::getUFJoinRecord($ufGroupId);

    // get the list of all ufgroup types
    $allUFGroupType = CRM_Core_SelectValues::ufGroupTypes();

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($groupTypes)) {
      $groupTypes = [];
    }

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($ufGroupRecord)) {
      $ufGroupRecord = [];
    }

    // check which values has to be inserted/deleted for contact
    $menuRebuild = FALSE;
    foreach ($allUFGroupType as $key => $value) {
      $joinParams = [];
      $joinParams['uf_group_id'] = $ufGroupId;
      $joinParams['module'] = $key;
      if ($key === 'User Account') {
        $menuRebuild = TRUE;
      }
      if (array_key_exists($key, $groupTypes) && !in_array($key, $ufGroupRecord)) {
        // insert a new record
        CRM_Core_BAO_UFGroup::addUFJoin($joinParams);
      }
      elseif (!array_key_exists($key, $groupTypes) && in_array($key, $ufGroupRecord)) {
        // delete a record for existing ufgroup
        CRM_Core_BAO_UFGroup::delUFJoin($joinParams);
      }
    }

    //update the weight
    $query = '
UPDATE civicrm_uf_join
SET    weight = %1
WHERE  uf_group_id = %2
AND    ( entity_id IS NULL OR entity_id <= 0 )
';
    $p = [
      1 => [$weight, 'Integer'],
      2 => [$ufGroupId, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($query, $p);

    // Do a menu rebuild, so it gets all the new menu entries for user account
    if ($menuRebuild) {
      $config = CRM_Core_Config::singleton();
      $config->userSystem->updateCategories();
    }
  }

  /**
   * Get the UF Join records for an ufgroup id.
   *
   * @param int $ufGroupId
   *   Uf group id.
   * @param int $displayName
   *   If set return display name in array.
   * @param int $status
   *   If set return module other than default modules (User Account/User registration/Profile).
   *
   * @return array
   *
   */
  public static function getUFJoinRecord($ufGroupId = NULL, $displayName = NULL, $status = NULL) {
    if ($displayName) {
      $UFGroupType = [];
      $UFGroupType = CRM_Core_SelectValues::ufGroupTypes();
    }

    $ufJoin = [];
    $dao = new CRM_Core_DAO_UFJoin();

    if ($ufGroupId) {
      $dao->uf_group_id = $ufGroupId;
    }

    $dao->find();
    $ufJoin = [];

    while ($dao->fetch()) {
      if (!$displayName) {
        $ufJoin[$dao->id] = $dao->module;
      }
      else {
        if (isset($UFGroupType[$dao->module])) {
          // skip the default modules
          if (!$status) {
            $ufJoin[$dao->id] = $UFGroupType[$dao->module];
          }
          // added for CRM-1475
        }
        elseif (!CRM_Utils_Array::key($dao->module, $ufJoin)) {
          $ufJoin[$dao->id] = $dao->module;
        }
      }
    }
    return $ufJoin;
  }

  /**
   * Function takes an associative array and creates a ufjoin record for ufgroup.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Core_DAO_UFJoin
   */
  public static function addUFJoin(&$params) {
    $ufJoin = new CRM_Core_DAO_UFJoin();
    $ufJoin->copyValues($params);
    $ufJoin->save();
    return $ufJoin;
  }

  /**
   * Delete the uf join record for an uf group.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   */
  public static function delUFJoin(&$params) {
    $ufJoin = new CRM_Core_DAO_UFJoin();
    $ufJoin->copyValues($params);
    $ufJoin->delete();
  }

  /**
   * Get the weight for ufjoin record.
   *
   * @param int $ufGroupId
   *   If $ufGroupId get update weight or add weight.
   *
   * @return int
   *   weight of the UFGroup
   */
  public static function getWeight($ufGroupId = NULL) {
    //calculate the weight
    $p = [];
    if (!$ufGroupId) {
      $queryString = "SELECT ( MAX(civicrm_uf_join.weight)+1) as new_weight
                            FROM civicrm_uf_join
                            WHERE module = 'User Registration' OR module = 'User Account' OR module = 'Profile'";
    }
    else {
      $queryString = "SELECT MAX(civicrm_uf_join.weight) as new_weight
                            FROM civicrm_uf_join
                            WHERE civicrm_uf_join.uf_group_id = %1
                            AND ( entity_id IS NULL OR entity_id <= 0 )";
      $p[1] = [$ufGroupId, 'Integer'];
    }

    $dao = CRM_Core_DAO::executeQuery($queryString, $p);
    $dao->fetch();
    return ($dao->new_weight) ? $dao->new_weight : 1;
  }

  /**
   * Get the uf group for a module.
   *
   * @param string $moduleName
   *   Module name.
   * @param int $count
   *   No to increment the weight.
   * @param bool $skipPermission
   * @param int $op
   *   Which operation (view, edit, create, etc) to check permission for.
   * @param array|null $returnFields
   *
   * @return array
   *   array of ufgroups for a module
   */
  public static function getModuleUFGroup($moduleName = NULL, $count = 0, $skipPermission = TRUE, $op = CRM_Core_Permission::VIEW, $returnFields = NULL) {
    $selectFields = ['id', 'title', 'created_id', 'is_active', 'is_reserved', 'group_type', 'description'];

    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_uf_group', 'frontend_title')) {
      $selectFields[] = 'frontend_title';
    }

    if (!empty($returnFields)) {
      $selectFields = array_merge($returnFields, array_diff($selectFields, $returnFields));
    }

    $queryString = 'SELECT civicrm_uf_group.' . implode(', civicrm_uf_group.', $selectFields) . '
                        FROM civicrm_uf_group
                        LEFT JOIN civicrm_uf_join ON (civicrm_uf_group.id = uf_group_id)';
    $p = [];
    if ($moduleName) {
      $queryString .= ' AND civicrm_uf_group.is_active = 1
                              WHERE civicrm_uf_join.module = %2';
      $p[2] = [$moduleName, 'String'];
    }

    // add permissioning for profiles only if not registration
    if (!$skipPermission) {
      $permissionClause = CRM_Core_Permission::ufGroupClause($op, 'civicrm_uf_group.');
      if (strpos($queryString, 'WHERE') !== FALSE) {
        $queryString .= " AND $permissionClause ";
      }
      else {
        $queryString .= " $permissionClause ";
      }
    }

    $queryString .= ' ORDER BY civicrm_uf_join.weight, civicrm_uf_group.title';
    $dao = CRM_Core_DAO::executeQuery($queryString, $p);

    $ufGroups = [];
    while ($dao->fetch()) {
      //skip mix profiles in user Registration / User Account
      if (($moduleName === 'User Registration' || $moduleName === 'User Account') &&
        CRM_Core_BAO_UFField::checkProfileType($dao->id)
      ) {
        continue;
      }
      foreach ($selectFields as $key => $field) {
        if ($field === 'id') {
          continue;
        }
        $ufGroups[$dao->id][$field] = $dao->$field;
      }
    }

    // Allow other modules to alter/override the UFGroups.
    CRM_Utils_Hook::buildUFGroupsForModule($moduleName, $ufGroups);

    return $ufGroups;
  }

  /**
   * Filter ufgroups based on logged in user contact type.
   *
   * @param int $ufGroupId
   *   Uf group id (profile id).
   * @param int $contactID
   *
   * @return bool
   *   true or false
   */
  public static function filterUFGroups($ufGroupId, $contactID = NULL) {
    if (!$contactID) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if ($contactID) {
      //get the contact type
      $contactType = CRM_Contact_BAO_Contact::getContactType($contactID);

      //match if exixting contact type is same as profile contact type
      $profileType = CRM_Core_BAO_UFField::getProfileType($ufGroupId);

      if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
        $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);

        //in some cases getBasicType() returns a cached array instead of string. Example: array ('sponsor' => 'organization')
        if (is_array($profileType)) {
          $profileType = array_shift($profileType);
        }
      }

      //allow special mix profiles for Contribution and Participant
      $specialProfiles = ['Contribution', 'Participant', 'Membership'];

      if (in_array($profileType, $specialProfiles)) {
        return TRUE;
      }

      if (($contactType == $profileType) || $profileType == 'Contact') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Add profile field to a form.
   *
   * @param CRM_Core_Form $form
   * @param array $field
   *   Properties.
   * @param int $mode
   *   Profile mode.
   * @param int $contactId
   * @param bool $online
   * @param string $usedFor
   *   For building up prefixed fieldname for special cases (e.g. onBehalf, Honor).
   * @param int $rowNumber
   * @param string $prefix
   *
   * @return null
   */
  public static function buildProfile(
    &$form,
    &$field,
    $mode,
    $contactId = NULL,
    $online = FALSE,
    $usedFor = NULL,
    $rowNumber = NULL,
    $prefix = ''
  ) {
    $defaultValues = [];
    $fieldName = $field['name'];
    $title = $field['title'];
    $attributes = $field['attributes'];
    $rule = $field['rule'];
    $view = $field['is_view'];
    $required = ($mode == CRM_Profile_Form::MODE_SEARCH) ? FALSE : $field['is_required'];
    $search = $mode == CRM_Profile_Form::MODE_SEARCH;
    $isShared = CRM_Utils_Array::value('is_shared', $field, 0);

    // do not display view fields in drupal registration form
    // CRM-4632
    if ($view && $mode == CRM_Profile_Form::MODE_REGISTER) {
      return NULL;
    }

    if ($usedFor == 'onbehalf') {
      $name = "onbehalf[$fieldName]";
    }
    elseif ($usedFor == 'honor') {
      $name = "honor[$fieldName]";
    }
    elseif ($contactId && !$online) {
      $name = "field[$contactId][$fieldName]";
    }
    elseif ($rowNumber) {
      $name = "field[$rowNumber][$fieldName]";
    }
    elseif (!empty($prefix)) {
      $name = $prefix . "[$fieldName]";
    }
    else {
      $name = $fieldName;
    }

    $selectAttributes = ['class' => 'crm-select2', 'placeholder' => TRUE];

    if ($fieldName == 'image_URL' && $mode == CRM_Profile_Form::MODE_EDIT) {
      $deleteExtra = json_encode(ts('Are you sure you want to delete the contact image?'));
      $deleteURL = [
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete Contact Image'),
          'url' => 'civicrm/contact/image',
          'qs' => 'reset=1&id=%%id%%&gid=%%gid%%&action=delete',
          'extra' => 'onclick = "' . htmlspecialchars("if (confirm($deleteExtra)) this.href+='&confirmed=1'; else return false;") . '"',
        ],
      ];
      $deleteURL = CRM_Core_Action::formLink($deleteURL,
        CRM_Core_Action::DELETE,
        [
          'id' => $form->get('id'),
          'gid' => $form->get('gid'),
        ],
        ts('more'),
        FALSE,
        'contact.profileimage.delete',
        'Contact',
        $form->get('id')
      );
      $form->assign('deleteURL', $deleteURL);
    }
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    if (substr($fieldName, 0, 14) === 'state_province') {
      $form->addChainSelect($name, ['label' => $title, 'required' => $required]);
      $config = CRM_Core_Config::singleton();
      if (!in_array($mode, [CRM_Profile_Form::MODE_EDIT, CRM_Profile_Form::MODE_SEARCH]) &&
        $config->defaultContactStateProvince
      ) {
        $defaultValues[$name] = $config->defaultContactStateProvince;
        $form->setDefaults($defaultValues);
      }
    }
    elseif (substr($fieldName, 0, 7) === 'country') {
      $form->add('select', $name, $title, CRM_Core_PseudoConstant::country(), $required, $selectAttributes);
      $config = CRM_Core_Config::singleton();
      if (!in_array($mode, [CRM_Profile_Form::MODE_EDIT, CRM_Profile_Form::MODE_SEARCH]) &&
        $config->defaultContactCountry
      ) {
        $defaultValues[$name] = $config->defaultContactCountry;
        $form->setDefaults($defaultValues);
      }
    }
    elseif (substr($fieldName, 0, 6) === 'county') {
      if ($addressOptions['county']) {
        $form->addChainSelect($name, ['label' => $title, 'required' => $required]);
      }
    }
    elseif (substr($fieldName, 0, 9) === 'image_URL') {
      if (!isset($attributes['accept'])) {
        $attributes['accept'] = 'image/png, image/jpeg, image/gif';
      }
      $form->add('file', $name, $title, $attributes, $required);
      $form->addUploadElement($name);
    }
    elseif (substr($fieldName, 0, 2) === 'im') {
      $form->add('text', $name, $title, $attributes, $required);
      if (!$contactId) {
        if ($usedFor) {
          if (substr($name, -1) === ']') {
            $providerName = substr($name, 0, -1) . '-provider_id]';
          }
          $form->add('select', $providerName, NULL,
            CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id'), $required
          );
        }
        else {
          $form->add('select', $name . '-provider_id', $title,
            CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id'), $required
          );
        }

        if ($view && $mode != CRM_Profile_Form::MODE_SEARCH) {
          $form->freeze($name . '-provider_id');
        }
      }
    }
    elseif (CRM_Utils_Array::value('name', $field) == 'membership_type') {
      [$orgInfo, $types] = CRM_Member_BAO_MembershipType::getMembershipTypeInfo();
      $sel = &$form->addElement('hierselect', $name, $title);
      $select = ['' => ts('- select membership type -')];
      if (count($orgInfo) == 1 && $field['is_required']) {
        // we only have one org - so we should default to it. Not sure about defaulting to first type
        // as it could be missed - so adding a select
        // however, possibly that is more similar to the membership form
        if (count($types[1]) > 1) {
          $types[1] = $select + $types[1];
        }
      }
      else {
        $orgInfo = $select + $orgInfo;
      }
      $sel->setOptions([$orgInfo, $types]);
    }
    elseif (CRM_Utils_Array::value('name', $field) == 'membership_status') {
      $form->add('select', $name, $title,
        CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'), $required
      );
    }
    elseif (in_array($fieldName, ['gender_id', 'communication_style_id'])) {
      $options = [];
      $pseudoValues = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', $fieldName);
      $form->addRadio($name, ts('%1', [1 => $title]), $pseudoValues, ['allowClear' => !$required], NULL, $required);
    }
    elseif ($fieldName === 'prefix_id' || $fieldName === 'suffix_id') {
      $form->addSelect($name, [
        'label' => $title,
        'entity' => 'contact',
        'field' => $fieldName,
        'class' => 'six',
        'placeholder' => '',
      ], $required);
    }
    elseif ($fieldName === 'contact_sub_type') {
      $gId = $form->get('gid') ? $form->get('gid') : CRM_Utils_Array::value('group_id', $field);
      if ($usedFor == 'onbehalf') {
        $profileType = 'Organization';
      }
      elseif ($usedFor == 'honor') {
        $profileType = CRM_Core_BAO_UFField::getProfileType($form->_params['honoree_profile_id']);
      }
      else {
        $profileType = $gId ? CRM_Core_BAO_UFField::getProfileType($gId) : NULL;
        if ($profileType == 'Contact') {
          $profileType = 'Individual';
        }
      }

      $setSubtype = FALSE;
      if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
        $setSubtype = $profileType;
        $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
      }

      $subtypes = $profileType ? CRM_Contact_BAO_ContactType::subTypePairs($profileType) : [];

      if ($setSubtype) {
        $subtypeList = [];
        $subtypeList[$setSubtype] = $subtypes[$setSubtype];
      }
      else {
        $subtypeList = $subtypes;
      }

      $form->add('select', $name, $title, $subtypeList, $required, ['class' => 'crm-select2', 'multiple' => TRUE]);
    }
    elseif (in_array($fieldName, CRM_Contact_BAO_Contact::$_greetingTypes)) {
      // Get contact type for greeting selector
      $gId = $form->get('gid') ?: CRM_Utils_Array::value('group_id', $field);
      $profileType = CRM_Core_BAO_UFField::getProfileType($gId, TRUE, FALSE, TRUE);

      if (!$profileType || in_array($profileType, ['Contact', 'Contribution', 'Participant', 'Membership'])) {
        $profileType = ($profileType == 'Contact' && $form->get('id')) ? CRM_Contact_BAO_Contact::getContactType($form->get('id')) : 'Individual';
      }
      if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
        $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
      }
      $greeting = [
        'contact_type' => $profileType,
        'greeting_type' => $fieldName,
      ];
      $form->add('select', $name, $title, CRM_Core_PseudoConstant::greeting($greeting), $required, ['placeholder' => TRUE]);
      // add custom greeting element
      $form->add('text', $fieldName . '_custom', ts('Custom %1', [1 => ucwords(str_replace('_', ' ', $fieldName))]),
        NULL, FALSE
      );
    }
    elseif ($fieldName === 'preferred_communication_method') {
      $communicationFields = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');
      foreach ($communicationFields as $key => $var) {
        if ($key == '') {
          continue;
        }
        $communicationOptions[] = $form->createElement('checkbox', $key, NULL, $var);
      }
      $form->addGroup($communicationOptions, $name, $title, '<br/>');
    }
    elseif ($fieldName === 'preferred_mail_format') {
      $form->add('select', $name, $title, CRM_Core_SelectValues::pmf());
    }
    elseif ($fieldName === 'preferred_language') {
      $form->add('select', $name, $title, CRM_Contact_BAO_Contact::buildOptions('preferred_language'), $required, ['placeholder' => TRUE]);
    }
    elseif ($fieldName == 'external_identifier') {
      $form->add('text', $name, $title, $attributes, $required);
      $contID = $contactId;
      if (!$contID) {
        $contID = $form->get('id');
      }
      $form->addRule($name,
        ts('External ID already exists in Database.'),
        'objectExists',
        ['CRM_Contact_DAO_Contact', $contID, 'external_identifier']
      );
    }
    elseif ($fieldName === 'group') {
      CRM_Contact_Form_Edit_TagsAndGroups::buildQuickForm($form, $contactId,
        CRM_Contact_Form_Edit_TagsAndGroups::GROUP,
        TRUE, $required,
        $title, NULL, $name, 'checkbox', TRUE
      );
    }
    elseif ($fieldName === 'tag') {
      CRM_Contact_Form_Edit_TagsAndGroups::buildQuickForm($form, $contactId,
        CRM_Contact_Form_Edit_TagsAndGroups::TAG,
        FALSE, $required,
        NULL, $title, $name
      );
    }
    elseif (substr($fieldName, 0, 4) === 'url-') {
      $form->add('text', $name, $title, CRM_Core_DAO::getAttribute('CRM_Core_DAO_Website', 'url'), $required);
      $form->addRule($name, ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'), 'url');
    }
    // Note should be rendered as textarea
    elseif (substr($fieldName, -4) == 'note') {
      $form->add('textarea', $name, $title, $attributes, $required);
    }
    elseif (substr($fieldName, 0, 6) === 'custom') {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($fieldName);
      if ($customFieldID) {
        CRM_Core_BAO_CustomField::addQuickFormElement($form, $name, $customFieldID, $required, $search, $title);
      }
    }
    elseif (substr($fieldName, 0, 14) === 'address_custom') {
      [$fName, $locTypeId] = CRM_Utils_System::explode('-', $fieldName, 2);
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID(substr($fName, 8));
      if ($customFieldID) {
        CRM_Core_BAO_CustomField::addQuickFormElement($form, $name, $customFieldID, $required, $search, $title);
      }
    }
    elseif ($fieldName == 'send_receipt') {
      $form->addElement('checkbox', $name, $title);
    }
    elseif ($fieldName == 'soft_credit') {
      $form->addEntityRef("soft_credit_contact_id[$rowNumber]", ts('Soft Credit To'), ['create' => TRUE]);
      $form->addMoney("soft_credit_amount[{$rowNumber}]", ts('Amount'), FALSE, NULL, FALSE);
    }
    elseif ($fieldName === 'product_name') {
      [$products, $options] = CRM_Contribute_BAO_Premium::getPremiumProductInfo();
      $sel = &$form->addElement('hierselect', $name, $title);
      $products = ['0' => ts('- select %1 -', [1 => $title])] + $products;
      $sel->setOptions([$products, $options]);
    }
    elseif ($fieldName === 'payment_instrument') {
      $form->add('select', $name, $title,
        CRM_Contribute_PseudoConstant::paymentInstrument(), $required, ['placeholder' => TRUE]);
    }
    elseif ($fieldName === 'financial_type') {
      $form->add('select', $name, $title,
        CRM_Contribute_PseudoConstant::financialType(), $required, ['placeholder' => TRUE]
      );
    }
    elseif ($fieldName === 'contribution_status_id') {
      $contributionStatuses = CRM_Contribute_BAO_Contribution_Utils::getPendingCompleteFailedAndCancelledStatuses();

      $form->add('select', $name, $title,
        $contributionStatuses, $required, ['placeholder' => TRUE]
      );
    }
    elseif ($fieldName === 'soft_credit_type') {
      $name = "soft_credit_type[$rowNumber]";
      $form->add('select', $name, $title,
        CRM_Core_OptionGroup::values("soft_credit_type"), ['placeholder' => TRUE]
      );
      //CRM-15350: choose SCT field default value as 'Gift' for membership use
      //else (for contribution), use configured SCT default value
      $SCTDefaultValue = CRM_Core_OptionGroup::getDefaultValue("soft_credit_type");
      if ($field['field_type'] == 'Membership') {
        $SCTDefaultValue = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'gift');
      }
      $form->addElement('hidden', 'sct_default_id', $SCTDefaultValue, ['id' => 'sct_default_id']);
    }
    elseif ($fieldName == 'contribution_soft_credit_pcp_id') {
      CRM_Contribute_Form_SoftCredit::addPCPFields($form, "[$rowNumber]");
    }
    elseif ($fieldName == 'currency') {
      $form->addCurrency($name, $title, $required, NULL, FALSE, FALSE);
    }
    elseif ($fieldName == 'contribution_page_id') {
      $form->add('select', $name, $title,
        CRM_Contribute_PseudoConstant::contributionPage(), $required, [
          'class' => 'big',
          'placeholder' => TRUE,
        ]
      );
    }
    elseif ($fieldName == 'activity_status_id') {
      $form->add('select', $name, $title,
        CRM_Core_PseudoConstant::activityStatus(), $required, ['placeholder' => TRUE]
      );
    }
    elseif ($fieldName == 'activity_engagement_level') {
      $form->add('select', $name, $title,
        CRM_Campaign_PseudoConstant::engagementLevel(), $required, ['placeholder' => TRUE]
      );
    }
    elseif ($fieldName == 'participant_status') {
      $cond = NULL;
      if ($online == TRUE) {
        $cond = 'visibility_id = 1';
      }
      $form->add('select', $name, $title,
        CRM_Event_PseudoConstant::participantStatus(NULL, $cond, 'label'), $required, ['placeholder' => TRUE]
      );
    }
    elseif ($fieldName == 'participant_role') {
      if (!empty($field['is_multiple'])) {
        $form->addCheckBox($name, $title, CRM_Event_PseudoConstant::participantRole(), NULL, NULL, NULL, NULL, '&nbsp', TRUE);
      }
      else {
        $form->add('select', $name, $title,
          CRM_Event_PseudoConstant::participantRole(), $required, ['placeholder' => TRUE]
        );
      }
    }
    elseif ($fieldName == 'world_region') {
      $form->add('select', $name, $title, CRM_Core_PseudoConstant::worldRegion(), $required, $selectAttributes);
    }
    elseif ($fieldName == 'signature_html') {
      $form->add('wysiwyg', $name, $title, CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', $fieldName));
    }
    elseif ($fieldName == 'signature_text') {
      $form->add('textarea', $name, $title, CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', $fieldName));
    }
    elseif (substr($fieldName, -11) == 'campaign_id') {
      if (CRM_Campaign_BAO_Campaign::isComponentEnabled()) {
        $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value($contactId,
          $form->_componentCampaigns
        ));
        $form->add('select', $name, $title,
          $campaigns, $required,
          [
            'class' => 'crm-select2 big',
            'placeholder' => TRUE,
          ]
        );
      }
    }
    elseif ($fieldName == 'activity_details') {
      $form->add('wysiwyg', $name, $title, ['rows' => 4, 'cols' => 60], $required);
    }
    elseif ($fieldName == 'activity_duration') {
      $form->add('text', $name, $title, $attributes, $required);
      $form->addRule($name, ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger');
    }
    elseif ($fieldName == 'case_status') {
      $form->add('select', $name, $title,
        CRM_Case_BAO_Case::buildOptions('case_status_id', 'create'),
        $required, ['placeholder' => TRUE]
      );
    }
    else {
      if (substr($fieldName, 0, 3) === 'is_' or substr($fieldName, 0, 7) === 'do_not_') {
        $form->add('advcheckbox', $name, $title, $attributes, $required);
      }
      elseif (CRM_Utils_Array::value('html_type', $field) === 'Select Date') {
        $extra = isset($field['datepicker']) ? $field['datepicker']['extra'] : CRM_Utils_Date::getDatePickerExtra($field);
        $attributes = isset($field['datepicker']) ? $field['datepicker']['attributes'] : CRM_Utils_Date::getDatePickerAttributes($field);
        $form->add('datepicker', $name, $title, $attributes, $required, $extra);
      }
      else {
        $form->add('text', $name, $title, $attributes, $required);
      }
    }

    static $hiddenSubtype = FALSE;
    if (!$hiddenSubtype && CRM_Contact_BAO_ContactType::isaSubType($field['field_type'])) {
      // In registration mode params are submitted via POST and we don't have any clue
      // about profile-id or the profile-type (which could be a subtype)
      // To generalize the  behavior and simplify the process,
      // lets always add the hidden
      //subtype value if there is any, and we won't have to
      // compute it while processing.
      if ($usedFor) {
        $form->addElement('hidden', $usedFor . '[contact_sub_type]', $field['field_type']);
      }
      else {
        $form->addElement('hidden', 'contact_sub_type_hidden', $field['field_type']);
      }
      $hiddenSubtype = TRUE;
    }

    if (($view && $mode != CRM_Profile_Form::MODE_SEARCH) || $isShared) {
      $form->freeze($name);
    }

    //add the rules
    if (in_array($fieldName, [
      'non_deductible_amount',
      'total_amount',
      'fee_amount',
      'net_amount',
    ])) {
      $form->addRule($name, ts('Please enter a valid amount.'), 'money');
    }
    if ($rule) {
      if (!($rule == 'email' && $mode == CRM_Profile_Form::MODE_SEARCH)) {
        $form->addRule($name, ts('Please enter a valid %1', [1 => $title]), $rule);
      }
    }
  }

  /**
   * Set profile defaults.
   *
   * @param int $contactId
   *   Contact id.
   * @param array $fields
   *   Associative array of fields.
   * @param array $defaults
   *   Defaults array.
   * @param bool $singleProfile
   *   True for single profile else false(Update multiple items).
   * @param int $componentId
   *   Id for specific components like contribute, event etc.
   * @param null $component
   */
  public static function setProfileDefaults(
    $contactId, &$fields, &$defaults,
    $singleProfile = TRUE, $componentId = NULL, $component = NULL
  ) {
    if (!$componentId) {
      //get the contact details
      $contactDetails = CRM_Contact_BAO_Contact::getHierContactDetails($contactId, $fields);
      $details = $contactDetails[$contactId] ?? NULL;
      $multipleFields = ['website' => 'url'];

      //start of code to set the default values
      foreach ($fields as $name => $field) {
        // skip pseudo fields
        if (substr($name, 0, 9) == 'phone_ext') {
          continue;
        }

        //set the field name depending upon the profile mode(single/multiple)
        if ($singleProfile) {
          $fldName = $name;
        }
        else {
          $fldName = "field[$contactId][$name]";
        }

        if ($name == 'group') {
          CRM_Contact_Form_Edit_TagsAndGroups::setDefaults($contactId, $defaults, CRM_Contact_Form_Edit_TagsAndGroups::GROUP, $fldName);
        }
        if ($name == 'tag') {
          CRM_Contact_Form_Edit_TagsAndGroups::setDefaults($contactId, $defaults, CRM_Contact_Form_Edit_TagsAndGroups::TAG, $fldName);
        }

        if (!empty($details[$name]) || isset($details[$name])) {
          //to handle custom data (checkbox) to be written
          // to handle birth/deceased date, greeting_type and few other fields
          if (in_array($name, CRM_Contact_BAO_Contact::$_greetingTypes)) {
            $defaults[$fldName] = $details[$name . '_id'];
            $defaults[$name . '_custom'] = $details[$name . '_custom'];
          }
          elseif ($name == 'preferred_communication_method') {
            $v = $details[$name];
            if (!is_array($details[$name])) {
              $v = explode(CRM_Core_DAO::VALUE_SEPARATOR, $v);
            }
            foreach ($v as $item) {
              if ($item) {
                $defaults[$fldName . "[$item]"] = 1;
              }
            }
          }
          elseif ($name == 'contact_sub_type') {
            $defaults[$fldName] = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($details[$name], CRM_Core_DAO::VALUE_SEPARATOR));
          }
          elseif ($name == 'world_region') {
            $defaults[$fldName] = $details['worldregion_id'];
          }
          elseif (CRM_Core_BAO_CustomField::getKeyID($name)) {
            $defaults[$fldName] = self::formatCustomValue($field, $details[$name]);
            if (!$singleProfile && $field['html_type'] === 'CheckBox') {
              // For batch update profile there needs to be a key lik
              // $defaults['field[166]['custom_8'][2]'] => 1 where
              // 166 is the conntact id, 8 is the field id and 2 is the checkbox option.
              foreach ($defaults[$fldName] as $itemKey => $itemValue) {
                $defaults[$fldName . '[' . $itemKey . ']'] = $itemValue;
              }
            }
          }
          else {
            $defaults[$fldName] = $details[$name];
          }
        }
        else {
          $blocks = ['email', 'phone', 'im', 'openid'];
          [$fieldName, $locTypeId, $phoneTypeId] = CRM_Utils_System::explode('-', $name, 3);
          if (!in_array($fieldName, $multipleFields)) {
            if (is_array($details)) {
              foreach ($details as $key => $value) {
                // when we fixed CRM-5319 - get primary loc
                // type as per loc field and removed below code.
                $primaryLocationType = FALSE;
                if ($locTypeId == 'Primary') {
                  if (is_array($value) && array_key_exists($fieldName, $value)) {
                    $primaryLocationType = TRUE;
                    if (in_array($fieldName, $blocks)) {
                      $locTypeId = CRM_Contact_BAO_Contact::getPrimaryLocationType($contactId, FALSE, $fieldName);
                    }
                    else {
                      $locTypeId = CRM_Contact_BAO_Contact::getPrimaryLocationType($contactId, FALSE, 'address');
                    }
                  }
                }

                // fixed for CRM-665
                if (is_numeric($locTypeId)) {
                  if ($primaryLocationType || $locTypeId == CRM_Utils_Array::value('location_type_id', $value)) {
                    if (!empty($value[$fieldName])) {
                      //to handle stateprovince and country
                      if ($fieldName == 'state_province') {
                        $defaults[$fldName] = $value['state_province_id'];
                      }
                      elseif ($fieldName == 'county') {
                        $defaults[$fldName] = $value['county_id'];
                      }
                      elseif ($fieldName == 'country') {
                        if (!isset($value['country_id']) || !$value['country_id']) {
                          $config = CRM_Core_Config::singleton();
                          if ($config->defaultContactCountry) {
                            $defaults[$fldName] = $config->defaultContactCountry;
                          }
                        }
                        else {
                          $defaults[$fldName] = $value['country_id'];
                        }
                      }
                      elseif ($fieldName == 'phone') {
                        if ($phoneTypeId) {
                          if (isset($value['phone'][$phoneTypeId])) {
                            $defaults[$fldName] = $value['phone'][$phoneTypeId];
                          }
                          if (isset($value['phone_ext'][$phoneTypeId])) {
                            $defaults[str_replace('phone', 'phone_ext', $fldName)] = $value['phone_ext'][$phoneTypeId];
                          }
                        }
                        else {
                          $phoneDefault = $value['phone'] ?? NULL;
                          // CRM-9216
                          if (!is_array($phoneDefault)) {
                            $defaults[$fldName] = $phoneDefault;
                          }
                        }
                      }
                      elseif ($fieldName == 'email') {
                        //adding the first email (currently we don't support multiple emails of same location type)
                        $defaults[$fldName] = $value['email'];
                      }
                      elseif ($fieldName == 'im') {
                        //adding the first im (currently we don't support multiple ims of same location type)
                        $defaults[$fldName] = $value['im'];
                        $defaults[$fldName . '-provider_id'] = $value['im_provider_id'];
                      }
                      else {
                        $defaults[$fldName] = $value[$fieldName];
                      }
                    }
                    elseif (strpos($fieldName, 'address_custom') === 0 && !empty($value[substr($fieldName, 8)])) {
                      $defaults[$fldName] = self::formatCustomValue($field, $value[substr($fieldName, 8)]);
                    }
                  }
                }
                elseif (strpos($fieldName, 'address_custom') === 0 && !empty($value[substr($fieldName, 8)])) {
                  $defaults[$fldName] = self::formatCustomValue($field, $value[substr($fieldName, 8)]);
                }
              }
            }
          }
          else {
            if (is_array($details)) {
              if ($fieldName === 'url'
                && !empty($details['website'])
                && !empty($details['website'][$locTypeId])
              ) {
                $defaults[$fldName] = $details['website'][$locTypeId]['url'] ?? NULL;
              }
            }
          }
        }
      }
    }

    // Handling Contribution Part of the batch profile
    if (CRM_Core_Permission::access('CiviContribute') && $component == 'Contribute') {
      self::setComponentDefaults($fields, $componentId, $component, $defaults);
    }

    // Handling Event Participation Part of the batch profile
    if (CRM_Core_Permission::access('CiviEvent') && $component == 'Event') {
      self::setComponentDefaults($fields, $componentId, $component, $defaults);
    }

    // Handling membership Part of the batch profile
    if (CRM_Core_Permission::access('CiviMember') && $component == 'Membership') {
      self::setComponentDefaults($fields, $componentId, $component, $defaults);
    }

    // Handling Activity Part of the batch profile
    if ($component == 'Activity') {
      self::setComponentDefaults($fields, $componentId, $component, $defaults);
    }

    // Handling Case Part of the batch profile
    if (CRM_Core_Permission::access('CiviCase') && $component == 'Case') {
      self::setComponentDefaults($fields, $componentId, $component, $defaults);
    }
  }

  /**
   * Get profiles by type  eg: pure Individual etc
   *
   * @param array $types
   *   Associative array of types eg: types('Individual').
   * @param bool $onlyPure
   *   True if only pure profiles are required.
   *
   * @return array
   *   associative array of profiles
   */
  public static function getProfiles($types, $onlyPure = FALSE) {
    $profiles = [];
    $ufGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');

    CRM_Utils_Hook::aclGroup(CRM_Core_Permission::ADMIN, NULL, 'civicrm_uf_group', $ufGroups, $ufGroups);

    // Exclude Batch Data Entry profiles - CRM-10901
    $batchProfiles = CRM_Core_BAO_UFGroup::getBatchProfiles();

    foreach ($ufGroups as $id => $title) {
      $ptype = CRM_Core_BAO_UFField::getProfileType($id, FALSE, $onlyPure);
      if (in_array($ptype, $types) && !array_key_exists($id, $batchProfiles)) {
        $profiles[$id] = $title;
      }
    }
    return $profiles;
  }

  /**
   * Check whether a profile is valid combination of
   * required and/or optional profile types
   *
   * @param array $required
   *   Array of types those are required.
   * @param array $optional
   *   Array of types those are optional.
   *
   * @return array
   *   associative array of profiles
   */
  public static function getValidProfiles($required, $optional = NULL) {
    if (!is_array($required) || empty($required)) {
      return NULL;
    }

    $profiles = [];
    $ufGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');

    CRM_Utils_Hook::aclGroup(CRM_Core_Permission::ADMIN, NULL, 'civicrm_uf_group', $ufGroups, $ufGroups);

    foreach ($ufGroups as $id => $title) {
      $type = CRM_Core_BAO_UFField::checkValidProfileType($id, $required, $optional);
      if ($type) {
        $profiles[$id] = $title;
      }
    }

    return $profiles;
  }

  /**
   * Check whether a profile is valid combination of
   * required profile fields
   *
   * @param array $ufId
   *   Integer id of the profile.
   * @param array $required
   *   Array of fields those are required in the profile.
   *
   * @return array
   *   associative array of profiles
   */
  public static function checkValidProfile($ufId, $required = NULL) {
    $validProfile = FALSE;
    if (!$ufId) {
      return $validProfile;
    }

    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $ufId, 'is_active')) {
      return $validProfile;
    }

    $profileFields = self::getFields($ufId, FALSE, CRM_Core_Action::VIEW, NULL,
      NULL, FALSE, NULL, FALSE, NULL,
      CRM_Core_Permission::CREATE, NULL
    );

    $validProfile = [];
    if (!empty($profileFields)) {
      $fields = array_keys($profileFields);
      foreach ($fields as $val) {
        foreach ($required as $key => $field) {
          if (strpos($val, $field) === 0) {
            unset($required[$key]);
          }
        }
      }

      $validProfile = (empty($required));
    }

    return $validProfile;
  }

  /**
   * Get default value for Register.
   *
   * @param array $fields
   * @param array $defaults
   *
   * @return array
   */
  public static function setRegisterDefaults(&$fields, &$defaults) {
    $config = CRM_Core_Config::singleton();
    foreach ($fields as $name => $field) {
      if (substr($name, 0, 8) == 'country-') {
        if (!empty($config->defaultContactCountry)) {
          $defaults[$name] = $config->defaultContactCountry;
        }
      }
      elseif (substr($name, 0, 15) == 'state_province-') {
        if (!empty($config->defaultContactStateProvince)) {
          $defaults[$name] = $config->defaultContactStateProvince;
        }
      }
    }
    return $defaults;
  }

  /**
   * make a copy of a profile, including
   * all the fields in the profile
   *
   * @param int $id
   *   The profile id to copy.
   *
   * @return \CRM_Core_DAO
   */
  public static function copy($id) {
    $maxId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_uf_group");

    $title = ts('[Copy id %1]', [1 => $maxId + 1]);
    $fieldsFix = [
      'suffix' => [
        'title' => ' ' . $title,
        'name' => '__Copy_id_' . ($maxId + 1) . '_',
      ],
    ];

    $copy = CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFGroup',
      ['id' => $id],
      NULL,
      $fieldsFix
    );

    if ($pos = strrpos($copy->name, "_{$id}")) {
      $copy->name = substr_replace($copy->name, '', $pos);
    }
    $copy->name = CRM_Utils_String::munge($copy->name, '_', 56) . "_{$copy->id}";
    $copy->save();

    $copyUFJoin = CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin',
      ['uf_group_id' => $id],
      ['uf_group_id' => $copy->id],
      NULL,
      'entity_table'
    );

    $copyUFField = CRM_Core_DAO::copyGeneric('CRM_Core_BAO_UFField',
      ['uf_group_id' => $id],
      ['uf_group_id' => $copy->id]
    );

    $maxWeight = CRM_Utils_Weight::getMax('CRM_Core_DAO_UFJoin', NULL, 'weight');

    //update the weight
    $query = "
UPDATE civicrm_uf_join
SET    weight = %1
WHERE  uf_group_id = %2
AND    ( entity_id IS NULL OR entity_id <= 0 )
";
    $p = [
      1 => [$maxWeight + 1, 'Integer'],
      2 => [$copy->id, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($query, $p);
    if ($copy->is_reserved) {
      $query = "UPDATE civicrm_uf_group SET is_reserved = 0 WHERE id = %1";
      $params = [1 => [$copy->id, 'Integer']];
      CRM_Core_DAO::executeQuery($query, $params);
    }
    CRM_Utils_Hook::copy('UFGroup', $copy);

    return $copy;
  }

  /**
   * Process that send notification e-mails
   *
   * @param int $contactID
   *   Contact id.
   * @param array $values
   *   Associative array of name/value pair.
   */
  public static function commonSendMail($contactID, &$values) {
    if (!$contactID || !$values) {
      return;

    }
    $template = CRM_Core_Smarty::singleton();

    $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $contactID,
      'display_name'
    );

    self::profileDisplay($values['id'], $values['values'], $template);
    $emailList = explode(',', $values['email']);

    $contactLink = CRM_Utils_System::url('civicrm/contact/view',
      "reset=1&cid=$contactID",
      TRUE, NULL, FALSE, FALSE, TRUE
    );

    //get the default domain email address.
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
      $fixUrl = CRM_Utils_System::url('civicrm/admin/domain', 'action=update&reset=1');
      CRM_Core_Error::statusBounce(ts('The site administrator needs to enter a valid \'FROM Email Address\' in <a href="%1">Administer CiviCRM &raquo; Communications &raquo; FROM Email Addresses</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]));
    }

    foreach ($emailList as $emailTo) {
      // FIXME: take the below out of the foreach loop
      CRM_Core_BAO_MessageTemplate::sendTemplate(
        [
          'groupName' => 'msg_tpl_workflow_uf',
          'workflow' => 'uf_notify',
          'contactId' => $contactID,
          'tplParams' => [
            'displayName' => $displayName,
            'currentDate' => date('r'),
            'contactLink' => $contactLink,
          ],
          'from' => "$domainEmailName <$domainEmailAddress>",
          'toEmail' => $emailTo,
        ]
      );
    }
  }

  /**
   * Given a contact id and a group id, returns the field values from the db
   * for this group and notify email only if group's notify field is
   * set and field values are not empty
   *
   * @param int $gid
   *   Group id.
   * @param int $cid
   *   Contact id.
   * @param array $params
   * @param bool $skipCheck
   *
   * @return array
   */
  public static function checkFieldsEmptyValues($gid, $cid, $params, $skipCheck = FALSE) {
    if ($gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid) || $skipCheck) {
        $values = [];
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW);
        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params, TRUE);

        $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'notify');

        if (!empty($values) &&
          !empty($email)
        ) {
          $val = [
            'id' => $gid,
            'values' => $values,
            'email' => $email,
          ];
          return $val;
        }
      }
    }
    return NULL;
  }

  /**
   * Assign uf fields to template.
   *
   * @param int $gid
   *   Group id.
   * @param array $values
   * @param CRM_Core_Smarty $template
   */
  public static function profileDisplay($gid, $values, $template) {
    $groupTitle = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'title');
    $template->assign('grouptitle', $groupTitle);
    if (count($values)) {
      $template->assign('values', $values);
    }
  }

  /**
   * Format fields for dupe Contact Matching.
   *
   * @param array $params
   *
   * @param int $contactId
   *
   * @return array
   *   associated formatted array
   */
  public static function formatFields($params, $contactId = NULL) {
    if ($contactId) {
      // get the primary location type id and email
      [$name, $primaryEmail, $primaryLocationType] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);
    }
    else {
      $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
      $primaryLocationType = $defaultLocationType->id;
    }

    $data = [];
    $locationType = [];
    $count = 1;
    $primaryLocation = 0;
    foreach ($params as $key => $value) {
      [$fieldName, $locTypeId, $phoneTypeId] = explode('-', $key);

      if ($locTypeId == 'Primary') {
        $locTypeId = $primaryLocationType;
      }

      if (is_numeric($locTypeId)) {
        if (!in_array($locTypeId, $locationType)) {
          $locationType[$count] = $locTypeId;
          $count++;
        }
        $loc = CRM_Utils_Array::key($locTypeId, $locationType);

        $data['location'][$loc]['location_type_id'] = $locTypeId;

        // if we are getting in a new primary email, dont overwrite the new one
        if ($locTypeId == $primaryLocationType) {
          if (!empty($params['email-' . $primaryLocationType])) {
            $data['location'][$loc]['email'][$loc]['email'] = $fields['email-' . $primaryLocationType];
          }
          elseif (isset($primaryEmail)) {
            $data['location'][$loc]['email'][$loc]['email'] = $primaryEmail;
          }
          $primaryLocation++;
        }

        if ($loc == 1) {
          $data['location'][$loc]['is_primary'] = 1;
        }
        if ($fieldName == 'phone') {
          if ($phoneTypeId) {
            $data['location'][$loc]['phone'][$loc]['phone_type_id'] = $phoneTypeId;
          }
          else {
            $data['location'][$loc]['phone'][$loc]['phone_type_id'] = '';
          }
          $data['location'][$loc]['phone'][$loc]['phone'] = $value;
        }
        elseif ($fieldName == 'email') {
          $data['location'][$loc]['email'][$loc]['email'] = $value;
        }
        elseif ($fieldName == 'im') {
          $data['location'][$loc]['im'][$loc]['name'] = $value;
        }
        else {
          if ($fieldName === 'state_province') {
            $data['location'][$loc]['address']['state_province_id'] = $value;
          }
          elseif ($fieldName === 'country') {
            $data['location'][$loc]['address']['country_id'] = $value;
          }
          else {
            $data['location'][$loc]['address'][$fieldName] = $value;
          }
        }
      }
      else {
        // TODO: prefix, suffix and gender translation may no longer be necessary - check inputs
        if ($key === 'individual_suffix') {
          $data['suffix_id'] = $value;
        }
        elseif ($key === 'individual_prefix') {
          $data['prefix_id'] = $value;
        }
        elseif ($key === 'gender') {
          $data['gender_id'] = $value;
        }
        elseif (substr($key, 0, 6) === 'custom') {
          if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
            //fix checkbox
            if ($customFields[$customFieldID]['html_type'] == 'CheckBox') {
              $value = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($value));
            }
            // fix the date field
            if ($customFields[$customFieldID]['data_type'] == 'Date') {
              $date = CRM_Utils_Date::format($value);
              if (!$date) {
                $date = '';
              }
              $value = $date;
            }

            $data['custom'][$customFieldID] = [
              'id' => $id,
              'value' => $value,
              'extends' => $customFields[$customFieldID]['extends'],
              'type' => $customFields[$customFieldID]['data_type'],
              'custom_field_id' => $customFieldID,
            ];
          }
        }
        elseif ($key == 'edit') {
          continue;
        }
        else {
          $data[$key] = $value;
        }
      }
    }

    if (!$primaryLocation) {
      $loc++;
      $data['location'][$loc]['email'][$loc]['email'] = $primaryEmail;
    }

    return $data;
  }

  /**
   * Calculate the profile type 'group_type' as per profile fields.
   *
   * @param int $gId
   *   Profile id.
   * @param bool $includeTypeValues
   * @param int $ignoreFieldId
   *   Ignore particular profile field.
   *
   * @return array
   *   list of calculated group type
   */
  public static function calculateGroupType($gId, $includeTypeValues = FALSE, $ignoreFieldId = NULL) {
    //get the profile fields.
    $ufFields = self::getFields($gId, FALSE, NULL, NULL, NULL, TRUE, NULL, TRUE);
    return self::_calculateGroupType($ufFields, $includeTypeValues, $ignoreFieldId);
  }

  /**
   * Calculate the profile type 'group_type' as per profile fields.
   *
   * @param $ufFields
   * @param bool $includeTypeValues
   * @param int $ignoreFieldId
   *   Ignore perticular profile field.
   *
   * @return array
   *   list of calculated group type
   */
  public static function _calculateGroupType($ufFields, $includeTypeValues = FALSE, $ignoreFieldId = NULL) {
    $groupType = $groupTypeValues = $customFieldIds = [];
    if (!empty($ufFields)) {
      foreach ($ufFields as $fieldName => $fieldValue) {
        //ignore field from group type when provided.
        //in case of update profile field.
        if ($ignoreFieldId && ($ignoreFieldId == $fieldValue['field_id'])) {
          continue;
        }
        if (!in_array($fieldValue['field_type'], $groupType)) {
          $groupType[$fieldValue['field_type']] = $fieldValue['field_type'];
        }

        if ($includeTypeValues && ($fldId = CRM_Core_BAO_CustomField::getKeyID($fieldName))) {
          $customFieldIds[$fldId] = $fldId;
        }
      }
    }

    if (!empty($customFieldIds)) {
      $query = 'SELECT DISTINCT(cg.id), cg.extends, cg.extends_entity_column_id, cg.extends_entity_column_value FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cf.custom_group_id = cg.id WHERE cg.extends_entity_column_value IS NOT NULL AND cf.id IN (' . implode(',', $customFieldIds) . ')';

      $customGroups = CRM_Core_DAO::executeQuery($query);
      while ($customGroups->fetch()) {
        if (!$customGroups->extends_entity_column_value) {
          continue;
        }

        $groupTypeName = "{$customGroups->extends}Type";
        if ($customGroups->extends == 'Participant' && $customGroups->extends_entity_column_id) {
          $groupTypeName = CRM_Core_PseudoConstant::getName('CRM_Core_DAO_CustomGroup', 'extends_entity_column_id', $customGroups->extends_entity_column_id);
        }

        foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $customGroups->extends_entity_column_value) as $val) {
          if ($val) {
            $groupTypeValues[$groupTypeName][$val] = $val;
          }
        }
      }

      if (!empty($groupTypeValues)) {
        $groupType = array_merge($groupType, $groupTypeValues);
      }
    }

    return $groupType;
  }

  /**
   * Update the profile type 'group_type' as per profile fields including group types and group subtype values.
   * Build and store string like: group_type1,group_type2[VALUE_SEPARATOR]group_type1Type:1:2:3,group_type2Type:1:2
   *
   * FIELDS                                                   GROUP_TYPE
   * BirthDate + Email                                        Individual,Contact
   * BirthDate + Subject                                      Individual,Activity
   * BirthDate + Subject + SurveyOnlyField                    Individual,Activity\0ActivityType:28
   * BirthDate + Subject + SurveyOnlyField + PhoneOnlyField   (Not allowed)
   * BirthDate + SurveyOnlyField                              Individual,Activity\0ActivityType:28
   * BirthDate + Subject + SurveyOrPhoneField                 Individual,Activity\0ActivityType:2:28
   * BirthDate + SurveyOrPhoneField                           Individual,Activity\0ActivityType:2:28
   * BirthDate + SurveyOrPhoneField + SurveyOnlyField         Individual,Activity\0ActivityType:2:28
   * BirthDate + StudentField + Subject + SurveyOnlyField     Individual,Activity,Student\0ActivityType:28
   *
   * @param int $gId
   * @param array $groupTypes
   *   With key having group type names.
   *
   * @return bool
   */
  public static function updateGroupTypes($gId, $groupTypes = []) {
    if (!is_array($groupTypes) || !$gId) {
      return FALSE;
    }

    // If empty group types set group_type as 'null'
    if (empty($groupTypes)) {
      return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $gId, 'group_type', 'null');
    }

    $componentGroupTypes = ['Contribution', 'Participant', 'Membership', 'Activity', 'Case'];
    $validGroupTypes = array_merge([
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ], $componentGroupTypes, CRM_Contact_BAO_ContactType::subTypes());

    $gTypes = $gTypeValues = [];

    $participantExtends = ['ParticipantRole', 'ParticipantEventName', 'ParticipantEventType'];
    // Get valid group type and group subtypes
    foreach ($groupTypes as $groupType => $value) {
      if (in_array($groupType, $validGroupTypes) && !in_array($groupType, $gTypes)) {
        $gTypes[] = $groupType;
      }

      $subTypesOf = NULL;

      if (in_array($groupType, $participantExtends)) {
        $subTypesOf = $groupType;
      }
      elseif (strpos($groupType, 'Type') > 0) {
        $subTypesOf = substr($groupType, 0, strpos($groupType, 'Type'));
      }
      else {
        continue;
      }

      if (!empty($value) &&
        (in_array($subTypesOf, $componentGroupTypes) ||
          in_array($subTypesOf, $participantExtends)
        )
      ) {
        $gTypeValues[$subTypesOf] = $groupType . ":" . implode(':', $value);
      }
    }

    if (empty($gTypes)) {
      return FALSE;
    }

    // Build String to store group types and group subtypes
    $groupTypeString = implode(',', $gTypes);
    if (!empty($gTypeValues)) {
      $groupTypeString .= CRM_Core_DAO::VALUE_SEPARATOR . implode(',', $gTypeValues);
    }

    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $gId, 'group_type', $groupTypeString);
  }

  /**
   * Create a "group_type" string.
   *
   * @param array $coreTypes
   *   E.g. array('Individual','Contact','Student').
   * @param array $subTypes
   *   E.g. array('ActivityType' => array(7, 11)).
   * @param string $delim
   *
   * @return string
   * @throws CRM_Core_Exception
   */
  public static function encodeGroupType($coreTypes, $subTypes, $delim = CRM_Core_DAO::VALUE_SEPARATOR) {
    $groupTypeExpr = '';
    if ($coreTypes) {
      $groupTypeExpr .= implode(',', $coreTypes);
    }
    if ($subTypes) {
      //CRM-15427 Allow Multiple subtype filtering
      //if (count($subTypes) > 1) {
      //throw new CRM_Core_Exception("Multiple subtype filtering is not currently supported by widget.");
      //}
      foreach ($subTypes as $subType => $subTypeIds) {
        $groupTypeExpr .= $delim . $subType . ':' . implode(':', $subTypeIds);
      }
    }
    return $groupTypeExpr;
  }

  /**
   * setDefault component specific profile fields.
   *
   * @param array $fields
   *   Profile fields.
   * @param int $componentId
   *   ComponetID.
   * @param string $component
   *   Component name.
   * @param array $defaults
   *   An array of default values.
   *
   * @param bool $isStandalone
   */
  public static function setComponentDefaults(&$fields, $componentId, $component, &$defaults, $isStandalone = FALSE) {
    if (!$componentId ||
      !in_array($component, ['Contribute', 'Membership', 'Event', 'Activity', 'Case'])
    ) {
      return;
    }

    $componentBAO = $componentSubType = NULL;
    switch ($component) {
      case 'Membership':
        $componentBAO = 'CRM_Member_BAO_Membership';
        $componentBAOName = 'Membership';
        $componentSubType = ['membership_type_id'];
        break;

      case 'Contribute':
        $componentBAO = 'CRM_Contribute_BAO_Contribution';
        $componentBAOName = 'Contribution';
        $componentSubType = ['financial_type_id'];
        break;

      case 'Event':
        $componentBAO = 'CRM_Event_BAO_Participant';
        $componentBAOName = 'Participant';
        $componentSubType = ['role_id', 'event_id', 'event_type_id'];
        break;

      case 'Activity':
        $componentBAO = 'CRM_Activity_BAO_Activity';
        $componentBAOName = 'Activity';
        $componentSubType = ['activity_type_id'];
        break;

      case 'Case':
        $componentBAO = 'CRM_Case_BAO_Case';
        $componentBAOName = 'Case';
        $componentSubType = ['case_type_id'];
        break;
    }

    $values = [];
    $params = ['id' => $componentId];

    //get the component values.
    CRM_Core_DAO::commonRetrieve($componentBAO, $params, $values);
    if ($componentBAOName == 'Participant') {
      $values += ['event_type_id' => CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $values['event_id'], 'event_type_id')];
    }

    $formattedGroupTree = [];

    foreach ($fields as $name => $field) {
      $fldName = $isStandalone ? $name : "field[$componentId][$name]";
      if (array_key_exists($name, $values)) {
        $defaults[$fldName] = $values[$name];
      }
      elseif ($name == 'participant_note') {
        $noteDetails = CRM_Core_BAO_Note::getNote($componentId, 'civicrm_participant');
        $defaults[$fldName] = array_pop($noteDetails);
      }
      elseif (in_array($name, [
        'financial_type',
        'payment_instrument',
        'participant_status',
        'participant_role',
      ])) {
        $defaults[$fldName] = $values["{$name}_id"];
      }
      elseif ($name == 'membership_type') {
        // since membership_type field is a hierselect -
        $defaults[$fldName][0]
          = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $values['membership_type_id'], 'member_of_contact_id', 'id');
        $defaults[$fldName][1] = $values['membership_type_id'];
      }
      elseif ($name == 'membership_status') {
        $defaults[$fldName] = $values['status_id'];
      }
      elseif ($name == 'case_status') {
        $defaults[$fldName] = $values['case_status_id'];
      }
      elseif (CRM_Core_BAO_CustomField::getKeyID($name, TRUE) !== [NULL, NULL]) {
        if (empty($formattedGroupTree)) {
          //get the groupTree as per subTypes.
          $groupTree = [];
          foreach ($componentSubType as $subType) {
            $subTree = CRM_Core_BAO_CustomGroup::getTree($componentBAOName, NULL,
              $componentId, 0, $values[$subType]
            );
            $groupTree = CRM_Utils_Array::crmArrayMerge($groupTree, $subTree);
          }
          $formattedGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1);
          CRM_Core_BAO_CustomGroup::setDefaults($formattedGroupTree, $defaults);
        }

        //FIX ME: We need to loop defaults, but once we move to custom_1_x convention this code can be simplified.
        foreach ($defaults as $customKey => $customValue) {
          if ($customFieldDetails = CRM_Core_BAO_CustomField::getKeyID($customKey, TRUE)) {
            if ($name == 'custom_' . $customFieldDetails[0]) {

              //hack to set default for checkbox
              //basically this is for weired field name like field[33][custom_19]
              //we are converting this field name to array structure and assign value.
              $skipValue = FALSE;

              foreach ($formattedGroupTree as $tree) {
                if (!empty($tree['fields'][$customFieldDetails[0]])) {
                  if ('CheckBox' == CRM_Utils_Array::value('html_type', $tree['fields'][$customFieldDetails[0]])) {
                    $skipValue = TRUE;
                    $defaults['field'][$componentId][$name] = $customValue;
                    break;
                  }
                  elseif (CRM_Utils_Array::value('data_type', $tree['fields'][$customFieldDetails[0]]) == 'Date') {
                    $skipValue = TRUE;

                    // CRM-6681, $default contains formatted date, time values.
                    $defaults[$fldName] = $customValue;
                    if (!empty($defaults[$customKey . '_time'])) {
                      $defaults['field'][$componentId][$name . '_time'] = $defaults[$customKey . '_time'];
                    }
                  }
                }
              }

              if (!$skipValue || $isStandalone) {
                $defaults[$fldName] = $customValue;
              }
              unset($defaults[$customKey]);
              break;
            }
          }
        }
      }
      elseif (isset($values[$fldName])) {
        $defaults[$fldName] = $values[$fldName];
      }
    }
  }

  /**
   * Retrieve groups of  profiles.
   *
   * @param int $profileID
   *   Id of the profile.
   *
   * @return array
   *   returns array
   */
  public static function profileGroups($profileID) {
    $groupTypes = [];
    $profileTypes = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'group_type');
    if ($profileTypes) {
      $groupTypeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $profileTypes);
      $groupTypes = explode(',', $groupTypeParts[0]);
    }
    return $groupTypes;
  }

  /**
   * Alter contact params by filtering existing subscribed groups and returns
   * unsubscribed groups array for subscription.
   *
   * @param array $params
   *   Contact params.
   * @param int $contactId
   *   User contact id.
   *
   * @return array
   *   This contains array of groups for subscription
   */
  public static function getDoubleOptInGroupIds(&$params, $contactId = NULL) {
    $config = CRM_Core_Config::singleton();
    $subscribeGroupIds = [];

    // process further only if profileDoubleOptIn enabled and if groups exist
    if (!array_key_exists('group', $params) ||
      !self::isProfileDoubleOptin() ||
      CRM_Utils_System::isNull($params['group'])
    ) {
      return $subscribeGroupIds;
    }

    //check if contact email exist.
    $hasEmails = FALSE;
    foreach ($params as $name => $value) {
      if (strpos($name, 'email-') !== FALSE) {
        $hasEmails = TRUE;
        break;
      }
    }

    //Proceed furthur only if email present
    if (!$hasEmails) {
      return $subscribeGroupIds;
    }

    //do check for already subscriptions.
    $contactGroups = [];
    if ($contactId) {
      $query = "
SELECT  group_id
  FROM  civicrm_group_contact
  WHERE status = 'Added'
    AND contact_id = %1";

      $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contactId, 'Integer']]);
      while ($dao->fetch()) {
        $contactGroups[$dao->group_id] = $dao->group_id;
      }
    }

    //since we don't have names, compare w/ label.
    $mailingListGroupType = array_search('Mailing List', CRM_Core_OptionGroup::values('group_type'));

    //actual processing start.
    foreach ($params['group'] as $groupId => $isSelected) {
      //unset group those are not selected.
      if (!$isSelected) {
        unset($params['group'][$groupId]);
        continue;
      }

      $groupTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $groupId, 'group_type', 'id')
      );
      //get only mailing type group and unset it from params
      if (in_array($mailingListGroupType, $groupTypes) && !in_array($groupId, $contactGroups)) {
        $subscribeGroupIds[$groupId] = $groupId;
        unset($params['group'][$groupId]);
      }
    }

    return $subscribeGroupIds;
  }

  /**
   * Check if we are rendering mixed profiles.
   *
   * @param array $profileIds
   *   Associated array of profile ids.
   *
   * @return bool
   *   true if profile is mixed
   */
  public static function checkForMixProfiles($profileIds) {
    $mixProfile = FALSE;

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes(TRUE);

    $components = ['Contribution', 'Participant', 'Membership', 'Activity'];

    $typeCount = ['ctype' => [], 'subtype' => []];
    foreach ($profileIds as $gid) {
      $profileType = CRM_Core_BAO_UFField::getProfileType($gid);
      // ignore profile of type Contact
      if ($profileType == 'Contact') {
        continue;
      }
      if (in_array($profileType, $contactTypes, TRUE)) {
        if (!isset($typeCount['ctype'][$profileType])) {
          $typeCount['ctype'][$profileType] = 1;
        }

        // check if we are rendering profile of different contact types
        if (count($typeCount['ctype']) == 2) {
          $mixProfile = TRUE;
          break;
        }
      }
      elseif (in_array($profileType, $components, TRUE)) {
        $mixProfile = TRUE;
        break;
      }
      else {
        if (!isset($typeCount['subtype'][$profileType])) {
          $typeCount['subtype'][$profileType] = 1;
        }
        // check if we are rendering profile of different contact sub types
        if (count($typeCount['subtype']) == 2) {
          $mixProfile = TRUE;
          break;
        }
      }
    }
    return $mixProfile;
  }

  /**
   * Determine of we show overlay profile or not.
   *
   * @return bool
   *   true if profile should be shown else false
   */
  public static function showOverlayProfile() {
    $showOverlay = TRUE;

    // get the id of overlay profile
    $overlayProfileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', 'summary_overlay', 'id', 'name');
    $query = "SELECT count(id) FROM civicrm_uf_field WHERE uf_group_id = {$overlayProfileId} AND visibility IN ('Public Pages', 'Public Pages and Listings') ";

    $count = CRM_Core_DAO::singleValueQuery($query);

    //check if there are no public fields and use is anonymous
    $session = CRM_Core_Session::singleton();
    if (!$count && !$session->get('userID')) {
      $showOverlay = FALSE;
    }

    return $showOverlay;
  }

  /**
   * Get group type values of the profile.
   *
   * @param int $profileId
   * @param string $groupType
   *
   * @return array
   *   group type values
   */
  public static function groupTypeValues($profileId, $groupType = NULL) {
    $groupTypeValue = [];
    $groupTypes = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileId, 'group_type');

    $groupTypeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $groupTypes);
    if (empty($groupTypeParts[1])) {
      return $groupTypeValue;
    }
    $participantExtends = ['ParticipantRole', 'ParticipantEventName', 'ParticipantEventType'];

    foreach (explode(',', $groupTypeParts[1]) as $groupTypeValues) {
      $values = [];
      $valueParts = explode(':', $groupTypeValues);
      if ($groupType &&
        ($valueParts[0] != "{$groupType}Type" ||
          ($groupType == 'Participant' &&
            !in_array($valueParts[0], $participantExtends)
          )
        )
      ) {
        continue;
      }
      foreach ($valueParts as $val) {
        if (CRM_Utils_Rule::integer($val)) {
          $values[$val] = $val;
        }
      }
      if (!empty($values)) {
        $typeName = substr($valueParts[0], 0, -4);
        if (in_array($valueParts[0], $participantExtends)) {
          $typeName = $valueParts[0];
        }
        $groupTypeValue[$typeName] = $values;
      }
    }

    return $groupTypeValue;
  }

  /**
   * @return bool|object
   */
  public static function isProfileDoubleOptin() {
    // check for double optin
    if (CRM_Core_Component::isEnabled('CiviMail')) {
      return Civi::settings()->get('profile_double_optin');
    }
    return FALSE;
  }

  /**
   * @return bool|object
   */
  public static function isProfileAddToGroupDoubleOptin() {
    // check for add to group double optin
    if (CRM_Core_Component::isEnabled('CiviMail')) {
      return Civi::settings()->get('profile_add_to_group_double_optin');
    }
    return FALSE;
  }

  /**
   * Get profiles used for batch entry.
   *
   * @return array
   *   profileIds profile ids
   */
  public static function getBatchProfiles() {
    $query = "SELECT id
      FROM civicrm_uf_group
      WHERE name IN ('contribution_batch_entry', 'membership_batch_entry')";
    $dao = CRM_Core_DAO::executeQuery($query);
    $profileIds = [];
    while ($dao->fetch()) {
      $profileIds[$dao->id] = $dao->id;
    }
    return $profileIds;
  }

  /**
   * @param $source
   * @param $destination
   * @param bool $returnMultiSummaryFields
   *
   * @return array|null
   * @todo what do I do?
   */
  public static function shiftMultiRecordFields(&$source, &$destination, $returnMultiSummaryFields = FALSE) {
    $multiSummaryFields = $returnMultiSummaryFields ? [] : NULL;
    foreach ($source as $field => $properties) {
      if (!CRM_Core_BAO_CustomField::getKeyID($field)) {
        continue;
      }
      if (CRM_Core_BAO_CustomField::isMultiRecordField($field)) {
        $destination[$field] = $properties;
        if ($returnMultiSummaryFields) {
          if ($properties['is_multi_summary']) {
            $multiSummaryFields[$field] = $properties;
          }
        }
        unset($source[$field]);
      }
    }
    return $multiSummaryFields;
  }

  /**
   * This is function is used to format pseudo fields.
   *
   * @param array $fields
   *   Associated array of profile fields.
   *
   */
  public static function reformatProfileFields(&$fields) {
    //reformat fields array
    foreach ($fields as $name => $field) {
      //reformat phone and extension field
      if (substr($field['name'], 0, 13) == 'phone_and_ext') {
        $fieldSuffix = str_replace('phone_and_ext-', '', $field['name']);

        // retain existing element properties and just update and replace key
        CRM_Utils_Array::crmReplaceKey($fields, $name, "phone-{$fieldSuffix}");
        $fields["phone-{$fieldSuffix}"]['name'] = "phone-{$fieldSuffix}";
        $fields["phone-{$fieldSuffix}"]['where'] = 'civicrm_phone.phone';

        // add additional phone extension field
        $fields["phone_ext-{$fieldSuffix}"] = $field;
        $fields["phone_ext-{$fieldSuffix}"]['title'] = $field['title'] . ' - ' . ts('Ext.');
        $fields["phone_ext-{$fieldSuffix}"]['name'] = "phone_ext-{$fieldSuffix}";
        $fields["phone_ext-{$fieldSuffix}"]['where'] = 'civicrm_phone.phone_ext';
        $fields["phone_ext-{$fieldSuffix}"]['skipDisplay'] = 1;
        //ignore required for extension field
        $fields["phone_ext-{$fieldSuffix}"]['is_required'] = 0;
      }
    }
  }

  /**
   * Get the frontend_title for the profile, falling back on 'title' if none.
   *
   * @param int $profileID
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  public static function getFrontEndTitle(int $profileID) {
    $profile = civicrm_api3('UFGroup', 'getsingle', ['id' => $profileID, 'return' => ['title', 'frontend_title']]);
    return $profile['frontend_title'] ?? $profile['title'];
  }

  /**
   * Format custom field value for use in prepopulating a quickform profile field.
   *
   * @param array $field
   *   Field metadata.
   * @param string $value
   *   Raw value
   *
   * @return mixed
   *   String or array, depending on the html type
   */
  private static function formatCustomValue($field, $value) {
    if (CRM_Core_BAO_CustomField::isSerialized($field)) {
      $value = CRM_Utils_Array::explodePadded($value);

      // This may not be required now.
      if ($field['html_type'] === 'CheckBox') {
        $checkboxes = [];
        foreach (array_filter($value) as $item) {
          $checkboxes[$item] = 1;
          // CRM-2969 seems like we need this for QF style checkboxes in profile where its multiindexed
          $checkboxes["[{$item}]"] = 1;
        }
        return $checkboxes;
      }
    }
    return $value;
  }

}
