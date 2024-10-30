<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_BAO_AfformSubmission extends CRM_Afform_DAO_AfformSubmission {

  /**
   * Pseudoconstant callback for `afform_name`
   * @return array
   */
  public static function getAllAfformsByName() {
    $suffixMap = [
      'id' => 'name',
      'name' => 'module_name',
      'abbr' => 'directive_name',
      'label' => 'title',
      'description' => 'description',
      'icon' => 'icon',
      'url' => 'server_route',
    ];
    $afforms = \Civi\Api4\Afform::get(FALSE)
      ->setSelect(array_values($suffixMap))
      ->addOrderBy('title')
      ->execute();
    $result = [];
    foreach ($afforms as $afform) {
      $formattedAfform = [];
      foreach ($suffixMap as $suffix => $field) {
        $formattedAfform[$suffix] = $afform[$field] ?? NULL;
      }
      $result[] = $formattedAfform;
    }
    return $result;
  }

}
