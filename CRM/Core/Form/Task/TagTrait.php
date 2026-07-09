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
 * Common functionality for adding/removing tags in Search tasks.
 */
trait CRM_Core_Form_Task_TagTrait {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $title = $this->getOperation() === 'add' ? ts('Add Tags') : ts('Remove Tags');

    $this->setTitle($title);

    $entityName = $this->getDefaultEntity();
    $entityTable = Civi::entity($entityName)->getMeta('table');

    $this->add('select2', 'tag', ts('Select Tag'), CRM_Core_BAO_Tag::getColorTags($entityTable), FALSE, ['multiple' => TRUE]);

    $parentNames = CRM_Core_BAO_Tag::getTagSet($entityTable);
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, $entityTable, NULL, TRUE, FALSE, 'entity_taglist');

    $this->addDefaultButtons($title);

    // Get base task template file for this entity
    $parentClass = get_parent_class($this);
    $this->assign('parentTemplate', CRM_Utils_System::getTemplateForClass($parentClass));
  }

  public function getTemplateFileName(): string {
    return 'CRM/Core/Form/Task/Tag.tpl';
  }

  public function addRules() {
    $this->addFormRule([get_class($this), 'formRule']);
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rule
   *
   * @return array
   */
  public static function formRule($form, $rule) {
    $errors = [];
    if (empty($form['tag']) && empty($form['contact_taglist'])) {
      $errors['_qf_default'] = ts('Please select at least one tag.');
    }
    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues();
    $submittedTags = $this->normalizeSelectedTags($params);

    $entityName = $this->getDefaultEntity();
    $entity = Civi::entity($entityName);
    $entityTable = $entity->getMeta('table');
    $entityTitle = $entity->getMeta('title');
    $titlePlural = $entity->getMeta('title_plural');

    $operation = $this->getOperation();

    foreach ($submittedTags as $tagId) {
      if ($operation === 'add') {
        [, $count, $notCount] = CRM_Core_BAO_EntityTag::addEntitiesToTag($this->_componentIds, $tagId, $entityTable, FALSE);
      }
      else {
        [, $count, $notCount] = CRM_Core_BAO_EntityTag::removeEntitiesFromTag($this->_componentIds, $tagId, $entityTable, FALSE);
      }

      $status = [];
      if ($operation === 'add') {
        $status[] = ts('1 %1 tagged', [
          1 => $entityTitle,
          2 => $titlePlural,
          'count' => $count,
          'plural' => '%count %2 tagged',
        ]);
        if ($notCount) {
          $status[] = ts('1 %1 already had this tag', [
            1 => $entityTitle,
            2 => $titlePlural,
            'count' => $notCount,
            'plural' => '%count %2 already had this tag',
          ]);
        }
      }
      else {
        $status[] = ts('1 %1 un-tagged', [
          1 => $entityTitle,
          2 => $titlePlural,
          'count' => $count,
          'plural' => '%count %2 un-tagged',
        ]);
        if ($notCount) {
          $status[] = ts('1 %1 already did not have this tag', [
            1 => $entityTitle,
            2 => $titlePlural,
            'count' => $notCount,
            'plural' => '%count %2 already did not have this tag',
          ]);
        }
      }

      $tagLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $tagId, 'label');
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('Tag <em>%1</em>', [1 => $tagLabel]), 'success');
    }
  }

  /**
   * Normalize submitted tag ids from both direct tags and tagsets.
   */
  protected function normalizeSelectedTags(array $params): array {
    $tagList = [];

    if (!empty($params['tag'])) {
      $tagList = array_flip(explode(',', $params['tag']));
    }

    foreach ($params['entity_taglist'] ?? [] as $val) {
      if ($val) {
        if (is_numeric($val)) {
          $tagList[$val] = 1;
        }
        else {
          $tagIDs = explode(',', $val);
          if (!empty($tagIDs)) {
            foreach ($tagIDs as $tagID) {
              if (is_numeric($tagID)) {
                $tagList[$tagID] = 1;
              }
            }
          }
        }
      }
    }

    return array_keys($tagList);
  }

  protected function getOperation(): string {
    $className = get_class($this);
    if (str_ends_with($className, 'AddToTag')) {
      return 'add';
    }
    elseif (str_ends_with($className, 'RemoveFromTag')) {
      return 'remove';
    }
    throw new \Exception("Unknown operation for $className");
  }

}
