<?php
require dirname(__FILE__) ."/common.php";

$api = new Jira_Api(
    "https://your-jira-project.net",
    new Jira_Api_Authentication_Basic("yourname", "password")
);

$api->editIssue($key, array(
    "fields" => array(
        "<FieldID>" => "Value"
    ),
));
