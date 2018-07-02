<?php
/**
 * @file
 *
 * Verify that the database parameters are well-formed.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));

    $dbFields = array('db', 'cmsDb');
    foreach ($dbFields as $dbField) {
      $errors = 0;
      $db = $e->getModel()->{$dbField};

      $keys = array_keys($db);
      sort($keys);
      $expectedKeys = array('server', 'username', 'password', 'database');
      sort($expectedKeys);
      if ($keys !== $expectedKeys) {
        $e->addError('database', $dbField, sprintf("The database credentials for \"%s\" should be specified as (%s) not (%s)",
          $dbField,
          implode(',', $expectedKeys),
          implode(',', $keys)
        ));
        $errors++;
      }

      foreach ($db as $k => $v) {
        if (!is_scalar($v)) {
          $e->addError('database', "$dbField.$k", "The property \"$dbField.$k\" is not well-formed.");
          $errors++;
        }
      }

      if (0 == $errors) {
        $e->addInfo('database', $dbField, "The database credentials for \"$dbField\" are well-formed.");
      }
    }
  });
