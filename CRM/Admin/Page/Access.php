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
 * Dashboard page for managing Access Control
 * For initial version, this page only contains static links - so this class is empty for now.
 */
class CRM_Admin_Page_Access extends CRM_Core_Page {
  /**
   * @return string
   */
  function run() {
    $config = CRM_Core_Config::singleton();

    switch ($config->userFramework) {
      case 'Drupal':
        $this->assign('ufAccessURL', CRM_Utils_System::url('admin/people/permissions'));
        break;

      case 'Drupal6':
        $this->assign('ufAccessURL', CRM_Utils_System::url('admin/user/permissions'));
        break;

      case 'Joomla':
        //condition based on Joomla version; <= 2.5 uses modal window; >= 3.0 uses full page with return value
        if( version_compare(JVERSION, '3.0', 'lt') ) {
          JHTML::_('behavior.modal');
          $url = $config->userFrameworkBaseURL . 'index.php?option=com_config&view=component&component=com_civicrm&tmpl=component';
          $jparams = 'rel="{handler: \'iframe\', size: {x: 875, y: 550}, onClose: function() {}}" class="modal"';

          $this->assign('ufAccessURL', $url);
          $this->assign('jAccessParams', $jparams);
        }
        else {
          $uri = (string) JUri::getInstance();
          $return = urlencode(base64_encode($uri));
          $url = $config->userFrameworkBaseURL . 'index.php?option=com_config&view=component&component=com_civicrm&return=' . $return;

          $this->assign('ufAccessURL', $url);
          $this->assign('jAccessParams', '');
        }
        break;

      case 'WordPress':
        $this->assign('ufAccessURL', CRM_Utils_System::url('civicrm/admin/access/wp-permissions', 'reset=1'));
        break;
    }
    return parent::run();
  }
}

