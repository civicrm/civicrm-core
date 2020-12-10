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
 * Capture the output from the console, copy it to a file, and pass it on.
 *
 * ```
 * $tee = CRM_Utils_ConsoleTee::create()->start();
 * echo "hello world";
 * $tee->stop();
 * assertEquals("hello world", file_get_contents($tee->getFileName()));
 * ```
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
    ob_start([$this, 'onOutput']);
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
