To run the tests you need to configure it as described in the wiki:

http://wiki.civicrm.org/confluence/display/CRM/Testing

The commands to run the tests include:

$ cd SVNROOT/tools
$ scripts/phpunit -u db_username -pdb_password -h db_host api_v2_ContactTest
$ scripts/phpunit -u db_username -pdb_password -h db_host WebTest_Contact_AddTest
$ scripts/phpunit -u db_username -pdb_password -h db_host WebTest_AllTests
$ scripts/phpunit -u db_username -pdb_password -h db_host api_v3_AllTests

You can also specify database name using "-bdb_name" option. If database name is not specified it will
use "civicrm_tests_dev" as default db for all the tests.


