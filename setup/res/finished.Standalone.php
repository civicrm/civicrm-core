<?php \Civi\Setup::assertRunning(); ?>
<h1><?php echo ts('CiviCRM Installed'); ?></h1>
<div style="padding: 1em;">
  <p style="background-color: #0C0; border: 1px #070 solid; color: white;">
    <?php echo ts("CiviCRM has been successfully installed"); ?>
  </p>
  <ul>
    <li><?php
    $cmsURL = '/civicrm/admin/configtask&reset=1';
    echo ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", [1 => "target='_blank' href='$cmsURL'"]);
    ?></li>
    <?php include 'finished.Common.php'; ?>
  </ul>

  <p><a href="/"><?php echo ts("Continue to the CiviCRM Dashboard"); ?></a></p>
</div>
