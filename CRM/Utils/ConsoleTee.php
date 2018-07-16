<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Capture the output from the console, copy it to a file, and pass it on.
 *
 * @code
 * $tee = CRM_Utils_ConsoleTee::create()->start();
 * echo "hello world";
 * $tee->stop();
 * assertEquals("hello world", file_get_contents($tee->getFileName()));
 * @endCode
 *
 * Loosely speaking, it serves a similar purpose to Unix `tee`.
 *
 * @link https://en.wikipedia.org/wiki/Tee_(command)
 */
class CRM_Utils_ConsoleTee {

  /**
   * Capture console output and copy to a temp file.
   *
   * @param string $prefix
   * @return CRM_Utils_ConsoleTee
   */
  public static function create($prefix = 'ConsoleTee-') {
    return new static(tempnam(sys_get_temp_dir(), $prefix));
  }

  /**
   * @var string
   */
  protected $fileName;

  /**
   * @var resource
   */
  protected $fh;

  /**
   * CRM_Utils_ConsoleTee constructor.
   *
   * @param string $fileName
   *   The full path of the file to write to.
   */
  public function __construct($fileName) {
    $this->fileName = $fileName;
  }

  /**
   * Start capturing console output and copying it to a file.
   *
   * @param string $mode
   *   The file output mode, e.g. `w` or `w+`.
   * @return CRM_Utils_ConsoleTee
   * @see fopen
   */
  public function start($mode = 'w') {
    $this->fh = fopen($this->fileName, $mode);
    ob_start(array($this, 'onOutput'));
    return $this;
  }

  /**
   * Process a snippet of data from the output buffer.
   *
   * @param string $buf
   * @return bool
   * @see ob_start
   */
  public function onOutput($buf) {
    fwrite($this->fh, $buf);
    return FALSE;
  }

  /**
   * Stop capturing console output.
   *
   * @return CRM_Utils_ConsoleTee
   */
  public function stop() {
    ob_end_flush();
    fclose($this->fh);
    return $this;
  }

  /**
   * @return string
   */
  public function getFileName() {
    return $this->fileName;
  }

}
