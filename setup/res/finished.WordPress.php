<?php \Civi\Setup::assertRunning(); ?>
<?php
// I don't really understand the behavior here -- seems to spend a lot of work
// building a page, and then it immediately redirects away...

$cmsURL = admin_url('admin.php?page=CiviCRM&q=civicrm/admin/configtask&reset=1');
$wpPermissionsURL = admin_url('admin.php?page=CiviCRM&q=civicrm/admin/access/wp-permissions&reset=1');
$wpInstallRedirect = admin_url('admin.php?page=CiviCRM&q=civicrm&reset=1');
?>
<h1><?php echo ts('CiviCRM Installed'); ?></h1>
<div style="padding: 1em;">
  <p style="background-color: #0C0; border: 1px #070 solid; color: white;">
    <?php echo ts("CiviCRM has been successfully installed"); ?>
  </p>
  <ul>
    <li><?php
      echo ts("WordPress user permissions have been automatically set - giving Anonymous and Subscribers access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(
        1 => "target='_blank' href='{$wpPermissionsURL}'",
        2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'",
      ));
      ?></li>
    <li><?php
    echo ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$cmsURL'"));
    ?></li>
    <?php include 'finished.Common.php'; ?>
  </ul>
</div>
<script>
  window.location = <?php echo json_encode($wpInstallRedirect); ?>;
</script>
