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
 * Scan a folder for task files and enqueue them for execution.
 *
 * @code
 * touch /usr/lib/mydir/100-first.foo.php
 * touch /usr/lib/mydir/105-second.foo.php
 * touch /usr/lib/mydir/200-third.foo.php
 * touch /usr/lib/mydir/IRRELEVANT-TEXT.txt
 *
 * $queue = CRM_Queue_Service::singleton()->create(...);
 * $taskDir = new CRM_Queue_TaskDir('/usr/lib/mydir', $myQueue);
 * $taskDir
 *   ->addCallback('/\.foo\.php/', 'handle_foo_php_file')
 *   ->setTitle('Execute {file.name}')
 *   ->setData(array(...))
 *   ->fill($queue);
 *
 * function handle_foo_php_file(CRM_Queue_TaskContext $ctx, $file, $data) { ... }
 * @endcode
 */
class CRM_Queue_TaskDir {

  /**
   * @var string
   *   Ex: '/var/www/mysource/taskdir'
   */
  private $path;

  /**
   * @var string
   *   Ex: "Upgrade DB 4.7.0 ({file.name})"
   *
   * Variables: {file.name}, {file.parent}, {file.path}
   */
  private $title;

  /**
   * @var array
   *   Array(string $regex => mixed $callable).
   *   Note: $callable must be serializable. Global/static functions work best.
   */
  private $callbacks;

  /**
   * @var array
   *   List of values to pass through to the file.
   *   Note: Must be serializable.
   */
  private $data;

  /**
   * CRM_Queue_TaskDir constructor.
   * @param string $path
   */
  public function __construct($path = NULL) {
    $this->path = $path;
    $this->title = 'Execute file ({file.name})';
    $this->callbacks = array();
    $this->data = array();
  }

  /**
   * @param $regex
   * @param $callback
   * @return $this
   */
  public function addCallback($regex, $callback) {
    $this->callbacks[$regex] = $callback;
    return $this;
  }

  /**
   * Find any executable files in $path and enqueue them.
   *
   * @param \CRM_Queue_Queue $queue
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function fill($queue) {
    $path = $this->path;
    $files = scandir($path);
    sort($files);
    foreach ($files as $file) {
      $callback = $this->findCallback($file);
      if (!$callback) {
        continue;
      }

      $fullFilePath = $path . DIRECTORY_SEPARATOR . $file;
      if (!is_readable($fullFilePath)) {
        throw new CRM_Core_Exception("Cannot read task [$fullFilePath]");
      }

      $vars = array(
        '{file.name}' => $file,
        '{file.parent}' => $path,
        '{file.path}' => $fullFilePath,
      );
      $title = strtr($this->title, $vars);

      $task = new CRM_Queue_Task($callback, array($fullFilePath, $this->data), $title);
      $queue->createItem($task);
    }

    return $this;
  }

  /**
   * Determine which callback handles a given file.
   *
   * @param string $file
   * @return NULL|mixed
   *   A callable, or NULL if none.
   */
  public function findCallback($file) {
    foreach ($this->callbacks as $regex => $callback) {
      if (preg_match($regex, $file)) {
        return $callback;
      }
    }
    return NULL;
  }

  /**
   * @return string
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   *   Ex: "Upgrade DB 4.7.0 ({file.name})"
   *   Variables: {file.name}, {file.parent}, {file.path}
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param array $data
   * @return $this
   */
  public function setData($data) {
    $this->data = $data;
    return $this;
  }

}
