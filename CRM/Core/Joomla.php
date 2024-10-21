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

    $blockIds = [
      CRM_Core_Block::CREATE_NEW,
      CRM_Core_Block::RECENTLY_VIEWED,
      CRM_Core_Block::DASHBOARD,
      CRM_Core_Block::ADD,
      CRM_Core_Block::LANGSWITCH,
      //CRM_Core_Block::EVENT,
      //CRM_Core_Block::FULLTEXT_SEARCH
    ];

    $blocks = [];
    foreach ($blockIds as $id) {
      $blocks[] = CRM_Core_Block::getContent($id);
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign('blocks', $blocks);
    $sidebarLeft = $template->fetch('CRM/Block/blocks.tpl');
    $template->assign('sidebarLeft', $sidebarLeft);
  }

}
