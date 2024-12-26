<?php

/**
 * Upgrader base class ONLY for core component extensions (e.g. `civi_mail`, `civi_event`).
 *
 * Can be used directly from the extensions' info.xml or can be extended for
 * additional install/uninstall/upgrade functionality in the extension.
 */
class CRM_Extension_Upgrader_Component extends CRM_Extension_Upgrader_Base {

  public function postInstall() {
    CRM_Core_BAO_ConfigSetting::enableComponent($this->getComponentName());
  }

  public function enable() {
    CRM_Core_BAO_ConfigSetting::enableComponent($this->getComponentName());
  }

  public function disable() {
    CRM_Core_BAO_ConfigSetting::disableComponent($this->getComponentName());
  }

  /**
   * Get name of component corresponding to this extension (e.g. CiviMail)
   *
   * @return string
   */
  protected function getComponentName(): string {
    return CRM_Utils_String::convertStringToCamel($this->extensionName);
  }

}
