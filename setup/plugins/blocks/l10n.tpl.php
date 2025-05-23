<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<h2><?php echo ts('Language and Region'); ?></h2>

<p><?php echo ts('CiviCRM can be installed with support for various regions and languages.') . ' ' . ts('The initial configuration of the basic data will also be set to that language (ex: individual prefixes, suffixes, activity types) as well as various settings (ex: date/time/address format, default currency). The settings can be changed later. It is also possible to enable multiple languages later on.') . ' ' . ts('<a href="%1" target="%2">Learn more about using CiviCRM language and region settings.</a>', [1 => 'https://lab.civicrm.org/dev/translation/-/wikis/home', 2 => '_blank']); ?></p>

<script>
  function civicrmInstallerSetLanguage(language) {
    var location = window.location.toString();

    if (location.match(/lang=.._../)) {
      location = location.replace(/lang=.._../, 'lang=' + language);
      window.location = location;
    }
    else {
      window.location += (location.indexOf('?') < 0 ? '?' : '&') + 'lang=' + language;
    }
  }

  <?php
  if ($reqs->isReloadRequired()) {
    // Reload the page so that the new translation is used
    echo "location.reload();";
  }
  ?>
</script>

<p style="margin-left: 2em" id="locale">
  <label for="lang"><span><?php echo ts('Language and Region:'); ?></span></label>
  <select id="lang" name="lang" onchange="civicrmInstallerSetLanguage(this.value);">
    <?php
    // c.f. setup/res/languages.php
    foreach ($model->getField('lang', 'options') as $locale => $language):
      $selected = ($locale == $model->lang) ? 'selected="selected"' : '';
      echo "<option value='$locale' $selected>$language</option>";
    endforeach;
    ?>
  </select>
</span>
</p>
