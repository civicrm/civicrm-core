<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class CRM_Core_Extensions_Extension {

  CONST STATUS_INSTALLED = 'installed';
  CONST STATUS_MISSING = 'missing';
  CONST STATUS_LOCAL = 'local';
  CONST STATUS_REMOTE = 'remote';

  public $type = NULL;

  public $path = NULL;

  public $upgradable = FALSE;

  public $upgradeVersion = NULL;

  function __construct($key, $type = NULL, $name = NULL, $label = NULL, $file = NULL, $is_active = 1) {
    $this->key       = $key;
    $this->type      = $type;
    $this->name      = $name;
    $this->label     = $label;
    $this->file      = $file;
    $this->is_active = $is_active;

    $config = CRM_Core_Config::singleton();
    $this->path = $config->extensionsDir . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR;
  }

  /**
   * Determine whether this extension supports special upgrade logic.
   * If not, then the implication is that an "upgrade" would
   * mean "uninstall and reinstall".
   *
   * @return bool
   */
  public function isUpgradeable() {
    $upgradeableTypes = array('module'); // FIXME
    return in_array($this->type, $upgradeableTypes);
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function setUpgradable() {
    $this->upgradable = TRUE;
  }

  public function setUpgradeVersion($version) {
    $this->upgradeVersion = $version;
  }

  public function setInstalled() {
    $this->setStatus(self::STATUS_INSTALLED);
  }

  public function setMissing() {
    $this->setStatus(self::STATUS_MISSING);
  }

  public function setLocal() {
    $this->setStatus(self::STATUS_LOCAL);
  }

  public function setRemote() {
    $this->setStatus(self::STATUS_REMOTE);
  }

  public function setStatus($status) {
    $labels = array(self::STATUS_INSTALLED => ts('Installed'),
      self::STATUS_MISSING => ts('Missing'),
      self::STATUS_LOCAL => ts('Local only'),
      self::STATUS_REMOTE => ts('Available'),
    );
    $this->status = $status;
    $this->statusLabel = $labels[$status];
  }

  public function xmlObjToArray($obj) {
    $arr = array();
    if (is_object($obj)) {
      $obj = get_object_vars($obj);
    }
    if (is_array($obj)) {
      foreach ($obj as $i => $v) {
        if (is_object($v) || is_array($v)) {
          $v = $this->xmlObjToArray($v);
        }
        if (empty($v)) {
          $arr[$i] = NULL;
        }
        else {
          $arr[$i] = $v;
        }
      }
    }
    return $arr;
  }

  /**
   * Determine whether the XML info file exists
   *
   * @return  bool
   */  
  public function hasXMLInfo() {
    return file_exists($this->path . 'info.xml');
  }

  public function readXMLInfo($xml = FALSE) {
    if ($xml === FALSE) {
      $info = $this->_parseXMLFile($this->path . 'info.xml');
    }
    else {
      $info = $this->_parseXMLString($xml);
    }

    if ($info == FALSE) {
      $this->name = 'Invalid extension';
    }
    else {

      $this->type  = (string) $info->attributes()->type;
      $this->file  = (string) $info->file;
      $this->label = (string) $info->name;

      // Convert first level variables to CRM_Core_Extension properties
      // and deeper into arrays. An exception for URLS section, since
      // we want them in special format.
      foreach ($info as $attr => $val) {
        if (count($val->children()) == 0) {
          $this->$attr = (string) $val;
        }
        elseif ($attr === 'urls') {
          $this->urls = array();
          foreach ($val->url as $url) {
            $urlAttr = (string) $url->attributes()->desc;
            $this->urls[$urlAttr] = (string) $url;
          }
          ksort($this->urls);
        }
        else {
          $this->$attr = $this->xmlObjToArray($val);
        }
      }
    }
  }

  private function _parseXMLString($string) {
    return simplexml_load_string($string, 'SimpleXMLElement');
  }

  private function _parseXMLFile($file) {
    if (file_exists($file)) {
      return simplexml_load_file($file,
        'SimpleXMLElement', LIBXML_NOCDATA
      );
    }
    else {
      CRM_Core_Error::fatal('Extension file ' . $file . ' does not exist. The download may have failed.');
    }
    return array();
  }

  /**
   * Attempt to download and install an extension.
   *
   * @return boolean Whether all tasks completed successfully.
   *
   * It's possible to install extensions locally, so this function
   * permits installation if extension is installed locally and
   * fails to download.
   */
  public function install() {
    if ($this->status != self::STATUS_LOCAL) {
      if ($this->download()) {
        if (!$this->installFiles()) {
          return FALSE;
        }
      } else {
        // CRM-10322 If download fails but files exist anyway, continue with install
        // using local version. If no local version exists, then it's OK
        // to raise a fatal error.
        $this->readXMLInfo();
      }
    }
    if ($this->_registerExtensionByType()) {
      $this->_createExtensionEntry();
      if ($this->type == 'payment') {
        $this->_runPaymentHook('install');
      }
    }
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Uninstall an extension.
   *
   * @param bool $removeFiles whether to remove PHP source tree for the extension
   * @return boolean Whether all tasks completed successfully.
   */
  public function uninstall($removeFiles = TRUE) {
    if ($this->type == 'payment' && $this->status != 'missing') {
      $this->_runPaymentHook('uninstall');
    }
    if ($this->_removeExtensionByType()) {
      if ($this->_removeExtensionEntry()) {
        // remove files *after* invoking hook_civicrm_uninstall
        if ((!$removeFiles) || $this->removeFiles()) {
          //MOVE AND TEST// CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
          return TRUE;
        }
      }
    }
   CRM_Core_Invoke::rebuildMenuAndCaches(TRUE); //MOVE AND TEST//
  }

  public function upgrade() {
    $this->download();
    $this->removeFiles();
    $this->installFiles();
    //TODO// $this->_updateExtensionEntry();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Remove extension files.
   *
   * @return boolean Whether the extension directory was removed.
   */
  public function removeFiles() {
    $config = CRM_Core_Config::singleton();
    if (CRM_Utils_File::cleanDir($config->extensionsDir . DIRECTORY_SEPARATOR . $this->key, TRUE)) {
      return TRUE;
    }
  }

  public function installFiles() {
    $config = CRM_Core_Config::singleton();

    $zip = new ZipArchive;
    $res = $zip->open($this->tmpFile);
    if ($res === TRUE) {
      $zipSubDir = CRM_Utils_Zip::guessBasedir($zip, $this->key);
      if ($zipSubDir === FALSE) {
        CRM_Core_Session::setStatus(ts('Unable to extract the extension: bad directory structure') . '<br/>');
        return FALSE;
      }
      $path = $config->extensionsDir . DIRECTORY_SEPARATOR . 'tmp';
      $extractedZipPath = $path . DIRECTORY_SEPARATOR . $zipSubDir;
      if (is_dir($extractedZipPath)) {
        if (!CRM_Utils_File::cleanDir($extractedZipPath, TRUE, FALSE)) {
          CRM_Core_Session::setStatus(ts('Unable to extract the extension: %1 cannot be cleared', array(1 => $extractedZipPath)) . '<br/>');
          return FALSE;
        }
      }
      if (!$zip->extractTo($path)) {
        CRM_Core_Session::setStatus(ts('Unable to extract the extension to %1.', array(1 => $path)) . '<br/>');
        return FALSE;
      }
      $zip->close();
    }
    else {
      CRM_Core_Session::setStatus('Unable to extract the extension.');
      return FALSE;
    }

    $filename = $extractedZipPath . DIRECTORY_SEPARATOR . 'info.xml';
    if (!is_readable($filename)) {
      CRM_Core_Session::setStatus(ts('Failed reading data from %1 during installation', array(1 => $filename)) . '<br/>');
      return FALSE;
    }
    $newxml = file_get_contents($filename);

    if (empty($newxml)) {
      CRM_Core_Session::setStatus(ts('Failed reading data from %1 during installation', array(1 => $filename)) . '<br/>');
      return FALSE;
    }

    $check = new CRM_Core_Extensions_Extension($this->key . ".newversion");
    $check->readXMLInfo($newxml);
    if ($check->version != $this->version) {
      CRM_Core_Error::fatal('Cannot install - there are differences between extdir XML file and archive XML file!');
    }

    // Why is this a copy instead of a move?
    CRM_Utils_File::copyDir($extractedZipPath,
      $config->extensionsDir . DIRECTORY_SEPARATOR . $this->key
    );

    if (!CRM_Utils_File::cleanDir($extractedZipPath, TRUE, FALSE)) {
      CRM_Core_Session::setStatus(ts('Failed to clean temp dir: %1', array(1 => $extractedZipPath)) . '<br/>');
    }
    
    return TRUE;
  }

  /**
   * Download the remote zipfile.
   *
   * @return boolean Whether the download was successful.
   */
  public function download() {
    require_once 'CA/Config/Curl.php';
    $config = CRM_Core_Config::singleton();

    $path = $config->extensionsDir . DIRECTORY_SEPARATOR . 'tmp';
    $filename = $path . DIRECTORY_SEPARATOR . $this->key . '.zip';

    if (!$this->downloadUrl) {
      CRM_Core_Error::fatal('Cannot install this extension - downloadUrl is not set!');
    }

    // Download extension zip file ...
    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal('Cannot install this extension - curl is not installed!');
    }
    if (preg_match('/^https:/', $this->downloadUrl) && !CA_Config_Curl::singleton()->isEnableSSL()) {
      CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
    }

    //setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->downloadUrl);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    if (preg_match('/^https:/', $this->downloadUrl)) {
      curl_setopt_array($ch, CA_Config_Curl::singleton()->toCurlOptions());
    }

    //follow redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $fp = fopen($filename, "w");
    if (! $fp) {
      CRM_Core_Session::setStatus(ts('Unable to write to %1.<br />Is the location writable?', array(1 => $filename)));
      return;
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);

    curl_exec($ch);
    if (curl_errno($ch)) {
      CRM_Core_Error::debug(curl_error($ch));
      CRM_Core_Error::debug(curl_errno($ch)); exit( );
      CRM_Core_Session::setStatus(ts('Unable to download extension from %1. Error Message: %2',
          array(1 => $this->downloadUrl, 2 => curl_error($ch))));
      return;
    }
    else {
      curl_close($ch);
    }

    fclose($fp);

    $this->tmpFile = $filename;
    return TRUE;
  }

  public function enable() {
    $this->_setActiveByType(1);
    CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Extension', $this->id, 'is_active', 1);
    if ($this->type == 'payment' && $this->status != 'missing') {
      $this->_runPaymentHook('enable');
    }
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  public function disable() {    
    if ($this->type == 'payment' && $this->status != 'missing') {
      $this->_runPaymentHook('disable');
    }
    $this->_setActiveByType(0);
    CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Extension', $this->id, 'is_active', 0);
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  private function _setActiveByType($state) {
    $hcName = "CRM_Core_Extensions_" . ucwords($this->type);
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $hcName) . '.php');
    $ext = new $hcName($this);
    $state ? $ext->enable() : $ext->disable();
  }

  private function _registerExtensionByType() {
    $hcName = "CRM_Core_Extensions_" . ucwords($this->type);
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $hcName) . '.php');
    $ext = new $hcName($this);
    $ext->install();
    // @TODO check return of $dao->save() in $ext->install()
    return TRUE;
  }

  private function _removeExtensionByType() {
    $hcName = "CRM_Core_Extensions_" . ucwords($this->type);
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $hcName) . '.php');
    $ext = new $hcName($this);
    return $ext->uninstall();
  }

  private function _removeExtensionEntry() {
    if (CRM_Core_BAO_Extension::del($this->id)) {
      CRM_Core_Session::setStatus(ts('Selected option value has been deleted.'));
      return TRUE;
    }
  }

  /**
   * Function to run hooks in the payment processor class
   * Load requested payment processor and call the method specified.
   *
   * @param string $method - the method to call in the payment processor class
   * @private
   */
  private function _runPaymentHook($method) {
    // Not concerned about performance at this stage, as these are seldomly performed tasks
    // (payment processor enable/disable/install/uninstall). May wish to implement some
    // kind of registry/caching system if more hooks are added.
    if (!isset($this->id) || empty($this->id)) {
      $this->id = 0;
    }

    $ext = new CRM_Core_Extensions();

    $paymentClass = $ext->keyToClass($this->key, 'payment');
    require_once $ext->classToPath($paymentClass);

    // See if we have any instances of this PP defined ..
    if ($this->id && $processor_id = CRM_Core_DAO::singleValueQuery("
                SELECT pp.id
                  FROM civicrm_extension ext
            INNER JOIN civicrm_payment_processor pp
                    ON pp.payment_processor_type = ext.name
                 WHERE ext.type = 'payment'
                   AND ext.id = %1

        ",
        array(
          1 => array($this->id, 'Integer'),
        )
      )) {
      // If so, load params in the usual way ..
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($processor_id, NULL);
    }
    else {
      // Otherwise, do the best we can to construct some ..
      $dao = CRM_Core_DAO::executeQuery("
                    SELECT ppt.*
                      FROM civicrm_extension ext
                INNER JOIN civicrm_payment_processor_type ppt
                        ON ppt.name = ext.name
                     WHERE ext.name = %1
                       AND ext.type = 'payment'
            ",
        array(
          1 => array($this->name, 'String'),
        )
      );
      if ($dao->fetch()) $paymentProcessor = array(
        'id' => -1,
        'name' => $dao->title,
        'payment_processor_type' => $dao->name,
        'user_name' => 'nothing',
        'password' => 'nothing',
        'signature' => '',
        'url_site' => $dao->url_site_default,
        'url_api' => $dao->url_api_default,
        'url_recur' => $dao->url_recur_default,
        'url_button' => $dao->url_button_default,
        'subject' => '',
        'class_name' => $dao->class_name,
        'is_recur' => $dao->is_recur,
        'billing_mode' => $dao->billing_mode,
        'payment_type' => $dao->payment_type,
      );
      else CRM_Core_Error::fatal("Unable to find payment processor in " . __CLASS__ . '::' . __METHOD__);
    }

    // In the case of uninstall, check for instances of PP first.
    // Don't run hook if any are found.
    if ($method == 'uninstall' && $paymentProcessor['id'] > 0) {
      return;
    }

    switch ($method) {
      case 'install':
      case 'uninstall':
      case 'enable':
      case 'disable':

        // Instantiate PP
        eval('$processorInstance = ' . $paymentClass . '::singleton( null, $paymentProcessor );');

        // Does PP implement this method, and can we call it?
        if (method_exists($processorInstance, $method) && is_callable(array(
          $processorInstance, $method))) {
          // If so, call it ...
          $processorInstance->$method();
        }
        break;

      default:
        CRM_Core_Session::setStatus("Unrecognized payment hook ($method) in " . __CLASS__ . '::' . __METHOD__);
    }
  }

  private function _createExtensionEntry() {
    $dao = new CRM_Core_DAO_Extension();
    $dao->label = $this->label;
    $dao->name = $this->name;
    $dao->full_name = $this->key;
    $dao->type = $this->type;
    $dao->file = $this->file;
    $dao->is_active = 1;
    if ($dao->insert()) {
      $this->id = $dao->id;
      return $this->id;
    }
  }

}
