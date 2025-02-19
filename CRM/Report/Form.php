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

use Civi\Report\OutputHandlerFactory;

/**
 * Class CRM_Report_Form
 */
class CRM_Report_Form extends CRM_Core_Form {
  /**
   * Variables smarty expects to have set.
   *
   * We ensure these are assigned (value = NULL) when Smarty is instantiated in
   * order to avoid e-notices / having to use empty or isset in the template layer.
   *
   * @var string[]
   */
  public $expectedSmartyVariables = ['pager', 'skip', 'sections', 'grandStat', 'chartEnabled', 'uniqueId', 'rows', 'group_bys_freq'];

  /**
   * Deprecated constant, Reports should be updated to use the getRowCount function.
   */
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
   * @var int
   */
  protected $_id;

  /**
   * The Number of rows to display on screen
   * @var int
   */
  protected $_rowCount;

  /**
   * The id of the report template
   *
   * @var int
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
  protected $_columns = [];

  /**
   * The set of filters in the report
   *
   * @var array
   */
  protected $_filters = [];

  /**
   * The set of optional columns in the report
   *
   * @var array
   */
  public $_options = [];

  /**
   * By default most reports hide contact id.
   * Setting this to true makes it available
   * @var bool
   */
  protected $_exposeContactID = TRUE;

  /**
   * Set of statistic fields
   *
   * @var array
   */
  protected $_statFields = [];

  /**
   * Set of statistics data
   *
   * @var array
   */
  protected $_statistics = [];

  /**
   * List of fields not to be repeated during display
   *
   * @var array
   */
  protected $_noRepeats = [];

  /**
   * List of fields not to be displayed
   *
   * @var array
   */
  protected $_noDisplay = [];

  /**
   * Object type that a custom group extends
   *
   * @var null
   */
  protected $_customGroupExtends = NULL;
  protected $_customGroupExtendsJoin = [];
  protected $_customGroupFilters = TRUE;
  protected $_customGroupGroupBy = FALSE;
  protected $_customGroupJoin = 'LEFT JOIN';

  /**
   * Build tags filter
   * @var bool
   */
  protected $_tagFilter = FALSE;

  /**
   * specify entity table for tags filter
   * @var string
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
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Navigation fields
   *
   * @var array
   */
  public $_navigation = [];

  public $_drilldownReport = [];

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
  protected $tabs = [];

  /**
   * Should we add paging.
   *
   * @var bool
   */
  protected $addPaging = TRUE;

  protected $isForceGroupBy = FALSE;

  protected $groupConcatTested = FALSE;

  /**
   * Are we in print mode. Can be set by another outputMode, ex: sendmail.
   *
   * @var bool
   */
  public $printOnly = FALSE;

  /**
   * An attribute for checkbox/radio form field layout
   *
   * @var array
   */
  protected $_fourColumnAttribute = [
    '</td><td width="25%">',
    '</td><td width="25%">',
    '</td><td width="25%">',
    '</tr><tr><td>',
  ];

  protected $_force = 1;

  protected $_params = NULL;
  protected $_formValues = NULL;
  protected $_instanceValues = NULL;

  protected $_instanceForm = FALSE;
  protected $_criteriaForm = FALSE;

  protected $_instanceButtonName = NULL;
  protected $_createNewButtonName = NULL;
  protected $_groupButtonName = NULL;
  protected $_chartButtonName = NULL;
  protected $_csvSupported = TRUE;
  protected $_add2groupSupported = TRUE;
  protected $_groups = NULL;
  protected $_grandFlag = FALSE;
  protected $_rowsFound;

  /**
   * @var array
   */
  protected $_selectAliases = [];
  protected $_rollup;

  /**
   * Table containing list of contact IDs within the group filter.
   *
   * @var string
   */
  protected $groupTempTable = '';

  /**
   * Table aliases. May be altered by hook_civicrm_alterReportVar.
   * @var array
   */
  public $_aliases = [];

  /**
   * SQL where clause. May be altered by hook_civicrm_alterReportVar.
   * @var string
   */
  public $_where;

  /**
   * SQL from clause. May be altered by hook_civicrm_alterReportVar.
   * @var string
   */
  public $_from;

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
   * @var int
   */
  protected $_limitValue = NULL;

  /**
   * This can be set to specify row offset
   * See notes on _limitValue
   *
   * @var int
   */
  protected $_offsetValue = NULL;

  /**
   * @var array
   */
  protected $_sections = [];

  protected $_autoIncludeIndexedFieldsAsOrderBys = 0;

  /**
   * Whether to generate absolute URLs (ex: in report emails).
   *
   * @var bool
   */
  public $_absoluteUrl = FALSE;

  /**
   * Flag to indicate if result-set is to be stored in a class variable which could be retrieved using getResultSet() method.
   *
   * @var bool
   */
  protected $_storeResultSet = FALSE;

  /**
   * When _storeResultSet Flag is set use this var to store result set in form of array
   *
   * @var bool
   */
  protected $_resultSet = [];

  /**
   * To what frequency group-by a date column
   *
   * @var array
   */
  protected $_groupByDateFreq = [
    'MONTH' => 'Month',
    'YEARWEEK' => 'Week',
    'QUARTER' => 'Quarter',
    'YEAR' => 'Year',
  ];

  /**
   * Variables to hold the acl inner join and where clause
   * @var string|null
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
  protected $_selectedTables = [];

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
   * @var string|null
   */
  protected $_format;

  public $_having = NULL;
  public $_select = NULL;
  public $_selectClauses = [];
  public $_columnHeaders = [];
  public $_orderBy = NULL;
  public $_orderByFields = [];
  public $_orderByArray = [];
  /**
   * Array of clauses to group by.
   *
   * @var array
   */
  protected $_groupByArray = [];
  public $_groupBy = NULL;
  public $_whereClauses = [];
  public $_havingClauses = [];

  /**
   * DashBoardRowCount Dashboard row count.
   *
   * @var int
   */
  public $_dashBoardRowCount;

  /**
   * Is this being called without a form controller (ie. the report is being render outside the normal form
   * - e.g the api is retrieving the rows.
   *
   * @var bool
   */
  public $noController = FALSE;

  /**
   * Variable to hold the currency alias.
   *
   * @var string|null
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
   * Report description.
   *
   * @var string
   */
  public $_description;

  /**
   * Is an address field selected.
   *
   * @var bool
   *   This was intended to determine if the address table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_addressField;

  /**
   * Is an email field selected.
   *
   * @var bool
   *   This was intended to determine if the email table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_emailField;

  /**
   * Is a phone field selected.
   *
   * @var bool
   *   This was intended to determine if the phone table should be joined in
   *   The isTableSelected function is now preferred for this purpose
   */
  protected $_phoneField;

  /**
   * Create new report instance? (or update existing) on save.
   *
   * @var bool
   */
  protected $_createNew;

  /**
   *  When a grand total row has calculated the status we pop it off to here.
   *
   * This allows us to access it from the stats function and avoid recalculating.
   *
   * @var array
   */
  protected $rollupRow = [];

  /**
   * Database attributes - character set and collation.
   *
   * @var string
   */
  protected $_databaseAttributes = ' DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';

  /**
   * SQL being run in this report.
   *
   * The sql in the report is stored in this variable in order to be displayed on the developer tab.
   *
   * @var string
   */

  protected $sql;

  /**
   * An instruction not to add a Group By.
   *
   * This is relevant where the group by might be otherwise added after the code that determines the group by array.
   *
   * e.g. where stat fields are being added but other settings cause it to not be desirable to add a group by
   * such as in pivot charts when no row header is set
   *
   * @var bool
   */
  protected $noGroupBy = FALSE;

  /**
   * SQL being run in this report as an array.
   *
   * The sql in the report is stored in this variable in order to be returned to api & test calls.
   *
   * @var array
   */
  protected $sqlArray;

  /**
   * Tables created for the report that need removal afterwards.
   *
   * ['civicrm_temp_report_x' => ['temporary' => TRUE, 'name' => 'civicrm_temp_report_x']
   * @var array
   */
  protected $temporaryTables = [];

  /**
   * Can this report use the sql mode ONLY_FULL_GROUP_BY.
   * @var bool
   */
  public $optimisedForOnlyFullGroupBy = TRUE;

  /**
   * Determines which chart types are supported for this report
   * @var string[]
   */
  protected $_charts = [];

  /**
   * Array of campaign data,
   * populated by calling `$this::addCampaignFields()`
   *
   * @var array
   */
  protected $campaigns = [];

  /**
   * @var \Civi\Report\OutputHandlerInterface
   */
  private $outputHandler;

  /**
   * Get the number of rows to show
   * @return int
   */
  public function getRowCount(): int {
    return $this->_rowCount;
  }

  /**
   * set the number of rows to show
   * @param int $rowCount
   */
  public function setRowCount($rowCount): void {
    $this->_rowCount = $rowCount;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->setRowCount(\Civi::settings()->get('default_pager_size'));

    $this->addClass('crm-report-form');

    if ($this->_tagFilter) {
      $this->buildTagFilter();
    }
    if ($this->_exposeContactID) {
      if (array_key_exists('civicrm_contact', $this->_columns)) {
        $this->_columns['civicrm_contact']['fields']['exposed_id'] = [
          'name' => 'id',
          'title' => ts('Contact ID'),
          'no_repeat' => TRUE,
        ];
      }
    }

    if ($this->_groupFilter) {
      $this->buildGroupFilter();
    }

    $this->addCustomDataToColumns();

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
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean');
    // Ensure smarty variables are assigned here since this function is called from
    // the report api and the main buildForm is not.
    self::$_template->ensureVariablesAreAssigned($this->expectedSmartyVariables);
    $this->_dashBoardRowCount = CRM_Utils_Request::retrieve('rowCount', 'Integer') ?? CRM_Utils_Request::retrieve('crmRowCount', 'Integer');

    $this->_section = CRM_Utils_Request::retrieve('section', 'Integer');

    $this->assign('section', $this->_section);
    CRM_Core_Region::instance('page-header')->add([
      'markup' => sprintf('<!-- Report class: [%s] -->', htmlentities(get_class($this))),
    ]);
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
      if (!isset($_SERVER['QUERY_STRING'])) {
        // in php 7.4 can do this with less lines with ??=
        $_SERVER['QUERY_STRING'] = '';
      }
      $_SERVER['QUERY_STRING'] .= "&qfKey={$this->controller->_key}";
    }

    if ($this->_id) {
      $this->assign('instanceId', $this->_id);
      $params = ['id' => $this->_id];
      $this->_instanceValues = [];
      CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
        $params,
        $this->_instanceValues
      );
      if (empty($this->_instanceValues)) {
        CRM_Core_Error::statusBounce("Report could not be loaded.");
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

      $formValues = $this->_instanceValues['form_values'] ?? NULL;
      if ($formValues) {
        $this->_formValues = CRM_Utils_String::unserialize($formValues);
      }
      else {
        $this->_formValues = NULL;
      }

      $this->setOutputMode();

      if ($this->_outputMode === 'copy') {
        $this->_createNew = TRUE;
        $this->_params = $this->_formValues;
        $this->_params['view_mode'] = 'criteria';
        $this->_params['title'] = $this->getTitle() . ts(' (copy created by %1 on %2)', [
          CRM_Core_Session::singleton()->getLoggedInContactDisplayName(),
          CRM_Utils_Date::customFormat(date('Y-m-d H:i')),
        ]);
        // Do not pass go. Do not collect another chance to re-run the same query.
        CRM_Report_Form_Instance::postProcess($this);
      }

      // lets always do a force if reset is found in the url.
      // Hey why not? see CRM-17225 for more about this. The use of reset to be force is historical for reasons stated
      // in the comment line above these 2.
      if (!empty($_REQUEST['reset'])
          && !in_array(CRM_Utils_Request::retrieve('output', 'String'), ['save', 'criteria'])) {
        $this->_force = 1;
      }

      // set the mode
      $this->assign('mode', 'instance');
    }
    elseif (!$this->noController) {
      [$optionValueID, $optionValue] = CRM_Report_Utils_Report::getValueIDFromUrl();
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
      (!empty($this->_instanceValues['is_reserved']) &&
        !CRM_Core_Permission::check('administer reserved reports'))
    ) {
      $this->_instanceForm = FALSE;
    }

    $this->assign('criteriaForm', FALSE);
    // Will be overwritten in postProcess if TRUE.
    $this->assign('printOnly', FALSE);
    // Display Report Criteria section if user has access Report Criteria OR administer Reports AND report instance is not reserved
    if (CRM_Core_Permission::check('administer Reports') ||
      CRM_Core_Permission::check('access Report Criteria')
    ) {
      if (empty($this->_instanceValues['is_reserved']) ||
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

    // graphs require the visual bundle
    \Civi::resources()->addBundle('visual');
  }

  /**
   * Add bread crumb.
   */
  public function addBreadCrumb() {
    $breadCrumbs
      = [
        [
          'title' => ts('Report Templates'),
          'url' => CRM_Utils_System::url('civicrm/admin/report/template/list', 'reset=1'),
        ],
      ];

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
      $this->setTableAlias($table, $tableName);

      $expFields = [];
      // higher preference to bao object
      $daoOrBaoName = $table['bao'] ?? $table['dao'] ?? NULL;

      if ($daoOrBaoName) {
        if (method_exists($daoOrBaoName, 'exportableFields')) {
          $expFields = $daoOrBaoName::exportableFields();
        }
        else {
          $expFields = $daoOrBaoName::export();
        }
      }

      $doNotCopy = ['required', 'default'];

      $fieldGroups = ['fields', 'filters', 'group_bys', 'order_bys'];
      foreach ($fieldGroups as $fieldGrp) {
        if (!empty($table[$fieldGrp]) && is_array($table[$fieldGrp])) {
          foreach ($table[$fieldGrp] as $fieldName => $field) {
            // $name is the field name used to reference the BAO/DAO export fields array
            $name = $field['name'] ?? $fieldName;

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
            $alias = $field['alias'] ?? (
              $this->_columns[$tableName]['alias'] ?? $tableName
            );
            $this->_columns[$tableName][$fieldGrp][$fieldName]['alias'] = $alias;

            // set name = fieldName, unless already set
            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['name'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['name'] = $name;
            }

            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['table_name'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['table_name'] = $tableName;
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
                switch ($this->_columns[$tableName][$fieldGrp][$fieldName]['type'] ?? NULL) {
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
                        = [
                          '' => ts('Any'),
                          '0' => ts('No'),
                          '1' => ts('Yes'),
                        ];
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
            if (!isset($this->_columns[$tableName]['metadata'][$fieldName])) {
              $this->_columns[$tableName]['metadata'][$fieldName] = $this->_columns[$tableName][$fieldGrp][$fieldName];
            }
            else {
              $this->_columns[$tableName]['metadata'][$fieldName] = array_merge($this->_columns[$tableName][$fieldGrp][$fieldName], $this->_columns[$tableName]['metadata'][$fieldName]);
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
    $freezeGroup = [];

    // FIXME: generalizing form field naming conventions would reduce
    // Lots of lines below.
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (empty($field['no_display'])) {
            if (!empty($field['required'])) {
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
            if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE
            ) {
              if (is_array($field['default'])) {
                $this->_defaults["{$fieldName}_from"] = $field['default']['from'] ?? NULL;
                $this->_defaults["{$fieldName}_to"] = $field['default']['to'] ?? NULL;
                $this->_defaults["{$fieldName}_relative"] = 0;
              }
              else {
                $this->_defaults["{$fieldName}_relative"] = $field['default'];
              }
            }
            else {
              if ((($field['type'] ?? NULL) & CRM_Utils_Type::T_INT) && is_array($field['default'])) {
                $this->_defaults["{$fieldName}_min"] = $field['default']['min'] ?? NULL;
                $this->_defaults["{$fieldName}_max"] = $field['default']['max'] ?? NULL;
              }
              $this->_defaults["{$fieldName}_value"] = $field['default'];
            }
          }
          //assign default value as "in" for multiselect
          //operator, To freeze the select element
          if (($field['operatorType'] ?? NULL) ==
            CRM_Report_Form::OP_MULTISELECT
          ) {
            $this->_defaults["{$fieldName}_op"] = 'in';
          }
          if (($field['operatorType'] ?? NULL) ==
            CRM_Report_Form::OP_ENTITYREF
          ) {
            $this->_defaults["{$fieldName}_op"] = 'in';
          }
          elseif (($field['operatorType'] ?? NULL) ==
            CRM_Report_Form::OP_MULTISELECT_SEPARATOR
          ) {
            $this->_defaults["{$fieldName}_op"] = 'mhas';
          }
          elseif (!empty($field['default_op'])) {
            $this->_defaults["{$fieldName}_op"] = $field['default_op'];
          }
        }
      }

      if (
        empty($this->_formValues['order_bys']) &&
        (array_key_exists('order_bys', $table) &&
        is_array($table['order_bys']))
      ) {
        if (!array_key_exists('order_bys', $this->_defaults)) {
          $this->_defaults['order_bys'] = [];
        }
        foreach ($table['order_bys'] as $fieldName => $field) {
          if (!empty($field['default']) || !empty($field['default_order']) ||
            !empty($field['default_is_section']) ||
            !empty($field['default_weight'])
          ) {
            $order_by = [
              'column' => $fieldName,
              'order' => $field['default_order'] ?? 'ASC',
              'section' => $field['default_is_section'] ?? 0,
            ];

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
   * @return object|bool
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
   * Getter for $_params.
   *
   * @return void|array $params
   */
  public function getParams() {
    return $this->_params;
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
   * Getter for $_id.
   *
   * @return int
   */
  public function getID() {
    return $this->_id;
  }

  /**
   * Getter for _outputMode
   *
   * Note you can implement hook_civicrm_alterReportVar('actions', ...)
   * which indirectly allows setting _outputMode if the user chooses
   * your action.
   *
   * @return string
   */
  public function getOutputMode():string {
    return $this->_outputMode;
  }

  /**
   * Getter for report header form field value
   *
   * @return string
   */
  public function getReportHeader():string {
    return $this->_formValues['report_header'] ?? '';
  }

  /**
   * Getter for report footer form field value
   *
   * @return string
   */
  public function getReportFooter():string {
    return $this->_formValues['report_footer'] ?? '';
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
   * @return array
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return ['report_header', 'report_footer'];
  }

  /**
   * Remove any temporary tables.
   */
  public function cleanUpTemporaryTables() {
    foreach ($this->temporaryTables as $temporaryTable) {
      CRM_Core_DAO::executeQuery('DROP ' . ($temporaryTable['temporary'] ? 'TEMPORARY' : '') . ' TABLE IF EXISTS ' . $temporaryTable['name']);
    }
  }

  /**
   * Create a temporary table.
   *
   * This function creates a table AND adds the details to the developer tab & $this->>temporary tables.
   *
   * @param string $identifier
   *   This is the key that will be used for the table in the temporaryTables property.
   * @param string $sql
   *   Sql select statement or column description (the latter requires the columns flag)
   * @param bool $isColumns
   *   Is the sql describing columns to create (rather than using a select query).
   * @param bool $isMemory
   *   Create a memory table rather than a normal INNODB table.
   *
   * @return string
   */
  public function createTemporaryTable($identifier, $sql, $isColumns = FALSE, $isMemory = FALSE) {
    $tempTable = CRM_Utils_SQL_TempTable::build();
    if ($isMemory) {
      $tempTable->setMemory();
    }
    if ($isColumns) {
      $tempTable->createWithColumns($sql);
    }
    else {
      $tempTable->createWithQuery($sql);
    }
    $name = $tempTable->getName();
    // Developers may force tables to be durable to assist in debugging so lets check.
    $isNotTrueTemporary = $tempTable->isDurable();
    $this->addToDeveloperTab($tempTable->getCreateSql());
    $this->temporaryTables[$identifier] = ['temporary' => !$isNotTrueTemporary, 'name' => $name];
    return $name;
  }

  /**
   * Add columns to report.
   */
  public function addColumns() {
    $options = [];
    $colGroups = NULL;
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          $groupTitle = '';
          if (empty($field['no_display'])) {
            foreach (['table', 'field'] as $var) {
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
            // Having a group_title is secret code for being a custom group
            // which cryptically translates to needing an accordion.
            // here we make that explicit.
            $colGroups[$tableName]['use_accordian_for_field_selection'] = !$groupTitle && isset($table['group_title']);

            if (!$groupTitle) {
              $groupTitle = $table['group_title'] ?? NULL;
            }

            $colGroups[$tableName]['fields'][$fieldName] = $field['title'] ?? NULL;
            if ($groupTitle && empty($colGroups[$tableName]['group_title'])) {
              $colGroups[$tableName]['group_title'] = $groupTitle;
            }
            $options[$fieldName] = $field['title'] ?? NULL;
          }
        }
      }
    }

    $this->addCheckBox("fields", ts('Select Columns'), $options, NULL,
      NULL, NULL, NULL, $this->_fourColumnAttribute, TRUE
    );
    if (!empty($colGroups)) {
      $this->tabs['FieldSelection'] = [
        'title' => ts('Columns'),
        'tpl' => 'FieldSelection',
        'div_label' => 'col-groups',
      ];

      // Note this assignment is only really required in buildForm. It is being 'over-called'
      // to reduce risk of being missed due to overridden functions.
      $this->assign('tabs', $this->tabs);
    }

    $this->assign('colGroups', $colGroups);
  }

  /**
   * Add filters to report.
   *
   * @throws \CRM_Core_Exception
   */
  public function addFilters() {
    $filters = $filterGroups = [];

    foreach ($this->_filters as $table => $attributes) {
      $groupingKey = $this->_columns[$table]['grouping'] ?? '';
      $filterGroups[$groupingKey]['tables'][$table] = [];
      // If a prior table hasn't set group title then set it.
      if (empty($filterGroups[$groupingKey]['group_title'])) {
        $filterGroups[$groupingKey]['group_title'] = $this->_columns[$table]['group_title'] ?? '';
      }
      // The presence of 'group_title' is secret code for 'display in an accordion'
      // here we make this explicit.
      if (!isset($filterGroups[$groupingKey]['use_accordion_for_field_selection'])) {
        if (isset($this->_columns[$table]['use_accordion_for_field_selection'])) {
          $filterGroups[$groupingKey]['use_accordion_for_field_selection'] = $this->_columns[$table]['use_accordion_for_field_selection'];
        }
        else {
          $filterGroups[$groupingKey]['use_accordion_for_field_selection'] = isset($this->_columns[$table]['group_title']);
        }
      }

      foreach ($attributes as $fieldName => $field) {
        if (empty($field['operatorType'])) {
          $field['operatorType'] = '';
        }
        $field['no_display'] ??= FALSE;
        $filterGroups[$groupingKey]['tables'][$table][$fieldName] = $field;
        // Filters is deprecated in favour of filterGroups.
        $filters[$table][$fieldName] = $field;
        // get ready with option value pair
        // @ todo being able to specific options for a field (e.g a date field) in the field spec as an array rather than an override
        // would be useful
        $operations = $this->getOperationPair(
          $field['operatorType'],
          $fieldName);

        switch ($field['operatorType']) {
          case CRM_Report_Form::OP_MONTH:
            if (!array_key_exists('options', $field) ||
              !is_array($field['options']) || empty($field['options'])
            ) {
              // If there's no option list for this filter, define one.
              $field['options'] = CRM_Utils_Date::getFullMonthNames();

              // Add this option list to this column _columns. This is
              // required so that filter statistics show properly.
              $this->_columns[$table]['filters'][$fieldName]['options'] = $field['options'];
            }
          case CRM_Report_Form::OP_MULTISELECT:
          case CRM_Report_Form::OP_MULTISELECT_SEPARATOR:
            // assume a multi-select field
            if (!empty($field['options']) ||
              $fieldName === 'state_province_id' || $fieldName === 'county_id'
            ) {
              $element = $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations,
                ['onchange' => "return showHideMaxMinVal( '$fieldName', this.value );"]
              );

              if (count($operations) <= 1) {
                $element->freeze();
              }
              if ($fieldName === 'state_province_id' ||
                $fieldName === 'county_id'
              ) {
                $this->addChainSelect($fieldName . '_value', [
                  'multiple' => TRUE,
                  'label' => NULL,
                  'class' => 'huge',
                ]);
              }
              else {
                $this->addElement('select', "{$fieldName}_value", NULL, $field['options'], [
                  'style' => 'min-width:250px',
                  'class' => 'crm-select2 huge',
                  'multiple' => TRUE,
                  'placeholder' => ts('- select -'),
                ]);
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
            $this->addDatePickerRange($fieldName, $field['title'], FALSE, FALSE, 'From', 'To', $operations, '_to', '_from');
            break;

          case CRM_Report_Form::OP_DATETIME:
            // build datetime fields
            $this->addDatePickerRange($fieldName, $field['title'], TRUE, FALSE, 'From', 'To', $operations, '_to', '_from');
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
              ['onchange' => "return showHideMaxMinVal( '$fieldName', this.value );", 'title' => ts('%1 Filter Operator', [1 => $field['title']])]
            );
            // we need text box for value input
            $this->add('text', "{$fieldName}_value", NULL, ['class' => 'huge']);
            break;
        }
      }
    }
    if (!empty($filterGroups)) {
      $this->tabs['Filters'] = [
        'title' => ts('Filters'),
        'tpl' => 'Filters',
        'div_label' => 'set-filters',
      ];
    }
    $this->assign('filterGroups', $filterGroups);
    // Filters is deprecated in favour of filterGroups.
    $this->assign('filters', $filters);
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
    $order = [
      'FieldSelection',
      'GroupBy',
      'OrderBy',
      'ReportOptions',
      'Filters',
    ];
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
  public function addToDeveloperTab(string $sql): void {
    if (!CRM_Core_Permission::check('view report sql')) {
      return;
    }
    $ignored_output_modes = ['pdf', 'csv', 'print'];
    if (in_array($this->_outputMode, $ignored_output_modes)) {
      return;
    }
    $this->tabs['Developer'] = [
      'title' => ts('Developer'),
      'tpl' => 'Developer',
      'div_label' => 'set-developer',
    ];

    $this->assignTabs();
    $sqlFormattedArray = [];
    $this->sqlArray[] = $sql;
    foreach ($this->sqlArray as $sql) {
      foreach (['LEFT JOIN'] as $term) {
        $sql = str_replace($term, '<br>  ' . $term, ($sql ?? ''));
      }
      foreach (['FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT', ';'] as $term) {
        $sql = str_replace($term, '<br><br>' . $term, ($sql ?? ''));
      }
      $sqlFormattedArray[] = $sql;
      $this->assign('sql', implode(';<br><br><br><br>', $sqlFormattedArray));
    }
    $this->assign('sqlModes', $sqlModes = CRM_Utils_SQL::getSqlModes());

  }

  /**
   * Add options defined in $this->_options to the report.
   */
  public function addOptions() {
    if (!empty($this->_options)) {
      // FIXME: For now lets build all elements as checkboxes.
      // Once we clear with the format we can build elements based on type

      foreach ($this->_options as $fieldName => $field) {
        $options = [];

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
      $this->tabs['ReportOptions'] = [
        'title' => ts('Display Options'),
        'tpl' => 'ReportOptions',
        'div_label' => 'other-options',
      ];
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
      $this->addElement('xbutton', $this->_chartButtonName, ts('View'), ['type' => 'submit']);
    }
  }

  /**
   * Add group by options to the report.
   */
  public function addGroupBys() {
    $options = $freqElements = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($field) && empty($field['no_display'])) {
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
      $this->tabs['GroupBy'] = [
        'title' => ts('Grouping'),
        'tpl' => 'GroupBy',
        'div_label' => 'group-by-elements',
      ];
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
    $options = [];
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
      $this->tabs['OrderBy'] = [
        'title' => ts('Sorting'),
        'tpl' => 'OrderBy',
        'div_label' => 'order-by-elements',
      ];
    }

    if (!empty($options)) {
      $options = [
        '-' => ts(' - none - '),
      ] + $options;
      for ($i = 1; $i <= 5; $i++) {
        $this->addElement('select', "order_bys[{$i}][column]", ts('Order by Column'), $options);
        $this->addElement('select', "order_bys[{$i}][order]", ts('Order by Order'), [
          'ASC' => ts('Ascending'),
          'DESC' => ts('Descending'),
        ]);
        $this->addElement('checkbox', "order_bys[{$i}][section]", ts('Order by Section'), FALSE, ['id' => "order_by_section_$i", 'title' => ts('Order by Section %1', [1 => $i])]);
        $this->addElement('checkbox', "order_bys[{$i}][pageBreak]", ts('Page Break'), FALSE, ['id' => "order_by_pagebreak_$i", 'title' => ts('Page Break %1', [1 => $i])]);
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
    if ($this->_instanceForm && $permission == CRM_Core_Permission::EDIT &&
      $this->_add2groupSupported
    ) {
      $this->addElement('select', 'groups', ts('Group'),
        ['' => ts('Add Contacts to Group')] +
        CRM_Core_PseudoConstant::nestedGroup(),
        ['class' => 'crm-select2 crm-action-menu fa-plus huge', 'title' => ts('Add Contacts to Group')]
      );
      $this->assign('group', TRUE);
    }

    $this->addElement('xbutton', $this->_groupButtonName, '', [
      'type' => 'submit',
      'style' => 'display: none;',
    ]);

    $this->addChartOptions();
    $showResultsLabel = $this->getResultsLabel();
    $this->addButtons([
        [
          'type' => 'submit',
          'name' => $showResultsLabel,
          'isDefault' => TRUE,
        ],
    ]);
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
      $actions['report_instance.save'] = [
        'title' => ts('Create Report'),
        'data' => [
          'is_confirm' => TRUE,
          'confirm_title' => ts('Create Report'),
          'confirm_refresh_fields' => json_encode([
            'title' => ['selector' => '.crm-report-instanceForm-form-block-title', 'prepend' => ''],
            'description' => ['selector' => '.crm-report-instanceForm-form-block-description', 'prepend' => ''],
          ]),
        ],
      ];
      unset($actions['report_instance.delete']);
    }

    if (!$this->_csvSupported) {
      unset($actions['report_instance.csv']);
    }

    CRM_Utils_Hook::alterReportVar('actions', $actions, $this);

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
    if (is_callable([
      $this,
      'formRule',
    ])) {
      $this->addFormRule([get_class($this), 'formRule'], $this);
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
  public function customDataFormRule($fields, $ignoreFields = []) {
    $errors = [];
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
              $errors['fields'] = ts("Please make sure fields selected in 'Group by Columns' section are also selected in 'Display Columns' section.");
            }
            elseif (array_key_exists($fieldName, $fields['group_bys'])) {
              foreach ($fields['fields'] as $fld => $val) {
                if (!array_key_exists($fld, $fields['group_bys']) &&
                  !in_array($fld, $ignoreFields)
                ) {
                  $errors['fields'] = ts("Please ensure that fields selected in 'Display Columns' are also selected in 'Group by Columns' section.");
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
   * @param int|string $type
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

        $result = [
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
        ];
        return $result;

      case CRM_Report_Form::OP_SELECT:
        $result = [
          'eq' => ts('Is equal to'),
        ];
        return $result;

      case CRM_Report_Form::OP_MONTH:
      case CRM_Report_Form::OP_MULTISELECT:
      case CRM_Report_Form::OP_ENTITYREF:

        $result = [
          'in' => ts('Is one of'),
          'notin' => ts('Is not one of'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
        return $result;

      case CRM_Report_Form::OP_DATE:

        $result = [
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
        return $result;

      case CRM_Report_Form::OP_MULTISELECT_SEPARATOR:
        // use this operator for the values, concatenated with separator. For e.g if
        // multiple options for a column is stored as ^A{val1}^A{val2}^A
        $result = [
          'mhas' => ts('Is one of'),
          'mnot' => ts('Is not one of'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
        return $result;

      default:
        // type is string
        $result = [
          'has' => ts('Contains'),
          'sw' => ts('Starts with'),
          'ew' => ts('Ends with'),
          'nhas' => ts('Does not contain'),
          'eq' => ts('Is equal to'),
          'neq' => ts('Is not equal to'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
        return $result;
    }
  }

  /**
   * Build the tag filter field to display on the filters tab.
   */
  public function buildTagFilter() {
    $contactTags = CRM_Core_BAO_Tag::getTags($this->_tagFilterTable);
    if (!empty($contactTags)) {
      $this->_columns['civicrm_tag'] = [
        'dao' => 'CRM_Core_DAO_Tag',
        'filters' => [
          'tagid' => [
            'name' => 'tag_id',
            'title' => ts('Tag'),
            'type' => CRM_Utils_Type::T_INT,
            'tag' => TRUE,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contactTags,
          ],
        ],
      ];
    }
  }

  /**
   * Adds group filters to _columns (called from _Construct).
   */
  public function buildGroupFilter() {
    $this->_columns['civicrm_group']['filters'] = [
      'gid' => [
        'name' => 'group_id',
        'title' => ts('Group'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'group' => TRUE,
        'options' => CRM_Core_PseudoConstant::nestedGroup(),
      ],
    ];
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

    $type = CRM_Utils_Type::typeToString($field['type'] ?? NULL);

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
          $clauses = [];
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
          if (!str_contains($value, '%')) {
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
          if (($field['type'] ?? NULL) ==
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
          if (!str_contains($value, '%')) {
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
        if ($type == 'String') {
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP OR {$field['dbAlias']} = '' )";
        }
        else {
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP )";
        }
        break;

      case 'nnll':
        if ($type == 'String') {
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP AND {$field['dbAlias']} <> '' )";
        }
        else {
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP )";
        }
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

    //dev/core/544 Add report support for multiple contact subTypes
    if ($field['name'] == 'contact_sub_type' && $clause) {
      $clause = $this->whereSubtypeClause($field, $value, $op);
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
   * Get SQL where clause for contact subtypes
   * @param array $field Field specifications
   * @param mixed $value
   * @param string $op SQL Operator
   *
   * @return string
   */
  public function whereSubtypeClause($field, $value, $op) {
    // Get the correct SQL operator.
    $orNull = FALSE;
    switch ($op) {
      case 'notin':
        $op = 'nhas';
        $clauseSeparator = ' AND ';
        $orNull = TRUE;
        break;

      case 'in':
        $op = 'has';
        $clauseSeparator = ' OR ';
        break;
    }
    $sqlOp = $this->getSQLOperator($op);
    if ($sqlOp == 'IS NULL' || $sqlOp == 'IS NOT NULL') {
      $clause = "{$field['dbAlias']} $sqlOp";
    }
    else {
      $subclauses = [];
      foreach ($value as $item) {
        $subclauses[] = "( {$field['dbAlias']} $sqlOp '%" . CRM_Core_DAO::VALUE_SEPARATOR . $item . CRM_Core_DAO::VALUE_SEPARATOR . "%' )";
      }
      $clause = implode($clauseSeparator, $subclauses);
    }
    $clause = "( $clause )";
    if ($orNull) {
      $clause = "( ( {$field['dbAlias']} IS NULL ) OR $clause )";
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
    $clauses = [];
    if (array_key_exists($relative, $this->getOperationPair(CRM_Report_Form::OP_DATE))) {
      $sqlOP = $this->getSQLOperator($relative);
      return "( {$fieldName} {$sqlOP} )";
    }
    if (strlen($to ?? '') === 10) {
      // If we just have the date we assume the end of that day.
      $to .= ' 23:59:59';
    }

    if ($relative) {
      [$from, $to] = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);
    }

    if ($from) {
      $clauses[] = "( {$fieldName} >= " . date('YmdHis', strtotime($from)) . ')';
    }

    if ($to) {
      $clauses[] = "( {$fieldName} <= " . date('YmdHis', strtotime($to)) . ')';
    }
    if (!empty($clauses)) {
      return implode(' AND ', $clauses);
    }

    return NULL;
  }

  /**
   * Get values for from and to for date ranges.
   *
   * @deprecated
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
    if (!empty($fromTime) || !empty($toTime)) {
      throw new CRM_Core_Exception('Report template needs to be updated as getFromTo no longer supports fromTime or ToTime Parameters');
    }
    return CRM_Utils_Date::getFromTo($relative, $from, $to);
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

    $customFields = [];
    $customFieldIds = [];
    foreach ($this->_params['fields'] as $fieldAlias => $value) {
      if ($fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
        $customFieldIds[$fieldAlias] = $fieldId;
      }
    }
    if (empty($customFieldIds)) {
      return;
    }

    $customGroups = CRM_Core_BAO_CustomGroup::getAll(['is_active' => TRUE, 'extends' => $this->_customGroupExtends]);
    foreach ($customGroups as $customGroup) {
      foreach ($customGroup['fields'] as $field) {
        if (
          in_array($field['id'], $customFieldIds) &&
          // skip for type date and ContactReference since date format is already handled
          !in_array($field['data_type'], ['ContactReference', 'Date'])
        ) {
          $customFields[$customGroup['table_name'] . '_custom_' . $field['id']] = $field['id'];
        }
      }
    }

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
    $checkList = [];

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

    if ($this->_limit && count($rows) >= $this->getRowCount()) {
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
    $chartEnabled = !empty($this->_params['charts']) && !empty($rows);
    $this->assign('chartEnabled', $chartEnabled);
    // allow building charts if any
    if ($chartEnabled) {
      $this->buildChart($rows);
      $this->_chartId = "{$this->_params['charts']}_" .
        ($this->_id ?: substr(get_class($this), 16)) . '_' .
        CRM_Core_Config::singleton()->userSystem->getSessionId();
      $this->assign('chartId', $this->_chartId);
    }

    // unset columns not to be displayed.
    foreach ($this->_columnHeaders as $key => $value) {
      if (!empty($value['no_display'])) {
        unset($this->_columnHeaders[$key]);
      }
      else {
        foreach (['colspan', 'type', 'group_by'] as $expectedKey) {
          if (!isset($this->_columnHeaders[$key][$expectedKey])) {
            // Ensure it is set to prevent smarty notices.
            $this->_columnHeaders[$key][$expectedKey] = FALSE;
          }
        }
      }
    }

    // unset columns not to be displayed.
    if (!empty($rows)) {
      foreach ($this->_noDisplay as $noDisplayField) {
        foreach ($rows as $rowNum => $row) {
          unset($this->_columnHeaders[$noDisplayField]);
          $expectedKeys = ['class'];
          foreach ($expectedKeys as $expectedKey) {
            if (!array_key_exists($expectedKey, $row)) {
              $rows[$rowNum][$expectedKey] = NULL;
            }
          }
        }
      }
    }

    // Find alter display functions.
    $firstRow = reset($rows);
    if ($firstRow) {
      $selectedFields = array_keys($firstRow);
      $alterFunctions = $alterMap = $alterSpecs = [];
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('metadata', $table)) {
          foreach ($table['metadata'] as $field => $specs) {
            if (in_array($tableName . '_' . $field, $selectedFields)) {
              if (array_key_exists('alter_display', $specs)) {
                $alterFunctions[$tableName . '_' . $field] = $specs['alter_display'];
                $alterMap[$tableName . '_' . $field] = $field;
                $alterSpecs[$tableName . '_' . $field] = $specs;
              }
              // Add any alters that can be intuited from the field specs.
              // So far only boolean but a lot more could be.
              if (empty($alterSpecs[$tableName . '_' . $field]) && isset($specs['type']) && $specs['type'] == CRM_Utils_Type::T_BOOLEAN) {
                $alterFunctions[$tableName . '_' . $field] = 'alterBoolean';
                $alterMap[$tableName . '_' . $field] = $field;
                $alterSpecs[$tableName . '_' . $field] = $specs;
              }
            }
          }
        }
      }

      // Run the alter display functions
      foreach ($rows as $index => & $row) {
        foreach ($row as $selectedField => $value) {
          if (array_key_exists($selectedField, $alterFunctions) && isset($value)) {
            $rows[$index][$selectedField] = $this->{$alterFunctions[$selectedField]}($value, $row, $selectedField, $alterMap[$selectedField], $alterSpecs[$selectedField]);
          }
        }
      }
    }

    // use this method for formatting rows for display purpose.
    $this->alterDisplay($rows);
    CRM_Utils_Hook::alterReportVar('rows', $rows, $this);

    // build array of section totals
    $this->sectionTotals();

    // process grand-total row
    $this->grandTotal($rows);

    // use this method for formatting custom rows for display purpose.
    $this->alterCustomDataDisplay($rows);
  }

  /**
   * @param $value
   * @param array $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return array
   */
  protected function alterStateProvinceID($value, &$row, $selectedfield, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedfield . '_raw'] = $value;
    $row[$selectedfield . '_link'] = $url;
    $row[$selectedfield . '_hover'] = ts("%1 for this state.", [
      1 => $value,
    ]);

    $states = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
    if (!is_array($states)) {
      return $states;
    }
  }

  /**
   * @param $value
   * @param array $row
   * @param $selectedField
   * @param $criteriaFieldName
   *
   * @return array
   */
  protected function alterCountryID($value, &$row, $selectedField, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedField . '_link'] = $url;
    $row[$selectedField . '_hover'] = ts("%1 for this country.", [
      1 => $value,
    ]);
    $countries = CRM_Core_PseudoConstant::country($value, FALSE);
    if (!is_array($countries)) {
      return $countries;
    }
  }

  /**
   * @param $value
   * @param array $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return array
   */
  protected function alterCountyID($value, &$row, $selectedfield, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedfield . '_link'] = $url;
    $row[$selectedfield . '_hover'] = ts("%1 for this county.", [
      1 => $value,
    ]);
    $counties = CRM_Core_PseudoConstant::county($value, FALSE);
    if (!is_array($counties)) {
      return $counties;
    }
  }

  /**
   * @param $value
   * @param array $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return mixed
   */
  protected function alterLocationTypeID($value, &$row, $selectedfield, $criteriaFieldName) {
    return CRM_Core_PseudoConstant::getLabel('CRM_Core_DAO_Address', 'location_type_id', $value);
  }

  /**
   * @param $value
   * @param array $row
   * @param $fieldname
   *
   * @return mixed
   */
  protected function alterContactID($value, &$row, $fieldname) {
    $nameField = substr($fieldname, 0, -2) . 'name';
    static $first = TRUE;
    static $viewContactList = FALSE;
    if ($first) {
      $viewContactList = CRM_Core_Permission::check('access CiviCRM');
      $first = FALSE;
    }
    if (!$viewContactList) {
      return $value;
    }
    if (array_key_exists($nameField, $row)) {
      $row[$nameField . '_link'] = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $value, $this->_absoluteUrl);
    }
    else {
      $row[$fieldname . '_link'] = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $value, $this->_absoluteUrl);
    }
    return $value;
  }

  /**
   * @param $value
   * @param array $row
   * @param $fieldname
   *
   * @return mixed
   */
  protected function alterCommunicationtMethod($value, &$row, $fieldname) {
    $communicationMethods = CRM_Contact_DAO_Contact::buildOptions('preferred_communication_method');

    // Explode padded values.
    $values = CRM_Utils_Array::explodePadded($value);
    // Flip values, compute intersection with $communicationMethods, and implode with commas.
    $value = implode(', ', array_intersect_key($communicationMethods, array_flip($values)));
    return $value;
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  protected function alterBoolean($value) {
    $options = [0 => '', 1 => ts('Yes')];
    if (isset($options[$value])) {
      return $options[$value];
    }
    return $value;
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
    $select = $this->_selectAliases = [];
    $this->storeGroupByArray();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            // deprecated, use $this->isTableSelected.
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
            if (!empty($field['statistics']) && !empty($this->_groupByArray)) {
              $select = $this->addStatisticsToSelect($field, $tableName, $fieldName, $select);
            }
            else {

              $selectClause = $this->getSelectClauseWithGroupConcatIfNotGroupedBy($tableName, $fieldName, $field);
              if ($selectClause) {
                $select[] = $selectClause;
              }
              else {
                $select = $this->addBasicFieldToSelect($tableName, $fieldName, $field, $select);
              }
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

          if (!empty($this->_params['group_bys'][$fieldName]) &&
            !empty($this->_params['group_bys_freq'])
          ) {
            switch ($this->_params['group_bys_freq'][$fieldName] ?? NULL) {
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
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = ['no_display' => TRUE];
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = ['no_display' => TRUE];
            }
          }
        }
      }
    }

    if (empty($select)) {
      // CRM-21412 Do not give fatal error on report when no fields selected
      $select = [1];
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
   * @param array $field
   *
   * @return bool
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if (!empty($field['pseudofield'])) {
      $alias = "{$tableName}_{$fieldName}";
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['dbAlias'] = $field['dbAlias'] ?? NULL;
      $this->_selectAliases[] = $alias;
      return ' 1 as  ' . $alias;
    }
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
          $clause = $this->generateFilterClause($field, $fieldName);

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

    if ($this->_sendmail && !$this->_outputMode) {
      // If we're here from the mail_report job, then the default there gets
      // set to pdf before we get here, but if we're somehow here and sending
      // by email and don't have a format set, then use print.
      // @todo Is this on purpose - why would they be different defaults?
      $this->_outputMode = 'print';
    }

    // _outputMode means multiple things and can cover export to file formats,
    // like csv, or actions with no output, like save. So this will only set
    // a handler if it's one of the former. But it's also possible we have a
    // really interesting handler out there. But the point is we don't need to
    // know, just to know that a handler doesn't always get set by this call.
    $this->setOutputHandler();

    if (!empty($this->outputHandler)) {
      if ($this->_sendmail) {
        // If we're sending by email these are the only options that make
        // sense.
        $this->printOnly = TRUE;
        $this->addPaging = FALSE;
        $this->_absoluteUrl = TRUE;
      }
      else {
        // otherwise ask the handler
        $this->printOnly = $this->outputHandler->isPrintOnly();
        $this->addPaging = $this->outputHandler->isAddPaging();
        $this->_absoluteUrl = $this->outputHandler->isAbsoluteUrl();
      }
    }
    elseif ($this->_outputMode == 'copy' && $this->_criteriaForm) {
      $this->_createNew = TRUE;
    }

    $this->assign('outputMode', $this->_outputMode);
    $this->assign('printOnly', $this->printOnly);
    // Get today's date to include in printed reports
    if ($this->printOnly) {
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
    if (empty($this->_params['fields']) && !$this->_noFields
      && empty($this->_params['task'])
    ) {
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
    $this->buildPermissionClause();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    foreach ($this->unselectedOrderByColumns() as $alias => $field) {
      $clause = $this->getSelectClauseWithGroupConcatIfNotGroupedBy($field['table_name'], $field['name'], $field);
      if (!$clause) {
        $clause = "{$field['dbAlias']} as {$alias}";
      }
      $this->_select .= ", $clause ";
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
    $this->storeGroupByArray();

    if (!empty($this->_groupByArray)) {
      if ($this->optimisedForOnlyFullGroupBy) {
        // We should probably deprecate this code path. What happens here is that
        // the group by is amended to reflect the select columns. This often breaks the
        // results. Retrofitting group strict group by onto existing report classes
        // went badly.
        $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $this->_groupByArray);
      }
      else {
        $this->_groupBy = ' GROUP BY ' . implode($this->_groupByArray);
      }
    }
  }

  /**
   * Build order by clause.
   */
  public function orderBy() {
    $this->_orderBy = "";
    $this->_sections = [];
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
    $orderBys = [];

    if (!empty($this->_params['order_bys']) &&
      is_array($this->_params['order_bys']) &&
      !empty($this->_params['order_bys'])
    ) {

      // Process order_bys in user-specified order
      foreach ($this->_params['order_bys'] as $orderBy) {
        $orderByField = [];
        foreach ($this->_columns as $tableName => $table) {
          if (array_key_exists('order_bys', $table)) {
            // For DAO columns defined in $this->_columns
            $fields = $table['order_bys'];
          }
          elseif (array_key_exists('extends', $table)) {
            // For custom fields referenced in $this->_customGroupExtends
            $fields = $table['fields'] ?? [];
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
          if ($this->groupConcatTested) {
            $orderBys[$orderByField['tplField']] = "{$orderByField['tplField']} {$orderBy['order']}";
          }
          else {
            // Not sure when this is preferable to using tplField (which has
            // definitely been tested to work in cases then this does not.
            // in caution not switching unless report has been tested for
            // group concat functionality.
            $orderBys[$orderByField['tplField']] = "{$orderByField['dbAlias']} {$orderBy['order']}";
          }

          // Record any section headers for assignment to the template
          if (!empty($orderBy['section'])) {
            $orderByField['pageBreak'] = $orderBy['pageBreak'] ?? NULL;
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
  public function unselectedOrderByColumns() {
    return array_diff_key($this->_orderByFields, $this->getSelectColumns());
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
      return [];
    }
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    if (!$this->optimisedForOnlyFullGroupBy) {
      CRM_Core_DAO::disableFullGroupByMode();
    }
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (stristr($this->_select, 'SQL_CALC_FOUND_ROWS')) {
      $this->_rowsFound = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');
    }
    CRM_Core_DAO::reenableFullGroupByMode();
    if (!is_array($rows)) {
      $rows = [];
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();

    while ($dao->fetch()) {
      $row = [];
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

      $ifnulls = [];
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
      $totals = [];
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $this->alterCustomDataDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = [];
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
    $this->assign('columnHeaders', $this->_columnHeaders);
    $this->assign('rows', $rows);
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
    $statistics = [];

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
    $statistics['counts']['rowCount'] = [
      'title' => ts('Row(s) Listed'),
      'value' => $count,
      'type' => CRM_Utils_Type::T_INT,
    ];

    if ($this->_rowsFound && ($this->_rowsFound > $count)) {
      $statistics['counts']['rowsFound'] = [
        'title' => ts('Total Row(s)'),
        'value' => $this->_rowsFound,
        'type' => CRM_Utils_Type::T_INT,
      ];
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
      $statistics['groups'][] = [
        'title' => ts('Grouping(s)'),
        'value' => implode(' & ', $combinations),
      ];
    }
    else {
      // prevents an e-notice in statistics.tpl.
      $statistics['groups'] = [];
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
            ($field['operatorType'] ?? NULL) !=
            CRM_Report_Form::OP_MONTH
          ) {
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;
            if (!empty($this->_params["{$fieldName}_relative"])) {
              [$from, $to] = CRM_Utils_Date::getFromTo($this->_params["{$fieldName}_relative"], NULL, NULL);
            }
            if (strlen($to ?? '') === 10) {
              // If we just have the date we assume the end of that day.
              $to .= ' 23:59:59';
            }
            if ($from || $to) {
              if ($from) {
                $from = date('l j F Y, g:iA', strtotime($from));
              }
              if ($to) {
                $to = date('l j F Y, g:iA', strtotime($to));
              }
              $statistics['filters'][] = [
                'title' => $field['title'],
                'value' => ts("Between %1 and %2", [1 => $from, 2 => $to]),
              ];
            }
            elseif (in_array($rel = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params),
              array_keys($this->getOperationPair(CRM_Report_Form::OP_DATE))
            )) {
              $pair = $this->getOperationPair(CRM_Report_Form::OP_DATE);
              $statistics['filters'][] = [
                'title' => $field['title'],
                'value' => $pair[$rel],
              ];
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            $value = NULL;
            if ($op) {
              $pair = $this->getOperationPair(
                CRM_Utils_Array::value('operatorType', $field),
                $fieldName
              );
              $min = $this->_params["{$fieldName}_min"] ?? NULL;
              $max = $this->_params["{$fieldName}_max"] ?? NULL;
              $val = $this->_params["{$fieldName}_value"] ?? NULL;
              if (in_array($op, ['bw', 'nbw']) && ($min || $max)) {
                $value = "{$pair[$op]} $min " . ts('and') . " $max";
              }
              elseif ($val && CRM_Utils_Array::value('operatorType', $field) & self::OP_ENTITYREF) {
                $this->setEntityRefDefaults($field, $tableName);
                $result = civicrm_api3($field['attributes']['entity'], 'getlist',
                  ['id' => $val] +
                  CRM_Utils_Array::value('api', $field['attributes'], []));
                $values = [];
                foreach ($result['values'] as $v) {
                  $values[] = $v['label'];
                }
                $value = "{$pair[$op]} " . implode(', ', $values);
              }
              elseif ($op == 'nll' || $op == 'nnll') {
                $value = $pair[$op];
              }
              elseif (is_array($val) && (!empty($val))) {
                $options = $field['options'] ?? [];
                foreach ($val as $key => $valIds) {
                  if (isset($options[$valIds])) {
                    $val[$key] = $options[$valIds];
                  }
                }
                $pair[$op] = (count($val) == 1) ?
                  (($op == 'notin' || $op == 'mnot') ? ts('Is Not') : ts('Is')) :
                  ($pair[$op] ?? '');
                $val = implode(', ', $val);
                $value = "{$pair[$op]} " . $val;
              }
              elseif (!is_array($val) && (!empty($val) || $val == '0') &&
                isset($field['options']) &&
                is_array($field['options']) && !empty($field['options'])
              ) {
                $value = ($pair[$op] ?? '') . ' ' . ($field['options'][$val] ?? $val);
              }
              elseif ($val || $val == '0') {
                $value = ($pair[$op] ?? '') . " " . $val;
              }
            }
            if ($value && empty($field['no_display'])) {
              $statistics['filters'][] = [
                'title' => $field['title'] ?? NULL,
                'value' => CRM_Utils_String::htmlToText($value),
              ];
            }
          }
        }
      }
    }
    // Prevents an e-notice in statistics.tpl.
    if (!isset($statistics['filters'])) {
      $statistics['filters'] = [];
    }
  }

  /**
   * End post processing.
   *
   * @param array|null $rows
   */
  public function endPostProcess(&$rows = NULL) {
    $this->assign('report_class', get_class($this));

    // This is used by unit tests, where _outputMode is intentionally blank.
    // Is there a reason it couldn't just always do this? It effectively does
    // the same thing anyway by assigning it to the template (in
    // doTemplateAssignment()).
    if ($this->_storeResultSet) {
      $this->_resultSet = $rows;
    }

    // Add contacts to group
    if ($this->_outputMode == 'group') {
      $group = $this->_params['groups'];
      $this->add2group($group);
    }
    else {
      if ($this->_sendmail) {
        $this->sendEmail();
      }
      elseif (!empty($this->outputHandler)) {
        $this->outputHandler->download();
        CRM_Utils_System::civiExit();
      }
      // else we don't need to do anything here since it must have been
      // outputMode=save or something like that
    }
  }

  /**
   * Set store result set indicator to TRUE.
   *
   * This is used by unit tests, along with getResultSet(), to get just
   * the output rows unformatted.
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
    return ($this->_formValues['report_header'] ?? '') .
      CRM_Core_Form::$_template->fetch($templateFile) .
      ($this->_formValues['report_footer'] ?? '');
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
    $rows = [];
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
   * @param int|null $rowCount
   *
   * @return array
   */
  public function limit($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    // lets do the pager if in html mode
    $this->_limit = NULL;

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }
    if ($this->addPaging) {
      $this->_select = preg_replace('/SELECT(\s+SQL_CALC_FOUND_ROWS)?\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select);

      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer');

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

      $pageId = $pageId ?: 1;
      $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
      $offset = ($pageId - 1) * $rowCount;

      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $this->_limit = " LIMIT $offset, $rowCount";
      return [$offset, $rowCount];
    }
    if ($this->_limitValue) {
      if ($this->_offsetValue) {
        $this->_limit = " LIMIT {$this->_offsetValue}, {$this->_limitValue} ";
      }
      else {
        $this->_limit = ' LIMIT ' . $this->_limitValue;
      }
    }
  }

  /**
   * Set pager.
   *
   * @param int|null $rowCount
   */
  public function setPager($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }

    if ($this->_limit && ($this->_limit != '')) {
      if (!$this->_rowsFound) {
        $sql = "SELECT FOUND_ROWS();";
        $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
      }
      $params = [
        'total' => $this->_rowsFound,
        'rowCount' => $rowCount,
        'status' => ts('Records') . ' %%StatusMessage%%',
        'buttonBottom' => 'PagerBottomButton',
        'buttonTop' => 'PagerTopButton',
      ];
      if (!empty($this->controller)) {
        // This happens when being called from the api Really we want the api to be able to
        // pass paging parameters, but at this stage just preventing test crashes.
        $params['pageID'] = $this->get(CRM_Utils_Pager::PAGE_ID);
      }

      $pager = new CRM_Utils_Pager($params);
      $this->assign('pager', $pager);
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
   * @param array $field
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
    $smartGroups = [];
    while ($group->fetch()) {
      if (in_array($group->id, (array) $this->_params['gid_value']) &&
        $group->saved_search_id
      ) {
        $smartGroups[] = $group->id;
      }
    }

    CRM_Contact_BAO_GroupContactCache::check($smartGroups);
    $aclFilter = NULL;
    $selectWhereClauses = array_filter(CRM_Contact_BAO_Group::getSelectWhereClause('group'));
    $aclFilter = implode(' AND ', $selectWhereClauses);
    $aclFilter = !empty($aclFilter) ? ' AND ' . $aclFilter : '';
    $smartGroupQuery = '';
    if (!empty($smartGroups)) {
      $smartGroups = implode(',', $smartGroups);
      $smartGroupQuery = " UNION DISTINCT
                  SELECT DISTINCT smartgroup_contact.contact_id
                  FROM civicrm_group_contact_cache smartgroup_contact
                  INNER JOIN `civicrm_group` AS `group` ON `group`.id = smartgroup_contact.group_id
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) {$aclFilter}";
    }

    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = [$value];
    }
    //include child groups if any
    $value = array_merge($value, CRM_Contact_BAO_Group::getChildGroupIds($value));

    $clause = "{$field['dbAlias']} IN (" . implode(', ', $value) . ")";

    $contactAlias = $this->_aliases['civicrm_contact'];
    if (!empty($this->relationType) && $this->relationType == 'b_a') {
      $contactAlias = $this->_aliases['civicrm_contact_b'];
    }
    return " {$contactAlias}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          INNER JOIN `civicrm_group` AS `group` ON `group`.id = {$this->_aliases['civicrm_group']}.group_id
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added' {$aclFilter}
                          {$smartGroupQuery} ) ";
  }

  /**
   * Build where clause for groups.
   *
   * @param array $field
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
      return " group_temp_table.contact_id IS NULL ";
    }
    // We will have used an inner join instead.
    return "1";
  }

  /**
   * Create a table of the contact ids included by the group filter.
   *
   * This function is called by both the api (tests) and the UI.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildGroupTempTable(): void {
    if (!empty($this->groupTempTable) || empty($this->_params['gid_value']) || $this->groupFilterNotOptimised) {
      return;
    }
    $this->groupTempTable = $this->createTemporaryTable('groups', 'contact_id INT', TRUE);
    $filteredGroups = (array) $this->_params['gid_value'];
    CRM_Contact_BAO_GroupContactCache::populateTemporaryTableWithContactsInGroups($filteredGroups, $this->groupTempTable);
    CRM_Core_DAO::executeQuery("ALTER TABLE $this->groupTempTable ADD INDEX contact_id(contact_id)");
  }

  /**
   * Execute query and add it to the developer tab.
   *
   * @param string $query
   * @param array $params
   *
   * @return \CRM_Core_DAO|object
   */
  protected function executeReportQuery($query, $params = []) {
    $this->addToDeveloperTab($query);
    return CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Build where clause for tags.
   *
   * @param array $field
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
      $value = [$value];
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
      $value = [$value];
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
      $value = [$value];
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
   * Build contact acl clause
   * @deprecated in favor of buildPermissionClause
   *
   * Note that if the buildPermissionClause function is called (which most reports do from
   * buildQuery then the results of this function are re-calculated and overwritten.
   *
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact_a') {
    [$this->_aclFrom, $this->_aclWhere] = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  /**
   * Build the permission clause for all entities in this report
   */
  public function buildPermissionClause() {
    $ret = [];
    foreach ($this->selectedTables() as $tableName) {
      $baoName = str_replace('_DAO_', '_BAO_', (CRM_Core_DAO_AllCoreTables::getClassForTable($tableName) ?? ''));
      // Do not include CiviCRM group add Select Where clause because we don't necessarily join here for reports with optimisedGroupFilters
      if ($baoName === 'CRM_Contact_BAO_Group') {
        continue;
      }
      if ($baoName && class_exists($baoName) && !empty($this->_columns[$tableName]['alias'])) {
        $tableAlias = $this->_columns[$tableName]['alias'];
        $clauses = array_filter($baoName::getSelectWhereClause($tableAlias));
        foreach ($clauses as $field => $clause) {
          // Skip contact_id field if redundant
          if ($field != 'contact_id' || !in_array('civicrm_contact', $this->selectedTables())) {
            $ret["$tableName.$field"] = $clause;
          }
        }
      }
    }
    // Override output from buildACLClause
    $this->_aclFrom = NULL;
    $this->_aclWhere = implode(' AND ', $ret);
  }

  /**
   * Add custom data to the columns.
   *
   * @param bool $addFields
   */
  public function addCustomDataToColumns($addFields = TRUE) {
    if (empty($this->_customGroupExtends)) {
      return;
    }
    if (!is_array($this->_customGroupExtends)) {
      $this->_customGroupExtends = [$this->_customGroupExtends];
    }

    $customGroups = CRM_Core_BAO_CustomGroup::getAll(['is_active' => TRUE, 'extends' => $this->_customGroupExtends], CRM_Core_Permission::VIEW);

    foreach ($customGroups as $customGroup) {
      if ($customGroup['fields']) {
        $curTable = $customGroup['table_name'];
        $curFields = $curFilters = [];

        // dummy dao object
        $this->_columns[$curTable]['dao'] = 'CRM_Contact_DAO_Contact';
        $this->_columns[$curTable]['extends'] = $customGroup['extends'];
        $this->_columns[$curTable]['grouping'] = $curTable;
        $this->_columns[$curTable]['group_title'] = $customGroup['title'];
        foreach (['fields', 'filters', 'group_bys'] as $colKey) {
          if (!array_key_exists($colKey, $this->_columns[$curTable])) {
            $this->_columns[$curTable][$colKey] = [];
          }
        }
      }
      foreach ($customGroup['fields'] as $customField) {
        $fieldName = 'custom_' . $customField['id'];

        if ($addFields) {
          // this makes aliasing work in favor
          $curFields[$fieldName] = [
            'name' => $customField['column_name'],
            'title' => $customField['label'],
            'dataType' => $customField['data_type'],
            'htmlType' => $customField['html_type'],
          ];
        }
        if ($this->_customGroupFilters) {
          // this makes aliasing work in favor
          $curFilters[$fieldName] = [
            'name' => $customField['column_name'],
            'title' => $customField['label'],
            'dataType' => $customField['data_type'],
            'htmlType' => $customField['html_type'],
          ];
        }

        switch ($customField['data_type']) {
          case 'Date':
            // filters
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_DATE;
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_DATE;
            // CRM-6946, show time part for datetime date fields
            if (!empty($customField['time_format'])) {
              $curFields[$fieldName]['type'] = CRM_Utils_Type::T_TIMESTAMP;
            }
            break;

          case 'Boolean':
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_SELECT;
            $curFilters[$fieldName]['options'] = ['' => ts('- select -')] + CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customField['id'], [], 'search');
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
            break;

          case 'Int':
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
            break;

          case 'Money':
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
            // Use T_FLOAT instead of T_MONEY as the money number format happens
            // by calling CRM_Core_BAO_CustomField::displayValue in alterCustomDataDisplay
            $curFields[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
            break;

          case 'Float':
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
            break;

          case 'String':
          case 'StateProvince':
          case 'Country':
            $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;

            $options = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customField['id'], [], 'search');
            if ((is_array($options) && count($options) != 0) || (!is_array($options) && $options !== FALSE)) {
              $curFilters[$fieldName]['operatorType'] = CRM_Core_BAO_CustomField::isSerialized($customField) ? CRM_Report_Form::OP_MULTISELECT_SEPARATOR : CRM_Report_Form::OP_MULTISELECT;
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
        if ($customField['html_type'] == 'Select' && !array_key_exists('options', $curFilters[$fieldName])) {
          $options = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $customField['id'], [], 'search');
          if ($options !== FALSE) {
            $curFilters[$fieldName]['operatorType'] = CRM_Core_BAO_CustomField::isSerialized($customField) ? CRM_Report_Form::OP_MULTISELECT_SEPARATOR : CRM_Report_Form::OP_MULTISELECT;
            $curFilters[$fieldName]['options'] = $options;
          }
        }

        if (!array_key_exists('type', $curFields[$fieldName])) {
          $curFields[$fieldName]['type'] = $curFilters[$fieldName]['type'] ?? [];
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
    $customTables = array_column(CRM_Core_BAO_CustomGroup::getAll(), 'table_name');

    foreach ($this->_columns as $table => $prop) {
      if (in_array($table, $customTables)) {
        $extendsTable = $mapper[$prop['extends']];
        // Check field is required for rendering the report.
        if ((!$this->isFieldSelected($prop)) || ($joinsForFiltersOnly && !$this->isFieldFiltered($prop))) {
          continue;
        }
        $baseJoin = $this->_customGroupExtendsJoin[$prop['extends']] ?? "{$this->_aliases[$extendsTable]}.id";

        $customJoin = is_array($this->_customGroupJoin) ? $this->_customGroupJoin[$table] : $this->_customGroupJoin;
        $this->_from .= "
{$customJoin} {$table} {$this->_aliases[$table]} ON {$this->_aliases[$table]}.entity_id = {$baseJoin}";
        // handle for ContactReference
        if (array_key_exists('fields', $prop)) {
          foreach ($prop['fields'] as $fieldName => $field) {
            if (($field['dataType'] ?? NULL) === 'ContactReference') {
              $columnName = CRM_Core_BAO_CustomField::getField(CRM_Core_BAO_CustomField::getKeyID($fieldName))['column_name'];
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
        foreach (['value', 'min', 'max', 'relative', 'from', 'to'] as $attach) {
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
          in_array($this->_params[$fieldAlias . '_op'], ['nll', 'nnll'])
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
    $_showHide = new CRM_Core_ShowHideBlocks();

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
      $formValues['order_bys'] = [1 => ['column' => '-']];
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
      $orderByColumns = [];
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
            $filterOp = $this->_params["{$filterName}_op"] ?? '';
            if ((isset($this->_params["{$filterName}_value"])
                && !CRM_Utils_System::isNull($this->_params["{$filterName}_value"]))
              || !empty($this->_params["{$filterName}_relative"])
              || $filterOp === 'nll'
              || $filterOp === 'nnll'
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
   * Add campaign fields.
   * @param string $entityTable
   * @param bool $groupBy
   *   Add GroupBy? Not appropriate for detail report.
   * @param bool $orderBy
   *   Add OrderBy? Not appropriate for detail report.
   * @param bool $filters
   *
   */
  public function addCampaignFields($entityTable = 'civicrm_contribution', $groupBy = FALSE, $orderBy = FALSE, $filters = TRUE) {
    // Check if CiviCampaign is a) enabled and b) has active campaigns
    if (CRM_Core_Component::isEnabled('CiviCampaign')) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE, TRUE);
      // If we have a campaign, build out the relevant elements
      if (!empty($getCampaigns['campaigns'])) {
        $this->campaigns = $getCampaigns['campaigns'];
        asort($this->campaigns);
        $this->_columns[$entityTable]['fields']['campaign_id'] = ['title' => ts('Campaign'), 'default' => 'false'];
        if ($filters) {
          $this->_columns[$entityTable]['filters']['campaign_id'] = [
            'title' => ts('Campaign'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->campaigns,
            'type' => CRM_Utils_Type::T_INT,
          ];
        }

        if ($groupBy) {
          $this->_columns[$entityTable]['group_bys']['campaign_id'] = ['title' => ts('Campaign')];
        }

        if ($orderBy) {
          $this->_columns[$entityTable]['order_bys']['campaign_id'] = ['title' => ts('Campaign')];
        }
      }
    }
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
  public function addAddressFields($groupBy = TRUE, $orderBy = FALSE, $filters = TRUE, $defaults = ['country_id' => TRUE]) {
    $defaultAddressFields = [
      'street_address' => ts('Street Address'),
      'supplemental_address_1' => ts('Supplementary Address Field 1'),
      'supplemental_address_2' => ts('Supplementary Address Field 2'),
      'supplemental_address_3' => ts('Supplementary Address Field 3'),
      'street_number' => ts('Street Number'),
      'street_name' => ts('Street Name'),
      'street_unit' => ts('Street Unit'),
      'city' => ts('City'),
      'postal_code' => ts('Postal Code'),
      'postal_code_suffix' => ts('Postal Code Suffix'),
      'country_id' => ts('Country'),
      'state_province_id' => ts('State/Province'),
      'county_id' => ts('County'),
    ];
    $addressFields = [
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'address_name' => [
            'title' => ts('Address Name'),
            'default' => $defaults['name'] ?? FALSE,
            'name' => 'name',
          ],
        ],
        'grouping' => 'location-fields',
      ],
    ];
    foreach ($defaultAddressFields as $fieldName => $fieldLabel) {
      $addressFields['civicrm_address']['fields'][$fieldName] = [
        'title' => $fieldLabel,
        'default' => $defaults[$fieldName] ?? FALSE,
      ];
    }

    $street_address_filters = $general_address_filters = [];
    if ($filters) {
      // Address filter depends on whether street address parsing is enabled.
      // (CRM-18696)
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      );
      if ($addressOptions['street_address_parsing']) {
        $street_address_filters = [
          'street_number' => [
            'title' => ts('Street Number'),
            'type' => CRM_Utils_Type::T_INT,
            'name' => 'street_number',
          ],
          'street_name' => [
            'title' => ts('Street Name'),
            'name' => 'street_name',
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ];
      }
      else {
        $street_address_filters = [
          'street_address' => [
            'title' => ts('Street Address'),
            'type' => CRM_Utils_Type::T_STRING,
            'name' => 'street_address',
          ],
        ];
      }
      $general_address_filters = [
        'postal_code' => [
          'title' => ts('Postal Code'),
          'type' => CRM_Utils_Type::T_STRING,
          'name' => 'postal_code',
        ],
        'city' => [
          'title' => ts('City'),
          'type' => CRM_Utils_Type::T_STRING,
          'name' => 'city',
        ],
        'country_id' => [
          'name' => 'country_id',
          'title' => ts('Country'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::country(),
        ],
        'state_province_id' => [
          'name' => 'state_province_id',
          'title' => ts('State/Province'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => [],
        ],
        'county_id' => [
          'name' => 'county_id',
          'title' => ts('County'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => [],
        ],
      ];
    }
    $addressFields['civicrm_address']['filters'] = array_merge(
        $street_address_filters,
        $general_address_filters);

    if ($orderBy) {
      $addressFields['civicrm_address']['order_bys'] = [
        'street_name' => ['title' => ts('Street Name')],
        'street_number' => ['title' => ts('Odd / Even Street Number')],
        'street_address' => NULL,
        'city' => NULL,
        'postal_code' => NULL,
      ];
    }

    if ($groupBy) {
      $addressFields['civicrm_address']['group_bys'] = [
        'street_address' => NULL,
        'city' => NULL,
        'postal_code' => NULL,
        'state_province_id' => [
          'title' => ts('State/Province'),
        ],
        'country_id' => [
          'title' => ts('Country'),
        ],
        'county_id' => [
          'title' => ts('County'),
        ],
      ];
    }
    return $addressFields;
  }

  /**
   * Do AlterDisplay processing on Address Fields.
   *  If there are multiple address field values then
   *  on basis of provided separator the code values are translated into respective labels
   *
   * @param array $row
   * @param array $rows
   * @param int $rowNum
   * @param string|null $baseUrl
   * @param string|null $linkText
   * @param string $separator
   *
   * @return bool
   */
  public function alterDisplayAddressFields(&$row, &$rows, &$rowNum, $baseUrl, $linkText, $separator = ',') {
    $criteriaQueryParams = CRM_Report_Utils_Report::getPreviewCriteriaQueryParams($this->_defaults, $this->_params);
    $entryFound = FALSE;
    $columnMap = [
      'civicrm_address_country_id' => 'country',
      'civicrm_address_county_id' => 'county',
      'civicrm_address_state_province_id' => 'stateProvince',
    ];
    foreach ($columnMap as $fieldName => $fnName) {
      if (array_key_exists($fieldName, $row)) {
        if ($values = $row[$fieldName]) {
          $values = (array) explode($separator, $values);
          $rows[$rowNum][$fieldName] = [];
          $addressField = $fnName == 'stateProvince' ? 'state' : $fnName;
          foreach ($values as $value) {
            $rows[$rowNum][$fieldName][] = CRM_Core_PseudoConstant::$fnName($value);
          }
          $rows[$rowNum][$fieldName] = implode($separator, $rows[$rowNum][$fieldName]);
          if ($baseUrl) {
            $url = CRM_Report_Utils_Report::getNextUrl($baseUrl,
              sprintf("reset=1&force=1&%s&%s_op=in&%s_value=%s",
                $criteriaQueryParams,
                str_replace('civicrm_address_', '', $fieldName),
                str_replace('civicrm_address_', '', $fieldName),
                implode(',', $values)
              ), $this->_absoluteUrl, $this->_id
            );
            $rows[$rowNum]["{$fieldName}_link"] = $url;
            $rows[$rowNum]["{$fieldName}_hover"] = ts("%1 for this %2.", [1 => $linkText, 2 => $addressField]);
          }
        }
        $entryFound = TRUE;
      }
    }

    return $entryFound;
  }

  /**
   * Do AlterDisplay processing on Address Fields.
   *
   * @param array $row
   * @param array $rows
   * @param int $rowNum
   * @param string|null $baseUrl
   * @param string|null $linkText
   *
   * @return bool
   */
  public function alterDisplayContactFields(&$row, &$rows, &$rowNum, $baseUrl, $linkText) {
    $entryFound = FALSE;
    // There is no reason not to add links for all fields but it seems a bit odd to be able to click on
    // 'Mrs'. Also, we don't have metadata about the title. So, add selectively to addLinks.
    $addLinks = ['gender_id' => 'Gender'];
    foreach (['prefix_id', 'suffix_id', 'gender_id', 'contact_sub_type', 'preferred_language'] as $fieldName) {
      if (array_key_exists('civicrm_contact_' . $fieldName, $row)) {
        if (($value = $row['civicrm_contact_' . $fieldName]) != FALSE) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = [];
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $fieldName, $rowValue);
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
    $yesNoFields = [
      'do_not_email', 'is_deceased', 'do_not_phone', 'do_not_sms', 'do_not_mail', 'do_not_trade', 'is_opt_out',
    ];
    foreach ($yesNoFields as $fieldName) {
      if (array_key_exists('civicrm_contact_' . $fieldName, $row)) {
        // Since these are essentially 'negative fields' it feels like it
        // makes sense to only highlight the exceptions hence no 'No'.
        $rows[$rowNum]['civicrm_contact_' . $fieldName] = !empty($rows[$rowNum]['civicrm_contact_' . $fieldName]) ? ts('Yes') : '';
        $entryFound = TRUE;
      }
    }

    // Handle employer id
    if (array_key_exists('civicrm_contact_employer_id', $row)) {
      $employerId = $row['civicrm_contact_employer_id'];
      if ($employerId) {
        $rows[$rowNum]['civicrm_contact_employer_id'] = CRM_Contact_BAO_Contact::displayName($employerId);
        $rows[$rowNum]['civicrm_contact_employer_id_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $employerId, $this->_absoluteUrl);
        $rows[$rowNum]['civicrm_contact_employer_id_hover'] = ts('View Contact Summary for Employer.');
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
    if (($this->_params['yid_op'] ?? NULL) == 'calendar' ||
      ($fy['d'] == 1 && $fy['M'] == 1)
    ) {
      return "YEAR( $fieldName )";
    }
    return "YEAR( $fieldName - INTERVAL " . ($fy['M'] - 1) . " MONTH" .
    ($fy['d'] > 1 ? (" - INTERVAL " . ($fy['d'] - 1) . " DAY") : '') . " )";
  }

  /**
   * Add Address into From Table if required.
   *
   * Prefix will be added to both tables as
   * it is assumed you are using it to get address of a secondary contact.
   *
   * @param string $prefix
   * @param array $extra Additional options.
   *      Not currently used in core but may be used in override extensions.
   */
  protected function joinAddressFromContact($prefix = '', $extra = []) {
    $defaults = ['primary_only' => TRUE];
    $params = array_merge($defaults, $extra);
    $addressTables = ['civicrm_address', 'civicrm_country', 'civicrm_worldregion', 'civicrm_state_province'];
    $isJoinRequired = $this->_addressField;
    foreach ($addressTables as $addressTable) {
      if ($this->isTableSelected($prefix . $addressTable)) {
        $isJoinRequired = TRUE;
      }
    }
    if ($isJoinRequired) {
      $fromJoin = "
                 LEFT JOIN civicrm_address {$this->_aliases[$prefix . 'civicrm_address']}
                           ON ({$this->_aliases[$prefix . 'civicrm_contact']}.id =
                               {$this->_aliases[$prefix . 'civicrm_address']}.contact_id)";
      if ($params['primary_only']) {
        $fromJoin .= "  AND
                               {$this->_aliases[$prefix . 'civicrm_address']}.is_primary = 1\n";
      }
      $this->_from .= $fromJoin;
    }
  }

  /**
   * Add Country into From Table if required.
   *
   * Prefix will be added to both tables as
   * it is assumed you are using it to get address of a secondary contact.
   *
   * @param string $prefix
   * @param array $extra Additional options.
   *      Not currently used in core but may be used in override extensions.
   */
  protected function joinCountryFromAddress($prefix = '', $extra = []) {
    $defaults = ['primary_only' => TRUE];
    $params = array_merge($defaults, $extra);
    // include country field if country column is to be included
    if ($this->isTableSelected($prefix . 'civicrm_country') || $this->isTableSelected($prefix . 'civicrm_worldregion')) {
      if (empty($this->_aliases[$prefix . 'civicrm_country'])) {
        $this->_aliases[$prefix . 'civicrm_country'] = $prefix . '_report_country';
      }
      $fromJoin = "
            LEFT JOIN civicrm_country {$this->_aliases[$prefix . 'civicrm_country']}
                   ON {$this->_aliases[$prefix . 'civicrm_address']}.country_id = {$this->_aliases[$prefix . 'civicrm_country']}.id";
      if ($params['primary_only']) {
        $fromJoin .= " AND
                      {$this->_aliases[$prefix . 'civicrm_address']}.is_primary = 1 ";
      }
      $this->_from .= $fromJoin;
    }
  }

  /**
   * Add Phone into From Table if required.
   *
   * Prefix will be added to both tables as
   * it is assumed you are using it to get address of a secondary contact.
   *
   * @param string $prefix
   * @param array $extra Additional options.
   *      Not currently used in core but may be used in override extensions.
   */
  protected function joinPhoneFromContact($prefix = '', $extra = []) {
    $defaults = ['primary_only' => TRUE];
    $params = array_merge($defaults, $extra);
    // include phone field if phone column is to be included
    if ($this->isTableSelected($prefix . 'civicrm_phone')) {
      $fromJoin = "
      LEFT JOIN civicrm_phone {$this->_aliases[$prefix . 'civicrm_phone']}
             ON {$this->_aliases[$prefix . 'civicrm_contact']}.id = {$this->_aliases[$prefix . 'civicrm_phone']}.contact_id";
      if ($params['primary_only']) {
        $fromJoin .= " AND
                {$this->_aliases[$prefix . 'civicrm_phone']}.is_primary = 1\n";
      }
      $this->_from .= $fromJoin;
    }
  }

  /**
   * Add Email into From Table if required.
   *
   * Prefix will be added to both tables as
   * it is assumed you are using it to get address of a secondary contact.
   *
   * @param string $prefix
   * @param array $extra Additional options.
   *      Not currently used in core but may be used in override extensions.
   */
  protected function joinEmailFromContact($prefix = '', $extra = []) {
    $defaults = ['primary_only' => TRUE];
    $params = array_merge($defaults, $extra);
    // include email field if email column is to be included
    if ($this->isTableSelected($prefix . 'civicrm_email')) {
      $fromJoin = "
            LEFT JOIN  civicrm_email {$this->_aliases[$prefix . 'civicrm_email']}
                   ON {$this->_aliases[$prefix . 'civicrm_contact']}.id = {$this->_aliases[$prefix . 'civicrm_email']}.contact_id";
      if ($params['primary_only']) {
        $fromJoin .= " AND
                       {$this->_aliases[$prefix . 'civicrm_email']}.is_primary = 1 ";
      }
      $this->_from .= $fromJoin;
    }
  }

  /**
   * Add Financial Transaction into From Table if required.
   */
  public function addFinancialTrxnFromClause() {
    if ($this->isTableSelected('civicrm_financial_trxn')) {
      $this->_from .= "
         LEFT JOIN civicrm_entity_financial_trxn eftcc
           ON ({$this->_aliases['civicrm_contribution']}.id = eftcc.entity_id AND
             eftcc.entity_table = 'civicrm_contribution')
         LEFT JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
           ON {$this->_aliases['civicrm_financial_trxn']}.id = eftcc.financial_trxn_id \n";
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
  public function getPhoneColumns($options = []) {
    $defaultOptions = [
      'prefix' => '',
      'prefix_label' => '',
    ];

    $options = array_merge($defaultOptions, $options);

    $fields = [
      $options['prefix'] . 'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          $options['prefix'] . 'phone' => [
            'title' => $options['prefix_label'] . ts('Phone'),
            'name' => 'phone',
          ],
        ],
      ],
    ];
    return $fields;
  }

  /**
   * Get a standard set of contact fields.
   * @deprecated - use getColumns('Contact') instead
   * @return array
   */
  public function getBasicContactFields() {
    return [
      'sort_name' => [
        'title' => ts('Contact Name'),
        'required' => TRUE,
        'default' => TRUE,
      ],
      'id' => [
        'no_display' => TRUE,
        'required' => TRUE,
      ],
      'prefix_id' => [
        'title' => ts('Contact Prefix'),
      ],
      'first_name' => [
        'title' => ts('First Name'),
      ],
      'nick_name' => [
        'title' => ts('Nick Name'),
      ],
      'middle_name' => [
        'title' => ts('Middle Name'),
      ],
      'last_name' => [
        'title' => ts('Last Name'),
      ],
      'suffix_id' => [
        'title' => ts('Contact Suffix'),
      ],
      'source' => ['title' => ts('Contact Source')],
      'postal_greeting_display' => ['title' => ts('Postal Greeting')],
      'email_greeting_display' => ['title' => ts('Email Greeting')],
      'addressee_display' => ['title' => ts('Addressee')],
      'contact_type' => [
        'title' => ts('Contact Type'),
      ],
      'contact_sub_type' => [
        'title' => ts('Contact Subtype'),
      ],
      'gender_id' => [
        'title' => ts('Gender'),
      ],
      'birth_date' => [
        'title' => ts('Birth Date'),
      ],
      'age' => [
        'title' => ts('Age'),
        'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
      ],
      'is_deceased' => [],
      'deceased_date' => [
        'title' => ts('Deceased Date'),
      ],
      'job_title' => [
        'title' => ts('Contact Job title'),
      ],
      'organization_name' => [
        'title' => ts('Organization Name'),
      ],
      'external_identifier' => [
        'title' => ts('Contact identifier from external system'),
      ],
      'do_not_email' => [],
      'do_not_phone' => [],
      'do_not_mail' => [],
      'do_not_sms' => [],
      'do_not_trade' => [],
      'is_opt_out' => [],
      'preferred_language' => [],
      'employer_id' => [
        'title' => ts('Current Employer'),
      ],
      'created_date' => [
        'title' => ts('Created Date'),
      ],
    ];
  }

  /**
   * Get a standard set of contact filters.
   *
   * @param array $defaults
   *
   * @return array
   */
  public function getBasicContactFilters($defaults = []) {
    return [
      'sort_name' => [
        'title' => ts('Contact Name'),
      ],
      'source' => [
        'title' => ts('Contact Source'),
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'id' => [
        'title' => ts('Contact ID'),
        'no_display' => TRUE,
      ],
      'gender_id' => [
        'title' => ts('Gender'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Contact_DAO_Contact::buildOptions('gender_id'),
      ],
      'birth_date' => [
        'title' => ts('Birth Date'),
        'operatorType' => CRM_Report_Form::OP_DATE,
      ],
      'contact_type' => [
        'title' => ts('Contact Type'),
      ],
      'contact_sub_type' => [
        'title' => ts('Contact Subtype'),
      ],
      'created_date' => [
        'title' => ts('Contact Created'),
        'operatorType' => CRM_Report_Form::OP_DATE,
        'type' => CRM_Utils_Type::T_DATE,
      ],
      'modified_date' => [
        'title' => ts('Contact Modified'),
        'operatorType' => CRM_Report_Form::OP_DATE,
        'type' => CRM_Utils_Type::T_DATE,
      ],
      'is_deceased' => [
        'title' => ts('Deceased'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'default' => $defaults['deceased'] ?? 0,
      ],
      'do_not_email' => [
        'title' => ts('Do not email'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'do_not_phone' => [
        'title' => ts('Do not phone'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'do_not_mail' => [
        'title' => ts('Do not mail'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'do_not_sms' => [
        'title' => ts('Do not SMS'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'do_not_trade' => [
        'title' => ts('Do not Trade'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'is_opt_out' => [
        'title' => ts('Do not bulk email'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
      ],
      'preferred_language' => [
        'title' => ts('Preferred Language'),
      ],
      'is_deleted' => [
        'no_display' => TRUE,
        'default' => 0,
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Deleted?'),
      ],
    ];
  }

  /**
   * Add contact to group.
   *
   * @param int $groupID
   */
  public function add2group($groupID) {
    if (is_numeric($groupID) && isset($this->_aliases['civicrm_contact'])) {
      $select = "SELECT DISTINCT {$this->_aliases['civicrm_contact']}.id AS addtogroup_contact_id, ";
      $select = preg_replace('/SELECT(\s+SQL_CALC_FOUND_ROWS)?\s+/i', $select, $this->_select);
      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";
      $sql = str_replace('WITH ROLLUP', '', $sql);
      if (!$this->optimisedForOnlyFullGroupBy) {
        CRM_Core_DAO::disableFullGroupByMode();
      }
      $dao = CRM_Core_DAO::executeQuery($sql);
      CRM_Core_DAO::reenableFullGroupByMode();

      $contact_ids = [];
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
   * Apply common settings to entityRef fields.
   *
   * @param array $field
   * @param string $table
   */
  public function setEntityRefDefaults(&$field, $table) {
    $field['attributes'] = $field['attributes'] ?: [];
    $field['attributes'] += [
      'entity' => CRM_Core_DAO_AllCoreTables::getEntityNameForTable($table),
      'multiple' => TRUE,
      'placeholder' => ts('- select -'),
    ];
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
      [1 => $linkText, 2 => $fieldLabel]
    );
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
    $this->_outputMode = str_replace('report_instance.', '', (CRM_Utils_Request::retrieve(
      'output',
      'String',
      CRM_Core_DAO::$_nullObject,
      FALSE,
      $this->_params['task'] ?? NULL
    ) ?? ''));
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
   * @param string $tempTable
   * @param string $columnName
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
    $selectColumns = [];
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
   * @param string|null $tableAlias
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
        ON group_temp_table.contact_id = $tableAlias.{$field} ";
      }
      else {
        $this->_from .= "
          LEFT JOIN $this->groupTempTable group_temp_table
          ON $tableAlias.{$field} = group_temp_table.contact_id ";
      }
    }
  }

  /**
   * Get all labels for fields that are used in a group concat.
   *
   * @param string $options
   *   comma separated option values.
   * @param string $baoName
   *   The BAO name for the field.
   * @param string $fieldName
   *   The name of the field for which labels should be retrieved.
   *
   * return string
   */
  public function getLabels($options, $baoName, $fieldName) {
    $types = explode(',', $options);
    $labels = [];
    foreach ($types as $value) {
      $labels[$value] = CRM_Core_PseudoConstant::getLabel($baoName, $fieldName, $value);
    }
    return implode(', ', array_filter($labels));
  }

  /**
   * Add statistics columns.
   *
   * If a group by is in play then add columns for the statistics fields.
   *
   * This would lead to a new field in the $row such as $fieldName_sum and a new, matching
   * column header field.
   *
   * @param array $field
   * @param string $tableName
   * @param string $fieldName
   * @param array $select
   *
   * @return array
   */
  protected function addStatisticsToSelect($field, $tableName, $fieldName, $select) {
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
    return $select;
  }

  /**
   * Add a basic field to the select clause.
   *
   * @param string $tableName
   * @param string $fieldName
   * @param array $field
   * @param array $select
   * @return array
   */
  protected function addBasicFieldToSelect($tableName, $fieldName, $field, $select) {
    $alias = "{$tableName}_{$fieldName}";
    $select[] = "{$field['dbAlias']} as $alias";
    $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
    $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
    $this->_selectAliases[] = $alias;
    return $select;
  }

  /**
   * Set table alias.
   *
   * @param array $table
   * @param string $tableName
   *
   * @return string
   *   Alias for table.
   */
  protected function setTableAlias($table, $tableName) {
    if (!isset($table['alias'])) {
      $this->_columns[$tableName]['alias'] = substr($tableName, 8) .
        '_civireport';
    }
    else {
      $this->_columns[$tableName]['alias'] = $table['alias'] . '_civireport';
    }

    $this->_aliases[$tableName] = $this->_columns[$tableName]['alias'];
    return $this->_aliases[$tableName];
  }

  /**
   * Function to add columns to reports.
   *
   * This is ported from extended reports, which also adds join filters to the options.
   *
   * @param string $type
   * @param array $options
   *  - prefix - A string to prepend to the table name
   *  - prefix_label  A string to prepend to the fields
   *  - fields (bool) - should the fields for this table be made available
   *  - group_by (bool) - should the group bys for this table be made available.
   *  - order_by (bool) - should the group bys for this table be made available.
   *  - filters (bool) - should the filters for this table by made available.
   *  - fields_defaults (array) array of fields that should be displayed by default.
   *  - filters_defaults (array) array of fields that should be filtered by default.
   *  - join_filters (array) fields available for filtering joins (requires additional custom code).
   *  - join_fields (array) fields available from join (requires additional custom code).
   *  - group_by_defaults (array) array of group bys that should be applied by default.
   *  - order_by_defaults (array) array of order bys that should be applied by default.
   *  - custom_fields (array) array of entity types for custom fields (not usually required).
   *  - contact_type (string) optional restriction on contact type for some tables.
   *  - fields_excluded (array) fields that are in the generic set for the table but not in the report.
   *
   * @return array
   */
  protected function getColumns($type, $options = []) {
    $defaultOptions = [
      'prefix' => '',
      'prefix_label' => '',
      'fields' => TRUE,
      'group_bys' => FALSE,
      'order_bys' => TRUE,
      'filters' => TRUE,
      'join_filters' => FALSE,
      'fields_defaults' => [],
      'filters_defaults' => [],
      'group_bys_defaults' => [],
      'order_bys_defaults' => [],
    ];
    $options = array_merge($defaultOptions, $options);

    $fn = 'get' . $type . 'Columns';
    return $this->$fn($options);
  }

  /**
   * Get columns for contact table.
   *
   * @param array $options
   *
   * @return array
   */
  protected function getContactColumns($options = []) {
    $defaultOptions = [
      'custom_fields' => CRM_Contact_BAO_ContactType::basicTypes(),
      'fields_defaults' => ['display_name', 'id'],
      'order_bys_defaults' => ['sort_name ASC'],
      'contact_type' => NULL,
    ];

    $options = array_merge($defaultOptions, $options);

    $tableAlias = $options['prefix'] . 'contact';

    $spec = [
      $options['prefix'] . 'display_name' => [
        'name' => 'display_name',
        'title' => $options['prefix_label'] . ts('Contact Name'),
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'sort_name' => [
        'name' => 'sort_name',
        'title' => $options['prefix_label'] . ts('Contact Name (in sort format)'),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'id' => [
        'name' => 'id',
        'title' => $options['prefix_label'] . ts('Contact ID'),
        'alter_display' => 'alterContactID',
        'type' => CRM_Utils_Type::T_INT,
        'is_order_bys' => TRUE,
        'is_group_bys' => TRUE,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
      ],
      $options['prefix'] . 'contact_type' => [
        'title' => $options['prefix_label'] . ts('Contact Type'),
        'name' => 'contact_type',
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Contact_BAO_Contact::buildOptions('contact_type'),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'contact_sub_type' => [
        'title' => $options['prefix_label'] . ts('Contact Sub Type'),
        'name' => 'contact_sub_type',
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Contact_BAO_Contact::buildOptions('contact_sub_type'),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'is_deleted' => [
        'title' => $options['prefix_label'] . ts('Is deleted'),
        'name' => 'is_deleted',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'is_fields' => FALSE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_SELECT,
        'options' => [
          '' => ts('Any'),
          '0' => ts('No'),
          '1' => ts('Yes'),
        ],
        'is_group_bys' => FALSE,
      ],
      $options['prefix'] . 'external_identifier' => [
        'title' => $options['prefix_label'] . ts('Contact identifier from external system'),
        'name' => 'external_identifier',
        'is_fields' => TRUE,
        'is_filters' => FALSE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_group_bys' => FALSE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'preferred_language' => [
        'title' => $options['prefix_label'] . ts('Preferred Language'),
        'name' => 'preferred_language',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'preferred_communication_method' => [
        'title' => $options['prefix_label'] . ts('Preferred Communication Method'),
        'alter_display' => 'alterCommunicationtMethod',
        'name' => 'preferred_communication_method',
        'is_fields' => TRUE,
        'is_filters' => FALSE,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'is_group_bys' => FALSE,
        'is_order_bys' => FALSE,
      ],
    ];
    foreach ([
      'postal_greeting_display' => 'Postal Greeting',
      'email_greeting_display' => 'Email Greeting',
      'addressee_display' => 'Addressee',
    ] as $field => $title) {
      $spec[$options['prefix'] . $field] = [
        'title' => $options['prefix_label'] . _ts($title),
        'name' => $field,
        'is_fields' => TRUE,
        'is_filters' => FALSE,
        'is_group_bys' => FALSE,
      ];
    }
    foreach (['do_not_email', 'do_not_phone', 'do_not_mail', 'do_not_sms', 'do_not_trade', 'is_opt_out'] as $field) {
      $spec[$options['prefix'] . $field] = [
        'name' => $field,
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => FALSE,
      ];
    }
    $individualFields = [
      $options['prefix'] . 'first_name' => [
        'name' => 'first_name',
        'title' => $options['prefix_label'] . ts('First Name'),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'middle_name' => [
        'name' => 'middle_name',
        'title' => $options['prefix_label'] . ts('Middle Name'),
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'last_name' => [
        'name' => 'last_name',
        'title' => $options['prefix_label'] . ts('Last Name'),
        'default_order' => 'ASC',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'nick_name' => [
        'name' => 'nick_name',
        'title' => $options['prefix_label'] . ts('Nick Name'),
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'prefix_id' => [
        'name' => 'prefix_id',
        'title' => $options['prefix_label'] . ts('Prefix'),
        'options' => CRM_Contact_BAO_Contact::buildOptions('prefix_id'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
      ],
      $options['prefix'] . 'suffix_id' => [
        'name' => 'suffix_id',
        'title' => $options['prefix_label'] . ts('Suffix'),
        'options' => CRM_Contact_BAO_Contact::buildOptions('suffix_id'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
      ],
      $options['prefix'] . 'gender_id' => [
        'name' => 'gender_id',
        'title' => $options['prefix_label'] . ts('Gender'),
        'options' => CRM_Contact_BAO_Contact::buildOptions('gender_id'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
      ],
      'birth_date' => [
        'title' => $options['prefix_label'] . ts('Birth Date'),
        'operatorType' => CRM_Report_Form::OP_DATE,
        'type' => CRM_Utils_Type::T_DATE,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
      ],
      'age' => [
        'title' => $options['prefix_label'] . ts('Age'),
        'dbAlias' => 'TIMESTAMPDIFF(YEAR, ' . $tableAlias . '_civireport.birth_date, CURDATE())',
        'type' => CRM_Utils_Type::T_INT,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'is_deceased' => [
        'title' => $options['prefix_label'] . ts('Is deceased'),
        'name' => 'is_deceased',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'is_fields' => FALSE,
        'is_filters' => TRUE,
        'options' => [
          '' => ts('Any'),
          '0' => ts('No'),
          '1' => ts('Yes'),
        ],
        'is_group_bys' => FALSE,
      ],
      $options['prefix'] . 'job_title' => [
        'name' => 'job_title',
        'is_fields' => TRUE,
        'is_filters' => FALSE,
        'is_group_bys' => FALSE,
      ],
      $options['prefix'] . 'employer_id' => [
        'title' => $options['prefix_label'] . ts('Current Employer'),
        'type' => CRM_Utils_Type::T_INT,
        'name' => 'employer_id',
        'is_fields' => TRUE,
        'is_filters' => FALSE,
        'is_group_bys' => TRUE,
      ],
    ];
    if (!$options['contact_type'] || $options['contact_type'] === 'Individual') {
      $spec = array_merge($spec, $individualFields);
    }

    return $this->buildColumns($spec, $options['prefix'] . 'civicrm_contact', 'CRM_Contact_DAO_Contact', $tableAlias, $this->getDefaultsFromOptions($options), $options);
  }

  /**
   * Get address columns to add to array.
   *
   * @param array $options
   *  - prefix Prefix to add to table (in case of more than one instance of the table)
   *  - prefix_label Label to give columns from this address table instance
   *  - group_bys enable these fields for group by - default false
   *  - order_bys enable these fields for order by
   *  - filters enable these fields for filtering
   *
   * @return array address columns definition
   */
  protected function getAddressColumns($options = []) {
    $defaultOptions = [
      'prefix' => '',
      'prefix_label' => '',
      'fields' => TRUE,
      'group_bys' => FALSE,
      'order_bys' => TRUE,
      'filters' => TRUE,
      'join_filters' => FALSE,
      'fields_defaults' => [],
      'filters_defaults' => [],
      'group_bys_defaults' => [],
      'order_bys_defaults' => [],
    ];

    $options = array_merge($defaultOptions, $options);
    $defaults = $this->getDefaultsFromOptions($options);
    $tableAlias = $options['prefix'] . 'address';

    $spec = [
      $options['prefix'] . 'name' => [
        'title' => $options['prefix_label'] . ts('Address Name'),
        'name' => 'name',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_number' => [
        'name' => 'street_number',
        'title' => $options['prefix_label'] . ts('Street Number'),
        'type' => 1,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'odd_street_number' => [
        'title' => ts('Odd / Even Street Number'),
        'name' => 'odd_street_number',
        'type' => CRM_Utils_Type::T_INT,
        'no_display' => TRUE,
        'required' => TRUE,
        'dbAlias' => "({$tableAlias}_civireport.street_number % 2)",
        'is_fields' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'street_name' => [
        'name' => 'street_name',
        'title' => $options['prefix_label'] . ts('Street Name'),
        'type' => 1,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'operator' => 'like',
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'street_address' => [
        'title' => $options['prefix_label'] . ts('Street Address'),
        'name' => 'street_address',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_1' => [
        'title' => $options['prefix_label'] . ts('Supplementary Address Field 1'),
        'name' => 'supplemental_address_1',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_2' => [
        'title' => $options['prefix_label'] . ts('Supplementary Address Field 2'),
        'name' => 'supplemental_address_2',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_3' => [
        'title' => $options['prefix_label'] . ts('Supplementary Address Field 3'),
        'name' => 'supplemental_address_3',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_number' => [
        'name' => 'street_number',
        'title' => $options['prefix_label'] . ts('Street Number'),
        'type' => 1,
        'is_order_bys' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_unit' => [
        'name' => 'street_unit',
        'title' => $options['prefix_label'] . ts('Street Unit'),
        'type' => 1,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'city' => [
        'title' => $options['prefix_label'] . ts('City'),
        'name' => 'city',
        'operator' => 'like',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'postal_code' => [
        'title' => $options['prefix_label'] . ts('Postal Code'),
        'name' => 'postal_code',
        'type' => 2,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'postal_code_suffix' => [
        'title' => $options['prefix_label'] . ts('Postal Code Suffix'),
        'name' => 'postal_code_suffix',
        'type' => 2,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operatorType' => CRM_Report_Form::OP_STRING,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'county_id' => [
        'title' => $options['prefix_label'] . ts('County'),
        'alter_display' => 'alterCountyID',
        'name' => 'county_id',
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::county(),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'state_province_id' => [
        'title' => $options['prefix_label'] . ts('State/Province'),
        'alter_display' => 'alterStateProvinceID',
        'name' => 'state_province_id',
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::stateProvince(),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_order_bys' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'country_id' => [
        'title' => $options['prefix_label'] . ts('Country'),
        'alter_display' => 'alterCountryID',
        'name' => 'country_id',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_order_bys' => TRUE,
        'is_group_bys' => TRUE,
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::country(),
      ],
      $options['prefix'] . 'location_type_id' => [
        'name' => 'location_type_id',
        'title' => $options['prefix_label'] . ts('Location Type'),
        'type' => CRM_Utils_Type::T_INT,
        'is_fields' => TRUE,
        'alter_display' => 'alterLocationTypeID',
      ],
      $options['prefix'] . 'id' => [
        'title' => $options['prefix_label'] . ts('ID'),
        'name' => 'id',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'is_primary' => [
        'name' => 'is_primary',
        'title' => $options['prefix_label'] . ts('Primary Address?'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'is_fields' => TRUE,
      ],
    ];
    return $this->buildColumns($spec, $options['prefix'] . 'civicrm_address', 'CRM_Core_DAO_Address', $tableAlias, $defaults, $options);
  }

  /**
   * Build the columns.
   *
   * The normal report class needs you to remember to do a few things that are often erratic
   *
   * 1) use a unique key for any field that might not be unique (e.g. start date, label)
   * - this class will prepend an alias to the key & set the 'name' if you don't set it yourself.
   *  You can suppress the alias with 'no_field_disambiguation' if transitioning existing reports. This
   *  means any saved filters / fields on saved report instances. This will mean that matching names from
   *  different tables may be ambigious, but it will smooth any code transition.
   * - note that it assumes the value being passed in is the actual table field name
   *
   * 2) set the field & set it to no display if you don't want the field but you might want to use the field in other
   * contexts - the code looks up the fields array for data - so it both defines the field spec & the fields you want to show
   *
   * 3) this function also sets the 'metadata' array - the extended report class now uses this in place
   *  of the fields array to reduce the issues caused when metadata is needed but 'fields' are not defined. Code in
   *  the core classes can start to move towards that.
   *
   * @param array $specs
   * @param string $tableName
   * @param string $daoName
   * @param string $tableAlias
   * @param array $defaults
   * @param array $options
   *
   * @return array
   */
  protected function buildColumns($specs, $tableName, $daoName = NULL, $tableAlias = NULL, $defaults = [], $options = []) {
    if (!$tableAlias) {
      $tableAlias = str_replace('civicrm_', '', $tableName);
    }
    $types = ['filters', 'group_bys', 'order_bys', 'join_filters'];
    $columns = [$tableName => array_fill_keys($types, [])];
    // The code that uses this no longer cares if it is a DAO or BAO so just call it a DAO.
    $columns[$tableName]['dao'] = $daoName;
    $columns[$tableName]['alias'] = $tableAlias;

    foreach ($specs as $specName => $spec) {
      if (empty($spec['name'])) {
        $spec['name'] = $specName;
      }

      $fieldAlias = (empty($options['no_field_disambiguation']) ? $tableAlias . '_' : '') . $specName;
      $columns[$tableName]['metadata'][$fieldAlias] = $spec;
      $columns[$tableName]['fields'][$fieldAlias] = $spec;
      if (isset($defaults['fields_defaults']) && in_array($spec['name'], $defaults['fields_defaults'])) {
        $columns[$tableName]['fields'][$fieldAlias]['default'] = TRUE;
      }

      if (!$spec['is_fields'] || (isset($options['fields_excluded']) && in_array($specName, $options['fields_excluded']))) {
        $columns[$tableName]['fields'][$fieldAlias]['no_display'] = TRUE;
      }

      if (isset($options['fields_required']) && in_array($specName, $options['fields_required'])) {
        $columns[$tableName]['fields'][$fieldAlias]['required'] = TRUE;
      }

      foreach ($types as $type) {
        if ($options[$type] && !empty($spec['is_' . $type])) {
          $columns[$tableName][$type][$fieldAlias] = $spec;
          if (isset($defaults[$type . '_defaults']) && isset($defaults[$type . '_defaults'][$spec['name']])) {
            $columns[$tableName][$type][$fieldAlias]['default'] = $defaults[$type . '_defaults'][$spec['name']];
          }
        }
      }
    }
    if (isset($options['grouping'])) {
      $columns[$tableName]['grouping'] = $options['grouping'];
      $columns[$tableName]['group_title'] = $options['group_title'] ?? '';
    }
    return $columns;
  }

  /**
   * Store group bys into array - so we can check elsewhere what is grouped.
   */
  protected function storeGroupByArray() {

    if (empty($this->_params['group_bys'])
      || !is_array($this->_params['group_bys'])) {
      $this->_params['group_bys'] = [];
    }

    foreach ($this->_columns as $tableName => $table) {
      $table = $this->_columns[$tableName];
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $fieldData) {
          $field = $this->_columns[$tableName]['metadata'][$fieldName];
          if (!empty($this->_params['group_bys'][$fieldName]) || !empty($fieldData['required'])) {
            if (!empty($field['chart'])) {
              $this->assign('chartSupported', TRUE);
            }

            if (!empty($table['group_bys'][$fieldName]['frequency']) &&
              !empty($this->_params['group_bys_freq'][$fieldName])
            ) {

              switch ($this->_params['group_bys_freq'][$fieldName]) {
                case 'FISCALYEAR':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = self::fiscalYearOffset($field['dbAlias']);
                  break;

                case 'YEAR':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = " YEAR({$field['dbAlias']})";
                  break;

                case 'QUARTER':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = "YEAR({$field['dbAlias']}), QUARTER({$field['dbAlias']})";
                  break;

                case 'YEARWEEK':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = "YEARWEEK({$field['dbAlias']})";
                  break;

                case 'MONTH':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = "EXTRACT(YEAR_MONTH FROM {$field['dbAlias']})";
                  break;

                case 'DATE':
                  $this->_groupByArray[$tableName . '_' . $fieldName . '_start'] = "DATE({$field['dbAlias']})";
                  break;
              }
            }
            else {
              if (!in_array($field['dbAlias'], $this->_groupByArray)) {
                $this->_groupByArray[$tableName . '_' . $fieldName] = $field['dbAlias'];
              }
            }
          }
        }

      }
    }
  }

  /**
   * @param array $options
   *
   * @return array
   */
  protected function getDefaultsFromOptions($options) {
    $defaults = [
      'fields_defaults' => $options['fields_defaults'],
      'filters_defaults' => $options['filters_defaults'],
      'group_bys_defaults' => $options['group_bys_defaults'],
      'order_bys_defaults' => $options['order_bys_defaults'],
    ];
    return $defaults;
  }

  /**
   * Get the select clause for a field, wrapping in GROUP_CONCAT if appropriate.
   *
   * Full group by mode dictates that a field must either be in the group by function or
   * wrapped in a aggregate function. Here we wrap the field in GROUP_CONCAT if it is not in the
   * group concat.
   *
   * @param string $tableName
   * @param string $fieldName
   * @param array $field
   * @return string
   */
  protected function getSelectClauseWithGroupConcatIfNotGroupedBy($tableName, &$fieldName, &$field) {
    if ($this->groupConcatTested && (!empty($this->_groupByArray) || $this->isForceGroupBy)) {
      if ((empty($field['statistics']) || in_array('GROUP_CONCAT', $field['statistics']))) {
        $label = $field['title'] ?? NULL;
        $alias = $field['tplField'] ?? "{$tableName}_{$fieldName}";
        $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $label;
        $this->_selectAliases[] = $alias;
        if (empty($this->_groupByArray[$tableName . '_' . $fieldName])) {
          return "GROUP_CONCAT(DISTINCT {$field['dbAlias']}) as $alias";
        }
        return "({$field['dbAlias']}) as $alias";
      }
    }
  }

  /**
   * Generate clause for the selected filter.
   *
   * @param array $field
   *   Field specification
   * @param string $fieldName
   *   Field name.
   *
   * @return string
   *   Relevant where clause.
   */
  protected function generateFilterClause($field, $fieldName) {
    if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
      if (($field['operatorType'] ?? NULL) ==
        CRM_Report_Form::OP_MONTH
      ) {
        $op = $this->_params["{$fieldName}_op"] ?? NULL;
        $value = $this->_params["{$fieldName}_value"] ?? NULL;
        if (is_array($value) && !empty($value)) {
          return "(month({$field['dbAlias']}) $op (" . implode(', ', $value) .
            '))';
        }
      }
      else {
        $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
        $from = $this->_params["{$fieldName}_from"] ?? NULL;
        $to = $this->_params["{$fieldName}_to"] ?? NULL;
        $fromTime = $this->_params["{$fieldName}_from_time"] ?? NULL;
        $toTime = $this->_params["{$fieldName}_to_time"] ?? NULL;
        return $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type'], $fromTime, $toTime);
      }
    }
    else {
      $op = $this->_params["{$fieldName}_op"] ?? NULL;
      if ($op) {
        return $this->whereClause($field,
          $op,
          $this->_params["{$fieldName}_value"] ?? NULL,
          $this->_params["{$fieldName}_min"] ?? NULL,
          $this->_params["{$fieldName}_max"] ?? NULL
        );
      }
    }
    return '';
  }

  /**
   * Retrieve a suitable object from the factory depending on the report
   * parameters, which typically might just be dependent on outputMode.
   *
   * If there is no suitable output handler, e.g. if outputMode is "copy",
   * then this sets it to NULL.
   */
  public function setOutputHandler(): void {
    $this->outputHandler = OutputHandlerFactory::singleton()->create($this);
  }

  /**
   * Send report by email
   */
  public function sendEmail() {
    if (empty($this->outputHandler)) {
      // It's possible to end up here with outputMode unset, so we use
      // the "print" handler which was the default before, i.e. include
      // it as html in the body.
      $oldOutputMode = $this->_outputMode ?? NULL;
      $this->_outputMode = 'print';
      $this->setOutputHandler();
      $this->_outputMode = $oldOutputMode;
    }

    $mailBody = $this->outputHandler->getMailBody();

    $attachments = [];
    $attachmentFileName = $this->outputHandler->getFileName();
    // It's not always in the form of an attachment, e.g. for 'print' the
    // output ends up in $mailBody above.
    if ($attachmentFileName) {
      $fullFilename = CRM_Core_Config::singleton()->templateCompileDir . CRM_Utils_File::makeFileName($attachmentFileName);
      file_put_contents($fullFilename, $this->outputHandler->getOutputString());
      $attachments[] = [
        'fullPath' => $fullFilename,
        'mime_type' => $this->outputHandler->getMimeType(),
        'cleanName' => $attachmentFileName,
        'charset' => $this->outputHandler->getCharset(),
      ];
    }

    // Send the email
    // @todo outputMode doesn't seem to get used by mailReport, which is good
    // since it shouldn't have any outputMode-related `if` statements in it.
    // Someday could remove the param from the function call.
    if (CRM_Report_Utils_Report::mailReport($mailBody, $this->_id, $this->_outputMode, $attachments)) {
      CRM_Core_Session::setStatus(ts('Report mail has been sent.'), ts('Sent'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('Report mail could not be sent.'), ts('Mail Error'), 'error');
    }
  }

}
