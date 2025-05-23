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
 * Class CRM_Core_Session.
 */
class CRM_Core_Session {

  /**
   * Cache of all the session names that we manage.
   * @var array
   */
  public static $_managedNames = NULL;

  /**
   * Key is used to allow the application to have multiple top
   * level scopes rather than a single scope. (avoids naming
   * conflicts). We also extend this idea further and have local
   * scopes within a global scope. Allows us to do cool things
   * like resetting a specific area of the session code while
   * keeping the rest intact
   *
   * @var string
   */
  protected $_key = 'CiviCRM';
  const USER_CONTEXT = 'userContext';

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
   * @var \CRM_Core_Session
   */
  static private $_singleton;

  /**
   * Constructor.
   *
   * The CMS takes care of initiating the php session handler session_start().
   *
   * All crm code should always use the session using
   * CRM_Core_Session. we prefix stuff to avoid collisions with the CMS and also
   * collisions with other crm modules!
   *
   * This constructor is invoked whenever any module requests an instance of
   * the session and one is not available.
   *
   * @return CRM_Core_Session
   */
  public function __construct() {
    $this->_session = NULL;
  }

  /**
   * Singleton function used to manage this object.
   *
   * @return CRM_Core_Session
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Session();
    }
    return self::$_singleton;
  }

  /**
   * Replace the session object with a fake session.
   */
  public static function useFakeSession() {
    self::$_singleton = new class() extends CRM_Core_Session {

      public function initialize($isRead = FALSE) {
        if ($isRead) {
          return;
        }

        if (!isset($this->_session)) {
          $this->_session = [];
        }

        if (!isset($this->_session[$this->_key]) || !is_array($this->_session[$this->_key])) {
          $this->_session[$this->_key] = [];
        }
      }

      public function isEmpty() {
        return empty($this->_session);
      }

    };
    self::$_singleton->_session = NULL;
    // This is not a revocable proposition. Should survive, even with things 'System.flush'.
    if (!defined('_CIVICRM_FAKE_SESSION')) {
      define('_CIVICRM_FAKE_SESSION', TRUE);
    }
    return self::$_singleton;
  }

  /**
   * Creates an array in the session.
   *
   * All variables now will be stored under this array.
   *
   * @param bool $isRead
   *   Is this a read operation, in this case, the session will not be touched.
   */
  public function initialize($isRead = FALSE) {
    // reset $this->_session in case if it is no longer a reference to $_SESSION;
    if (isset($_SESSION) && isset($this->_session) && $_SESSION !== $this->_session) {
      unset($this->_session);
    }
    // lets initialize the _session variable just before we need it
    // hopefully any bootstrapping code will actually load the session from the CMS
    if (!isset($this->_session)) {
      // CRM-9483
      if (!isset($_SESSION) && PHP_SAPI !== 'cli') {
        if ($isRead) {
          return;
        }
        CRM_Core_Config::singleton()->userSystem->sessionStart();
      }
      $this->_session =& $_SESSION;
    }

    if ($isRead) {
      return;
    }

    if (!isset($this->_session[$this->_key]) ||
      !is_array($this->_session[$this->_key])
    ) {
      $this->_session[$this->_key] = [];
    }
  }

  /**
   * Resets the session store.
   *
   * @param int|string $mode
   *   1: Default mode. Deletes the `CiviCRM` data from $_SESSION.
   *   2: More invasive version of that. (somehow)
   *   'keep_login': Less invasive. Preserve basic data (current user ID) from this session. Reset everything else.
   */
  public function reset($mode = 1) {
    if ($mode === 'keep_login') {
      if (!empty($this->_session[$this->_key])) {
        $this->_session[$this->_key] = CRM_Utils_Array::subset(
          $this->_session[$this->_key],
          ['ufID', 'userID', 'authx']
        );
      }
    }
    elseif ($mode != 1) {
      $this->initialize();

      // to make certain we clear it, first initialize it to empty
      $this->_session[$this->_key] = [];
      unset($this->_session[$this->_key]);
    }
    else {
      $this->_session[$this->_key] = [];
      unset($this->_session);
    }
  }

  /**
   * Creates a session local scope.
   *
   * @param string $prefix
   *   Local scope name.
   * @param bool $isRead
   *   Is this a read operation, in this case, the session will not be touched.
   */
  public function createScope($prefix, $isRead = FALSE) {
    $this->initialize($isRead);

    if ($isRead || empty($prefix)) {
      return;
    }

    if (empty($this->_session[$this->_key][$prefix])) {
      $this->_session[$this->_key][$prefix] = [];
    }
  }

  /**
   * Resets the session local scope.
   *
   * @param string $prefix
   *   Local scope name.
   */
  public function resetScope($prefix) {
    $this->initialize();

    if (empty($prefix)) {
      return;
    }

    if (array_key_exists($prefix, $this->_session[$this->_key])) {
      unset($this->_session[$this->_key][$prefix]);
    }
  }

  /**
   * Store a name-value pair in the session scope.
   *
   * @param string $name
   *   Name of the variable.
   * @param mixed $value
   *   Value of the variable. It is safe to use scalar values here, as well as
   *   arrays whose leaf nodes are scalar values. Instances of built-in classes
   *   like DateTime may be safe, although the retrieved objects will be copies
   *   of the ones saved here. Instances of custom classes (such as those
   *   defined in CiviCRM core or extension code) will probably not be rebuilt
   *   correctly on retrieval. Resources and other special variable types are
   *   not safe to use. References will be dereferenced.
   * @param string $prefix
   *   A string to prefix the keys in the session with.
   */
  public function set($name, $value = NULL, $prefix = NULL) {
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
   * Gets the value of the named variable in the session scope.
   *
   * This function takes a name and retrieves the value of this
   * variable from the session scope.
   *
   *
   * @param string $name
   *   name of the variable.
   * @param string $prefix
   *   adds another level of scope to the session.
   *
   * @return mixed
   */
  public function get($name, $prefix = NULL) {
    // create session scope
    $this->createScope($prefix, TRUE);

    if (empty($this->_session) || empty($this->_session[$this->_key])) {
      return NULL;
    }

    if (empty($prefix)) {
      $session =& $this->_session[$this->_key];
    }
    else {
      if (empty($this->_session[$this->_key][$prefix])) {
        return NULL;
      }
      $session =& $this->_session[$this->_key][$prefix];
    }

    return $session[$name] ?? NULL;
  }

  /**
   * Gets all the variables in the current session scope and stuffs them in an associate array.
   *
   * @param array $vars
   *   Associative array to store name/value pairs.
   * @param string $prefix
   *   Will be stripped from the key before putting it in the return.
   */
  public function getVars(&$vars, $prefix = '') {
    // create session scope
    $this->createScope($prefix, TRUE);

    if (empty($prefix)) {
      $values = &$this->_session[$this->_key];
    }
    else {
      $values = Civi::cache('session')->get("CiviCRM_{$prefix}");
    }

    if ($values) {
      foreach ($values as $name => $value) {
        $vars[$name] = $value;
      }
    }
  }

  /**
   * Set and check a timer.
   *
   * If it's expired, it will be set again.
   *
   * Good for showing a message to the user every hour or day (so not bugging them on every page)
   * Returns true-ish values if the timer is not set or expired, and false if the timer is still running
   * If you want to get more nuanced, you can check the type of the return to see if it's 'not set' or actually expired at a certain time
   *
   *
   * @param string $name
   *   name of the timer.
   * @param int $expire
   *   expiry time (in seconds).
   *
   * @return mixed
   */
  public function timer($name, $expire) {
    $ts = $this->get($name, 'timer');
    if (!$ts || $ts < time() - $expire) {
      $this->set($name, time(), 'timer');
      return $ts ?: 'not set';
    }
    return FALSE;
  }

  /**
   * Adds a userContext to the stack.
   *
   * @param string $userContext
   *   The url to return to when done.
   * @param bool $check
   *   Should we do a dupe checking with the top element.
   */
  public function pushUserContext($userContext, $check = TRUE) {
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
   * Replace the userContext of the stack with the passed one.
   *
   * @param string $userContext
   *   The url to return to when done.
   */
  public function replaceUserContext($userContext) {
    if (empty($userContext)) {
      return;
    }

    $this->createScope(self::USER_CONTEXT);

    array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
    array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
  }

  /**
   * Pops the top userContext stack.
   *
   * @return string
   *   the top of the userContext stack (also pops the top element)
   */
  public function popUserContext() {
    $this->createScope(self::USER_CONTEXT);

    return array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
  }

  /**
   * Reads the top userContext stack.
   *
   * @return string
   *   the top of the userContext stack
   */
  public function readUserContext() {
    $this->createScope(self::USER_CONTEXT);

    $config = CRM_Core_Config::singleton();
    $lastElement = count($this->_session[$this->_key][self::USER_CONTEXT]) - 1;
    return $lastElement >= 0 ? $this->_session[$this->_key][self::USER_CONTEXT][$lastElement] : $config->userFrameworkBaseURL;
  }

  /**
   * Dumps the session to the log.
   *
   * @param int $all
   */
  public function debug($all = 1) {
    $this->initialize();
    if ($all != 1) {
      CRM_Core_Error::debug('CRM Session', $this->_session);
    }
    else {
      CRM_Core_Error::debug('CRM Session', $this->_session[$this->_key]);
    }
  }

  /**
   * Fetches status messages.
   *
   * @param bool $reset
   *   Should we reset the status variable?.
   *
   * @return array
   *   the status message if any
   */
  public function getStatus($reset = FALSE) : array {
    $this->initialize();

    $status = [];
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
   * Stores an alert to be displayed to the user via crm-messages.
   *
   * @param string $text
   *   The status message.
   *
   * @param string $title
   *   The optional title of this message. For accessibility reasons,
   *   please terminate with a full stop/period.
   *
   * @param string $type
   *   The type of this message (printed as a css class). Possible options:
   *     - 'alert' (default)
   *     - 'info'
   *     - 'success'
   *     - 'error' (this message type by default will remain on the screen
   *               until the user dismisses it)
   *     - 'no-popup' (will display in the document like old-school)
   *
   * @param array $options
   *   Additional options. Possible values:
   *     - 'unique' (default: true) Check if this message was already set before adding
   *     - 'expires' how long to display this message before fadeout (in ms)
   *                 set to 0 for no expiration
   *                 defaults to 10 seconds for most messages, 5 if it has a title but no body,
   *                 or 0 for errors or messages containing links
   */
  public static function setStatus($text, $title = '', $type = 'alert', $options = []) {
    // make sure session is initialized, CRM-8120
    $session = self::singleton();
    $session->initialize();

    // Sanitize any HTML we're displaying. This helps prevent reflected XSS in error messages.
    $text = CRM_Utils_String::purifyHTML($text);
    $title = CRM_Utils_String::purifyHTML($title);

    // default options
    $options += ['unique' => TRUE];

    if (!isset(self::$_singleton->_session[self::$_singleton->_key]['status'])) {
      self::$_singleton->_session[self::$_singleton->_key]['status'] = [];
    }
    if ($text || $title) {
      if ($options['unique']) {
        foreach (self::$_singleton->_session[self::$_singleton->_key]['status'] as $msg) {
          if ($msg['text'] == $text && $msg['title'] == $title) {
            return;
          }
        }
      }
      unset($options['unique']);
      self::$_singleton->_session[self::$_singleton->_key]['status'][] = [
        'text' => $text,
        'title' => $title,
        'type' => $type,
        'options' => $options ?: NULL,
      ];
    }
  }

  /**
   * Register and retrieve session objects.
   *
   * @param string|array $names
   */
  public static function registerAndRetrieveSessionObjects($names) {
    if (!is_array($names)) {
      $names = [$names];
    }

    if (!self::$_managedNames) {
      self::$_managedNames = $names;
    }
    else {
      self::$_managedNames = array_merge(self::$_managedNames, $names);
    }

    CRM_Core_BAO_Cache::restoreSessionFromCache($names);
  }

  /**
   * Store session objects.
   *
   * @param bool $reset
   */
  public static function storeSessionObjects($reset = TRUE) {
    if (empty(self::$_managedNames)) {
      return;
    }

    self::$_managedNames = CRM_Utils_Array::crmArrayUnique(self::$_managedNames);

    CRM_Core_BAO_Cache::storeSessionToCache(self::$_managedNames, $reset);

    self::$_managedNames = NULL;
  }

  /**
   * Retrieve contact id of the logged in user.
   *
   * @return int|null
   *   contact ID of logged in user
   */
  public static function getLoggedInContactID(): ?int {
    $userId = CRM_Core_Session::singleton()->get('userID');
    return is_numeric($userId) ? (int) $userId : NULL;
  }

  /**
   * Get display name of the logged in user.
   *
   * @return string
   *
   * @throws CRM_Core_Exception
   */
  public function getLoggedInContactDisplayName(): string {
    $userContactID = CRM_Core_Session::getLoggedInContactID();
    if (!$userContactID) {
      return '';
    }
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userContactID, 'display_name') ?? '';
  }

  /**
   * Check if session is empty.
   *
   * if so we don't cache stuff that we can get away with, helps proxies like varnish.
   *
   * @return bool
   */
  public function isEmpty() {
    return empty($_SESSION);
  }

}
