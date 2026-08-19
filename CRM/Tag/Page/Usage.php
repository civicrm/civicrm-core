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
 * Popup listing the entities tagged with a given tag.
 */
class CRM_Tag_Page_Usage extends CRM_Core_Page {

  public function run() {
    $tagId = CRM_Utils_Request::retrieve('tag_id', 'Positive', $this, TRUE);
    $tag = civicrm_api4('Tag', 'get', [
      'select' => ['label'],
      'where' => [['id', '=', $tagId]],
      'checkPermissions' => FALSE,
    ])->first();
    if (!$tag) {
      CRM_Core_Error::statusBounce(ts('Tag not found.'));
    }

    CRM_Utils_System::setTitle(ts('Records tagged "%1"', [1 => $tag['label']]));
    $this->assign('tagLabel', $tag['label']);

    $itemsByType = [];
    $tagged = civicrm_api4('Tag', 'getTaggedEntities', [
      'where' => [['tag_id', '=', $tagId]],
      'select' => ['entity_type', 'label', 'url'],
      'checkPermissions' => FALSE,
    ]);
    foreach ($tagged as $item) {
      $itemsByType[$item['entity_type']][] = ['label' => $item['label'], 'url' => $item['url']];
    }
    $groups = [];
    foreach ($itemsByType as $entityType => $items) {
      $groups[] = [
        'title' => \Civi\Api4\Utils\CoreUtil::getInfoItem($entityType, 'title_plural') ?: $entityType,
        'items' => $items,
      ];
    }
    $this->assign('groups', $groups);

    parent::run();
  }

}
