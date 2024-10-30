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
 * The Contact Wrapper is a wrapper class which is called by
 * contact.module after it parses the menu path.
 *
 * The key elements of the wrapper are the controller and the
 * run method as explained below.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Wrapper {

  /**
   * Simple Controller.
   *
   * The controller which will handle the display and processing of this page.
   * @var \CRM_Core_Controller_Simple
   */
  protected $_controller;

  /**
   * Run.
   *
   * The heart of the callback processing is done by this method.
   * forms are of different type and have different operations.
   *
   * @param string $formName name of the form processing this action
   * @param string $formLabel label for the above form
   * @param array $arguments
   *  - int mode: mode of operation.
   *  - bool addSequence: should we add a unique sequence number to the end of the key
   *  - bool ignoreKey: should we not set a qfKey for this controller (for standalone forms)
   *
   * @return mixed
   */
  public function run($formName, $formLabel = NULL, $arguments = NULL) {
    if (is_array($arguments)) {
      $mode = $arguments['mode'] ?? NULL;
      $imageUpload = !empty($arguments['imageUpload']);
      $addSequence = !empty($arguments['addSequence']);
      $attachUpload = !empty($arguments['attachUpload']);
      $ignoreKey = !empty($arguments['ignoreKey']);
    }
    else {
      $arguments = [];
      $mode = NULL;
      $addSequence = $ignoreKey = $imageUpload = $attachUpload = FALSE;
    }

    $this->_controller = new CRM_Core_Controller_Simple(
      $formName,
      $formLabel,
      $mode,
      $imageUpload,
      $addSequence,
      $ignoreKey,
      $attachUpload
    );

    if (array_key_exists('urlToSession', $arguments)) {
      if (is_array($arguments['urlToSession'])) {
        foreach ($arguments['urlToSession'] as $params) {
          $urlVar = $params['urlVar'] ?? NULL;
          $sessionVar = $params['sessionVar'] ?? NULL;
          $type = $params['type'] ?? NULL;
          $default = $params['default'] ?? NULL;
          $abort = $params['abort'] ?? FALSE;

          $value = NULL;
          $value = CRM_Utils_Request::retrieve(
            $urlVar,
            $type,
            $this->_controller,
            $abort,
            $default
          );
          $this->_controller->set($sessionVar, $value);
        }
      }
    }

    if (array_key_exists('setEmbedded', $arguments)) {
      $this->_controller->setEmbedded(TRUE);
    }

    $this->_controller->process();
    return $this->_controller->run();
  }

}
