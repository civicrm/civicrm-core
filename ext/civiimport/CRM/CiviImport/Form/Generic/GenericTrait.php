<?php

trait CRM_CiviImport_Form_Generic_GenericTrait {

  /**
   * Get the base entity for the import.
   *
   * @return string
   */
  protected function getBaseEntity(): string {
    if (!$this->isStandalone()) {
      return $this->controller->getStateMachine()->getEntity();
    }
    elseif ($this->getUserJobID()) {
      return $this->getUserJob()['metadata']['base_entity'];
    }
    $pathArguments = explode('/', (CRM_Utils_System::currentPath() ?: ''));
    unset($pathArguments[0], $pathArguments[1]);
    return CRM_Utils_String::convertStringToCamel(implode('_', $pathArguments));
  }

}
