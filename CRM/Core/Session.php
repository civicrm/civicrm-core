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


require_once "PEAR.php";
class CRM_Core_Session {

  /**
   * Cache of all the session names that we manage
   */
  static $_managedNames = NULL;

  /**
   * key is used to allow the application to have multiple top
   * level scopes rather than a single scope. (avoids naming
   * conflicts). We also extend this idea further and have local
   * scopes within a global scope. Allows us to do cool things
   * like resetting a specific area of the session code while
   * keeping the rest intact
   *
   * @var string
   */
  protected $_key = 'CiviCRM';
  CONST USER_CONTEXT = 'userContext';

  /**
   * This is just a reference to the real session. Allows us to
   * debug this class a wee bit easier
   *
   * @var object
   */
  protected $_session = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * Since we are now a client / module of drupal, drupal takes care
   * of initiating the php session handler session_start ().
   *
   * When using CiviCRM standalone (i.e. w/o Drupal), we start the session
   * in index.php and then pass it off to here.
   *
   * All crm code should always use the session using
   * CRM_Core_Session. we prefix stuff to avoid collisions with drupal and also
   * collisions with other crm modules!!
   * This constructor is invoked whenever any module requests an instance of
   * the session and one is not available.
   *
   * @return void
   */
  function __construct() {
    $this->_session = null;
  }

  /**
   * singleton function used to manage this object
   *
   * @return CRM_CoreSession
   * @static
   */
  static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Session;
    }
    return self::$_singleton;
  }

  /**
   * Creates an array in the session. All variables now will be stored
   * under this array
   *
   * @param boolean isRead is this a read operation, in this case, the session will not be touched
   *
   * @access private
   *
   * @return void
   */
  function initialize($isRead = FALSE) {
    // lets initialize the _session variable just before we need it
    // hopefully any bootstrapping code will actually load the session from the CMS
    if (!isset($this->_session)) {
      // CRM-9483
      if (!isset($_SESSION) && PHP_SAPI !== 'cli') {
        if ($isRead) {
          return;
        }
        $config =& CRM_Core_Config::singleton();
        if ($config->userSystem->is_drupal && function_exists('drupal_session_start')) {
          drupal_session_start();
        }
        else {
          session_start();
        }
      }
      $this->_session =& $_SESSION;
    }

    if ($isRead) {
      return;
    }

    if (!isset($this->_session[$this->_key]) ||
      !is_array($this->_session[$this->_key])
    ) {
      $this->_session[$this->_key] = array();
    }
    return;
  }

  /**
   * Resets the session store
   *
   * @access public
   *
   * @return void
   */
  function reset($all = 1) {
    if ($all != 1) {
      $this->initialize();

      // to make certain we clear it, first initialize it to empty
      $this->_session[$this->_key] = array();
      unset($this->_session[$this->_key]);
    }
    else {
      $this->_session = array();
    }

    return;
  }

  /**
   * creates a session local scope
   *
   * @param string  prefix local scope name
   * @param boolean isRead is this a read operation, in this case, the session will not be touched
   *
   * @access public
   *
   * @return void
   */
  function createScope($prefix, $isRead = FALSE) {
    $this->initialize($isRead);

    if ($isRead || empty($prefix)) {
      return;
    }

    if (!CRM_Utils_Array::value($prefix, $this->_session[$this->_key])) {
      $this->_session[$this->_key][$prefix] = array();
    }
  }

  /**
   * resets the session local scope
   *
   * @param string local scope name
   * @access public
   *
   * @return void
   */
  function resetScope($prefix) {
    $this->initialize();

    if (empty($prefix)) {
      return;
    }

    if (array_key_exists($prefix, $this->_session[$this->_key])) {
      unset($this->_session[$this->_key][$prefix]);
    }
  }

  /**
   * Store the variable with the value in the session scope
   *
   * This function takes a name, value pair and stores this
   * in the session scope. Not sure what happens if we try
   * to store complex objects in the session. I suspect it
   * is supported but we need to verify this
   *
   * @access public
   *
   * @param  string $name    name  of the variable
   * @param  mixed  $value   value of the variable
   * @param  string $prefix  a string to prefix the keys in the session with
   *
   * @return void
   *
   */
  function set($name, $value = NULL, $prefix = NULL) {
    // create session scope
    $this->createScope($prefix);

    if (empty($prefix)) {
      $session = &$this->_session[$this->_key];
    }
    else {
      $session = &$this->_session[$this->_key][$prefix];
    }

    if (is_array($name)) {
      foreach ($name as $n => $v) {
        $session[$n] = $v;
      }
    }
    else {
      $session[$name] = $value;
    }
  }

  /**
   * Gets the value of the named variable in the session scope
   *
   * This function takes a name and retrieves the value of this
   * variable from the session scope.
   *
   * @access public
   *
   * @param  string name  : name  of the variable
   * @param  string prefix : adds another level of scope to the session
   *
   * @return mixed
   *
   */
  function get($name, $prefix = NULL) {
    // create session scope
    $this->createScope($prefix, TRUE);

    if (empty($this->_session) || empty($this->_session[$this->_key])) {
      return null;
    }

    if (empty($prefix)) {
      $session =& $this->_session[$this->_key];
    }
    else {
      if (empty($this->_session[$this->_key][$prefix])) {
        return null;
      }
      $session =& $this->_session[$this->_key][$prefix];
    }

    return CRM_Utils_Array::value($name, $session);
  }

  /**
   * Gets all the variables in the current session scope
   * and stuffs them in an associate array
   *
   * @access public
   *
   * @param  array  vars : associative array to store name/value pairs
   * @param  string  Strip prefix from the key before putting it in the return
   *
   * @return void
   *
   */
  function getVars(&$vars, $prefix = '') {
    // create session scope
    $this->createScope($prefix, TRUE);

    if (empty($prefix)) {
      $values = &$this->_session[$this->_key];
    }
    else {
      $values = CRM_Core_BAO_Cache::getItem('CiviCRM Session', "CiviCRM_{$prefix}");
    }

    if ($values) {
      foreach ($values as $name => $value) {
        $vars[$name] = $value;
      }
    }
  }

  /**
   * Set and check a timer. If it's expired, it will be set again.
   * Good for showing a message to the user every hour or day (so not bugging them on every page)
   * Returns true-ish values if the timer is not set or expired, and false if the timer is still running
   * If you want to get more nuanced, you can check the type of the return to see if it's 'not set' or actually expired at a certain time
   *
   * @access public
   *
   * @param  string name : name of the timer
   * @param  int expire  : expiry time (in seconds)
   *
   * @return mixed
   *
   */
  function timer($name, $expire) {
    $ts = $this->get($name, 'timer');
    if (!$ts || $ts < time() - $expire) {
      $this->set($name, time(), 'timer');
      return $ts ? $ts : 'not set';
    }
    return false;
  }

  /**
   * adds a userContext to the stack
   *
   * @param string  $userContext the url to return to when done
   * @param boolean $check       should we do a dupe checking with the top element
   *
   * @return void
   *
   * @access public
   *
   */
  function pushUserContext($userContext, $check = TRUE) {
    if (empty($userContext)) {
      return;
    }

    $this->createScope(self::USER_CONTEXT);

    // hack, reset if too big
    if (count($this->_session[$this->_key][self::USER_CONTEXT]) > 10) {
      $this->resetScope(self::USER_CONTEXT);
      $this->createScope(self::USER_CONTEXT);
    }

    $topUC = array_pop($this->_session[$this->_key][self::USER_CONTEXT]);

    // see if there is a match between the new UC and the top one. the match needs to be
    // fuzzy since we use the referer at times
    // if close enough, lets just replace the top with the new one
    if ($check && $topUC && CRM_Utils_String::match($topUC, $userContext)) {
      array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
    }
    else {
      if ($topUC) {
        array_push($this->_session[$this->_key][self::USER_CONTEXT], $topUC);
      }
      array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
    }
  }

  /**
   * replace the userContext of the stack with the passed one
   *
   * @param string the url to return to when done
   *
   * @return void
   *
   * @access public
   *
   */
  function replaceUserContext($userContext) {
    if (empty($userContext)) {
      return;
    }

    $this->createScope(self::USER_CONTEXT);

    array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
    array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
  }

  /**
   * pops the top userContext stack
   *
   * @param void
   *
   * @return the top of the userContext stack (also pops the top element)
   *
   */
  function popUserContext() {
    $this->createScope(self::USER_CONTEXT);

    return array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
  }

  /**
   * reads the top userContext stack
   *
   * @param void
   *
   * @return the top of the userContext stack
   *
   */
  function readUserContext() {
    $this->createScope(self::USER_CONTEXT);

    $config = CRM_Core_Config::singleton();
    $lastElement = count($this->_session[$this->_key][self::USER_CONTEXT]) - 1;
    return $lastElement >= 0 ? $this->_session[$this->_key][self::USER_CONTEXT][$lastElement] : $config->userFrameworkBaseURL;
  }

  /**
   * dumps the session to the log
   */
  function debug($all = 1) {
    $this->initialize();
    if ($all != 1) {
      CRM_Core_Error::debug('CRM Session', $this->_session);
    }
    else {
      CRM_Core_Error::debug('CRM Session', $this->_session[$this->_key]);
    }
  }

  /**
   * Fetches status messages
   *
   * @param $reset boolean should we reset the status variable?
   *
   * @return string        the status message if any
   */
  function getStatus($reset = FALSE) {
    $this->initialize();

    $status = NULL;
    if (array_key_exists('status', $this->_session[$this->_key])) {
      $status = $this->_session[$this->_key]['status'];
    }
    if ($reset) {
      $this->_session[$this->_key]['status'] = NULL;
      unset($this->_session[$this->_key]['status']);
    }
    return $status;
  }

  /**
   * Stores an alert to be displayed to the user via crm-messages
   *
   * @param $text string
   *   The status message
   *
   * @param $title string
   *   The optional title of this message
   *
   * @param $type string
   *   The type of this message (printed as a css class). Possible options:
   *     - 'alert' (default)
   *     - 'info'
   *     - 'success'
   *     - 'error' (this message type by default will remain on the screen
   *               until the user dismisses it)
   *     - 'no-popup' (will display in the document like old-school)
   *
   * @param $options array
   *   Additional options. Possible values:
   *     - 'unique' (default: true) Check if this message was already set before adding
   *     - 'expires' how long to display this message before fadeout (in ms)
   *                 set to 0 for no expiration
   *                 defaults to 10 seconds for most messages, 5 if it has a title but no body,
   *                 or 0 for errors or messages containing links
   *
   * @static
   *
   * @return void
   */
  static function setStatus($text, $title = '', $type = 'alert', $options = array()) {
    // make sure session is initialized, CRM-8120
    $session = self::singleton();
    $session->initialize();

    // default options
    $options += array('unique' => TRUE);

    if (!isset(self::$_singleton->_session[self::$_singleton->_key]['status'])) {
      self::$_singleton->_session[self::$_singleton->_key]['status'] = array();
    }
    if ($text) {
      if ($options['unique']) {
        foreach (self::$_singleton->_session[self::$_singleton->_key]['status'] as $msg) {
          if ($msg['text'] == $text && $msg['title'] == $title) {
            return;
          }
        }
      }
      unset($options['unique']);
      self::$_singleton->_session[self::$_singleton->_key]['status'][] = array(
        'text' => $text,
        'title' => $title,
        'type' => $type,
        'options' => $options ? json_encode($options) : NULL,
      );
    }
  }

  static function registerAndRetrieveSessionObjects($names) {
    if (!is_array($names)) {
      $names = array($names);
    }

    if (!self::$_managedNames) {
      self::$_managedNames = $names;
    }
    else {
      self::$_managedNames = array_merge(self::$_managedNames, $names);
    }

    CRM_Core_BAO_Cache::restoreSessionFromCache($names);
  }

  static function storeSessionObjects($reset = TRUE) {
    if (empty(self::$_managedNames)) {
      return;
    }

    self::$_managedNames = CRM_Utils_Array::crmArrayUnique(self::$_managedNames);

    CRM_Core_BAO_Cache::storeSessionToCache(self::$_managedNames, $reset);

    self::$_managedNames = NULL;
  }

  function isEmpty() {
    // check if session is empty, if so we dont cache
    // stuff that we can get away with
    // helps proxies like varnish
    return empty($_SESSION) ? TRUE : FALSE;
  }
}
