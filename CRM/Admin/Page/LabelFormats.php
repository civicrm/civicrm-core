<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * Page for displaying list of Label Formats
 */
class CRM_Admin_Page_LabelFormats extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_LabelFormat';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/labelFormats',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Label Format'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy'),
          'url' => 'civicrm/admin/labelFormats',
          'qs' => 'action=copy&id=%%id%%',
          'title' => ts('Copy Label Format'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/labelFormats',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Label Format'),
        ),
      );
    }

    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_LabelFormats';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Mailing Label Formats';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/labelFormats';
  }

  /**
   * Browse all Label Format settings.
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    // Get list of configured Label Formats
    $labelFormatList = CRM_Core_BAO_LabelFormat::getList();

    // Add action links to each of the Label Formats
    foreach ($labelFormatList as & $format) {
      $action = array_sum(array_keys($this->links()));
      if (CRM_Utils_Array::value('is_reserved', $format)) {
        $action -= CRM_Core_Action::DELETE;
      }
      $format['action'] = CRM_Core_Action::formLink(self::links(), $action, array('id' => $format['id']));
    }

    // Order Label Formats by weight
    $returnURL = CRM_Utils_System::url(self::userContext());
    CRM_Core_BAO_LabelFormat::addOrder($labelFormatList, $returnURL);

    $this->assign('rows', $labelFormatList);
  }
}

