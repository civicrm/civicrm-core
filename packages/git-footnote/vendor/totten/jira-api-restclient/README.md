# Jira Api Rest Client

you know JIRA5 supports REST API. this is very useful to make some custom notifications and automated jobs.
(JIRA also supports email notification, but it's too much to custom templates, notification timing. unfortunately it requires Administration permission.)
this API library will help your problems regarding JIRA. hope you enjoy it.

# Usage

````php
<?php
require "Jira/Autoloader.php";
Jira_Autoloader::register();

$api = new Jira_Api(
    "https://your-jira-project.net",
    new Jira_Api_Authentication_Basic("yourname", "password")
);

$walker = new Jira_Issues_Walker($api);
$walker->push("project = YOURPROJECT AND (status != closed and status != resolved) ORDER BY priority DESC");
foreach ($walker as $issue) {
    var_dump($result);
    // send custom notification here.
}
````

# License

MIT License

# JIRA5 Rest API Documents

https://developer.atlassian.com/static/rest/jira/5.0.html
