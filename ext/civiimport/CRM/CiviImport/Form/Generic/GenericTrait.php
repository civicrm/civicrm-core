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
    elseif ($this->getUserJobID() && isset($this->getUserJob()['metadata']['base_entity'])) {
      return $this->getUserJob()['metadata']['base_entity'];
    }
    // We don't try this for import_generic as that import will either get it from the url (below)
    // or have it saved in the metadata already (above).
    elseif ($this->getUserJobID() && $this->getUserJobType() !== 'import_generic') {
      return $this->getParser()->getBaseEntity();
    }
    $pathArguments = explode('/', (CRM_Utils_System::currentPath() ?: ''));
    unset($pathArguments[0], $pathArguments[1]);
    return CRM_Utils_String::convertStringToCamel(implode('_', $pathArguments));
  }

}
