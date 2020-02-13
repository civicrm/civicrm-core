This file documents all the changes made on top of core 5.22 by Australian Greens  

| Commit#    | Commit information and case info                                                                                 | Current Case Info                                                |
| ---------- | ---------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| 5dfa365    | Redmine-9013 Fix issue where `mail_report` cron returns a copy of the html of the report even when not requested | Unlikely to be fixed soon                                        |
| cdf9c82    | Atrium 4516/Redmine 11737 Stop ACL Cache from being cleared                                                      | No upstream fix and unlikely to be soon                          |
| 8a60827    | Add in AUG Change log                                                                                            | Specific local Changes                                           |
| 565e42f    | CRM-19835 port of PR 9801                                                                                        | No sign its being merged in core soon                            |
| 0ad6579    | Add in `settings_location.php` file                                                                              | Not going to be fixed in Core                                    |
| d7a9675    | Update composer.json and remove composer.lock as is required for composer in docker                              | Not going to be fixed local changes needed to support AUG docker |
| b99343a    | dev/core/#273: Set doNotSms To False When Phone Number Is Given                                                  | Need to check against core pr to determine if still needed       |
| f3152ba    | dev/drupal#98 Fix masquerade issue caused by drupal update change                                                | Looks like it will be coming with CiviCRM 5.23                   |
| 06f34a9    | dev/core#1489 Include hook_civicrm_managed into the list of upgrade friendly hooks                               | Looks to be merged soon, possibly prior to CiviCRM 5.23          |
