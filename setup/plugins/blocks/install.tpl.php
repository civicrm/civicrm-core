<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<div class="action-box">
  <button id="install_button" type="submit" name="civisetup[action][Install]"
         onclick="document.getElementById('saving_top').style.display = ''; this.innerHTML = '<?php echo ts('Installing CiviCRM...', array('escape' => 'js')); ?>'"><?php echo htmlentities(ts('Install CiviCRM')); ?></button>
  <div id="saving_top" style="display: none;">
&nbsp;   <img src="<?php echo htmlentities($installURLPath . "network-save.gif") ?>"/>
    <?php echo ts('(this will take a few minutes)'); ?>
  </div>
</div>
