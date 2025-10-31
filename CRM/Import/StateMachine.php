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
 * State machine for managing different states of the Import process.
 *
 * @internal
 */
class CRM_Import_StateMachine extends CRM_Core_StateMachine {

  /**
   * Get the entity name.
   *
   * @var string
   */
  protected string $entity;

  private string $classPrefix;

  public function getEntity(): string {
    return $this->entity;
  }

  /**
   * Class constructor.
   *
   * @param CRM_Import_Controller $controller
   * @param int $action
   * @param ?string $entity
   * @param string|null $classPrefix
   *   When the class name does not easily map to the prefix - ie the Custom import class.
   *
   * @internal only supported for core use.
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE, ?string $entity = NULL, ?string $classPrefix = NULL) {
    parent::__construct($controller, $action);
    $this->entity = ucfirst((string) $entity);
    if ($classPrefix) {
      $this->classPrefix = $classPrefix;
    }
    elseif ($this->entity) {
      $entityName = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($this->entity);
      if (!$entityName) {
        throw new CRM_Core_Exception(ts('Invalid import entity %1', [1 => htmlentities($this->entity)]));
      }
      $entityPath = explode('_', $entityName);
      if ($this->entity === 'Event'
        || ($entityPath[1] === 'Contact' && $entity !== 'Contact')
      ) {
        // If we allow this to be set it finds the participant import...
        $this->classPrefix = '';
      }
      else {
        $this->classPrefix = $entityPath[0] . '_' . $entityPath[1] . '_Import';
      }
    }
    else {
      CRM_Core_Error::deprecatedWarning('entity parameter expected, always passed in core & few outside core uses so this will go');
      $this->classPrefix = str_replace('_Controller', '', get_class($controller));
    }
    $this->_pages = [
      $this->getDataSourceFormName() => NULL,
      $this->getMapFieldFormName() => NULL,
      $this->getPreviewFormName() => NULL,
    ];
    $this->addSequentialPages($this->_pages);
  }

  private function getDataSourceFormName(): string {
    return class_exists($this->classPrefix . '_Form_DataSource') ? $this->classPrefix . '_Form_DataSource' : 'CRM_CiviImport_Form_Generic_DataSource';
  }

  private function getMapFieldFormName(): string {
    return class_exists($this->classPrefix . '_Form_MapField') ? $this->classPrefix . '_Form_MapField' : 'CRM_CiviImport_Form_Generic_MapField';
  }

  private function getPreviewFormName(): string {
    return class_exists($this->classPrefix . '_Form_Preview') ? $this->classPrefix . '_Form_Preview' : 'CRM_CiviImport_Form_Generic_Preview';
  }

}
