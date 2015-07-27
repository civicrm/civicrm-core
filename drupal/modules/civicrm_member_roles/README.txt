// $Id: README.txt,v 1.2.2.2.2.1 2008/09/01 04:43:43 posco Exp $

============================
CiviMember Roles Sync Readme
============================

Requirements
-------------------------------

This module requires CiviCRM 2.1.x or greater and Drupal 6.x. In order to use this module with
other versions of Drupal and CiviCRM, please see the available versions
at http://drupal.org/project/civimember_roles.


Installation Instructions
-------------------------------

To install the CiviMember Roles Sync module, unpack the .tar.gz archive
file and move the `civimember_roles` directory to your sites' `modules` directory.
Then navigate to Administer > Site building > Modules and enable the CiviMember
Roles Sync module.

Refer to the Drupal documentation for further information on how to install modules.


How to Use
-------------------------------

After you have installed CiviMember Roles Sync following the above instructions,
navigate to Administer > Site configuration > CiviMember Roles Sync. If you do not see
this link, make sure you have given yourself permission to see it in the 
Administer > User management > Permissions section of Drupal.

Here you will be able to create "association rules." These rules determine how
CiviMember Roles Sync should synchronize CiviMember Membership Types
to Drupal Roles. Click the "Add Association Rule" tab to add your first rule. Select
the CiviMember Membership Type and Drupal Role you wish to associate and then
select the CiviMember Status Rules you wish to use. Currently, CiviMember Roles
Sync only knows about two different kinds of rules: "Current" and "Expired." Any
CiviMember Status Rule that is assigned to "Current" will be used to determine
if a CiviCRM Contact's Drupal account should be given the role. Any CiviMember
Status Rule that is assigned to "Expired" will be used to determine if a CiviCRM
Contact's Drupal account should have the role removed.  After you have finished
filling out the form, click the "Add association rule" button to add the rule.

Next, click the Configure tab. This form allows you to configure how CiviMember
Roles Sync should automatically synchronize Membership Types to Roles, or if
it should do this automatically at all. If you choose the user login/logout method
you may need to use the "Manually Synchronize" form each time you add an
association rule in order to synchronize all Drupal Users initially. Otherwise, 
each users' roles will be updated on an individual basis each time they login or 
logout. If you select the Drupal cron method, all users' roles will be updated 
periodically each time the Drupal cron function is ran. Please refer to the Drupal 
documentation on how to configure Drupal cron.

User Import
-------------------------------

Each CiviCRM Contact on your site will need a corresponding Drupal User account
in order for CiviMember Roles Sync to synchronize Membership Types to Roles.
Refer to the CiviCRM documentation:

http://wiki.civicrm.org/confluence/display/CRMDOC/Creating+a+Drupal+user+for+every+CiviCRM+contact

When a new Drupal account is created a new CiviCRM Contact will also be
created. So you will only have to worry about the CiviCRM Contacts that you
import into CiviCRM from a source outside of Drupal.


Contact Information
-------------------------------

All feedback and comments of a technical nature (including support questions)
should be posted on the CiviMember Roles Sync project page at http://drupal.org/project/civimember_roles.
For all other comments you can reach me at the following e-mail address. Please
include "CiviMember Roles Sync" somewhere in the subject.

ngoodman AT wisc.edu


License Information
-------------------------------

Copyright (C) Neil Goodman 2010
Released under GNU General Public License version 2 or later
