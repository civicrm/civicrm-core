# Release Notes

These release notes are manually compiled from pull requests and Jira issues
starting with CiviCRM 4.7.14.

## CiviCRM 4.7.14

Released December 2016

### Features

#### Core CiviCRM

- **CRM_Utils_Check - Suggest using `[cms.root]`, etal
  ([8466](https://github.com/civicrm/civicrm-core/pull/8466))**

  Add a system check to see if directories and resource URLs are using the new
  path tokens—and report a message if not.

- **[CRM-19533](https://issues.civicrm.org/jira/browse/CRM-19533) System check
  to see if important folders are writable
  ([9285](https://github.com/civicrm/civicrm-core/pull/9285))**

  If CiviCRM can’t write to certain important folders, a system check message
  should appear.

- **[CRM-19463](https://issues.civicrm.org/jira/browse/CRM-19463) Get
  E2E_AllTests working on php7
  ([9268](https://github.com/civicrm/civicrm-core/pull/9268))**

  Responses from SOAP requests to the API should be encoded properly to be
  compatible with PHP 7

- **[CRM-19494](https://issues.civicrm.org/jira/browse/CRM-19494) Refactoring of
  permission code ([9246](https://github.com/civicrm/civicrm-core/pull/9246))**

  Improve performance of contact view/edit permissions

#### CiviCase

- **[CRM-19552](https://issues.civicrm.org/jira/browse/CRM-19552) Case API may
  throw SQL errors when case_id not provided
  ([9308](https://github.com/civicrm/civicrm-core/pull/9308))**

  The Case.update API will accept the `id` parameter as case ID if `case_id` is
  missing.

#### Helper Scripts

- **bin/givi - Add backdrop support
  ([8944](https://github.com/civicrm/civicrm-core/pull/8944))**

  Support Backdrop in the givi script.

### Bugs

#### Core CiviCRM

- **[CRM-19472](https://issues.civicrm.org/jira/browse/CRM-19472) Export headers
  for relationships are in machine name format
  ([9187](https://github.com/civicrm/civicrm-core/pull/9187))**

  Fixed problem where relationship type labels were not displaying correctly in
  export files

- **[CRM-19380](https://issues.civicrm.org/jira/browse/CRM-19380) Allow for
  multiple from email addresses but only one per domain
  ([9066](https://github.com/civicrm/civicrm-core/pull/9066))**

- **[CRM-19122](https://issues.civicrm.org/jira/browse/CRM-19122) Group
  Organization & parent default code should be the same
  ([8751](https://github.com/civicrm/civicrm-core/pull/8751))**

  In a multisite instance of CiviCRM, you should be able to set the group
  organization for smart groups as well as static groups.

- **[CRM-19471](https://issues.civicrm.org/jira/browse/CRM-19471) Custom
  relationships for custom contact types not available during export
  ([9259](https://github.com/civicrm/civicrm-core/pull/9259))**

  Fixed problem where related contacts, related via relationship types specific
  to contact subtypes, were not available in the export screen.

- **[CRM-19079](https://issues.civicrm.org/jira/browse/CRM-19079) Profile edit
  permission checks bypass standard route in WP
  ([8707](https://github.com/civicrm/civicrm-core/pull/8707))**

  Fixed problem in WordPress where the normal permission checks and hooks were
  bypassed on profiles in edit mode.

- **[CRM-19490](https://issues.civicrm.org/jira/browse/CRM-19490) Add a “short
  date” format setting to allow for localized display of dates in profile fields
  ([9253](https://github.com/civicrm/civicrm-core/pull/9253))**

  When date fields appear in profiles on the confirmation page of contribution
  pages, they should show the date in the localized format.

- **[CRM-17616](https://issues.civicrm.org/jira/browse/CRM-17616) Moving to an
  arbitrary search page result could lead to incomplete results
  ([9266](https://github.com/civicrm/civicrm-core/pull/9266))**

  When viewing hundreds of rows in search results, a cache is kept of the next
  several hundred rows; this cache should be filled and sized according to the
  page being viewed.

- **Minor comment fix
  ([9269](https://github.com/civicrm/civicrm-core/pull/9269))**

- **[CRM-19511](https://issues.civicrm.org/jira/browse/CRM-19511) Disabled
  fields still visible in "Import Multi-value Custom Data"
  ([9274](https://github.com/civicrm/civicrm-core/pull/9274))**

  When importing multi-value custom data, disabled custom fields should not be
  available for import.

- **[CRM-19512](https://issues.civicrm.org/jira/browse/CRM-19512) Ensure that
  language param is always passed in for navigation script url
  ([9280](https://github.com/civicrm/civicrm-core/pull/9280))**

  When getting a locale, there should always be a result; `en_US` is the
  fallback.

- **[CRM-19528](https://issues.civicrm.org/jira/browse/CRM-19528)
  Internationalise "Select Code" on contributions page widget tab
  ([9282](https://github.com/civicrm/civicrm-core/pull/9282))**

  The US English words “select code” on the contribution page widget should be
  translated.

- **[CRM-19313](https://issues.civicrm.org/jira/browse/CRM-19313) Can't assign
  custom group to relationships with two contact subtypes involved
  ([9287](https://github.com/civicrm/civicrm-core/pull/9287) and
  [9328](https://github.com/civicrm/civicrm-core/pull/9328))**

- **[CRM-19529](https://issues.civicrm.org/jira/browse/CRM-19529)
  Upcoming/Recent Case Activities results into "Network Error" in PHP 7
  ([9283](https://github.com/civicrm/civicrm-core/pull/9283))**

- **[CRM-18953](https://issues.civicrm.org/jira/browse/CRM-18953) Better cleanup
  of news widget markup
  ([9289](https://github.com/civicrm/civicrm-core/pull/9289))**

  Formatting tags and style should be stripped out of news items in the CiviCRM
  News dashlet

- **[CRM-19513](https://issues.civicrm.org/jira/browse/CRM-19513) Saved search
  is incorrectly using IN rather than BETWEEN for custom fields for civicrm
  group cache ([9284](https://github.com/civicrm/civicrm-core/pull/9284))**

  A smart group based upon a search by range should include the whole range, not
  just the extremes.

- **[CRM-19540](https://issues.civicrm.org/jira/browse/CRM-19540) UFGroup API
  does not respect name parameter
  ([9295](https://github.com/civicrm/civicrm-core/pull/9295))**

  Creating a profile through the API should allow you to specify a machine name
  rather than have it generated from the title

- **[CRM-19541](https://issues.civicrm.org/jira/browse/CRM-19541) Custom Date
  Range saved search doesn't sets default values to the input
  ([9297](https://github.com/civicrm/civicrm-core/pull/9297))**

  After creating a smart group, the values displayed in the search form should
  reflect the smart group criteria. Until this fix, range criteria for a date
  field weren’t filled.

- **[CRM-19559](https://issues.civicrm.org/jira/browse/CRM-19559) Handling for
  postal_code missing in CRM_Contact_BAO_Contact_Utils::contactDetails()
  ([9313](https://github.com/civicrm/civicrm-core/pull/9313))**

  Fixed problem when Postal Code is enabled in Settings :: Search Preferences ::
  Autocomplete Contact Search it was not retrieved in Contribute, Activity,
  Member and Event batch forms.

- **[CRM-19543](https://issues.civicrm.org/jira/browse/CRM-19543) api fields set
  to '0' are not passed to _civicrm_api3_api_match_pseudoconstant for validation
  ([9320](https://github.com/civicrm/civicrm-core/pull/9320))**

  An integer field with the value “0” should not bypass validation

- **[CRM-19563](https://issues.civicrm.org/jira/browse/CRM-19563) Mappings from
  search builder saved with mapping_type_id = NULL
  ([9316](https://github.com/civicrm/civicrm-core/pull/9316))**

  When creating a smart group from search builder, the mapping type should be
  set as “Search Builder”, and the mapping should not appear in the
  import/export mappings list.

- **[CRM-19278](https://issues.civicrm.org/jira/browse/CRM-19278) Google
  Geocoding - Errors are ignored
  ([8956](https://github.com/civicrm/civicrm-core/pull/8956))**

  If Google returns an error while geocoding (other than not finding any results
  for the address), the error message should be logged.

#### Accounting

- **[CRM-19485](https://issues.civicrm.org/jira/browse/CRM-19485) Selector issue
  on Batch trxn assignment page
  ([9211](https://github.com/civicrm/civicrm-core/pull/9211))**

  When the financial batch assignment list refreshes, if the select-all checkbox
  is checked, all transactions should be checked.

#### CiviCampaign

- **[CRM-19536](https://issues.civicrm.org/jira/browse/CRM-19536) Type is not
  defined for field campaign_id in CRM_Report_Form->whereClause()
  ([9288](https://github.com/civicrm/civicrm-core/pull/9288))**

  Reports should treat `campaign_id` as an integer.

#### CiviCase

- **Select correct activity if more than one in upcoming or recent period
  ([9011](https://github.com/civicrm/civicrm-core/pull/9011))**

  The upcoming case activity displayed with a case should be the one coming up
  soonest within the next 14 days.  Similarly, the recent case activity should
  be the most recent one within the past 14 days.

- **Remove phony fk info from case api
  ([9262](https://github.com/civicrm/civicrm-core/pull/9262))**

  No longer specify foreign key APIs for contact and activity IDs in the case
  API spec

- **[CRM-19506](https://issues.civicrm.org/jira/browse/CRM-19506) API Regression -
  conflicting uniquename in CaseContact DAO
  ([9318](https://github.com/civicrm/civicrm-core/pull/9318))**

  Record the contact ID in `civicrm_case` table as `contact_id` rather than
  `case_contact_id`

- **Remove accidental debug statement
  ([9292](https://github.com/civicrm/civicrm-core/pull/9292))**

#### CiviContribute

- **[CRM-19539](https://issues.civicrm.org/jira/browse/CRM-19539) Bug prevents
  error message to be shown on pledge contribution import
  ([9302](https://github.com/civicrm/civicrm-core/pull/9302))**

  Importing pledges with problems should generate meaningful error messages

- **[CRM-3795](https://issues.civicrm.org/jira/browse/CRM-3795) 'Bcc' fields on
  the contribution pages behave like 'Cc'
  ([9312](https://github.com/civicrm/civicrm-core/pull/9312))**

  This provides some commentary on the fix, which was included in 4.7.11

- **[CRM-19561](https://issues.civicrm.org/jira/browse/CRM-19561) When using Pay
  Later with a Price Set, Contribution Details in Email Receipt are Blank
  ([9321](https://github.com/civicrm/civicrm-core/pull/9321))**

  Fixed problem on online contributions where pay-later contributions with price
  sets had no contribution details.

#### CiviEvent

- **[CRM-19535](https://issues.civicrm.org/jira/browse/CRM-19535) Workflow that
  inadvertently cancels all registrants all enabled events
  ([9291](https://github.com/civicrm/civicrm-core/pull/9291))**

  Fixed problem where bulk actions on participants of a disabled event instead
  take effect on participants of all enabled events.

- **[CRM-19550](https://issues.civicrm.org/jira/browse/CRM-19550) Standalone
  participant/add form does not properly check for duplicates
  ([9303](https://github.com/civicrm/civicrm-core/pull/9303))**

  When registering a contact for an event from the backend, the form should
  prevent the registration if that contact has already been registered.

- **[CRM-18594](https://issues.civicrm.org/jira/browse/CRM-18594) Creating event
  templates throws an 'Invalid Entity Filter' exception
  ([8424](https://github.com/civicrm/civicrm-core/pull/8424))**

  Test that events can have text as the event type.

#### CiviGrant

- **[CRM-19543](https://issues.civicrm.org/jira/browse/CRM-19543) contact_id
  should be marked as required on grant api
  ([9296](https://github.com/civicrm/civicrm-core/pull/9296))**

  The Grant API spec should indicate that `contact_id`, `status_id`, and
  `amount_total` are required.
