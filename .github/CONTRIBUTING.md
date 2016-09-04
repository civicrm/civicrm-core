CiviCRM is a community-driven open-source project. It has a small,
full-time "core team" which facilitates development and works on critical
issues. However, many improvements are driven by the active contributors.

This document provides important information about how to contribute.

## Review/Release Process

Releases are developed on a monthly cycle.  At the start of the month, the
release-manager will send an invitation to developers who have open PRs,
encouraging them to participate in the release-cycle.  Participation
provides a way to exchange feedback with other developers, get PRs merged,
and ensure the next release works -- all with a predictable timeline.

 * For a high-level summary of the release process, see the
   [Release Management README](https://github.com/civicrm/release-management/blob/master/README.md).
 * For an example invitation, see the previous [invitation for the April-May 2016](https://github.com/civicrm/release-management/issues/1).

## Pull-Request Subject

When filing a pull-request, use a descriptive subject. These are good examples:

 * `CRM-12345 - Fix Paypal IPNs when moon is at half-crescent (waxing)`
 * `(WIP) CRM-67890 - Refactor SMS callback endpoint`
 * `(NFC) CRM_Utils_PDF - Improve docblocks`

A few elements to include:

 * **CRM-_XXXXX_** - This is a reference to the [CiviCRM issue tracker](http://issues.civicrm.org/)
   (JIRA). A bot will setup crosslinks between JIRA and GitHub.
 * **Description** - Provide a brief description of what the pull-request does.
 * **(WIP)** - "Work in Progress" - If you are still developing a set of
   changes, it may be useful to submit a pull-request and flag it as
   `(WIP)`. This allows you to have discussion with other developers and
   check test results. Once the change is ready, update the subject line
   to remove `(WIP)`.
 * **(NFC)** - "Non-Functional Change" - Most patches are designed to
   change functionality (e.g. fix an error message or add a new button).
   However, some changes are non-functional -- e.g. they cleanup the
   code-style, improve the comments, or improve the test-suite.

## Testing

Pull-requests are tested automatically by a build-bot. Key things to know:

 * If you are a new contributor, the tests may be placed on hold pending a
   cursory review. One of the administrators will post a comment like
   `jenkins, ok to test` or `jenkins, add to whitelist`.
 * The pull-request will have a colored dot indicating its status:
   * **Yellow**: The automated tests are running.
   * **Red**: The automated tests have failed.
   * **Green**: The automated tests have passed.
 * If the automated test fails, click on the red dot to investigate details. Check for information in:
   * The initial summary. Ordinarily, this will list test failures and error messages.
   * The console output. If the test-suite encountered a significant error (such as a PHP crash),
     the key details will only appear in the console.
 * Code-style tests are executed first. If the code-style in this patch is inconsistent, the remaining tests will be skipped.
 * The primary tests may take 20-120 min to execute. This includes the following suites: `api_v3_AllTests`, `CRM_AllTests`, `Civi\AllTests`, `civicrm-upgrade-test`, and `karma`
 * There are a handful of unit tests which are time-sensitive and which fail sporadically. See: https://forum.civicrm.org/index.php?topic=36964.0
 * The web test suite (`WebTest_AllTests`) takes several hours to execute. [It runs separately -- after the PR has been merged.](https://test.civicrm.org/job/CiviCRM-WebTest-Matrix/)

For detailed discussion about automated tests, see http://wiki.civicrm.org/confluence/display/CRMDOC/Testing

## Updating a pull-request

During review, there may be some feedback about problems or additional
changes required for acceptance.  If you've never updated a pull-request
before, see [Stackoverflow: How to update a pull request](http://stackoverflow.com/questions/9790448/how-to-update-a-pull-request).

When you push the update to the pull-request, the test suite will re-execute.
