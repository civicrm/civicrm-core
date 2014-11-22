<?php
require dirname(__FILE__) ."/common.php";

$api = new Jira_Api(
    "https://your-jira-project.net",
    new Jira_Api_Authentication_Basic("yourname", "password")
);

/**
 * available options.

 * "description"     => string
 * "userReleaseDate" => YYYY-MM-DD
 * "releaseDate"     => YYYY-MM-DD
 * "released"        => boolean
 * "archived"        => boolean
 *
 * this api will throw an Exceptions when passed invalid options, or already created.
 */
$api->createVersion("YOURPRJOECT", "0.3.1", $options = array());