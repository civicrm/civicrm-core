<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * We use QFC for both single page and multi page wizards. We want to make
 * creation of single page forms as easy and as seamless as possible. This
 * class is used to optimize and make single form pages a relatively trivial
 * process
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Controller_Simple extends CRM_Core_Controller {

  /**
   * constructor
   *
   * @param null $path
   * @param bool $title
   * @param string  path        the class Path of the form being implemented
   * @param bool $imageUpload
   * @param bool $addSequence
   * @param bool $ignoreKey
   * @param bool $attachUpload
   *
   * @internal param \addSequence $boolean should we add a unique sequence number to the end of the key
   * @internal param \ignoreKey $boolean should we not set a qfKey for this controller (for standalone forms)
   *
   * @return \CRM_Core_Controller_Simple
  @access public
   */
  function __construct(
    $path,
    $title,
    $mode         = NULL,
    $imageUpload  = FALSE,
    $addSequence  = FALSE,
    $ignoreKey    = FALSE,
    $attachUpload = FALSE
  ) {
    // by definition a single page is modal :). We use the form name as the scope for this controller
    parent::__construct($title, TRUE, $mode, $path, $addSequence, $ignoreKey);

    $this->_stateMachine = new CRM_Core_StateMachine($this);

    $params = array($path => NULL);

    $savedAction = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, NULL);
    if (!empty($savedAction) &&
      $savedAction != $mode
    ) {
      $mode = $savedAction;
    }


    $this->_stateMachine->addSequentialPages($params, $mode);

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
        $this->addActions($config->imageUploadDir, array('uploadFile'));
      }
      else {
        $this->addActions();
      }
    }
  }

  /**
   * @param $parent
   */
  public function setParent($parent) {
    $this->_parent = $parent;
  }

  /**
   * @return mixed
   */
  public function getTemplateFileName() {
    // there is only one form here, so should be quite easy
    $actionName = $this->getActionName();
    list($pageName, $action) = $actionName;

    return $this->_pages[$pageName]->getTemplateFileName();
  }

  /**
   * A wrapper for getTemplateFileName that includes calling the hook to
   * prevent us from having to copy & paste the logic of calling the hook
   */
  function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }
}

