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

namespace Civi\Api4\Action\Tag;

use Civi\Api4\EntityTag;
use Civi\Api4\Tag;
use Civi\Api4\Utils\CoreUtil;

/**
 * Find every record tagged with one or more tags, across both `EntityTag`-backed
 * entities (real DB tables) and any entity registered via the
 * `alterNonDbTaggableEntities` hook (tag-able but not a physical table, e.g. Afform).
 *
 * Filter by `tag_id` in the `where` clause (`=`/`IN`) to scope which tags are looked
 * up; omitting it looks up every tag, which is expensive and rarely what you want.
 * Every row always has `tag_id`/`entity_table`/`entity_type`/`entity_id`. Including
 * `label` and/or `url` in `select` additionally resolves a display label and (where
 * the entity declares one) a link, ready for a UI to render directly -- each costs
 * one extra `get` call per distinct entity type found, so only ask for them when you
 * need them.
 */
class GetTaggedEntities extends \Civi\Api4\Generic\BasicGetAction {

  protected function getRecords() {
    $tagIds = $this->_itemsToGet('tag_id');
    // No tag_id restriction given - look up every tag. Expensive, but the caller asked for it.
    if ($tagIds === NULL) {
      $tagIds = Tag::get($this->getCheckPermissions())->addSelect('id')->execute()->column('id');
    }
    // Nonexistent tag ids have no tagged entities - that's a legitimate empty
    // result, not an error, so drop them before invoking any hook callback.
    $tagIds = Tag::get($this->getCheckPermissions())->addSelect('id')->addWhere('id', 'IN', $tagIds)->execute()->column('id');

    $resolveDisplayInfo = $this->_isFieldSelected('label', 'url');

    $rows = [];
    foreach ($tagIds as $tagId) {
      $idsByEntity = [];
      foreach (EntityTag::get($this->getCheckPermissions())
        ->addWhere('tag_id', '=', $tagId)
        ->addSelect('entity_table', 'entity_id')
        ->execute() as $entityTag) {
        $entityName = \CRM_Core_DAO_AllCoreTables::getEntityNameForTable($entityTag['entity_table']);
        if ($entityName) {
          $idsByEntity[$entityName][] = (int) $entityTag['entity_id'];
        }
      }
      foreach (\CRM_Core_BAO_EntityTag::getNonDbTaggedIds($tagId) as $entityName => $ids) {
        $idsByEntity[$entityName] = array_merge($idsByEntity[$entityName] ?? [], $ids);
      }

      foreach ($idsByEntity as $entityName => $ids) {
        $displayInfo = $resolveDisplayInfo ? $this->getDisplayInfo($entityName, $ids) : [];
        foreach ($ids as $entityId) {
          $row = [
            'tag_id' => $tagId,
            // Non-DB-backed entities (e.g. Afform) have no real table -- fall back to the
            // entity name itself, matching what getNonDbTaggedIds() already keys its result by.
            'entity_table' => CoreUtil::getTableName($entityName) ?: $entityName,
            'entity_type' => $entityName,
            'entity_id' => $entityId,
          ];
          if ($resolveDisplayInfo) {
            $row += $displayInfo[$entityId] ?? ['label' => '#' . $entityId, 'url' => NULL];
          }
          $rows[] = $row;
        }
      }
    }
    return $rows;
  }

  /**
   * Resolve a display label + url for each id of a given entity type.
   *
   * @param string $entityName
   * @param int[] $ids
   * @return array<int, array{label: string, url: string|null}>
   */
  private function getDisplayInfo(string $entityName, array $ids): array {
    $idField = CoreUtil::getIdFieldName($entityName);
    $labelField = CoreUtil::getInfoItem($entityName, 'label_field') ?: $idField;
    $paths = CoreUtil::getInfoItem($entityName, 'paths') ?? [];
    $path = $paths['view'] ?? $paths['update'] ?? NULL;
    try {
      $records = civicrm_api4($entityName, 'get', [
        'select' => array_unique([$idField, $labelField]),
        'where' => [[$idField, 'IN', $ids]],
        'checkPermissions' => FALSE,
      ]);
    }
    catch (\CRM_Core_Exception $e) {
      return [];
    }
    $info = [];
    foreach ($records as $record) {
      $info[$record[$idField]] = [
        'label' => $record[$labelField] ?? ('#' . $record[$idField]),
        'url' => $path ? \CRM_Utils_System::url(str_replace('[id]', $record[$idField], $path)) : NULL,
      ];
    }
    return $info;
  }

  public static function fields() {
    return [
      [
        'name' => 'tag_id',
        'title' => 'Tag ID',
        'description' => 'The tag this row is tagged with',
        'data_type' => 'Integer',
        'fk_entity' => 'Tag',
      ],
      [
        'name' => 'entity_table',
        'title' => 'Entity Table',
        'description' => 'Table name, or the entity name itself for non-DB-backed entities',
        'data_type' => 'String',
      ],
      [
        'name' => 'entity_type',
        'title' => 'Entity Type',
        'description' => 'Api entity name of the tagged record',
        'data_type' => 'String',
      ],
      [
        'name' => 'entity_id',
        'title' => 'Entity ID',
        // Not always Integer - non-DB-backed entities can have a non-numeric primary key
        // (e.g. Afform's is `name`), so this can't be declared as a fixed data_type without
        // the output formatter coercing those values down to 0.
        'description' => 'Id (or other unique identifier) of the tagged record',
      ],
      [
        'name' => 'label',
        'title' => 'Label',
        'description' => 'Display label of the tagged record. Only resolved when selected.',
        'data_type' => 'String',
      ],
      [
        'name' => 'url',
        'title' => 'URL',
        'description' => 'Link to view/edit the tagged record, if the entity declares one. Only resolved when selected.',
        'data_type' => 'String',
      ],
    ];
  }

}
