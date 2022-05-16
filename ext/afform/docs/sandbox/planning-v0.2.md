## Example Use Case

A school has an application process which begins when a parent fills out a
form with basic details about:

* Themselves
* Their spouse/coparent (if applicable)
* The kid
* A meeting request

The data is saved to 4 entities:

* "Parent" (or "You" or "Me")
* "Spouse"
* "Kid"
* "Activity" (of type "Phone Call" assigned to a particular person)

## General Metadata

```php
// The tag library is a definition of available tags/directives. It can be
// used to validate form definitions; to advise sysadmins; and (probably)
// to generate the $palette.
//
// Must NOT be required at runtime. Only required for administration.
//
// CONSIDER: Specifying non-conventional names for design/props variants
$afformTags = [
  'afl-entity' =>[
    'attrs' => ['entity-name', 'matching-rule', 'assigned-values'],
  ],
  'afl-name' => [
    'attrs' => ['contact-id', 'afl-label'],
  ],
  'afl-contact-email' => [
    'attrs' => ['contact-id', 'afl-label'],
  ],
];

// The "palette" is a list of things that can be selected by a web-based
// admin and added to a form.
//
// The canonical form is flat-ish array, but it will be requested in 2-level indexed
// format (e.g. `entity,id`).
//
// Must NOT be required at runtime. Only required for administration.
function computePalette($entityType) {
  return [
    [
      'group' => 'Blocks',
      'title' => 'Name',
      'template' => '<afl-name entity="%%ENTITY%%" afl-label="Name"/>',
    ],
    [
      'title' => 'Address',
      'template' => '<afl-address entity="%%ENTITY%%" afl-label="Address"/>',
    ], 
    [
      'group' => 'Fields',
      'title' => 'First Name',
      'template' => '<afl-api-field entity="%%ENTITY%%" afl-field="first_name" afl-label="First Name" afl-type="String" />',
    ],
  ];
}
```

## Form Metadata: *.aff.json

```json
{
  "server_route": "civicrm/hello-world",
  "entities": {
    "parent": {
      "type": "Individual",
      "set": {"favorite_color": "red"},
      "matchingRule": "email_only"
    }
  }
}
```

## Form Layout: *.aff.html

```html
<afl-form>
  <crm-ui-tabset>

    <crm-ui-tab>
      <afl-entity name="parent">
        <afl-name afl-label="Your Name" ng-required="true" name-style="First-Last" />
        <afl-email afl-label="Your Email" ng-required="true" />
      </afl-entity>
    </crm-ui-tab>

    <crm-ui-tab>
      <afl-entity name="spouse">
        <afl-name afl-label="Spouse Name" name-style="First-Last" />
        <afl-email afl-label="Spouse Email" />
      </afl-entity>
    </crm-ui-tab>

    <crm-ui-tab>
      <afl-entity name="kid">
        <afl-name afl-label="Kid Name" ng-required="true" name-style="First-Last" />
      </afl-entity>      
    </crm-ui-tab>
  </crm-ui-tabset>

  <button ng-click="save()">Save</button>
</afl-form>
```

## Form Editor: Rendering the same layout

```html
<afl-form-af-design>
  <crm-ui-tabset-af-design>
    <crm-ui-tab-af-design>
      
    </crm-ui-tab-af-design>
  </crm-ui-tabset-af-design>
</afl-form-af-design>
```

## File Structure

In v0.1, there was a convention of autoprefixing everything with `afform-`.
This was generally tied to the goal of providing a strong symmetries in
the names of folders/files/modules/directives/tags.

In the long run, though, it doesn't seem sustainable force everything under
the `afform-` prefix.

* As we started spec'ing the standard library, it became clear that the
  stdlib should have a diff namespace from the more business-oriented form defs.
* As third parties start writing code, they're going to look for way to
  make their own prefix.

However, we still want to preserve the strong symmetries in the filesystem where:

* The symbol in HTML (e.g.  `afform-email`) should match the file-name (e.g. 
  `afform/Email/layout.html` or `afform-email.aff.html`)
* The file name in the base-code provided by an extension should match
  the file-name in the local override folder.

The following file-structure preserves those parallels. It also:

* Gives access to a full range of tag names
* Is a bit easier with IDE file-opening (more unique file-names)
* Is a bit harder to manually copy (e.g. `cp -r foo bar` => `mcp 'foo.*' 'bar.#1'`

Which leads to this structure:

```
// A business-y form; in canonical definition and local override
{ext:org.foobar}/ang/afform-edit-contact.aff.json
{ext:org.foobar}/ang/afform-edit-contact.aff.html
 {civicrm.files}/ang/afform-edit-contact.aff.json
 {civicrm.files}/ang/afform-edit-contact.aff.html

// An address block in the stdlib; in canonical definition and local override
// Note: We also have the "design" and "props" variants
{ext:org.foobar}/ang/afl-address.aff.json
{ext:org.foobar}/ang/afl-address.aff.html
{ext:org.foobar}/ang/afl-address.aff.props.html
{ext:org.foobar}/ang/afl-address.aff.design.html
 {civicrm.files}/ang/afl-address.aff.json
 {civicrm.files}/ang/afl-address.aff.html
```
