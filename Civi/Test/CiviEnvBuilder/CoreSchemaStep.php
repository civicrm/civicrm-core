<?php
namespace Civi\Test\CiviEnvBuilder;

/**
 * Class CoreSchemaStep
 * @package Civi\Test\CiviEnvBuilder
 *
 * An initialization step which loads the core SQL schema.
 *
 * Note that the computation of the schema is cached for the duration of
 * the PHP process (\Civi\Test::$statics).
 */
class CoreSchemaStep implements StepInterface {

  /**
   * @return array
   *   - digest: string
   *   - content: string
   */
  public function getSql() {
    if (!isset(\Civi\Test::$statics['core_schema_sql'])) {
      $schema = new \CRM_Core_CodeGen_Schema(\Civi\Test::codeGen());
      $files = $schema->generateCreateSql();
      \Civi\Test::$statics['core_schema_sql'] = [
        'digest' => md5($files['civicrm.mysql']),
        'content' => $files['civicrm.mysql'],
      ];
    }
    return \Civi\Test::$statics['core_schema_sql'];
  }

  public function getSig() {
    return $this->getSql()['digest'];
  }

  public function isValid() {
    return TRUE;
  }

  /**
   * @param \Civi\Test\CiviEnvBuilder $ctx
   */
  public function run($ctx) {
    $sql = $this->getSql();
    if (\Civi\Test::execute($sql['content']) === FALSE) {
      throw new \RuntimeException("Cannot execute SQL");
    }
  }

}
