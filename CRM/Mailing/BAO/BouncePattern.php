<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Mailing_BAO_BouncePattern extends CRM_Mailing_DAO_BouncePattern {

  /**
   * Pseudo-constant pattern array
   */
  static $_patterns = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Build the static pattern array
   *
   * @return void
   * @access public
   * @static
   */
  public static function buildPatterns() {
    self::$_patterns = array();
    $bp = new CRM_Mailing_BAO_BouncePattern();
    $bp->find();

    while ($bp->fetch()) {
      self::$_patterns[$bp->bounce_type_id][] = $bp->pattern;
    }

    foreach (self::$_patterns as $type => $patterns) {
      if (count($patterns) == 1) {
        self::$_patterns[$type] = '{(' . $patterns[0] . ')}im';
      }
      else {
        self::$_patterns[$type] = '{(' . implode(')|(', $patterns) . ')}im';
      }
    }
  }

  /**
   * Try to match the string to a bounce type.
   *
   * @param string $message       The message to be matched
   *
   * @return array                Tuple (bounce_type, bounce_reason)
   * @access public
   * @static
   */
  public static function &match(&$message) {
    // clean up $message and replace all white space by a single space, CRM-4767
    $message = preg_replace('/\s+/', ' ', $message);

    if (self::$_patterns == NULL) {
      self::buildPatterns();
    }

    foreach (self::$_patterns as $type => $re) {
      if (preg_match($re, $message, $matches)) {
        $bounce = array(
          'bounce_type_id' => $type,
          'bounce_reason' => $message,
        );
        return $bounce;
      }
    }

    $bounce = array(
      'bounce_type_id' => NULL,
      'bounce_reason' => $message,
    );

    return $bounce;
  }
}

