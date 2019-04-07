<?php /*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Temporarily change a global variable.
 *
 * @code
 * $globals = CRM_Utils_GlobalStack::singleton();
 * $globals->push(array(
 *   '_GET' => array(
 *     'q' => 'some-value
 *   ),
 * ));
 * ...do stuff...
 * $globals->pop();
 * @endcode
 *
 * Note: for purposes of this class, we'll refer to the array passed into
 * push() as a frame.
 */
class CRM_Utils_GlobalStack {
  /**
   * We don't have a container or dependency-injection, so use singleton instead
   *
   * @var object
   */
  private static $_singleton = NULL;

  private $backups = [];

  /**
   * Get or set the single instance of CRM_Utils_GlobalStack.
   *
   * @return CRM_Utils_GlobalStack
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_GlobalStack();
    }
    return self::$_singleton;
  }

  /**
   * @param $newFrame
   */
  public function push($newFrame) {
    $this->backups[] = $this->createBackup($newFrame);
    $this->applyFrame($newFrame);
  }

  public function pop() {
    $this->applyFrame(array_pop($this->backups));
  }

  /**
   * @param array $new
   *   The new, incoming frame.
   * @return array
   *   frame
   */
  public function createBackup($new) {
    $frame = [];
    foreach ($new as $globalKey => $values) {
      if (is_array($values)) {
        foreach ($values as $key => $value) {
          $frame[$globalKey][$key] = CRM_Utils_Array::value($key, $GLOBALS[$globalKey]);
        }
      }
      else {
        $frame[$globalKey] = CRM_Utils_Array::value($globalKey, $GLOBALS);
      }
    }
    return $frame;
  }

  /**
   * @param $newFrame
   */
  public function applyFrame($newFrame) {
    foreach ($newFrame as $globalKey => $values) {
      if (is_array($values)) {
        foreach ($values as $key => $value) {
          $GLOBALS[$globalKey][$key] = $value;
        }
      }
      else {
        $GLOBALS[$globalKey] = $values;
      }
    }
  }

}
