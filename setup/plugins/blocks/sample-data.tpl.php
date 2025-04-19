<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<h2><?php echo ts('Sample Data'); ?></h2>

<p>
  <label for="loadGenerated"><span><?php echo ts('Test data'); ?></span> <input id="loadGenerated" type="checkbox" name="civisetup[loadGenerated]" value=1 <?php echo $model->loadGenerated ? "checked='checked'" : ""; ?> /></label> <br />
  <span class="advancedTip"><?php echo ts("Fill the database with randomly-generated contacts, contributions, activities, etc. Only availble for English (United-States). Mainly used for demo and testing sites, not for real installations."); ?></span><br />
</p>

<script type="text/javascript">
  csj$(function($) {
    function hideLang() {
      if ($('[name=lang]').val() === 'en_US') {
        $('#loadGenerated').prop('disabled', false);
      }
      else {
        $('#loadGenerated').prop('disabled', true).prop('checked', false);
      }
      setTimeout(hideLang, 100);
    }
    setTimeout(hideLang, 100);
  });
</script>
