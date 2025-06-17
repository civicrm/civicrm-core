<?php
use CRM_riverlea_ExtensionUtil as E;

class CRM_riverlea_Page_RiverleaStreams extends CRM_Core_Page {

  public function run() {
    // TODO: create a bundle? OR a bundler for webcomponents?
    $resources = \Civi::resources();
    $resources->addScriptFile(E::SHORT_NAME, 'js/utils.js', ['weight' => 100]);
    $resources->addScriptFile(E::SHORT_NAME, 'js/editor.js', ['weight' => 200]);
    $resources->addScriptFile(E::SHORT_NAME, 'js/stream-list.js', ['weight' => 200]);
    $resources->addStyleFile(E::SHORT_NAME, 'css/stream-list.css');

    if (!_riverlea_is_active()) {
      \CRM_Core_Session::setStatus(E::ts('You must first activate a Riverlea stream to be able to preview others'), ts('Stream Previews'), 'warning');
    }

    parent::run();
  }

}
