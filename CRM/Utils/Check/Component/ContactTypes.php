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
class CRM_Utils_Check_Component_ContactTypes extends CRM_Utils_Check_Component {

  /**
   * TODO: This check should be removed when the contact_type.image_URL column is dropped
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkContactTypeIcons() {
    if (CRM_Utils_System::version() !== CRM_Core_BAO_Domain::version()) {
      return [];
    }

    $messages = [];
    $contactTypesWithImages = \Civi\Api4\ContactType::get(FALSE)
      ->addWhere('image_URL', 'IS NOT EMPTY')
      ->addWhere('icon', 'IS EMPTY')
      ->execute();

    if ($contactTypesWithImages->count()) {
      $message = CRM_Utils_Check_Message::warning([
        'name' => __FUNCTION__,
        'icon' => 'fa-picture-o',
        'topic' => ts('Contact Types'),
        'subtopic' => ts('Deprecated image format'),
        'message' => ts('Please select an icon for the following contact types using the new icon picker, as image urls will not be supported in future versions of CiviCRM.'),
      ]);
      foreach ($contactTypesWithImages as $contactType) {
        $message->addAction($contactType['label'], FALSE, 'href', ['path' => 'civicrm/admin/options/subtype/edit', 'query' => ['action' => 'update', 'id' => $contactType['id'], 'reset' => 1]], 'fa-pencil');
      }
      $messages[] = $message;
    }

    return $messages;
  }

}
