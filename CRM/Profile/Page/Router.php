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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This is some kind of special-purpose router/front-controller for the various profile URLs.
 */
class CRM_Profile_Page_Router extends CRM_Core_Page {

  /**
   * This is some kind of special-purpose router/front-controller for the various profile URLs.
   *
   * @param $args array this array contains the arguments of the url
   *
   * @return string|void
   * @static
   * @access public
   */
  function run($args = NULL) {
    if ($args[1] !== 'profile') {
      return;
    }

    $secondArg = CRM_Utils_Array::value(2, $args, '');

    if ($secondArg == 'map') {
      $controller = new CRM_Core_Controller_Simple(
        'CRM_Contact_Form_Task_Map',
        ts('Map Contact'),
        NULL, FALSE, FALSE, TRUE
      );

      $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

      if (count($gids) > 1) {
        foreach ($gids as $pfId) {
          $profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
        $controller->set('gid', $profileIds[0]);
        $profileGID = $profileIds[0];
      }
      else {
        $profileGID = CRM_Utils_Request::retrieve('gid', 'Integer', $controller, TRUE);
      }


      // make sure that this profile enables mapping
      // CRM-8609
      $isMap =
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileGID, 'is_map');
      if (!$isMap) {
        CRM_Core_Error::statusBounce(ts('This profile does not have the map feature turned on.'));
      }

      $profileView = CRM_Utils_Request::retrieve('pv', 'Integer', $controller, FALSE);

      // set the userContext stack
      $session = CRM_Core_Session::singleton();
      if ($profileView) {
        $session->pushUserContext(CRM_Utils_System::url('civicrm/profile/view'));
      }
      else {
        $session->pushUserContext(CRM_Utils_System::url('civicrm/profile', 'force=1'));
      }

      $controller->set('profileGID', $profileGID);
      $controller->process();
      return $controller->run();
    }

    if ($secondArg == 'edit' || $secondArg == 'create') {
      if ($secondArg == 'edit') {
        $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Edit',
          ts('Create Profile'),
          CRM_Core_Action::UPDATE,
          FALSE, FALSE, TRUE
        );
        $controller->set('edit', 1);
        $controller->process();
        return $controller->run();
      }
      else {
        $wrapper = new CRM_Utils_Wrapper();
        return $wrapper->run('CRM_Profile_Form_Edit',
          ts('Create Profile'),
          array(
            'mode' => CRM_Core_Action::ADD,
            'ignoreKey' => TRUE,
          )
        );
      }
    }

    if ($secondArg == 'view' || empty($secondArg)) {
      $page = new CRM_Profile_Page_Listings();
      return $page->run();
    }

    CRM_Utils_System::permissionDenied();
    return;
  }

}
