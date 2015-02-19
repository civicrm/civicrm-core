==== QUnit Test Suite for CiviCRM ====

QUnit is a JavaScript-based unit-testing framework. It is ideally suited to
testing pure-JavaScript modules -- for example, jQuery, Backbone, and many
of their plugins test with QUnit. For more details about, see:

  http://qunitjs.com/
  http://qunitjs.com/cookbook/

CiviCRM is a large application and may include some pure-Javascript
components -- one should use QUnit to test these components.  Note: CiviCRM
also includes many non-Javascript components (MySQL, PHP, etc).  For
integration-testing that encompasses all of CiviCRM's different
technologies, see the CiviCRM WebTest suite. QUnit is *only* appropriate
unit-testing of pure JS.

Note: When making a new JS component, consider designing a package which
doesn't depend on CivCRM at all -- put it in its own repo and handle the
testing yourself.  This is ideal for collaborating with developers on other
projects (beside CiviCRM).  When the package is stable, you can import your
package into CiviCRM's codebase (by way of "packages/" or "vendors/").

Note: The primary benefit of using this system -- rather than a vanilla
QUnit deployment -- is that you can include dependencies based on Civi's
conventions.  The primary drawback is that the test will require CiviCRM to
execute.

However, if you really need to write a Javascript component in CiviCRM core
(or in a CiviCRM extension), then proceed with testing it...

==== QUICKSTART ====

To see an example test-suite:

1. Inspect the example code "civicrm/tests/qunit/example"

2. Run the example code by logging into CiviCRM as administrator and
   visiting:

   http://localhost/civicrm/dev/qunit/civicrm/example

   (Modify "localhost" to match your CiviCRM installation.)

To create a new test-suite:

1. Determine a name for the new test-suite, such as "my-stuff".

2. Copy "civicrm/tests/qunit/example" to "civicrm/tests/qunit/my-stuff"

3. Edit the "civicrm/tests/qunit/my-stuff/test.php" to load your JS file
   (my-stuff.js) as well as any special dependencies (jQuery plugins,
   Backbone, etc).

4. Edit the "civicrm/tests/qunit/my-stuff/test.js"

5. To run the test-suite, login to CiviCRM as administrator and visit:

   http://${base_url}/civicrm/dev/qunit/${extension}/${suite}

   For example, suppose the base_url is "localhost", and suppose the
   qunit test is part of the core codebase (aka extension="civicrm"),
   and suppose the suite is "my-stuff". Then navigate to:

   http://localhost/civicrm/dev/qunit/civicrm/my-stuff

==== CONVENTIONS ====

The following is a quick draft of coding conventions. If there's a problem
with it, we can change it -- but please communicate any problems/issues
(e.g.  via IRC, mailing-list, or forum).

 * CiviCRM includes multiple test-suites. One test-suite should be created for
   each logically distinct JavaScript component.

   Rationale: CiviCRM is a large application with a diverse mix of
   components written by diverse authors.  Each component may present
   different requirements for testing -- e.g. HTML fixtures, CSS fixtures,
   third-party JS dependencies, etc.

   Note: As a rule-of-thumb, if you add a new js file to CiviCRM
   ("civicrm/js/foo.js"), and if that file is useful on its own, then you
   might create a new test-suite for it ("civicrm/tests/qunit/foo").

 * Each QUnit test-suite for CiviCRM lives in a subdirectory of
   "tests/qunit/".

   Rationale: Following a predictable naming convention will help us automate
   testing/loading across all suites, and it will make the code more recognizable
   to other developers.

 * Each QUnit test-suite *may* include the file "test.php" to specify
   loading of resource files or bundles (such as CSS/JS). The file will
   be recognized automatically.

   Rationale: CiviCRM has its own resource-loading conventions. When
   preparing a test environment, one needs to load JS/CSS dependencies.
   Since there is no autoloader, this is most easily done with CiviCRM's
   resource-loader.

 * Each QUnit test-suite *may* include the file "test.tpl" to specify
   any HTML or CSS fixtures. The file will be recognized automatically.

 * Each QUnit test-suite *may* include the file "test.js" to specify
   assertions. The file will be recognized automatically. If one wants to
   split the tests into multiple JS files, then each file should
   registered as a resource in "test.php".

==== TODO ====

 * GUI Testing -- Display a browsable list of all tests.

 * Automatic Testing -- Add an item to the WebTest suite (e.g.
   WebTest_Core_QUnitTestCase) which iteratively executes each QUnit
   test-suite and verifies that they pass.
