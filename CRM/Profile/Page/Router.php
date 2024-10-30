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

/**
 * This is some kind of special-purpose router/front-controller for the various profile URLs.
 */
class CRM_Profile_Page_Router extends CRM_Core_Page {

  /**
   * This is some kind of special-purpose router/front-controller for the various profile URLs.
   *
   * @param array $args
   *   this array contains the arguments of the url.
   *
   * @return string|void
   */
  public function run($args = NULL) {
    if ($args[1] !== 'profile') {
      return NULL;
    }

    $secondArg = $args[2] ?? '';

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
      $isMap = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileGID, 'is_map');
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
      $allowRemoteSubmit = Civi::settings()->get('remote_profile_submissions');
      if ($secondArg == 'edit') {
        $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Edit',
          ts('Create Profile'),
          CRM_Core_Action::UPDATE,
          FALSE, FALSE, $allowRemoteSubmit
        );
        $controller->set('edit', 1);
        $controller->process();
        return $controller->run();
      }
      else {
        $wrapper = new CRM_Utils_Wrapper();
        return $wrapper->run('CRM_Profile_Form_Edit',
          ts('Create Profile'),
          [
            'mode' => CRM_Core_Action::ADD,
            'ignoreKey' => $allowRemoteSubmit,
          ]
        );
      }
    }

    if ($secondArg == 'view' || empty($secondArg)) {
      $page = new CRM_Profile_Page_Listings();
      return $page->run();
    }

    CRM_Utils_System::permissionDenied();
  }

}
