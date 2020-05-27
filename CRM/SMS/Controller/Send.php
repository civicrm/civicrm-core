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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_SMS_Controller_Send extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal, NULL, FALSE, TRUE);

    $mailingID = CRM_Utils_Request::retrieve('mid', 'String', $this);

    // also get the text and html file
    $txtFile = CRM_Utils_Request::retrieveValue('txtFile', 'String');

    $config = CRM_Core_Config::singleton();
    if ($txtFile &&
      file_exists($config->uploadDir . $txtFile)
    ) {
      $this->set('textFilePath', $config->uploadDir . $txtFile);
    }

    $this->_stateMachine = new CRM_SMS_StateMachine_Send($this, $action, $mailingID);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $uploadNames = array_merge(['textFile'],
      CRM_Core_BAO_File::uploadNames()
    );

    $this->addActions(CRM_Core_Config::singleton()->uploadDir,
      $uploadNames
    );
  }

}
