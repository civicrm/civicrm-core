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
  public $_options = array();

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
   * Has the report been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it.
   *
   * This property exists to highlight the reports which are still using the
   * slow method & allow group filtering to still work for them until they
   * can be migrated.
   *
   * In order to protect extensions we have to default to TRUE - but I have
   * separately marked every class with a groupFilter in the hope that will trigger
   * people to fix them as they touch them.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

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
   * Should we add paging.
   *
   * @var bool
   */
  protected $addPaging = TRUE;

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
   * Table containing list of contact IDs within the group filter.
   *
   * @var string
   */
  protected $groupTempTable = '';

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
   * of the code (see limit() fn.
   *
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
   * Array of DAO tables having columns included in SELECT or ORDER BY clause.
   *
   * Where has also been added to this although perhaps the 'includes both' array should have a different name.
   *
   * @var array
   */
  protected $_selectedTables;

  /**
   * Array of DAO tables having columns included in WHERE or HAVING clause
   *
   * @var array
   */
  protected $filteredTables;

  /**
   * Output mode e.g 'print', 'csv', 'pdf'.
   *
   * @var string
   */
  protected $_outputMode;

  /**
   * Format of any chart in use.
   *
   * (it's unclear if this could be merged with outputMode at this stage)
   *
   * @var
   */
  protected $_format;

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
   *  When a grand total row has calculated the status we pop it off to here.
   *
   * This allows us to access it from the stats function and avoid recalculating.
   */
  protected $rollupRow = array();

  /**
   * @var string Database attributes - character set and collation
   */
  protected $_databaseAttributes = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';

  /**
   * SQL being run in this report.
   *
   * The sql in the report is stored in this variable in order to be displayed on the developer tab.
   *
   * @var string
   */

  protected $sql;

  /**
   * SQL being run in this report as an array.
   *
   * The sql in the report is stored in this variable in order to be returned to api & test calls.
   *
   * @var string
   */

  protected $sqlArray;
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
          'title' => ts('Contact ID'),
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

      $this->setOutputMode();

      if ($this->_outputMode == 'copy') {
        $this->_createNew = TRUE;
        $this->_params = $this->_formValues;
        $this->_params['view_mode'] = 'criteria';
        $this->_params['title'] = $this->getTitle() . ts(' (copy created by %1 on %2)', array(
          CRM_Core_Session::singleton()->getLoggedInContactDisplayName(),
          CRM_Utils_Date::customFormat(date('Y-m-d H:i')),
        ));
        // Do not pass go. Do not collect another chance to re-run the same query.
        CRM_Report_Form_Instance::postProcess($this);
      }

      // lets always do a force if reset is found in the url.
      // Hey why not? see CRM-17225 for more about this. The use of reset to be force is historical for reasons stated
      // in the comment line above these 2.
      if (!empty($_REQUEST['reset'])
          && !in_array(CRM_Utils_Request::retrieve('output', 'String'), array('save', 'criteria'))) {
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

    // Special permissions check for private instance if it's not the current contact instance
    if ($this->_id &&
      (CRM_Report_BAO_ReportInstance::reportIsPrivate($this->_id) &&
      !CRM_Report_BAO_ReportInstance::contactIsOwner($this->_id))) {
      if (!CRM_Core_Permission::check('access all private reports')) {
        $this->_instanceForm = FALSE;
        $this->assign('criteriaForm', FALSE);
      }
    }

    $this->_instanceButtonName = $this->getButtonName('submit', 'save');
    $this->_createNewButtonName = $this->getButtonName('submit', 'next');
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
              if ((CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_INT) && is_array($field['default'])) {
                $this->_defaults["{$fieldName}_min"] = CRM_Utils_Array::value('min', $field['default']);
                $this->_defaults["{$fieldName}_max"] = CRM_Utils_Array::value('max', $field['default']);
              }
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
   * @param bool $isForce
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
   * Setter for $addPaging.
   *
   * @param bool $value
   */
  public function setAddPaging($value) {
    $this->addPaging = $value;
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
              // which cryptically translates to needing an accordion.
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
            CRM_Core_Form_Date::buildDateRange($this, $fieldName, $count, '_from', '_to', ts('From:'), FALSE, $operations);
            $count++;
            break;

          case CRM_Report_Form::OP_DATETIME:
            // build datetime fields
            CRM_Core_Form_Date::buildDateRange($this, $fieldName, $count, '_from', '_to', ts('From:'), FALSE, $operations, 'searchDate', TRUE);
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
   * The intent is to add a tab for developers to view the sql.
   *
   * Currently using dpm.
   *
   * @param string $sql
   */
  protected function addToDeveloperTab($sql) {
    if (!CRM_Core_Permission::check('view report sql')) {
      return;
    }
    $this->tabs['Developer'] = array(
      'title' => ts('Developer'),
      'tpl' => 'Developer',
      'div_label' => 'set-developer',
    );

    $this->assignTabs();
    $this->sqlArray[] = $sql;
    foreach ($this->sqlArray as $sql) {
      foreach (array('LEFT JOIN') as $term) {
        $sql = str_replace($term, '<br>&nbsp&nbsp' . $term, $sql);
      }
      foreach (array('FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT', ';') as $term) {
        $sql = str_replace($term, '<br><br>' . $term, $sql);
      }
      $this->sqlFormattedArray[] = $sql;
      $this->assign('sql', implode(';<br><br><br><br>', $this->sqlFormattedArray));
    }
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
    if (!empty($this->_options) &&
        (!$this->_id
          || ($this->_id && CRM_Report_BAO_ReportInstance::contactCanAdministerReport($this->_id)))
    ) {
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
          'ASC' => ts('Ascending'),
          'DESC' => ts('Descending'),
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
    $this->_actionButtonName = $this->getButtonName('submit');
    $this->addTaskMenu($this->getActions($this->_id));

    $this->assign('instanceForm', $this->_instanceForm);

    // CRM-16274 Determine if user has 'edit all contacts' or equivalent
    $permission = CRM_Core_Permission::getPermission();
    if ($permission == CRM_Core_Permission::EDIT &&
      $this->_add2groupSupported
    ) {
      $this->addElement('select', 'groups', ts('Group'),
        array('' => ts('Add Contacts to Group')) +
        CRM_Core_PseudoConstant::nestedGroup(),
        array('class' => 'crm-select2 crm-action-menu fa-plus huge')
      );
      $this->assign('group', TRUE);
    }

    $this->addElement('submit', $this->_groupButtonName, '', array('style' => 'display: none;'));

    $this->addChartOptions();
    $showResultsLabel = $this->getResultsLabel();
    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => $showResultsLabel,
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Has this form been submitted already?
   *
   * @return bool
   */
  public function resultsDisplayed() {
    $buttonName = $this->controller->getButtonName();
    return ($buttonName || $this->_outputMode);
  }

  /**
   * Get the actions for this report instance.
   *
   * @param int $instanceId
   *
   * @return array
   */
  protected function getActions($instanceId) {
    $actions = CRM_Report_BAO_ReportInstance::getActionMetadata();
    if (empty($instanceId)) {
      $actions['report_instance.save'] = array(
        'title' => ts('Create Report'),
        'data' => array(
          'is_confirm' => TRUE,
          'confirm_title' => ts('Create Report'),
          'confirm_refresh_fields' => json_encode(array(
            'title' => array('selector' => '.crm-report-instanceForm-form-block-title', 'prepend' => ''),
            'description' => array('selector' => '.crm-report-instanceForm-form-block-description', 'prepend' => ''),
          )),
        ),
      );
    }

    if (!$this->_csvSupported) {
      unset($actions['report_instance.csv']);
    }

    return $actions;
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
   * A form rule function for custom data.
   *
   * The rule ensures that fields selected in group_by if any) should only be the ones
   * present in display/select fields criteria;
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
            'type' => CRM_Utils_Type::T_INT,
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
        'type' => CRM_Utils_Type::T_INT,
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

    // CRM-18010: Ensure type of each report filters
    if (!$type) {
      trigger_error('Type is not defined for field ' . $field['name'], E_USER_WARNING);
    }
    $clause = NULL;

    switch ($op) {
      case 'bw':
      case 'nbw':
        if (($min !== NULL && strlen($min) > 0) ||
          ($max !== NULL && strlen($max) > 0)
        ) {
          $clauses = array();
          if ($min) {
            $min = CRM_Utils_Type::escape($min, $type);
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} >= $min )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} < $min OR {$field['dbAlias']} IS NULL )";
            }
          }
          if ($max) {
            $max = CRM_Utils_Type::escape($max, $type);
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
              $clause = '(' . implode('OR', $clauses) . ')';
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
        if ((is_string($value) || is_numeric($value)) && strlen($value)) {
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
      case 'mnot':
        // multiple has or multiple not
        if ($value !== NULL && count($value) > 0) {
          $value = CRM_Utils_Type::escapeAll($value, $type);
          $operator = $op == 'mnot' ? 'NOT' : '';
          $regexp = "([[:cntrl:]]|^)" . implode('([[:cntrl:]]|$)|([[:cntrl:]]|^)', (array) $value) . "([[:cntrl:]]|$)";
          $clause = "{$field['dbAlias']} {$operator} REGEXP '{$regexp}'";
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

      case 'eq':
      case 'neq':
      case 'ne':
        //CRM-18457: some custom field passes value in array format against binary operator
        if (is_array($value) && count($value)) {
          $value = $value[0];
        }

      default:
        if ($value !== NULL && $value !== '') {
          if (isset($field['clause'])) {
            // FIXME: we not doing escape here. Better solution is to use two
            // different types - data-type and filter-type
            $clause = $field['clause'];
          }
          elseif (!is_array($value)) {
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
   * @param string $relative
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

    $customFields = array();
    $customFieldIds = array();
    foreach ($this->_params['fields'] as $fieldAlias => $value) {
      if ($fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
        $customFieldIds[$fieldAlias] = $fieldId;
      }
    }
    if (empty($customFieldIds)) {
      return;
    }

    // skip for type date and ContactReference since date format is already handled
    $query = "
SELECT cg.table_name, cf.id
FROM  civicrm_custom_field cf
INNER JOIN civicrm_custom_group cg ON cg.id = cf.custom_group_id
WHERE cg.extends IN ('" . implode("','", $this->_customGroupExtends) . "') AND
      cg.is_active = 1 AND
      cf.is_active = 1 AND
      cf.is_searchable = 1 AND
      cf.data_type   NOT IN ('ContactReference', 'Date') AND
      cf.id IN (" . implode(",", $customFieldIds) . ")";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $customFields[$dao->table_name . '_custom_' . $dao->id] = $dao->id;
    }
    $dao->free();

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      foreach ($row as $tableCol => $val) {
        if (array_key_exists($tableCol, $customFields)) {
          $rows[$rowNum][$tableCol] = CRM_Core_BAO_CustomField::displayValue($val, $customFields[$tableCol]);
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
   * Remove duplicate rows.
   *
   * @param array $rows
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
   * Fix subtotal display.
   *
   * @param array $row
   * @param array $fields
   * @param bool $subtotal
   */
  public function fixSubTotalDisplay(&$row, $fields, $subtotal = TRUE) {
    foreach ($row as $colName => $colVal) {
      if (in_array($colName, $fields)) {
      }
      elseif (isset($this->_columnHeaders[$colName])) {
        if ($subtotal) {
          $row[$colName] = 'Subtotal';
          $subtotal = FALSE;
        }
        else {
          unset($row[$colName]);
        }
      }
    }
  }

  /**
   * Calculate grant total.
   *
   * @param array $rows
   *
   * @return bool
   */
  public function grandTotal(&$rows) {
    if (!$this->_rollup  || count($rows) == 1) {
      return FALSE;
    }

    $this->moveSummaryColumnsToTheRightHandSide();

    if ($this->_limit && count($rows) >= self::ROW_COUNT_LIMIT) {
      return FALSE;
    }

    $this->rollupRow = array_pop($rows);

    foreach ($this->_columnHeaders as $fld => $val) {
      if (!in_array($fld, $this->_statFields)) {
        if (!$this->_grandFlag) {
          $this->rollupRow[$fld] = ts('Grand Total');
          $this->_grandFlag = TRUE;
        }
        else {
          $this->rollupRow[$fld] = "";
        }
      }
    }

    $this->assign('grandStat', $this->rollupRow);
    return TRUE;
  }

  /**
   * Format display output.
   *
   * @param array $rows
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
   * Build chart.
   *
   * @param array $rows
   */
  public function buildChart(&$rows) {
    // override this method for building charts.
  }

  // select() method below has been added recently (v3.3), and many of the report templates might
  // still be having their own select() method. We should fix them as and when encountered and move
  // towards generalizing the select() method below.

  /**
   * Generate the SELECT clause and set class variable $_select.
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
            // for a particular field of a particular type.
            // 2. This method when used should receive params by reference and modify $this->_columnHeaders
            // as needed.
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
          // for a particular field of a particular type.
          // 2. This method when used should receive params by reference and modify $this->_columnHeaders
          // as needed.
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
   * Build select clause for a single field.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param string $field
   *
   * @return bool
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    return FALSE;
  }

  /**
   * Build where clause.
   */
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
   * Store Where clauses into an array.
   *
   * Breaking out this step makes over-riding more flexible as the clauses can be used in constructing a
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

  /**
   * Set output mode.
   */
  public function processReportMode() {
    $this->setOutputMode();

    $this->_sendmail
      = CRM_Utils_Request::retrieve(
        'sendmail',
        'Boolean',
        CRM_Core_DAO::$_nullObject
      );

    $this->_absoluteUrl = FALSE;
    $printOnly = FALSE;
    $this->assign('printOnly', FALSE);

    if ($this->_outputMode == 'print' ||
      ($this->_sendmail && !$this->_outputMode)
    ) {
      $this->assign('printOnly', TRUE);
      $printOnly = TRUE;
      $this->addPaging = FALSE;
      $this->assign('outputMode', 'print');
      $this->_outputMode = 'print';
      if ($this->_sendmail) {
        $this->_absoluteUrl = TRUE;
      }
    }
    elseif ($this->_outputMode == 'pdf') {
      $printOnly = TRUE;
      $this->addPaging = FALSE;
      $this->_absoluteUrl = TRUE;
    }
    elseif ($this->_outputMode == 'csv') {
      $printOnly = TRUE;
      $this->_absoluteUrl = TRUE;
      $this->addPaging = FALSE;
    }
    elseif ($this->_outputMode == 'group') {
      $this->assign('outputMode', 'group');
    }
    elseif ($this->_outputMode == 'create_report' && $this->_criteriaForm) {
      $this->assign('outputMode', 'create_report');
    }
    elseif ($this->_outputMode == 'copy' && $this->_criteriaForm) {
      $this->_createNew = TRUE;
    }

    $this->assign('outputMode', $this->_outputMode);
    $this->assign('printOnly', $printOnly);
    // Get today's date to include in printed reports
    if ($printOnly) {
      $reportDate = CRM_Utils_Date::customFormat(date('Y-m-d H:i'));
      $this->assign('reportDate', $reportDate);
    }
  }

  /**
   * Post Processing function for Form.
   *
   * postProcessCommon should be used to set other variables from input as the api accesses that function.
   * This function is not accessed when the api calls the report.
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

    $this->processReportMode();

    if ($this->_outputMode == 'save' || $this->_outputMode == 'copy') {
      $this->_createNew = ($this->_outputMode == 'copy');
      CRM_Report_Form_Instance::postProcess($this);
    }
    if ($this->_outputMode == 'delete') {
      CRM_Report_BAO_ReportInstance::doFormDelete($this->_id, 'civicrm/report/list?reset=1', 'civicrm/report/list?reset=1');
    }

    $this->_formValues = $this->_params;

    $this->beginPostProcessCommon();
  }

  /**
   * BeginPostProcess function run in both report mode and non-report mode (api).
   */
  public function beginPostProcessCommon() {
  }

  /**
   * Build the report query.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->buildGroupTempTable();
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->where();
    if (array_key_exists('civicrm_contribution', $this->getVar('_columns'))) {
      $this->getPermissionedFTQuery($this);
    }
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
    $this->addToDeveloperTab($sql);
    return $sql;
  }

  /**
   * Build group by clause.
   */
  public function groupBy() {
    $groupBys = array();
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys'])
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
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBys);
    }
  }

  /**
   * Build order by clause.
   */
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
   * Extract order by fields and store as an array.
   *
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
          $this->_orderByFields[$orderByField['tplField']] = $orderByField;
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
   * Determine unselected columns.
   *
   * @return array
   */
  public function unselectedSectionColumns() {
    if (is_array($this->_sections)) {
      return array_diff_key($this->_sections, $this->getSelectColumns());
    }
    else {
      return array();
    }
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
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
   * Calculate section totals.
   *
   * When "order by" fields are marked as sections, this assigns to the template
   * an array of total counts for each section. This data is used by the Smarty
   * plugin {sectionTotal}.
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
      $this->_select = "SELECT " . implode(", ", $ifnulls);
      $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($ifnulls, $sectionAliases);

      // Group (un-limited) report by all aliases and get counts. This might
      // be done more efficiently when the contents of $sql are known, ie. by
      // overriding this method in the report class.

      $query = $this->_select .
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

  /**
   * Modify column headers.
   */
  public function modifyColumnHeaders() {
    // use this method to modify $this->_columnHeaders
  }

  /**
   * Move totals columns to the right edge of the table.
   *
   * It seems like a more logical layout to have any totals columns on the far right regardless of
   * the location of the rest of their table.
   */
  public function moveSummaryColumnsToTheRightHandSide() {
    $statHeaders = (array_intersect_key($this->_columnHeaders, array_flip($this->_statFields)));
    $this->_columnHeaders = array_merge(array_diff_key($this->_columnHeaders, $statHeaders), $this->_columnHeaders, $statHeaders);
  }

  /**
   * Assign rows to the template.
   *
   * @param array $rows
   */
  public function doTemplateAssignment(&$rows) {
    $this->assign_by_ref('columnHeaders', $this->_columnHeaders);
    $this->assign_by_ref('rows', $rows);
    $this->assign('statistics', $this->statistics($rows));
  }

  /**
   * Build report statistics.
   *
   * Override this method to build your own statistics.
   *
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = array();

    $count = count($rows);
    // Why do we increment the count for rollup seems to artificially inflate the count.
    // It seems perhaps intentional to include the summary row in the count of results - although
    // this just seems odd.
    if ($this->_rollup && ($this->_rollup != '') && $this->_grandFlag) {
      $count++;
    }

    $this->countStat($statistics, $count);

    $this->groupByStat($statistics);

    $this->filterStat($statistics);

    return $statistics;
  }

  /**
   * Add count statistics.
   *
   * @param array $statistics
   * @param int $count
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
   * Add group by statistics.
   *
   * @param array $statistics
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
   * Filter statistics.
   *
   * @param array $statistics
   */
  public function filterStat(&$statistics) {
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if ((CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE ||
              CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_TIME) &&
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
        return;
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
          CRM_Utils_System::setHttpHeader('Content-type', 'image/png');
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
   * Get the sql used to generate the report.
   *
   * @return string
   */
  public function getReportSql() {
    return $this->sqlArray;
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
   *
   * Although this function is super-short it is useful to keep separate so it can be over-ridden by report classes.
   *
   * @return string
   */
  public function compileContent() {
    $templateFile = $this->getHookedTemplateFileName();
    return CRM_Utils_Array::value('report_header', $this->_formValues) .
    CRM_Core_Form::$_template->fetch($templateFile) .
    CRM_Utils_Array::value('report_footer', $this->_formValues);
  }


  /**
   * Post process function.
   */
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
   * Set limit.
   *
   * @param int $rowCount
   *
   * @return array
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    // lets do the pager if in html mode
    $this->_limit = NULL;

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }
    if ($this->addPaging) {
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
   * Set pager.
   *
   * @param int $rowCount
   */
  public function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }

    if ($this->_limit && ($this->_limit != '')) {
      if (!$this->_rowsFound) {
        $sql = "SELECT FOUND_ROWS();";
        $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
      }
      $params = array(
        'total' => $this->_rowsFound,
        'rowCount' => $rowCount,
        'status' => ts('Records') . ' %%StatusMessage%%',
        'buttonBottom' => 'PagerBottomButton',
        'buttonTop' => 'PagerTopButton',
      );
      if (!empty($this->controller)) {
        // This happens when being called from the api Really we want the api to be able to
        // pass paging parameters, but at this stage just preventing test crashes.
        $params['pageID'] = $this->get(CRM_Utils_Pager::PAGE_ID);
      }

      $pager = new CRM_Utils_Pager($params);
      $this->assign_by_ref('pager', $pager);
      $this->ajaxResponse['totalRows'] = $this->_rowsFound;
    }
  }

  /**
   * Build a group filter with contempt for large data sets.
   *
   * This function has been retained as it takes time to migrate the reports over
   * to the new method which will not crash on large datasets.
   *
   * @deprecated
   *
   * @param string $field
   * @param mixed $value
   * @param string $op
   *
   * @return string
   */
  public function legacySlowGroupFilterClause($field, $value, $op) {
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

    $contactAlias = $this->_aliases['civicrm_contact'];
    if (!empty($this->relationType) && $this->relationType == 'b_a') {
      $contactAlias = $this->_aliases['civicrm_contact_b'];
    }
    return " {$contactAlias}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added'
                          {$smartGroupQuery} ) ";
  }

  /**
   * Build where clause for groups.
   *
   * @param string $field
   * @param mixed $value
   * @param string $op
   *
   * @return string
   */
  public function whereGroupClause($field, $value, $op) {
    if ($this->groupFilterNotOptimised) {
      return $this->legacySlowGroupFilterClause($field, $value, $op);
    }
    if ($op === 'notin') {
      return " group_temp_table.id IS NULL ";
    }
    // We will have used an inner join instead.
    return "1";
  }


  /**
   * Create a table of the contact ids included by the group filter.
   *
   * This function is called by both the api (tests) and the UI.
   */
  public function buildGroupTempTable() {
    if (!empty($this->groupTempTable) || empty ($this->_params['gid_value']) || $this->groupFilterNotOptimised) {
      return;
    }
    $filteredGroups = (array) $this->_params['gid_value'];

    $groups = civicrm_api3('Group', 'get', array(
      'is_active' => 1,
      'id' => array('IN' => $filteredGroups),
      'saved_search_id' => array('>' => 0),
      'return' => 'id',
    ));
    $smartGroups = array_keys($groups['values']);

    $query = "
       SELECT group_contact.contact_id as id
       FROM civicrm_group_contact group_contact
       WHERE group_contact.group_id IN (" . implode(', ', $filteredGroups) . ")
       AND group_contact.status = 'Added' ";

    if (!empty($smartGroups)) {
      CRM_Contact_BAO_GroupContactCache::check($smartGroups);
      $smartGroups = implode(',', $smartGroups);
      $query .= "
        UNION DISTINCT
        SELECT smartgroup_contact.contact_id as id
        FROM civicrm_group_contact_cache smartgroup_contact
        WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }

    $this->groupTempTable = 'civicrm_report_temp_group_' . date('Ymd_') . uniqid();
    $this->executeReportQuery("
      CREATE TEMPORARY TABLE $this->groupTempTable
      $query
    ");
    CRM_Core_DAO::executeQuery("ALTER TABLE $this->groupTempTable ADD INDEX i_id(id)");
  }

  /**
   * Execute query and add it to the developer tab.
   *
   * @param string $query
   * @param array $params
   *
   * @return \CRM_Core_DAO|object
   */
  protected function executeReportQuery($query, $params = array()) {
    $this->addToDeveloperTab($query);
    return CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Build where clause for tags.
   *
   * @param string $field
   * @param mixed $value
   * @param string $op
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
   *
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
   * Build acl clauses.
   *
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact_a') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  /**
   * Add custom data to the columns.
   *
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

        foreach (array('fields', 'filters', 'group_bys') as $colKey) {
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
          $curFilters[$fieldName]['options'] = array('' => ts('- select -')) + CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customDAO->cf_id, array(), 'search');
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Int':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Money':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
          $curFields[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
          break;

        case 'Float':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
          break;

        case 'String':
        case 'StateProvince':
        case 'Country':
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;

          $options = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customDAO->cf_id, array(), 'search');
          if ($options !== FALSE) {
            $curFilters[$fieldName]['operatorType'] = CRM_Core_BAO_CustomField::isSerialized($customDAO) ? CRM_Report_Form::OP_MULTISELECT_SEPARATOR : CRM_Report_Form::OP_MULTISELECT;
            $curFilters[$fieldName]['options'] = $options;
          }
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

      // CRM-19401 fix
      if ($customDAO->html_type == 'Select' && !array_key_exists('options', $curFilters[$fieldName])) {
        $options = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customDAO->cf_id, array(), 'search');
        if ($options !== FALSE) {
          $curFilters[$fieldName]['operatorType'] = CRM_Core_BAO_CustomField::isSerialized($customDAO) ? CRM_Report_Form::OP_MULTISELECT_SEPARATOR : CRM_Report_Form::OP_MULTISELECT;
          $curFilters[$fieldName]['options'] = $options;
        }
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

  /**
   * Build custom data from clause.
   *
   * @param bool $joinsForFiltersOnly
   *   Only include joins to support filters. This would be used if creating a table of contacts to include first.
   */
  public function customDataFrom($joinsForFiltersOnly = FALSE) {
    if (empty($this->_customGroupExtends)) {
      return;
    }
    $mapper = CRM_Core_BAO_CustomQuery::$extendsMap;
    //CRM-18276 GROUP_CONCAT could be used with singleValueQuery and then exploded,
    //but by default that truncates to 1024 characters, which causes errors with installs with lots of custom field sets
    $customTables = array();
    $customTablesDAO = CRM_Core_DAO::executeQuery("SELECT table_name FROM civicrm_custom_group");
    while ($customTablesDAO->fetch()) {
      $customTables[] = $customTablesDAO->table_name;
    }

    foreach ($this->_columns as $table => $prop) {
      if (in_array($table, $customTables)) {
        $extendsTable = $mapper[$prop['extends']];
        // Check field is required for rendering the report.
        if ((!$this->isFieldSelected($prop)) || ($joinsForFiltersOnly && !$this->isFieldFiltered($prop))) {
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
   * Check if the field is selected.
   *
   * @param string $prop
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
    return $this->isFieldFiltered($prop);

  }

  /**
   * Check if the field is used as a filter.
   *
   * @param string $prop
   *
   * @return bool
   */
  protected function isFieldFiltered($prop) {
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
   * Check for empty order_by configurations and remove them.
   *
   * Also set template to hide them.
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
   * Check if table name has columns in SELECT clause.
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
   * Check if table name has columns in WHERE or HAVING clause.
   *
   * @param string $tableName
   *   Name of table (index of $this->_columns array).
   *
   * @return bool
   */
  public function isTableFiltered($tableName) {
    // Cause the array to be generated if not previously done.
    if (!$this->_selectedTables && !$this->filteredTables) {
      $this->selectedTables();
    }
    return in_array($tableName, $this->filteredTables);
  }

  /**
   * Fetch array of DAO tables having columns included in SELECT or ORDER BY clause.
   *
   * If the array is unset it will be built.
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
              $this->filteredTables[] = $tableName;
              break;
            }
          }
        }
      }
    }
    return $this->_selectedTables;
  }

  /**
   * Add address fields.
   *
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
          'address_name' => array(
            'title' => ts('Address Name'),
            'default' => CRM_Utils_Array::value('name', $defaults, FALSE),
            'name' => 'name',
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
      // Address filter depends on whether street address parsing is enabled.
      // (CRM-18696)
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      );
      if ($addressOptions['street_address_parsing']) {
        $street_address_filters = array(
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
        );
      }
      else {
        $street_address_filters = array(
          'street_address' => array(
            'title' => ts('Street Address'),
            'operator' => 'like',
            'name' => 'street_address',
          ),
        );
      }
      $general_address_filters = array(
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
    $addressFields['civicrm_address']['filters'] = array_merge(
        $street_address_filters,
        $general_address_filters);

    if ($orderBy) {
      $addressFields['civicrm_address']['order_bys'] = array(
        'street_name' => array('title' => ts('Street Name')),
        'street_number' => array('title' => ts('Odd / Even Street Number')),
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
   * @param array $row
   * @param array $rows
   * @param int $rowNum
   * @param string $baseUrl
   * @param string $linkText
   *
   * @return bool
   */
  public function alterDisplayAddressFields(&$row, &$rows, &$rowNum, $baseUrl, $linkText) {
    $criteriaQueryParams = CRM_Report_Utils_Report::getPreviewCriteriaQueryParams($this->_defaults, $this->_params);
    $entryFound = FALSE;
    // handle country
    if (array_key_exists('civicrm_address_country_id', $row)) {
      if ($value = $row['civicrm_address_country_id']) {
        $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        if ($baseUrl) {
          $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
            "reset=1&force=1&{$criteriaQueryParams}&" .
            "country_id_op=in&country_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_country_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_country_id_hover'] = ts("%1 for this country.",
            array(1 => $linkText)
          );
        }
      }

      $entryFound = TRUE;
    }
    if (array_key_exists('civicrm_address_county_id', $row)) {
      if ($value = $row['civicrm_address_county_id']) {
        $rows[$rowNum]['civicrm_address_county_id'] = CRM_Core_PseudoConstant::county($value, FALSE);
        if ($baseUrl) {
          $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
            "reset=1&force=1&{$criteriaQueryParams}&" .
            "county_id_op=in&county_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_county_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_county_id_hover'] = ts("%1 for this county.",
            array(1 => $linkText)
          );
        }
      }
      $entryFound = TRUE;
    }
    // handle state province
    if (array_key_exists('civicrm_address_state_province_id', $row)) {
      if ($value = $row['civicrm_address_state_province_id']) {
        $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        if ($baseUrl) {
          $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
            "reset=1&force=1&{$criteriaQueryParams}&state_province_id_op=in&state_province_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_state_province_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_state_province_id_hover'] = ts("%1 for this state.",
            array(1 => $linkText)
          );
        }
      }
      $entryFound = TRUE;
    }

    return $entryFound;
  }

  /**
   * Do AlterDisplay processing on Address Fields.
   *
   * @param array $row
   * @param array $rows
   * @param int $rowNum
   * @param string $baseUrl
   * @param string $linkText
   *
   * @return bool
   */
  public function alterDisplayContactFields(&$row, &$rows, &$rowNum, $baseUrl, $linkText) {
    $entryFound = FALSE;
    // There is no reason not to add links for all fields but it seems a bit odd to be able to click on
    // 'Mrs'. Also, we don't have metadata about the title. So, add selectively to addLinks.
    $addLinks = array('gender_id' => 'Gender');
    foreach (array('prefix_id', 'suffix_id', 'gender_id', 'contact_sub_type', 'preferred_language') as $fieldName) {
      if (array_key_exists('civicrm_contact_' . $fieldName, $row)) {
        if (($value = $row['civicrm_contact_' . $fieldName]) != FALSE) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = array();
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_Pseudoconstant::getLabel('CRM_Contact_BAO_Contact', $fieldName, $rowValue);
            }
          }
          $rows[$rowNum]['civicrm_contact_' . $fieldName] = implode(', ', $rowLabels);
          if ($baseUrl && ($title = CRM_Utils_Array::value($fieldName, $addLinks)) != FALSE) {
            $this->addLinkToRow($rows[$rowNum], $baseUrl, $linkText, $value, $fieldName, 'civicrm_contact', $title);
          }
        }
        $entryFound = TRUE;
      }
    }
    $yesNoFields = array(
      'do_not_email', 'is_deceased', 'do_not_phone', 'do_not_sms', 'do_not_mail', 'is_opt_out',
    );
    foreach ($yesNoFields as $fieldName) {
      if (array_key_exists('civicrm_contact_' . $fieldName, $row)) {
        // Since these are essentially 'negative fields' it feels like it
        // makes sense to only highlight the exceptions hence no 'No'.
        $rows[$rowNum]['civicrm_contact_' . $fieldName] = !empty($rows[$rowNum]['civicrm_contact_' . $fieldName]) ? ts('Yes') : '';
        $entryFound = TRUE;
      }
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
   * Get a standard set of contact fields.
   *
   * @return array
   */
  public function getBasicContactFields() {
    return array(
      'sort_name' => array(
        'title' => ts('Contact Name'),
        'required' => TRUE,
        'default' => TRUE,
      ),
      'id' => array(
        'no_display' => TRUE,
        'required' => TRUE,
      ),
      'prefix_id' => array(
        'title' => ts('Contact Prefix'),
      ),
      'first_name' => array(
        'title' => ts('First Name'),
      ),
      'nick_name' => array(
        'title' => ts('Nick Name'),
      ),
      'middle_name' => array(
        'title' => ts('Middle Name'),
      ),
      'last_name' => array(
        'title' => ts('Last Name'),
      ),
      'suffix_id' => array(
        'title' => ts('Contact Suffix'),
      ),
      'postal_greeting_display' => array('title' => ts('Postal Greeting')),
      'email_greeting_display' => array('title' => ts('Email Greeting')),
      'addressee_display' => array('title' => ts('Addressee')),
      'contact_type' => array(
        'title' => ts('Contact Type'),
      ),
      'contact_sub_type' => array(
        'title' => ts('Contact Subtype'),
      ),
      'gender_id' => array(
        'title' => ts('Gender'),
      ),
      'birth_date' => array(
        'title' => ts('Birth Date'),
      ),
      'age' => array(
        'title' => ts('Age'),
        'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
      ),
      'job_title' => array(
        'title' => ts('Contact Job title'),
      ),
      'organization_name' => array(
        'title' => ts('Organization Name'),
      ),
      'external_identifier' => array(
        'title' => ts('Contact identifier from external system'),
      ),
      'do_not_email' => array(),
      'do_not_phone' => array(),
      'do_not_mail' => array(),
      'do_not_sms' => array(),
      'is_opt_out' => array(),
      'is_deceased' => array(),
      'preferred_language' => array(),
    );
  }

  /**
   * Get a standard set of contact filters.
   *
   * @return array
   */
  public function getBasicContactFilters() {
    return array(
      'sort_name' => array(
        'title' => ts('Contact Name'),
      ),
      'source' => array(
        'title' => ts('Contact Source'),
        'type' => CRM_Utils_Type::T_STRING,
      ),
      'id' => array(
        'title' => ts('Contact ID'),
        'no_display' => TRUE,
      ),
      'gender_id' => array(
        'title' => ts('Gender'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
      ),
      'birth_date' => array(
        'title' => ts('Birth Date'),
        'operatorType' => CRM_Report_Form::OP_DATE,
      ),
      'contact_type' => array(
        'title' => ts('Contact Type'),
      ),
      'contact_sub_type' => array(
        'title' => ts('Contact Subtype'),
      ),
      'modified_date' => array(
        'title' => ts('Contact Modified'),
        'operatorType' => CRM_Report_Form::OP_DATE,
        'type' => CRM_Utils_Type::T_DATE,
      ),
      'is_deceased' => array(
        'title' => ts('Deceased'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'default' => 0,
      ),
      'do_not_email' => array(
        'title' => ts('Do not email'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ),
      'do_not_phone' => array(
        'title' => ts('Do not phone'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ),
      'do_not_mail' => array(
        'title' => ts('Do not mail'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ),
      'do_not_sms' => array(
        'title' => ts('Do not SMS'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ),
      'is_opt_out' => array(
        'title' => ts('Do not bulk email'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ),
      'preferred_language' => array(
        'title' => ts('Preferred Language'),
      ),
    );
  }

  /**
   * Add contact to group.
   *
   * @param int $groupID
   */
  public function add2group($groupID) {
    if (is_numeric($groupID) && isset($this->_aliases['civicrm_contact'])) {
      $select = "SELECT DISTINCT {$this->_aliases['civicrm_contact']}.id AS addtogroup_contact_id, ";

      // here are we are prepending / adding  contact id field that could be used for adding group
      // so first check for "SELECT SQL_CALC_FOUND_ROWS" and if does not exist replace "SELECT"
      if (preg_match('/^SELECT SQL_CALC_FOUND_ROWS/', $this->_select)) {
        $select = str_ireplace('SELECT SQL_CALC_FOUND_ROWS ', $select, $this->_select);
      }
      else {
        $select = str_ireplace('SELECT ', $select, $this->_select);
      }

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
   * Show charts on print screen.
   */
  public static function uploadChartImage() {
    // upload strictly for '.png' images
    $name = trim(basename(CRM_Utils_Request::retrieve('name', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET')));
    if (preg_match('/\.png$/', $name)) {

      // Get the RAW .png from the input.
      $httpRawPostData = file_get_contents("php://input");

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

  /**
   * Add link fields to the row.
   *
   * Function adds the _link & _hover fields to the row.
   *
   * @param array $row
   * @param string $baseUrl
   * @param string $linkText
   * @param string $value
   * @param string $fieldName
   * @param string $tablePrefix
   * @param string $fieldLabel
   *
   * @return mixed
   */
  protected function addLinkToRow(&$row, $baseUrl, $linkText, $value, $fieldName, $tablePrefix, $fieldLabel) {
    $criteriaQueryParams = CRM_Report_Utils_Report::getPreviewCriteriaQueryParams($this->_defaults, $this->_params);
    $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
      "reset=1&force=1&{$criteriaQueryParams}&" .
      $fieldName . "_op=in&{$fieldName}_value={$value}",
      $this->_absoluteUrl, $this->_id
    );
    $row["{$tablePrefix}_{$fieldName}_link"] = $url;
    $row["{$tablePrefix}_{$fieldName}_hover"] = ts("%1 for this %2.",
      array(1 => $linkText, 2 => $fieldLabel)
    );
  }

  /**
   * Generate temporary table to hold all contributions with permissioned FTs.
   *
   * @param object $query
   * @param string $alias
   * @param bool $return
   */
  public function getPermissionedFTQuery(&$query, $alias = NULL, $return = FALSE) {
    if (!CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    $financialTypes = NULL;
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    if (empty($financialTypes)) {
      $contFTs = "0";
      $liFTs = implode(',', array_keys(CRM_Contribute_Pseudoconstant::financialType()));
    }
    else {
      $contFTs = $liFTs = implode(',', array_keys($financialTypes));
    }
    $temp = CRM_Utils_Array::value('civicrm_line_item', $query->_aliases);
    if ($alias) {
      $query->_aliases['civicrm_line_item'] = $alias;
    }
    elseif (!$temp) {
      $query->_aliases['civicrm_line_item'] = 'civicrm_line_item_civireport';
    }
    if (empty($query->_where)) {
      $query->_where = "WHERE {$query->_aliases['civicrm_contribution']}.id IS NOT NULL ";
    }
    CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS civicrm_contribution_temp");
    $sql = "CREATE TEMPORARY TABLE civicrm_contribution_temp AS SELECT {$query->_aliases['civicrm_contribution']}.id {$query->_from}
              LEFT JOIN civicrm_line_item   {$query->_aliases['civicrm_line_item']}
                      ON {$query->_aliases['civicrm_contribution']}.id = {$query->_aliases['civicrm_line_item']}.contribution_id AND
                         {$query->_aliases['civicrm_line_item']}.entity_table = 'civicrm_contribution'
                      AND {$query->_aliases['civicrm_line_item']}.financial_type_id NOT IN (" . $liFTs . ")
              {$query->_where}
                      AND {$query->_aliases['civicrm_contribution']}.financial_type_id IN (" . $contFTs . ")
                      AND {$query->_aliases['civicrm_line_item']}.id IS NULL
              GROUP BY {$query->_aliases['civicrm_contribution']}.id";
    CRM_Core_DAO::executeQuery($sql);
    if (isset($temp)) {
      $query->_aliases['civicrm_line_item'] = $temp;
    }
    $from = " INNER JOIN civicrm_contribution_temp temp ON {$query->_aliases['civicrm_contribution']}.id = temp.id ";
    if ($return) {
      return $from;
    }
    $query->_from .= $from;
  }

  /**
   * Get label for show results buttons.
   *
   * @return string
   */
  public function getResultsLabel() {
    $showResultsLabel = $this->resultsDisplayed() ? ts('Refresh results') : ts('View results');
    return $showResultsLabel;
  }

  /**
   * Determine the output mode from the url or input.
   *
   * Output could be
   *   - pdf : Render as pdf
   *   - csv : Render as csv
   *   - print : Render in print format
   *   - save : save the report and display the new report
   *   - copy : save the report as a new instance and display that.
   *   - group : go to the add to group screen.
   *
   *  Potentially chart variations could also be included but the complexity
   *   is that we might print a bar chart as a pdf.
   */
  protected function setOutputMode() {
    $this->_outputMode = str_replace('report_instance.', '', CRM_Utils_Request::retrieve(
      'output',
      'String',
      CRM_Core_DAO::$_nullObject,
      FALSE,
      CRM_Utils_Array::value('task', $this->_params)
    ));
    // if contacts are added to group
    if (!empty($this->_params['groups']) && empty($this->_outputMode)) {
      $this->_outputMode = 'group';
    }
    if (isset($this->_params['task'])) {
      unset($this->_params['task']);
    }
  }

  /**
   * CRM-17793 - Alter DateTime section header to group by date from the datetime field.
   *
   * @param $tempTable
   * @param $columnName
   */
  public function alterSectionHeaderForDateTime($tempTable, $columnName) {
    // add new column with date value for the datetime field
    $tempQuery = "ALTER TABLE {$tempTable} ADD COLUMN {$columnName}_date VARCHAR(128)";
    CRM_Core_DAO::executeQuery($tempQuery);
    $updateQuery = "UPDATE {$tempTable} SET {$columnName}_date = date({$columnName})";
    CRM_Core_DAO::executeQuery($updateQuery);
    $this->_selectClauses[] = "{$columnName}_date";
    $this->_select .= ", {$columnName}_date";
    $this->_sections["{$columnName}_date"] = $this->_sections["{$columnName}"];
    unset($this->_sections["{$columnName}"]);
    $this->assign('sections', $this->_sections);
  }

  /**
   * Get an array of the columns that have been selected for display.
   *
   * @return array
   */
  public function getSelectColumns() {
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
    return $selectColumns;
  }

  /**
   * Add location tables to the query if they are used for filtering.
   *
   * This is for when we are running the query separately for filtering and retrieving display fields.
   */
  public function selectivelyAddLocationTablesJoinsToFilterQuery() {
    if ($this->isTableFiltered('civicrm_email')) {
      $this->_from .= "
          LEFT  JOIN civicrm_email  {$this->_aliases['civicrm_email']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id
            AND {$this->_aliases['civicrm_email']}.is_primary = 1";
    }
    if ($this->isTableFiltered('civicrm_phone')) {
      $this->_from .= "
          LEFT  JOIN civicrm_phone  {$this->_aliases['civicrm_phone']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id
            AND {$this->_aliases['civicrm_phone']}.is_primary = 1";
    }
    if ($this->isTableFiltered('civicrm_address')) {
      $this->_from .= "
          LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
          ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id)
          AND {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  /**
   * Set the base table for the FROM clause.
   *
   * Sets up the from clause, allowing for the possibility it might be a
   * temp table pre-filtered by groups if a group filter is in use.
   *
   * @param string $baseTable
   * @param string $field
   * @param null $tableAlias
   */
  public function setFromBase($baseTable, $field = 'id', $tableAlias = NULL) {
    if (!$tableAlias) {
      $tableAlias = $this->_aliases[$baseTable];
    }
    $this->_from = $this->_from = " FROM $baseTable $tableAlias ";
    $this->joinGroupTempTable($baseTable, $field, $tableAlias);
    $this->_from .= " {$this->_aclFrom} ";
  }

  /**
   * Join the temp table contacting contacts who are members of the filtered groups.
   *
   * If we are using an IN filter we use an inner join, otherwise a left join.
   *
   * @param string $baseTable
   * @param string $field
   * @param string $tableAlias
   */
  public function joinGroupTempTable($baseTable, $field, $tableAlias) {
    if ($this->groupTempTable) {
      if ($this->_params['gid_op'] == 'in') {
        $this->_from = " FROM $this->groupTempTable group_temp_table INNER JOIN $baseTable $tableAlias
        ON group_temp_table.id = $tableAlias.{$field} ";
      }
      else {
        $this->_from .= "
          LEFT JOIN $this->groupTempTable group_temp_table
          ON $tableAlias.{$field} = group_temp_table.id ";
      }
    }
  }

}
