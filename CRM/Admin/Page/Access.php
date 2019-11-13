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
 * Dashboard page for managing Access Control.
 *
 * For initial version, this page only contains static links - so this class is empty for now.
 */
class CRM_Admin_Page_Access extends CRM_Core_Page {

  /**
   * @return string
   */
  public function run() {
    $config = CRM_Core_Config::singleton();

    switch ($config->userFramework) {
      case 'Drupal':
        $this->assign('ufAccessURL', url('admin/people/permissions'));
        break;

      case 'Drupal6':
        $this->assign('ufAccessURL', url('admin/user/permissions'));
        break;

      case 'Joomla':
        //condition based on Joomla version; <= 2.5 uses modal window; >= 3.0 uses full page with return value
        if (version_compare(JVERSION, '3.0', 'lt')) {
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
