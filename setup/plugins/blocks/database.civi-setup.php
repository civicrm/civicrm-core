<?php
if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Register blocks', basename(__FILE__)));

    /**
     * @var \Civi\Setup\UI\SetupController $ctrl
     */
    $ctrl = $e->getCtrl();


    $ctrl->blocks['database'] = [
      'is_active' => ($e->getModel()->cms === 'Standalone'),
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'database.tpl.php',
      'class' => '',
      'weight' => 15,
    ];
    if (empty($ctrl->blocks['database']['is_active'])) {
      return;
    }

    // in what follows we check for pre-existing database values from the environment
    // and then pass them as defaults to the Web UI
    // EXCEPT for the db password - we don't want to send this over the wire if we don't need to
    // so if we have a password we use a flag to say "we already know the password, you can't enter a new one"
    //
    // I think ideally this would be the behaviour for any component, but with the others we cant tell here
    // if the value we get from AppSettings::get is a real user value, or just a default from AppSettings
    // ( e.g. database = civicrm / port = 3306 ) so we don't know whether to lock it or not
    $host = \Civi\Standalone\AppSettings::get('CIVICRM_DB_HOST');
    $port = \Civi\Standalone\AppSettings::get('CIVICRM_DB_PORT');

    $envPassword = \Civi\Standalone\AppSettings::get('CIVICRM_DB_PASS');

    $webDefault = [
        'server' => "{$host}:{$port}",
        'database' => \Civi\Standalone\AppSettings::get('CIVICRM_DB_NAME'),
        'username' => \Civi\Standalone\AppSettings::get('CIVICRM_DB_USER'),
        'password' => $envPassword ? '[PRESET]' : '',
        'password_preset' => $envPassword ? '1' : '0',
    ];

    if ($e->getMethod() === 'GET') {
      // merge in the environment default password (which isn't included in webDefault)
      $e->getModel()->db = array_merge($webDefault, ['password' => $envPassword]);
    }
    elseif ($e->getMethod() === 'POST') {
      $userEnteredValues = $e->getField('db', $webDefault);

      foreach (['server', 'database', 'username'] as $field) {
        $e->getModel()->db[$field] = $userEnteredValues[$field];
      }
      // if we have a password from the environment, we ignore whatever is submitted through the UI
      // (it should just be [PRESET])
      $e->getModel()->db['password'] = $webDefault['password_preset'] ? $envPassword : $userEnteredValues['password'];
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
