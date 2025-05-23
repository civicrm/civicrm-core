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
class CRM_Utils_Check_Message {
  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $message;

  /**
   * @var string
   */
  private $title;

  /**
   * @var int
   * @see Psr\Log\LogLevel
   */
  private $level;

  /**
   * @var string
   *   help text (to be presented separately from the message)
   */
  private $help;

  /**
   * @var array
   *   actions which can be performed with this message
   */
  private $actions = [];

  /**
   * @var string
   *   crm-i css class
   */
  private $icon;

  /**
   * @var bool
   *   Has this message been suppressed?
   */
  private $isVisible;

  /**
   * @var bool|string
   *   Date this message is hidden until
   */
  private $hiddenUntil;

  /**
   * Class constructor.
   *
   * @param string $name
   *   Symbolic name for the check.
   * @param string $message
   *   Printable message (short or long).
   * @param string $title
   *   Printable message (short).
   * @param string $level
   *   The severity of the message. Use PSR-3 log levels.
   * @param string $icon
   *
   * @see Psr\Log\LogLevel
   *
   */
  public function __construct($name, $message, $title, $level = \Psr\Log\LogLevel::WARNING, $icon = NULL) {
    $this->name = $name;
    $this->message = $message;
    $this->title = $title;
    $this->icon = $icon;
    $this->setLevel($level);
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get message.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get severity level number.
   *
   * @return int
   * @see Psr\Log\LogLevel
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * Get severity string.
   *
   * @return string
   * @see Psr\Log\LogLevel
   */
  public function getSeverity() {
    return CRM_Utils_Check::severityMap($this->level, TRUE);
  }

  /**
   * Set optional additional help text.
   *
   * @param string $help
   */
  public function addHelp($help) {
    $this->help = $help;
  }

  /**
   * Set optional additional actions text.
   *
   * @param string $title
   *   Text displayed on the status message as a link or button.
   * @param string|false $confirmation
   *   Optional confirmation message before performing action
   * @param string $type
   *   Link action type. One of: href|api3|api4
   * @param array $params
   *   Params to be passed to the api or CRM.url (depending on $type)
   *   Ex (api4): ['MyApiEntity', 'MyApiAction', [...apiParams...]]
   *   Ex (href): ['path' => 'civicrm/admin/foo', 'query' => 'reset=1']
   *   Ex (href): ['url' => 'https://example.com/more/info']
   * @param string $icon
   *   Fa-icon class for the button
   */
  public function addAction($title, $confirmation, $type, $params, $icon = NULL) {
    $this->actions[] = [
      'title' => $title,
      'confirm' => $confirmation,
      'type' => $type,
      'params' => $params,
      'icon' => $icon,
    ];
  }

  /**
   * Set severity level
   *
   * @param string|int $level
   * @throws \CRM_Core_Exception
   */
  public function setLevel($level) {
    // Convert level to integer
    if (!CRM_Utils_Rule::positiveInteger($level)) {
      $level = CRM_Utils_Check::severityMap($level);
    }
    else {
      // Validate numeric input - this will throw an exception if invalid
      CRM_Utils_Check::severityMap($level, TRUE);
    }
    $this->level = $level;
    // Clear internal caches
    unset($this->isVisible, $this->hiddenUntil);
  }

  /**
   * Convert to array.
   *
   * @return array
   */
  public function toArray() {
    $array = [
      'name' => $this->name,
      'message' => $this->message,
      'title' => $this->title,
      'severity' => $this->getSeverity(),
      'severity_id' => $this->level,
      'is_visible' => (int) $this->isVisible(),
      'icon' => $this->icon,
    ];
    if ($this->getHiddenUntil()) {
      $array['hidden_until'] = $this->getHiddenUntil();
    }
    if (!empty($this->help)) {
      $array['help'] = $this->help;
    }
    if (!empty($this->actions)) {
      $array['actions'] = $this->actions;
    }
    return $array;
  }

  /**
   * Get message visibility.
   *
   * @return bool
   */
  public function isVisible() {
    if (!isset($this->isVisible)) {
      $this->isVisible = !$this->checkStatusPreference();
    }
    return $this->isVisible;
  }

  /**
   * Get date hidden until.
   *
   * @return string
   */
  public function getHiddenUntil() {
    if (!isset($this->hiddenUntil)) {
      $this->checkStatusPreference();
    }
    return $this->hiddenUntil;
  }

  /**
   * Check if message has been hidden by the user.
   *
   * Also populates this->hiddenUntil property.
   *
   * @return bool
   *   TRUE means hidden, FALSE means visible.
   * @throws \CRM_Core_Exception
   */
  private function checkStatusPreference() {
    $this->hiddenUntil = FALSE;
    // Debug & info can't be hidden
    if ($this->level < 2) {
      return FALSE;
    }
    $where = [
      ['name', '=', $this->getName()],
      ['domain_id', '=', CRM_Core_Config::domainID()],
    ];
    // Check if there's a StatusPreference matching this name/domain.
    $pref = civicrm_api4('StatusPreference', 'get', ['checkPermissions' => FALSE, 'where' => $where])->first();
    if ($pref) {
      // If so, compare severity to StatusPreference->severity.
      if ($this->level <= $pref['ignore_severity']) {
        if (isset($pref['hush_until'])) {
          // Time-based hush.
          $this->hiddenUntil = $pref['hush_until'];
          $today = new DateTime();
          $snoozeDate = new DateTime($pref['hush_until']);
          return !($today > $snoozeDate);
        }
        else {
          // Hidden indefinitely.
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
