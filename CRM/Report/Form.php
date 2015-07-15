<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class CRM_Report_Form
 */
class CRM_Report_Form extends CRM_Core_Form {
  const ROW_COUNT_LIMIT = 50;

  /**
   * Operator types - used for displaying filter elements
   */
  const
    OP_INT = 1,
    OP_STRING = 2,
    OP_DATE = 4,
    OP_DATETIME = 5,
    OP_FLOAT = 8,
    OP_SELECT = 64,
    OP_MULTISELECT = 65,
    OP_MULTISELECT_SEPARATOR = 66,
    OP_MONTH = 128,
    OP_ENTITYREF = 256;

  /**
   * The id of the report instance
   *
   * @var integer
   */
  protected $_id;

  /**
   * The id of the report template
   *
   * @var integer;
   */
  protected $_templateID;

  /**
   * The report title
   *
   * @var string
   */
  protected $_title;
  protected $_noFields = FALSE;

  /**
   * The set of all columns in the report. An associative array
   * with column name as the key and attributes as the value
   *
   * @var array
   */
  protected $_columns = array();

  /**
   * The set of filters in the report
   *
   * @var array
   */
  protected $_filters = array();

  /**
   * The set of optional columns in the report
   *
   * @var array
   */
  protected $_options = array();

  protected $_defaults = array();

  /**
   * By default most reports hide contact id.
   * Setting this to true makes it available
   */
  protected $_exposeContactID = TRUE;

  /**
   * Set of statistic fields
   *
   * @var array
   */
  protected $_statFields = array();

  /**
   * Set of statistics data
   *
   * @var array
   */
  protected $_statistics = array();

  /**
   * List of fields not to be repeated during display
   *
   * @var array
   */
  protected $_noRepeats = array();

  /**
   * List of fields not to be displayed
   *
   * @var array
   */
  protected $_noDisplay = array();

  /**
   * Object type that a custom group extends
   *
   * @var null
   */
  protected $_customGroupExtends = NULL;
  protected $_customGroupExtendsJoin = array();
  protected $_customGroupFilters = TRUE;
  protected $_customGroupGroupBy = FALSE;
  protected $_customGroupJoin = 'LEFT JOIN';

  /**
   * Build tags filter
   */
  protected $_tagFilter = FALSE;

  /**
   * specify entity table for tags filter
   */
  protected $_tagFilterTable = 'civicrm_contact';

  /**
   * Build groups filter.
   *
   * @var bool
   */
  protected $_groupFilter = FALSE;

  /**
   * Navigation fields
   *
   * @var array
   */
  public $_navigation = array();

  public $_drilldownReport = array();

  /**
   * Array of tabs to display on report.
   *
   * E.g we define the tab title, the tpl and the tab-specific part of the css or  html link.
   *
   *  $this->tabs['OrderBy'] = array(
   *    'title' => ts('Sorting'),
   *    'tpl' => 'OrderBy',
   *    'div_label' => 'order-by',
   *  );
   *
   * @var array
   */
  protected $tabs = array();

  /**
   * An attribute for checkbox/radio form field layout
   *
   * @var array
   */
  protected $_fourColumnAttribute = array(
    '</td><td width="25%">',
    '</td><td width="25%">',
    '</td><td width="25%">',
    '</tr><tr><td>',
  );

  protected $_force = 1;

  protected $_params = NULL;
  protected $_formValues = NULL;
  protected $_instanceValues = NULL;

  protected $_instanceForm = FALSE;
  protected $_criteriaForm = FALSE;

  protected $_instanceButtonName = NULL;
  protected $_createNewButtonName = NULL;
  protected $_printButtonName = NULL;
  protected $_pdfButtonName = NULL;
  protected $_csvButtonName = NULL;
  protected $_groupButtonName = NULL;
  protected $_chartButtonName = NULL;
  protected $_csvSupported = TRUE;
  protected $_add2groupSupported = TRUE;
  protected $_groups = NULL;
  protected $_grandFlag = FALSE;
  protected $_rowsFound = NULL;
  protected $_selectAliases = array();
  protected $_rollup = NULL;

  /**
   * @var array
   */
  protected $_aliases = array();

  /**
   * @var string
   */
  protected $_where;

  /**
   * @var string
   */
  protected $_from;

  /**
   * SQL Limit clause
   * @var  string
   */
  protected $_limit = NULL;

  /**
   * This can be set to specify a limit to the number of rows
   * Since it is currently envisaged as part of the api usage it is only being applied
   * when $_output mode is not 'html' or 'group' so as not to have to interpret / mess with that part
   * of the code (see limit() fn
   * @var integer
   */
  protected $_limitValue = NULL;

  /**
   * This can be set to specify row offset
   * See notes on _limitValue
   * @var integer
   */
  protected $_offsetValue = NULL;
  /**
   * @var null
   */
  protected $_sections = NULL;
  protected $_autoIncludeIndexedFieldsAsOrderBys = 0;
  protected $_absoluteUrl = FALSE;

  /**
   * Flag to indicate if result-set is to be stored in a class variable which could be retrieved using getResultSet() method.
   *
   * @var boolean
   */
  protected $_storeResultSet = FALSE;

  /**
   * When _storeResultSet Flag is set use this var to store result set in form of array
   *
   * @var boolean
   */
  protected $_resultSet = array();

  /**
   * To what frequency group-by a date column
   *
   * @var array
   */
  protected $_groupByDateFreq = array(
    'MONTH' => 'Month',
    'YEARWEEK' => 'Week',
    'QUARTER' => 'Quarter',
    'YEAR' => 'Year',
  );

  /**
   * Variables to hold the acl inner join and where clause
   */
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Array of DAO tables having columns included in SELECT or ORDER BY clause
   *
   * @var array
   */
  protected $_selectedTables;

  /**
   * Output mode e.g 'print', 'csv', 'pdf'
   * @var string
   */
  protected $_outputMode;

  public $_having = NULL;
  public $_select = NULL;
  public $_selectClauses = array();
  public $_columnHeaders = array();
  public $_orderBy = NULL;
  public $_orderByFields = array();
  public $_orderByArray = array();
  public $_groupBy = NULL;
  public $_whereClauses = array();
  public $_havingClauses = array();

  /**
   * DashBoardRowCount Dashboard row count
   * @var Integer
   */
  public $_dashBoardRowCount;

  /**
   * Is this being called without a form controller (ie. the report is being render outside the normal form
   * - e.g the api is retrieving the rows
   * @var boolean
   */
  public $noController = FALSE;

  /**
   * Variable to hold the currency alias
   */
  protected $_currencyColumn = NULL;

  /**
   * @var string
   */
  protected $_interval;

  /**
   * @var bool
   */
  protected $_sendmail;

  /**
   * @var int
   */
  protected $_chartId;

  /**
   * @var int
   */
  public $_section;

  /**
   * @var string Report description.
   */
  public $_description;

  /**
   * @var bool Is an address field selected.
   *   This was intended to determine if the address table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_addressField;

  /**
   * @var bool Is an email field selected.
   *   This was intended to determine if the email table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_emailField;

  /**
   * @var bool Is a phone field selected.
   *   This was intended to determine if the phone table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_phoneField;

  /**
   * @var bool Create new report instance? (or update existing) on save.
   */
  protected $_createNew;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->addClass('crm-report-form');

    if ($this->_tagFilter) {
      $this->buildTagFilter();
    }
    if ($this->_exposeContactID) {
      if (array_key_exists('civicrm_contact', $this->_columns)) {
        $this->_columns['civicrm_contact']['fields']['exposed_id'] = array(
          'name' => 'id',
          'title' => 'Contact ID',
          'no_repeat' => TRUE,
        );
      }
    }

    if ($this->_groupFilter) {
      $this->buildGroupFilter();
    }

    // Get all custom groups
    $allGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_CustomField', 'custom_group_id');

    // Get the custom groupIds for which the user has VIEW permission
    // If the user has 'access all custom data' permission, we'll leave $permCustomGroupIds empty
    // and addCustomDataToColumns() will allow access to all custom groups.
    $permCustomGroupIds = array();
    if (!CRM_Core_Permission::check('access all custom data')) {
      $permCustomGroupIds = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_custom_group', $allGroups, NULL);
      // do not allow custom data for reports if user doesn't have
      // permission to access custom data.
      if (!empty($this->_customGroupExtends) && empty($permCustomGroupIds)) {
        $this->_customGroupExtends = array();
      }
    }

    // merge custom data columns to _columns list, if any
    $this->addCustomDataToColumns(TRUE, $permCustomGroupIds);

    // add / modify display columns, filters ..etc
    CRM_Utils_Hook::alterReportVar('columns', $this->_columns, $this);

    //assign currencyColumn variable to tpl
    $this->assign('currencyColumn', $this->_currencyColumn);
  }

  /**
   * Shared pre-process function.
   *
   * If overriding preProcess function this should still be called.
   *
   * @throws \Exception
   */
  public function preProcessCommon() {
    $this->_force
      = CRM_Utils_Request::retrieve(
      'force',
      'Boolean',
      CRM_Core_DAO::$_nullObject
      );

    $this->_dashBoardRowCount
      = CRM_Utils_Request::retrieve(
      'rowCount',
      'Integer',
      CRM_Core_DAO::$_nullObject
      );

    $this->_section = CRM_Utils_Request::retrieve('section', 'Integer', CRM_Core_DAO::$_nullObject);

    $this->assign('section', $this->_section);
    CRM_Core_Region::instance('page-header')->add(array(
      'markup' => sprintf('<!-- Report class: [%s] -->', htmlentities(get_class($this))),
    ));
    if (!$this->noController) {
      $this->setID($this->get('instanceId'));

      if (!$this->_id) {
        $this->setID(CRM_Report_Utils_Report::getInstanceID());
        if (!$this->_id) {
          $this->setID(CRM_Report_Utils_Report::getInstanceIDForPath());
        }
      }

      // set qfkey so that pager picks it up and use it in the "Next > Last >>" links.
      // FIXME: Note setting it in $_GET doesn't work, since pager generates link based on QUERY_STRING
      $_SERVER['QUERY_STRING'] .= "&qfKey={$this->controller->_key}";
    }

    if ($this->_id) {
      $this->assign('instanceId', $this->_id);
      $params = array('id' => $this->_id);
      $this->_instanceValues = array();
      CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
        $params,
        $this->_instanceValues
      );
      if (empty($this->_instanceValues)) {
        CRM_Core_Error::fatal("Report could not be loaded.");
      }
      $this->_title = $this->_instanceValues['title'];
      if (!empty($this->_instanceValues['permission']) &&
        (!(CRM_Core_Permission::check($this->_instanceValues['permission']) ||
          CRM_Core_Permission::check('administer Reports')
        ))
      ) {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }

      $formValues = CRM_Utils_Array::value('form_values', $this->_instanceValues);
      if ($formValues) {
        $this->_formValues = unserialize($formValues);
      }
      else {
        $this->_formValues = NULL;
      }

      // lets always do a force if reset is found in the url.
      if (!empty($_REQUEST['reset'])) {
        $this->_force = 1;
      }

      // set the mode
      $this->assign('mode', 'instance');
    }
    elseif (!$this->noController) {
      list($optionValueID, $optionValue) = CRM_Report_Utils_Report::getValueIDFromUrl();
      $instanceCount = CRM_Report_Utils_Report::getInstanceCount($optionValue);
      if (($instanceCount > 0) && $optionValueID) {
        $this->assign('instanceUrl',
          CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid=$optionValueID"
          )
        );
      }
      if ($optionValueID) {
        $this->_description = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $optionValueID, 'description');
      }

      // set the mode
      $this->assign('mode', 'template');
    }

    // lets display the Report Settings section
    $this->_instanceForm = $this->_force || $this->_id || (!empty($_POST));

    // Do not display Report Settings section if administer Reports permission is absent OR
    // if report instance is reserved and administer reserved reports absent
    if (!CRM_Core_Permission::check('administer Reports') ||
      ($this->_instanceValues['is_reserved'] &&
        !CRM_Core_Permission::check('administer reserved reports'))
    ) {
      $this->_instanceForm = FALSE;
    }

    $this->assign('criteriaForm', FALSE);
    // Display Report Criteria section if user has access Report Criteria OR administer Reports AND report instance is not reserved
    if (CRM_Core_Permission::check('administer Reports') ||
      CRM_Core_Permission::check('access Report Criteria')
    ) {
      if (!$this->_instanceValues['is_reserved'] ||
        CRM_Core_Permission::check('administer reserved reports')
      ) {
        $this->assign('criteriaForm', TRUE);
        $this->_criteriaForm = TRUE;
      }
    }

    $this->_instanceButtonName = $this->getButtonName('submit', 'save');
    $this->_createNewButtonName = $this->getButtonName('submit', 'next');
    $this->_printButtonName = $this->getButtonName('submit', 'print');
    $this->_pdfButtonName = $this->getButtonName('submit', 'pdf');
    $this->_csvButtonName = $this->getButtonName('submit', 'csv');
    $this->_groupButtonName = $this->getButtonName('submit', 'group');
    $this->_chartButtonName = $this->getButtonName('submit', 'chart');
  }

  /**
   * Add bread crumb.
   */
  public function addBreadCrumb() {
    $breadCrumbs
      = array(
        array(
          'title' => ts('Report Templates'),
          'url' => CRM_Utils_System::url('civicrm/admin/report/template/list', 'reset=1'),
        ),
      );

    CRM_Utils_System::appendBreadCrumb($breadCrumbs);
  }

  /**
   * Pre process function.
   *
   * Called prior to build form.
   */
  public function preProcess() {
    $this->preProcessCommon();

    if (!$this->_id) {
      $this->addBreadCrumb();
    }

    foreach ($this->_columns as $tableName => $table) {
      // set alias
      if (!isset($table['alias'])) {
        $this->_columns[$tableName]['alias'] = substr($tableName, 8) .
          '_civireport';
      }
      else {
        $this->_columns[$tableName]['alias'] = $table['alias'] . '_civireport';
      }

      $this->_aliases[$tableName] = $this->_columns[$tableName]['alias'];

      $daoOrBaoName = NULL;
      // higher preference to bao object
      if (array_key_exists('bao', $table)) {
        $daoOrBaoName = $table['bao'];
        $expFields = $daoOrBaoName::exportableFields();
      }
      elseif (array_key_exists('dao', $table)) {
        $daoOrBaoName = $table['dao'];
        $expFields = $daoOrBaoName::export();
      }
      else {
        $expFields = array();
      }

      $doNotCopy = array('required');

      $fieldGroups = array('fields', 'filters', 'group_bys', 'order_bys');
      foreach ($fieldGroups as $fieldGrp) {
        if (!empty($table[$fieldGrp]) && is_array($table[$fieldGrp])) {
          foreach ($table[$fieldGrp] as $fieldName => $field) {
            // $name is the field name used to reference the BAO/DAO export fields array
            $name = isset($field['name']) ? $field['name'] : $fieldName;

            // Sometimes the field name key in the BAO/DAO export fields array is
            // different from the actual database field name.
            // Unset $field['name'] so that actual database field name can be obtained
            // from the BAO/DAO export fields array.
            unset($field['name']);

            if (array_key_exists($name, $expFields)) {
              foreach ($doNotCopy as $dnc) {
                // unset the values we don't want to be copied.
                unset($expFields[$name][$dnc]);
              }
              if (empty($field)) {
                $this->_columns[$tableName][$fieldGrp][$fieldName] = $expFields[$name];
              }
              else {
                foreach ($expFields[$name] as $property => $val) {
                  if (!array_key_exists($property, $field)) {
                    $this->_columns[$tableName][$fieldGrp][$fieldName][$property] = $val;
                  }
                }
              }
            }

            // fill other vars
            if (!empty($field['no_repeat'])) {
              $this->_noRepeats[] = "{$tableName}_{$fieldName}";
            }
            if (!empty($field['no_display'])) {
              $this->_noDisplay[] = "{$tableName}_{$fieldName}";
            }

            // set alias = table-name, unless already set
            $alias = isset($field['alias']) ? $field['alias'] : (
              isset($this->_columns[$tableName]['alias']) ? $this->_columns[$tableName]['alias'] : $tableName
            );
            $this->_columns[$tableName][$fieldGrp][$fieldName]['alias'] = $alias;

            // set name = fieldName, unless already set
            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['name'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['name'] = $name;
            }

            // set dbAlias = alias.name, unless already set
            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['dbAlias'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['dbAlias']
                = $alias . '.' .
                $this->_columns[$tableName][$fieldGrp][$fieldName]['name'];
            }

            // a few auto fills for filters
            if ($fieldGrp == 'filters') {
              // fill operator types
              if (!array_key_exists('operatorType', $this->_columns[$tableName][$fieldGrp][$fieldName])) {
                switch (CRM_Utils_Array::value('type', $this->_columns[$tableName][$fieldGrp][$fieldName])) {
                  case CRM_Utils_Type::T_MONEY:
                  case CRM_Utils_Type::T_FLOAT:
                    $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
                    break;

                  case CRM_Utils_Type::T_INT:
                    $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
                    break;

                  case CRM_Utils_Type::T_DATE:
                    $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_DATE;
                    break;

                  case CRM_Utils_Type::T_BOOLEAN:
                    $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_SELECT;
                    if (!array_key_exists('options', $this->_columns[$tableName][$fieldGrp][$fieldName])) {
                      $this->_columns[$tableName][$fieldGrp][$fieldName]['options']
                        = array(
                          '' => ts('Any'),
                          '0' => ts('No'),
                          '1' => ts('Yes'),
                        );
                    }
                    break;

                  default:
                    if ($daoOrBaoName &&
                      array_key_exists('pseudoconstant', $this->_columns[$tableName][$fieldGrp][$fieldName])
                    ) {
                      // with multiple options operator-type is generally multi-select
                      $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
                      if (!array_key_exists('options', $this->_columns[$tableName][$fieldGrp][$fieldName])) {
                        // fill options
                        $this->_columns[$tableName][$fieldGrp][$fieldName]['options'] = CRM_Core_PseudoConstant::get($daoOrBaoName, $fieldName);
                      }
                    }
                    break;
                }
              }
            }
          }
        }
      }

      // copy filters to a separate handy variable
      if (array_key_exists('filters', $table)) {
        $this->_filters[$tableName] = $this->_columns[$tableName]['filters'];
      }

      if (array_key_exists('group_bys', $table)) {
        $groupBys[$tableName] = $this->_columns[$tableName]['group_bys'];
      }

      if (array_key_exists('fields', $table)) {
        $reportFields[$tableName] = $this->_columns[$tableName]['fields'];
      }
    }

    if ($this->_force) {
      $this->setDefaultValues(FALSE);
    }

    CRM_Report_Utils_Get::processFilter($this->_filters, $this->_defaults);
    CRM_Report_Utils_Get::processGroupBy($groupBys, $this->_defaults);
    CRM_Report_Utils_Get::processFields($reportFields, $this->_defaults);
    CRM_Report_Utils_Get::processChart($this->_defaults);

    if ($this->_force) {
      $this->_formValues = $this->_defaults;
      $this->postProcess();
    }
  }

  /**
   * Set default values.
   *
   * @param bool $freeze
   *
   * @return array
   */
  public function setDefaultValues($freeze = TRUE) {
    $freezeGroup = array();

    // FIXME: generalizing form field naming conventions would reduce
    // Lots of lines below.
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (empty($field['no_display'])) {
            if (isset($field['required'])) {
              // set default
              $this->_defaults['fields'][$fieldName] = 1;

              if ($freeze) {
                // find element object, so that we could use quickform's freeze method
                // for required elements
                $obj = $this->getElementFromGroup("fields", $fieldName);
                if ($obj) {
                  $freezeGroup[] = $obj;
                }
              }
            }
            elseif (isset($field['default'])) {
              $this->_defaults['fields'][$fieldName] = $field['default'];
            }
          }
        }
      }

      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (isset($field['default'])) {
            if (!empty($field['frequency'])) {
              $this->_defaults['group_bys_freq'][$fieldName] = 'MONTH';
            }
            $this->_defaults['group_bys'][$fieldName] = $field['default'];
          }
        }
      }
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if (isset($field['default'])) {
            if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE
            ) {
              if (is_array($field['default'])) {
                $this->_defaults["{$fieldName}_from"] = CRM_Utils_Array::value('from', $field['default']);
                $this->_defaults["{$fieldName}_to"] = CRM_Utils_Array::value('to', $field['default']);
                $this->_defaults["{$fieldName}_relative"] = 0;
              }
              else {
                $this->_defaults["{$fieldName}_relative"] = $field['default'];
              }
            }
            else {
              $this->_defaults["{$fieldName}_value"] = $field['default'];
            }
          }
          //assign default value as "in" for multiselect
          //operator, To freeze the select element
          if (CRM_Utils_Array::value('operatorType', $field) ==
            CRM_Report_Form::OP_MULTISELECT
          ) {
            $this->_defaults["{$fieldName}_op"] = 'in';
          }
          if (CRM_Utils_Array::value('operatorType', $field) ==
            CRM_Report_Form::OP_ENTITYREF
          ) {
            $this->_defaults["{$fieldName}_op"] = 'in';
          }
          elseif (CRM_Utils_Array::value('operatorType', $field) ==
            CRM_Report_Form::OP_MULTISELECT_SEPARATOR
          ) {
            $this->_defaults["{$fieldName}_op"] = 'mhas';
          }
          elseif ($op = CRM_Utils_Array::value('default_op', $field)) {
            $this->_defaults["{$fieldName}_op"] = $op;
          }
        }
      }

      if (
        empty($this->_formValues['order_bys']) &&
        (array_key_exists('order_bys', $table) &&
        is_array($table['order_bys']))
      ) {
        if (!array_key_exists('order_bys', $this->_defaults)) {
          $this->_defaults['order_bys'] = array();
        }
        foreach ($table['order_bys'] as $fieldName => $field) {
          if (!empty($field['default']) || !empty($field['default_order']) ||
            CRM_Utils_Array::value('default_is_section', $field) ||
            !empty($field['default_weight'])
          ) {
            $order_by = array(
              'column' => $fieldName,
              'order' => CRM_Utils_Array::value('default_order', $field, 'ASC'),
              'section' => CRM_Utils_Array::value('default_is_section', $field, 0),
            );

            if (!empty($field['default_weight'])) {
              $this->_defaults['order_bys'][(int) $field['default_weight']] = $order_by;
            }
            else {
              array_unshift($this->_defaults['order_bys'], $order_by);
            }
          }
        }
      }

      foreach ($this->_options as $fieldName => $field) {
        if (isset($field['default'])) {
          $this->_defaults['options'][$fieldName] = $field['default'];
        }
      }
    }

    if (!empty($this->_submitValues)) {
      $this->preProcessOrderBy($this->_submitValues);
    }
    else {
      $this->preProcessOrderBy($this->_defaults);
    }

    // lets finish freezing task here itself
    if (!empty($freezeGroup)) {
      foreach ($freezeGroup as $elem) {
        $elem->freeze();
      }
    }

    if ($this->_formValues) {
      $this->_defaults = array_merge($this->_defaults, $this->_formValues);
    }

    if ($this->_instanceValues) {
      $this->_defaults = array_merge($this->_defaults, $this->_instanceValues);
    }

    CRM_Report_Form_Instance::setDefaultValues($this, $this->_defaults);

    return $this->_defaults;
  }

  /**
   * Get element from group.
   *
   * @param string $group
   * @param string $grpFieldName
   *
   * @return bool
   */
  public function getElementFromGroup($group, $grpFieldName) {
    $eleObj = $this->getElement($group);
    foreach ($eleObj->_elements as $index => $obj) {
      if ($grpFieldName == $obj->_attributes['name']) {
        return $obj;
      }
    }
    return FALSE;
  }

  /**
   * Setter for $_params.
   *
   * @param array $params
   */
  public function setParams($params) {
    $this->_params = $params;
  }

  /**
   * Setter for $_id.
   *
   * @param int $instanceID
   */
  public function setID($instanceID) {
    $this->_id = $instanceID;
  }

  /**
   * Setter for $_force.
   *
   * @param $isForce
   */
  public function setForce($isForce) {
    $this->_force = $isForce;
  }

  /**
   * Setter for $_limitValue.
   *
   * @param int $_limitValue
   */
  public function setLimitValue($_limitValue) {
    $this->_limitValue = $_limitValue;
  }

  /**
   * Setter for $_offsetValue.
   *
   * @param int $_offsetValue
   */
  public function setOffsetValue($_offsetValue) {
    $this->_offsetValue = $_offsetValue;
  }

  /**
   * Getter for $_defaultValues.
   *
   * @return array
   */
  public function getDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Add columns to report.
   */
  public function addColumns() {
    $options = array();
    $colGroups = NULL;
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          $groupTitle = '';
          if (empty($field['no_display'])) {
            foreach (array('table', 'field') as $var) {
              if (!empty(${$var}['grouping'])) {
                if (!is_array(${$var}['grouping'])) {
                  $tableName = ${$var}['grouping'];
                }
                else {
                  $tableName = array_keys(${$var}['grouping']);
                  $tableName = $tableName[0];
                  $groupTitle = array_values(${$var}['grouping']);
                  $groupTitle = $groupTitle[0];
                }
              }
            }

            if (!$groupTitle && isset($table['group_title'])) {
              $groupTitle = $table['group_title'];
              // Having a group_title is secret code for being a custom group
              // which cryptically translates to needing an accordian.
              // here we make that explicit.
              $colGroups[$tableName]['use_accordian_for_field_selection'] = TRUE;
            }

            $colGroups[$tableName]['fields'][$fieldName] = CRM_Utils_Array::value('title', $field);
            if ($groupTitle && empty($colGroups[$tableName]['group_title'])) {
              $colGroups[$tableName]['group_title'] = $groupTitle;
            }
            $options[$fieldName] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    $this->addCheckBox("fields", ts('Select Columns'), $options, NULL,
      NULL, NULL, NULL, $this->_fourColumnAttribute, TRUE
    );
    if (!empty($colGroups)) {
      $this->tabs['FieldSelection'] = array(
        'title' => ts('Columns'),
        'tpl' => 'FieldSelection',
        'div_label' => 'col-groups',
      );

      // Note this assignment is only really required in buildForm. It is being 'over-called'
      // to reduce risk of being missed due to overridden functions.
      $this->assign('tabs', $this->tabs);
    }

    $this->assign('colGroups', $colGroups);
  }

  /**
   * Add filters to report.
   */
  public function addFilters() {
    $filters = $filterGroups = array();
    $count = 1;

    foreach ($this->_filters as $table => $attributes) {
      if (isset($this->_columns[$table]['group_title'])) {
        // The presence of 'group_title' is secret code for 'is_a_custom_table'
        // which magically means to 'display in an accordian'
        // here we make this explicit.
        $filterGroups[$table] = array(
          'group_title' => $this->_columns[$table]['group_title'],
          'use_accordian_for_field_selection' => TRUE,

        );
      }
      foreach ($attributes as $fieldName => $field) {
        // get ready with option value pair
        // @ todo being able to specific options for a field (e.g a date field) in the field spec as an array rather than an override
        // would be useful
        $operations = $this->getOperationPair(
          CRM_Utils_Array::value('operatorType', $field),
          $fieldName);

        $filters[$table][$fieldName] = $field;

        switch (CRM_Utils_Array::value('operatorType', $field)) {
          case CRM_Report_Form::OP_MONTH:
            if (!array_key_exists('options', $field) ||
              !is_array($field['options']) || empty($field['options'])
            ) {
              // If there's no option list for this filter, define one.
              $field['options'] = array(
                1 => ts('January'),
                2 => ts('February'),
                3 => ts('March'),
                4 => ts('April'),
                5 => ts('May'),
                6 => ts('June'),
                7 => ts('July'),
                8 => ts('August'),
                9 => ts('September'),
                10 => ts('October'),
                11 => ts('November'),
                12 => ts('December'),
              );
              // Add this option list to this column _columns. This is
              // required so that filter statistics show properly.
              $this->_columns[$table]['filters'][$fieldName]['options'] = $field['options'];
            }
          case CRM_Report_Form::OP_MULTISELECT:
          case CRM_Report_Form::OP_MULTISELECT_SEPARATOR:
            // assume a multi-select field
            if (!empty($field['options']) ||
              $fieldName == 'state_province_id' || $fieldName == 'county_id'
            ) {
              $element = $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations);
              if (count($operations) <= 1) {
                $element->freeze();
              }
              if ($fieldName == 'state_province_id' ||
                $fieldName == 'county_id'
              ) {
                $this->addChainSelect($fieldName . '_value', array(
                  'multiple' => TRUE,
                  'label' => NULL,
                  'class' => 'huge',
                ));
              }
              else {
                $this->addElement('select', "{$fieldName}_value", NULL, $field['options'], array(
                  'style' => 'min-width:250px',
                  'class' => 'crm-select2 huge',
                  'multiple' => TRUE,
                  'placeholder' => ts('- select -'),
                ));
              }
            }
            break;

          case CRM_Report_Form::OP_SELECT:
            // assume a select field
            $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations);
            if (!empty($field['options'])) {
              $this->addElement('select', "{$fieldName}_value", NULL, $field['options']);
            }
            break;

          case CRM_Report_Form::OP_ENTITYREF:
            $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations);
            $this->setEntityRefDefaults($field, $table);
            $this->addEntityRef("{$fieldName}_value", NULL, $field['attributes']);
            break;

          case CRM_Report_Form::OP_DATE:
            // build datetime fields
            CRM_Core_Form_Date::buildDateRange($this, $fieldName, $count, '_from', '_to', 'From:', FALSE, $operations);
            $count++;
            break;

          case CRM_Report_Form::OP_DATETIME:
            // build datetime fields
            CRM_Core_Form_Date::buildDateRange($this, $fieldName, $count, '_from', '_to', 'From:', FALSE, $operations, 'searchDate', TRUE);
            $count++;
            break;

          case CRM_Report_Form::OP_INT:
          case CRM_Report_Form::OP_FLOAT:
            // and a min value input box
            $this->add('text', "{$fieldName}_min", ts('Min'));
            // and a max value input box
            $this->add('text', "{$fieldName}_max", ts('Max'));
          default:
            // default type is string
            $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations,
              array('onchange' => "return showHideMaxMinVal( '$fieldName', this.value );")
            );
            // we need text box for value input
            $this->add('text', "{$fieldName}_value", NULL, array('class' => 'huge'));
            break;
        }
      }
    }
    if (!empty($filters)) {
      $this->tabs['Filters'] = array(
        'title' => ts('Filters'),
        'tpl' => 'Filters',
        'div_label' => 'set-filters',
      );
    }
    $this->assign('filters', $filters);
    $this->assign('filterGroups', $filterGroups);
  }

  /**
   * Function to assign the tabs to the template in the correct order.
   *
   * We want the tabs to wind up in this order (if not overridden).
   *
   *   - Field Selection
   *   - Group Bys
   *   - Order Bys
   *   - Other Options
   *   - Filters
   */
  protected function assignTabs() {
    $order = array(
      'FieldSelection',
      'GroupBy',
      'OrderBy',
      'ReportOptions',
      'Filters',
    );
    $order = array_intersect_key(array_fill_keys($order, 1), $this->tabs);
    $order = array_merge($order, $this->tabs);
    $this->assign('tabs', $order);
  }

  /**
   * Add options defined in $this->_options to the report.
   */
  public function addOptions() {
    if (!empty($this->_options)) {
      // FIXME: For now lets build all elements as checkboxes.
      // Once we clear with the format we can build elements based on type

      foreach ($this->_options as $fieldName => $field) {
        $options = array();

        if ($field['type'] == 'select') {
          $this->addElement('select', "{$fieldName}", $field['title'], $field['options']);
        }
        elseif ($field['type'] == 'checkbox') {
          $options[$field['title']] = $fieldName;
          $this->addCheckBox($fieldName, NULL,
            $options, NULL,
            NULL, NULL, NULL, $this->_fourColumnAttribute
          );
        }
      }
    }
    if (!empty($this->_options)) {
      $this->tabs['ReportOptions'] = array(
        'title' => ts('Display Options'),
        'tpl' => 'ReportOptions',
        'div_label' => 'other-options',
      );
    }
    $this->assign('otherOptions', $this->_options);
  }

  /**
   * Add chart options to the report.
   */
  public function addChartOptions() {
    if (!empty($this->_charts)) {
      $this->addElement('select', "charts", ts('Chart'), $this->_charts);
      $this->assign('charts', $this->_charts);
      $this->addElement('submit', $this->_chartButtonName, ts('View'));
    }
  }

  /**
   * Add group by options to the report.
   */
  public function addGroupBys() {
    $options = $freqElements = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($field)) {
            $options[$field['title']] = $fieldName;
            if (!empty($field['frequency'])) {
              $freqElements[$field['title']] = $fieldName;
            }
          }
        }
      }
    }
    $this->addCheckBox("group_bys", ts('Group by columns'), $options, NULL,
      NULL, NULL, NULL, $this->_fourColumnAttribute
    );
    $this->assign('groupByElements', $options);
    if (!empty($options)) {
      $this->tabs['GroupBy'] = array(
        'title' => ts('Grouping'),
        'tpl' => 'GroupBy',
        'div_label' => 'group-by-elements',
      );
    }

    foreach ($freqElements as $name) {
      $this->addElement('select', "group_bys_freq[$name]",
        ts('Frequency'), $this->_groupByDateFreq
      );
    }
  }

  /**
   * Add data for order by tab.
   */
  public function addOrderBys() {
    $options = array();
    foreach ($this->_columns as $tableName => $table) {

      // Report developer may define any column to order by; include these as order-by options.
      if (array_key_exists('order_bys', $table)) {
        foreach ($table['order_bys'] as $fieldName => $field) {
          if (!empty($field)) {
            $options[$fieldName] = $field['title'];
          }
        }
      }

      // Add searchable custom fields as order-by options, if so requested
      // (These are already indexed, so allowing to order on them is cheap.)

      if ($this->_autoIncludeIndexedFieldsAsOrderBys &&
        array_key_exists('extends', $table) && !empty($table['extends'])
      ) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (empty($field['no_display'])) {
            $options[$fieldName] = $field['title'];
          }
        }
      }
    }

    asort($options);

    $this->assign('orderByOptions', $options);
    if (!empty($options)) {
      $this->tabs['OrderBy'] = array(
        'title' => ts('Sorting'),
        'tpl' => 'OrderBy',
        'div_label' => 'order-by-elements',
      );
    }

    if (!empty($options)) {
      $options = array(
        '-' => ' - none - ',
      ) + $options;
      for ($i = 1; $i <= 5; $i++) {
        $this->addElement('select', "order_bys[{$i}][column]", ts('Order by Column'), $options);
        $this->addElement('select', "order_bys[{$i}][order]", ts('Order by Order'), array(
          'ASC' => 'Ascending',
          'DESC' => 'Descending',
        ));
        $this->addElement('checkbox', "order_bys[{$i}][section]", ts('Order by Section'), FALSE, array('id' => "order_by_section_$i"));
        $this->addElement('checkbox', "order_bys[{$i}][pageBreak]", ts('Page Break'), FALSE, array('id' => "order_by_pagebreak_$i"));
      }
    }
  }

  /**
   * This adds the tab referred to as Title and Format, rendered through Instance.tpl.
   *
   * @todo call this tab into the report template in the same way as OrderBy etc, ie
   * by adding a description of the tab to $this->tabs, causing the tab to be added in
   * Criteria.tpl.
   */
  public function buildInstanceAndButtons() {
    CRM_Report_Form_Instance::buildForm($this);

    $label = $this->_id ? ts('Update Report') : ts('Create Report');

    $this->addElement('submit', $this->_instanceButtonName, $label);
    $this->addElement('submit', $this->_printButtonName, ts('Print Report'));
    $this->addElement('submit', $this->_pdfButtonName, ts('PDF'));

    if ($this->_id) {
      $this->addElement('submit', $this->_createNewButtonName,
        ts('Save a Copy') . '...');
    }
    $this->assign('instanceForm', $this->_instanceForm);

    $label = $this->_id ? ts('Print Report') : ts('Print Preview');
    $this->addElement('submit', $this->_printButtonName, $label);

    $label = $this->_id ? ts('PDF') : ts('Preview PDF');
    $this->addElement('submit', $this->_pdfButtonName, $label);

    $label = $this->_id ? ts('Export to CSV') : ts('Preview CSV');

    if ($this->_csvSupported) {
      $this->addElement('submit', $this->_csvButtonName, $label);
    }

    if (CRM_Core_Permission::check('administer Reports') &&
      $this->_add2groupSupported
    ) {
      $this->addElement('select', 'groups', ts('Group'),
        array('' => ts('Add Contacts to Group')) +
        CRM_Core_PseudoConstant::nestedGroup(),
        array('class' => 'crm-select2 crm-action-menu action-icon-plus huge')
      );
      $this->assign('group', TRUE);
    }

    $this->addElement('submit', $this->_groupButtonName, '', array('style' => 'display: none;'));

    $this->addChartOptions();
    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Preview Report'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Main build form function.
   */
  public function buildQuickForm() {
    $this->addColumns();

    $this->addFilters();

    $this->addOptions();

    $this->addGroupBys();

    $this->addOrderBys();

    $this->buildInstanceAndButtons();

    // Add form rule for report.
    if (is_callable(array(
      $this,
      'formRule',
    ))) {
      $this->addFormRule(array(get_class($this), 'formRule'), $this);
    }
    $this->assignTabs();
  }

  /**
   * A form rule function to ensure that fields selected in group_by
   * (if any) should only be the ones present in display/select fields criteria;
   * note: works if and only if any custom field selected in group_by.
   *
   * @param array $fields
   * @param array $ignoreFields
   *
   * @return array
   */
  public function customDataFormRule($fields, $ignoreFields = array()) {
    $errors = array();
    if (!empty($this->_customGroupExtends) && $this->_customGroupGroupBy &&
      !empty($fields['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if ((substr($tableName, 0, 13) == 'civicrm_value' ||
            substr($tableName, 0, 12) == 'custom_value') &&
          !empty($this->_columns[$tableName]['fields'])
        ) {
          foreach ($this->_columns[$tableName]['fields'] as $fieldName => $field) {
            if (array_key_exists($fieldName, $fields['group_bys']) &&
              !array_key_exists($fieldName, $fields['fields'])
            ) {
              $errors['fields'] = "Please make sure fields selected in 'Group by Columns' section are also selected in 'Display Columns' section.";
            }
            elseif (array_key_exists($fieldName, $fields['group_bys'])) {
              foreach ($fields['fields'] as $fld => $val) {
                if (!array_key_exists($fld, $fields['group_bys']) &&
                  !in_array($fld, $ignoreFields)
                ) {
                  $errors['fields'] = "Please ensure that fields selected in 'Display Columns' are also selected in 'Group by Columns' section.";
                }
              }
            }
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Get operators to display on form.
   *
   * Note: $fieldName param allows inheriting class to build operationPairs specific to a field.
   *
   * @param string $type
   * @param string $fieldName
   *
   * @return array
   */
  public function getOperationPair($type = "string", $fieldName = NULL) {
    // FIXME: At some point we should move these key-val pairs
    // to option_group and option_value table.
    switch ($type) {
      case CRM_Report_Form::OP_INT:
      case CRM_Report_Form::OP_FLOAT:

        $result = array(
          'lte' => ts('Is less than or equal to'),
          'gte' => ts('Is greater than or equal to'),
          'bw' => ts('Is between'),
          'eq' => ts('Is equal to'),
          'lt' => ts('Is less than'),
          'gt' => ts('Is greater than'),
          'neq' => ts('Is not equal to'),
          'nbw' => ts('Is not between'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        );
        return $result;

      case CRM_Report_Form::OP_SELECT:
        $result = array(
          'eq' => ts('Is equal to'),
        );
        return $result;

      case CRM_Report_Form::OP_MONTH:
      case CRM_Report_Form::OP_MULTISELECT:
      case CRM_Report_Form::OP_ENTITYREF:

        $result = array(
          'in' => ts('Is one of'),
          'notin' => ts('Is not one of'),
        );
        return $result;

      case CRM_Report_Form::OP_DATE:

        $result = array(
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        );
        return $result;

      case CRM_Report_Form::OP_MULTISELECT_SEPARATOR:
        // use this operator for the values, concatenated with separator. For e.g if
        // multiple options for a column is stored as ^A{val1}^A{val2}^A
        $result = array(
          'mhas' => ts('Is one of'),
          'mnot' => ts('Is not one of'),
        );
        return $result;

      default:
        // type is string
        $result = array(
          'has' => ts('Contains'),
          'sw' => ts('Starts with'),
          'ew' => ts('Ends with'),
          'nhas' => ts('Does not contain'),
          'eq' => ts('Is equal to'),
          'neq' => ts('Is not equal to'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        );
        return $result;
    }
  }

  /**
   * Build the tag filter field to display on the filters tab.
   */
  public function buildTagFilter() {
    $contactTags = CRM_Core_BAO_Tag::getTags($this->_tagFilterTable);
    if (!empty($contactTags)) {
      $this->_columns['civicrm_tag'] = array(
        'dao' => 'CRM_Core_DAO_Tag',
        'filters' => array(
          'tagid' => array(
            'name' => 'tag_id',
            'title' => ts('Tag'),
            'tag' => TRUE,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contactTags,
          ),
        ),
      );
    }
  }

  /**
   * Adds group filters to _columns (called from _Construct).
   */
  public function buildGroupFilter() {
    $this->_columns['civicrm_group']['filters'] = array(
      'gid' => array(
        'name' => 'group_id',
        'title' => ts('Group'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'group' => TRUE,
        'options' => CRM_Core_PseudoConstant::nestedGroup(),
      ),
    );
    if (empty($this->_columns['civicrm_group']['dao'])) {
      $this->_columns['civicrm_group']['dao'] = 'CRM_Contact_DAO_GroupContact';
    }
    if (empty($this->_columns['civicrm_group']['alias'])) {
      $this->_columns['civicrm_group']['alias'] = 'cgroup';
    }
  }

  /**
   * Get SQL operator from form text version.
   *
   * @param string $operator
   *
   * @return string
   */
  public function getSQLOperator($operator = "like") {
    switch ($operator) {
      case 'eq':
        return '=';

      case 'lt':
        return '<';

      case 'lte':
        return '<=';

      case 'gt':
        return '>';

      case 'gte':
        return '>=';

      case 'ne':
      case 'neq':
        return '!=';

      case 'nhas':
        return 'NOT LIKE';

      case 'in':
        return 'IN';

      case 'notin':
        return 'NOT IN';

      case 'nll':
        return 'IS NULL';

      case 'nnll':
        return 'IS NOT NULL';

      default:
        // type is string
        return 'LIKE';
    }
  }

  /**
   * Generate where clause.
   *
   * This can be overridden in reports for special treatment of a field
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {

    $type = CRM_Utils_Type::typeToString(CRM_Utils_Array::value('type', $field));
    $clause = NULL;

    switch ($op) {
      case 'bw':
      case 'nbw':
        if (($min !== NULL && strlen($min) > 0) ||
          ($max !== NULL && strlen($max) > 0)
        ) {
          $min = CRM_Utils_Type::escape($min, $type);
          $max = CRM_Utils_Type::escape($max, $type);
          $clauses = array();
          if ($min) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} >= $min )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} < $min )";
            }
          }
          if ($max) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} <= $max )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} > $max )";
            }
          }

          if (!empty($clauses)) {
            if ($op == 'bw') {
              $clause = implode(' AND ', $clauses);
            }
            else {
              $clause = implode(' OR ', $clauses);
            }
          }
        }
        break;

      case 'has':
      case 'nhas':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            $value = "'%{$value}%'";
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP $value )";
        }
        break;

      case 'in':
      case 'notin':
        if (is_string($value) && strlen($value)) {
          $value = explode(',', $value);
        }
        if ($value !== NULL && is_array($value) && count($value) > 0) {
          $sqlOP = $this->getSQLOperator($op);
          if (CRM_Utils_Array::value('type', $field) ==
            CRM_Utils_Type::T_STRING
          ) {
            //cycle through selections and escape values
            foreach ($value as $key => $selection) {
              $value[$key] = CRM_Utils_Type::escape($selection, $type);
            }
            $clause
              = "( {$field['dbAlias']} $sqlOP ( '" . implode("' , '", $value) .
              "') )";
          }
          else {
            // for numerical values
            $clause = "{$field['dbAlias']} $sqlOP (" . implode(', ', $value) .
              ")";
          }
          if ($op == 'notin') {
            $clause = "( " . $clause . " OR {$field['dbAlias']} IS NULL )";
          }
          else {
            $clause = "( " . $clause . " )";
          }
        }
        break;

      case 'mhas':
        // mhas == multiple has
        if ($value !== NULL && count($value) > 0) {
          $sqlOP = $this->getSQLOperator($op);
          $clause
            = "{$field['dbAlias']} REGEXP '[[:cntrl:]]" . implode('|', $value) .
            "[[:cntrl:]]'";
        }
        break;

      case 'mnot':
        // mnot == multiple is not one of
        if ($value !== NULL && count($value) > 0) {
          $sqlOP = $this->getSQLOperator($op);
          $clause
            = "( {$field['dbAlias']} NOT REGEXP '[[:cntrl:]]" . implode('|', $value) .
            "[[:cntrl:]]' OR {$field['dbAlias']} IS NULL )";
        }
        break;

      case 'sw':
      case 'ew':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            if ($op == 'sw') {
              $value = "'{$value}%'";
            }
            else {
              $value = "'%{$value}'";
            }
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP $value )";
        }
        break;

      case 'nll':
      case 'nnll':
        $sqlOP = $this->getSQLOperator($op);
        $clause = "( {$field['dbAlias']} $sqlOP )";
        break;

      default:
        if ($value !== NULL && strlen($value) > 0) {
          if (isset($field['clause'])) {
            // FIXME: we not doing escape here. Better solution is to use two
            // different types - data-type and filter-type
            $clause = $field['clause'];
          }
          else {
            $value = CRM_Utils_Type::escape($value, $type);
            $sqlOP = $this->getSQLOperator($op);
            if ($field['type'] == CRM_Utils_Type::T_STRING) {
              $value = "'{$value}'";
            }
            $clause = "( {$field['dbAlias']} $sqlOP $value )";
          }
        }
        break;
    }

    if (!empty($field['group']) && $clause) {
      $clause = $this->whereGroupClause($field, $value, $op);
    }
    elseif (!empty($field['tag']) && $clause) {
      // not using left join in query because if any contact
      // belongs to more than one tag, results duplicate
      // entries.
      $clause = $this->whereTagClause($field, $value, $op);
    }
    elseif (!empty($field['membership_org']) && $clause) {
      $clause = $this->whereMembershipOrgClause($value, $op);
    }
    elseif (!empty($field['membership_type']) && $clause) {
      $clause = $this->whereMembershipTypeClause($value, $op);
    }
    return $clause;
  }

  /**
   * Get SQL where clause for a date field.
   *
   * @param string $fieldName
   * @param $relative
   * @param string $from
   * @param string $to
   * @param string $type
   * @param string $fromTime
   * @param string $toTime
   *
   * @return null|string
   */
  public function dateClause(
    $fieldName,
    $relative, $from, $to, $type = NULL, $fromTime = NULL, $toTime = NULL
  ) {
    $clauses = array();
    if (in_array($relative, array_keys($this->getOperationPair(CRM_Report_Form::OP_DATE)))) {
      $sqlOP = $this->getSQLOperator($relative);
      return "( {$fieldName} {$sqlOP} )";
    }

    list($from, $to) = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);

    if ($from) {
      $from = ($type == CRM_Utils_Type::T_DATE) ? substr($from, 0, 8) : $from;
      $clauses[] = "( {$fieldName} >= $from )";
    }

    if ($to) {
      $to = ($type == CRM_Utils_Type::T_DATE) ? substr($to, 0, 8) : $to;
      $clauses[] = "( {$fieldName} <= {$to} )";
    }

    if (!empty($clauses)) {
      return implode(' AND ', $clauses);
    }

    return NULL;
  }

  /**
   * Possibly unused function.
   *
   * @todo - could not find any instances where this is called
   *
   * @param bool $relative
   * @param string $from
   * @param string $to
   *
   * @return string|NULL
   */
  public function dateDisplay($relative, $from, $to) {
    list($from, $to) = $this->getFromTo($relative, $from, $to);

    if ($from) {
      $clauses[] = CRM_Utils_Date::customFormat($from, NULL, array('m', 'M'));
    }
    else {
      $clauses[] = 'Past';
    }

    if ($to) {
      $clauses[] = CRM_Utils_Date::customFormat($to, NULL, array('m', 'M'));
    }
    else {
      $clauses[] = 'Today';
    }

    if (!empty($clauses)) {
      return implode(' - ', $clauses);
    }

    return NULL;
  }

  /**
   * Get values for from and to for date ranges.
   *
   * @param bool $relative
   * @param string $from
   * @param string $to
   * @param string $fromTime
   * @param string $toTime
   *
   * @return array
   */
  public function getFromTo($relative, $from, $to, $fromTime = NULL, $toTime = NULL) {
    if (empty($toTime)) {
      $toTime = '235959';
    }
    //FIX ME not working for relative
    if ($relative) {
      list($term, $unit) = CRM_Utils_System::explode('.', $relative, 2);
      $dateRange = CRM_Utils_Date::relativeToAbsolute($term, $unit);
      $from = substr($dateRange['from'], 0, 8);
      //Take only Date Part, Sometime Time part is also present in 'to'
      $to = substr($dateRange['to'], 0, 8);
    }
    $from = CRM_Utils_Date::processDate($from, $fromTime);
    $to = CRM_Utils_Date::processDate($to, $toTime);
    return array($from, $to);
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
  }

  /**
   * Alter the way in which custom data fields are displayed.
   *
   * @param array $rows
   */
  public function alterCustomDataDisplay(&$rows) {
    // custom code to alter rows having custom values
    if (empty($this->_customGroupExtends)) {
      return;
    }

    $customFieldIds = array();
    foreach ($this->_params['fields'] as $fieldAlias => $value) {
      if ($fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
        $customFieldIds[$fieldAlias] = $fieldId;
      }
    }
    if (empty($customFieldIds)) {
      return;
    }

    $customFields = $fieldValueMap = array();
    $customFieldCols = array(
      'column_name',
      'data_type',
      'html_type',
      'option_group_id',
      'id',
    );

    // skip for type date and ContactReference since date format is already handled
    $query = "
SELECT cg.table_name, cf." . implode(", cf.", $customFieldCols) . ", ov.value, ov.label
FROM  civicrm_custom_field cf
INNER JOIN civicrm_custom_group cg ON cg.id = cf.custom_group_id
LEFT JOIN civicrm_option_value ov ON cf.option_group_id = ov.option_group_id
WHERE cg.extends IN ('" . implode("','", $this->_customGroupExtends) . "') AND
      cg.is_active = 1 AND
      cf.is_active = 1 AND
      cf.is_searchable = 1 AND
      cf.data_type   NOT IN ('ContactReference', 'Date') AND
      cf.id IN (" . implode(",", $customFieldIds) . ")";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      foreach ($customFieldCols as $key) {
        $customFields[$dao->table_name . '_custom_' .
        $dao->id][$key] = $dao->$key;
      }
      if ($dao->option_group_id) {
        $fieldValueMap[$dao->option_group_id][$dao->value] = $dao->label;
      }
    }
    $dao->free();

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      foreach ($row as $tableCol => $val) {
        if (array_key_exists($tableCol, $customFields)) {
          $rows[$rowNum][$tableCol] = $this->formatCustomValues($val, $customFields[$tableCol], $fieldValueMap);
          $entryFound = TRUE;
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param $value
   * @param $customField
   * @param $fieldValueMap
   *
   * @return float|string|void
   */
  public function formatCustomValues($value, $customField, $fieldValueMap) {
    if (CRM_Utils_System::isNull($value)) {
      return NULL;
    }

    $htmlType = $customField['html_type'];

    switch ($customField['data_type']) {
      case 'Boolean':
        if ($value == '1') {
          $retValue = ts('Yes');
        }
        else {
          $retValue = ts('No');
        }
        break;

      case 'Link':
        $retValue = CRM_Utils_System::formatWikiURL($value);
        break;

      case 'File':
        $retValue = $value;
        break;

      case 'Memo':
        $retValue = $value;
        break;

      case 'Float':
        if ($htmlType == 'Text') {
          $retValue = (float) $value;
          break;
        }
      case 'Money':
        if ($htmlType == 'Text') {
          $retValue = CRM_Utils_Money::format($value, NULL, '%a');
          break;
        }
      case 'String':
      case 'Int':
        if (in_array($htmlType, array(
          'Text',
          'TextArea',
        ))) {
          $retValue = $value;
          break;
        }
      case 'StateProvince':
      case 'Country':

        switch ($htmlType) {
          case 'Multi-Select Country':
            $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            $customData = array();
            foreach ($value as $val) {
              if ($val) {
                $customData[] = CRM_Core_PseudoConstant::country($val, FALSE);
              }
            }
            $retValue = implode(', ', $customData);
            break;

          case 'Select Country':
            $retValue = CRM_Core_PseudoConstant::country($value, FALSE);
            break;

          case 'Select State/Province':
            $retValue = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
            break;

          case 'Multi-Select State/Province':
            $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            $customData = array();
            foreach ($value as $val) {
              if ($val) {
                $customData[] = CRM_Core_PseudoConstant::stateProvince($val, FALSE);
              }
            }
            $retValue = implode(', ', $customData);
            break;

          case 'Select':
          case 'Radio':
          case 'Autocomplete-Select':
            $retValue = $fieldValueMap[$customField['option_group_id']][$value];
            break;

          case 'CheckBox':
          case 'AdvMulti-Select':
          case 'Multi-Select':
            $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            $customData = array();
            foreach ($value as $val) {
              if ($val) {
                $customData[] = $fieldValueMap[$customField['option_group_id']][$val];
              }
            }
            $retValue = implode(', ', $customData);
            break;

          default:
            $retValue = $value;
        }
        break;

      default:
        $retValue = $value;
    }

    return $retValue;
  }

  /**
   * @param $rows
   */
  public function removeDuplicates(&$rows) {
    if (empty($this->_noRepeats)) {
      return;
    }
    $checkList = array();

    foreach ($rows as $key => $list) {
      foreach ($list as $colName => $colVal) {
        if (array_key_exists($colName, $checkList) &&
          $checkList[$colName] == $colVal
        ) {
          $rows[$key][$colName] = "";
        }
        if (in_array($colName, $this->_noRepeats)) {
          $checkList[$colName] = $colVal;
        }
      }
    }
  }

  /**
   * @param $row
   * @param $fields
   * @param bool $subtotal
   */
  public function fixSubTotalDisplay(&$row, $fields, $subtotal = TRUE) {
    foreach ($row as $colName => $colVal) {
      if (in_array($colName, $fields)) {
      }
      elseif (isset($this->_columnHeaders[$colName])) {
        if ($subtotal) {
          $row[$colName] = "Subtotal";
          $subtotal = FALSE;
        }
        else {
          unset($row[$colName]);
        }
      }
    }
  }

  /**
   * @param $rows
   *
   * @return bool
   */
  public function grandTotal(&$rows) {
    if (!$this->_rollup || ($this->_rollup == '') ||
      ($this->_limit && count($rows) >= self::ROW_COUNT_LIMIT)
    ) {
      return FALSE;
    }
    $lastRow = array_pop($rows);

    foreach ($this->_columnHeaders as $fld => $val) {
      if (!in_array($fld, $this->_statFields)) {
        if (!$this->_grandFlag) {
          $lastRow[$fld] = "Grand Total";
          $this->_grandFlag = TRUE;
        }
        else {
          $lastRow[$fld] = "";
        }
      }
    }

    $this->assign('grandStat', $lastRow);
    return TRUE;
  }

  /**
   * @param $rows
   * @param bool $pager
   */
  public function formatDisplay(&$rows, $pager = TRUE) {
    // set pager based on if any limit was applied in the query.
    if ($pager) {
      $this->setPager();
    }

    // allow building charts if any
    if (!empty($this->_params['charts']) && !empty($rows)) {
      $this->buildChart($rows);
      $this->assign('chartEnabled', TRUE);
      $this->_chartId = "{$this->_params['charts']}_" .
        ($this->_id ? $this->_id : substr(get_class($this), 16)) . '_' .
        session_id();
      $this->assign('chartId', $this->_chartId);
    }

    // unset columns not to be displayed.
    foreach ($this->_columnHeaders as $key => $value) {
      if (!empty($value['no_display'])) {
        unset($this->_columnHeaders[$key]);
      }
    }

    // unset columns not to be displayed.
    if (!empty($rows)) {
      foreach ($this->_noDisplay as $noDisplayField) {
        foreach ($rows as $rowNum => $row) {
          unset($this->_columnHeaders[$noDisplayField]);
        }
      }
    }

    // build array of section totals
    $this->sectionTotals();

    // process grand-total row
    $this->grandTotal($rows);

    // use this method for formatting rows for display purpose.
    $this->alterDisplay($rows);
    CRM_Utils_Hook::alterReportVar('rows', $rows, $this);

    // use this method for formatting custom rows for display purpose.
    $this->alterCustomDataDisplay($rows);
  }

  /**
   * @param $rows
   */
  public function buildChart(&$rows) {
    // override this method for building charts.
  }

  // select() method below has been added recently (v3.3), and many of the report templates might
  // still be having their own select() method. We should fix them as and when encountered and move
  // towards generalizing the select() method below.

  /**
   * Generate the SELECT clause and set class variable $_select
   */
  public function select() {
    $select = $this->_selectAliases = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if ($tableName == 'civicrm_email') {
            $this->_emailField = TRUE;
          }
          if ($tableName == 'civicrm_phone') {
            $this->_phoneField = TRUE;
          }

          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            // 1. In many cases we want select clause to be built in slightly different way
            //    for a particular field of a particular type.
            // 2. This method when used should receive params by reference and modify $this->_columnHeaders
            //    as needed.
            $selectClause = $this->selectClause($tableName, 'fields', $fieldName, $field);
            if ($selectClause) {
              $select[] = $selectClause;
              continue;
            }

            // include statistics columns only if set
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                $alias = "{$tableName}_{$fieldName}_{$stat}";
                switch (strtolower($stat)) {
                  case 'max':
                  case 'sum':
                    $select[] = "$stat({$field['dbAlias']}) as $alias";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[$label] = $alias;
                    $this->_selectAliases[] = $alias;
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as $alias";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_statFields[$label] = $alias;
                    $this->_selectAliases[] = $alias;
                    break;

                  case 'count_distinct':
                    $select[] = "COUNT(DISTINCT {$field['dbAlias']}) as $alias";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_statFields[$label] = $alias;
                    $this->_selectAliases[] = $alias;
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as $alias";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[$label] = $alias;
                    $this->_selectAliases[] = $alias;
                    break;
                }
              }
            }
            else {
              $alias = "{$tableName}_{$fieldName}";
              $select[] = "{$field['dbAlias']} as $alias";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_selectAliases[] = $alias;
            }
          }
        }
      }

      // select for group bys
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {

          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if ($tableName == 'civicrm_email') {
            $this->_emailField = TRUE;
          }
          if ($tableName == 'civicrm_phone') {
            $this->_phoneField = TRUE;
          }
          // 1. In many cases we want select clause to be built in slightly different way
          //    for a particular field of a particular type.
          // 2. This method when used should receive params by reference and modify $this->_columnHeaders
          //    as needed.
          $selectClause = $this->selectClause($tableName, 'group_bys', $fieldName, $field);
          if ($selectClause) {
            $select[] = $selectClause;
            continue;
          }

          if (!empty($this->_params['group_bys']) &&
            !empty($this->_params['group_bys'][$fieldName]) &&
            !empty($this->_params['group_bys_freq'])
          ) {
            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            // for graphs and charts -
            if (!empty($this->_params['group_bys_freq'][$fieldName])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title']
                = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transferred to rows.
              // since we 'll need them for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = array('no_display' => TRUE);
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = array('no_display' => TRUE);
            }
          }
        }
      }
    }

    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param string $tableName
   * @param $tableKey
   * @param string $fieldName
   * @param $field
   *
   * @return bool
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    return FALSE;
  }

  public function where() {
    $this->storeWhereHavingClauseArray();

    if (empty($this->_whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }

  /**
   * Store Where clauses into an array - breaking out this step makes
   * over-riding more flexible as the clauses can be used in constructing a
   * temp table that may not be part of the final where clause or added
   * in other functions
   */
  public function storeWhereHavingClauseArray() {
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          // respect pseudofield to filter spec so fields can be marked as
          // not to be handled here
          if (!empty($field['pseudofield'])) {
            continue;
          }
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            if (CRM_Utils_Array::value('operatorType', $field) ==
              CRM_Report_Form::OP_MONTH
            ) {
              $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (is_array($value) && !empty($value)) {
                $clause
                  = "(month({$field['dbAlias']}) $op (" . implode(', ', $value) .
                  '))';
              }
            }
            else {
              $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
              $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
              $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
              $fromTime = CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params);
              $toTime = CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params);
              $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type'], $fromTime, $toTime);
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            if (!empty($field['having'])) {
              $this->_havingClauses[] = $clause;
            }
            else {
              $this->_whereClauses[] = $clause;
            }
          }
        }
      }
    }

  }

  public function processReportMode() {
    $buttonName = $this->controller->getButtonName();

    $output = CRM_Utils_Request::retrieve(
      'output',
      'String',
      CRM_Core_DAO::$_nullObject
    );

    $this->_sendmail
      = CRM_Utils_Request::retrieve(
        'sendmail',
        'Boolean',
        CRM_Core_DAO::$_nullObject
      );

    $this->_absoluteUrl = FALSE;
    $printOnly = FALSE;
    $this->assign('printOnly', FALSE);

    if ($this->_printButtonName == $buttonName || $output == 'print' ||
      ($this->_sendmail && !$output)
    ) {
      $this->assign('printOnly', TRUE);
      $printOnly = TRUE;
      $this->assign('outputMode', 'print');
      $this->_outputMode = 'print';
      if ($this->_sendmail) {
        $this->_absoluteUrl = TRUE;
      }
    }
    elseif ($this->_pdfButtonName == $buttonName || $output == 'pdf') {
      $this->assign('printOnly', TRUE);
      $printOnly = TRUE;
      $this->assign('outputMode', 'pdf');
      $this->_outputMode = 'pdf';
      $this->_absoluteUrl = TRUE;
    }
    elseif ($this->_csvButtonName == $buttonName || $output == 'csv') {
      $this->assign('printOnly', TRUE);
      $printOnly = TRUE;
      $this->assign('outputMode', 'csv');
      $this->_outputMode = 'csv';
      $this->_absoluteUrl = TRUE;
    }
    elseif ($this->_groupButtonName == $buttonName || $output == 'group') {
      $this->assign('outputMode', 'group');
      $this->_outputMode = 'group';
    }
    elseif ($output == 'create_report' && $this->_criteriaForm) {
      $this->assign('outputMode', 'create_report');
      $this->_outputMode = 'create_report';
    }
    else {
      $this->assign('outputMode', 'html');
      $this->_outputMode = 'html';
    }

    // Get today's date to include in printed reports
    if ($printOnly) {
      $reportDate = CRM_Utils_Date::customFormat(date('Y-m-d H:i'));
      $this->assign('reportDate', $reportDate);
    }
  }

  /**
   * Post Processing function for Form (postProcessCommon should be used to set other variables from input as the api accesses that function)
   */
  public function beginPostProcess() {
    $this->setParams($this->controller->exportValues($this->_name));

    if (empty($this->_params) &&
      $this->_force
    ) {
      $this->setParams($this->_formValues);
    }

    // hack to fix params when submitted from dashboard, CRM-8532
    // fields array is missing because form building etc is skipped
    // in dashboard mode for report
    //@todo - this could be done in the dashboard no we have a setter
    if (empty($this->_params['fields']) && !$this->_noFields) {
      $this->setParams($this->_formValues);
    }

    $this->_formValues = $this->_params;
    if (CRM_Core_Permission::check('administer Reports') &&
      isset($this->_id) &&
      ($this->_instanceButtonName ==
        $this->controller->getButtonName() . '_save' ||
        $this->_chartButtonName == $this->controller->getButtonName()
      )
    ) {
      $this->assign('updateReportButton', TRUE);
    }
    $this->processReportMode();
    $this->beginPostProcessCommon();
  }

  /**
   * BeginPostProcess function run in both report mode and non-report mode (api)
   */
  public function beginPostProcessCommon() {

  }

  /**
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    // order_by columns not selected for display need to be included in SELECT
    $unselectedSectionColumns = $this->unselectedSectionColumns();
    foreach ($unselectedSectionColumns as $alias => $section) {
      $this->_select .= ", {$section['dbAlias']} as {$alias}";
    }

    if ($applyLimit && empty($this->_params['charts'])) {
      $this->limit();
    }
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    return $sql;
  }

  public function groupBy() {
    $groupBys = array();
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              $groupBys[] = $field['dbAlias'];
            }
          }
        }
      }
    }

    if (!empty($groupBys)) {
      $this->_groupBy = "GROUP BY " . implode(', ', $groupBys);
    }
  }

  public function orderBy() {
    $this->_orderBy = "";
    $this->_sections = array();
    $this->storeOrderByArray();
    if (!empty($this->_orderByArray) && !$this->_rollup == 'WITH ROLLUP') {
      $this->_orderBy = "ORDER BY " . implode(', ', $this->_orderByArray);
    }
    $this->assign('sections', $this->_sections);
  }

  /**
   * In some cases other functions want to know which fields are selected for ordering by
   * Separating this into a separate function allows it to be called separately from constructing
   * the order by clause
   */
  public function storeOrderByArray() {
    $orderBys = array();

    if (!empty($this->_params['order_bys']) &&
      is_array($this->_params['order_bys']) &&
      !empty($this->_params['order_bys'])
    ) {

      // Process order_bys in user-specified order
      foreach ($this->_params['order_bys'] as $orderBy) {
        $orderByField = array();
        foreach ($this->_columns as $tableName => $table) {
          if (array_key_exists('order_bys', $table)) {
            // For DAO columns defined in $this->_columns
            $fields = $table['order_bys'];
          }
          elseif (array_key_exists('extends', $table)) {
            // For custom fields referenced in $this->_customGroupExtends
            $fields = CRM_Utils_Array::value('fields', $table, array());
          }
          else {
            continue;
          }
          if (!empty($fields) && is_array($fields)) {
            foreach ($fields as $fieldName => $field) {
              if ($fieldName == $orderBy['column']) {
                $orderByField = array_merge($field, $orderBy);
                $orderByField['tplField'] = "{$tableName}_{$fieldName}";
                break 2;
              }
            }
          }
        }

        if (!empty($orderByField)) {
          $this->_orderByFields[] = $orderByField;
          $orderBys[] = "{$orderByField['dbAlias']} {$orderBy['order']}";

          // Record any section headers for assignment to the template
          if (!empty($orderBy['section'])) {
            $orderByField['pageBreak'] = CRM_Utils_Array::value('pageBreak', $orderBy);
            $this->_sections[$orderByField['tplField']] = $orderByField;
          }
        }
      }
    }

    $this->_orderByArray = $orderBys;

    $this->assign('sections', $this->_sections);
  }

  /**
   * @return array
   */
  public function unselectedSectionColumns() {
    $selectColumns = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            $selectColumns["{$tableName}_{$fieldName}"] = 1;
          }
        }
      }
    }

    if (is_array($this->_sections)) {
      return array_diff_key($this->_sections, $selectColumns);
    }
    else {
      return array();
    }
  }

  /**
   * @param $sql
   * @param $rows
   */
  public function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      // section headers not selected for display need to be added to row
      foreach ($unselectedSectionColumns as $key => $values) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      $rows[] = $row;
    }
  }

  /**
   * When "order by" fields are marked as sections, this assigns to the template
   * an array of total counts for each section. This data is used by the Smarty
   * plugin {sectionTotal}
   */
  public function sectionTotals() {

    // Reports using order_bys with sections must populate $this->_selectAliases in select() method.
    if (empty($this->_selectAliases)) {
      return;
    }

    if (!empty($this->_sections)) {
      // build the query with no LIMIT clause
      $select = str_ireplace('SELECT SQL_CALC_FOUND_ROWS ', 'SELECT ', $this->_select);
      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";

      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);

      $ifnulls = array();
      foreach (array_merge($sectionAliases, $this->_selectAliases) as $alias) {
        $ifnulls[] = "ifnull($alias, '') as $alias";
      }

      // Group (un-limited) report by all aliases and get counts. This might
      // be done more efficiently when the contents of $sql are known, ie. by
      // overriding this method in the report class.

      $query = "select " . implode(", ", $ifnulls) .
        ", count(*) as ct from ($sql) as subquery group by " .
        implode(", ", $sectionAliases);

      // initialize array of total counts
      $totals = array();
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = array();
        $i = 1;
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] += $dao->ct;
          }
        }
      }
      $this->assign('sectionTotals', $totals);
    }
  }

  public function modifyColumnHeaders() {
    // use this method to modify $this->_columnHeaders
  }

  /**
   * @param $rows
   */
  public function doTemplateAssignment(&$rows) {
    $this->assign_by_ref('columnHeaders', $this->_columnHeaders);
    $this->assign_by_ref('rows', $rows);
    $this->assign('statistics', $this->statistics($rows));
  }

  /**
   * override this method to build your own statistics
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = array();

    $count = count($rows);

    if ($this->_rollup && ($this->_rollup != '') && $this->_grandFlag) {
      $count++;
    }

    $this->countStat($statistics, $count);

    $this->groupByStat($statistics);

    $this->filterStat($statistics);

    return $statistics;
  }

  /**
   * @param $statistics
   * @param $count
   */
  public function countStat(&$statistics, $count) {
    $statistics['counts']['rowCount'] = array(
      'title' => ts('Row(s) Listed'),
      'value' => $count,
    );

    if ($this->_rowsFound && ($this->_rowsFound > $count)) {
      $statistics['counts']['rowsFound'] = array(
        'title' => ts('Total Row(s)'),
        'value' => $this->_rowsFound,
      );
    }
  }

  /**
   * @param $statistics
   */
  public function groupByStat(&$statistics) {
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              $combinations[] = $field['title'];
            }
          }
        }
      }
      $statistics['groups'][] = array(
        'title' => ts('Grouping(s)'),
        'value' => implode(' & ', $combinations),
      );
    }
  }

  /**
   * @param $statistics
   */
  public function filterStat(&$statistics) {
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE &&
            CRM_Utils_Array::value('operatorType', $field) !=
            CRM_Report_Form::OP_MONTH
          ) {
            list($from, $to)
              = $this->getFromTo(
                CRM_Utils_Array::value("{$fieldName}_relative", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_from", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_to", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params)
              );
            $from_time_format = !empty($this->_params["{$fieldName}_from_time"]) ? 'h' : 'd';
            $from = CRM_Utils_Date::customFormat($from, NULL, array($from_time_format));

            $to_time_format = !empty($this->_params["{$fieldName}_to_time"]) ? 'h' : 'd';
            $to = CRM_Utils_Date::customFormat($to, NULL, array($to_time_format));

            if ($from || $to) {
              $statistics['filters'][] = array(
                'title' => $field['title'],
                'value' => ts("Between %1 and %2", array(1 => $from, 2 => $to)),
              );
            }
            elseif (in_array($rel = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params),
              array_keys($this->getOperationPair(CRM_Report_Form::OP_DATE))
            )) {
              $pair = $this->getOperationPair(CRM_Report_Form::OP_DATE);
              $statistics['filters'][] = array(
                'title' => $field['title'],
                'value' => $pair[$rel],
              );
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            $value = NULL;
            if ($op) {
              $pair = $this->getOperationPair(
                CRM_Utils_Array::value('operatorType', $field),
                $fieldName
              );
              $min = CRM_Utils_Array::value("{$fieldName}_min", $this->_params);
              $max = CRM_Utils_Array::value("{$fieldName}_max", $this->_params);
              $val = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (in_array($op, array('bw', 'nbw')) && ($min || $max)) {
                $value = "{$pair[$op]} $min " . ts('and') . " $max";
              }
              elseif ($val && CRM_Utils_Array::value('operatorType', $field) & self::OP_ENTITYREF) {
                $this->setEntityRefDefaults($field, $tableName);
                $result = civicrm_api3($field['attributes']['entity'], 'getlist',
                  array('id' => $val) +
                  CRM_Utils_Array::value('api', $field['attributes'], array()));
                $values = array();
                foreach ($result['values'] as $v) {
                  $values[] = $v['label'];
                }
                $value = "{$pair[$op]} " . implode(', ', $values);
              }
              elseif ($op == 'nll' || $op == 'nnll') {
                $value = $pair[$op];
              }
              elseif (is_array($val) && (!empty($val))) {
                $options = CRM_Utils_Array::value('options', $field, array());
                foreach ($val as $key => $valIds) {
                  if (isset($options[$valIds])) {
                    $val[$key] = $options[$valIds];
                  }
                }
                $pair[$op] = (count($val) == 1) ? (($op == 'notin' || $op ==
                  'mnot') ? ts('Is Not') : ts('Is')) : CRM_Utils_Array::value($op, $pair);
                $val = implode(', ', $val);
                $value = "{$pair[$op]} " . $val;
              }
              elseif (!is_array($val) && (!empty($val) || $val == '0') &&
                isset($field['options']) &&
                is_array($field['options']) && !empty($field['options'])
              ) {
                $value = CRM_Utils_Array::value($op, $pair) . " " .
                  CRM_Utils_Array::value($val, $field['options'], $val);
              }
              elseif ($val) {
                $value = CRM_Utils_Array::value($op, $pair) . " " . $val;
              }
            }
            if ($value) {
              $statistics['filters'][] = array(
                'title' => CRM_Utils_Array::value('title', $field),
                'value' => $value,
              );
            }
          }
        }
      }
    }
  }

  /**
   * End post processing.
   *
   * @param array|null $rows
   */
  public function endPostProcess(&$rows = NULL) {
    if ($this->_storeResultSet) {
      $this->_resultSet = $rows;
    }

    if ($this->_outputMode == 'print' ||
      $this->_outputMode == 'pdf' ||
      $this->_sendmail
    ) {

      $content = $this->compileContent();
      $url = CRM_Utils_System::url("civicrm/report/instance/{$this->_id}",
        "reset=1", TRUE
      );

      if ($this->_sendmail) {
        $config = CRM_Core_Config::singleton();
        $attachments = array();

        if ($this->_outputMode == 'csv') {
          $content
            = $this->_formValues['report_header'] . '<p>' . ts('Report URL') .
            ": {$url}</p>" . '<p>' .
            ts('The report is attached as a CSV file.') . '</p>' .
            $this->_formValues['report_footer'];

          $csvFullFilename = $config->templateCompileDir .
            CRM_Utils_File::makeFileName('CiviReport.csv');
          $csvContent = CRM_Report_Utils_Report::makeCsv($this, $rows);
          file_put_contents($csvFullFilename, $csvContent);
          $attachments[] = array(
            'fullPath' => $csvFullFilename,
            'mime_type' => 'text/csv',
            'cleanName' => 'CiviReport.csv',
          );
        }
        if ($this->_outputMode == 'pdf') {
          // generate PDF content
          $pdfFullFilename = $config->templateCompileDir .
            CRM_Utils_File::makeFileName('CiviReport.pdf');
          file_put_contents($pdfFullFilename,
            CRM_Utils_PDF_Utils::html2pdf($content, "CiviReport.pdf",
              TRUE, array('orientation' => 'landscape')
            )
          );
          // generate Email Content
          $content
            = $this->_formValues['report_header'] . '<p>' . ts('Report URL') .
            ": {$url}</p>" . '<p>' .
            ts('The report is attached as a PDF file.') . '</p>' .
            $this->_formValues['report_footer'];

          $attachments[] = array(
            'fullPath' => $pdfFullFilename,
            'mime_type' => 'application/pdf',
            'cleanName' => 'CiviReport.pdf',
          );
        }

        if (CRM_Report_Utils_Report::mailReport($content, $this->_id,
          $this->_outputMode, $attachments
        )
        ) {
          CRM_Core_Session::setStatus(ts("Report mail has been sent."), ts('Sent'), 'success');
        }
        else {
          CRM_Core_Session::setStatus(ts("Report mail could not be sent."), ts('Mail Error'), 'error');
        }
        return TRUE;
      }
      elseif ($this->_outputMode == 'print') {
        echo $content;
      }
      else {
        if ($chartType = CRM_Utils_Array::value('charts', $this->_params)) {
          $config = CRM_Core_Config::singleton();
          //get chart image name
          $chartImg = $this->_chartId . '.png';
          //get image url path
          $uploadUrl
            = str_replace('/persist/contribute/', '/persist/', $config->imageUploadURL) .
            'openFlashChart/';
          $uploadUrl .= $chartImg;
          //get image doc path to overwrite
          $uploadImg
            = str_replace('/persist/contribute/', '/persist/', $config->imageUploadDir) .
            'openFlashChart/' . $chartImg;
          //Load the image
          $chart = imagecreatefrompng($uploadUrl);
          //convert it into formatted png
          header('Content-type: image/png');
          //overwrite with same image
          imagepng($chart, $uploadImg);
          //delete the object
          imagedestroy($chart);
        }
        CRM_Utils_PDF_Utils::html2pdf($content, "CiviReport.pdf", FALSE, array('orientation' => 'landscape'));
      }
      CRM_Utils_System::civiExit();
    }
    elseif ($this->_outputMode == 'csv') {
      CRM_Report_Utils_Report::export2csv($this, $rows);
    }
    elseif ($this->_outputMode == 'group') {
      $group = $this->_params['groups'];
      $this->add2group($group);
    }
    elseif ($this->_instanceButtonName == $this->controller->getButtonName()) {
      CRM_Report_Form_Instance::postProcess($this);
    }
    elseif ($this->_createNewButtonName == $this->controller->getButtonName() ||
      $this->_outputMode == 'create_report'
    ) {
      $this->_createNew = TRUE;
      CRM_Report_Form_Instance::postProcess($this);
    }
  }

  /**
   * Set store result set indicator to TRUE.
   *
   * @todo explain what this does
   */
  public function storeResultSet() {
    $this->_storeResultSet = TRUE;
  }

  /**
   * Get result set.
   *
   * @return bool
   */
  public function getResultSet() {
    return $this->_resultSet;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $defaultTpl = parent::getTemplateFileName();
    $template = CRM_Core_Smarty::singleton();
    if (!$template->template_exists($defaultTpl)) {
      $defaultTpl = 'CRM/Report/Form.tpl';
    }
    return $defaultTpl;
  }

  /**
   * Compile the report content.
   * Although this function is super-short it is useful to keep separate so it can be over-ridden by report classes.
   *
   * @return string
   */
  public function compileContent() {
    $templateFile = $this->getHookedTemplateFileName();
    return $this->_formValues['report_header'] .
    CRM_Core_Form::$_template->fetch($templateFile) .
    $this->_formValues['report_footer'];
  }


  public function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();

    // build query
    $sql = $this->buildQuery();

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $rows = array();
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  /**
   * @param int $rowCount
   * @return array
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    // lets do the pager if in html mode
    $this->_limit = NULL;

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }
    if ($this->_outputMode == 'html' || $this->_outputMode == 'group') {
      $this->_select = str_ireplace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select);

      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

      // @todo all http vars should be extracted in the preProcess
      // - not randomly in the class
      if (!$pageId && !empty($_POST)) {
        if (isset($_POST['PagerBottomButton']) && isset($_POST['crmPID_B'])) {
          $pageId = max((int) $_POST['crmPID_B'], 1);
        }
        elseif (isset($_POST['PagerTopButton']) && isset($_POST['crmPID'])) {
          $pageId = max((int) $_POST['crmPID'], 1);
        }
        unset($_POST['crmPID_B'], $_POST['crmPID']);
      }

      $pageId = $pageId ? $pageId : 1;
      $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
      $offset = ($pageId - 1) * $rowCount;

      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $this->_limit = " LIMIT $offset, $rowCount";
      return array($offset, $rowCount);
    }
    if ($this->_limitValue) {
      if ($this->_offsetValue) {
        $this->_limit = " LIMIT {$this->_offsetValue}, {$this->_limitValue} ";
      }
      else {
        $this->_limit = " LIMIT " . $this->_limitValue;
      }
    }
  }

  /**
   * @param int $rowCount
   */
  public function setPager($rowCount = self::ROW_COUNT_LIMIT) {

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }

    if ($this->_limit && ($this->_limit != '')) {
      $sql = "SELECT FOUND_ROWS();";
      $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
      $params = array(
        'total' => $this->_rowsFound,
        'rowCount' => $rowCount,
        'status' => ts('Records') . ' %%StatusMessage%%',
        'buttonBottom' => 'PagerBottomButton',
        'buttonTop' => 'PagerTopButton',
        'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
      );

      $pager = new CRM_Utils_Pager($params);
      $this->assign_by_ref('pager', $pager);
      $this->ajaxResponse['totalRows'] = $this->_rowsFound;
    }
  }

  /**
   * @param $field
   * @param $value
   * @param $op
   *
   * @return string
   */
  public function whereGroupClause($field, $value, $op) {

    $smartGroupQuery = "";

    $group = new CRM_Contact_DAO_Group();
    $group->is_active = 1;
    $group->find();
    $smartGroups = array();
    while ($group->fetch()) {
      if (in_array($group->id, $this->_params['gid_value']) &&
        $group->saved_search_id
      ) {
        $smartGroups[] = $group->id;
      }
    }

    CRM_Contact_BAO_GroupContactCache::check($smartGroups);

    $smartGroupQuery = '';
    if (!empty($smartGroups)) {
      $smartGroups = implode(',', $smartGroups);
      $smartGroupQuery = " UNION DISTINCT
                  SELECT DISTINCT smartgroup_contact.contact_id
                  FROM civicrm_group_contact_cache smartgroup_contact
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }

    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = array($value);
    }
    $clause = "{$field['dbAlias']} IN (" . implode(', ', $value) . ")";

    return " {$this->_aliases['civicrm_contact']}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added'
                          {$smartGroupQuery} ) ";
  }

  /**
   * @param $field
   * @param $value
   * @param $op
   *
   * @return string
   */
  public function whereTagClause($field, $value, $op) {
    // not using left join in query because if any contact
    // belongs to more than one tag, results duplicate
    // entries.
    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = array($value);
    }
    $clause = "{$field['dbAlias']} IN (" . implode(', ', $value) . ")";
    $entity_table = $this->_tagFilterTable;
    return " {$this->_aliases[$entity_table]}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_tag']}.entity_id
                          FROM civicrm_entity_tag {$this->_aliases['civicrm_tag']}
                          WHERE entity_table = '$entity_table' AND {$clause} ) ";
  }

  /**
   * Generate membership organization clause.
   *
   * @param mixed $value
   * @param string $op SQL Operator
   *
   * @return string
   */
  public function whereMembershipOrgClause($value, $op) {
    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = array($value);
    }

    $tmp_membership_org_sql_list = implode(', ', $value);
    return " {$this->_aliases['civicrm_contact']}.id {$sqlOp} (
                          SELECT DISTINCT mem.contact_id
                          FROM civicrm_membership mem
                          LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id
                          LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id
                          WHERE mt.member_of_contact_id IN (" .
    $tmp_membership_org_sql_list . ")
                          AND mt.is_active = '1'
                          AND mem_status.is_current_member = '1'
                          AND mem_status.is_active = '1' )  ";
  }

  /**
   * Generate Membership Type SQL Clause.
   * @param mixed $value
   * @param string $op
   *
   * @return string
   *   SQL query string
   */
  public function whereMembershipTypeClause($value, $op) {
    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = array($value);
    }

    $tmp_membership_sql_list = implode(', ', $value);
    return " {$this->_aliases['civicrm_contact']}.id {$sqlOp} (
                          SELECT DISTINCT mem.contact_id
                          FROM civicrm_membership mem
                          LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id
                          LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id
                          WHERE mem.membership_type_id IN (" .
    $tmp_membership_sql_list . ")
                          AND mt.is_active = '1'
                          AND mem_status.is_current_member = '1'
                          AND mem_status.is_active = '1' ) ";
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact_a') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  /**
   * @param bool $addFields
   * @param array $permCustomGroupIds
   */
  public function addCustomDataToColumns($addFields = TRUE, $permCustomGroupIds = array()) {
    if (empty($this->_customGroupExtends)) {
      return;
    }
    if (!is_array($this->_customGroupExtends)) {
      $this->_customGroupExtends = array($this->_customGroupExtends);
    }
    $customGroupWhere = '';
    if (!empty($permCustomGroupIds)) {
      $customGroupWhere = "cg.id IN (" . implode(',', $permCustomGroupIds) .
        ") AND";
    }
    $sql = "
SELECT cg.table_name, cg.title, cg.extends, cf.id as cf_id, cf.label,
       cf.column_name, cf.data_type, cf.html_type, cf.option_group_id, cf.time_format
FROM   civicrm_custom_group cg
INNER  JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
WHERE cg.extends IN ('" . implode("','", $this->_customGroupExtends) . "') AND
      {$customGroupWhere}
      cg.is_active = 1 AND
      cf.is_active = 1 AND
      cf.is_searchable = 1
ORDER BY cg.weight, cf.weight";
    $customDAO = CRM_Core_DAO::executeQuery($sql);

    $curTable = NULL;
    while ($customDAO->fetch()) {
      if ($customDAO->table_name != $curTable) {
        $curTable = $customDAO->table_name;
        $curFields = $curFilters = array();

        // dummy dao object
        $this->_columns[$curTable]['dao'] = 'CRM_Contact_DAO_Contact';
        $this->_columns[$curTable]['extends'] = $customDAO->extends;
        $this->_columns[$curTable]['grouping'] = $customDAO->table_name;
        $this->_columns[$curTable]['group_title'] = $customDAO->title;

        foreach (array(
                   'fields',
                   'filters',
                   'group_bys',
                 ) as $colKey) {
          if (!array_key_exists($colKey, $this->_columns[$curTable])) {
            $this->_columns[$curTable][$colKey] = array();
          }
        }
      }
      $fieldName = 'custom_' . $customDAO->cf_id;

      if ($addFields) {
        // this makes aliasing work in favor
        $curFields[$fieldName] = array(
          'name' => $customDAO->column_name,
          'title' => $customDAO->label,
          'dataType' => $customDAO->data_type,
          'htmlType' => $customDAO->html_type,
        );
      }
      if ($this->_customGroupFilters) {
        // this makes aliasing work in favor
        $curFilters[$fieldName] = array(
          'name' => $customDAO->column_name,
          'title' => $customDAO->label,
          'dataType' => $customDAO->data_type,
          'htmlType' => $customDAO->html_type,
        );
      }

      switch ($customDAO->data_type) {
        case 'Date':
          // filters
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_DATE;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_DATE;
          // CRM-6946, show time part for datetime date fields
          if ($customDAO->time_format) {
            $curFields[$fieldName]['type'] = CRM_Utils_Type::T_TIMESTAMP;
          }
          break;

        case 'Boolean':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_SELECT;
          $curFilters[$fieldName]['options'] = array(
            '' => ts('- select -'),
            1 => ts('Yes'),
            0 => ts('No'),
          );
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Int':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Money':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
          break;

        case 'Float':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
          break;

        case 'String':
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;

          if (!empty($customDAO->option_group_id)) {
            if (in_array($customDAO->html_type, array(
              'Multi-Select',
              'AdvMulti-Select',
              'CheckBox',
            ))) {
              $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
            }
            else {
              $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
            }
            if ($this->_customGroupFilters) {
              $curFilters[$fieldName]['options'] = array();
              $ogDAO = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.label FROM civicrm_option_value ov WHERE ov.option_group_id = %1 ORDER BY ov.weight", array(
                1 => array(
                  $customDAO->option_group_id,
                  'Integer',
                ),
              ));
              while ($ogDAO->fetch()) {
                $curFilters[$fieldName]['options'][$ogDAO->value] = $ogDAO->label;
              }
              CRM_Utils_Hook::customFieldOptions($customDAO->cf_id, $curFilters[$fieldName]['options'], FALSE);
            }
          }
          break;

        case 'StateProvince':
          if (in_array($customDAO->html_type, array(
            'Multi-Select State/Province',
          ))) {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
          }
          else {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
          }
          $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::stateProvince();
          break;

        case 'Country':
          if (in_array($customDAO->html_type, array(
            'Multi-Select Country',
          ))) {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
          }
          else {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
          }
          $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::country();
          break;

        case 'ContactReference':
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFilters[$fieldName]['name'] = 'display_name';
          $curFilters[$fieldName]['alias'] = "contact_{$fieldName}_civireport";

          $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFields[$fieldName]['name'] = 'display_name';
          $curFields[$fieldName]['alias'] = "contact_{$fieldName}_civireport";
          break;

        default:
          $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
      }

      if (!array_key_exists('type', $curFields[$fieldName])) {
        $curFields[$fieldName]['type'] = CRM_Utils_Array::value('type', $curFilters[$fieldName], array());
      }

      if ($addFields) {
        $this->_columns[$curTable]['fields'] = array_merge($this->_columns[$curTable]['fields'], $curFields);
      }
      if ($this->_customGroupFilters) {
        $this->_columns[$curTable]['filters'] = array_merge($this->_columns[$curTable]['filters'], $curFilters);
      }
      if ($this->_customGroupGroupBy) {
        $this->_columns[$curTable]['group_bys'] = array_merge($this->_columns[$curTable]['group_bys'], $curFields);
      }
    }
  }

  public function customDataFrom() {
    if (empty($this->_customGroupExtends)) {
      return;
    }
    $mapper = CRM_Core_BAO_CustomQuery::$extendsMap;

    foreach ($this->_columns as $table => $prop) {
      if (substr($table, 0, 13) == 'civicrm_value' ||
        substr($table, 0, 12) == 'custom_value'
      ) {
        $extendsTable = $mapper[$prop['extends']];

        // check field is in params
        if (!$this->isFieldSelected($prop)) {
          continue;
        }
        $baseJoin = CRM_Utils_Array::value($prop['extends'], $this->_customGroupExtendsJoin, "{$this->_aliases[$extendsTable]}.id");

        $customJoin = is_array($this->_customGroupJoin) ? $this->_customGroupJoin[$table] : $this->_customGroupJoin;
        $this->_from .= "
{$customJoin} {$table} {$this->_aliases[$table]} ON {$this->_aliases[$table]}.entity_id = {$baseJoin}";
        // handle for ContactReference
        if (array_key_exists('fields', $prop)) {
          foreach ($prop['fields'] as $fieldName => $field) {
            if (CRM_Utils_Array::value('dataType', $field) ==
              'ContactReference'
            ) {
              $columnName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', CRM_Core_BAO_CustomField::getKeyID($fieldName), 'column_name');
              $this->_from .= "
LEFT JOIN civicrm_contact {$field['alias']} ON {$field['alias']}.id = {$this->_aliases[$table]}.{$columnName} ";
            }
          }
        }
      }
    }
  }

  /**
   * @param $prop
   *
   * @return bool
   */
  public function isFieldSelected($prop) {
    if (empty($prop)) {
      return FALSE;
    }

    if (!empty($this->_params['fields'])) {
      foreach (array_keys($prop['fields']) as $fieldAlias) {
        $customFieldId = CRM_Core_BAO_CustomField::getKeyID($fieldAlias);
        if ($customFieldId) {
          if (array_key_exists($fieldAlias, $this->_params['fields'])) {
            return TRUE;
          }

          //might be survey response field.
          if (!empty($this->_params['fields']['survey_response']) &&
            !empty($prop['fields'][$fieldAlias]['isSurveyResponseField'])
          ) {
            return TRUE;
          }
        }
      }
    }

    if (!empty($this->_params['group_bys']) && $this->_customGroupGroupBy) {
      foreach (array_keys($prop['group_bys']) as $fieldAlias) {
        if (array_key_exists($fieldAlias, $this->_params['group_bys']) &&
          CRM_Core_BAO_CustomField::getKeyID($fieldAlias)
        ) {
          return TRUE;
        }
      }
    }

    if (!empty($this->_params['order_bys'])) {
      foreach (array_keys($prop['fields']) as $fieldAlias) {
        foreach ($this->_params['order_bys'] as $orderBy) {
          if ($fieldAlias == $orderBy['column'] &&
            CRM_Core_BAO_CustomField::getKeyID($fieldAlias)
          ) {
            return TRUE;
          }
        }
      }
    }

    if (!empty($prop['filters']) && $this->_customGroupFilters) {
      foreach ($prop['filters'] as $fieldAlias => $val) {
        foreach (array(
                   'value',
                   'min',
                   'max',
                   'relative',
                   'from',
                   'to',
                 ) as $attach) {
          if (isset($this->_params[$fieldAlias . '_' . $attach]) &&
            (!empty($this->_params[$fieldAlias . '_' . $attach])
              || ($attach != 'relative' &&
                $this->_params[$fieldAlias . '_' . $attach] == '0')
            )
          ) {
            return TRUE;
          }
        }
        if (!empty($this->_params[$fieldAlias . '_op']) &&
          in_array($this->_params[$fieldAlias . '_op'], array('nll', 'nnll'))
        ) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Check for empty order_by configurations and remove them; also set
   * template to hide them.
   *
   * @param array $formValues
   */
  public function preProcessOrderBy(&$formValues) {
    // Object to show/hide form elements
    $_showHide = new CRM_Core_ShowHideBlocks('', '');

    $_showHide->addShow('optionField_1');

    // Cycle through order_by options; skip any empty ones, and hide them as well
    $n = 1;

    if (!empty($formValues['order_bys'])) {
      foreach ($formValues['order_bys'] as $order_by) {
        if ($order_by['column'] && $order_by['column'] != '-') {
          $_showHide->addShow('optionField_' . $n);
          $orderBys[$n] = $order_by;
          $n++;
        }
      }
    }
    for ($i = $n; $i <= 5; $i++) {
      if ($i > 1) {
        $_showHide->addHide('optionField_' . $i);
      }
    }

    // overwrite order_by options with modified values
    if (!empty($orderBys)) {
      $formValues['order_bys'] = $orderBys;
    }
    else {
      $formValues['order_bys'] = array(1 => array('column' => '-'));
    }

    // assign show/hide data to template
    $_showHide->addToTemplate();
  }

  /**
   * Does table name have columns in SELECT clause?
   *
   * @param string $tableName
   *   Name of table (index of $this->_columns array).
   *
   * @return bool
   */
  public function isTableSelected($tableName) {
    return in_array($tableName, $this->selectedTables());
  }

  /**
   * Fetch array of DAO tables having columns included in SELECT or ORDER BY clause.
   * (building the array if it's unset)
   *
   * @return array
   *   selectedTables
   */
  public function selectedTables() {
    if (!$this->_selectedTables) {
      $orderByColumns = array();
      if (array_key_exists('order_bys', $this->_params) &&
        is_array($this->_params['order_bys'])
      ) {
        foreach ($this->_params['order_bys'] as $orderBy) {
          $orderByColumns[] = $orderBy['column'];
        }
      }

      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('fields', $table)) {
          foreach ($table['fields'] as $fieldName => $field) {
            if (!empty($field['required']) ||
              !empty($this->_params['fields'][$fieldName])
            ) {
              $this->_selectedTables[] = $tableName;
              break;
            }
          }
        }
        if (array_key_exists('order_bys', $table)) {
          foreach ($table['order_bys'] as $orderByName => $orderBy) {
            if (in_array($orderByName, $orderByColumns)) {
              $this->_selectedTables[] = $tableName;
              break;
            }
          }
        }
        if (array_key_exists('filters', $table)) {
          foreach ($table['filters'] as $filterName => $filter) {
            if (!empty($this->_params["{$filterName}_value"]) ||
              CRM_Utils_Array::value("{$filterName}_op", $this->_params) ==
              'nll' ||
              CRM_Utils_Array::value("{$filterName}_op", $this->_params) ==
              'nnll'
            ) {
              $this->_selectedTables[] = $tableName;
              break;
            }
          }
        }
      }
    }
    return $this->_selectedTables;
  }

  /**
   * @deprecated - use getAddressColumns which is a more accurate description
   * and also accepts an array of options rather than a long list
   *
   * adding address fields to construct function in reports
   *
   * @param bool $groupBy
   *   Add GroupBy? Not appropriate for detail report.
   * @param bool $orderBy
   *   Add GroupBy? Not appropriate for detail report.
   * @param bool $filters
   * @param array $defaults
   *
   * @return array
   *   address fields for construct clause
   */
  public function addAddressFields($groupBy = TRUE, $orderBy = FALSE, $filters = TRUE, $defaults = array('country_id' => TRUE)) {
    $addressFields = array(
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'name' => array(
            'title' => ts('Address Name'),
            'default' => CRM_Utils_Array::value('name', $defaults, FALSE),
          ),
          'street_address' => array(
            'title' => ts('Street Address'),
            'default' => CRM_Utils_Array::value('street_address', $defaults, FALSE),
          ),
          'supplemental_address_1' => array(
            'title' => ts('Supplementary Address Field 1'),
            'default' => CRM_Utils_Array::value('supplemental_address_1', $defaults, FALSE),
          ),
          'supplemental_address_2' => array(
            'title' => ts('Supplementary Address Field 2'),
            'default' => CRM_Utils_Array::value('supplemental_address_2', $defaults, FALSE),
          ),
          'street_number' => array(
            'name' => 'street_number',
            'title' => ts('Street Number'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_number', $defaults, FALSE),
          ),
          'street_name' => array(
            'name' => 'street_name',
            'title' => ts('Street Name'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_name', $defaults, FALSE),
          ),
          'street_unit' => array(
            'name' => 'street_unit',
            'title' => ts('Street Unit'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_unit', $defaults, FALSE),
          ),
          'city' => array(
            'title' => ts('City'),
            'default' => CRM_Utils_Array::value('city', $defaults, FALSE),
          ),
          'postal_code' => array(
            'title' => ts('Postal Code'),
            'default' => CRM_Utils_Array::value('postal_code', $defaults, FALSE),
          ),
          'postal_code_suffix' => array(
            'title' => ts('Postal Code Suffix'),
            'default' => CRM_Utils_Array::value('postal_code_suffix', $defaults, FALSE),
          ),
          'country_id' => array(
            'title' => ts('Country'),
            'default' => CRM_Utils_Array::value('country_id', $defaults, FALSE),
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'default' => CRM_Utils_Array::value('state_province_id', $defaults, FALSE),
          ),
          'county_id' => array(
            'title' => ts('County'),
            'default' => CRM_Utils_Array::value('county_id', $defaults, FALSE),
          ),
        ),
        'grouping' => 'location-fields',
      ),
    );

    if ($filters) {
      $addressFields['civicrm_address']['filters'] = array(
        'street_number' => array(
          'title' => ts('Street Number'),
          'type' => 1,
          'name' => 'street_number',
        ),
        'street_name' => array(
          'title' => ts('Street Name'),
          'name' => 'street_name',
          'operator' => 'like',
        ),
        'postal_code' => array(
          'title' => ts('Postal Code'),
          'type' => 1,
          'name' => 'postal_code',
        ),
        'city' => array(
          'title' => ts('City'),
          'operator' => 'like',
          'name' => 'city',
        ),
        'country_id' => array(
          'name' => 'country_id',
          'title' => ts('Country'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::country(),
        ),
        'state_province_id' => array(
          'name' => 'state_province_id',
          'title' => ts('State/Province'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => array(),
        ),
        'county_id' => array(
          'name' => 'county_id',
          'title' => ts('County'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => array(),
        ),
      );
    }

    if ($orderBy) {
      $addressFields['civicrm_address']['order_bys'] = array(
        'street_name' => array('title' => ts('Street Name')),
        'street_number' => array('title' => 'Odd / Even Street Number'),
        'street_address' => NULL,
        'city' => NULL,
        'postal_code' => NULL,
      );
    }

    if ($groupBy) {
      $addressFields['civicrm_address']['group_bys'] = array(
        'street_address' => NULL,
        'city' => NULL,
        'postal_code' => NULL,
        'state_province_id' => array(
          'title' => ts('State/Province'),
        ),
        'country_id' => array(
          'title' => ts('Country'),
        ),
        'county_id' => array(
          'title' => ts('County'),
        ),
      );
    }
    return $addressFields;
  }

  /**
   * Do AlterDisplay processing on Address Fields.
   *
   * @param $row
   * @param $rows
   * @param $rowNum
   * @param $baseUrl
   * @param $urltxt
   *
   * @return bool
   */
  public function alterDisplayAddressFields(&$row, &$rows, &$rowNum, $baseUrl, $urltxt) {
    $criteriaQueryParams = CRM_Report_Utils_Report::getPreviewCriteriaQueryParams($this->_defaults, $this->_params);
    $entryFound = FALSE;
    // handle country
    if (array_key_exists('civicrm_address_country_id', $row)) {
      if ($value = $row['civicrm_address_country_id']) {
        $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
          "reset=1&force=1&{$criteriaQueryParams}&" .
          "country_id_op=in&country_id_value={$value}",
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_address_country_id_link'] = $url;
        $rows[$rowNum]['civicrm_address_country_id_hover'] = ts("%1 for this country.",
          array(1 => $urltxt)
        );
      }

      $entryFound = TRUE;
    }
    if (array_key_exists('civicrm_address_county_id', $row)) {
      if ($value = $row['civicrm_address_county_id']) {
        $rows[$rowNum]['civicrm_address_county_id'] = CRM_Core_PseudoConstant::county($value, FALSE);
        $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
          "reset=1&force=1&{$criteriaQueryParams}&" .
          "county_id_op=in&county_id_value={$value}",
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_address_county_id_link'] = $url;
        $rows[$rowNum]['civicrm_address_county_id_hover'] = ts("%1 for this county.",
          array(1 => $urltxt)
        );
      }
      $entryFound = TRUE;
    }
    // handle state province
    if (array_key_exists('civicrm_address_state_province_id', $row)) {
      if ($value = $row['civicrm_address_state_province_id']) {
        $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);

        $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
          "reset=1&force=1&{$criteriaQueryParams}&state_province_id_op=in&state_province_id_value={$value}",
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_address_state_province_id_link'] = $url;
        $rows[$rowNum]['civicrm_address_state_province_id_hover'] = ts("%1 for this state.",
          array(1 => $urltxt)
        );
      }
      $entryFound = TRUE;
    }

    return $entryFound;
  }

  /**
   * Adjusts dates passed in to YEAR() for fiscal year.
   *
   * @param string $fieldName
   *
   * @return string
   */
  public function fiscalYearOffset($fieldName) {
    $config = CRM_Core_Config::singleton();
    $fy = $config->fiscalYearStart;
    if (CRM_Utils_Array::value('yid_op', $this->_params) == 'calendar' ||
      ($fy['d'] == 1 && $fy['M'] == 1)
    ) {
      return "YEAR( $fieldName )";
    }
    return "YEAR( $fieldName - INTERVAL " . ($fy['M'] - 1) . " MONTH" .
    ($fy['d'] > 1 ? (" - INTERVAL " . ($fy['d'] - 1) . " DAY") : '') . " )";
  }

  /**
   * Add Address into From Table if required.
   */
  public function addAddressFromClause() {
    // include address field if address column is to be included
    if ((isset($this->_addressField) &&
        $this->_addressField
      ) ||
      $this->isTableSelected('civicrm_address')
    ) {
      $this->_from .= "
                 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                           ON ({$this->_aliases['civicrm_contact']}.id =
                               {$this->_aliases['civicrm_address']}.contact_id) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  /**
   * Add Phone into From Table if required.
   */
  public function addPhoneFromClause() {
    // include address field if address column is to be included
    if ($this->isTableSelected('civicrm_phone')
    ) {
      $this->_from .= "
      LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
      ON ({$this->_aliases['civicrm_contact']}.id =
      {$this->_aliases['civicrm_phone']}.contact_id) AND
      {$this->_aliases['civicrm_phone']}.is_primary = 1\n";
    }
  }

  /**
   * Get phone columns to add to array.
   *
   * @param array $options
   *   - prefix Prefix to add to table (in case of more than one instance of the table)
   *   - prefix_label Label to give columns from this phone table instance
   *
   * @return array
   *   phone columns definition
   */
  public function getPhoneColumns($options = array()) {
    $defaultOptions = array(
      'prefix' => '',
      'prefix_label' => '',
    );

    $options = array_merge($defaultOptions, $options);

    $fields = array(
      $options['prefix'] . 'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          $options['prefix'] . 'phone' => array(
            'title' => ts($options['prefix_label'] . 'Phone'),
            'name' => 'phone',
          ),
        ),
      ),
    );
    return $fields;
  }

  /**
   * Get address columns to add to array.
   *
   * @param array $options
   *   - prefix Prefix to add to table (in case of more than one instance of the table)
   *   - prefix_label Label to give columns from this address table instance
   *
   * @return array
   *   address columns definition
   */
  public function getAddressColumns($options = array()) {
    $options += array(
      'prefix' => '',
      'prefix_label' => '',
      'group_by' => TRUE,
      'order_by' => TRUE,
      'filters' => TRUE,
      'defaults' => array(),
    );
    return $this->addAddressFields(
      $options['group_by'],
      $options['order_by'],
      $options['filters'],
      $options['defaults']
    );
  }

  /**
   * @param int $groupID
   */
  public function add2group($groupID) {
    if (is_numeric($groupID) && isset($this->_aliases['civicrm_contact'])) {
      $select = "SELECT DISTINCT {$this->_aliases['civicrm_contact']}.id AS addtogroup_contact_id, ";
      $select = str_ireplace('SELECT SQL_CALC_FOUND_ROWS ', $select, $this->_select);

      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";
      $sql = str_replace('WITH ROLLUP', '', $sql);
      $dao = CRM_Core_DAO::executeQuery($sql);

      $contact_ids = array();
      // Add resulting contacts to group
      while ($dao->fetch()) {
        if ($dao->addtogroup_contact_id) {
          $contact_ids[$dao->addtogroup_contact_id] = $dao->addtogroup_contact_id;
        }
      }

      if (!empty($contact_ids)) {
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contact_ids, $groupID);
        CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts("The listed records(s) cannot be added to the group."));
      }
    }
  }

  /**
   * function used for showing charts on print screen.
   */
  public static function uploadChartImage() {
    // upload strictly for '.png' images
    $name = trim(basename(CRM_Utils_Request::retrieve('name', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET')));
    if (preg_match('/\.png$/', $name)) {
      //
      // POST data is usually string data, but we are passing a RAW .png
      // so PHP is a bit confused and $_POST is empty. But it has saved
      // the raw bits into $HTTP_RAW_POST_DATA
      //
      $httpRawPostData = $GLOBALS['HTTP_RAW_POST_DATA'];

      // prepare the directory
      $config = CRM_Core_Config::singleton();
      $defaultPath
        = str_replace('/persist/contribute/', '/persist/', $config->imageUploadDir) .
        '/openFlashChart/';
      if (!file_exists($defaultPath)) {
        mkdir($defaultPath, 0777, TRUE);
      }

      // full path to the saved image including filename
      $destination = $defaultPath . $name;

      //write and save
      $jfh = fopen($destination, 'w') or die("can't open file");
      fwrite($jfh, $httpRawPostData);
      fclose($jfh);
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * Apply common settings to entityRef fields.
   *
   * @param array $field
   * @param string $table
   */
  private function setEntityRefDefaults(&$field, $table) {
    $field['attributes'] = $field['attributes'] ? $field['attributes'] : array();
    $field['attributes'] += array(
      'entity' => CRM_Core_DAO_AllCoreTables::getBriefName(CRM_Core_DAO_AllCoreTables::getClassForTable($table)),
      'multiple' => TRUE,
      'placeholder' => ts('- select -'),
    );
  }

}
