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
 *  Include configuration
 */
define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');
define('CIVICRM_WEBTEST', 1);

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

  // Current logged-in user
  protected $loggedInAs = NULL;

  /**
   *  Constructor.
   *
   *  Because we are overriding the parent class constructor, we
   *  need to show the same arguments as exist in the constructor of
   *  PHPUnit_Framework_TestCase, since
   *  PHPUnit_Framework_TestSuite::createTest() creates a
   *  ReflectionClass of the Test class and checks the constructor
   *  of that class to decide how to set up the test.
   *
   * @param string $name
   * @param array $data
   * @param string $dataName
   * @param array $browser
   */
  public function __construct($name = NULL, array$data = array(), $dataName = '', array$browser = array()) {
    parent::__construct($name, $data, $dataName, $browser);
    $this->loggedInAs = NULL;

    require_once 'CiviSeleniumSettings.php';
    $this->settings = new CiviSeleniumSettings();
    if (property_exists($this->settings, 'serverStartupTimeOut') && $this->settings->serverStartupTimeOut) {
      global $CiviSeleniumTestCase_polled;
      if (!$CiviSeleniumTestCase_polled) {
        $CiviSeleniumTestCase_polled = TRUE;
        CRM_Utils_Network::waitForServiceStartup(
          $this->drivers[0]->getHost(),
          $this->drivers[0]->getPort(),
          $this->settings->serverStartupTimeOut
        );
      }
    }

    // autoload
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    // also initialize a connection to the db
    // FIXME: not necessary for most tests, consider moving into functions that need this
    $config = CRM_Core_Config::singleton();
  }

  protected function setUp() {
    $this->setBrowser($this->settings->browser);
    // Make sure that below strings have path separator at the end
    $this->setBrowserUrl($this->settings->sandboxURL);
    $this->sboxPath = $this->settings->sandboxPATH;
    if (property_exists($this->settings, 'rcHost') && $this->settings->rcHost) {
      $this->setHost($this->settings->rcHost);
    }
    if (property_exists($this->settings, 'rcPort') && $this->settings->rcPort) {
      $this->setPort($this->settings->rcPort);
    }
  }

  /**
   * @return string
   */
  protected function prepareTestSession() {
    $result = parent::prepareTestSession();

    // Set any cookies required by local installation
    // Note: considered doing this in setUp(), but the Selenium session wasn't yet initialized.
    if (property_exists($this->settings, 'cookies')) {
      // We don't really care about this page, but it seems we need
      // to open a page before setting a cookie.
      $this->open($this->sboxPath);
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->setCookies($this->settings->cookies);
    }
    return $result;
  }

  /**
   * @param array $cookies
   *   Each item is an Array with keys:
   *   - name: string
   *   - value: string; note that RFC's don't define particular encoding scheme, so
   *    you must pick one yourself and pre-encode; does not allow values with
   *    commas, semicolons, or whitespace
   *   - path: string; default: '/'
   *   - max_age: int; default: 1 week (7*24*60*60)
   */
  protected function setCookies($cookies) {
    foreach ($cookies as $cookie) {
      if (!isset($cookie['path'])) {
        $cookie['path'] = '/';
      }
      if (!isset($cookie['max_age'])) {
        $cookie['max_age'] = 7 * 24 * 60 * 60;
      }
      $this->deleteCookie($cookie['name'], $cookie['path']);
      $optionExprs = array();
      foreach ($cookie as $key => $value) {
        if ($key != 'name' && $key != 'value') {
          $optionExprs[] = "$key=$value";
        }
      }
      $this->createCookie("{$cookie['name']}={$cookie['value']}", implode(', ', $optionExprs));
    }
  }

  protected function tearDown() {
  }

  /**
   * Authenticate as drupal user.
   * @param $user : (str) the key 'user' or 'admin', or a literal username
   * @param $pass : (str) if $user is a literal username and not 'user' or 'admin', supply the password
   */
  public function webtestLogin($user = 'user', $pass = NULL) {
    // If already logged in as correct user, do nothing
    if ($this->loggedInAs === $user) {
      return;
    }
    // If we are logged in as a different user, log out first
    if ($this->loggedInAs) {
      $this->webtestLogout();
    }
    $this->open("{$this->sboxPath}user");
    // Lookup username & password if not supplied
    $username = $user;
    if ($pass === NULL) {
      $pass = $user == 'admin' ? $this->settings->adminPassword : $this->settings->password;
      $username = $user == 'admin' ? $this->settings->adminUsername : $this->settings->username;
    }
    // Make sure login form is available
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $username);
    $this->type('edit-pass', $pass);
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->loggedInAs = $user;
  }

  public function webtestLogout() {
    if ($this->loggedInAs) {
      $this->open($this->sboxPath . "user/logout");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    $this->loggedInAs = NULL;
  }

  /**
   * Open an internal path beginning with 'civicrm/'
   *
   * @param string $url
   *   omit the 'civicrm/' it will be added for you.
   * @param string|array $args
   *   optional url arguments.
   * @param $waitFor
   *   Page element to wait for - using this is recommended to ensure the document is fully loaded.
   *
   * Although it doesn't seem to do much now, using this function is recommended for
   * opening all civi pages, and using the $args param is also strongly encouraged
   * This will make it much easier to run webtests in other CMSs in the future
   */
  public function openCiviPage($url, $args = NULL, $waitFor = 'civicrm-footer') {
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
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->checkForErrorsOnPage();
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
  }

  /**
   * Click on a link or button.
   * Wait for the page to load
   * Wait for an element to be present
   * @param $element
   * @param string $waitFor
   * @param bool $waitForPageLoad
   */
  public function clickLink($element, $waitFor = 'civicrm-footer', $waitForPageLoad = TRUE) {
    $this->click($element);
    // conditional wait for page load e.g for ajax form save
    if ($waitForPageLoad) {
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->checkForErrorsOnPage();
    }
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
  }

  /**
   * Click a link or button and wait for an ajax dialog to load.
   * @param string $element
   * @param string $waitFor
   */
  public function clickPopupLink($element, $waitFor = NULL) {
    $this->clickAjaxLink($element, 'css=.ui-dialog');
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
  }

  /**
   * Click a link or button and wait for ajax content to load or refresh.
   * @param string $element
   * @param string $waitFor
   */
  public function clickAjaxLink($element, $waitFor = NULL) {
    $this->click($element);
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
    $this->waitForAjaxContent();
  }

  /**
   * Force a link to open full-page, even if it would normally open in a popup
   * @note: works with links only, not buttons
   * @param string $element
   * @param string $waitFor
   */
  public function clickLinkSuppressPopup($element, $waitFor = 'civicrm-footer') {
    $link = $this->getAttribute($element . '@href');
    $this->open($link);
    $this->waitForPageToLoad($this->getTimeoutMsec());
    if ($waitFor) {
      $this->waitForElementPresent($waitFor);
    }
  }

  /**
   * Wait for all ajax snippets to finish loading.
   */
  public function waitForAjaxContent() {
    $this->waitForElementNotPresent('css=.blockOverlay');
    // Some ajax calls happen in pairs (e.g. submit a popup form then refresh the underlying content)
    // So we'll wait a sec and recheck to see if any more stuff is loading
    sleep(1);
    if ($this->isElementPresent('css=.blockOverlay')) {
      $this->waitForAjaxContent();
    }
  }

  /**
   * Call the API on the local server.
   * (kind of defeats the point of a webtest - see CRM-11889)
   * @param $entity
   * @param $action
   * @param $params
   * @return array|int
   */
  public function webtest_civicrm_api($entity, $action, $params) {
    if (!isset($params['version'])) {
      $params['version'] = 3;
    }

    $result = civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result);
    return $result;
  }

  /**
   * Call the API on the remote server using the AJAX endpoint.
   *
   * @see CRM-11889
   * @param $entity
   * @param $action
   * @param array $params
   * @return mixed
   */
  public function rest_civicrm_api($entity, $action, $params = array()) {
    $params += array(
      'version' => 3,
    );
    static $reqId = 0;
    $reqId++;

    $jsCmd = '
      setTimeout(function(){
        CRM.api3("@entity", "@action", @params).then(function(a){
          cj("<textarea>").css("display", "none").prop("id","crmajax@reqId").val(JSON.stringify(a)).appendTo("body");
        });
      }, 500);
    ';
    $jsArgs = array(
      '@entity' => $entity,
      '@action' => $action,
      '@params' => json_encode($params),
      '@reqId' => $reqId,
    );
    $js = strtr($jsCmd, $jsArgs);
    $this->runScript($js);
    $this->waitForElementPresent("crmajax{$reqId}");
    $result = json_decode($this->getValue("crmajax{$reqId}"), TRUE);
    $this->runScript("cj('#crmajax{$reqId}').remove();");
    return $result;
  }

  /**
   * @param string $option_group_name
   *
   * @return array|int
   */
  public function webtestGetFirstValueForOptionGroup($option_group_name) {
    $result = $this->webtest_civicrm_api("OptionValue", "getvalue", array(
      'option_group_name' => $option_group_name,
      'option.limit' => 1,
      'return' => 'value',
    ));
    return $result;
  }

  /**
   * @return mixed
   */
  public function webtestGetValidCountryID() {
    static $_country_id;
    if (is_null($_country_id)) {
      $config_backend = $this->webtestGetConfig('countryLimit');
      $_country_id = current($config_backend);
    }
    return $_country_id;
  }

  /**
   * @param $entity
   *
   * @return mixed|null
   */
  public function webtestGetValidEntityID($entity) {
    // michaelmcandrew: would like to use getvalue but there is a bug
    // for e.g. group where option.limit not working at the moment CRM-9110
    $result = $this->webtest_civicrm_api($entity, "get", array('option.limit' => 1, 'return' => 'id'));
    if (!empty($result['values'])) {
      return current(array_keys($result['values']));
    }
    return NULL;
  }

  /**
   * @param $field
   *
   * @return mixed
   */
  public function webtestGetConfig($field) {
    static $_config_backend;
    if (is_null($_config_backend)) {
      $result = $this->webtest_civicrm_api("Domain", "getvalue", array(
        'current_domain' => 1,
        'option.limit' => 1,
        'return' => 'config_backend',
      ));
      $_config_backend = unserialize($result);
    }
    return $_config_backend[$field];
  }

  /**
   * Ensures the required CiviCRM components are enabled.
   * @param $components
   */
  public function enableComponents($components) {
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
      $this->clickLink("_qf_Component_next-bottom");
      $this->checkCRMAlert("Saved");
    }
  }

  /**
   * Add a contact with the given first and last names and either a given email.
   * (when specified), a random email (when true) or no email (when unspecified or null).
   *
   * @param string $fname
   *   Contact’s first name.
   * @param string $lname
   *   Contact’s last name.
   * @param mixed $email
   *   Contact’s email (when string) or random email (when true) or no email (when null).
   * @param string $contactSubtype
   *
   * @return string|null
   *   either a string with the (either generated or provided) email or null (if no email)
   */
  public function webtestAddContact($fname = 'Anthony', $lname = 'Anderson', $email = NULL, $contactSubtype = NULL) {
    $args = 'reset=1&ct=Individual';
    if ($contactSubtype) {
      $args .= "&cst={$contactSubtype}";
    }
    $this->openCiviPage('contact/add', $args, '_qf_Contact_upload_view-bottom');
    $this->type('first_name', $fname);
    $this->type('last_name', $lname);
    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }
    $this->clickLink('_qf_Contact_upload_view-bottom');
    return $email;
  }

  /**
   * @param string $householdName
   * @param null $email
   *
   * @return null|string
   */
  public function webtestAddHousehold($householdName = "Smith's Home", $email = NULL) {
    $this->openCiviPage("contact/add", "reset=1&ct=Household");
    $this->click('household_name');
    $this->type('household_name', $householdName);

    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }

    $this->clickLink('_qf_Contact_upload_view');
    return $email;
  }

  /**
   * @param string $organizationName
   * @param null $email
   * @param null $contactSubtype
   *
   * @return null|string
   */
  public function webtestAddOrganization($organizationName = "Organization XYZ", $email = NULL, $contactSubtype = NULL) {
    $args = 'reset=1&ct=Organization';
    if ($contactSubtype) {
      $args .= "&cst={$contactSubtype}";
    }
    $this->openCiviPage('contact/add', $args, '_qf_Contact_upload_view-bottom');
    $this->click('organization_name');
    $this->type('organization_name', $organizationName);

    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }
    $this->clickLink('_qf_Contact_upload_view');
    return $email;
  }

  /**
   * @param $sortName
   * @param string $fieldName
   */
  public function webtestFillAutocomplete($sortName, $fieldName = 'contact_id') {
    $this->select2($fieldName, $sortName);
    //$this->assertContains($sortName, $this->getValue($fieldName), "autocomplete expected $sortName but didn’t find it in " . $this->getValue($fieldName));
  }

  /**
   * @param $sortName
   */
  public function webtestOrganisationAutocomplete($sortName) {
    $this->clickAt("//*[@id='contact_id']/../div/a");
    $this->waitForElementPresent("//*[@id='select2-drop']/div/input");
    $this->keyDown("//*[@id='select2-drop']/div/input", " ");
    $this->type("//*[@id='select2-drop']/div/input", $sortName);
    $this->typeKeys("//*[@id='select2-drop']/div/input", $sortName);
    $this->waitForElementPresent("//*[@class='select2-result-label']");
    $this->clickAt("//*[@class='select2-results']/li[1]");
    //$this->assertContains($sortName, $this->getValue('contact_1'), "autocomplete expected $sortName but didn’t find it in " . $this->getValue('contact_1'));
  }

  /**
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
   * @param $dateElement
   * @param null $strToTimeArgs
   */
  public function webtestFillDate($dateElement, $strToTimeArgs = NULL, $multiselect = FALSE) {
    $timeStamp = strtotime($strToTimeArgs ? $strToTimeArgs : '+1 month');

    $year = date('Y', $timeStamp);
    // -1 ensures month number is inline with calender widget's month
    $mon = date('n', $timeStamp) - 1;
    $day = date('j', $timeStamp);

    if (!$multiselect) {
      $this->click("xpath=//input[starts-with(@id, '{$dateElement}_display_')]");
    }
    $this->waitForElementPresent("css=div#ui-datepicker-div.ui-datepicker div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-month");
    $this->select("css=div#ui-datepicker-div.ui-datepicker div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-month", "value=$mon");
    $this->select("css=div#ui-datepicker-div div.ui-datepicker-header div.ui-datepicker-title select.ui-datepicker-year", "value=$year");
    $this->click("link=$day");
  }

  /**
   * 1. set both date and time.
   * @param $dateElement
   * @param null $strToTimeArgs
   */
  public function webtestFillDateTime($dateElement, $strToTimeArgs = NULL) {
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
   * @param array $expected
   *   Array of key/value pairs (like Status/Registered) to be checked.
   * @param string $xpathPrefix
   *   Pass in an xpath locator to "get to" the desired table or tables. Will be prefixed to xpath.
   *                              table path. Include leading forward slashes (e.g. "//div[@id='activity-content']").
   * @param string $tableId
   *   Pass in the id attribute of a table to be verified if you want to only check a specific table.
   *                              on the web page.
   */
  public function webtestVerifyTabularData($expected, $xpathPrefix = NULL, $tableId = NULL) {
    $tableLocator = "";
    if ($tableId) {
      $tableLocator = "[@id='$tableId']";
    }
    foreach ($expected as $label => $value) {
      if ($xpathPrefix) {
        $this->waitForElementPresent("xpath=//table{$tableLocator}/tbody/tr/td{$xpathPrefix}[text()='{$label}']/../following-sibling::td");
        $this->verifyText("xpath=//table{$tableLocator}/tbody/tr/td{$xpathPrefix}[text()='{$label}']/../following-sibling::td", preg_quote($value), 'In line ' . __LINE__);
      }
      else {
        $this->waitForElementPresent("xpath=//table{$tableLocator}/tbody/tr/td[text()='{$label}']/following-sibling::td");
        $this->verifyText("xpath=//table{$tableLocator}/tbody/tr/td[text()='{$label}']/following-sibling::td", preg_quote($value), 'In line ' . __LINE__);
      }
    }
  }

  /**
   * Types text into a ckEditor rich text field in a form.
   *
   * @param string $fieldName
   *   Form field name (as assigned by PHP buildForm class).
   * @param string $text
   *   Text to type into the field.
   * @param string $editor
   *   Which text editor (valid values are 'CKEditor', 'TinyMCE').
   *
   * @param bool $compressed
   * @throws \PHPUnit_Framework_AssertionFailedError
   */
  public function fillRichTextField($fieldName, $text = 'Typing this text into editor.', $editor = 'CKEditor', $compressed = FALSE) {
    // make sure cursor focuses on the field
    $this->fireEvent($fieldName, 'focus');
    if ($editor == 'CKEditor') {
      if ($compressed) {
        $this->click("{$fieldName}-plain");
      }
      $this->waitForElementPresent("xpath=//div[@id='cke_{$fieldName}']//iframe");
      $this->runScript("CKEDITOR.instances['{$fieldName}'].setData('<p>{$text}</p>');");
    }
    elseif ($editor == 'TinyMCE') {
      $this->waitForElementPresent("xpath=//iframe[@id='{$fieldName}_ifr']");
      $this->runScript("tinyMCE.activeEditor.setContent('<p>{$text}</p>');");
    }
    else {
      $this->fail("Unknown editor value: $editor, failing (in CiviSeleniumTestCase::fillRichTextField ...");
    }
    $this->selectFrame('relative=top');
  }

  /**
   * Types option label and name into a table of multiple choice options.
   * (for price set fields of type select, radio, or checkbox)
   * TODO: extend for custom field multiple choice table input
   *
   * @param array $options
   *   Form field name (as assigned by PHP buildForm class).
   * @param array $validateStrings
   *   Appends label and name strings to this array so they can be validated later.
   *
   * @return void
   */
  public function addMultipleChoiceOptions($options, &$validateStrings) {
    foreach ($options as $oIndex => $oValue) {
      $validateStrings[] = $oValue['label'];
      $validateStrings[] = $oValue['amount'];
      if (!empty($oValue['membership_type_id'])) {
        $this->select("membership_type_id_{$oIndex}", "value={$oValue['membership_type_id']}");
      }
      if (!empty($oValue['financial_type_id'])) {
        $this->select("option_financial_type_id_{$oIndex}", "label={$oValue['financial_type_id']}");
      }
      $this->type("option_label_{$oIndex}", $oValue['label']);
      $this->type("option_amount_{$oIndex}", $oValue['amount']);
      $this->click('link=another choice');
    }
  }

  /**
   * Use a contact EntityRef field to add a new contact.
   * @param string $field
   *   Selector.
   * @param string $contactType
   * @return array
   *   Array of contact attributes (id, names, email)
   */
  public function createDialogContact($field = 'contact_id', $contactType = 'Individual') {
    $selectId = 's2id_' . $this->getAttribute($field . '@id');
    $this->clickAt("xpath=//div[@id='$selectId']/a");
    $this->clickAjaxLink("xpath=//li[@class='select2-no-results']//a[contains(text(), 'New $contactType')]", '_qf_Edit_next');

    $name = substr(sha1(rand()), 0, rand(6, 8));
    $params = array();
    if ($contactType == 'Individual') {
      $params['first_name'] = "$name $contactType";
      $params['last_name'] = substr(sha1(rand()), 0, rand(5, 9));
    }
    else {
      $params[strtolower($contactType) . '_name'] = "$name $contactType";
    }
    foreach ($params as $param => $val) {
      $this->type($param, $val);
    }
    $this->type('email-Primary', $params['email'] = "{$name}@example.com");
    $this->clickAjaxLink('_qf_Edit_next');

    $this->waitForText("xpath=//div[@id='$selectId']", "$name");

    $params['sort_name'] = $contactType == 'Individual' ? $params['last_name'] . ', ' . $params['first_name'] : "$name $contactType";
    $params['display_name'] = $contactType == 'Individual' ? $params['first_name'] . ' ' . $params['last_name'] : $params['sort_name'];
    $params['id'] = $this->getValue($field);
    return $params;
  }

  /**
   * @deprecated in favor of createDialogContact
   * @param string $fname
   * @param string $lname
   * @param string $email
   * @param int $type
   * @param string $selectId
   * @param int $row
   * @param string $prefix
   */
  public function webtestNewDialogContact(
    $fname = 'Anthony', $lname = 'Anderson', $email = 'anthony@anderson.biz',
    $type = 4, $selectId = 's2id_contact_id', $row = 1, $prefix = '') {
    // 4 - Individual profile
    // 5 - Organization profile
    // 6 - Household profile
    $profile = array('4' => 'New Individual', '5' => 'New Organization', '6' => 'New Household');
    $this->clickAt("xpath=//div[@id='$selectId']/a");
    $this->clickPopupLink("xpath=//li[@class='select2-no-results']//a[contains(text(),' $profile[$type]')]", '_qf_Edit_next');

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
    $this->clickAjaxLink('_qf_Edit_next');

    // Is new contact created?
    if ($lname) {
      $this->waitForText("xpath=//div[@id='$selectId']", "$lname, $fname");
    }
    else {
      $this->waitForText("xpath=//div[@id='$selectId']", "$fname");
    }
  }

  /**
   * Generic function to check that strings are present in the page.
   *
   * @strings  array    array of strings or a single string
   *
   * @param $strings
   * @return void
   */
  public function assertStringsPresent($strings) {
    foreach ((array) $strings as $string) {
      $this->assertTrue($this->isTextPresent($string), "Could not find $string on page");
    }
  }

  /**
   * Generic function to parse a URL string into it's elements.extract a variable value from a string (url)
   *
   * @url      string url to parse or retrieve current url if null
   *
   * @param null $url
   * @return array
   *   returns an associative array containing any of the various components
   *                  of the URL that are present. Querystring elements are returned in sub-array (elements.queryString)
   *                  http://php.net/manual/en/function.parse-url.php
   */
  public function parseURL($url = NULL) {
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
   * Returns a single argument from the url query.
   * @param $arg
   * @param null $url
   * @return null
   */
  public function urlArg($arg, $url = NULL) {
    $elements = $this->parseURL($url);
    return isset($elements['queryString'][$arg]) ? $elements['queryString'][$arg] : NULL;
  }

  /**
   * Define a payment processor for use by a webtest. Default is to create Dummy processor
   * which is useful for testing online public forms (online contribution pages and event registration)
   *
   * @param string $processorName
   *   Name assigned to new processor.
   * @param string $processorType
   *   Name for processor type (e.g. PayPal, Dummy, etc.).
   * @param array $processorSettings
   *   Array of fieldname => value for required settings for the processor.
   *
   * @param string $financialAccount
   * @throws PHPUnit_Framework_AssertionFailedError
   * @return int
   */
  public function webtestAddPaymentProcessor($processorName = 'Test Processor', $processorType = 'Dummy', $processorSettings = NULL, $financialAccount = 'Deposit Bank Account') {
    if (!$processorName) {
      $this->fail("webTestAddPaymentProcessor requires $processorName.");
    }
    // Ensure we are logged in as admin before we proceed
    $this->webtestLogin('admin');

    if ($processorName === 'Test Processor') {
      // Use the default test processor, no need to create a new one
      $this->openCiviPage('admin/paymentProcessor', 'action=update&id=1&reset=1', '_qf_PaymentProcessor_cancel-bottom');
      $this->check('is_default');
      $this->select('financial_account_id', "label={$financialAccount}");
      $this->clickLink('_qf_PaymentProcessor_next-bottom');
      return 1;
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
        //dummy live username/password are passed to bypass processor validation on live credential
        'user_name' => '3HcY62mY',
        'password' => '69943NrwaQA92b8J',
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
        'user_name' => '559999327053114',
        'test_password' => 'R2zv2g60-A7GXKJYl0nR0g',
        'test_signature' => 'R2zv2g60-A7GXKJYl0nR0g',
        'password' => 'R2zv2g60-A7GXKJYl0nR0g',
        'signature' => 'R2zv2g60-A7GXKJYl0nR0g',
      );
    }
    elseif ($processorType == 'PayPal_Standard') {
      $processorSettings = array(
        'user_name' => 'V18ki@9r5Bf.org',
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
    $this->openCiviPage('admin/paymentProcessor', 'action=add&reset=1&pp=' . $pid, 'name');
    $this->type('name', $processorName);
    $this->select('financial_account_id', "label={$financialAccount}");
    foreach ($processorSettings as $f => $v) {
      $this->type($f, $v);
    }

    // Save
    $this->clickLink('_qf_PaymentProcessor_next-bottom');

    $this->waitForTextPresent($processorName);

    // Get payment processor id
    $paymentProcessorLink = $this->getAttribute("xpath=//table[@class='selector row-highlight']//tbody//tr/td[text()='{$processorName}']/../td[7]/span/a[1]@href");
    return $this->urlArg('id', $paymentProcessorLink);
  }

  public function webtestAddCreditCardDetails() {
    $this->waitForElementPresent('credit_card_type');
    $this->select('credit_card_type', 'label=Visa');
    $this->type('credit_card_number', '4807731747657838');
    $this->type('cvv2', '123');
    $this->select('credit_card_exp_date[M]', 'label=Feb');
    $this->select('credit_card_exp_date[Y]', 'label=2019');
  }

  /**
   * @param null $firstName
   * @param null $middleName
   * @param null $lastName
   *
   * @return array
   */
  public function webtestAddBillingDetails($firstName = NULL, $middleName = NULL, $lastName = NULL) {
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
    $this->select2('billing_country_id-5', 'United States');
    $this->select2('billing_state_province_id-5', 'California');
    $this->type('billing_postal_code-5', '93245');

    return array($firstName, $middleName, $lastName);
  }

  /**
   * @param $fieldLocator
   * @param null $filePath
   *
   * @return null|string
   */
  public function webtestAttachFile($fieldLocator, $filePath = NULL) {
    if (!$filePath) {
      $filePath = '/tmp/testfile_' . substr(sha1(rand()), 0, 7) . '.txt';
      $fp = @fopen($filePath, 'w');
      fwrite($fp, 'Test file created by selenium test.');
      @fclose($fp);
    }

    $this->assertTrue(file_exists($filePath), 'Not able to locate file: ' . $filePath);

    $this->attachFile($fieldLocator, "file://{$filePath}");

    return $filePath;
  }

  /**
   * @param $headers
   * @param $rows
   * @param null $filePath
   *
   * @return null|string
   */
  public function webtestCreateCSV($headers, $rows, $filePath = NULL) {
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
   * @param array $params
   *   array of required params.
   *
   * @return array
   *   array of saved params values.
   */
  public function webtestAddRelationshipType($params = array()) {
    $this->openCiviPage("admin/reltype", "reset=1&action=add");

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

    $this->openCiviPage("admin/reltype", "reset=1");

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
   * FIXME: this function take an absurd number of params - very unwieldy :(
   *
   * @param null $hash
   * @param null $rand
   * @param null $pageTitle
   * @param array $processor
   * @param bool $amountSection
   * @param bool $payLater
   * @param bool $onBehalf
   * @param bool $pledges
   * @param bool $recurring
   * @param bool $membershipTypes
   * @param int $memPriceSetId
   * @param bool $friend
   * @param int $profilePreId
   * @param int $profilePostId
   * @param bool $premiums
   * @param bool $widget
   * @param bool $pcp
   * @param bool $isAddPaymentProcessor
   * @param bool $isPcpApprovalNeeded
   * @param bool $isSeparatePayment
   * @param bool $honoreeSection
   * @param bool $allowOtherAmount
   * @param bool $isConfirmEnabled
   * @param string $financialType
   * @param bool $fixedAmount
   * @param bool $membershipsRequired
   *
   * @return null
   *   of newly created online contribution page.
   */
  public function webtestAddContributionPage(
    $hash = NULL,
    $rand = NULL,
    $pageTitle = NULL,
    $processor = array('Test Processor' => 'Dummy'),
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
    $allowOtherAmount = TRUE,
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
    $this->openCiviPage('admin/contribute', 'action=add&reset=1');

    // fill in step 1 (Title and Settings)
    $this->type('title', $pageTitle);

    //to select financial type
    $this->select('financial_type_id', "label={$financialType}");

    if ($onBehalf) {
      $this->click('is_organization');
      $this->select("xpath=//*[@class='crm-contribution-onbehalf_profile_id']//span[@class='crm-profile-selector-select']//select", 'label=On Behalf Of Organization');
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
      $this->click("//*[@id='s2id_soft_credit_types']/ul");
      $this->waitForElementPresent("//*[@id='select2-drop']/ul");
      $this->waitForElementPresent("//*[@class='select2-result-label']");
      $this->clickAt("//*[@class='select2-results']/li[1]");
    }

    // is confirm enabled? it starts out enabled, so uncheck it if false
    if (!$isConfirmEnabled) {
      $this->click("id=is_confirm_enabled");
    }

    // Submit form
    $this->clickLink('_qf_Settings_next', "_qf_Amount_next-bottom");

    // Get contribution page id
    $pageId = $this->urlArg('id');

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
        $this->fillRichTextField('pay_later_receipt', "Pay later instructions $hash");
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
      if ($allowOtherAmount) {

        $this->click('is_allow_other_amount');

        // there shouldn't be minimums and maximums on test contribution forms unless you specify it
        //$this->type('min_amount', $rand / 2);
        //$this->type('max_amount', $rand * 10);
      }
      if ($fixedAmount || !$allowOtherAmount) {
        $this->type('label_1', "Label $hash");
        $this->type('value_1', "$rand");
      }
      $this->click('CIVICRM_QFID_1_4');
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
          $membershipTypes = array(array('id' => 2, 'name' => 'Student', 'default' => 1));
        }

        // FIXME: handle Introductory Message - New Memberships/Renewals
        foreach ($membershipTypes as $mType) {
          $this->click("membership_type_{$mType['id']}");
          if (array_key_exists('default', $mType)) {
            $this->click("xpath=//label[text() = '$mType[name]']/parent::td/parent::tr/td[2]/input");
          }
          if (array_key_exists('auto_renew', $mType)) {
            $this->select("auto_renew_{$mType['id']}", "label=Give option");
          }
        }
        if ($membershipsRequired) {
          $this->click('is_required');
        }
        if ($isSeparatePayment) {
          $this->click('is_separate_payment');
        }
      }
      $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
      $text = "'MembershipBlock' information has been saved.";
      $this->isTextPresent($text);
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
        $this->select('css=tr.crm-contribution-contributionpage-custom-form-block-custom_pre_id span.crm-profile-selector-select select', "value={$profilePreId}");
      }

      if ($profilePostId) {
        $this->select('css=tr.crm-contribution-contributionpage-custom-form-block-custom_post_id span.crm-profile-selector-select select', "value={$profilePostId}");
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

    return $pageId;
  }

  /**
   * Update default strict rule.
   *
   * @param string $contactType
   * @param array $fields
   *   Fields to be set for strict rule.
   * @param int $threshold
   *   Rule's threshold value.
   */
  public function webtestStrictDedupeRuleDefault($contactType = 'Individual', $fields = array(), $threshold = 10) {
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

    $this->openCiviPage('contact/deduperules', "action=update&id=$strictRuleId", '_qf_DedupeRules_next-bottom');

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

  /**
   * @param string $period_type
   * @param int $duration_interval
   * @param string $duration_unit
   * @param string $auto_renew
   * @param int $minimumFee
   * @param string $financialType
   *
   * @return array
   */
  public function webtestAddMembershipType($period_type = 'rolling', $duration_interval = 1, $duration_unit = 'year', $auto_renew = 'no', $minimumFee = 100, $financialType = 'Member Dues') {
    $membershipTitle = substr(sha1(rand()), 0, 7);
    $membershipOrg = $membershipTitle . ' memorg';
    $this->webtestAddOrganization($membershipOrg, TRUE);

    $title = 'Membership Type ' . substr(sha1(rand()), 0, 7);
    $memTypeParams = array(
      'membership_type' => $title,
      'member_of_contact' => $membershipOrg,
      'financial_type' => $financialType,
      'period_type' => $period_type,
    );

    $this->openCiviPage("admin/member/membershipType/add", "action=add&reset=1", '_qf_MembershipType_cancel-bottom');

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

    $this->select2('member_of_contact_id', $membershipTitle);

    $this->type('minimum_fee', $minimumFee);
    $this->select('financial_type_id', "label={$memTypeParams['financial_type']}");

    $this->type('duration_interval', $duration_interval);
    $this->select('duration_unit', "label={$duration_unit}");

    $this->select('period_type', "value={$period_type}");

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->assertTrue($this->isTextPresent("The membership type '$title' has been saved."));

    return $memTypeParams;
  }

  /**
   * @param null $groupName
   * @param null $parentGroupName
   *
   * @return null|string
   */
  public function WebtestAddGroup($groupName = NULL, $parentGroupName = NULL) {
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
    $this->clickLink('_qf_Edit_upload-bottom');

    // Is status message correct?
    $this->waitForText('crm-notification-container', "$groupName");
    return $groupName;
  }

  /**
   * @param string $activityType
   *
   * @return null
   */
  public function WebtestAddActivity($activityType = "Meeting") {
    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Anderson", $firstName2 . "@anderson.name");

    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    $this->waitForElementPresent("_qf_Activity_upload-bottom");
    $this->waitForElementPresent("s2id_target_contact_id");

    $this->assertTrue($this->isTextPresent("Anderson, " . $firstName2), "Contact not found in line " . __LINE__);

    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->select2("assignee_contact_id", $firstName1, TRUE);

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
    $this->waitForElementPresent("xpath=//div[@id='crm-notification-container']");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Activity '$subject' has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']//table/tbody/tr[2]/td[8]/span/a[text()='View']");

    // click through to the Activity view screen
    $this->clickLinkSuppressPopup("xpath=//div[@class='dataTables_wrapper no-footer']//table/tbody/tr[2]/td[8]/span/a[text()='View']", '_qf_Activity_cancel-bottom');

    // parse URL to grab the activity id
    // pass id back to any other tests that call this class
    return $this->urlArg('id');
  }

  /**
   * @return bool
   */
  public static function checkDoLocalDBTest() {
    if (defined('CIVICRM_WEBTEST_LOCAL_DB') &&
      CIVICRM_WEBTEST_LOCAL_DB
    ) {
      require_once 'tests/phpunit/CiviTest/CiviDBAssert.php';
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Generic function to compare expected values after an api call to retrieved.
   * DB values.
   *
   * @param string $daoName
   *   DAO Name of object we're evaluating.
   * @param int $id
   *   Id of object
   * @param array $match
   *   Associative array of field name => expected value. Empty if asserting
   *                      that a DELETE occurred
   * @param bool $delete
   *   are we checking that a DELETE action occurred?
   */
  public function assertDBState($daoName, $id, $match, $delete = FALSE) {
    if (self::checkDoLocalDBTest()) {
      CiviDBAssert::assertDBState($this, $daoName, $id, $match, $delete);
    }
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if a record is found.
   * @param string $daoName
   * @param string $searchValue
   * @param string $returnColumn
   * @param string $searchColumn
   * @param string $message
   */
  public function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (self::checkDoLocalDBTest()) {
      CiviDBAssert::assertDBNotNull($this, $daoName, $searchValue, $returnColumn, $searchColumn, $message);
    }
  }

  /**
   * Request a record from the DB by searchColumn+searchValue. Success if returnColumn value is NULL.
   * @param string $daoName
   * @param string $searchValue
   * @param string $returnColumn
   * @param string $searchColumn
   * @param string $message
   */
  public function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (self::checkDoLocalDBTest()) {
      CiviDBAssert::assertDBNull($this, $daoName, $searchValue, $returnColumn, $searchColumn, $message);
    }
  }

  /**
   * Request a record from the DB by id. Success if row not found.
   * @param string $daoName
   * @param int $id
   * @param string $message
   */
  public function assertDBRowNotExist($daoName, $id, $message) {
    if (self::checkDoLocalDBTest()) {
      CiviDBAssert::assertDBRowNotExist($this, $daoName, $id, $message);
    }
  }

  /**
   * Compare all values in a single retrieved DB record to an array of expected values.
   * @param string $daoName
   * @param array $searchParams
   * @param $expectedValues
   */
  public function assertDBCompareValues($daoName, $searchParams, $expectedValues) {
    if (self::checkDoLocalDBTest()) {
      CiviDBAssert::assertDBCompareValues($this, $daoName, $searchParams, $expectedValues);
    }
  }

  /**
   * @param $expected
   * @param $actual
   * @param string $message
   */
  public function assertType($expected, $actual, $message = '') {
    $this->assertInternalType($expected, $actual, $message);
  }

  /**
   * Add new Financial Account.
   * @param $financialAccountTitle
   * @param bool $financialAccountDescription
   * @param bool $accountingCode
   * @param bool $firstName
   * @param bool $financialAccountType
   * @param bool $taxDeductible
   * @param bool $isActive
   * @param bool $isTax
   * @param bool $taxRate
   * @param bool $isDefault
   */
  public function _testAddFinancialAccount(
    $financialAccountTitle,
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

    $this->openCiviPage("admin/financial/financialAccount", "reset=1");

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
  }

  /**
   * Edit Financial Account.
   * @param $editfinancialAccount
   * @param bool $financialAccountTitle
   * @param bool $financialAccountDescription
   * @param bool $accountingCode
   * @param bool $firstName
   * @param bool $financialAccountType
   * @param bool $taxDeductible
   * @param bool $isActive
   * @param bool $isTax
   * @param bool $taxRate
   * @param bool $isDefault
   */
  public function _testEditFinancialAccount(
    $editfinancialAccount,
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
      $this->openCiviPage("admin/financial/financialAccount", "reset=1");
    }

    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1]/div[text()='{$editfinancialAccount}']/../../td[9]/span/a[text()='Edit']");
    $this->clickLink("xpath=//table/tbody//tr/td[1]/div[text()='{$editfinancialAccount}']/../../td[9]/span/a[text()='Edit']", '_qf_FinancialAccount_cancel-botttom', FALSE);

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
    $this->waitForElementPresent('link=Add Financial Account');
  }

  /**
   * Delete Financial Account.
   * @param $financialAccountTitle
   */
  public function _testDeleteFinancialAccount($financialAccountTitle) {
    $this->click("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Delete']");
    $this->waitForElementPresent('_qf_FinancialAccount_next-botttom');
    $this->click('_qf_FinancialAccount_next-botttom');
    $this->waitForElementPresent('link=Add Financial Account');
    $this->waitForText('crm-notification-container', "Selected Financial Account has been deleted.");
  }

  /**
   * Verify data after ADD and EDIT.
   * @param $verifyData
   */
  public function _assertFinancialAccount($verifyData) {
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

  /**
   * @param $verifySelectFieldData
   */
  public function _assertSelectVerify($verifySelectFieldData) {
    foreach ($verifySelectFieldData as $key => $expectedvalue) {
      $actualvalue = $this->getSelectedLabel($key);
      $this->assertEquals($expectedvalue, $actualvalue);
    }
  }

  /**
   * @param $financialType
   * @param string $option
   */
  public function addeditFinancialType($financialType, $option = 'new') {
    $this->openCiviPage("admin/financial/financialType", "reset=1");

    if ($option == 'Delete') {
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1]/div[text()='$financialType[name]']/../../td[7]/span[2]");
      $this->waitForElementPresent("css=span.btn-slide-active");
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1]/div[text()='$financialType[name]']/../../td[7]/span[2]/ul/li[2]/a");
      $this->waitForElementPresent("_qf_FinancialType_next");
      $this->click("_qf_FinancialType_next");
      $this->waitForElementPresent("newFinancialType");
      $this->waitForText('crm-notification-container', 'Selected financial type has been deleted.');
      return;
    }
    if ($option == 'new') {
      $this->click("link=Add Financial Type");
    }
    else {
      $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1]/div[text()='$financialType[oldname]']/../../td[7]/span/a[text()='Edit']");
    }
    $this->waitForElementPresent("name");
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
    if ($option == 'new') {
      $text = "Your Financial \"{$financialType['name']}\" Type has been created, along with a corresponding income account \"{$financialType['name']}\". That income account, along with standard financial accounts \"Accounts Receivable\", \"Banking Fees\" and \"Premiums\" have been linked to the financial type. You may edit or replace those relationships here.";
    }
    else {
      $text = "The financial type \"{$financialType['name']}\" has been updated.";
    }
    $this->checkCRMAlert($text);
  }

  /**
   * Give the specified permissions.
   * Note: this function logs in as 'admin' (logging out if necessary)
   * @param $permission
   */
  public function changePermissions($permission) {
    $this->webtestLogin('admin');
    $this->open("{$this->sboxPath}admin/people/permissions");
    $this->waitForElementPresent('edit-submit');
    foreach ((array) $permission as $perm) {
      $this->check($perm);
    }
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('The changes have been saved.'));
  }

  /**
   * @param $profileTitle
   * @param $profileFields
   */
  public function addProfile($profileTitle, $profileFields) {
    $this->openCiviPage('admin/uf/group', "reset=1");

    $this->clickLink('link=Add Profile', '_qf_Group_cancel-bottom');
    $this->type('title', $profileTitle);
    $this->clickLink('_qf_Group_next-bottom');

    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    foreach ($profileFields as $field) {
      $this->waitForElementPresent('field_name_0');
      $this->click("id=field_name_0");
      $this->select("id=field_name_0", "label=" . $field['type']);
      $this->waitForElementPresent('field_name_1');
      $this->click("id=field_name_1");
      $this->select("id=field_name_1", "label=" . $field['name']);
      $this->waitForElementPresent('label');
      $this->type("id=label", $field['label']);
      $this->click("id=_qf_Field_next_new-top");
      $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");
      //$this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '" . $field['name'] . "' has been saved to '" . $profileTitle . "'. You can add another profile field."));
    }
  }

  /**
   * @param string $name
   * @param $sku
   * @param $amount
   * @param $price
   * @param $cost
   * @param $financialType
   */
  public function addPremium($name, $sku, $amount, $price, $cost, $financialType) {
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

  /**
   * @param $label
   * @param $financialAccount
   */
  public function addPaymentInstrument($label, $financialAccount) {
    $this->openCiviPage('admin/options/payment_instrument', 'action=add&reset=1', "_qf_Options_next-bottom");
    $this->type("label", $label);
    $this->type("value", "value" . $label);
    $this->select("financial_account_id", "value=$financialAccount");
    $this->click("_qf_Options_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * Ensure we have a default mailbox set up for CiviMail.
   */
  public function setupDefaultMailbox() {
    $this->openCiviPage('admin/mailSettings', 'action=update&id=1&reset=1');
    // Check if it hasn't already been set up
    if (!$this->getSelectedValue('protocol')) {
      $this->type('name', 'Test Domain');
      $this->select('protocol', "IMAP");
      $this->type('server', 'localhost');
      $this->type('domain', 'example.com');
      $this->clickLink('_qf_MailSettings_next-top');
    }
  }

  /**
   * Determine the default time-out in milliseconds.
   *
   * @return string, timeout expressed in milliseconds
   */
  public function getTimeoutMsec() {
    // note: existing local versions of CiviSeleniumSettings may not declare $timeout, so use @
    $timeout = ($this->settings && @$this->settings->timeout) ? ($this->settings->timeout * 1000) : 30000;
    return (string) $timeout; // don't know why, but all our old code used a string
  }

  /**
   * CRM-12378
   * checks custom fields rendering / loading properly on the fly WRT entity passed as parameter
   *
   *
   * @param array $customSets
   *   Custom sets i.e entity wise sets want to be created and checked.
   *   e.g    $customSets = array(array('entity' => 'Contribution', 'subEntity' => 'Donation',
   * 'triggerElement' => $triggerElement))
   * array  $triggerElement:   the element which is responsible for custom group to load
   *
   * which uses the entity info as its selection value
   * @param array $pageUrl
   *   The url which on which the ajax custom group load takes place.
   * @param string $beforeTriggering
   * @return void
   */
  public function customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl, $beforeTriggering = NULL) {
    // FIXME: Testing a theory that these failures have something to do with permissions
    $this->webtestLogin('admin');

    //add the custom set
    $return = $this->addCustomGroupField($customSets);

    // FIXME: Hack to ensure caches are properly cleared
    if (TRUE) {
      $userName = $this->loggedInAs;
      $this->webtestLogout();
      $this->webtestLogin($userName);
    }

    $this->openCiviPage($pageUrl['url'], $pageUrl['args']);

    // FIXME: Try to find out what the heck is going on with these tests
    $this->waitForAjaxContent();
    $this->checkForErrorsOnPage();

    foreach ($return as $values) {
      foreach ($values as $entityType => $customData) {
        //initiate necessary variables
        list($entity, $entityData) = explode('_', $entityType);
        $elementType = CRM_Utils_Array::value('type', $customData['triggerElement'], 'select');
        $elementName = CRM_Utils_Array::value('name', $customData['triggerElement']);
        if (is_callable($beforeTriggering)) {
          call_user_func($beforeTriggering);
        }
        if ($elementType == 'select') {
          //reset the select box, so triggering of ajax only happens
          //WRT input of value in this function
          $this->select($elementName, "index=0");
        }
        if (!empty($entityData)) {
          if ($elementType == 'select') {
            $this->select($elementName, "label=regexp:{$entityData}");
          }
          elseif ($elementType == 'checkbox') {
            $val = explode(',', $entityData);
            foreach ($val as $v) {
              $checkId = $this->getAttribute("xpath=//label[text()='{$v}']/@for");
              $this->check($checkId);
            }
          }
          elseif ($elementType == 'select2') {
            $this->select2($elementName, $entityData);
          }
        }
        // FIXME: Try to find out what the heck is going on with these tests
        $this->waitForAjaxContent();
        $this->checkForErrorsOnPage();

        //checking for proper custom data which is loading through ajax
        $this->waitForElementPresent("css=.custom-group-{$customData['cgtitle']}");
        $this->assertElementPresent("xpath=//div[contains(@class, 'custom-group-{$customData['cgtitle']}')]/div[contains(@class, 'crm-accordion-body')]/table/tbody/tr/td[2]/input",
          "The on the fly custom group field is not present for entity : {$entity} => {$entityData}");
      }
    }
  }

  /**
   * @param $customSets
   *
   * @return array
   */
  public function addCustomGroupField($customSets) {
    $return = array();
    foreach ($customSets as $customSet) {
      $this->openCiviPage("admin/custom/group", "action=add&reset=1");

      //fill custom group title
      $customGroupTitle = "webtest_for_ajax_cd" . substr(sha1(rand()), 0, 4);
      $this->click("title");
      $this->type("title", $customGroupTitle);

      //custom group extends
      $this->click("extends_0");
      $this->select("extends_0", "value={$customSet['entity']}");
      if (!empty($customSet['subEntity'])) {
        $this->addSelection("extends_1", "label={$customSet['subEntity']}");
      }

      // Don't collapse
      $this->uncheck('collapse_display');

      // Save
      $this->click('_qf_Group_next-bottom');

      //Is custom group created?
      $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added.");

      $gid = $this->urlArg('gid');
      $this->waitForTextPresent("{$customGroupTitle} - New Field");

      $fieldLabel = "custom_field_for_{$customSet['entity']}_{$customSet['subEntity']}" . substr(sha1(rand()), 0, 4);
      $this->waitForElementPresent('label');
      $this->type('label', $fieldLabel);
      $this->click('_qf_Field_done-bottom');

      $this->waitForText('crm-notification-container', $fieldLabel);
      $this->waitForAjaxContent();

      $customGroupTitle = preg_replace('/\s/', '_', trim($customGroupTitle));
      $return[] = array(
        "{$customSet['entity']}_{$customSet['subEntity']}" => array(
          'cgtitle' => $customGroupTitle,
          'gid' => $gid,
          'triggerElement' => $customSet['triggerElement'],
        ),
      );

      // Go home for a sec to give time for caches to clear
      $this->openCiviPage('');
    }
    return $return;
  }

  /**
   * Type and select first occurance of autocomplete.
   * @param $fieldName
   * @param $label
   * @param bool $multiple
   * @param bool $xpath
   */
  public function select2($fieldName, $labels, $multiple = FALSE, $xpath = FALSE) {
    // In the case of chainSelect, wait for options to load
    $this->waitForElementNotPresent('css=select.loading');
    if ($multiple) {
      foreach ((array) $labels as $label) {
        $this->clickAt("//*[@id='$fieldName']/../div/ul/li");
        $this->keyDown("//*[@id='$fieldName']/../div/ul/li//input", " ");
        $this->type("//*[@id='$fieldName']/../div/ul/li//input", $label);
        $this->typeKeys("//*[@id='$fieldName']/../div/ul/li//input", $label);
        $this->waitForElementPresent("//*[@class='select2-result-label']");
        $this->clickAt("//*[contains(@class,'select2-result-selectable')]/div[contains(@class, 'select2-result-label')]");
      }
    }
    else {
      if ($xpath) {
        $this->clickAt($fieldName);
      }
      else {
        $this->clickAt("//*[@id='$fieldName']/../div/a");
      }
      $this->waitForElementPresent("//*[@id='select2-drop']/div/input");
      $this->keyDown("//*[@id='select2-drop']/div/input", " ");
      $this->type("//*[@id='select2-drop']/div/input", $labels);
      $this->typeKeys("//*[@id='select2-drop']/div/input", $labels);
      $this->waitForElementPresent("//*[@class='select2-result-label']");
      $this->clickAt("//*[contains(@class,'select2-result-selectable')]/div[contains(@class, 'select2-result-label')]");
    }
    // Wait a sec for select2 to update the original element
    sleep(1);
  }

  /**
   * Select multiple options.
   * @param $fieldid
   * @param $params
   * @param $isDate if multiple date is to be selected from datepicker
   */
  public function multiselect2($fieldid, $params, $isDate = FALSE) {
    // In the case of chainSelect, wait for options to load
    $this->waitForElementNotPresent('css=select.loading');
    foreach ($params as $value) {
      if ($isDate) {
        $this->clickAt("xpath=//*[@id='$fieldid']/../div/ul//li/input");
        $this->webtestFillDate($fieldid, $value, TRUE);
      }
      else {
        $this->clickAt("xpath=//*[@id='$fieldid']/../div/ul//li/input");
        $this->waitForElementPresent("xpath=//ul[@class='select2-results']");
        $this->clickAt("xpath=//ul[@class='select2-results']//li/div[text()='$value']");
        $this->assertElementContainsText("xpath=//*[@id='$fieldid']/preceding-sibling::div[1]/", $value);
      }
    }
    // Wait a sec for select2 to update the original element
    sleep(1);
  }

  /**
   * Check for unobtrusive status message as set by CRM.status
   * @param null $text
   */
  public function checkCRMStatus($text = NULL) {
    $this->waitForElementPresent("css=.crm-status-box-outer.status-success");
    if ($text) {
      $this->assertElementContainsText("css=.crm-status-box-outer.status-success", $text);
    }
  }

  /**
   * Check for obtrusive status message as set by CRM.alert
   * @param $text
   * @param string $type
   */
  public function checkCRMAlert($text, $type = 'success') {
    $this->waitForElementPresent("css=div.ui-notify-message.$type");
    $this->waitForText("css=div.ui-notify-message.$type", $text);
    // We got the message, now let's close it so the webtest doesn't get confused by lots of open alerts
    $this->click('css=.ui-notify-cross');
  }

  /**
   * Enable or disable Pop-ups via Display Preferences
   * @param bool $enabled
   */
  public function enableDisablePopups($enabled = TRUE) {
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1');
    $isChecked = $this->isChecked('ajaxPopupsEnabled');
    if (($isChecked && !$enabled) || (!$isChecked && $enabled)) {
      $this->click('ajaxPopupsEnabled');
    }
    if ($enabled) {
      $this->assertChecked('ajaxPopupsEnabled');
    }
    else {
      $this->assertNotChecked('ajaxPopupsEnabled');
    }
    $this->clickLink("_qf_Display_next-bottom");
  }

  /**
   * Attempt to get information about what went wrong if we encounter an error when loading a page.
   */
  public function checkForErrorsOnPage() {
    foreach (array('Access denied', 'Page not found') as $err) {
      if ($this->isElementPresent("xpath=//h1[contains(., '$err')]")) {
        $this->fail("'$err' encountered at " . $this->getLocation() . "\nwhile logged in as '{$this->loggedInAs}'");
      }
    }
    if ($this->isElementPresent("xpath=//span[text()='Sorry but we are not able to provide this at the moment.']")) {
      $msg = '"Fatal Error" encountered at ' . $this->getLocation();
      if ($this->isElementPresent('css=div.crm-section.crm-error-message')) {
        $msg .= "\nError Message: " . $this->getText('css=div.crm-section.crm-error-message');
      }
      $this->fail($msg);
    }
  }

  /**
   * @return array
   *   Contact record (per APIv3).
   */
  public function webtestGetLoggedInContact() {
    $result = $this->rest_civicrm_api('Contact', 'get', array(
      'id' => 'user_contact_id',
    ));
    $this->assertAPISuccess($result, 'Load logged-in contact');
    return CRM_Utils_Array::first($result['values']);
  }

  public function assertAPISuccess($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $errorMessage = empty($apiResult['error_message']) ? '' : " " . $apiResult['error_message'];

    if (!empty($apiResult['debug_information'])) {
      $errorMessage .= "\n " . print_r($apiResult['debug_information'], TRUE);
    }
    if (!empty($apiResult['trace'])) {
      $errorMessage .= "\n" . print_r($apiResult['trace'], TRUE);
    }
    $this->assertFalse(civicrm_error($apiResult), $prefix . $errorMessage);
    //$this->assertEquals(0, $apiResult['is_error']);
  }

}
