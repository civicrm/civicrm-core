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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contribute_Info extends CRM_Core_Component_Info {


  // docs inherited from interface
  protected $keyword = 'contribute';

  // docs inherited from interface
  public function getInfo() {
    return array(
      'name' => 'CiviContribute',
      'translatedName' => ts('CiviContribute'),
      'title' => ts('CiviCRM Contribution Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    );
  }

  // docs inherited from interface
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array(
      'access CiviContribute',
      'edit contributions',
      'make online contributions',
      'delete in CiviContribute',
    );
  }


  // docs inherited from interface
  public function getUserDashboardElement() {
    return array('name' => ts('Contributions'),
      'title' => ts('Your Contribution(s)'),
      'perm' => array('make online contributions'),
      'weight' => 10,
    );
  }

  // docs inherited from interface
  public function registerTab() {
    return array('title' => ts('Contributions'),
      'url' => 'contribution',
      'weight' => 20,
    );
  }

  // docs inherited from interface
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Contributions'),
      'weight' => 20,
    );
  }

  // docs inherited from interface
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  public function creatNewShortcut(&$shortCuts, $newCredit) {
    if (CRM_Core_Permission::check('access CiviContribute') &&
      CRM_Core_Permission::check('edit contributions')
    ) {
      $shortCuts = array_merge($shortCuts, array(
        array('path' => 'civicrm/contribute/add',
            'query' => "reset=1&action=add&context=standalone",
            'ref' => 'new-contribution',
            'title' => ts('Contribution'),
          )));
      if ($newCredit) {
        $title = ts('Contribution') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCuts = array_merge($shortCuts, array(
          array('path' => 'civicrm/contribute/add',
              'query' => "reset=1&action=add&context=standalone&mode=live",
              'ref' => 'new-contribution-cc',
              'title' => $title,
            )));        
      }
    }
  }
}

