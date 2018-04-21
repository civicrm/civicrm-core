<?php
/**
 * @file
 *
 * Run the PHP+MySQL system requirements checks from Civi\Install\Requirements.
 *
 * Aesthetically, I'd sorta prefer to remove this and (instead) migrate the
 * `Requirements.php` so that each check was its own plugin. But for now this works.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    $model = $e->getModel();
    $r = new \Civi\Install\Requirements();

    \Civi\Setup::log()->info(sprintf('[%s] Run Requirements::checkSystem()', basename(__FILE__)));
    $systemMsgs = $r->checkSystem(array(/* we do this elsewhere */));
    _corereqadapter_addMessages($e, 'system', $systemMsgs);

    \Civi\Setup::log()->info(sprintf('[%s] Run Requirements::checkDatabase()', basename(__FILE__)));
    list ($host, $port) = \Civi\Setup\DbUtil::decodeHostPort($model->db['server']);
    $dbMsgs = $r->checkDatabase(array(
      'host' => $host,
      'port' => $port,
      'username' => $model->db['username'],
      'password' => $model->db['password'],
      'database' => $model->db['database'],
    ));
    _corereqadapter_addMessages($e, 'database', $dbMsgs);
  });

/**
 * @param \Civi\Setup\Event\CheckRequirementsEvent $e
 *   Symbolic machine name for this group of messages.
 *   Ex: 'database' or 'system'.
 * @param array $msgs
 *   A list of messages in the format used by \Civi\Install\Requirements
 */
function _corereqadapter_addMessages($e, $section, $msgs) {
  $severityMap = array(
    \Civi\Install\Requirements::REQUIREMENT_OK => 'info',
    \Civi\Install\Requirements::REQUIREMENT_WARNING => 'warning',
    \Civi\Install\Requirements::REQUIREMENT_ERROR => 'error',
  );

  foreach ($msgs as $msg) {
    $e->addMessage($severityMap[$msg['severity']], $section, $msg['title'], $msg['details']);
  }
}
