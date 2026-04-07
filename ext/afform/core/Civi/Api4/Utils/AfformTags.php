<?php
namespace Civi\Api4\Utils;

/**
 * Class AfformTags
 * @package Civi\Api4\Utils
 *
 * Utils for managing the tags field on Afforms
 *
 */
class AfformTags {

  /**
   * @return array
   */
  public static function getTagOptions(): array {
    $tagRecords = (array) \Civi\Api4\Tag::get(FALSE)
      ->addSelect('name', 'label', 'description', 'color')
      ->addWhere('used_for', 'CONTAINS', 'Afform')
      ->addWhere('is_selectable', '=', TRUE)
      ->addOrderBy('label', 'ASC')
      ->execute();

    $tagOptions = [];

    // prefer tag names to keys for portability
    foreach ($tagRecords as $record) {
      $record['id'] = $record['name'];
      $tagOptions[] = $record;
    }

    return $tagOptions;
  }

}
