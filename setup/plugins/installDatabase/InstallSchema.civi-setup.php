<?php
/**
 * @file
 *
 * Populate the database schema.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

class InstallSchemaPlugin implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.setup.checkRequirements' => [
        ['checkXmlFiles', 0],
        ['checkSqlFiles', 0],
      ],
      'civi.setup.installDatabase' => [
        ['installDatabase', 0],
      ],
    ];
  }

  public function checkXmlFiles(\Civi\Setup\Event\CheckRequirementsEvent $e) {
    $m = $e->getModel();
    $files = array(
      'xmlMissing' => implode(DIRECTORY_SEPARATOR, [$m->srcPath, 'xml']),
      'xmlSchemaMissing' => implode(DIRECTORY_SEPARATOR, [$m->srcPath, 'xml', 'schema', 'Schema.xml']),
      'xmlVersionMissing' => implode(DIRECTORY_SEPARATOR, [$m->srcPath, 'xml', 'version.xml']),
    );

    foreach ($files as $key => $file) {
      if (!file_exists($file)) {
        $e->addError('system', $key, "Schema file is missing: \"$file\"");
      }
    }
  }

  public function checkSqlFiles(\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));
    $seedLanguage = $e->getModel()->lang;
    $sqlPath = $e->getModel()->srcPath . DIRECTORY_SEPARATOR . 'sql';

    if (!$seedLanguage || $seedLanguage === 'en_US') {
      $e->addInfo('system', 'lang', "Default language is allowed");
      return;
    }

    if (!preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $seedLanguage)) {
      $e->addError('system', 'langMalformed', 'Language name is malformed.');
      return;
    }

    if (!file_exists($e->getModel()->settingsPath)) {
      $e->addError('system', 'settingsPath', sprintf('The CiviCRM setting file is missing.'));
    }

    $e->addInfo('system', 'lang', "Language $seedLanguage is allowed.");
  }

  public function installDatabase(\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Install database schema', basename(__FILE__)));

    $model = $e->getModel();

    $sqlPath = $model->srcPath . DIRECTORY_SEPARATOR . 'sql';
    $spec = $this->loadSpecification($model->srcPath);

    $conn = \Civi\Setup\DbUtil::connect($model->db);
    \CRM_Core_I18n::$SQL_ESCAPER = function($text) use ($conn) {
      return $conn->escape_string($text);
    };

    \Civi\Setup::log()->info(sprintf('[%s] Load basic tables', basename(__FILE__)));
    \Civi\Setup\DbUtil::sourceSQL($model->db, \Civi\Setup\SchemaGenerator::generateCreateSql($model->srcPath, $spec->database, $spec->tables));

    $seedLanguage = $model->lang;
    if (!empty($model->loadGenerated)) {
      \Civi\Setup::log()->info(sprintf('[%s] Load sample data', basename(__FILE__)));
      // At time of writing, `generateSampleData()` is not yet a full replacement for `civicrm_generated.mysql`.
      \Civi\Setup\DbUtil::sourceSQL($model->db, file_get_contents($sqlPath . DIRECTORY_SEPARATOR . 'civicrm_generated.mysql'));
      // \Civi\Setup\DbUtil::sourceSQL($model->db, \Civi\Setup\SchemaGenerator::generateSampleData($model->srcPath));
    }
    elseif ($seedLanguage) {
      global $tsLocale;
      $tsLocale = $seedLanguage;
      \Civi\Setup::log()->info(sprintf('[%s] Load basic data', basename(__FILE__)));
      \Civi\Setup\DbUtil::sourceSQL($model->db, \Civi\Setup\SchemaGenerator::generateBasicData($model->srcPath));
    }

    require_once $model->settingsPath;
    \Civi\Core\Container::boot(TRUE);

    \CRM_Core_I18n::$SQL_ESCAPER = NULL;
  }

  /**
   * @param string $srcPath
   * @return \CRM_Core_CodeGen_Specification
   */
  protected function loadSpecification($srcPath) {
    $schemaFile = implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'schema', 'Schema.xml']);
    $versionFile = implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'version.xml']);
    $xmlBuilt = \CRM_Core_CodeGen_Util_Xml::parse($versionFile);
    $buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $xmlBuilt->version_no);
    $specification = new \CRM_Core_CodeGen_Specification();
    $specification->parse($schemaFile, $buildVersion, FALSE);
    return $specification;
  }

}

\Civi\Setup::dispatcher()->addSubscriber(new InstallSchemaPlugin());
