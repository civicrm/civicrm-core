<?php

namespace Civi\Api4\Utils;

use CRM_Afform_ExtensionUtil as E;

/**
 * Class AfformSaveTrait
 * @package Civi\Api4\Action\Afform
 */
trait AfformTranslateTrait {

  use AfformFormatTrait;

  /**
   * Save Translations for Afform
   * array $item
   */
  protected function saveTranslations($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    // If no name is given thrown an error

    if (empty($item['name'])) {
      throw new \CRM_Core_Exception("Afform.{$this->getActionName()}: No name provided the form should be saved first.");
    }
    else {
      // Fetch existing metadata
      //      $fields = \Civi\Api4\Afform::getfields()->setCheckPermissions(FALSE)->setAction('create')->addSelect('name')->execute()->column('name');
      //      unset($fields[array_search('layout', $fields)]);

      //      $orig = \Civi\Api4\Afform::get()->setCheckPermissions(FALSE)->addWhere('name', '=', $item['name'])->setSelect($fields)->execute()->first();
      $form = \Civi\Api4\Afform::get()->setCheckPermissions(FALSE)->addWhere('name', '=', $item['name'])->execute()->first();
      Civi::log()->warning('orig ' . var_export($form, TRUE));

      // Translate the main title
      if (!empty($form['title'])) {

      }

      // Translate entire afform
      $sourceStrings = $this->getFieldTranslations($form['layout']);
      $this->saveFieldTranslations($sourceStrings);
    }

  }

  /**
   * Get Field Source Strings
   * array $fields
   *
   * @return array
   */
  protected function getFieldTranslations($fields) {
    if (empty($fields)) {
      throw new \CRM_Core_Exception("Afform.{$this->getActionName()}: No fields provided.");
    }

    $translateStrings = [];

    // Loop through passed fields
    foreach ($fields as $field) {
      if (!empty($field['#children'])) {
        $childrenStrings = $this->getFieldTranslations($field['#children']);
        $translateStrings = array_merge($translateStrings, $childrenStrings);
      }
      else {
        if (!empty($field['label'])) {
          $translateStrings[]['source'] = $field['label'];
        }
        elseif (!empty($field['af-title'])) {
          $translateStrings[]['source'] = $field['af-title'];
        }
        elseif (!empty($field['#text'])) {
          $translateStrings[]['source'] = $field['#text'];
        }
      }
    }

    $translateStrings = array_unique($translateStrings);
    return $translateStrings;
  }

  /**
   * Save Field Source Strings
   * array $strings
   */
  protected function saveSourceTranslations($strings) {
    if (empty($strings)) {
      throw new \CRM_Core_Exception("Afform.{$this->getActionName()}: No strings provided.");
    }

    \Civi\Api4\TranslationSource::save()
      ->addRecord(...$strings)
      ->setMatch([
        'source',
      ])
      ->execute();
  }

}
