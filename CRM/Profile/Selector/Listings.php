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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Profile_Selector_Listings extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * array of supported links, currenly view and edit
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   * @static
   */
  static $_columnHeaders;

  /**
   * The sql params we use to get the list of contacts
   *
   * @var string
   * @access protected
   */
  protected $_params;

  /**
   * the public visible fields to be shown to the user
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * the custom fields for this domain
   *
   * @var array
   * @access protected
   */
  protected $_customFields;

  /**
   * cache the query object
   *
   * @var object
   * @access protected
   */
  protected $_query;

  /**
   * cache the expanded options list if any
   *
   * @var object
   * @access protected
   */
  protected $_options;

  /**
   * The group id that we are editing
   *
   * @var int
   */
  protected $_gid;

  /**
   * Do we enable mapping of users
   *
   * @var boolean
   */
  protected $_map;

  /**
   * Do we enable edit link
   *
   * @var boolean
   */
  protected $_editLink;

  /**
   * Should we link to the UF Profile
   *
   * @var boolean
   */
  protected $_linkToUF;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
   */
  protected $_profileIds = array();

  protected $_multiRecordTableName = NULL;
  /**
   * Class constructor
   *
   * @param string params the params for the where clause
   *
   * @return CRM_Contact_Selector_Profile
   * @access public
   */
  function __construct(
    &$params,
    &$customFields,
    $ufGroupIds = NULL,
    $map = FALSE,
    $editLink = FALSE,
    $linkToUF = FALSE
  ) {
    $this->_params = $params;

    if (is_array($ufGroupIds)) {
      $this->_profileIds = $ufGroupIds;
      $this->_gid = $ufGroupIds[0];
    }
    else {
      $this->_profileIds = array($ufGroupIds);
      $this->_gid = $ufGroupIds;
    }

    $this->_map      = $map;
    $this->_editLink = $editLink;
    $this->_linkToUF = $linkToUF;

    //get the details of the uf group
    if ($this->_gid) {
      $groupId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_UFGroup',
        $this->_gid, 'limit_listings_group_id'
      );
    }

    // add group id to params if a uf group belong to a any group
    if ($groupId) {
      if (CRM_Utils_Array::value('group', $this->_params)) {
        $this->_params['group'][$groupId] = 1;
      }
      else {
        $this->_params['group'] = array($groupId => 1);
      }
    }

    $this->_fields = CRM_Core_BAO_UFGroup::getListingFields(CRM_Core_Action::VIEW,
      CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY |
      CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
      FALSE, $this->_profileIds
    );

    $this->_customFields = &$customFields;

    $returnProperties = CRM_Contact_BAO_Contact::makeHierReturnProperties($this->_fields);
    $returnProperties['contact_type'] = 1;
    $returnProperties['contact_sub_type'] = 1;
    $returnProperties['sort_name'] = 1;

    $queryParams    = CRM_Contact_BAO_Query::convertFormValues($this->_params, 1);
    $this->_query   = new CRM_Contact_BAO_Query($queryParams, $returnProperties, $this->_fields);

    //the below is done for query building for multirecord custom field listing
    //to show all the custom field multi valued records of a particular contact
    $this->setMultiRecordTableName($this->_fields);

    $this->_options = &$this->_query->_options;
  }
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   *
   * @return array
   * @access public
   *
   */
  static function &links($map = FALSE, $editLink = FALSE, $ufLink = FALSE, $gids = NULL) {
    if (!self::$_links) {
      self::$_links = array();

      $viewPermission = TRUE;
      if ($gids) {
        // check view permission for each profile id, in case multiple profile ids are rendered
        // then view action is disabled if any profile returns false
        foreach ($gids as $profileId) {
          $viewPermission = CRM_Core_Permission::ufGroupValid($profileId, CRM_Core_Permission::VIEW);
          if (!$viewPermission) {
            break;
          }
        }
      }

      if ($viewPermission) {
        self::$_links[CRM_Core_Action::VIEW] = array(
          'name' => ts('View'),
          'url' => 'civicrm/profile/view',
          'qs' => 'reset=1&id=%%id%%&gid=%%gid%%',
          'title' => ts('View Profile Details'),
        );
      }

      if ($editLink) {
        self::$_links[CRM_Core_Action::UPDATE] = array(
          'name' => ts('Edit'),
          'url' => 'civicrm/profile/edit',
          'qs' => 'reset=1&id=%%id%%&gid=%%gid%%',
          'title' => ts('Edit'),
        );
      }

      if ($ufLink) {
        self::$_links[CRM_Core_Action::PROFILE] = array(
          'name' => ts('Website Profile'),
          'url' => 'user/%%ufID%%',
          'qs' => ' ',
          'title' => ts('View Website Profile'),
        );
      }

      if ($map) {
        self::$_links[CRM_Core_Action::MAP] = array(
          'name' => ts('Map'),
          'url' => 'civicrm/profile/map',
          'qs' => 'reset=1&cid=%%id%%&gid=%%gid%%',
          'title' => ts('Map'),
        );
      }
    }
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $status =
      CRM_Utils_System::isNull($this->_multiRecordTableName) ? ts('Contact %%StatusMessage%%') : ts('Contact Multi Records %%StatusMessage%%');
    $params['status']    = $status;
    $params['csvString'] = NULL;
    $params['rowCount']  = CRM_Utils_Pager::ROWCOUNT;

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  function &getColumnHeaders($action = NULL, $output = NULL) {
    static $skipFields = array('group', 'tag');
    $multipleFields = array('url');
    $direction      = CRM_Utils_Sort::ASCENDING;
    $empty          = TRUE;
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = array(array('name' => ''),
        array(
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
      );

      $locationTypes = CRM_Core_PseudoConstant::locationType();

      foreach ($this->_fields as $name => $field) {
        // skip pseudo fields
        if (substr($name, 0, 9) == 'phone_ext') {
          continue;
        }

        if (CRM_Utils_Array::value('in_selector', $field) &&
          !in_array($name, $skipFields)
        ) {

          if (strpos($name, '-') !== FALSE) {
            $value     = explode('-', $name);
            $fieldName = CRM_Utils_Array::value(0, $value);
            $lType     = CRM_Utils_Array::value(1, $value);
            $type      = CRM_Utils_Array::value(2, $value);

            if (!in_array($fieldName, $multipleFields)) {
              if ($lType == 'Primary') {
                $locationTypeName = 1;
              }
              else {
                $locationTypeName = $locationTypes[$lType];
              }

              if (in_array($fieldName, array(
                'phone', 'im', 'email'))) {
                if ($type) {
                  $name = "`$locationTypeName-$fieldName-$type`";
                }
                else {
                  $name = "`$locationTypeName-$fieldName`";
                }
              }
              else {
                $name = "`$locationTypeName-$fieldName`";
              }
            }
            else {
              $name = "website-{$lType}-{$fieldName}";
            }
          }

          self::$_columnHeaders[] = array(
            'name' => $field['title'],
            'sort' => $name,
            'direction' => $direction,
          );

          $direction = CRM_Utils_Sort::DONTCARE;
          $empty = FALSE;
        }
      }

      // if we dont have any valid columns, dont add the implicit ones
      // this allows the template to check on emptiness of column headers
      if ($empty) {
        self::$_columnHeaders = array();
      }
      else {
        self::$_columnHeaders[] = array('desc' => ts('Actions'));
      }
    }
    return self::$_columnHeaders;
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    $additionalWhereClause = 'contact_a.is_deleted = 0';
    $additionalFromClause = NULL;
    $returnQuery = NULL;

    if ($this->_multiRecordTableName &&
        !array_key_exists($this->_multiRecordTableName, $this->_query->_whereTables)) {
      $additionalFromClause = CRM_Utils_Array::value($this->_multiRecordTableName, $this->_query->_tables);
      $returnQuery = TRUE;
    }

    $countVal = $this->_query->searchQuery(0, 0, NULL, TRUE, NULL, NULL, NULL,
      $returnQuery, $additionalWhereClause, NULL, $additionalFromClause
    );

    if (!$returnQuery) {
      return $countVal;
    }

    if ($returnQuery) {
      $sql = preg_replace('/DISTINCT/', '', $countVal);
      return CRM_Core_DAO::singleValueQuery($sql);
    }
  }

  /**
   * Return the qill for this selector
   *
   * @return string
   * @access public
   */
  function getQill() {
    return $this->_query->qill();
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {

    $multipleFields = array('url');
    //$sort object processing for location fields
    if ($sort) {
      $vars = $sort->_vars;
      $varArray = array();
      foreach ($vars as $key => $field) {
        $field      = $vars[$key];
        $fieldArray = explode('-', $field['name']);
        $fieldType  = CRM_Utils_Array::value('2', $fieldArray);
        if (is_numeric(CRM_Utils_Array::value('1', $fieldArray))) {
          if (!in_array($fieldType, $multipleFields)) {
            $locationType = new CRM_Core_DAO_LocationType();
            $locationType->id = $fieldArray[1];
            $locationType->find(TRUE);
            if ($fieldArray[0] == 'email' || $fieldArray[0] == 'im' || $fieldArray[0] == 'phone') {
              $field['name'] = "`" . $locationType->name . "-" . $fieldArray[0] . "-1`";
            }
            else {
              $field['name'] = "`" . $locationType->name . "-" . $fieldArray[0] . "`";
            }
          }
          else {
            $field['name'] = "`website-" . $fieldArray[1] . "-{$fieldType}`";
          }
        }
        $varArray[$key] = $field;
      }
    }

    $sort->_vars = $varArray;

    $additionalWhereClause = 'contact_a.is_deleted = 0';
    $returnQuery = NULL;
    if ($this->_multiRecordTableName) {
      $returnQuery = TRUE;
    }

    $result = $this->_query->searchQuery($offset, $rowCount, $sort, NULL, NULL,
      NULL, NULL, $returnQuery, $additionalWhereClause
    );

    if ($returnQuery) {
      $resQuery = preg_replace('/GROUP BY contact_a.id[\s]+ORDER BY/', ' ORDER BY', $result);
      $result   = CRM_Core_DAO::executeQuery($resQuery);
    }

    // process the result of the query
    $rows = array();

    // check if edit is configured in profile settings
    if ($this->_gid) {
      $editLink = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'is_edit_link');
    }

    //FIXME : make sure to handle delete separately. CRM-4418
    $mask = CRM_Core_Action::mask(array(CRM_Core_Permission::getPermission()));
    if ($editLink && ($mask & CRM_Core_Permission::EDIT)) {
      // do not allow edit for anon users in joomla frontend, CRM-4668
      $config = CRM_Core_Config::singleton();
      if (!$config->userFrameworkFrontend) {
        $this->_editLink = TRUE;
      }
    }
    $links = self::links($this->_map, $this->_editLink, $this->_linkToUF, $this->_profileIds);

    $locationTypes = CRM_Core_PseudoConstant::locationType();

    $names = array();
    static $skipFields = array('group', 'tag');

    foreach ($this->_fields as $key => $field) {
      // skip pseudo fields
      if (substr($key, 0, 9) == 'phone_ext') {
        continue;
      }

      if (CRM_Utils_Array::value('in_selector', $field) &&
        !in_array($key, $skipFields)
      ) {
        if (strpos($key, '-') !== FALSE) {
          $value     = explode('-', $key);
          $fieldName = CRM_Utils_Array::value(0, $value);
          $id        = CRM_Utils_Array::value(1, $value);
          $type      = CRM_Utils_Array::value(2, $value);

          if (!in_array($fieldName, $multipleFields)) {
            $locationTypeName = NULL;
            if (is_numeric($id)) {
              $locationTypeName = CRM_Utils_Array::value($id, $locationTypes);
            }
            else {
              if ($id == 'Primary') {
                $locationTypeName = 1;
              }
            }

            if (!$locationTypeName) {
              continue;
            }
            $locationTypeName = str_replace(' ', '_', $locationTypeName);
            if (in_array($fieldName, array(
              'phone', 'im', 'email'))) {
              if ($type) {
                $names[] = "{$locationTypeName}-{$fieldName}-{$type}";
              }
              else {
                $names[] = "{$locationTypeName}-{$fieldName}";
              }
            }
            else {
              $names[] = "{$locationTypeName}-{$fieldName}";
            }
          }
          else {
            $names[] = "website-{$id}-{$fieldName}";
          }
        }
        elseif ($field['name'] == 'id') {
          $names[] = 'contact_id';
        }
        else {
          $names[] = $field['name'];
        }
      }
    }


    $multipleSelectFields = array('preferred_communication_method' => 1);
    $multiRecordTableId = NULL;
    if ($this->_multiRecordTableName) {
      $multiRecordTableId = "{$this->_multiRecordTableName}_id";
    }

    // we need to determine of overlay profile should be shown
    $showProfileOverlay = CRM_Core_BAO_UFGroup::showOverlayProfile();

    $imProviders  = CRM_Core_PseudoConstant::IMProvider();
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();
    $languages    = CRM_Core_PseudoConstant::languages();
    while ($result->fetch()) {
      if (isset($result->country)) {
        // the query returns the untranslated country name
        $i18n = CRM_Core_I18n::singleton();
        $result->country = $i18n->translate($result->country);
      }
      $row   = array();
      $empty = TRUE;
      $row[] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
        $result->contact_sub_type : $result->contact_type,
        FALSE,
        $result->contact_id,
        $showProfileOverlay
      );
      if ($result->sort_name) {
        $row['sort_name'] = $result->sort_name;
        $empty = FALSE;
      }
      else {
        continue;
      }

      foreach ($names as $name) {
        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $row[] = CRM_Core_BAO_CustomField::getDisplayValue($result->$name,
            $cfID,
            $this->_options,
            $result->contact_id
          );
        }
        elseif (substr($name, -4) == '-url' &&
          !empty($result->$name)
        ) {
          $url      = CRM_Utils_System::fixURL($result->$name);
          $typeId   = substr($name, 0, -4) . "-website_type_id";
          $typeName = $websiteTypes[$result->$typeId];
          if ($typeName) {
            $row[] = "<a href=\"$url\">{$result->$name} (${typeName})</a>";
          }
          else {
            $row[] = "<a href=\"$url\">{$result->$name}</a>";
          }
        }
        elseif ($name == 'preferred_language') {
          $row[] = $languages[$result->$name];
        }
        elseif ($multipleSelectFields &&
          array_key_exists($name, $multipleSelectFields)
        ) {
          // FIXME: Code related to the old CRM_Quest - should be removed
          $key = $name;
          $paramsNew = array($key => $result->$name);
          if ($key == 'test_tutoring') {
            $name = array($key => array('newName' => $key, 'groupName' => 'test'));
            // for  readers group
          }
          elseif (substr($key, 0, 4) == 'cmr_') {
            $name = array(
              $key => array('newName' => $key,
                'groupName' => substr($key, 0, -3),
              ));
          }
          else {
            $name = array($key => array('newName' => $key, 'groupName' => $key));
          }
          CRM_Core_OptionGroup::lookupValues($paramsNew, $name, FALSE);
          $row[] = $paramsNew[$key];
        }
        elseif (strpos($name, '-im')) {
          if (!empty($result->$name)) {
            $providerId   = $name . "-provider_id";
            $providerName = $imProviders[$result->$providerId];
            $row[]        = $result->$name . " ({$providerName})";
          }
          else {
            $row[] = '';
          }
        }
        elseif (strpos($name, '-phone-')) {
          $phoneExtField = str_replace('phone', 'phone_ext', $name);
          if (isset($result->$phoneExtField)) {
            $row[] = $result->$name . " (" . $result->$phoneExtField . ")";
          }
          else {
            $row[] = $result->$name;
          }
        }
        elseif (in_array($name, array(
          'addressee', 'email_greeting', 'postal_greeting'))) {
          $dname = $name . '_display';
          $row[] = $result->$dname;
        }
        elseif (in_array($name, array(
          'birth_date', 'deceased_date'))) {
          $row[] = CRM_Utils_Date::customFormat($result->$name);
        }
        elseif (isset($result->$name)) {
          $row[] = $result->$name;
        }
        else {
          $row[] = '';
        }

        if (!empty($result->$name)) {
          $empty = FALSE;
        }
      }

      $newLinks = $links;
      $params = array(
        'id' => $result->contact_id,
        'gid' => implode(',', $this->_profileIds),
      );

      // pass record id param to view url for multi record view
      if ($multiRecordTableId && $newLinks) {
        if ($result->$multiRecordTableId){
          if ($newLinks[CRM_Core_Action::VIEW]['url'] == 'civicrm/profile/view') {
            $newLinks[CRM_Core_Action::VIEW]['qs'] .= "&multiRecord=view&recordId=%%recordId%%&allFields=1";
            $params['recordId'] = $result->$multiRecordTableId;
          }
        }
      }

      if ($this->_linkToUF) {
        $ufID = CRM_Core_BAO_UFMatch::getUFId($result->contact_id);
        if (!$ufID) {
          unset($newLinks[CRM_Core_Action::PROFILE]);
        }
        else {
          $params['ufID'] = $ufID;
        }
      }

      $row[] = CRM_Core_Action::formLink($newLinks,
        $mask,
        $params
      );

      if (!$empty) {
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviCRM Profile Listings');
  }

  /**
   *  set the _multiRecordTableName to display the result set
   *  according to multi record custom field values
   */
  function setMultiRecordTableName($fields) {
   $customGroupId = $multiRecordTableName = NULL;
   $selectorSet = FALSE;

    foreach ($fields as $field => $properties) {
      if (!CRM_Core_BAO_CustomField::getKeyID($field)) {
        continue;
      }
      if ($cgId = CRM_Core_BAO_CustomField::isMultiRecordField($field)) {
        $customGroupId = CRM_Utils_System::isNull($customGroupId) ? $cgId : $customGroupId;

        //if the field is submitted set multiRecordTableName
        if ($customGroupId) {
          $isSubmitted = FALSE;
          foreach ($this->_query->_params as $key => $value) {
            //check the query params 'where' element
            if ($value[0] == $field) {
              $isSubmitted = TRUE;
              break;
            }
          }

          if ($isSubmitted) {
            $this->_multiRecordTableName = $multiRecordTableName =
              CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name');
            if ($multiRecordTableName) {
              return;
            }
          }

          if (CRM_Utils_Array::value('in_selector', $properties)) {
            $selectorSet = TRUE;
          }
        }
      }
    }

    if (!isset($customGroupId) || !$customGroupId) {
      return;
    }

    //if the field is in selector and not a searchable field
    //get the proper customvalue table name
    if ($selectorSet) {
      $this->_multiRecordTableName = $multiRecordTableName =
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name');
    }
  } //func close
}
//end of class

