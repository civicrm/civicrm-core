<?php
/**
 * @file
 *
 * Cleanup any CiviCRM session state after uninstallation.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.uninstallDatabase', function (\Civi\Setup\Event\UninstallDatabaseEvent $e) {
    $supportedCms = array('Drupal', 'Backdrop');
    if (!in_array($e->getModel()->cms, $supportedCms)) {
      return;
    }
    \Civi\Setup::log()->info('[CleanupDrupalSession.civi-setup.php] Purge Drupal session state which have stale CiviCRM references');

    // This keeps the Drupal user logged in, but it purges any data.

    // It's a bit ham-handed, but no one provides an API like this, and a
    // more surgical approach would get messy (due to variations of session-encoding),
    // and... it seems to work...

    db_query('UPDATE sessions SET session = NULL');

    //    foreach(db_query('SELECT sid FROM sessions') as $sid) {
    //      $sessionResult = db_query('SELECT session FROM sessions WHERE sid = :sid', array(
    //        'sid' => $sid->sid,
    //      ));
    //      foreach ($sessionResult as $session) {
    //        $data = session_decode($session->session); // blerg, nothign does this right :(
    //        print_r(['sr'=>$session, 'data'=>$data]);
    //        if (!empty($data['CiviCRM'])) {
    //          echo "must clear " . $sid->sid . "\n";
    //          unset($data['CiviCRM']);
    //          reserialize and write back to DB
    //        }
    //        else {
    //          echo "ignore " . $sid->sid . "\n";
    //        }
    //
    //      }
    //    }
  });
