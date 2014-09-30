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

/**
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Contact_Selector extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
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
   * Properties of contact we're interested in displaying
   * @var array
   * @static
   */
  static $_properties = array(
    'contact_id', 'contact_type', 'contact_sub_type',
    'sort_name', 'street_address',
    'city', 'state_province', 'postal_code', 'country',
    'geo_code_1', 'geo_code_2', 'is_deceased',
    'email', 'on_hold', 'phone', 'status',
    'do_not_email', 'do_not_phone', 'do_not_mail',
  );

  /**
   * formValues is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   * @access protected
   */
  public $_formValues;

  /**
   * The contextMenu
   *
   * @var array
   * @access protected
   */
  protected $_contextMenu;

  /**
   * params is the array in a value used by the search query creator
   *
   * @var array
   * @access protected
   */
  public $_params;

  /**
   * The return properties used for search
   *
   * @var array
   * @access protected
   */
  protected $_returnProperties;

  /**
   * represent the type of selector
   *
   * @var int
   * @access protected
   */
  protected $_action;

  protected $_searchContext;

  protected $_query;

  /**
   * group id
   *
   * @var int
   */
  protected $_ufGroupID;

  /**
   * the public visible fields to be shown to the user
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * Class constructor
   *
   * @param $customSearchClass
   * @param array $formValues array of form values imported
   * @param array $params array of parameters for query
   * @param null $returnProperties
   * @param \const|int $action - action of search basic or advanced.
   *
   * @param bool $includeContactIds
   * @param bool $searchDescendentGroups
   * @param string $searchContext
   * @param null $contextMenu
   *
   * @return CRM_Contact_Selector
   * @access public
   */
  function __construct(
    $customSearchClass,
    $formValues = NULL,
    $params = NULL,
    $returnProperties = NULL,
    $action = CRM_Core_Action::NONE,
    $includeContactIds = FALSE,
    $searchDescendentGroups = TRUE,
    $searchContext = 'search',
    $contextMenu = NULL
  ) {
    //don't build query constructor, if form is not submitted
    $force = CRM_Utils_Request::retrieve('force', 'Boolean', CRM_Core_DAO::$_nullObject);
    if (empty($formValues) && !$force) {
      return;
    }

    // submitted form values
    $this->_formValues = &$formValues;
    $this->_params = &$params;
    $this->_returnProperties = &$returnProperties;
    $this->_contextMenu = &$contextMenu;
    $this->_context = $searchContext;

    // type of selector
    $this->_action = $action;

    $this->_searchContext = $searchContext;

    $this->_ufGroupID = CRM_Utils_Array::value('uf_group_id', $this->_formValues);

    if ($this->_ufGroupID) {
      $this->_fields = CRM_Core_BAO_UFGroup::getListingFields(CRM_Core_Action::VIEW,
        CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY |
        CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
        FALSE, $this->_ufGroupID
      );
      self::$_columnHeaders = NULL;

      $this->_customFields = CRM_Core_BAO_CustomField::getFieldsForImport('Individual');

      $this->_returnProperties = CRM_Contact_BAO_Contact::makeHierReturnProperties($this->_fields);
      $this->_returnProperties['contact_type'] = 1;
      $this->_returnProperties['contact_sub_type'] = 1;
      $this->_returnProperties['sort_name'] = 1;
    }

    $displayRelationshipType = CRM_Utils_Array::value('display_relationship_type', $this->_formValues);
    $operator = CRM_Utils_Array::value('operator', $this->_formValues, 'AND');

    // rectify params to what proximity search expects if there is a value for prox_distance
    // CRM-7021
    if (!empty($this->_params)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($this->_params);
    }

    $this->_query = new CRM_Contact_BAO_Query(
      $this->_params,
      $this->_returnProperties,
      NULL,
      $includeContactIds,
      FALSE,
      CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      $searchDescendentGroups,
      FALSE,
      $displayRelationshipType,
      $operator
    );

    $this->_options = &$this->_query->_options;
  }
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   * @access public
   *
   */
  static function &links() {
    list($context, $contextMenu, $key) = func_get_args();
    $extraParams = ($key) ? "&key={$key}" : NULL;
    $searchContext = ($context) ? "&context=$context" : NULL;

    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view',
          'class' => 'no-popup',
          'qs' => "reset=1&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('View Contact Details'),
          'ref' => 'view-contact',
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/add',
          'class' => 'no-popup',
          'qs' => "reset=1&action=update&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('Edit Contact Details'),
          'ref' => 'edit-contact',
        ),
      );

      $config = CRM_Core_Config::singleton();
      if ($config->mapAPIKey && $config->mapProvider) {
        self::$_links[CRM_Core_Action::MAP] = array(
          'name' => ts('Map'),
          'url' => 'civicrm/contact/map',
          'qs' => "reset=1&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('Map Contact'),
        );
      }

      // Adding Context Menu Links in more action
      if ($contextMenu) {
        $counter = 7000;
        foreach ($contextMenu as $key => $value) {
          $contextVal = '&context=' . $value['key'];
          if ($value['key'] == 'delete') {
            $contextVal = $searchContext;
          }
          $url = "civicrm/contact/view/{$value['key']}";
          $qs = "reset=1&action=add&cid=%%id%%{$contextVal}{$extraParams}";
          if ($value['key'] == 'activity') {
            $qs = "action=browse&selectedChild=activity&reset=1&cid=%%id%%{$extraParams}";
          }
          elseif ($value['key'] == 'email') {
            $url = "civicrm/contact/view/activity";
            $qs = "atype=3&action=add&reset=1&cid=%%id%%{$extraParams}";
          }

          self::$_links[$counter++] = array(
            'name' => $value['title'],
            'url' => $url,
            'qs' => $qs,
            'title' => $value['title'],
            'ref' => $value['ref'],
            'class' => CRM_Utils_Array::value('class', $value),
          );
        }
      }
    }
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param $params
   *
   * @internal param $
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['status']    = ts('Contact %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount']  = CRM_Utils_Pager::ROWCOUNT;

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

  /**
   * @param null $action
   * @param null $output
   *
   * @return array
   */
  function &getColHeads($action = NULL, $output = NULL) {
    $colHeads = self::_getColumnHeaders();
    $colHeads[] = array('desc' => ts('Actions'), 'name' => ts('Action'));
    return $colHeads;
  }

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
    $headers = NULL;

    // unset return property elements that we don't care
    if (!empty($this->_returnProperties)) {
      $doNotCareElements = array(
        'contact_type',
        'contact_sub_type',
        'sort_name',
      );
      foreach ( $doNotCareElements as $value) {
        unset($this->_returnProperties[$value]);
      }
    }

    if ($output == CRM_Core_Selector_Controller::EXPORT) {
      $csvHeaders = array(ts('Contact ID'), ts('Contact Type'));
      foreach ($this->getColHeads($action, $output) as $column) {
        if (array_key_exists('name', $column)) {
          $csvHeaders[] = $column['name'];
        }
      }
      $headers = $csvHeaders;
    }
    elseif ($output == CRM_Core_Selector_Controller::SCREEN) {
      $csvHeaders = array(ts('Name'));
      foreach ($this->getColHeads($action, $output) as $key => $column) {
        if (array_key_exists('name', $column) &&
          $column['name'] &&
          $column['name'] != ts('Name')
        ) {
          $csvHeaders[$key] = $column['name'];
        }
      }
      $headers = $csvHeaders;
    }
    elseif ($this->_ufGroupID) {
      // we dont use the cached value of column headers
      // since it potentially changed because of the profile selected
      static $skipFields = array('group', 'tag');
      $direction = CRM_Utils_Sort::ASCENDING;
      $empty = TRUE;
      if (!self::$_columnHeaders) {
        self::$_columnHeaders = array(array('name' => ''),
          array(
            'name' => ts('Name'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::ASCENDING,
          ),
        );

        $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

        foreach ($this->_fields as $name => $field) {
          if (!empty($field['in_selector']) &&
            !in_array($name, $skipFields)
          ) {
            if (strpos($name, '-') !== FALSE) {
              list($fieldName, $lType, $type) = CRM_Utils_System::explode('-', $name, 3);

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
            //to handle sort key for Internal contactId.CRM-2289
            if ($name == 'id') {
              $name = 'contact_id';
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
          self::$_columnHeaders[] = array('desc' => ts('Actions'), 'name' => ts('Action'));
        }
      }
      $headers = self::$_columnHeaders;
    }
    elseif (!empty($this->_returnProperties)) {
      self::$_columnHeaders = array(array('name' => ''),
        array(
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
      );
      $properties = self::makeProperties($this->_returnProperties);

      foreach ($properties as $prop) {
        if (strpos($prop, '-')) {
          list($loc, $fld, $phoneType) = CRM_Utils_System::explode('-', $prop, 3);
          $title = $this->_query->_fields[$fld]['title'];
          if (trim($phoneType) && !is_numeric($phoneType) && strtolower($phoneType) != $fld) {
            $title .= "-{$phoneType}";
          }
          $title .= " ($loc)";
        }
        elseif (isset($this->_query->_fields[$prop]) && isset($this->_query->_fields[$prop]['title'])) {
          $title = $this->_query->_fields[$prop]['title'];
        }
        else {
          $title = '';
        }

        self::$_columnHeaders[] = array('name' => $title, 'sort' => $prop);
      }
      self::$_columnHeaders[] = array('name' => ts('Actions'));
      $headers = self::$_columnHeaders;
    }
    else {
      $headers = $this->getColHeads($action, $output);
    }

    return $headers;
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
    // Use count from cache during paging/sorting
    if (!empty($_GET['crmPID']) || !empty($_GET['crmSID'])) {
      $count = CRM_Core_BAO_Cache::getItem('Search Results Count', $this->_key);
    }
    if (empty($count)) {
      $count = $this->_query->searchQuery(0, 0, NULL, TRUE);
      CRM_Core_BAO_Cache::setItem($count, 'Search Results Count', $this->_key);
    }
    return $count;
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
    $config = CRM_Core_Config::singleton();

    if (($output == CRM_Core_Selector_Controller::EXPORT ||
        $output == CRM_Core_Selector_Controller::SCREEN
      ) &&
      $this->_formValues['radio_ts'] == 'ts_sel'
    ) {
      $includeContactIds = TRUE;
    }
    else {
      $includeContactIds = FALSE;
    }

    // note the formvalues were given by CRM_Contact_Form_Search to us
    // and contain the search criteria (parameters)
    // note that the default action is basic
    if ($rowCount) {
      $cacheKey = $this->buildPrevNextCache($sort);
      $result = $this->_query->getCachedContacts($cacheKey, $offset, $rowCount, $includeContactIds);
    }
    else {
      $result = $this->_query->searchQuery($offset, $rowCount, $sort, FALSE, $includeContactIds);
    }

    // process the result of the query
    $rows = array();
    $permissions = array(CRM_Core_Permission::getPermission());
    if (CRM_Core_Permission::check('delete contacts')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    if ($this->_searchContext == 'smog') {
      $gc = CRM_Core_SelectValues::groupContactStatus();
    }

    if ($this->_ufGroupID) {
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

      $names = array();
      static $skipFields = array('group', 'tag');
      foreach ($this->_fields as $key => $field) {
        if (!empty($field['in_selector']) &&
          !in_array($key, $skipFields)
        ) {
          if (strpos($key, '-') !== FALSE) {
            list($fieldName, $id, $type) = CRM_Utils_System::explode('-', $key, 3);

            if ($id == 'Primary') {
              $locationTypeName = 1;
            }
            else {
              $locationTypeName = CRM_Utils_Array::value($id, $locationTypes);
              if (!$locationTypeName) {
                continue;
              }
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
            $names[] = $field['name'];
          }
        }
      }

      $names[] = "status";
    }
    elseif (!empty($this->_returnProperties)) {
      $names = self::makeProperties($this->_returnProperties);
    }
    else {
      $names = self::$_properties;
    }

    $multipleSelectFields = array('preferred_communication_method' => 1);

    $links = self::links($this->_context, $this->_contextMenu, $this->_key);

    //check explicitly added contact to a Smart Group.
    $groupID = CRM_Utils_Array::key('1', $this->_formValues['group']);

    $pseudoconstants = array();
    // for CRM-3157 purposes
    if (in_array('world_region', $names)) {
      $pseudoconstants['world_region'] = array(
        'dbName' => 'world_region_id',
        'values' => CRM_Core_PseudoConstant::worldRegion()
      );
    }

    $seenIDs = array();
    while ($result->fetch()) {
      $row = array();
      $this->_query->convertToPseudoNames($result);

      // the columns we are interested in
      foreach ($names as $property) {
        if ($property == 'status') {
          continue;
        }
        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($property)) {
          $row[$property] = CRM_Core_BAO_CustomField::getDisplayValue(
            $result->$property,
            $cfID,
            $this->_options,
            $result->contact_id
          );
        }
        elseif (
          $multipleSelectFields &&
          array_key_exists($property, $multipleSelectFields)
        ) {
          $key = $property;
          $paramsNew = array($key => $result->$property);
          $name = array($key => array('newName' => $key, 'groupName' => $key));

          CRM_Core_OptionGroup::lookupValues($paramsNew, $name, FALSE);
          $row[$key] = $paramsNew[$key];
        }
        elseif (strpos($property, '-im')) {
          $row[$property] = $result->$property;
          if (!empty($result->$property)) {
            $imProviders    = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
            $providerId     = $property . "-provider_id";
            $providerName   = $imProviders[$result->$providerId];
            $row[$property] = $result->$property . " ({$providerName})";
          }
        }
        elseif (in_array($property, array(
          'addressee', 'email_greeting', 'postal_greeting'))) {
          $greeting = $property . '_display';
          $row[$property] = $result->$greeting;
        }
        elseif (isset($pseudoconstants[$property])) {
          $row[$property] = CRM_Utils_Array::value(
            $result->{$pseudoconstants[$property]['dbName']},
            $pseudoconstants[$property]['values']
          );
        }
        elseif (strpos($property, '-url') !== FALSE) {
          $websiteUrl = '';
          $websiteKey = 'website-1';
          $propertyArray = explode('-', $property);
          $websiteFld = $websiteKey . '-' . array_pop($propertyArray);
          if (!empty($result->$websiteFld)) {
            $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
            $websiteType  = $websiteTypes[$result->{"$websiteKey-website_type_id"}];
            $websiteValue = $result->$websiteFld;
            $websiteUrl   = "<a href=\"{$websiteValue}\">{$websiteValue}  ({$websiteType})</a>";
          }
          $row[$property] = $websiteUrl;
        }
        else {
          $row[$property] = isset($result->$property) ? $result->$property : NULL;
        }
      }

      if (!empty($result->postal_code_suffix)) {
        $row['postal_code'] .= "-" . $result->postal_code_suffix;
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT &&
        $this->_searchContext == 'smog'
      ) {
        if (empty($result->status) &&
          $groupID
        ) {
          $contactID = $result->contact_id;
          if ($contactID) {
            $gcParams = array(
              'contact_id' => $contactID,
              'group_id' => $groupID,
            );

            $gcDefaults = array();
            CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_GroupContact', $gcParams, $gcDefaults);

            if (empty($gcDefaults)) {
              $row['status'] = ts('Smart');
            }
            else {
              $row['status'] = $gc[$gcDefaults['status']];
            }
          }
          else {
            $row['status'] = NULL;
          }
        }
        else {
          $row['status'] = $gc[$result->status];
        }
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT) {
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->contact_id;

        if (!empty($this->_formValues['deleted_contacts']) && CRM_Core_Permission::check('access deleted contacts')
        ) {
          $links = array(
            array(
              'name' => ts('View'),
              'url' => 'civicrm/contact/view',
              'qs' => 'reset=1&cid=%%id%%',
              'class' => 'no-popup',
              'title' => ts('View Contact Details'),
            ),
            array(
              'name' => ts('Restore'),
              'url' => 'civicrm/contact/view/delete',
              'qs' => 'reset=1&cid=%%id%%&restore=1',
              'title' => ts('Restore Contact'),
            ),
          );
          if (CRM_Core_Permission::check('delete contacts')) {
            $links[] = array(
              'name' => ts('Delete Permanently'),
              'url' => 'civicrm/contact/view/delete',
              'qs' => 'reset=1&cid=%%id%%&skip_undelete=1',
              'title' => ts('Permanently Delete Contact'),
            );
          }
          $row['action'] = CRM_Core_Action::formLink(
            $links,
            NULL,
            array('id' => $result->contact_id),
            ts('more'),
            FALSE,
            'contact.selector.row',
            'Contact',
            $result->contact_id
          );
        }
        elseif ((is_numeric(CRM_Utils_Array::value('geo_code_1', $row))) ||
          ($config->mapGeoCoding && !empty($row['city']) &&
            CRM_Utils_Array::value('state_province', $row)
          )
        ) {
          $row['action'] = CRM_Core_Action::formLink(
            $links,
            $mask,
            array('id' => $result->contact_id),
            ts('more'),
            FALSE,
            'contact.selector.row',
            'Contact',
            $result->contact_id
          );
        }
        else {
          $row['action'] = CRM_Core_Action::formLink(
            $links,
            $mapMask,
            array('id' => $result->contact_id),
            ts('more'),
            FALSE,
            'contact.selector.row',
            'Contact',
            $result->contact_id
          );
        }

        // allow components to add more actions
        CRM_Core_Component::searchAction($row, $result->contact_id);

        $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
          $result->contact_sub_type : $result->contact_type,
          FALSE,
          $result->contact_id
        );

        $row['contact_type_orig'] = $result->contact_sub_type ? $result->contact_sub_type : $result->contact_type;
        $row['contact_sub_type']  = $result->contact_sub_type ?
          CRM_Contact_BAO_ContactType::contactTypePairs(FALSE, $result->contact_sub_type, ', ') : $result->contact_sub_type;
        $row['contact_id'] = $result->contact_id;
        $row['sort_name'] = $result->sort_name;
        if (array_key_exists('id', $row)) {
          $row['id'] = $result->contact_id;
        }
      }

      // Dedupe contacts
      if (in_array($row['contact_id'], $seenIDs) === FALSE) {
        $seenIDs[] = $row['contact_id'];
        $rows[] = $row;
      }
    }

    return $rows;
  }

  /**
   * @param $sort
   *
   * @return string
   */
  function buildPrevNextCache($sort) {
    $cacheKey = 'civicrm search ' . $this->_key;

    // We should clear the cache in following conditions:
    // 1. when starting from scratch, i.e new search
    // 2. if records are sorted

    // get current page requested
    $pageNum = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

    // get the current sort order
    $currentSortID = CRM_Utils_Request::retrieve('crmSID', 'String', CRM_Core_DAO::$_nullObject);

    $session = CRM_Core_Session::singleton();

    // get previous sort id
    $previousSortID = $session->get('previousSortID');

    // check for current != previous to ensure cache is not reset if paging is done without changing
    // sort criteria
    if (!$pageNum || (!empty($currentSortID) && $currentSortID != $previousSortID) ) {
      CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey, 'civicrm_contact');
      // this means it's fresh search, so set pageNum=1
      if (!$pageNum) {
        $pageNum = 1;
      }
    }

    // set the current sort as previous sort
    if (!empty($currentSortID)) {
      $session->set('previousSortID', $currentSortID);
    }

    $pageSize = CRM_Utils_Request::retrieve('crmRowCount', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, 50);
    $firstRecord = ($pageNum - 1) * $pageSize;

    //for alphabetic pagination selection save
    $sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter', 'String', CRM_Core_DAO::$_nullObject);

    //for text field pagination selection save
    $countRow = CRM_Core_BAO_PrevNextCache::getCount($cacheKey, NULL, "entity_table = 'civicrm_contact'");

    // $sortByCharacter triggers a refresh in the prevNext cache
    if ($sortByCharacter && $sortByCharacter != 'all') {
      $cacheKey .= "_alphabet";
      $this->fillupPrevNextCache($sort, $cacheKey);
    }
    elseif ($firstRecord >= $countRow) {
      $this->fillupPrevNextCache($sort, $cacheKey, $countRow, 500);
    }
    return $cacheKey;
  }

  /**
   * @param $rows
   */
  function addActions(&$rows) {
    $config = CRM_Core_Config::singleton();

    $permissions = array(CRM_Core_Permission::getPermission());
    if (CRM_Core_Permission::check('delete contacts')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);
    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    $links = self::links($this->_context, $this->_contextMenu, $this->_key);


    foreach ($rows as $id => & $row) {
      if (!empty($this->_formValues['deleted_contacts']) && CRM_Core_Permission::check('access deleted contacts')
      ) {
        $links = array(
          array(
            'name' => ts('View'),
            'url' => 'civicrm/contact/view',
            'qs' => 'reset=1&cid=%%id%%',
            'class' => 'no-popup',
            'title' => ts('View Contact Details'),
          ),
          array(
            'name' => ts('Restore'),
            'url' => 'civicrm/contact/view/delete',
            'qs' => 'reset=1&cid=%%id%%&restore=1',
            'title' => ts('Restore Contact'),
          ),
        );
        if (CRM_Core_Permission::check('delete contacts')) {
          $links[] = array(
            'name' => ts('Delete Permanently'),
            'url' => 'civicrm/contact/view/delete',
            'qs' => 'reset=1&cid=%%id%%&skip_undelete=1',
            'title' => ts('Permanently Delete Contact'),
          );
        }
        $row['action'] = CRM_Core_Action::formLink(
          $links,
          null,
          array('id' => $row['contact_id']),
          ts('more'),
          FALSE,
          'contact.selector.actions',
          'Contact',
          $row['contact_id']
        );
      }
      elseif ((is_numeric(CRM_Utils_Array::value('geo_code_1', $row))) ||
        ($config->mapGeoCoding && !empty($row['city']) &&
          CRM_Utils_Array::value('state_province', $row)
        )
      ) {
        $row['action'] = CRM_Core_Action::formLink(
          $links,
          $mask,
          array('id' => $row['contact_id']),
          ts('more'),
          FALSE,
          'contact.selector.actions',
          'Contact',
          $row['contact_id']
        );
      }
      else {
        $row['action'] = CRM_Core_Action::formLink(
          $links,
          $mapMask,
          array('id' => $row['contact_id']),
          ts('more'),
          FALSE,
          'contact.selector.actions',
          'Contact',
          $row['contact_id']
        );
      }

      // allow components to add more actions
      CRM_Core_Component::searchAction($row, $row['contact_id']);

      if (!empty($row['contact_type_orig'])) {
        $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($row['contact_type_orig'],
          FALSE, $row['contact_id']);
      }
    }
  }

  /**
   * @param $rows
   */
  function removeActions(&$rows) {
    foreach ($rows as $rid => & $rValue) {
      unset($rValue['contact_type']);
      unset($rValue['action']);
    }
  }

  /**
   * @param object $sort
   * @param string $cacheKey
   * @param int $start
   * @param int $end
   */
  function fillupPrevNextCache($sort, $cacheKey, $start = 0, $end = 500) {
    $coreSearch = TRUE;
    // For custom searches, use the contactIDs method
    if (is_a($this, 'CRM_Contact_Selector_Custom')) {
      $sql = $this->_search->contactIDs($start, $end, $sort, TRUE);
      $replaceSQL = "SELECT contact_a.id as contact_id";
      $coreSearch = FALSE;
    }
    // For core searches use the searchQuery method
    else {
      $sql = $this->_query->searchQuery($start, $end, $sort, FALSE, $this->_query->_includeContactIds,
        FALSE, TRUE, TRUE);
      $replaceSQL = "SELECT contact_a.id as id";
    }

    // CRM-9096
    // due to limitations in our search query writer, the above query does not work
    // in cases where the query is being sorted on a non-contact table
    // this results in a fatal error :(
    // see below for the gross hack of trapping the error and not filling
    // the prev next cache in this situation
    // the other alternative of running the FULL query will just be incredibly inefficient
    // and slow things down way too much on large data sets / complex queries

    $insertSQL = "
INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data )
SELECT DISTINCT 'civicrm_contact', contact_a.id, contact_a.id, '$cacheKey', contact_a.display_name
";

    $sql = str_replace($replaceSQL, $insertSQL, $sql);

    $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    $result = CRM_Core_DAO::executeQuery($sql);
    unset($errorScope);

    if (is_a($result, 'DB_Error')) {
      // check if we get error during core search
      if ($coreSearch) {
        // in the case of error, try rebuilding cache using full sql which is used for search selector display
        // this fixes the bugs reported in CRM-13996 & CRM-14438
        $this->rebuildPreNextCache($start, $end, $sort, $cacheKey);
      }
      else {
        // return if above query fails
        return;
      }
    }

    // also record an entry in the cache key table, so we can delete it periodically
    CRM_Core_BAO_Cache::setItem($cacheKey, 'CiviCRM Search PrevNextCache', $cacheKey);
  }

  /**
   * This function is called to rebuild prev next cache using full sql in case of core search ( excluding custom search)
   *
   * @param int $start start for limit clause
   * @param int $end end for limit clause
   * @param $sort
   * @param string $cacheKey cache key
   *
   * @internal param $object $sort sort object
   * @return void
   */
  function rebuildPreNextCache($start, $end, $sort, $cacheKey) {
    // generate full SQL
    $sql = $this->_query->searchQuery($start, $end, $sort, FALSE, $this->_query->_includeContactIds,
      FALSE, FALSE, TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    // build insert query, note that currently we build cache for 500 contact records at a time, hence below approach
    $insertValues = array();
    while($dao->fetch()) {
      $insertValues[] = "('civicrm_contact', {$dao->contact_id}, {$dao->contact_id}, '{$cacheKey}', '" . CRM_Core_DAO::escapeString($dao->sort_name) . "')";
    }

    //update pre/next cache using single insert query
    if (!empty($insertValues)) {
      $sql = 'INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data ) VALUES
'.implode(',', $insertValues);

      $result = CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Given the current formValues, gets the query in local
   * language
   *
   * @param  array(
     reference)   $formValues   submitted formValues
   *
   * @return array              $qill         which contains an array of strings
   * @access public
   */

  // the current internationalisation is bad, but should more or less work
  // for most of "European" languages
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviCRM Contact Search');
  }

  /**
   * get colunmn headers for search selector
   *
   *
   * @return array $_columnHeaders
   * @access private
   */
  private static function &_getColumnHeaders() {
    if (!isset(self::$_columnHeaders)) {
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options', TRUE, NULL, TRUE
      );

      self::$_columnHeaders = array(
        'contact_type' => array('desc' => ts('Contact Type')),
        'sort_name' => array(
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
      );

      $defaultAddress = array(
        'street_address' => array('name' => ts('Address')),
        'city' => array(
          'name' => ts('City'),
          'sort' => 'city',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        'state_province' => array(
          'name' => ts('State'),
          'sort' => 'state_province',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        'postal_code' => array(
          'name' => ts('Postal'),
          'sort' => 'postal_code',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        'country' => array(
          'name' => ts('Country'),
          'sort' => 'country',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
      );

      foreach ($defaultAddress as $columnName => $column) {
        if (!empty($addressOptions[$columnName])) {
          self::$_columnHeaders[$columnName] = $column;
        }
      }

      self::$_columnHeaders['email'] = array(
        'name' => ts('Email'),
        'sort' => 'email',
        'direction' => CRM_Utils_Sort::DONTCARE,
      );

      self::$_columnHeaders['phone'] = array('name' => ts('Phone'));
    }
    return self::$_columnHeaders;
  }

  /**
   * @return CRM_Contact_BAO_Query
   */
  function &getQuery() {
    return $this->_query;
  }

  /**
   * @return CRM_Contact_DAO_Contact
   */
  function alphabetQuery() {
    return $this->_query->searchQuery(NULL, NULL, NULL, FALSE, FALSE, TRUE);
  }

  /**
   * @param $params
   * @param $action
   * @param $sortID
   * @param null $displayRelationshipType
   * @param string $queryOperator
   *
   * @return CRM_Contact_DAO_Contact
   */
  function contactIDQuery($params, $action, $sortID, $displayRelationshipType = NULL, $queryOperator = 'AND') {
    $sortOrder = &$this->getSortOrder($this->_action);
    $sort = new CRM_Utils_Sort($sortOrder, $sortID);

    // rectify params to what proximity search expects if there is a value for prox_distance
    // CRM-7021 CRM-7905
    if (!empty($params)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($params);
    }

    if (!$displayRelationshipType) {
      $query = new CRM_Contact_BAO_Query($params,
        $this->_returnProperties,
        NULL, FALSE, FALSE, 1,
        FALSE, TRUE, TRUE, NULL,
        $queryOperator
      );
    }
    else {
      $query = new CRM_Contact_BAO_Query($params, $this->_returnProperties,
        NULL, FALSE, FALSE, 1,
        FALSE, TRUE, TRUE, $displayRelationshipType,
        $queryOperator
      );
    }
    $value = $query->searchQuery(0, 0, $sort,
      FALSE, FALSE, FALSE,
      FALSE, FALSE
    );
    return $value;
  }

  /**
   * @param $returnProperties
   *
   * @return array
   */
  function &makeProperties(&$returnProperties) {
    $properties = array();
    foreach ($returnProperties as $name => $value) {
      if ($name != 'location') {
        // special handling for group and tag
        if (in_array($name, array('group', 'tag'))) {
          $name = "{$name}s";
        }

        // special handling for notes
        if (in_array($name, array('note', 'note_subject', 'note_body'))) {
          $name = "notes";
        }

        $properties[] = $name;
      }
      else {
        // extract all the location stuff
        foreach ($value as $n => $v) {
          foreach ($v as $n1 => $v1) {
            if (!strpos('_id', $n1) && $n1 != 'location_type') {
              $properties[] = "{$n}-{$n1}";
            }
          }
        }
      }
    }
    return $properties;
  }
}
//end of class

