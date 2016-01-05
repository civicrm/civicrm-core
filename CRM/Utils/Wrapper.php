<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * The Contact Wrapper is a wrapper class which is called by
 * contact.module after it parses the menu path.
 *
 * The key elements of the wrapper are the controller and the
 * run method as explained below.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Wrapper {

  /**
   * Simple Controller.
   *
   * The controller which will handle the display and processing of this page.
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
      $mode = CRM_Utils_Array::value('mode', $arguments);
      $imageUpload = !empty($arguments['imageUpload']);
      $addSequence = !empty($arguments['addSequence']);
      $attachUpload = !empty($arguments['attachUpload']);
      $ignoreKey = !empty($arguments['ignoreKey']);
    }
    else {
      $arguments = array();
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
          $urlVar = CRM_Utils_Array::value('urlVar', $params);
          $sessionVar = CRM_Utils_Array::value('sessionVar', $params);
          $type = CRM_Utils_Array::value('type', $params);
          $default = CRM_Utils_Array::value('default', $params);
          $abort = CRM_Utils_Array::value('abort', $params, FALSE);

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
