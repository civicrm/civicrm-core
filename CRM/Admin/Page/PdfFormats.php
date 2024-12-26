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
 * Page for displaying list of PDF Page Formats.
 */
class CRM_Admin_Page_PdfFormats extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/pdfFormats/edit',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit PDF Page Format'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/pdfFormats/edit',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete PDF Page Format'),
        ],
      ];
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
        ['id' => $format['id']],
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
