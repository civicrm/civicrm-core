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

/**
 * Class CRM_Utils_Mail_CaseMail.
 */
class CRM_Utils_Mail_CaseMail {

  /**
   * A word that is used for cases by default (in email subject).
   *
   * @var string
   */
  private $caseLabel = 'case';

  /**
   * Default cases related email subject regexp patterns.
   *
   * All emails related to cases have case hash/id in the subject, e.g:
   * [case #ab12efg] Magic moment
   * [case #1234] Magic is here
   * This variable is defined in constructor.
   *
   * @var array|string[]
   */
  private $subjectPatterns = [];

  /**
   * Cases related email subject regexp patterns extended by hooks.
   *
   * @var array|string[]
   */
  private $subjectPatternsHooked = [];

  /**
   * CRM_Utils_Mail_CaseMail constructor.
   */
  public function __construct() {
    $this->subjectPatterns = [
      '/\[' . $this->caseLabel . ' #([0-9a-f]{7})\]/i',
      '/\[' . $this->caseLabel . ' #(\d+)\]/i',
    ];
  }

  /**
   * Checks if email is related to cases.
   *
   * @param string $subject
   *   Email subject.
   *
   * @return bool
   *   TRUE if email subject contains case ID or case hash, FALSE otherwise.
   */
  public function isCaseEmail ($subject) {
    $subject = trim($subject);
    $patterns = $this->getSubjectPatterns();
    $res = FALSE;

    for ($i = 0; !$res && $i < count($patterns); $i++) {
      $res = preg_match($patterns[$i], $subject) === 1;
    }

    return $res;
  }

  /**
   * Returns cases related email subject patterns.
   *
   * These patterns could be used to check if email is related to cases.
   *
   * @return array|string[]
   */
  public function getSubjectPatterns() {
    // Allow others to change patterns using hook.
    if (empty($this->subjectPatternsHooked)) {
      $patterns = $this->subjectPatterns;
      CRM_Utils_Hook::caseEmailSubjectPatterns($patterns);
      $this->subjectPatternsHooked = $patterns;
    }

    return !empty($this->subjectPatternsHooked)
      ? $this->subjectPatternsHooked
      : $this->subjectPatterns;
  }

  /**
   * Returns value of some class property.
   *
   * @param string $name
   *   Property name.
   *
   * @return mixed|null
   *   Property value or null if property does not exist.
   */
  public function get($name) {
    return $this->{$name} ?? NULL;
  }

  /**
   * Sets value of some class property.
   *
   * @param string $name
   *   Property name.
   * @param mixed $value
   *   New property value.
   */
  public function set($name, $value) {
    if (isset($this->{$name})) {
      $this->{$name} = $value;
    }
  }

}
