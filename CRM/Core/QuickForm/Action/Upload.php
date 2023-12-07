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
 * Redefine the upload action.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
      /** @var HTML_QuickForm_file $element */
      if ($element->isUploadedFile()) {
        // rename the uploaded file with a unique number at the end
        $value = $element->getValue();

        $newName = CRM_Utils_File::makeFileName($value['name'], TRUE);
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
