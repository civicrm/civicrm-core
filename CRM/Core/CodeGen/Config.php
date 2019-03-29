<?php

/**
 * Generate configuration files
 */
class CRM_Core_CodeGen_Config extends CRM_Core_CodeGen_BaseTask {
  public function run() {
    $this->setupCms();
  }

  public function setupCms() {
    if (!in_array($this->config->cms, [
      'backdrop',
      'drupal',
      'drupal8',
      'joomla',
      'wordpress',
    ])) {
      echo "Config file for '{$this->config->cms}' not known.";
      exit();
    }
    elseif ($this->config->cms !== 'joomla') {
      $configTemplate = $this->findConfigTemplate($this->config->cms);
      if ($configTemplate) {
        echo "Generating civicrm.config.php\n";
        copy($configTemplate, '../civicrm.config.php');
      }
      else {
        throw new Exception("Failed to locate template for civicrm.config.php");
      }
    }
  }

  /**
   * @param string $cms
   *   "drupal"|"wordpress".
   * @return null|string
   *   path to config template
   */
  public function findConfigTemplate($cms) {
    $candidates = [];
    switch ($cms) {
      case 'backdrop':
        // FIXME!!!!
        $candidates[] = "../backdrop/civicrm.config.php.backdrop";
        $candidates[] = "../../backdrop/civicrm.config.php.backdrop";
        $candidates[] = "../drupal/civicrm.config.php.backdrop";
        $candidates[] = "../../drupal/civicrm.config.php.backdrop";
        break;

      case 'drupal':
        $candidates[] = "../drupal/civicrm.config.php.drupal";
        $candidates[] = "../../drupal/civicrm.config.php.drupal";
        break;

      case 'drupal8':
        $candidates[] = "../../modules/civicrm/civicrm.config.php.drupal";
        $candidates[] = "../../../modules/civicrm/civicrm.config.php.drupal";
        $candidates[] = "../../../modules/civicrm-drupal/civicrm.config.php.drupal";
        break;

      case 'wordpress':
        $candidates[] = "../../civicrm.config.php.wordpress";
        $candidates[] = "../WordPress/civicrm.config.php.wordpress";
        break;
    }
    foreach ($candidates as $candidate) {
      if (file_exists($candidate)) {
        return $candidate;
        break;
      }
    }
    return NULL;
  }

}
