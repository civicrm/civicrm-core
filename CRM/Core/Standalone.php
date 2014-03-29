<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Standalone {
	
		/* Copied from CRM/Core/Joomla.php */

    /**
     * Reuse drupal blocks into a left sidebar. Assign the generated template
     * to the smarty instance
     *
     * @return void
     * @access public
     * @static
     */
    static function sidebarLeft( ) {
        $config = CRM_Core_Config::singleton( );

        require_once 'CRM/Core/Block.php';

        $blockIds = array(
            CRM_Core_Block::CREATE_NEW,
            CRM_Core_Block::RECENTLY_VIEWED,
        //    CRM_Core_Block::DASHBOARD,
            CRM_Core_Block::ADD,
            CRM_Core_Block::DID_YOU_KNOW,
            CRM_Core_Block::LANGSWITCH,
        //    CRM_Core_Block::EVENT,
        //    CRM_Core_Block::FULLTEXT_SEARCH
        );

      // ATTENTION: Cividesk-specific!
      global $customer;
      if ($customer->name() == 'nhvef') {
        // Do not show the 'New Individual' block
        unset($blockIds[2]);
      }

      $blocks = array( );
        // CiviDesk logo block
        if ( defined( 'CIVICRM_LOGO_IMG' )) {
            $content = '<img src="' . str_replace('/persist/contribute/', '/files/', $config->imageUploadURL) . CIVICRM_LOGO_IMG . '" width=170>';
            if ( defined( 'CIVICRM_LOGO_URL' ))
                $content = '<a href="'. CIVICRM_LOGO_URL . '" target="_blank">'. $content . '</a>';
        } else
            $content = '<a href="http://www.cividesk.com" target="_blank"><img src="' . $config->resourceBase . 'i/logos/cividesk.png" width=170></a>';
        // Replace with warning if on a development server
        if (defined('ITBLISS_DEVELOPMENT')) {
          $content = '<p style="font-size: 14px; color: red;">This page served from<br>a development server.</p>';
        }
        $blocks[] = array(
            'name'    => 'block-civicrm',
            'id'      => 'block-civicrm_logo',
            'content' => $content,
            );
        // CiviCRM core blocks
        foreach ( $blockIds as $id ) {
            $blocks[] = CRM_Core_Block::getContent( $id );
        }

        require_once 'CRM/Core/Smarty.php';
        $template = CRM_Core_Smarty::singleton();
        $template->assign_by_ref( 'blocks', $blocks );
        $sidebarLeft = $template->fetch( 'CRM/Block/blocks.tpl' );
        $template->assign_by_ref( 'sidebarLeft', $sidebarLeft );
    }

}


