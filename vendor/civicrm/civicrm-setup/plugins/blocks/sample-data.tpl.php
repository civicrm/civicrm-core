<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<h2><?php echo ts('Sample Data'); ?></h2>

<p>
  <label for="loadGenerated"><span>Load sample data:</span><input id="loadGenerated" type="checkbox" name="civisetup[loadGenerated]" value=1 <?php echo $model->loadGenerated ? "checked='checked'" : ""; ?> /></label> <br />
  <span class="advancedTip"><?php echo ts("Check this box to pre-populate CiviCRM with sample English contact records, online contribution pages, profile forms, etc. These examples can help you learn about CiviCRM features."); ?></span><br />
</p>

<script type="text/javascript">
  csj$(function(){
    function hideLang() {
      if (csj$('[name=lang]').val() == 'en_US') {
        csj$('#loadGenerated').prop('disabled', false);
      }
      else {
        csj$('#loadGenerated').prop('disabled', true).prop('checked', false);
      }
      setTimeout(hideLang, 100);
    }
    setTimeout(hideLang, 100);
  });
</script>
