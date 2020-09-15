<?php \Civi\Setup::assertRunning(); ?>
<?php
$drupalPermissionsURL = url('admin/people/permissions');
$drupalURL = url('civicrm/admin/configtask', array(
  'query' => array(
    'reset' => 1,
  ),
));
?>
<div style="padding: 1em;">
  <h1><?php echo ts('CiviCRM Installed'); ?></h1>
  <p class="good"><?php echo ts('CiviCRM has been successfully installed'); ?></p>
  <ul>
    <li><?php echo ts("Drupal user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(
        1 => "target='_blank' href='{$drupalPermissionsURL}'",
        2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'",
      )); ?></li>
    <li><?php echo ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$drupalURL'")); ?></li>
    <?php include 'finished.Common.php'; ?>
  </ul>
</div>
