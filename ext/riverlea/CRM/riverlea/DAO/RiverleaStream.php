<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 *
 * @property int|string|null $id
 * @property string|null $name
 * @property string $label
 * @property string|null $description
 * @property bool|string $is_reserved
 * @property string|null $extension
 * @property string|null $file_prefix
 * @property string|null $css_file
 * @property string|null $css_file_dark
 * @property string|null $vars
 * @property string|null $vars_dark
 * @property string|null $custom_css
 * @property string|null $custom_css_dark
 * @property string|null $modified_date
 */
class CRM_riverlea_DAO_RiverleaStream extends CRM_riverlea_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_riverlea_stream';

}
