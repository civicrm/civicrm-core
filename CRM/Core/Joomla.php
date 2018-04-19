<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Joomla class.
 *
 * (clearly copied & pasted from a drupal class).
 *
 * Still used?
 */
class CRM_Core_Joomla {

  /**
   * Reuse drupal blocks into a left sidebar.
   *
   * Assign the generated template to the smarty instance.
   */
  public static function sidebarLeft() {
    $config = CRM_Core_Config::singleton();

    if ($config->userFrameworkFrontend) {
      return;
    }

    $blockIds = array(
      CRM_Core_Block::CREATE_NEW,
      CRM_Core_Block::RECENTLY_VIEWED,
      CRM_Core_Block::DASHBOARD,
      CRM_Core_Block::ADD,
      CRM_Core_Block::LANGSWITCH,
      //CRM_Core_Block::EVENT,
      //CRM_Core_Block::FULLTEXT_SEARCH
    );

    $blocks = array();
    foreach ($blockIds as $id) {
      $blocks[] = CRM_Core_Block::getContent($id);
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('blocks', $blocks);
    $sidebarLeft = $template->fetch('CRM/Block/blocks.tpl');
    $template->assign_by_ref('sidebarLeft', $sidebarLeft);
  }

}
