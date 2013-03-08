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


require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

/**
 *  Include configuration
 */
define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');

if (file_exists(CIVICRM_SETTINGS_LOCAL_PATH)) {
  require_once CIVICRM_SETTINGS_LOCAL_PATH;
}
require_once CIVICRM_SETTINGS_PATH;

/**
 *  Base class for CiviCRM Selenium tests
 *
 *  Common functions for unit tests
 * @package CiviCRM
 */
class CiviSeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase {

  //    protected $coverageScriptUrl = 'http://tests.dev.civicrm.org/drupal/phpunit_coverage.php';

  /**
   *  Constructor
   *
   *  Because we are overriding the parent class constructor, we
   *  need to show the same arguments as exist in the constructor of
   *  PHPUnit_Framework_TestCase, since
   *  PHPUnit_Framework_TestSuite::createTest() creates a
   *  ReflectionClass of the Test class and checks the constructor
   *  of that class to decide how to set up the test.
   *
   * @param  string $name
   * @param  array  $data
   * @param  string $dataName
   */
  function __construct($name = NULL, array$data = array(), $dataName = '', array$browser = array()) {
    parent::__construct($name, $data, $dataName, $browser);

    require_once 'CiviSeleniumSettings.php';
    $this->settings = new CiviSeleniumSettings();

    // autoload
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    // also initialize a connection to the db
    $config = CRM_Core_Config::singleton();
  }

  protected function setUp() {
    $this->setBrowser($this->settings->browser);
    // Make sure that below strings have path separator at the end
    $this->setBrowserUrl($this->settings->sandboxURL);
    $this->sboxPath = $this->settings->sandboxPATH;
  }

  protected function tearDown() {
    //        $this->open( $this->settings->sandboxPATH . "logout?reset=1");
  }

  /**
   * Authenticate as drupal user
   * @param $admin: (bool) use admin user/pass instead of normal user
   */
  function webtestLogin($admin = FALSE) {
    $this->open("{$this->sboxPath}user");
    $password = $admin ? $this->settings->adminPassword : $this->settings->password;
    $username = $admin ? $this->settings->adminUsername : $this->settings->username;
    // Make sure login form is available
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $username);
    $this->type('edit-pass', $password);
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * Open an internal path beginning with 'civicrm/'
   *
   * @param $url (str) omit the 'civicrm/' it will be added for you
   * @param $args (str|array) optional url arguments
   * @param $waitFor - page element to wait for - using this is recommended to ensure the document is fully loaded
   *
   * Although it doesn't seem to do much now, using this function is recommended for
   * opening all civi pages, and using the $args param is also strongly encouraged
   * This will make it much easier to run webtests in other CMSs in the future
   */
  function openCiviPage($url, $args = NULL, $waitFor = 'civicrm-footer') {
    // Construct full url with args
    // This could be extended in future to work with other CMS style urls
    if ($args) {
      if (is_array($args)) {
        $sep = '?';
        foreach ($args as $key => $val) {
          $url .= $sep . $key . '=' . $val;
          $sep = '&';
        }
      }
      else {
        $url .= "?$args";
      }
    }
    $this->open("{$this->sboxPath}civicrm/$url");
    $this->waitForPageToLoad();
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
  }

  /**
   * Call the API on the local server
   * (kind of defeats the point of a webtest - see CRM-11889)
   */
  function webtest_civicrm_api($entity, $action, $params) {
    if (!isset($params['version'])) {
      $params['version'] = 3;
    }

    $result = civicrm_api($entity, $action, $params);
    $this->assertTrue(!civicrm_error($result), 'Civicrm api error.');
    return $result;
  }

  /**
   * Call the API on the remote server
   * Experimental - currently only works if permissions on remote site allow anon user to access ajax api
   * @see CRM-11889
   */
  function rest_civicrm_api($entity, $action, $params = array()) {
    $params += array(
      'version' => 3,
    );
    $url = "{$this->settings->sandboxURL}/{$this->sboxPath}civicrm/ajax/rest?entity=$entity&action=$action&json=" . json_encode($params);
    $request = array(
      'http' => array(
        'method' => 'POST',
        // Naughty sidestep of civi's security checks
        'header' => "X-Requested-With: XMLHttpRequest",
      ),
    );
    $ctx = stream_context_create($request);
    $result = file_get_contents($url, FALSE, $ctx);
    return json_decode($result, TRUE);
  }

  function webtestGetFirstValueForOptionGroup($option_group_name) {
    $result = $this->webtest_civicrm_api("OptionValue", "getvalue", array(
      'option_group_name' => $option_group_name,
      'option.limit' => 1,
      'return' => 'value'
    ));
    return $result;
  }

  function webtestGetValidCountryID() {
    static $_country_id;
    if (is_null($_country_id)) {
      $config_backend = $this->webtestGetConfig('countryLimit');
      $_country_id = current($config_backend);
    }
    return $_country_id;
  }

  function webtestGetValidEntityID($entity) {
    // michaelmcandrew: would like to use getvalue but there is a bug
    // for e.g. group where option.limit not working at the moment CRM-9110
    $result = $this->webtest_civicrm_api($entity, "get", array('option.limit' => 1, 'return' => 'id'));
    if (!empty($result['values'])) {
      return current(array_keys($result['values']));
    }
    return NULL;
  }

  function webtestGetConfig($field) {
    static $_config_backend;
    if (is_null($_config_backend)) {
      $result = $this->webtest_civicrm_api("Domain", "getvalue", array(
        'current_domain' => 1,
        'option.limit' => 1,
        'return' => 'config_backend'
      ));
      $_config_backend = unserialize($result);
    }
    return $_config_backend[$field];
  }

  /**
   * Ensures the required CiviCRM components are enabled
   */
  function enableComponents($components) {
    $this->openCiviPage("admin/setting/component", "reset=1", "_qf_Component_next-bottom");
    $enabledComponents = $this->getSelectOptions("enableComponents-t");
    $added = FALSE;
    foreach ((array) $components as $comp) {
      if (!in_array($comp, $enabledComponents)) {
        $this->addSelection("enableComponents-f", "label=$comp");
        $this->click("//option[@value='$comp']");
        $this->click("add");
        $added = TRUE;
      }
    }
    if ($added) {
      $this->click("_qf_Component_next-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->assertElementContainsText("crm-notification-container", "Saved");
    }
  }

  /**
   * Add a contact with the given first and last names and either a given email
   * (when specified), a random email (when true) or no email (when unspecified or null).
   *
   * @param string $fname contact’s first name
   * @param string $lname contact’s last name
   * @param mixed  $email contact’s email (when string) or random email (when true) or no email (when null)
   *
   * @return mixed either a string with the (either generated or provided) email or null (if no email)
   */
  function webtestAddContact($fname = 'Anthony', $lname = 'Anderson', $email = NULL, $contactSubtype = NULL) {
    $url = $this->sboxPath . 'civicrm/contact/add?reset=1&ct=Individual';
    if ($contactSubtype) {
      $url = $url . "&cst={$contactSubtype}";
    }
    $this->open($url);
    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');
    $this->type('first_name', $fname);
    $this->type('last_name', $lname);
    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }
    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');
    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $email;
  }

  function webtestAddHousehold($householdName = "Smith's Home", $email = NULL) {

    $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Household');
    $this->click('household_name');
    $this->type('household_name', $householdName);

    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }

    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $email;
  }

  function webtestAddOrganization($organizationName = "Organization XYZ", $email = NULL) {

    $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
    $this->click('organization_name');
    $this->type('organization_name', $organizationName);

    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $email;
  }

  /**
   */
  function webtestFillAutocomplete($sortName) {
    $this->click('contact_1');
    $this->type('contact_1', $sortName);
    $this->typeKeys('contact_1', $sortName);
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($sortName, $this->getValue('contact_1'), "autocomplete expected $sortName but didn’t find it in " . $this->getValue('contact_1'));
  }

  /**
   */
  function webtestOrganisationAutocomplete($sortName) {
    $this->type('contact_name', $sortName);
    $this->click('contact_name');
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    //$this->assertContains($sortName, $this->getValue('contact_1'), "autocomplete expected $sortName but didn’t find it in " . $this->getValue('contact_1'));
  }


  /*
     * 1. By default, when no strtotime arg is specified, sets date to "now + 1 month"
     * 2. Does not set time. For setting both date and time use webtestFillDateTime() method.
     * 3. Examples of $strToTime arguments -
     *        webtestFillDate('start_date',"now")
     *        webtestFillDate('start_date',"10 September 2000")
     *        webtestFillDate('start_date',"+1 day")
     *        webtestFillDate('start_date',"+1 week")
     *        webtestFillDate('start_date',"+1 week 2 days 4 hours 2 seconds")
     *        webtestFillDate('start_date',"next Thursday")
     *        webtestFillDate('start_date',"last Monday")
     */
  function webtestFillDate($dateElement, $strToTimeArgs = NULL) {
    $timeStamp = strtotime($strToTimeArgs ? $strToTimeArgs : '+1 month');

    $year = date('Y', $timeStamp);
    // -1 ensures month number is inline with calender widget's month
    $mon = date('n', $timeStamp) - 1;
    $day = date('j', $timeStamp);

    $this->click("{$dateElement}_display");
    $this->waitForElementPresent("css=div#ui-datepicker-div.ui-datepicker div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-month");
    $this->select("css=div#ui-datepicker-div.ui-datepicker div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-month", "value=$mon");
    $this->select("css=div#ui-datepicker-div div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-year", "value=$year");
    $this->click("link=$day");
  }

  // 1. set both date and time.
  function webtestFillDateTime($dateElement, $strToTimeArgs = NULL) {
    $this->webtestFillDate($dateElement, $strToTimeArgs);

    $timeStamp = strtotime($strToTimeArgs ? $strToTimeArgs : '+1 month');
    $hour = date('h', $timeStamp);
    $min = date('i', $timeStamp);
    $meri = date('A', $timeStamp);

    $this->type("{$dateElement}_time", "{$hour}:{$min}{$meri}");
  }

  /**
   * Verify that given label/value pairs are in *sibling* td cells somewhere on the page.
   *
   * @param array $expected       Array of key/value pairs (like Status/Registered) to be checked
   * @param string $xpathPrefix   Pass in an xpath locator to "get to" the desired table or tables. Will be prefixed to xpath
   *                              table path. Include leading forward slashes (e.g. "//div[@id='activity-content']").
   * @param string $tableId       Pass in the id attribute of a table to be verified if you want to only check a specific table
   *                              on the web page.
   */
  function webtestVerifyTabularData($expected, $xpathPrefix = NULL, $tableId = NULL) {
    $tableLocator = "";
    if ($tableId) {
      $tableLocator = "[@id='$tableId']";
    }
    foreach ($expected as $label => $value) {
      if ($xpathPrefix) {
        $this->verifyText("xpath=//table{$tableLocator}/tbody/tr/td{$xpathPrefix}[text()='{$label}']/../following-sibling::td", preg_quote($value), 'In line ' . __LINE__);
      }
      else {
        $this->verifyText("xpath=//table{$tableLocator}/tbody/tr/td[text()='{$label}']/following-sibling::td", preg_quote($value), 'In line ' . __LINE__);
      }
    }
  }

  /**
   * Types text into a ckEditor rich text field in a form
   *
   * @param string $fieldName form field name (as assigned by PHP buildForm class)
   * @param string $text      text to type into the field
   * @param string $editor    which text editor (valid values are 'CKEditor', 'TinyMCE')
   *
   * @return void
   */
  function fillRichTextField($fieldName, $text = 'Typing this text into editor.', $editor = 'CKEditor') {
    // make sure cursor focuses on the field
    $this->fireEvent($fieldName, 'focus');
    if ($editor == 'CKEditor') {
      $this->waitForElementPresent("xpath=//div[@id='cke_{$fieldName}']//iframe");
      $this->runScript("CKEDITOR.instances['{$fieldName}'].setData('<p>{$text}</p>');");
    }
    elseif ($editor == 'TinyMCE') {
      $this->selectFrame("xpath=//iframe[@id='{$fieldName}_ifr']");
      $this->type("//html/body[@id='tinymce']", $text);
    }
    else {
      $this->fail("Unknown editor value: $editor, failing (in CiviSeleniumTestCase::fillRichTextField ...");
    }
    $this->selectFrame('relative=top');
  }

  /**
   * Types option label and name into a table of multiple choice options
   * (for price set fields of type select, radio, or checkbox)
   * TODO: extend for custom field multiple choice table input
   *
   * @param array  $options           form field name (as assigned by PHP buildForm class)
   * @param array  $validateStrings   appends label and name strings to this array so they can be validated later
   *
   * @return void
   */
  function addMultipleChoiceOptions($options, &$validateStrings) {
    foreach ($options as $oIndex => $oValue) {
      $validateStrings[] = $oValue['label'];
      $validateStrings[] = $oValue['amount'];
      if (CRM_Utils_Array::value('membership_type_id', $oValue)) {
        $this->select("membership_type_id_{$oIndex}", "value={$oValue['membership_type_id']}");
      }
      if (CRM_Utils_Array::value('financial_type_id', $oValue)) {
        $this->select("option_financial_type_id_{$oIndex}", "label={$oValue['financial_type_id']}");
      }
      $this->type("option_label_{$oIndex}", $oValue['label']);
      $this->type("option_amount_{$oIndex}", $oValue['amount']);
      $this->click('link=another choice');
    }
  }

  /**
   */
  function webtestNewDialogContact($fname = 'Anthony', $lname = 'Anderson', $email = 'anthony@anderson.biz', $type = 4, $selectId = 'profiles_1', $row = 1) {
    // 4 - Individual profile
    // 5 - Organization profile
    // 6 - Household profile
    $this->select($selectId, "value={$type}");

    // create new contact using dialog
    $this->waitForElementPresent("css=div#contact-dialog-{$row}");
    $this->waitForElementPresent('_qf_Edit_next');

    switch ($type) {
      case 4:
        $this->type('first_name', $fname);
        $this->type('last_name', $lname);
        break;

      case 5:
        $this->type('organization_name', $fname);
        break;

      case 6:
        $this->type('household_name', $fname);
        break;
    }

    $this->type('email-Primary', $email);
    $this->click('_qf_Edit_next');

    // Is new contact created?
    if ($lname) {
      $this->assertTrue($this->isTextPresent("$lname, $fname has been created."), "Status message didn't show up after saving!");
    }
    else {
      $this->assertTrue($this->isTextPresent("$fname has been created."), "Status message didn't show up after saving!");
    }
  }

  /**
   * Generic function to check that strings are present in the page
   *
   * @strings  array    array of strings or a single string
   *
   * @return   void
   */
  function assertStringsPresent($strings) {
    foreach ((array) $strings as $string) {
      $this->assertTrue($this->isTextPresent($string), "Could not find $string on page");
    }
  }

  /**
   * Generic function to parse a URL string into it's elements.extract a variable value from a string (url)
   *
   * @url      string url to parse or retrieve current url if null
   *
   * @return   array  returns an associative array containing any of the various components
   *                  of the URL that are present. Querystring elements are returned in sub-array (elements.queryString)
   *                  http://php.net/manual/en/function.parse-url.php
   *
   */
  function parseURL($url = NULL) {
    if (!$url) {
      $url = $this->getLocation();
    }

    $elements = parse_url($url);
    if (!empty($elements['query'])) {
      $elements['queryString'] = array();
      parse_str($elements['query'], $elements['queryString']);
    }
    return $elements;
  }

  /**
   * Define a payment processor for use by a webtest. Default is to create Dummy processor
   * which is useful for testing online public forms (online contribution pages and event registration)
   *
   * @param string $processorName Name assigned to new processor
   * @param string $processorType Name for processor type (e.g. PayPal, Dummy, etc.)
   * @param array  $processorSettings Array of fieldname => value for required settings for the processor
   *
   * @return void
   */

  function webtestAddPaymentProcessor($processorName, $processorType = 'Dummy', $processorSettings = NULL, $financialAccount = 'Deposit Bank Account') {
    if (!$processorName) {
      $this->fail("webTestAddPaymentProcessor requires $processorName.");
    }
    if ($processorType == 'Dummy') {
      $processorSettings = array(
        'user_name' => 'dummy',
        'url_site' => 'http://dummy.com',
        'test_user_name' => 'dummytest',
        'test_url_site' => 'http://dummytest.com',
      );
    }
    elseif ($processorType == 'AuthNet') {
      // FIXME: we 'll need to make a new separate account for testing
      $processorSettings = array(
        'test_user_name' => '5ULu56ex',
        'test_password' => '7ARxW575w736eF5p',
      );
    }
    elseif ($processorType == 'Google_Checkout') {
      // FIXME: we 'll need to make a new separate account for testing
      $processorSettings = array(
        'test_user_name' => '559999327053114',
        'test_password' => 'R2zv2g60-A7GXKJYl0nR0g',
      );
    }
    elseif ($processorType == 'PayPal') {
      $processorSettings = array(
        'test_user_name' => '559999327053114',
        'test_password' => 'R2zv2g60-A7GXKJYl0nR0g',
        'test_signature' => 'R2zv2g60-A7GXKJYl0nR0g',
      );
    }
    elseif ($processorType == 'PayPal_Standard') {
      $processorSettings = array(
        'test_user_name' => 'V18ki@9r5Bf.org',
      );
    }
    elseif (empty($processorSettings)) {
      $this->fail("webTestAddPaymentProcessor requires $processorSettings array if processorType is not Dummy.");
    }
    $pid = CRM_Core_DAO::getFieldValue("CRM_Financial_DAO_PaymentProcessorType", $processorType, "id", "name");
    if (empty($pid)) {
      $this->fail("$processorType processortype not found.");
    }
    $this->open($this->sboxPath . 'civicrm/admin/paymentProcessor?action=add&reset=1&pp=' . $pid);
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type('name', $processorName);
    $this->select('financial_account_id', "label={$financialAccount}");

    foreach ($processorSettings AS $f => $v) {
      $this->type($f, $v);
    }
    $this->click('_qf_PaymentProcessor_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Is new processor created?
    $this->assertTrue($this->isTextPresent($processorName), 'Processor name not found in selector after adding payment processor (webTestAddPaymentProcessor).');

    $paymentProcessorId = explode('&id=', $this->getAttribute("xpath=//table[@class='selector']//tbody//tr/td[text()='{$processorName}']/../td[7]/span/a[1]@href"));
    $paymentProcessorId = explode('&', $paymentProcessorId[1]);
    return $paymentProcessorId[0];
  }

  function webtestAddCreditCardDetails() {
    $this->waitForElementPresent('credit_card_type');
    $this->select('credit_card_type', 'label=Visa');
    $this->type('credit_card_number', '4807731747657838');
    $this->type('cvv2', '123');
    $this->select('credit_card_exp_date[M]', 'label=Feb');
    $this->select('credit_card_exp_date[Y]', 'label=2019');
  }

  function webtestAddBillingDetails($firstName = NULL, $middleName = NULL, $lastName = NULL) {
    if (!$firstName) {
      $firstName = 'John';
    }

    if (!$middleName) {
      $middleName = 'Apple';
    }

    if (!$lastName) {
      $lastName = 'Smith_' . substr(sha1(rand()), 0, 7);
    }

    $this->type('billing_first_name', $firstName);
    $this->type('billing_middle_name', $middleName);
    $this->type('billing_last_name', $lastName);

    $this->type('billing_street_address-5', '234 Lincoln Ave');
    $this->type('billing_city-5', 'San Bernadino');
    $this->click('billing_state_province_id-5');
    $this->select('billing_state_province_id-5', 'label=California');
    $this->type('billing_postal_code-5', '93245');

    return array($firstName, $middleName, $lastName);
  }

  function webtestAttachFile($fieldLocator, $filePath = NULL) {
    if (!$filePath) {
      $filePath = '/tmp/testfile_' . substr(sha1(rand()), 0, 7) . '.txt';
      $fp = @fopen($filePath, 'w');
      fputs($fp, 'Test file created by selenium test.');
      @fclose($fp);
    }

    $this->assertTrue(file_exists($filePath), 'Not able to locate file: ' . $filePath);

    $this->attachFile($fieldLocator, "file://{$filePath}");

    return $filePath;
  }

  function webtestCreateCSV($headers, $rows, $filePath = NULL) {
    if (!$filePath) {
      $filePath = '/tmp/testcsv_' . substr(sha1(rand()), 0, 7) . '.csv';
    }

    $data = '"' . implode('", "', $headers) . '"' . "\r\n";

    foreach ($rows as $row) {
      $temp = array();
      foreach ($headers as $field => $header) {
        $temp[$field] = isset($row[$field]) ? '"' . $row[$field] . '"' : '""';
      }
      $data .= implode(', ', $temp) . "\r\n";
    }

    $fp = @fopen($filePath, 'w');
    @fwrite($fp, $data);
    @fclose($fp);

    $this->assertTrue(file_exists($filePath), 'Not able to locate file: ' . $filePath);

    return $filePath;
  }

  /**
   * Create new relationship type w/ user specified params or default.
   *
   * @param $params array of required params.
   *
   * @return an array of saved params values.
   */
  function webtestAddRelationshipType($params = array()) {
    $this->open($this->sboxPath . 'civicrm/admin/reltype?reset=1&action=add');

    //build the params if not passed.
    if (!is_array($params) || empty($params)) {
      $params = array(
        'label_a_b' => 'Test Relationship Type A - B -' . rand(),
        'label_b_a' => 'Test Relationship Type B - A -' . rand(),
        'contact_types_a' => 'Individual',
        'contact_types_b' => 'Individual',
        'description' => 'Test Relationship Type Description',
      );
    }
    //make sure we have minimum required params.
    if (!isset($params['label_a_b']) || empty($params['label_a_b'])) {
      $params['label_a_b'] = 'Test Relationship Type A - B -' . rand();
    }

    //start the form fill.
    $this->type('label_a_b', $params['label_a_b']);
    $this->type('label_b_a', $params['label_b_a']);
    $this->select('contact_types_a', "value={$params['contact_type_a']}");
    $this->select('contact_types_b', "value={$params['contact_type_b']}");
    $this->type('description', $params['description']);

    //save the data.
    $this->click('_qf_RelationshipType_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //does data saved.
    $this->assertTrue($this->isTextPresent('The Relationship Type has been saved.'),
      "Status message didn't show up after saving!"
    );

    $this->open($this->sboxPath . 'civicrm/admin/reltype?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //validate data on selector.
    $data = $params;
    if (isset($data['description'])) {
      unset($data['description']);
    }
    $this->assertStringsPresent($data);

    return $params;
  }

  /**
   * Create new online contribution page w/ user specified params or defaults.
   *
   * @param User can define pageTitle, hash and rand values for later data verification
   *
   * @return $pageId of newly created online contribution page.
   */
  function webtestAddContributionPage($hash = NULL,
                                      $rand = NULL,
                                      $pageTitle = NULL,
                                      $processor = array('Dummy Processor' => 'Dummy'),
                                      $amountSection = TRUE,
                                      $payLater = TRUE,
                                      $onBehalf = TRUE,
                                      $pledges = TRUE,
                                      $recurring = FALSE,
                                      $membershipTypes = TRUE,
                                      $memPriceSetId = NULL,
                                      $friend = TRUE,
                                      $profilePreId = 1,
                                      $profilePostId = 7,
                                      $premiums = TRUE,
                                      $widget = TRUE,
                                      $pcp = TRUE,
                                      $isAddPaymentProcessor = TRUE,
                                      $isPcpApprovalNeeded = FALSE,
                                      $isSeparatePayment = FALSE,
                                      $honoreeSection = TRUE,
                                      $allowOtherAmmount = TRUE,
                                      $isConfirmEnabled = TRUE,
                                      $financialType = 'Donation',
                                      $fixedAmount = TRUE,
                                      $membershipsRequired = TRUE
  ) {
    if (!$hash) {
      $hash = substr(sha1(rand()), 0, 7);
    }
    if (!$pageTitle) {
      $pageTitle = 'Donate Online ' . $hash;
    }

    if (!$rand) {
      $rand = 2 * rand(2, 50);
    }

    // Create a new payment processor if requested
    if ($isAddPaymentProcessor) {
      while (list($processorName, $processorType) = each($processor)) {
        $this->webtestAddPaymentProcessor($processorName, $processorType);
      }
    }

    // go to the New Contribution Page page
    $this->open($this->sboxPath . 'civicrm/admin/contribute?action=add&reset=1');
    $this->waitForPageToLoad();

    // fill in step 1 (Title and Settings)
    $this->type('title', $pageTitle);

    //to select financial type
    $this->select('financial_type_id', "label={$financialType}");

    if ($onBehalf) {
      $this->click('is_organization');
      $this->select('onbehalf_profile_id', 'label=On Behalf Of Organization');
      $this->type('for_organization', "On behalf $hash");

      if ($onBehalf == 'required') {
        $this->click('CIVICRM_QFID_2_4');
      }
      elseif ($onBehalf == 'optional') {
        $this->click('CIVICRM_QFID_1_2');
      }
    }

    $this->fillRichTextField('intro_text', 'This is introductory message for ' . $pageTitle, 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is footer message for ' . $pageTitle, 'CKEditor');

    $this->type('goal_amount', 10 * $rand);

    // FIXME: handle Start/End Date/Time
    if ($honoreeSection) {
      $this->click('honor_block_is_active');
      $this->type('honor_block_title', "Honoree Section Title $hash");
      $this->type('honor_block_text', "Honoree Introductory Message $hash");
    }

    // is confirm enabled? it starts out enabled, so uncheck it if false
    if (!$isConfirmEnabled) {
      $this->click("id=is_confirm_enabled");
    }

    // go to step 2
    $this->click('_qf_Settings_next');
    $this->waitForElementPresent('_qf_Amount_next-bottom');

    // fill in step 2 (Processor, Pay Later, Amounts)
    if (!empty($processor)) {
      reset($processor);
      while (list($processorName) = each($processor)) {
        // select newly created processor
        $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
        $this->assertTrue($this->isTextPresent($processorName));
        $this->check($xpath);
      }
    }

    if ($amountSection && !$memPriceSetId) {
      if ($payLater) {
        $this->click('is_pay_later');
        $this->type('pay_later_text', "Pay later label $hash");
        $this->type('pay_later_receipt', "Pay later instructions $hash");
      }

      if ($pledges) {
        $this->click('is_pledge_active');
        $this->click('pledge_frequency_unit[week]');
        $this->click('is_pledge_interval');
        $this->type('initial_reminder_day', 3);
        $this->type('max_reminders', 2);
        $this->type('additional_reminder_day', 1);
      }
      elseif ($recurring) {
        $this->click('is_recur');
        $this->click("is_recur_interval");
        $this->click("is_recur_installments");
      }
      if ($allowOtherAmmount) {

        $this->click('is_allow_other_amount');

        // there shouldn't be minimums and maximums on test contribution forms unless you specify it
        //$this->type('min_amount', $rand / 2);
        //$this->type('max_amount', $rand * 10);
      }
      if ($fixedAmount || !$allowOtherAmmount) {
        $this->type('label_1', "Label $hash");
        $this->type('value_1', "$rand");
      }
      $this->click('CIVICRM_QFID_1_2');
    }
    else {
      $this->click('amount_block_is_active');
    }

    $this->click('_qf_Amount_next');
    $this->waitForElementPresent('_qf_Amount_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $text = "'Amount' information has been saved.";
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);

    if ($memPriceSetId || (($membershipTypes === TRUE) || (is_array($membershipTypes) && !empty($membershipTypes)))) {
      // go to step 3 (memberships)
      $this->click('link=Memberships');
      $this->waitForElementPresent('_qf_MembershipBlock_next-bottom');

      // fill in step 3 (Memberships)
      $this->click('member_is_active');
      $this->waitForElementPresent('displayFee');
      $this->type('new_title', "Title - New Membership $hash");
      $this->type('renewal_title', "Title - Renewals $hash");

      if ($memPriceSetId) {
        $this->click('member_price_set_id');
        $this->select('member_price_set_id', "value={$memPriceSetId}");
      }
      else {
        if ($membershipTypes === TRUE) {
          $membershipTypes = array(array('id' => 2));
        }

        // FIXME: handle Introductory Message - New Memberships/Renewals
        foreach ($membershipTypes as $mType) {
          $this->click("membership_type_{$mType['id']}");
          if (array_key_exists('default', $mType)) {
            // FIXME:
          }
          if (array_key_exists('auto_renew', $mType)) {
            $this->select("auto_renew_{$mType['id']}", "label=Give option");
          }
        }
        if ($membershipsRequired) {
          $this->click('is_required');
        }
        $this->waitForElementPresent('CIVICRM_QFID_2_4');
        $this->click('CIVICRM_QFID_2_4');
        if ($isSeparatePayment) {
          $this->click('is_separate_payment');
        }
      }
      $this->click('_qf_MembershipBlock_next');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForElementPresent('_qf_MembershipBlock_next-bottom');
      $text = "'MembershipBlock' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    // go to step 4 (thank-you and receipting)
    $this->click('link=Receipt');
    $this->waitForElementPresent('_qf_ThankYou_next-bottom');

    // fill in step 4
    $this->type('thankyou_title', "Thank-you Page Title $hash");
    // FIXME: handle Thank-you Message/Page Footer
    $this->type('receipt_from_name', "Receipt From Name $hash");
    $this->type('receipt_from_email', "$hash@example.org");
    $this->type('receipt_text', "Receipt Message $hash");
    $this->type('cc_receipt', "$hash@example.net");
    $this->type('bcc_receipt', "$hash@example.com");

    $this->click('_qf_ThankYou_next');
    $this->waitForElementPresent('_qf_ThankYou_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $text = "'ThankYou' information has been saved.";
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);

    if ($friend) {
      // fill in step 5 (Tell a Friend)
      $this->click('link=Tell a Friend');
      $this->waitForElementPresent('_qf_Contribute_next-bottom');
      $this->click('tf_is_active');
      $this->type('tf_title', "TaF Title $hash");
      $this->type('intro', "TaF Introduction $hash");
      $this->type('suggested_message', "TaF Suggested Message $hash");
      $this->type('general_link', "TaF Info Page Link $hash");
      $this->type('tf_thankyou_title', "TaF Thank-you Title $hash");
      $this->type('tf_thankyou_text', "TaF Thank-you Message $hash");

      //$this->click('_qf_Contribute_next');
      $this->click('_qf_Contribute_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $text = "'Friend' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    if ($profilePreId || $profilePostId) {
      // fill in step 6 (Include Profiles)
      $this->click('css=li#tab_custom a');
      $this->waitForElementPresent('_qf_Custom_next-bottom');

      if ($profilePreId) {
        $this->select('custom_pre_id', "value={$profilePreId}");
      }

      if ($profilePostId) {
        $this->select('custom_post_id', "value={$profilePostId}");
      }

      $this->click('_qf_Custom_next-bottom');
      //$this->waitForElementPresent('_qf_Custom_next-bottom');

      $this->waitForPageToLoad($this->getTimeoutMsec());
      $text = "'Custom' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    if ($premiums) {
      // fill in step 7 (Premiums)
      $this->click('link=Premiums');
      $this->waitForElementPresent('_qf_Premium_next-bottom');
      $this->click('premiums_active');
      $this->type('premiums_intro_title', "Prem Title $hash");
      $this->type('premiums_intro_text', "Prem Introductory Message $hash");
      $this->type('premiums_contact_email', "$hash@example.info");
      $this->type('premiums_contact_phone', rand(100000000, 999999999));
      $this->click('premiums_display_min_contribution');
      $this->type('premiums_nothankyou_label', 'No thank-you');
      $this->click('_qf_Premium_next');
      $this->waitForElementPresent('_qf_Premium_next-bottom');

      $this->waitForPageToLoad($this->getTimeoutMsec());
      $text = "'Premium' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }


    if ($widget) {
      // fill in step 8 (Widget Settings)
      $this->click('link=Widgets');
      $this->waitForElementPresent('_qf_Widget_next-bottom');

      $this->click('is_active');
      $this->type('url_logo', "URL to Logo Image $hash");
      $this->type('button_title', "Button Title $hash");
      // Type About text in ckEditor (fieldname, text to type, editor)
      $this->fillRichTextField('about', 'This is for ' . $pageTitle, 'CKEditor');

      $this->click('_qf_Widget_next');
      $this->waitForElementPresent('_qf_Widget_next-bottom');

      $this->waitForPageToLoad($this->getTimeoutMsec());
      $text = "'Widget' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    if ($pcp) {
      // fill in step 9 (Enable Personal Campaign Pages)
      $this->click('link=Personal Campaigns');
      $this->waitForElementPresent('_qf_Contribute_next-bottom');
      $this->click('pcp_active');
      if (!$isPcpApprovalNeeded) {
        $this->click('is_approval_needed');
      }
      $this->type('notify_email', "$hash@example.name");
      $this->select('supporter_profile_id', 'value=2');
      $this->type('tellfriend_limit', 7);
      $this->type('link_text', "'Create Personal Campaign Page' link text $hash");

      $this->click('_qf_Contribute_next-bottom');
      //$this->waitForElementPresent('_qf_PCP_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $text = "'Pcp' information has been saved.";
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    // parse URL to grab the contribution page id
    $elements = $this->parseURL();
    $pageId = $elements['queryString']['id'];

    // pass $pageId back to any other tests that call this class
    return $pageId;
  }

  /**
   * Function to update default strict rule.
   *
   * @params  string   $contactType  Contact type
   * @param   array    $fields       Fields to be set for strict rule
   * @param   Integer  $threshold    Rule's threshold value
   */
  function webtestStrictDedupeRuleDefault($contactType = 'Individual', $fields = array(), $threshold = 10) {
    // set default strict rule.
    $strictRuleId = 4;
    if ($contactType == 'Organization') {
      $strictRuleId = 5;
    }
    elseif ($contactType == 'Household') {
      $strictRuleId = 6;
    }

    // Default dedupe fields for each Contact type.
    if (empty($fields)) {
      $fields = array('civicrm_email.email' => 10);
      if ($contactType == 'Organization') {
        $fields = array(
          'civicrm_contact.organization_name' => 10,
          'civicrm_email.email' => 10,
        );
      }
      elseif ($contactType == 'Household') {
        $fields = array(
          'civicrm_contact.household_name' => 10,
          'civicrm_email.email' => 10,
        );
      }
    }

    $this->open($this->sboxPath . 'civicrm/contact/deduperules?action=update&id=' . $strictRuleId);
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_DedupeRules_next-bottom');

    $count = 0;
    foreach ($fields as $field => $weight) {
      $this->select("where_{$count}", "value={$field}");
      $this->type("length_{$count}", '');
      $this->type("weight_{$count}", $weight);
      $count++;
    }

    if ($count > 4) {
      $this->type('threshold', $threshold);
      // click save
      $this->click('_qf_DedupeRules_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      return;
    }

    for ($i = $count; $i <= 4; $i++) {
      $this->select("where_{$i}", 'label=- none -');
      $this->type("length_{$i}", '');
      $this->type("weight_{$i}", '');
    }

    $this->type('threshold', $threshold);

    // click save
    $this->click('_qf_DedupeRules_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function webtestAddMembershipType($period_type = 'rolling', $duration_interval = 1, $duration_unit = 'year', $auto_renew = 'no') {
    $membershipTitle = substr(sha1(rand()), 0, 7);
    $membershipOrg = $membershipTitle . ' memorg';
    $this->webtestAddOrganization($membershipOrg, TRUE);

    $title = 'Membership Type ' . substr(sha1(rand()), 0, 7);
    $memTypeParams = array(
      'membership_type' => $title,
      'member_of_contact' => $membershipOrg,
      'financial_type' => 2,
      'period_type' => $period_type,
    );

    $this->open($this->sboxPath . 'civicrm/admin/member/membershipType/add?action=add&reset=1');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', $memTypeParams['membership_type']);

    // if auto_renew optional or required - a valid payment processor must be created first (e.g Auth.net)
    // select the radio first since the element id changes after membership org search results are loaded
    switch ($auto_renew) {
      case 'optional':
        $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");
        break;

      case 'required':
        $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Auto-renew required')]");
        break;

      default:
        //check if for the element presence (the Auto renew options can be absent when proper payment processor not configured)
        if ($this->isElementPresent("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'No auto-renew option')]")) {
          $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'No auto-renew option')]");
        }
        break;
    }

    $this->type('member_of_contact', $membershipTitle);
    $this->click('member_of_contact');
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', "value={$memTypeParams['financial_type']}");

    $this->type('duration_interval', $duration_interval);
    $this->select('duration_unit', "label={$duration_unit}");

    $this->select('period_type', "label={$period_type}");

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->assertTrue($this->isTextPresent("The membership type '$title' has been saved."));

    return $memTypeParams;
  }

  function WebtestAddGroup($groupName = NULL, $parentGroupName = NULL) {
    $this->openCiviPage('group/add', 'reset=1', '_qf_Edit_upload-bottom');

    // fill group name
    if (!$groupName) {
      $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    }
    $this->type('title', $groupName);

    // fill description
    $this->type('description', 'Adding new group.');

    // check Access Control
    $this->click('group_type[1]');

    // check Mailing List
    $this->click('group_type[2]');

    // select Visibility as Public Pages
    $this->select('visibility', 'value=Public Pages');

    // select parent group
    if ($parentGroupName) {
      $this->select('parents', "*$parentGroupName");
    }

    // Clicking save.
    $this->click('_qf_Edit_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "$groupName");
    return $groupName;
  }

  function WebtestAddActivity($activityType = "Meeting") {
    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Anderson", $firstName2 . "@anderson.name");

    // Go directly to the URL of the screen that you will be testing (Activity Tab).
    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    $this->waitForElementPresent("_qf_Activity_upload-bottom");

    $this->assertTrue($this->isTextPresent("Anderson, " . $firstName2), "Contact not found in line " . __LINE__);

    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
    $this->type("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName1);
    $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-input-token-facebook");

    //.need to use mouseDown on first result (which is a li element), click does not work
    // Note that if you are using firebug this appears at the bottom of the html source, before the closing </body> tag, not where the <li> referred to above is, which is a different <li>.
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook li");
    $this->mouseDown("css=div.token-input-dropdown-facebook li");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertTrue($this->isTextPresent("Summerson, " . $firstName1), "Contact not found in line " . __LINE__);

    // Putting the contents into subject field - assigning the text to variable, it'll come in handy later
    $subject = "This is subject of test activity being added through activity tab of contact summary screen.";
    // For simple input fields we can use field id as selector
    $this->type("subject", $subject);
    $this->type("location", "Some location needs to be put in this field.");

    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');

    // Setting duration.
    $this->type("duration", "30");

    // Putting in details.
    $this->type("details", "Really brief details information.");

    // Making sure that status is set to Scheduled (using value, not label).
    $this->select("status_id", "value=1");

    // Setting priority.
    $this->select("priority_id", "value=1");

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 month 11:10PM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->click("_qf_Activity_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Activity '$subject' has been saved."), "Status message didn't show up after saving!");

    $this->waitForElementPresent("xpath=//div[@id='Activities']//table/tbody/tr[2]/td[9]/span/a[text()='View']");

    // click through to the Activity view screen
    $this->click("xpath=//div[@id='Activities']//table/tbody/tr[2]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_Activity_cancel-bottom');
    $elements = $this->parseURL();
    $activityID = $elements['queryString']['id'];
    return $activityID;
  }

  static
  function checkDoLocalDBTest() {
    if (defined('CIVICRM_WEBTEST_LOCAL_DB') &&
      CIVICRM_WEBTEST_LOCAL_DB
    ) {
      require_once 'tests/phpunit/CiviTest/CiviDBAssert.php';
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Generic function to compare expected values after an api call to retrieved
   * DB values.
   *
   * @daoName  string   DAO Name of object we're evaluating.
   * @id       int      Id of object
   * @match    array    Associative array of field name => expected value. Empty if asserting
   *                      that a DELETE occurred
   * @delete   boolean  True if we're checking that a DELETE action occurred.
   */
  function assertDBState($daoName, $id, $match, $delete = FALSE) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBState($this, $daoName, $id, $match, $delete);
  }

  // Request a record from the DB by seachColumn+searchValue. Success if a record is found.
  function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBNotNull($this, $daoName, $searchValue, $returnColumn, $searchColumn, $message);
  }

  // Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.
  function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBNull($this, $daoName, $searchValue, $returnColumn, $searchColumn, $message);
  }

  // Request a record from the DB by id. Success if row not found.
  function assertDBRowNotExist($daoName, $id, $message) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBRowNotExist($this, $daoName, $id, $message);
  }

  // Compare a single column value in a retrieved DB record to an expected value
  function assertDBCompareValue($daoName, $searchValue, $returnColumn, $searchColumn,
                                $expectedValue, $message
  ) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBCompareValue($daoName, $searchValue, $returnColumn, $searchColumn,
      $expectedValue, $message
    );
  }

  // Compare all values in a single retrieved DB record to an array of expected values
  function assertDBCompareValues($daoName, $searchParams, $expectedValues) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertDBCompareValues($this, $daoName, $searchParams, $expectedValues);
  }

  function assertAttributesEquals(&$expectedValues, &$actualValues) {
    if (!self::checkDoLocalDBTest()) {
      return;
    }

    return CiviDBAssert::assertAttributesEquals($expectedValues, $actualValues);
  }

  function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }

  function changeAdminLinks() {
    $version = 7;
    if ($version == 7) {
      $this->open("{$this->sboxPath}admin/people/permissions");
    }
    else {
      $this->open("{$this->sboxPath}admin/user/permissions");
    }
  }


  /**
   * Add new Financial Account
   */

  function _testAddFinancialAccount($financialAccountTitle,
                                    $financialAccountDescription = FALSE,
                                    $accountingCode = FALSE,
                                    $firstName = FALSE,
                                    $financialAccountType = FALSE,
                                    $taxDeductible = FALSE,
                                    $isActive = FALSE,
                                    $isTax = FALSE,
                                    $taxRate = FALSE,
                                    $isDefault = FALSE
  ) {

    // Go directly to the URL
    $this->open($this->sboxPath . "civicrm/admin/financial/financialAccount?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Financial Account");
    $this->waitForElementPresent('_qf_FinancialAccount_cancel-botttom');

    // Financial Account Name
    $this->type('name', $financialAccountTitle);

    // Financial Description
    if ($financialAccountDescription) {
      $this->type('description', $financialAccountDescription);
    }

    //Accounting Code
    if ($accountingCode) {
      $this->type('accounting_code', $accountingCode);
    }

    // Autofill Organization
    if ($firstName) {
      $this->webtestOrganisationAutocomplete($firstName);
    }

    // Financial Account Type
    if ($financialAccountType) {
      $this->select('financial_account_type_id', "label={$financialAccountType}");
    }

    // Is Tax Deductible
    if ($taxDeductible) {
      $this->check('is_deductible');
    }
    else {
      $this->uncheck('is_deductible');
    }
    // Is Active
    if (!$isActive) {
      $this->check('is_active');
    }
    else {
      $this->uncheck('is_active');
    }
    // Is Tax
    if ($isTax) {
      $this->check('is_tax');
    }
    else {
      $this->uncheck('is_tax');
    }
    // Tax Rate
    if ($taxRate) {
      $this->type('tax_rate', $taxRate);
    }

    // Set Default
    if ($isDefault) {
      $this->check('is_default');
    }
    else {
      $this->uncheck('is_default');
    }
    $this->click('_qf_FinancialAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }


  /**
   * Edit Financial Account
   */
  function _testEditFinancialAccount($editfinancialAccount,
                                     $financialAccountTitle = FALSE,
                                     $financialAccountDescription = FALSE,
                                     $accountingCode = FALSE,
                                     $firstName = FALSE,
                                     $financialAccountType = FALSE,
                                     $taxDeductible = FALSE,
                                     $isActive = TRUE,
                                     $isTax = FALSE,
                                     $taxRate = FALSE,
                                     $isDefault = FALSE
  ) {
    if ($firstName) {
      $this->open($this->sboxPath . "civicrm/admin/financial/financialAccount?reset=1");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }

    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1][text()='{$editfinancialAccount}']/../td[9]/span/a[text()='Edit']");
    $this->click("xpath=//table/tbody//tr/td[1][text()='{$editfinancialAccount}']/../td[9]/span/a[text()='Edit']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent('_qf_FinancialAccount_cancel-botttom');

    // Change Financial Account Name
    if ($financialAccountTitle) {
      $this->type('name', $financialAccountTitle);
    }

    // Financial Description
    if ($financialAccountDescription) {
      $this->type('description', $financialAccountDescription);
    }

    //Accounting Code
    if ($accountingCode) {
      $this->type('accounting_code', $accountingCode);
    }


    // Autofill Edit Organization
    if ($firstName) {
      $this->webtestOrganisationAutocomplete($firstName);
    }

    // Financial Account Type
    if ($financialAccountType) {
      $this->select('financial_account_type_id', "label={$financialAccountType}");
    }

    // Is Tax Deductible
    if ($taxDeductible) {
      $this->check('is_deductible');
    }
    else {
      $this->uncheck('is_deductible');
    }

    // Is Tax
    if ($isTax) {
      $this->check('is_tax');
    }
    else {
      $this->uncheck('is_tax');
    }

    // Tax Rate
    if ($taxRate) {
      $this->type('tax_rate', $taxRate);
    }

    // Set Default
    if ($isDefault) {
      $this->check('is_default');
    }
    else {
      $this->uncheck('is_default');
    }

    // Is Active
    if ($isActive) {
      $this->check('is_active');
    }
    else {
      $this->uncheck('is_active');
    }
    $this->click('_qf_FinancialAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }


  /**
   * Delete Financial Account
   */
  function _testDeleteFinancialAccount($financialAccountTitle) {
    $this->click("xpath=//table/tbody//tr/td[1][text()='{$financialAccountTitle}']/../td[9]/span/a[text()='Delete']");
    $this->waitForElementPresent('_qf_FinancialAccount_next-botttom');
    $this->click('_qf_FinancialAccount_next-botttom');
    $this->waitForElementPresent('link=Add Financial Account');
    $this->assertTrue($this->isTextPresent("Selected Financial Account has been deleted."));
  }

  /**
   * Verify data after ADD and EDIT
   */
  function _assertFinancialAccount($verifyData) {
    foreach ($verifyData as $key => $expectedValue) {
      $actualValue = $this->getValue($key);
      if ($key == 'parent_financial_account') {
        $this->assertTrue((bool) preg_match("/^{$expectedValue}/", $actualValue));
      }
      else {
        $this->assertEquals($expectedValue, $actualValue);
      }
    }
  }

  function _assertSelectVerify($verifySelectFieldData) {
    foreach ($verifySelectFieldData as $key => $expectedvalue) {
      $actualvalue = $this->getSelectedLabel($key);
      $this->assertEquals($expectedvalue, $actualvalue);
    }
  }

  function addeditFinancialType($financialType, $option = 'new') {
    $this->open($this->sboxPath . 'civicrm/admin/financial/financialType?reset=1');

    if ($option == 'Delete') {
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[7]/span[2]");
      $this->waitForElementPresent("css=span.btn-slide-active");
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[7]/span[2]/ul/li[2]/a");
      $this->waitForElementPresent("_qf_FinancialType_next");
      $this->click("_qf_FinancialType_next");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->assertTrue($this->isTextPresent('Selected financial type has been deleted.'), 'Missing text: ' . 'Selected financial type has been deleted.');
      return;
    }
    if ($option == 'new') {
      $this->click("link=Add Financial Type");
    }
    else {
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[oldname]']/../td[7]/span/a[text()='Edit']");
    }
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type('name', $financialType['name']);
    if ($option == 'new') {
      $this->type('description', $financialType['name'] . ' description');
    }

    if ($financialType['is_reserved']) {
      $this->check('is_reserved');
    }
    else {
      $this->uncheck('is_reserved');
    }

    if ($financialType['is_deductible']) {
      $this->check('is_deductible');
    }
    else {
      $this->uncheck('is_deductible');
    }

    $this->click('_qf_FinancialType_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    if ($option == 'new') {
      $text = "The financial type '{$financialType['name']}' has been added. You can add Financial Accounts to this Financial Type now.";
    }
    else {
      $text = "The financial type '{$financialType['name']}' has been saved.";
    }
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
  }


  function changePermissions($permission) {
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->webtestLogin(TRUE);
    $this->changeAdminLinks();
    $this->waitForElementPresent('edit-submit');
    foreach ((array) $permission as $key => $value) {
      $this->check($value);
    }
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('The changes have been saved.'));
    $this->open($this->sboxPath . "user/logout");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->webtestLogin();
  }

  function addProfile($profileTitle, $profileFields) {
    // Go directly to the URL of the screen that you will be testing (New Profile).
    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('link=Add Profile');

    // Add membership custom data field to profile
    $this->waitForElementPresent('_qf_Group_cancel-bottom');
    $this->type('title', $profileTitle);
    $this->click('_qf_Group_next-bottom');

    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    //$this->assertTrue($this->isTextPresent("Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now."));

    foreach ($profileFields as $field) {
      $this->waitForElementPresent('field_name_0');
      // $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->click("id=field_name_0");
      $this->select("id=field_name_0", "label=" . $field['type']);
      $this->waitForElementPresent('field_name_1');
      $this->click("id=field_name_1");
      $this->select("id=field_name_1", "label=" . $field['name']);
      $this->waitForElementPresent('label');
      $this->type("id=label", $field['label']);
      $this->click("id=_qf_Field_next_new-top");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      //$this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '" . $field['name'] . "' has been saved to '" . $profileTitle . "'. You can add another profile field."));
    }
  }

  function _testAddFinancialType() {
    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Revenue'; //Asset Revenue
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 9.99999999;
    $isDefault = FALSE;

    //Add new organisation
    if ($orgName) {
      $this->webtestAddOrganization($orgName);
    }

    $this->_testAddFinancialAccount(
      $financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $orgName,
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );
    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1][text()='{$financialAccountTitle}']/../td[8]/span/a[text()='Edit']");

    //Add new Financial Type
    $financialType['name'] = 'FinancialType ' . substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = TRUE;
    $financialType['is_reserved'] = FALSE;
    $this->addeditFinancialType($financialType);

    $accountRelationship = "Income Account is"; //Asset Account - of Income Account is
    $expected[] = array(
      'financial_account' => $financialAccountTitle,
      'account_relationship' => $accountRelationship
    );

    $this->select('account_relationship', "label={$accountRelationship}");
    sleep(2);
    $this->select('financial_account_id', "label={$financialAccountTitle}");
    $this->click('_qf_FinancialTypeAccount_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $text = 'The financial type Account has been saved.';
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    return $financialType['name'];
  }

  function addPremium($name, $sku, $amount, $price, $cost, $financialType) {
    $this->waitForElementPresent("_qf_ManagePremiums_upload-bottom");
    $this->type("name", $name);
    $this->type("sku", $sku);
    $this->click("CIVICRM_QFID_noImage_16");
    $this->type("min_contribution", $amount);
    $this->type("price", $price);
    $this->type("cost", $cost);
    if ($financialType) {
      $this->select("financial_type_id", "label={$financialType}");
    }
    $this->click("_qf_ManagePremiums_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function addPaymentInstrument($label, $financialAccount) {
    $this->open($this->sboxPath . "civicrm/admin/options/payment_instrument?group=payment_instrument&action=add&reset=1");
    $this->waitForElementPresent("_qf_Options_next-bottom");
    $this->type("label", $label);
    $this->select("financial_account_id", "value=$financialAccount");
    $this->click("_qf_Options_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * Determine the default time-out in milliseconds.
   *
   * @return string, timeout expressed in milliseconds
   */
  function getTimeoutMsec() {
    // note: existing local versions of CiviSeleniumSettings may not declare $timeout, so use @
    $timeout = ($this->settings && @$this->settings->timeout) ? ($this->settings->timeout * 1000) : 30000;
    return (string) $timeout; // don't know why, but all our old code used a string
  }
}

