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
 * We use QFC for both single page and multi page wizards. We want to make
 * creation of single page forms as easy and as seamless as possible. This
 * class is used to optimize and make single form pages a relatively trivial
 * process
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Controller_Simple extends CRM_Core_Controller {

  /**
   * Constructor.
   *
   * @param string $path
   *   The class Path of the form being implemented
   * @param bool $title
   * @param string $mode
   * @param bool $imageUpload
   * @param bool $addSequence
   *   Should we add a unique sequence number to the end of the key.
   * @param bool $ignoreKey
   *   Should we not set a qfKey for this controller (for standalone forms).
   * @param bool $attachUpload
   *
   * @return \CRM_Core_Controller_Simple
   */
  public function __construct(
    $path,
    $title,
    $mode = NULL,
    $imageUpload = FALSE,
    $addSequence = FALSE,
    $ignoreKey = FALSE,
    $attachUpload = FALSE
  ) {
    // by definition a single page is modal :). We use the form name as the scope for this controller
    parent::__construct($title, TRUE, $mode, $path, $addSequence, $ignoreKey);

    $this->_stateMachine = new CRM_Core_StateMachine($this);

    $params = [$path => NULL];

    $savedAction = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, NULL);
    if (!empty($savedAction) &&
      $savedAction != $mode
    ) {
      $mode = $savedAction;
    }

    $this->_stateMachine->addSequentialPages($params);

    $this->addPages($this->_stateMachine, $mode);

    //changes for custom data type File
    $uploadNames = $this->get('uploadNames');

    $config = CRM_Core_Config::singleton();

    if (is_array($uploadNames) && !empty($uploadNames)) {
      $uploadArray = $uploadNames;
      $this->addActions($config->customFileUploadDir, $uploadArray);
      $this->set('uploadNames', NULL);
    }
    else {
      // always allow a single upload file with same name
      if ($attachUpload) {
        $this->addActions($config->uploadDir,
          CRM_Core_BAO_File::uploadNames()
        );
      }
      elseif ($imageUpload) {
        $this->addActions($config->imageUploadDir, ['uploadFile']);
      }
      else {
        $this->addActions();
      }
    }
  }

  /**
   * Set parent.
   *
   * @param $parent
   */
  public function setParent($parent) {
    $this->_parent = $parent;
  }

  /**
   * Get template file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    // there is only one form here, so should be quite easy
    $actionName = $this->getActionName();
    [$pageName, $action] = $actionName;

    return $this->_pages[$pageName]->getTemplateFileName();
  }

  /**
   * A wrapper for getTemplateFileName.
   *
   * This includes calling the hook to  prevent us from having to copy & paste
   * the logic of calling the hook
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

}
