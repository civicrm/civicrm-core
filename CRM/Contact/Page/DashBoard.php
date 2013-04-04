<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * CiviCRM Dashboard
 *
 */
class CRM_Contact_Page_DashBoard extends CRM_Core_Page {

  /**
   * Run dashboard
   *
   * @return none
   * @access public
   */
  function run() {
    // Add dashboard js and css
    $resources = CRM_Core_Resources::singleton();
    $resources->addScriptFile('civicrm', 'packages/jquery/plugins/jquery.dashboard.js', 0, 'html-header', FALSE);
    $resources->addStyleFile('civicrm', 'packages/jquery/css/dashboard.css');

    $config = CRM_Core_Config::singleton();

    // Add dashlet-specific js files
    // TODO: Need a much better way of managing on-the-fly js requirements. Require.js perhaps?
    // Checking if a specific dashlet is enabled is a pain and including the js here sucks anyway
    // So here's a compromise:
    if (in_array('CiviCase', $config->enableComponents)) {
      $resources->addScriptFile('civicrm', 'templates/CRM/Case/Form/ActivityChangeStatus.js');
    }

    $resetCache = CRM_Utils_Request::retrieve('resetCache', 'Positive', CRM_Core_DAO::$_nullObject);

    CRM_Utils_System::setTitle(ts('CiviCRM Home'));
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    if ($resetCache) {
      CRM_Core_BAO_Dashboard::resetDashletCache($contactID);
    }

    // call hook to get html from other modules
    // ignored but needed to prevent warnings
    $contentPlacement = CRM_Utils_Hook::DASHBOARD_BELOW;
    $html = CRM_Utils_Hook::dashboard($contactID, $contentPlacement);
    if (is_array($html)) {
      $this->assign_by_ref('hookContent', $html);
      $this->assign('hookContentPlacement', $contentPlacement);
    }

    //check that default FROM email address, owner (domain) organization name and default mailbox are configured.
    $fromEmailOK      = TRUE;
    $ownerOrgOK       = TRUE;
    $defaultMailboxOK = TRUE;

    // Don't put up notices if user doesn't have administer CiviCRM permission
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $destination = CRM_Utils_System::url(
        'civicrm/dashboard',
        'reset=1',
        FALSE, NULL, FALSE
      );

      $destination = urlencode($destination);

      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);

      if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
        $fixEmailUrl = CRM_Utils_System::url("civicrm/admin/domain", "action=update&reset=1&civicrmDestination={$destination}");
        $this->assign('fixEmailUrl', $fixEmailUrl);
        $fromEmailOK = FALSE;
      }

      $domain = CRM_Core_BAO_Domain::getDomain();
      $domainName = $domain->name;
      if (!$domainName || $domainName == 'Default Domain Name') {
        $fixOrgUrl = CRM_Utils_System::url("civicrm/admin/domain", "action=update&reset=1&civicrmDestination={$destination}");
        $this->assign('fixOrgUrl', $fixOrgUrl);
        $ownerOrgOK = FALSE;
      }

      if (in_array('CiviMail', $config->enableComponents) &&
        CRM_Core_BAO_MailSettings::defaultDomain() == "EXAMPLE.ORG"
      ) {
        $fixDefaultMailbox = CRM_Utils_System::url('civicrm/admin/mailSettings', "reset=1&civicrmDestination={$destination}");
        $this->assign('fixDefaultMailbox', $fixDefaultMailbox);
        $defaultMailboxOK = FALSE;
      }
    }

    $this->assign('fromEmailOK', $fromEmailOK);
    $this->assign('ownerOrgOK', $ownerOrgOK);
    $this->assign('defaultMailboxOK', $defaultMailboxOK);

    $communityMessages = CRM_Core_CommunityMessages::create();
    if ($communityMessages->isEnabled()) {
      $message = $communityMessages->pick();
      if ($message) {
        $this->assign('communityMessages', $communityMessages->evalMarkup($message['markup']));
      }
    }

    return parent::run();
  }
}

