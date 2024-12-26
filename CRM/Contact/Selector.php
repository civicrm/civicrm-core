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
 * Class is to retrieve and display a range of contacts that match the given criteria.
 *
 * It is specifically for results of advanced search options.
 */
class CRM_Contact_Selector extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  const CACHE_SIZE = 500;

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  public static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   */
  public static $_properties = [
    'contact_id',
    'contact_type',
    'contact_sub_type',
    'contact_is_deleted',
    'sort_name',
    'street_address',
    'city',
    'state_province',
    'postal_code',
    'country',
    'geo_code_1',
    'geo_code_2',
    'is_deceased',
    'email',
    'on_hold',
    'phone',
    'status',
    'do_not_email',
    'do_not_phone',
    'do_not_sms',
    'do_not_mail',
  ];

  /**
   * FormValues is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_formValues;

  /**
   * The contextMenu
   *
   * @var array
   */
  protected $_contextMenu;

  /**
   * The search context
   *
   * @var string
   */
  public $_context;

  /**
   * Params is the array in a value used by the search query creator
   *
   * @var array
   */
  public $_params;

  /**
   * The return properties used for search
   *
   * @var array
   */
  protected $_returnProperties;

  /**
   * Represent the type of selector
   *
   * @var int
   */
  protected $_action;

  protected $_searchContext;

  /**
   * Query object for this selector.
   *
   * @var CRM_Contact_BAO_Query
   */
  protected $_query;

  /**
   * Get the query object for this selector.
   *
   * @return CRM_Contact_BAO_Query
   */
  public function getQueryObject() {
    return $this->_query;
  }

  /**
   * Group id
   *
   * @var int
   */
  protected $_ufGroupID;

  /**
   * The public visible fields to be shown to the user
   *
   * @var array
   */
  protected $_fields;

  /**
   * Class constructor.
   *
   * @param $customSearchClass
   * @param array $formValues
   *   Array of form values imported.
   * @param array $params
   *   Array of parameters for query.
   * @param null $returnProperties
   * @param \const|int $action - action of search basic or advanced.
   *
   * @param bool $includeContactIds
   * @param bool $searchDescendentGroups
   * @param string $searchContext
   * @param null $contextMenu
   *
   * @return CRM_Contact_Selector
   */
  public function __construct(
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
    $force = CRM_Utils_Request::retrieve('force', 'Boolean');
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

    $this->_ufGroupID = $this->_formValues['uf_group_id'] ?? NULL;

    if ($this->_ufGroupID) {
      $this->_fields = CRM_Core_BAO_UFGroup::getListingFields(CRM_Core_Action::VIEW,
        CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY |
        CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
        FALSE, $this->_ufGroupID
      );
      self::$_columnHeaders = NULL;

      $this->_returnProperties = CRM_Contact_BAO_Contact::makeHierReturnProperties($this->_fields);
      $this->_returnProperties['contact_type'] = 1;
      $this->_returnProperties['contact_sub_type'] = 1;
      $this->_returnProperties['sort_name'] = 1;
      if (!empty($this->_returnProperties['location']) && is_array($this->_returnProperties['location'])) {
        foreach ($this->_returnProperties['location'] as $key => $property) {
          if (!empty($property['email'])) {
            $this->_returnProperties['location'][$key]['on_hold'] = 1;
          }
        }
      }
    }

    $displayRelationshipType = $this->_formValues['display_relationship_type'] ?? NULL;
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
  }

  /**
   * This method set cache key, later used in test environment
   *
   * @param string $key
   */
  public function setKey($key) {
    $this->_key = $key;
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   */
  public static function &links() {
    [$context, $contextMenu, $key] = func_get_args();
    $extraParams = ($key) ? "&key={$key}" : NULL;
    $searchContext = ($context) ? "&context=$context" : NULL;

    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view',
          'class' => 'no-popup',
          'qs' => "reset=1&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('View Contact Details'),
          'ref' => 'view-contact',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/add',
          'class' => 'no-popup',
          'qs' => "reset=1&action=update&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('Edit Contact Details'),
          'ref' => 'edit-contact',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
      ];

      //CRM-16552: mapAPIKey is not mandatory as google no longer requires an API Key
      if (\Civi::settings()->get('mapProvider') === 'Google' || (\Civi::settings()->get('mapProvider') && \Civi::settings()->get('mapAPIKey'))) {
        self::$_links[CRM_Core_Action::MAP] = [
          'name' => ts('Map'),
          'url' => 'civicrm/contact/map',
          'qs' => "reset=1&cid=%%id%%{$searchContext}{$extraParams}",
          'title' => ts('Map Contact'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::MAP),
        ];
      }

      // Adding Context Menu Links in more action
      if ($contextMenu) {
        $counter = 7000;
        foreach ($contextMenu as $value) {
          $contextVal = '&context=' . $value['key'];
          if ($value['key'] === 'delete') {
            $contextVal = $searchContext;
          }
          $url = "civicrm/contact/view/{$value['key']}";
          $qs = "reset=1&action=add&cid=%%id%%{$contextVal}{$extraParams}";
          if ($value['key'] === 'activity') {
            $qs = "action=browse&selectedChild=activity&reset=1&cid=%%id%%{$extraParams}";
          }
          elseif ($value['key'] === 'email') {
            $url = 'civicrm/contact/view/activity';
            $qs = "atype=3&action=add&reset=1&cid=%%id%%{$extraParams}";
          }

          self::$_links[$counter++] = [
            'name' => $value['title'],
            'url' => $url,
            'qs' => $qs,
            'title' => $value['title'],
            'ref' => $value['ref'],
            'class' => $value['class'] ?? NULL,
            'weight' => $value['weight'],
          ];
        }
      }
    }
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Contact %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = Civi::settings()->get('default_pager_size');

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * @param null $action
   * @param null $output
   *
   * @return array
   */
  public function &getColHeads($action = NULL, $output = NULL) {
    $colHeads = self::_getColumnHeaders();
    $colHeads[] = ['desc' => ts('Actions'), 'name' => ts('Action')];
    return $colHeads;
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    $headers = NULL;

    // unset return property elements that we don't care
    if (!empty($this->_returnProperties)) {
      $doNotCareElements = [
        'contact_type',
        'contact_sub_type',
        'sort_name',
      ];
      foreach ($doNotCareElements as $value) {
        unset($this->_returnProperties[$value]);
      }
    }

    if ($output == CRM_Core_Selector_Controller::EXPORT) {
      $csvHeaders = [ts('Contact ID'), ts('Contact Type')];
      foreach ($this->getColHeads($action, $output) as $column) {
        if (array_key_exists('name', $column)) {
          $csvHeaders[] = $column['name'];
        }
      }
      $headers = $csvHeaders;
    }
    elseif ($output == CRM_Core_Selector_Controller::SCREEN) {
      $csvHeaders = [ts('Name')];
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
      static $skipFields = ['group', 'tag'];
      $direction = CRM_Utils_Sort::ASCENDING;
      $empty = TRUE;
      if (!self::$_columnHeaders) {
        self::$_columnHeaders = [
          ['name' => ''],
          [
            'name' => ts('Name'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::ASCENDING,
          ],
        ];

        $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');

        foreach ($this->_fields as $name => $field) {
          if (!empty($field['in_selector']) &&
            !in_array($name, $skipFields)
          ) {
            if (strpos($name, '-') !== FALSE) {
              [$fieldName, $lType, $type] = CRM_Utils_System::explode('-', $name, 3);

              if ($lType === 'Primary') {
                $locationTypeName = 1;
              }
              else {
                $locationTypeName = $locationTypes[$lType];
              }

              if (in_array($fieldName, [
                'phone',
                'im',
                'email',
              ])) {
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
            if ($name === 'id') {
              $name = 'contact_id';
            }

            self::$_columnHeaders[] = [
              'name' => $field['title'],
              'sort' => $name,
              'direction' => $direction,
            ];
            $direction = CRM_Utils_Sort::DONTCARE;
            $empty = FALSE;
          }
        }

        // if we dont have any valid columns, dont add the implicit ones
        // this allows the template to check on emptiness of column headers
        if ($empty) {
          self::$_columnHeaders = [];
        }
        else {
          self::$_columnHeaders[] = ['desc' => ts('Actions'), 'name' => ts('Action')];
        }
      }
      $headers = self::$_columnHeaders;
    }
    elseif (!empty($this->_returnProperties)) {
      self::$_columnHeaders = [
        ['name' => ''],
        [
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ],
      ];
      $properties = self::makeProperties($this->_returnProperties);

      foreach ($properties as $prop) {
        if (strpos($prop, '-')) {
          [$loc, $fld, $phoneType] = CRM_Utils_System::explode('-', $prop, 3);
          $title = $this->_query->_fields[$fld]['title'];
          if (trim($phoneType) && !is_numeric($phoneType) && strtolower($phoneType) != $fld) {
            $title .= "-{$phoneType}";
          }
          // fetch Location type label from name as $loc, which will be later used in column header
          $title .= sprintf(" (%s)",
            CRM_Core_PseudoConstant::getLabel(
              'CRM_Core_DAO_Address',
              'location_type_id',
              CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Address', 'location_type_id', $loc)
            )
          );

        }
        elseif (isset($this->_query->_fields[$prop]) && isset($this->_query->_fields[$prop]['title'])) {
          $title = $this->_query->_fields[$prop]['title'];
        }
        elseif (isset($this->_query->_pseudoConstantsSelect[$prop]) && isset($this->_query->_pseudoConstantsSelect[$prop]['pseudoconstant']['optionGroupName'])) {
          $title = CRM_Core_BAO_OptionGroup::getTitleByName($this->_query->_pseudoConstantsSelect[$prop]['pseudoconstant']['optionGroupName']);
        }
        else {
          $title = '';
        }

        self::$_columnHeaders[] = ['name' => $title, 'sort' => $prop];
      }
      self::$_columnHeaders[] = ['name' => ts('Actions')];
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
   * @param int $action
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
    // Use count from cache during paging/sorting
    if (!empty($_GET['crmPID']) || !empty($_GET['crmSID'])) {
      $count = Civi::cache('long')->get("Search Results Count $this->_key");
    }
    if (empty($count)) {
      $count = $this->_query->searchQuery(0, 0, NULL, TRUE);
      Civi::cache('long')->set("Search Results Count $this->_key", $count);
    }
    return $count;
  }

  /**
   * Returns all the rows in the given offset and rowCount.
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return int
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    if (($output == CRM_Core_Selector_Controller::EXPORT ||
        $output == CRM_Core_Selector_Controller::SCREEN
      ) &&
      $this->_formValues['radio_ts'] === 'ts_sel'
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
      /** @var CRM_Core_PrevNextCache_Interface $prevNext */
      $prevNext = Civi::service('prevnext');
      $cacheKey = $this->buildPrevNextCache($sort);
      $cids = $prevNext->fetch($cacheKey, $offset, $rowCount);
      $resultSet = empty($cids) ? [] : $this->_query->getCachedContacts($cids, $includeContactIds)->fetchGenerator();
    }
    else {
      $resultSet = $this->_query->searchQuery($offset, $rowCount, $sort, FALSE, $includeContactIds)->fetchGenerator();
    }

    // process the result of the query
    $rows = [];
    $permissions = [CRM_Core_Permission::getPermission()];
    if (CRM_Core_Permission::check('delete contacts')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    if ($this->_searchContext === 'smog') {
      $gc = CRM_Core_SelectValues::groupContactStatus();
    }

    if ($this->_ufGroupID) {
      $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id');

      $names = [];
      static $skipFields = ['group', 'tag'];
      foreach ($this->_fields as $key => $field) {
        if (!empty($field['in_selector']) &&
          !in_array($key, $skipFields)
        ) {
          if (strpos($key, '-') !== FALSE) {
            [$fieldName, $id, $type] = CRM_Utils_System::explode('-', $key, 3);

            if ($id === 'Primary') {
              $locationTypeName = 1;
            }
            elseif ($fieldName === 'url') {
              $locationTypeName = "website-{$id}";
            }
            else {
              $locationTypeName = $locationTypes[$id] ?? NULL;
              if (!$locationTypeName) {
                continue;
              }
            }

            $locationTypeName = str_replace(' ', '_', $locationTypeName);
            if (in_array($fieldName, [
              'phone',
              'im',
              'email',
            ])) {
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

    $links = self::links($this->_context, $this->_contextMenu, $this->_key);

    //check explicitly added contact to a Smart Group.
    $groupID = $this->_formValues['group'] ?? NULL;

    $pseudoconstants = [];
    // for CRM-3157 purposes
    if (in_array('world_region', $names)) {
      $pseudoconstants['world_region'] = [
        'dbName' => 'worldregion_id',
        'values' => CRM_Core_PseudoConstant::worldRegion(),
      ];
    }

    foreach ($resultSet as $result) {
      $row = [];
      $this->_query->convertToPseudoNames($result);
      // the columns we are interested in
      foreach ($names as $property) {
        if ($property === 'status') {
          continue;
        }
        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($property)) {
          $row[$property] = CRM_Core_BAO_CustomField::displayValue(
            $result->$property,
            $cfID,
            $result->contact_id
          );
        }
        elseif (strpos($property, '-im')) {
          $row[$property] = $result->$property;
          if (!empty($result->$property)) {
            $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');
            $providerId = $property . "-provider_id";
            $providerName = $imProviders[$result->$providerId];
            $row[$property] = $result->$property . " ({$providerName})";
          }
        }
        elseif (in_array($property, [
          'addressee',
          'email_greeting',
          'postal_greeting',
        ])) {
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
          $websiteKey = str_replace('-url', '', $property);
          $propertyArray = explode('-', $property);
          $websiteFld = $websiteKey . '-' . array_pop($propertyArray);
          if (!empty($result->$websiteFld)) {
            $websiteTypes = CRM_Core_DAO_Website::buildOptions('website_type_id');
            $websiteType = $websiteTypes[$result->{"$websiteKey-website_type_id"}];
            $websiteValue = $result->$websiteFld;
            $websiteUrl = "<a href=\"{$websiteValue}\">{$websiteValue}  ({$websiteType})</a>";
          }
          $row[$property] = $websiteUrl;
        }
        elseif (strpos($property, '-email') !== FALSE) {
          [$locType] = explode("-email", $property);
          $onholdProperty = "{$locType}-on_hold";

          $row[$property] = $result->$property ?? NULL;
          if (!empty($row[$property]) && !empty($result->$onholdProperty)) {
            $row[$property] .= " (On Hold)";
          }
        }
        else {
          $row[$property] = $result->$property ?? NULL;
        }
      }

      if (!empty($result->postal_code_suffix)) {
        $row['postal_code'] .= "-" . $result->postal_code_suffix;
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT &&
        $this->_searchContext === 'smog'
      ) {
        if (empty($result->status) &&
          $groupID
        ) {
          $contactID = $result->contact_id;
          if ($contactID) {
            $gcParams = [
              'contact_id' => $contactID,
              'group_id' => $groupID,
            ];

            $gcDefaults = [];
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
          $links = [
            [
              'name' => ts('View'),
              'url' => 'civicrm/contact/view',
              'qs' => 'reset=1&cid=%%id%%',
              'class' => 'no-popup',
              'title' => ts('View Contact Details'),
              'weight' => -20,
            ],
            [
              'name' => ts('Restore'),
              'url' => 'civicrm/contact/view/delete',
              'qs' => 'reset=1&cid=%%id%%&restore=1',
              'title' => ts('Restore Contact'),
            ],
          ];
          if (CRM_Core_Permission::check('delete contacts')) {
            $links[] = [
              'name' => ts('Delete Permanently'),
              'url' => 'civicrm/contact/view/delete',
              'qs' => 'reset=1&cid=%%id%%&skip_undelete=1',
              'title' => ts('Permanently Delete Contact'),
              'weight' => 100,
            ];
          }
          $row['action'] = CRM_Core_Action::formLink(
            $links,
            NULL,
            ['id' => $result->contact_id],
            ts('more'),
            FALSE,
            'contact.selector.row',
            'Contact',
            $result->contact_id
          );
        }
        elseif ((is_numeric($row['geo_code_1'] ?? '')) ||
          (!empty($row['city']) && !empty($row['state_province']))
        ) {
          $row['action'] = CRM_Core_Action::formLink(
            $links,
            $mask,
            ['id' => $result->contact_id],
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
            ['id' => $result->contact_id],
            ts('more'),
            FALSE,
            'contact.selector.row',
            'Contact',
            $result->contact_id
          );
        }

        // allow components to add more actions
        CRM_Core_Component::searchAction($row, $result->contact_id);

        $contactUrl = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$result->contact_id}&key={$this->_key}&context={$this->_context}"
        );
        $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?: $result->contact_type,
          FALSE,
          $result->contact_id,
          TRUE,
          $contactUrl
        );

        $row['contact_type_orig'] = $result->contact_sub_type ?: $result->contact_type;
        $row['contact_id'] = $result->contact_id;
        $row['sort_name'] = $result->sort_name;
        // Surely this if should be if NOT - otherwise it's just wierd.
        if (array_key_exists('id', $row)) {
          $row['id'] = $result->contact_id;
        }
      }

      $rows[$row['contact_id']] = $row;
    }

    return $rows;
  }

  /**
   * @param CRM_Utils_Sort $sort
   *
   * @return string
   */
  private function buildPrevNextCache($sort) {
    $cacheKey = 'civicrm search ' . $this->_key;

    // We should clear the cache in following conditions:
    // 1. when starting from scratch, i.e new search
    // 2. if records are sorted

    // get current page requested
    $pageNum = CRM_Utils_Request::retrieve('crmPID', 'Integer');

    // get the current sort order
    $currentSortID = CRM_Utils_Request::retrieve('crmSID', 'String');

    $session = CRM_Core_Session::singleton();

    // get previous sort id
    $previousSortID = $session->get('previousSortID');

    // check for current != previous to ensure cache is not reset if paging is done without changing
    // sort criteria
    if (!$pageNum || (!empty($currentSortID) && $currentSortID != $previousSortID)) {
      Civi::service('prevnext')->deleteItem(NULL, $cacheKey, 'civicrm_contact');
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
    $sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter', 'String');

    //for text field pagination selection save
    $countRow = Civi::service('prevnext')->getCount($cacheKey);
    // $sortByCharacter triggers a refresh in the prevNext cache
    if ($sortByCharacter && $sortByCharacter != 'all') {
      $this->fillupPrevNextCache($sort, $cacheKey, 0, max(self::CACHE_SIZE, $pageSize));
    }
    elseif (($firstRecord + $pageSize) >= $countRow) {
      $this->fillupPrevNextCache($sort, $cacheKey, $countRow, max(self::CACHE_SIZE, $pageSize) + $firstRecord - $countRow);
    }
    return $cacheKey;
  }

  /**
   * @param $rows
   */
  public function addActions(&$rows) {

    $basicPermissions = CRM_Core_Permission::check('delete contacts') ? [CRM_Core_Permission::DELETE] : [];

    // get permissions on an individual level (CRM-12645)
    // @todo look at storing this to the session as this is called twice during search results render.
    $can_edit_list = CRM_Contact_BAO_Contact_Permission::allowList(array_keys($rows), CRM_Core_Permission::EDIT);

    $links_template = self::links($this->_context, $this->_contextMenu, $this->_key);

    foreach ($rows as $id => & $row) {
      $links = $links_template;
      if (in_array($id, $can_edit_list)) {
        $mask = CRM_Core_Action::mask(array_merge([CRM_Core_Permission::EDIT], $basicPermissions));
      }
      else {
        $mask = CRM_Core_Action::mask(array_merge([CRM_Core_Permission::VIEW], $basicPermissions));
      }

      if ((!is_numeric($row['geo_code_1'] ?? '')) &&
        (empty($row['city']) || empty($row['state_province']))
      ) {
        $mask = $mask & 4095;
      }

      if (!empty($this->_formValues['deleted_contacts']) && CRM_Core_Permission::check('access deleted contacts')
      ) {
        $links = [
          [
            'name' => ts('View'),
            'url' => 'civicrm/contact/view',
            'qs' => 'reset=1&cid=%%id%%',
            'class' => 'no-popup',
            'title' => ts('View Contact Details'),
            'weight' => -20,
          ],
          [
            'name' => ts('Restore'),
            'url' => 'civicrm/contact/view/delete',
            'qs' => 'reset=1&cid=%%id%%&restore=1',
            'title' => ts('Restore Contact'),
            'weight' => 80,
          ],
        ];
        if (CRM_Core_Permission::check('delete contacts')) {
          $links[] = [
            'name' => ts('Delete Permanently'),
            'url' => 'civicrm/contact/view/delete',
            'qs' => 'reset=1&cid=%%id%%&skip_undelete=1',
            'title' => ts('Permanently Delete Contact'),
            'weight' => 100,
          ];
        }
        $row['action'] = CRM_Core_Action::formLink(
          $links,
          NULL,
          ['id' => $row['contact_id']],
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
          $mask,
          ['id' => $row['contact_id']],
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
        $contactUrl = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$row['contact_id']}&key={$this->_key}&context={$this->_context}"
        );
        $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($row['contact_type_orig'],
          FALSE,
          $row['contact_id'],
          TRUE,
          $contactUrl
        );
      }
    }
  }

  /**
   * @param $rows
   */
  public function removeActions(&$rows) {
    foreach ($rows as $rid => & $rValue) {
      unset($rValue['contact_type']);
      unset($rValue['action']);
    }
  }

  /**
   * @param CRM_Utils_Sort $sort
   * @param string $cacheKey
   * @param int $start
   * @param int $end
   *
   * @todo - use test cover in CRM_Contact_Form_Search_BasicTest to
   * to remove the extraneous logging that happens in the tested
   * scenario (It does the catch & then write to the log - I was
   * going to fix but got stalled on getting https://github.com/civicrm/civicrm-core/pull/25392
   * merged - this comment won't conflict with that PR :-)
   *
   * @throws \CRM_Core_Exception
   */
  private function fillupPrevNextCache($sort, $cacheKey, $start = 0, $end = self::CACHE_SIZE) {
    $sql = $this->_query->getSearchSQL($start, $end, $sort, FALSE, $this->_query->_includeContactIds,
        FALSE, TRUE);

    // CRM-9096
    // due to limitations in our search query writer, the above query does not work
    // in cases where the query is being sorted on a non-contact table
    // this results in a fatal error :(
    // see below for the gross hack of trapping the error and not filling
    // the prev next cache in this situation
    // the other alternative of running the FULL query will just be incredibly inefficient
    // and slow things down way too much on large data sets / complex queries

    $selectSQL = CRM_Core_DAO::composeQuery('SELECT DISTINCT %1, contact_a.id, contact_a.sort_name', [1 => [$cacheKey, 'String']]);

    $sql = str_ireplace(['SELECT contact_a.id as contact_id', 'SELECT contact_a.id as id'], $selectSQL, $sql);
    $sql = str_ireplace('ORDER BY `contact_id`', 'ORDER BY `id`', $sql, $sql);

    try {
      Civi::service('prevnext')->fillWithSql($cacheKey, $sql);
    }
    catch (\Exception $e) {
      // in the case of error, try rebuilding cache using full sql which is used for search selector display
      // this fixes the bugs reported in CRM-13996 & CRM-14438
      $this->rebuildPreNextCache($start, $end, $sort, $cacheKey);
    }

    if (Civi::service('prevnext') instanceof CRM_Core_PrevNextCache_Sql) {
      // SQL-backed prevnext cache uses an extra record for pruning the cache.
      // Also ensure that caches stay alive for 2 days as per previous code
      Civi::cache('prevNextCache')->set($cacheKey, $cacheKey, 60 * 60 * 24 * CRM_Core_PrevNextCache_Sql::cacheDays);
    }
  }

  /**
   * called to rebuild prev next cache using full sql in case of core search ( excluding custom search)
   *
   * @param int $start
   *   Start for limit clause.
   * @param int $end
   *   End for limit clause.
   * @param CRM_Utils_Sort $sort
   * @param string $cacheKey
   *   Cache key.
   */
  private function rebuildPreNextCache($start, $end, $sort, $cacheKey): void {
    // generate full SQL
    $sql = $this->_query->searchQuery($start, $end, $sort, FALSE, $this->_query->_includeContactIds,
      FALSE, FALSE, TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    // build insert query, note that currently we build cache for 500 (self::CACHE_SIZE) contact records at a time, hence below approach
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'entity_table' => 'civicrm_contact',
        'entity_id1' => $dao->contact_id,
        'entity_id2' => $dao->contact_id,
        'data' => $dao->sort_name,
      ];
    }

    Civi::service('prevnext')->fillWithArray($cacheKey, $rows);
  }

  /**
   * @inheritDoc
   */
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('CiviCRM Contact Search');
  }

  /**
   * Get colunmn headers for search selector.
   *
   * @return array
   */
  private static function &_getColumnHeaders() {
    if (!isset(self::$_columnHeaders)) {
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options', TRUE, NULL, TRUE
      );

      self::$_columnHeaders = [
        'contact_type' => ['desc' => ts('Contact Type')],
        'sort_name' => [
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ],
      ];

      $defaultAddress = [
        'street_address' => ['name' => ts('Address')],
        'city' => [
          'name' => ts('City'),
          'sort' => 'city',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        'state_province' => [
          'name' => ts('State'),
          'sort' => 'state_province',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        'postal_code' => [
          'name' => ts('Postal'),
          'sort' => 'postal_code',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        'country' => [
          'name' => ts('Country'),
          'sort' => 'country',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
      ];

      foreach ($defaultAddress as $columnName => $column) {
        if (!empty($addressOptions[$columnName])) {
          self::$_columnHeaders[$columnName] = $column;
        }
      }

      self::$_columnHeaders['email'] = [
        'name' => ts('Email'),
        'sort' => 'email',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ];

      self::$_columnHeaders['phone'] = ['name' => ts('Phone')];
    }
    return self::$_columnHeaders;
  }

  /**
   * @return CRM_Contact_BAO_Query
   */
  public function getQuery() {
    return $this->_query;
  }

  /**
   * @return CRM_Contact_DAO_Contact
   */
  public function alphabetQuery() {
    return $this->_query->alphabetQuery();
  }

  /**
   * @param array $params
   * @param int $sortID
   * @param null $displayRelationshipType
   * @param string $queryOperator
   *
   * @return CRM_Contact_DAO_Contact
   */
  public function contactIDQuery($params, $sortID, $displayRelationshipType = NULL, $queryOperator = 'AND') {
    $sortOrder = &$this->getSortOrder($this->_action);
    $sort = new CRM_Utils_Sort($sortOrder, $sortID);

    // rectify params to what proximity search expects if there is a value for prox_distance
    // CRM-7021 CRM-7905
    if (!empty($params)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($params);
    }

    if (!$displayRelationshipType) {
      $query = new CRM_Contact_BAO_Query($params,
        CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES,
        NULL, FALSE, FALSE, 1,
        FALSE, TRUE, TRUE, NULL,
        $queryOperator
      );
    }
    else {
      $query = new CRM_Contact_BAO_Query($params,
        CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES,
        NULL, FALSE, FALSE, 1,
        FALSE, TRUE, TRUE, $displayRelationshipType,
        $queryOperator
      );
    }
    return $query->searchQuery(0, 0, $sort);
  }

  /**
   * @param $returnProperties
   *
   * @return array
   */
  public function &makeProperties(&$returnProperties) {
    $properties = [];
    foreach ($returnProperties as $name => $value) {
      if ($name != 'location') {
        // special handling for group and tag
        if (in_array($name, ['group', 'tag'])) {
          $name = "{$name}s";
        }

        // special handling for notes
        if (in_array($name, ['note', 'note_subject', 'note_body'])) {
          $name = "notes";
        }

        $properties[] = $name;
      }
      else {
        // extract all the location stuff
        foreach ($value as $n => $v) {
          foreach ($v as $n1 => $v1) {
            if (!strpos('_id', $n1) && $n1 != 'location_type') {
              $n = str_replace(' ', '_', $n);
              $properties[] = "{$n}-{$n1}";
            }
          }
        }
      }
    }
    return $properties;
  }

}
