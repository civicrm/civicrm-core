<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                             |
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

/**
 * Base class for Export Formats
 * Create a subclass for a specific format.
 * @see http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+Specifications+-++Batches#CiviAccountsSpecifications-Batches-%C2%A0Overviewofimplementation
 */
class CRM_Financial_BAO_ExportFormat {

  /**
   * data which the individual export formats will output in the desired format.
   * @var array
   */
  protected $_exportParams;

  /**
   * smarty template.
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   * Class constructor.
   */
  public function __construct() {
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
    }
  }

  /**
   * Override to assemble the appropriate subset of financial data for the specific export format.
   * @param array $exportParams
   *
   * @return mixed
   */
  public function export($exportParams) {
    $this->_exportParams = $exportParams;
    return $exportParams;
  }

  /**
   * @param null $fileName
   */
  public function output($fileName = NULL) {
    switch ($this->getFileExtension()) {
      case 'csv':
        self::createActivityExport($this->_batchIds, $fileName);
        break;

      case 'iif':
        $tplFile = $this->getHookedTemplateFileName();
        $out = self::getTemplate()->fetch($tplFile);
        $fileName = $this->putFile($out);
        self::createActivityExport($this->_batchIds, $fileName);
        break;
    }
  }

  /**
   * @return string
   */
  public function getMimeType() {
    return 'text/plain';
  }

  /**
   * @return string
   */
  public function getFileExtension() {
    return 'txt';
  }

  /**
   * @return object
   */
  public static function &getTemplate() {
    return self::$_template;
  }

  /**
   * @param $var
   * @param null $value
   */
  public function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /*
   * This gets called for every item of data being compiled before being sent to the exporter for output.
   *
   * Depending on the output format might want to override this, e.g. for IIF tabs need to be escaped etc,
   * but for CSV it doesn't make sense because php has built in csv output functions.
   */
  /**
   * @param $s
   * @param string $type
   *
   * @return null
   */
  public static function format($s, $type = 'string') {
    if (!empty($s)) {
      return $s;
    }
    else {
      return NULL;
    }
  }

  public function initiateDownload() {
    $config = CRM_Core_Config::singleton();
    // zip files if more than one.
    if (count($this->_downloadFile) > 1) {
      $zip = $config->customFileUploadDir . 'Financial_Transactions_' . date('YmdHis') . '.zip';
      $result = $this->createZip($this->_downloadFile, $zip, TRUE);
      if ($result) {
        CRM_Utils_System::setHttpHeader('Content-Type', 'application/zip');
        CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($zip)));
        CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($zip));
        ob_clean();
        flush();
        readfile($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($zip)));
        unlink($zip); //delete the zip to avoid clutter.
        CRM_Utils_System::civiExit();
      }
    }
    else {
      CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
      CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($this->_downloadFile[0])));
      CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($this->_downloadFile[0]));
      ob_clean();
      flush();
      readfile($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($this->_downloadFile[0])));
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * @param $batchIds
   * @param string $fileName
   *
   * @throws CRM_Core_Exception
   */
  public static function createActivityExport($batchIds, $fileName) {
    $session = CRM_Core_Session::singleton();
    $values = array();
    $params = array('id' => $batchIds);
    CRM_Batch_BAO_Batch::retrieve($params, $values);
    $createdBy = CRM_Contact_BAO_Contact::displayName($values['created_id']);
    $modifiedBy = CRM_Contact_BAO_Contact::displayName($values['modified_id']);

    $values['payment_instrument_id'] = '';
    if (isset($values['payment_instrument_id'])) {
      $paymentInstrument = array_flip(CRM_Contribute_PseudoConstant::paymentInstrument('label'));
      $values['payment_instrument_id'] = array_search($values['payment_instrument_id'], $paymentInstrument);
    }
    $details = '<p>' . ts('Record:') . ' ' . $values['title'] . '</p><p>' . ts('Description:') . '</p><p>' . ts('Created By:') . " $createdBy" . '</p><p>' . ts('Created Date:') . ' ' . $values['created_date'] . '</p><p>' . ts('Last Modified By:') . ' ' . $modifiedBy . '</p><p>' . ts('Payment Method:') . ' ' . $values['payment_instrument_id'] . '</p>';
    $subject = '';
    if (!empty($values['total'])) {
      $subject .= ts('Total') . '[' . CRM_Utils_Money::format($values['total']) . '],';
    }
    if (!empty($values['item_count'])) {
      $subject .= ' ' . ts('Count') . '[' . $values['item_count'] . '],';
    }

    // create activity.
    $subject .= ' ' . ts('Batch') . '[' . $values['title'] . ']';
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $activityParams = array(
      'activity_type_id' => array_search('Export Accounting Batch', $activityTypes),
      'subject' => $subject,
      'status_id' => 2,
      'activity_date_time' => date('YmdHis'),
      'source_contact_id' => $session->get('userID'),
      'source_record_id' => $values['id'],
      'target_contact_id' => $session->get('userID'),
      'details' => $details,
      'attachFile_1' => array(
        'uri' => $fileName,
        'type' => 'text/csv',
        'location' => $fileName,
        'upload_date' => date('YmdHis'),
      ),
    );

    CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * @param array $files
   * @param null $destination
   * @param bool $overwrite
   *
   * @return bool
   */
  public function createZip($files = array(), $destination = NULL, $overwrite = FALSE) {
    // if the zip file already exists and overwrite is false, return false
    if (file_exists($destination) && !$overwrite) {
      return FALSE;
    }
    $valid_files = array();
    if (is_array($files)) {
      foreach ($files as $file) {
        // make sure the file exists
        if (file_exists($file)) {
          $validFiles[] = $file;
        }
      }
    }
    if (count($validFiles)) {
      $zip = new ZipArchive();
      if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== TRUE) {
        return FALSE;
      }
      foreach ($validFiles as $file) {
        $zip->addFile($file, CRM_Utils_File::cleanFileName(basename($file)));
      }
      $zip->close();
      return file_exists($destination);
    }
    else {
      return FALSE;
    }
  }

}
