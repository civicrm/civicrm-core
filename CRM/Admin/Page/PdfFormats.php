<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Page for displaying list of PDF Page Formats.
 */
class CRM_Admin_Page_PdfFormats extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_PdfFormat';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/pdfFormats',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit PDF Page Format'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/pdfFormats',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete PDF Page Format'),
        ),
      );
    }

    return self::$_links;
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_PdfFormats';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'PDF Page Formats';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/pdfFormats';
  }

  /**
   * Browse all PDF Page Formats.
   *
   * @param null $action
   */
  public function browse($action = NULL) {
    // Get list of configured PDF Page Formats
    $pdfFormatList = CRM_Core_BAO_PdfFormat::getList();

    // Add action links to each of the PDF Page Formats
    $action = array_sum(array_keys($this->links()));
    foreach ($pdfFormatList as & $format) {
      $format['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        array('id' => $format['id']),
        ts('more'),
        FALSE,
        'pdfFormat.manage.action',
        'PdfFormat',
        $format['id']
      );
    }

    // Order Label Formats by weight
    $returnURL = CRM_Utils_System::url(self::userContext());
    CRM_Core_BAO_PdfFormat::addOrder($pdfFormatList, $returnURL);

    $this->assign('rows', $pdfFormatList);
  }

}
