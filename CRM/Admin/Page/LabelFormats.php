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
 * Page for displaying list of Label Formats.
 */
class CRM_Admin_Page_LabelFormats extends CRM_Core_Page_Basic {

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
    return 'CRM_Core_BAO_LabelFormat';
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
          'url' => 'civicrm/admin/labelFormats/edit',
          'qs' => 'action=update&id=%%id%%&group=%%group%%&reset=1',
          'title' => ts('Edit Label Format'),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy'),
          'url' => 'civicrm/admin/labelFormats/edit',
          'qs' => 'action=copy&id=%%id%%&group=%%group%%&reset=1',
          'title' => ts('Copy Label Format'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/labelFormats/edit',
          'qs' => 'action=delete&id=%%id%%&group=%%group%%&reset=1',
          'title' => ts('Delete Label Format'),
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
    return 'CRM_Admin_Form_LabelFormats';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Mailing Label Formats';
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
    return 'civicrm/admin/labelFormats';
  }

  /**
   * Browse all Label Format settings.
   *
   * @param null $action
   */
  public function browse($action = NULL) {
    // Get list of configured Label Formats
    $labelFormatList = CRM_Core_BAO_LabelFormat::getList();
    $nameFormatList = CRM_Core_BAO_LabelFormat::getList(FALSE, 'name_badge');

    // Add action links to each of the Label Formats
    foreach ($labelFormatList as & $format) {
      $action = array_sum(array_keys($this->links()));
      if (!empty($format['is_reserved'])) {
        $action -= CRM_Core_Action::DELETE;
      }

      $format['groupName'] = ts('Mailing Label');
      $format['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $format['id'], 'group' => 'label_format'],
        ts('more'),
        FALSE,
        'labelFormat.manage.action',
        'LabelFormat',
        $format['id']
      );
    }

    // Add action links to each of the Label Formats
    foreach ($nameFormatList as & $format) {
      $format['groupName'] = ts('Name Badge');
    }

    $labelFormatList = array_merge($labelFormatList, $nameFormatList);

    // Order Label Formats by weight
    $returnURL = CRM_Utils_System::url(self::userContext());
    CRM_Core_BAO_LabelFormat::addOrder($labelFormatList, $returnURL);

    $this->assign('rows', $labelFormatList);
  }

}
