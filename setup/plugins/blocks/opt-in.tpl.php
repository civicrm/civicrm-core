<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<h2 id="settings"><?php echo ts('Opt-in'); ?></h2>

<p>
  <?php echo ts('CiviCRM is provided for free -- built with donations and volunteerism. We don\'t have the marketing or data-analytics prowess of a large corporation, so we rely on users to keep us informed -- and to spread the word.'); ?>
  <?php echo ts('Of course, not everyone can help in these ways. But if you can, opt-in to help enrich the product and community.'); ?>
</p>

<div>
  <input class="optin-cb sr-only" style="display: none;" type="checkbox" name="civisetup[opt-in][versionCheck]" id="civisetup[opt-in][versionCheck]" value="1" <?php echo $model->extras['opt-in']['versionCheck'] ? 'checked' : ''; ?>>
  <label class="optin-box" for="civisetup[opt-in][versionCheck]">
    <span class="optin-label"><?php echo ts('Version pingback'); ?></span>
    <span class="optin-desc"><?php echo ts('Check for CiviCRM version updates. Report anonymous usage statistics.'); ?></span>
  </label>

  <input class="optin-cb sr-only" style="display: none;" type="checkbox" name="civisetup[opt-in][empoweredBy]" id="civisetup[opt-in][empoweredBy]" value="1" <?php echo $model->extras['opt-in']['empoweredBy'] ? 'checked' : ''; ?>>
  <label class="optin-box" for="civisetup[opt-in][empoweredBy]">
    <span class="optin-label"><?php echo ts('Empowered by CiviCRM'); ?></span>
    <span class="optin-desc"><?php echo ts('Display "Empowered by CiviCRM" in the footer of public forms.'); ?></span>
  </label>
</div>
