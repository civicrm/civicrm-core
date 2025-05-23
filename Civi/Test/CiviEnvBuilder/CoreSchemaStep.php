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
      $sql = \Civi::schemaHelper()->generateInstallSql();
      \Civi\Test::$statics['core_schema_sql'] = [
        'digest' => md5($sql),
        'content' => $sql,
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
