<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<h2><?php echo ts('Localization'); ?></h2>

<p><?php echo ts('CiviCRM has been translated to many languages, thanks to its community of translators. By selecting another language, the installer may be available in that language. The initial configuration of the basic data will also be set to that language (ex: individual prefixes, suffixes, activity types, etc.). <a href="%1" target="%2">Learn more about using CiviCRM in other languages.</a>', array(1 => 'http://wiki.civicrm.org/confluence/pages/viewpage.action?pageId=88408149', 2 => '_blank')); ?></p>

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
</script>

<p style="margin-left: 2em" id="locale">
  <label for="lang"><span><?php echo ts('Language of basic data:'); ?></span></label>
  <select id="lang" name="lang" onchange="civicrmInstallerSetLanguage(this.value);">
    <?php
    foreach ($model->getField('lang', 'options') as $locale => $language):
      $selected = ($locale == $model->lang) ? 'selected="selected"' : '';
      echo "<option value='$locale' $selected>$language</option>";
    endforeach;
    ?>
  </select>

  <span class="advancedTip">
  <?php
  if (count($model->getField('lang', 'options')) < 2):
    echo "(download the civicrm-{$civicrm_version}-l10n.tar.gz file and unzip into CiviCRM’s directory to add languages here)";
  endif;
  ?>
</span>
</p>
