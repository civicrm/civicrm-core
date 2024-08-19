<?php \Civi\Setup::assertRunning(); ?>
<div class="civicrm-setup-body complete">
  <h1><?php echo ts('CiviCRM Installed'); ?></h1>
  <p class="good">
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
