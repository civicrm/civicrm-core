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

namespace Civi\Afform;

/**
 * Class AfformMetadataInjector
 * @package Civi\Afform
 */
class AfformMetadataInjector {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('fieldMetadata')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        try {
          $module = \Civi::service('angular')->getModule(basename($path, '.aff.html'));
          $meta = \Civi\Api4\Afform::get()->addWhere('name', '=', $module['_afform'])->setSelect(['join', 'block'])->setCheckPermissions(FALSE)->execute()->first();
        }
        catch (\Exception $e) {
        }

        $blockEntity = $meta['join'] ?? $meta['block'] ?? NULL;
        if (!$blockEntity) {
          $entities = self::getFormEntities($doc);
        }

        // Each field can be nested within a fieldset, a join or a block
        foreach (pq('af-field', $doc) as $afField) {
          /** @var \DOMElement $afField */
          $action = 'create';
          $joinName = pq($afField)->parents('[af-join]')->attr('af-join');
          if ($joinName) {
            self::fillFieldMetadata($joinName, $action, $afField);
            continue;
          }
          if ($blockEntity) {
            self::fillFieldMetadata($blockEntity, $action, $afField);
            continue;
          }
          // Not a block or a join, get metadata from fieldset
          $fieldset = pq($afField)->parents('[af-fieldset]');
          $apiEntities = pq($fieldset)->attr('api-entities');
          // If this fieldset is standalone (not linked to an af-entity) it is for get rather than create
          if ($apiEntities) {
            $action = 'get';
            $entityType = self::getFieldEntityType($afField->getAttribute('name'), \CRM_Utils_JS::decode($apiEntities));
          }
          else {
            $entityName = pq($fieldset)->attr('af-fieldset');
            if (!preg_match(';^[a-zA-Z0-9\_\-\. ]+$;', $entityName)) {
              \Civi::log()->error("Afform error: cannot process $path: malformed entity name ($entityName)");
              return;
            }
            $entityType = $entities[$entityName]['type'];
          }
          self::fillFieldMetadata($entityType, $action, $afField);
        }
      });
    $e->angular->add($changeSet);
  }

  /**
   * Merge field definition metadata into an afform field's definition
   *
   * @param string $entityType
   * @param string $action
   * @param \DOMElement $afField
   * @throws \API_Exception
   */
  private static function fillFieldMetadata($entityType, $action, \DOMElement $afField) {
    $fieldName = $afField->getAttribute('name');
    if (strpos($entityType, ' AS ')) {
      [$entityType, $alias] = explode(' AS ', $entityType);
      $fieldName = preg_replace('/^' . preg_quote($alias . '.', '/') . '/', '', $fieldName);
    }
    $params = [
      'action' => $action,
      'where' => [['name', '=', $fieldName]],
      'select' => ['label', 'input_type', 'input_attrs', 'options'],
      'loadOptions' => ['id', 'label'],
    ];
    if (in_array($entityType, \CRM_Contact_BAO_ContactType::basicTypes(TRUE))) {
      $params['values'] = ['contact_type' => $entityType];
      $entityType = 'Contact';
    }
    // Merge field definition data with whatever's already in the markup.
    // If the admin has chosen to include this field on the form, then it's OK for us to get metadata about the field - regardless of user's other permissions.
    $getFields = civicrm_api4($entityType, 'getFields', $params + ['checkPermissions' => FALSE]);
    $deep = ['input_attrs'];
    foreach ($getFields as $fieldInfo) {
      $existingFieldDefn = trim(pq($afField)->attr('defn') ?: '');
      if ($existingFieldDefn && $existingFieldDefn[0] != '{') {
        // If it's not an object, don't mess with it.
        continue;
      }
      // Default placeholder for select inputs
      if ($fieldInfo['input_type'] === 'Select') {
        $fieldInfo['input_attrs'] = ($fieldInfo['input_attrs'] ?? []) + ['placeholder' => ts('Select')];
      }

      $fieldDefn = $existingFieldDefn ? \CRM_Utils_JS::getRawProps($existingFieldDefn) : [];
      foreach ($fieldInfo as $name => $prop) {
        // Merge array props 1 level deep
        if (in_array($name, $deep) && !empty($fieldDefn[$name])) {
          $fieldDefn[$name] = \CRM_Utils_JS::writeObject(\CRM_Utils_JS::getRawProps($fieldDefn[$name]) + array_map(['\CRM_Utils_JS', 'encode'], $prop));
        }
        elseif (!isset($fieldDefn[$name])) {
          $fieldDefn[$name] = \CRM_Utils_JS::encode($prop);
        }
      }
      pq($afField)->attr('defn', htmlspecialchars(\CRM_Utils_JS::writeObject($fieldDefn)));
    }
  }

  /**
   * @param string $fieldName
   * @param string[] $entityList
   * @return string
   */
  private static function getFieldEntityType($fieldName, $entityList) {
    $prefix = strpos($fieldName, '.') ? explode('.', $fieldName)[0] : NULL;
    $baseEntity = array_shift($entityList);
    if ($prefix) {
      foreach ($entityList as $entityAndAlias) {
        [$entity, $alias] = explode(' AS ', $entityAndAlias);
        if ($alias === $prefix) {
          return $entityAndAlias;
        }
      }
    }
    return $baseEntity;
  }

  private static function getFormEntities(\phpQueryObject $doc) {
    $entities = [];
    foreach ($doc->find('af-entity') as $afmModelProp) {
      $entities[$afmModelProp->getAttribute('name')] = [
        'type' => $afmModelProp->getAttribute('type'),
      ];
    }
    return $entities;
  }

}
