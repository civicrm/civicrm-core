<?php
/*
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
 * Redefine the upload action.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 */
class CRM_Core_QuickForm_Action_Upload extends CRM_Core_QuickForm_Action {

  /**
   * The array of uploaded file names.
   * @var array
   */
  protected $_uploadNames;

  /**
   * The directory to store the uploaded files.
   * @var string
   */
  protected $_uploadDir;

  /**
   * Class constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   * @param string $uploadDir
   *   Directory to store the uploaded files.
   * @param array $uploadNames
   *   Element names of the various uploadable files.
   *
   * @return \CRM_Core_QuickForm_Action_Upload
   */
  public function __construct(&$stateMachine, $uploadDir, $uploadNames) {
    parent::__construct($stateMachine);

    $this->_uploadDir = $uploadDir;
    $this->_uploadNames = $uploadNames;
  }

  /**
   * Upload and move the file if valid to the uploaded directory.
   *
   * @param CRM_Core_Form $page
   *   The CRM_Core_Form object.
   * @param object $data
   *   The QFC data container.
   * @param string $pageName
   *   The name of the page which index the data container with.
   * @param string $uploadName
   *   The name of the uploaded file.
   */
  public function upload(&$page, &$data, $pageName, $uploadName) {
    // make sure uploadName exists in the QF array
    // else we skip, CRM-3427
    if (empty($uploadName) ||
      !isset($page->_elementIndex[$uploadName])
    ) {
      return;
    }

    // get the element containing the upload
    $element = &$page->getElement($uploadName);
    if ('file' == $element->getType()) {
      if ($element->isUploadedFile()) {
        // rename the uploaded file with a unique number at the end
        $value = $element->getValue();

        $newName = CRM_Utils_File::makeFileName($value['name']);
        $status = $element->moveUploadedFile($this->_uploadDir, $newName);
        if (!$status) {
          CRM_Core_Error::statusBounce(ts('We could not move the uploaded file %1 to the upload directory %2. Please verify that the \'Temporary Files\' setting points to a valid path which is writable by your web server.', [
                1 => $value['name'],
                2 => $this->_uploadDir,
              ]));
        }
        if (!empty($data['values'][$pageName][$uploadName]['name'])) {
          @unlink($this->_uploadDir . $data['values'][$pageName][$uploadName]);
        }

        $value = [
          'name' => $this->_uploadDir . $newName,
          'type' => $value['type'],
        ];
        //CRM-19460 handle brackets if present in $uploadName, similar things we do it for all other inputs.
        $value = $element->_prepareValue($value, TRUE);
        $data['values'][$pageName] = HTML_QuickForm::arrayMerge($data['values'][$pageName], $value);
      }
    }
  }

  /**
   * Processes the request.
   *
   * @param CRM_Core_Form $page
   *   CRM_Core_Form the current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   */
  public function perform(&$page, $actionName) {
    // like in Action_Next
    $page->isFormBuilt() or $page->buildForm();

    // so this is a brain-seizure moment, so hang tight (real tight!)
    // the above buildForm potentially changes the action function with different args
    // so basically the rug might have been pulled from us, so we actually just check
    // and potentially call the right one
    // this allows standalone form uploads to work nicely
    $page->controller->_actions['upload']->realPerform($page, $actionName);
  }

  /**
   * Real perform.
   *
   * @todo document what I do.
   *
   * @param CRM_Core_Form $page
   * @param string $actionName
   *
   * @return mixed
   */
  public function realPerform(&$page, $actionName) {
    $pageName = $page->getAttribute('name');
    $data = &$page->controller->container();
    $data['values'][$pageName] = $page->exportValues();
    $data['valid'][$pageName] = $page->validate();

    if (!$data['valid'][$pageName]) {
      return $page->handle('display');
    }

    foreach ($this->_uploadNames as $name) {
      $this->upload($page, $data, $pageName, $name);
    }

    $state = &$this->_stateMachine->getState($pageName);
    if (empty($state)) {
      return $page->handle('display');
    }

    // the page is valid, process it before we jump to the next state
    $page->mainProcess();

    // check if destination is set, if so goto destination
    $destination = $this->_stateMachine->getDestination();
    if ($destination) {
      $destination = urldecode($destination);
      CRM_Utils_System::redirect($destination);
    }
    else {
      return $state->handleNextState($page);
    }
  }

}
