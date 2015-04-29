<?php
/**
 * JSON handling functions for use by releaser script.
 */


/**
 * Analyze the given json data to find the current stable major version string,
 * and print that major version string to STDOUT.
 *
 * @param string $json JSON string from latest.civicrm.org/versions.json
 * @return void
 */
function print_current_stable_version($json) {
  $versions = json_decode($json, TRUE);
  foreach ($versions as $major_version => $version) {
    if ($version['status'] == 'stable') {
      echo $major_version;
      return;
    }
  }
}

/**
 * Add a new release to the given JSON data,
 * and print the modified JSON string to STDOUT.
 *
 * @param string $json JSON string from latest.civicrm.org/versions.json
 * @param string $major_version A major version string, e.g. 1.1
 * @param string $release_properties_json JSON string containing properties
 *  for the new release, as seen in latest.civicrm.org/versions.json
 *  {$major_version:{'releases':[$release_properties_json]}}
 */
function add_release($json, $major_version, $release_properties_json) {
  $versions = json_decode($json, TRUE);
  $release_properties = json_decode($release_properties_json, TRUE);
  if (array_key_exists('security', $release_properties)) {
    mylog($release_properties, '$release_properties');
    if ($release_properties['security'] == 'false') {
      unset($release_properties['security']);
    }
  }
  $versions[$major_version]['releases'][] = $release_properties;
  echo json_encode($versions);
}

/**
 * Modify the status for a given major version in the given JSON data,
 * and print the modified JSON string to STDOUT.
 *
 * @param string $json JSON string from latest.civicrm.org/versions.json
 * @param string $major_version A major version string, e.g. 1.1
 * @param string $status The correct status for the major version, e.g.,
 *  'stable', 'eol'
 */
function update_version_status($json, $major_version, $status) {
  $versions = json_decode($json, TRUE);
  $versions[$major_version]['status'] = $status;
  echo json_encode($versions);
}
