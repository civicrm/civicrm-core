<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
   * @var string
   *   crm-i css class
   */
  private $icon;

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
   *
   * @see Psr\Log\LogLevel
   */
  public function __construct($name, $message, $title, $level = \Psr\Log\LogLevel::WARNING, $icon = NULL) {
    $this->name = $name;
    $this->message = $message;
    $this->title = $title;
    // Handle non-integer severity levels.
    if (!CRM_Utils_Rule::integer($level)) {
      $level = CRM_Utils_Check::severityMap($level);
    }
    $this->level = $level;
    $this->icon = $icon;
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
   * Get level.
   *
   * @return string
   * @see Psr\Log\LogLevel
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * Alias for Level
   * @return string
   */
  public function getSeverity() {
    return $this->getLevel();
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
   * Convert to array.
   *
   * @return array
   */
  public function toArray() {
    $array = array(
      'name' => $this->name,
      'message' => $this->message,
      'title' => $this->title,
      'severity' => $this->level,
      'is_visible' => (int) $this->isVisible(),
      'icon' => $this->icon,
    );
    if (!empty($this->help)) {
      $array['help'] = $this->help;
    }
    return $array;
  }

  /**
   * Check if message is visible or has been hidden by the user.
   *
   * @return bool
   */
  public function isVisible() {
    return !CRM_Utils_Check::checkHushSnooze($this);
  }

}
