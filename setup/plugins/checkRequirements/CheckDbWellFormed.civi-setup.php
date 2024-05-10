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

    $dbFields = ['db'];

    if ($e->getModel()->cms !== 'Standalone') {
      $dbFields[] = 'cmsDb';
    }

    foreach ($dbFields as $dbField) {
      $errors = 0;
      $db = $e->getModel()->{$dbField};

      $keys = array_keys($db);
      $requiredKeys = ['server', 'username', 'password', 'database'];
      $allowedKeys = [...$requiredKeys, 'ssl_params', 'password_preset'];

      // are we missing any required keys, or do we have any not allowed keys
      $requiredKeysMissing = array_diff($requiredKeys, $keys);
      if ($requiredKeysMissing) {
        $e->addError('database', $dbField, sprintf("The database credentials for \"%s\" are missing required keys: (%s)",
          $dbField,
          implode(',', $requiredKeysMissing)
        ));
        $errors++;
      }
      $disallowedKeys = array_diff($keys, $allowedKeys);
      if ($disallowedKeys) {
        $e->addError('database', $dbField, sprintf("The database credentials for \"%s\" have extra keys that aren't allowed: (%s)",
          $dbField,
          implode(',', $disallowedKeys)
        ));
        $errors++;
      }


      foreach ($db as $k => $v) {
        if ($k === 'password' && empty($v)) {
          $e->addWarning('database', "$dbField.$k", "The property \"$dbField.$k\" is blank. This may be correct in some controlled environments; it could also be a mistake or a symptom of an insecure configuration.");
        }
        elseif ($k !== 'ssl_params' && !is_scalar($v)) {
          $e->addError('database', "$dbField.$k", "The property \"$dbField.$k\" is not well-formed.");
          $errors++;
        }
      }

      if (0 == $errors) {
        $e->addInfo('database', $dbField, "The database credentials for \"$dbField\" are well-formed.");
      }
    }
  });
