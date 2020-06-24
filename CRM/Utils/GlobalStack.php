<?php /*
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
 * Temporarily change a global variable.
 *
 * ```
 * $globals = CRM_Utils_GlobalStack::singleton();
 * $globals->push(array(
 *   '_GET' => array(
 *     'q' => 'some-value
 *   ),
 * ));
 * ...do stuff...
 * $globals->pop();
 * ```
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
          $frame[$globalKey][$key] = $GLOBALS[$globalKey][$key] ?? NULL;
        }
      }
      else {
        $frame[$globalKey] = $GLOBALS[$globalKey] ?? NULL;
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
