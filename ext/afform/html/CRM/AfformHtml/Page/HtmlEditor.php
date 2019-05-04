<?php
use CRM_AfformHtml_ExtensionUtil as E;

class CRM_AfformHtml_Page_HtmlEditor extends CRM_Core_Page {

  const MONACO_DIR = 'node_modules/monaco-editor/min/vs';

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Afform HTML Editor'));

    CRM_Core_Region::instance('html-header')->add([
      'markup' => '<meta http-equiv="X-UA-Compatible" content="IE=edge" />',
    ]);
    Civi::resources()
      ->addVars('afform_html', [
        'paths' => [
          'vs' => E::url(self::MONACO_DIR),
        ],
      ])
      ->addScriptFile(E::LONG_NAME, self::MONACO_DIR . '/loader.js', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');

    parent::run();
  }

}
