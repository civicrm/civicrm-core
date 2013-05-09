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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: $
 *
 */

abstract class CRM_Utils_Hook {

  // Allowed values for dashboard hook content placement
  // Default - place content below activity list
  CONST DASHBOARD_BELOW = 1;
  // Place content above activity list
  CONST DASHBOARD_ABOVE = 2;
  // Don't display activity list at all
  CONST DASHBOARD_REPLACE = 3;

  // by default - place content below existing content
  CONST SUMMARY_BELOW = 1;
  // pace hook content above
  CONST SUMMARY_ABOVE = 2;
  // create your own summarys
  CONST SUMMARY_REPLACE = 3;

  static $_nullObject = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * @var bool
   */
  private $commonIncluded = FALSE;

  /**
   * @var array(string)
   */
  private $commonCiviModules = array();

  /**
   * Constructor and getter for the singleton instance
   *
   * @return instance of $config->userHookClass
   */
  static function singleton($fresh = FALSE) {
    if (self::$_singleton == NULL || $fresh) {
      $config = CRM_Core_Config::singleton();
      $class = $config->userHookClass;
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
      self::$_singleton = new $class();
    }
    return self::$_singleton;
  }

  abstract function invoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5,
    $fnSuffix
  );

  function commonInvoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5,
    $fnSuffix, $fnPrefix
  ) {

    $this->commonBuildModuleList($fnPrefix);

    return $this->runHooks($this->commonCiviModules, $fnSuffix,
      $numParams, $arg1, $arg2, $arg3, $arg4, $arg5
    );
  }

  /**
   * Build the list of modules to be processed for hooks.
   */
  function commonBuildModuleList($fnPrefix) {
    if (!$this->commonIncluded) {
      // include external file
      $this->commonIncluded = TRUE;

      $config = CRM_Core_Config::singleton();
      if (!empty($config->customPHPPathDir) &&
        file_exists("{$config->customPHPPathDir}/civicrmHooks.php")
      ) {
        @include_once ("civicrmHooks.php");
      }

      if (!empty($fnPrefix)) {
        $this->commonCiviModules[$fnPrefix] = $fnPrefix;
      }

      $this->requireCiviModules($this->commonCiviModules);
    }
  }

  function runHooks(&$civiModules, $fnSuffix, $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5
  ) {
    $result = $fResult = array();

    if ($civiModules !== NULL) {
      foreach ($civiModules as $module) {
        $fnName = "{$module}_{$fnSuffix}";
        if (function_exists($fnName)) {
          switch ($numParams) {
            case 0:
              $fResult = $fnName();
              break;

            case 1:
              $fResult = $fnName($arg1);
              break;

            case 2:
              $fResult = $fnName($arg1, $arg2);
              break;

            case 3:
              $fResult = $fnName($arg1, $arg2, $arg3);
              break;

            case 4:
              $fResult = $fnName($arg1, $arg2, $arg3, $arg4);
              break;

            case 5:
              $fResult = $fnName($arg1, $arg2, $arg3, $arg4, $arg5);
              break;

            default:
              CRM_Core_Error::fatal(ts('Invalid hook invocation'));
              break;
          }
        }

        if (!empty($fResult) &&
          is_array($fResult)) {
          $result = array_merge($result, $fResult);
        }
      }
    }

    return empty($result) ? TRUE : $result;
  }

  function requireCiviModules(&$moduleList) {
    $civiModules = CRM_Core_PseudoConstant::getModuleExtensions();
    foreach ($civiModules as $civiModule) {
      if (!file_exists($civiModule['filePath'])) {
        CRM_Core_Session::setStatus(
        	ts( 'Error loading module file (%1). Please restore the file or disable the module.', array(1 => $civiModule['filePath']) ),
        	ts( 'Warning'), 'error');
        continue;
      }
      include_once $civiModule['filePath'];
      $moduleList[$civiModule['prefix']] = $civiModule['prefix'];
      }
    }

  /**
   * This hook is called before a db write on some core objects.
   * This hook does not allow the abort of the operation
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param object $id         the object id if available
   * @param array  $params     the parameters used for object creation / editing
   *
   * @return null the return value is ignored
   * @access public
   */
  static function pre($op, $objectName, $id, &$params) {
    return self::singleton()->invoke(4, $op, $objectName, $id, $params, $op, 'civicrm_pre');
  }

  /**
   * This hook is called after a db write on some core objects.
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param int    $objectId   the unique identifier for the object
   * @param object $objectRef  the reference to the object if available
   *
   * @return mixed             based on op. pre-hooks return a boolean or
   *                           an error message which aborts the operation
   * @access public
   */
  static function post($op, $objectName, $objectId, &$objectRef) {
    return self::singleton()->invoke(4, $op, $objectName, $objectId, $objectRef, $op, 'civicrm_post');
  }

  /**
   * This hook to assessment grant
   *
   * @param string $param       the params
   *
   * @access public
   */
  static function grantAssessment( &$params ) {
    return self::singleton( )->invoke( 1, $params, self::$_nullObject , self::$_nullObject,  self::$_nullObject, self::$_nullObject, 'civicrm_grantAssessment' );
  }

  /**
   * This hook retrieves links from other modules and injects it into
   * the view contact tabs
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param int    $objectId   the unique identifier for the object
   * @params array $links      (optional ) the links array (introduced in v3.2)
   *
   * @return array|null        an array of arrays, each element is a tuple consisting of id, url, img, title, weight
   *
   * @access public
   */
  static function links($op, $objectName, &$objectId, &$links, &$mask = NULL) {
    return self::singleton()->invoke(5, $op, $objectName, $objectId, $links, $mask, 'civicrm_links');
  }

  /**
   * This hook is invoked when building a CiviCRM form. This hook should also
   * be used to set the default values of a form element
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function buildForm($formName, &$form) {
    return self::singleton()->invoke(2, $formName, $form, $formName, $formName, $formName, 'civicrm_buildForm');
  }

  /**
   * This hook is invoked when a CiviCRM form is submitted. If the module has injected
   * any form elements, this hook should save the values in the database
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function postProcess($formName, &$form) {
    return self::singleton()->invoke(2, $formName, $form, $formName, $formName, $formName, 'civicrm_postProcess');
  }

  /**
   * This hook is invoked during all CiviCRM form validation. An array of errors
   * detected is returned. Else we assume validation succeeded.
   *
   * @param string $formName the name of the form
   * @param array  &$fields   the POST parameters as filtered by QF
   * @param array  &$files    the FILES parameters as sent in by POST
   * @param array  &$form     the form object
   *
   * @return mixed             formRule hooks return a boolean or
   *                           an array of error messages which display a QF Error
   * @access public
   */
  static function validate($formName, &$fields, &$files, &$form) {
    return self::singleton()->invoke(4, $formName, $fields, $files, $form, $formName, 'civicrm_validate');
  }

  /**
   * This hook is invoked during all CiviCRM form validation. An array of errors
   * detected is returned. Else we assume validation succeeded.
   *
   * @param string $formName  the name of the form
   * @param array  &$fields   the POST parameters as filtered by QF
   * @param array  &$files    the FILES parameters as sent in by POST
   * @param array  &$form     the form object
   * @param array &$errors    the array of errors.
   *
   * @return mixed             formRule hooks return a boolean or
   *                           an array of error messages which display a QF Error
   * @access public
   */
  static function validateForm($formName, &$fields, &$files, &$form, &$errors) {
    return self::singleton()->invoke(5, $formName, $fields, $files, $form, $errors, 'civicrm_validateForm');
  }

  /**
   * This hook is called before a db write on a custom table
   *
   * @param string $op         the type of operation being performed
   * @param string $groupID    the custom group ID
   * @param object $entityID   the entityID of the row in the custom table
   * @param array  $params     the parameters that were sent into the calling function
   *
   * @return null the return value is ignored
   * @access public
   */
  static function custom($op, $groupID, $entityID, &$params) {
    return self::singleton()->invoke(4, $op, $groupID, $entityID, $params, $op, 'civicrm_custom');
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int $type the type of permission needed
   * @param array $tables (reference ) add the tables that are needed for the select clause
   * @param array $whereTables (reference ) add the tables that are needed for the where clause
   * @param int    $contactID the contactID for whom the check is made
   * @param string $where the currrent where clause
   *
   * @return null the return value is ignored
   * @access public
   */
  static function aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    return self::singleton()->invoke(5, $type, $tables, $whereTables, $contactID, $where, 'civicrm_aclWhereClause');
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int    $type          the type of permission needed
   * @param int    $contactID     the contactID for whom the check is made
   * @param string $tableName     the tableName which is being permissioned
   * @param array  $allGroups     the set of all the objects for the above table
   * @param array  $currentGroups the set of objects that are currently permissioned for this contact
   *
   * @return null the return value is ignored
   * @access public
   */
  static function aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    return self::singleton()->invoke(5, $type, $contactID, $tableName, $allGroups, $currentGroups, 'civicrm_aclGroup');
  }

  /**
   * This hook is called when building the menu table
   *
   * @param array $files The current set of files to process
   *
   * @return null the return value is ignored
   * @access public
   */
  static function xmlMenu(&$files) {
    return self::singleton()->invoke(1, $files,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_xmlMenu'
    );
  }

  /**
   * This hook is called for declaring managed entities via API
   *
   * @param array $entities List of pending entities
   *
   * @return null the return value is ignored
   * @access public
   */
  static function managed(&$entities) {
    return self::singleton()->invoke(1, $entities,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_managed'
    );
  }

  /**
   * This hook is called when rendering the dashboard (q=civicrm/dashboard)
   *
   * @param int $contactID - the contactID for whom the dashboard is being rendered
   * @param int $contentPlacement - (output parameter) where should the hook content be displayed relative to the activity list
   *
   * @return string the html snippet to include in the dashboard
   * @access public
   */
  static function dashboard($contactID, &$contentPlacement = self::DASHBOARD_BELOW) {
    $retval = self::singleton()->invoke(2, $contactID, $contentPlacement,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_dashboard'
    );

    /*
     * Note we need this seemingly unnecessary code because in the event that the implementation
     * of the hook declares the second parameter but doesn't set it, then it comes back unset even
     * though we have a default value in this function's declaration above.
     */
    if (!isset($contentPlacement)) {
      $contentPlacement = self::DASHBOARD_BELOW;
    }

    return $retval;
  }

  /**
   * This hook is called before storing recently viewed items.
   *
   * @param array $recentArray - an array of recently viewed or processed items, for in place modification
   *
   * @return array
   * @access public
   */
  static function recent(&$recentArray) {
    return self::singleton()->invoke(1, $recentArray,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_recent'
    );
  }

  /**
   * This hook is called when building the amount structure for a Contribution or Event Page
   *
   * @param int    $pageType - is this a contribution or event page
   * @param object $form     - reference to the form object
   * @param array  $amount   - the amount structure to be displayed
   *
   * @return null
   * @access public
   */
  static function buildAmount($pageType, &$form, &$amount) {
    return self::singleton()->invoke(3, $pageType, $form, $amount, self::$_nullObject, self::$_nullObject, 'civicrm_buildAmount');
  }

  /**
   * This hook is called when building the state list for a particular country.
   *
   * @param array  $countryID - the country id whose states are being selected.
   *
   * @return null
   * @access public
   */
  static function buildStateProvinceForCountry($countryID, &$states) {
    return self::singleton()->invoke(2, $countryID, $states,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_buildStateProvinceForCountry'
    );
  }

  /**
   * This hook is called when rendering the tabs for a contact (q=civicrm/contact/view)c
   *
   * @param array $tabs      - the array of tabs that will be displayed
   * @param int   $contactID - the contactID for whom the dashboard is being rendered
   *
   * @return null
   * @access public
   */
  static function tabs(&$tabs, $contactID) {
    return self::singleton()->invoke(2, $tabs, $contactID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_tabs'
    );
  }

  /**
   * This hook is called when sending an email / printing labels
   *
   * @param array $tokens    - the list of tokens that can be used for the contact
   *
   * @return null
   * @access public
   */
  static function tokens(&$tokens) {
    return self::singleton()->invoke(1, $tokens,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_tokens'
    );
  }

  /**
   * This hook is called when sending an email / printing labels to get the values for all the
   * tokens returned by the 'tokens' hook
   *
   * @param array  $details    - the array to store the token values indexed by contactIDs (unless it a single)
   * @param array  $contactIDs - an array of contactIDs
   * @param int    $jobID      - the jobID if this is associated with a CiviMail mailing
   * @param array  $tokens     - the list of tokens associated with the content
   * @param string $className  - the top level className from where the hook is invoked
   *
   * @return null
   * @access public
   */
  static function tokenValues(&$details,
    $contactIDs,
    $jobID     = NULL,
    $tokens    = array(),
    $className = NULL
  ) {
    return self::singleton()->invoke(5, $details, $contactIDs, $jobID, $tokens, $className, 'civicrm_tokenValues');
  }

  /**
   * This hook is called before a CiviCRM Page is rendered. You can use this hook to insert smarty variables
   * in a  template
   *
   * @param object $page - the page that will be rendered
   *
   * @return null
   * @access public
   */
  static function pageRun(&$page) {
    return self::singleton()->invoke(1, $page,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_pageRun'
    );
  }

  /**
   * This hook is called after a copy of an object has been made. The current objects are
   * Event, Contribution Page and UFGroup
   *
   * @param string $objectName - name of the object
   * @param object $object     - reference to the copy
   *
   * @return null
   * @access public
   */
  static function copy($objectName, &$object) {
    return self::singleton()->invoke(2, $objectName, $object,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_copy'
    );
  }

  /**
   * This hook is called when a contact unsubscribes from a mailing.  It allows modules
   * to override what the contacts are removed from.
   *
   * @param int $mailing_id - the id of the mailing to unsub from
   * @param int $contact_id - the id of the contact who is unsubscribing
   * @param array / int $groups - array of groups the contact will be removed from
   **/
  static function unsubscribeGroups($op, $mailingId, $contactId, &$groups, &$baseGroups) {
    return self::singleton()->invoke(5, $op, $mailingId, $contactId, $groups, $baseGroups, 'civicrm_unsubscribeGroups');
  }

  /**
   * This hook is called when CiviCRM needs to edit/display a custom field with options (select, radio, checkbox, adv multiselect)
   *
   * @param int $customFieldID - the custom field ID
   * @param array $options - the current set of options for that custom field.
   *   You can add/remove existing options.
   *   Important: This array may contain meta-data about the field that is needed elsewhere, so it is important to be careful to not overwrite the array.
   *   Only add/edit/remove the specific field options you intend to affect.
   * @param boolean $detailedFormat - if true, the options are in an ID => array ( 'id' => ID, 'label' => label, 'value' => value ) format
   */
  static function customFieldOptions($customFieldID, &$options, $detailedFormat = FALSE) {
    return self::singleton()->invoke(3, $customFieldID, $options, $detailedFormat,
      self::$_nullObject, self::$_nullObject,
      'civicrm_customFieldOptions'
    );
  }

  /**
   *
   * This hook is called to display the list of actions allowed after doing a search.
   * This allows the module developer to inject additional actions or to remove existing actions.
   *
   * @param string $objectType - the object type for this search
   *   - activity, campaign, case, contact, contribution, event, grant, membership, and pledge are supported.
   * @param array $tasks - the current set of tasks for that custom field.
   *   You can add/remove existing tasks.
   *   Each task needs to have a title (eg 'title'  => ts( 'Add Contacts to Group')) and a class (eg 'class'  => 'CRM_Contact_Form_Task_AddToGroup').
   *   Optional result (boolean) may also be provided. Class can be an array of classes (not sure what that does :( ).
   *   The key for new Task(s) should not conflict with the keys for core tasks of that $objectType, which can be found in CRM/$objectType/Task.php.
   */
  static function searchTasks($objectType, &$tasks) {
    return self::singleton()->invoke(2, $objectType, $tasks,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_searchTasks'
    );
  }

  static function eventDiscount(&$form, &$params) {
    return self::singleton()->invoke(2, $form, $params,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_eventDiscount'
    );
  }

  /**
   * This hook is called when composing a mailing. You can include / exclude other groups as needed.
   *
   * @param unknown_type $form - the form object for which groups / mailings being displayed
   * @param array $groups - the list of groups being included / excluded
   * @param array $mailings - the list of mailings being included / excluded
   */
  static function mailingGroups(&$form, &$groups, &$mailings) {
    return self::singleton()->invoke(3, $form, $groups, $mailings,
      self::$_nullObject, self::$_nullObject,
      'civicrm_mailingGroups'
    );
  }

  /**
   * This hook is called when composing the array of membershipTypes and their cost during a membership registration (new or renewal).
   * Note the hook is called on initial page load and also reloaded after submit (PRG pattern).
   * You can use it to alter the membership types when first loaded, or after submission
   * (for example if you want to gather data in the form and use it to alter the fees).
   *
   * @param unknown_type $form -  the form object that is presenting the page
   * @param array $membershipTypes - the array of membership types and their amount
   */
  static function membershipTypeValues(&$form, &$membershipTypes) {
    return self::singleton()->invoke(2, $form, $membershipTypes,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_membershipTypeValues'
    );
  }

  /**
   * This hook is called when rendering the contact summary
   *
   * @param int $contactID - the contactID for whom the summary is being rendered
   * @param int $contentPlacement - (output parameter) where should the hook content be displayed relative to the existing content
   *
   * @return string the html snippet to include in the contact summary
   * @access public
   */
  static function summary($contactID, &$content, &$contentPlacement = self::SUMMARY_BELOW) {
    return self::singleton()->invoke(3, $contactID, $content, $contentPlacement,
      self::$_nullObject, self::$_nullObject,
      'civicrm_summary'
    );
  }

  /**
   * Use this hook to populate the list of contacts returned by Contact Reference custom fields.
   * By default, Contact Reference fields will search on and return all CiviCRM contacts.
   * If you want to limit the contacts returned to a specific group, or some other criteria
   * - you can override that behavior by providing a SQL query that returns some subset of your contacts.
   * The hook is called when the query is executed to get the list of contacts to display.
   *
   * @param unknown_type $query - - the query that will be executed (input and output parameter);
   *   It's important to realize that the ACL clause is built prior to this hook being fired,
   *   so your query will ignore any ACL rules that may be defined.
   *   Your query must return two columns:
   *     the contact 'data' to display in the autocomplete dropdown (usually contact.sort_name - aliased as 'data')
   *     the contact IDs
   * @param string $name - the name string to execute the query against (this is the value being typed in by the user)
   * @param string $context - the context in which this ajax call is being made (for example: 'customfield', 'caseview')
   * @param int $id - the id of the object for which the call is being made.
   *   For custom fields, it will be the custom field id
   */
  static function contactListQuery(&$query, $name, $context, $id) {
    return self::singleton()->invoke(4, $query, $name, $context, $id,
      self::$_nullObject,
      'civicrm_contactListQuery'
    );
  }

  /**
   * Hook definition for altering payment parameters before talking to a payment processor back end.
   *
   * Definition will look like this:
   *
   *   function hook_civicrm_alterPaymentProcessorParams($paymentObj,
   *                                                     &$rawParams, &$cookedParams);
   *
   * @param string $paymentObj
   *    instance of payment class of the payment processor invoked (e.g., 'CRM_Core_Payment_Dummy')
   * @param array &$rawParams
   *    array of params as passed to to the processor
   * @params array  &$cookedParams
   *     params after the processor code has translated them into its own key/value pairs
   *
   * @return void
   */
  static function alterPaymentProcessorParams($paymentObj,
    &$rawParams,
    &$cookedParams
  ) {
    return self::singleton()->invoke(3, $paymentObj, $rawParams, $cookedParams,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterPaymentProcessorParams'
    );
  }

  /**
   * This hook is called when an email is about to be sent by CiviCRM.
   *
   * @param array $params - array fields include: groupName, from, toName, toEmail, subject, cc, bcc, text, html, returnPath, replyTo, headers, attachments (array)
   * @param string $context - the context in which the hook is being invoked, eg 'civimail'
   */
  static function alterMailParams(&$params, $context = NULL) {
    return self::singleton()->invoke(2, $params, $context,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailParams'
    );
  }

  /**
   * This hook is called when rendering the Manage Case screen
   *
   * @param int $caseID - the case ID
   *
   * @return array of data to be displayed, where the key is a unique id to be used for styling (div id's) and the value is an array with keys 'label' and 'value' specifying label/value pairs
   * @access public
   */
  static function caseSummary($caseID) {
    return self::singleton()->invoke(1, $caseID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_caseSummary'
    );
  }

  /**
   * This hook is called soon after the CRM_Core_Config object has ben initialized.
   * You can use this hook to modify the config object and hence behavior of CiviCRM dynamically.

   * @param array $config -  the config object
   */
  static function config(&$config) {
    return self::singleton()->invoke(1, $config,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_config'
    );
  }

  static function enableDisable($recordBAO, $recordID, $isActive) {
    return self::singleton()->invoke(3, $recordBAO, $recordID, $isActive,
      self::$_nullObject, self::$_nullObject,
      'civicrm_enableDisable'
    );
  }

  /**
   * This hooks allows to change option values
   *
   * @param $options associated array of option values / id
   * @param $name    option group name
   *
   * @access public
   */
  static function optionValues(&$options, $name) {
    return self::singleton()->invoke(2, $options, $name,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_optionValues'
    );
  }

  /**
   * This hook allows modification of the navigation menu.
   *
   * @param $params associated array of navigation menu entry to Modify/Add
   * @access public
   */
  static function navigationMenu(&$params) {
    return self::singleton()->invoke(1, $params,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_navigationMenu'
    );
  }

  /**
   * This hook allows modification of the data used to perform merging of duplicates.
   *
   * @param string $type the type of data being passed (cidRefs|eidRefs|relTables|sqls)
   * @param array $data  the data, as described in $type
   * @param int $mainId  contact_id of the contact that survives the merge
   * @param int $otherId contact_id of the contact that will be absorbed and deleted
   * @param array $tables when $type is "sqls", an array of tables as it may have been handed to the calling function
   *
   * @access public
   */
  static function merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
    return self::singleton()->invoke(5, $type, $data, $mainId, $otherId, $tables, 'civicrm_merge');
  }

  /**
   * This hook provides a way to override the default privacy behavior for notes.
   *
   * @param array $note (reference) Associative array of values for this note
   *
   * @access public
   */
  static function notePrivacy(&$noteValues) {
    return self::singleton()->invoke(1, $noteValues,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_notePrivacy'
    );
  }

  /**
   * This hook is called before record is exported as CSV
   *
   * @param string $exportTempTable - name of the temporary export table used during export
   * @param array  $headerRows      - header rows for output
   * @param array  $sqlColumns      - SQL columns
   * @param int    $exportMode      - export mode ( contact, contribution, etc...)
   *
   * @return void
   * @access public
   */
  static function export(&$exportTempTable, &$headerRows, &$sqlColumns, &$exportMode) {
    return self::singleton()->invoke(4, $exportTempTable, $headerRows, $sqlColumns, $exportMode,
      self::$_nullObject,
      'civicrm_export'
    );
  }

  /**
   * This hook allows modification of the queries constructed from dupe rules.
   *
   * @param string $obj object of rulegroup class
   * @param string $type type of queries e.g table / threshold
   * @param array  $query set of queries
   *
   * @access public
   */
  static function dupeQuery($obj, $type, &$query) {
    return self::singleton()->invoke(3, $obj, $type, $query,
      self::$_nullObject, self::$_nullObject,
      'civicrm_dupeQuery'
    );
  }

  /**
   * This hook is called AFTER EACH email has been processed by the script bin/EmailProcessor.php
   *
   * @param string  $type    type of mail processed: 'activity' OR 'mailing'
   * @param array  &$params  the params that were sent to the CiviCRM API function
   * @param object  $mail    the mail object which is an ezcMail class
   * @param array  &$result  the result returned by the api call
   * @param string  $action  (optional ) the requested action to be performed if the types was 'mailing'
   *
   * @access public
   */
  static function emailProcessor($type, &$params, $mail, &$result, $action = NULL) {
    return self::singleton()->invoke(5, $type, $params, $mail, $result, $action, 'civicrm_emailProcessor');
  }

  /**
   * This hook is called after a row has been processed and the
   * record (and associated records imported
   *
   * @param string  $object     - object being imported (for now Contact only, later Contribution, Activity, Participant and Member)
   * @param string  $usage      - hook usage/location (for now process only, later mapping and others)
   * @param string  $objectRef  - import record object
   * @param array   $params     - array with various key values: currently
   *                  contactID       - contact id
   *                  importID        - row id in temp table
   *                  importTempTable - name of tempTable
   *                  fieldHeaders    - field headers
   *                  fields          - import fields
   *
   * @return void
   * @access public
   */
  static function import($object, $usage, &$objectRef, &$params) {
    return self::singleton()->invoke(4, $object, $usage, $objectRef, $params,
      self::$_nullObject,
      'civicrm_import'
    );
  }

  /**
   * This hook is called when API permissions are checked (cf. civicrm_api3_api_check_permission()
   * in api/v3/utils.php and _civicrm_api3_permissions() in CRM/Core/DAO/permissions.php).
   *
   * @param string $entity       the API entity (like contact)
   * @param string $action       the API action (like get)
   * @param array &$params       the API parameters
   * @param array &$permisisons  the associative permissions array (probably to be altered by this hook)
   */
  static function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    return self::singleton()->invoke(4, $entity, $action, $params, $permissions,
      self::$_nullObject,
      'civicrm_alterAPIPermissions'
    );
  }

  static function postSave(&$dao) {
    $hookName = 'civicrm_postSave_' . $dao->getTableName();
    return self::singleton()->invoke(1, $dao,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      $hookName
    );
  }

  /**
   * This hook allows user to customize context menu Actions on contact summary page.
   *
   * @param array $actions       Array of all Actions in contextmenu.
   * @param int   $contactID     ContactID for the summary page
   */
  static function summaryActions(&$actions, $contactID = NULL) {
    return self::singleton()->invoke(2, $actions, $contactID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_summaryActions'
    );
  }

  /**
   * This hook is called from CRM_Core_Selector_Controller through which all searches in civicrm go.
   * This enables us hook implementors to modify both the headers and the rows
   *
   * The BIGGEST drawback with this hook is that you may need to modify the result template to include your
   * fields. The result files are CRM/{Contact,Contribute,Member,Event...}/Form/Selector.tpl
   *
   * However, if you use the same number of columns, you can overwrite the existing columns with the values that
   * you want displayed. This is a hackish, but avoids template modification.
   *
   * @param string $objectName the component name that we are doing the search
   *                           activity, campaign, case, contact, contribution, event, grant, membership, and pledge
   * @param array  &$headers   the list of column headers, an associative array with keys: ( name, sort, order )
   * @param array  &$rows      the list of values, an associate array with fields that are displayed for that component
   * @param array  &$seletor   the selector object. Allows you access to the context of the search
   *
   * @return void  modify the header and values object to pass the data u need
   */
  static function searchColumns($objectName, &$headers, &$rows, &$selector) {
    return self::singleton()->invoke(4, $objectName, $headers, $rows, $selector,
      self::$_nullObject,
      'civicrm_searchColumns'
    );
  }

  /**
   * This hook is called when uf groups are being built for a module.
   *
   * @param string $moduleName module name.
   * @param array $ufGroups array of ufgroups for a module.
   *
   * @return null
   * @access public
   */
  static function buildUFGroupsForModule($moduleName, &$ufGroups) {
    return self::singleton()->invoke(2, $moduleName, $ufGroups,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_buildUFGroupsForModule'
    );
  }

  /**
   * This hook is called when we are determining the contactID for a specific
   * email address
   *
   * @param string $email     the email address
   * @param int    $contactID the contactID that matches this email address, IF it exists
   * @param array  $result (reference) has two fields
   *                          contactID - the new (or same) contactID
   *                          action - 3 possible values:
   *                                   CRM_Utils_Mail_Incoming::EMAILPROCESSOR_CREATE_INDIVIDUAL - create a new contact record
   *                                   CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE - use the new contactID
   *                                   CRM_Utils_Mail_Incoming::EMAILPROCESSOR_IGNORE   - skip this email address
   *
   * @return null
   * @access public
   */
  static function emailProcessorContact($email, $contactID, &$result) {
    return self::singleton()->invoke(3, $email, $contactID, $result,
      self::$_nullObject, self::$_nullObject,
      'civicrm_emailProcessorContact'
    );
  }

  /**
   * Hook definition for altering the generation of Mailing Labels
   *
   * @param array $args an array of the args in the order defined for the tcpdf multiCell api call
   *                    with the variable names below converted into string keys (ie $w become 'w'
   *                    as the first key for $args)
   *   float $w Width of cells. If 0, they extend up to the right margin of the page.
   *   float $h Cell minimum height. The cell extends automatically if needed.
   *   string $txt String to print
   *   mixed $border Indicates if borders must be drawn around the cell block. The value can
   *                 be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or
   *                 a string containing some or all of the following characters (in any order):
   *                 <ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
   *   string $align Allows to center or align the text. Possible values are:<ul><li>L or empty string:
   *                 left align</li><li>C: center</li><li>R: right align</li><li>J: justification
   *                 (default value when $ishtml=false)</li></ul>
   *   int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
   *   int $ln Indicates where the current position should go after the call. Possible values are:<ul><li>0:
   *           to the right</li><li>1: to the beginning of the next line [DEFAULT]</li><li>2: below</li></ul>
   *   float $x x position in user units
   *   float $y y position in user units
   *   boolean $reseth if true reset the last cell height (default true).
   *   int $stretch stretch carachter mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if
   *                necessary</li><li>2 = forced horizontal scaling</li><li>3 = character spacing only if
   *                necessary</li><li>4 = forced character spacing</li></ul>
   *   boolean $ishtml set to true if $txt is HTML content (default = false).
   *   boolean $autopadding if true, uses internal padding and automatically adjust it to account for line width.
   *   float $maxh maximum height. It should be >= $h and less then remaining space to the bottom of the page,
   *               or 0 for disable this feature. This feature works only when $ishtml=false.
   *
   */
  static function alterMailingLabelParams(&$args) {
    return self::singleton()->invoke(1, $args,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailingLabelParams'
    );
  }

  /**
   * This hooks allows alteration of generated page content
   *
   * @param $content  previously generated content
   * @param $context  context of content - page or form
   * @param $tplName  the file name of the tpl
   * @param $object   a reference to the page or form object
   *
   * @access public
   */
  static function alterContent(&$content, $context, $tplName, &$object) {
    return self::singleton()->invoke(4, $content, $context, $tplName, $object,
      self::$_nullObject,
      'civicrm_alterContent'
    );
  }

  /**
   * This hook collects the trigger definition from all components
   *
   * @param $triggerInfo reference to an array of trigger information
   *   each element has 4 fields:
   *     table - array of tableName
   *     when  - BEFORE or AFTER
   *     event - array of eventName - INSERT OR UPDATE OR DELETE
   *     sql   - array of statements optionally terminated with a ;
   *             a statement can use the tokes {tableName} and {eventName}
   *             to do token replacement with the table / event. This allows
   *             templatizing logging and other hooks
   * @param string $tableName (optional) the name of the table that we are interested in only
   */
  static function triggerInfo(&$info, $tableName = NULL) {
    return self::singleton()->invoke(2, $info, $tableName,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject,
      'civicrm_triggerInfo'
    );
  }

  /**
   * This hook is called when a module-extension is installed.
   * Each module will receive hook_civicrm_install during its own installation (but not during the installation of unrelated modules).
   */
  static function install() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_install'
    );
  }

  /**
   * This hook is called when a module-extension is uninstalled.
   * Each module will receive hook_civicrm_uninstall during its own uninstallation (but not during the uninstallation of unrelated modules).
   */
  static function uninstall() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_uninstall'
    );
  }

  /**
   * This hook is called when a module-extension is re-enabled.
   * Each module will receive hook_civicrm_enable during its own re-enablement (but not during the re-enablement of unrelated modules).
   */
  static function enable() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_enable'
    );
  }

  /**
   * This hook is called when a module-extension is disabled.
   * Each module will receive hook_civicrm_disable during its own disablement (but not during the disablement of unrelated modules).
   */
  static function disable() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_disable'
    );
  }

  static function alterReportVar($varType, &$var, &$object) {
    return self::singleton()->invoke(3, $varType, $var, $object,
      self::$_nullObject,
      self::$_nullObject,
      'civicrm_alterReportVar'
    );
  }

  /**
   * This hook is called to drive database upgrades for extension-modules.
   *
   * @param string $op         the type of operation being performed; 'check' or 'enqueue'
   * @param string $queue      (for 'enqueue') the modifiable list of pending up upgrade tasks
   *
   * @return mixed             based on op. 'check' returns a array(boolean) (TRUE if upgrades are pending)
   *                           'enqueue' returns void
   * @access public
   */
  static function upgrade($op, CRM_Queue_Queue $queue = NULL) {
    return self::singleton()->invoke(2, $op, $queue,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject,
      'civicrm_upgrade'
    );
  }

  /**
   * This hook is called when an email has been successfully sent by CiviCRM, but not on an error.
   *
   * @param array $params - the mailing parameters array fields include: groupName, from, toName, toEmail, subject, cc, bcc, text, html, returnPath, replyTo, headers, attachments (array)
   */
  static function postEmailSend(&$params) {
    return self::singleton()->invoke(1, $params,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_postEmailSend'
    );
  }

  /**
   * This hook is called when Settings specifications are loaded
   *
   * @param array $settingsFolders - list of paths from which to derive metadata
   */
  static function alterSettingsFolders(&$settingsFolders) {
    return self::singleton()->invoke(1, $settingsFolders,
        self::$_nullObject, self::$_nullObject,
        self::$_nullObject, self::$_nullObject,
        'civicrm_alterSettingsFolders'
    );
  }

  /**
   * This hook is called when Settings have been loaded from the xml
   * It is an opportunity for hooks to alter the data
   *
   * @param array $settingsMetaData - Settings Metadata
   * @domainID integer $domainID
   */
  static function alterSettingsMetaData(&$settingsMetaData, $domainID, $profile) {
    return self::singleton()->invoke(3, $settingsMetaData,
        $domainID, $profile,
        self::$_nullObject, self::$_nullObject,
        'civicrm_alterSettingsMetaData'
    );
  }

  /**
   * This hook is called before running pending cron jobs.
   *
   * @param CRM_Core_JobManager $jobManager
   *
   * @return null the return value is ignored
   * @access public
   */
  static function cron($jobManager) {
    return self::singleton()->invoke(1,
      $jobManager, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_cron'
    );
  }

  /**
   * This hook is called when loading CMS permissions; use this hook to modify
   * the array of system permissions for CiviCRM.
   *
   * @param Array $permissions Array of permissions. See CRM_Core_Permission::getCorePermissions()
   *   for the format of this array.
   *
   * @return null the return value is ignored
   * @access public
   */
  static function permission(&$permissions) {
    return self::singleton()->invoke(1, $permissions,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_permission'
    );
  }
}
