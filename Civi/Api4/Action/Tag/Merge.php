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

use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Tag;
use Civi\API\Exception\UnauthorizedException;

/**
 * Merge one or more tags into a single target tag.
 *
 * All entities tagged with the merged tags end up tagged with the target
 * tag instead, children of the merged tags are reparented onto the target,
 * and `used_for` on the target (and its children) becomes the union of all
 * the merged tags' `used_for` values. The merged tags are deleted.
 *
 * @method $this setTargetId(int $targetId)
 * @method int getTargetId()
 * @method $this setTagIds(array $tagIds)
 * @method array getTagIds()
 * @method $this setLabel(?string $label)
 * @method string|null getLabel()
 */
class Merge extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Tag to keep. Receives the other tags' entity-tags, children and used_for values.
   *
   * @var int
   * @required
   */
  protected $targetId;

  /**
   * Tags to merge into the target tag. These tags are deleted once merged.
   *
   * @var array
   * @required
   */
  protected $tagIds = [];

  /**
   * Optional new label for the target tag once merged.
   *
   * @var string|null
   */
  protected $label;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    $mergeIds = array_unique(array_diff($this->tagIds, [$this->targetId]));
    if (!$mergeIds) {
      throw new \CRM_Core_Exception('Select at least one other tag to merge into the target tag.');
    }

    $tags = Tag::get(FALSE)
      ->addWhere('id', 'IN', array_merge([$this->targetId], $mergeIds))
      ->addSelect('id', 'label', 'is_reserved')
      ->execute()
      ->indexBy('id');

    if (!isset($tags[$this->targetId])) {
      throw new \CRM_Core_Exception('Target tag not found.');
    }
    foreach ($mergeIds as $tagId) {
      if (!isset($tags[$tagId])) {
        throw new \CRM_Core_Exception("Tag {$tagId} not found.");
      }
    }

    if ($this->getCheckPermissions() && !\CRM_Core_Permission::check('administer reserved tags')) {
      foreach ($tags as $tag) {
        if (!empty($tag['is_reserved'])) {
          throw new UnauthorizedException('You do not have permission to administer reserved tags.');
        }
      }
    }

    foreach ($mergeIds as $tagId) {
      \CRM_Core_BAO_EntityTag::mergeTags($this->targetId, $tagId);
    }

    if ($this->label !== NULL && $this->label !== $tags[$this->targetId]['label']) {
      Tag::update($this->getCheckPermissions())
        ->addWhere('id', '=', $this->targetId)
        ->addValue('label', $this->label)
        ->execute();
    }

    $result[] = [
      'id' => $this->targetId,
      'merged' => array_values($mergeIds),
    ];
  }

  /**
   * @param \Civi\Api4\Generic\BasicGetFieldsAction $action
   * @return array
   */
  public static function fields(BasicGetFieldsAction $action) {
    return [];
  }

}
