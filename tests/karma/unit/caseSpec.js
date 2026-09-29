'use strict';

// Each row is [input, expected dashCase, expected pascalCase].
describe('CRM.utils case converters', function() {

  function check(cases) {
    cases.forEach(function(row) {
      expect(CRM.utils.dashCase(row[0])).toBe(row[1]);
      expect(CRM.utils.pascalCase(row[0])).toBe(row[2]);
    });
  }

  it('converts afform input types', function() {
    check([
      ['Text', 'text', 'Text'],
      ['ChainSelect', 'chain-select', 'ChainSelect'],
      ['EntityRef', 'entity-ref', 'EntityRef'],
      ['DisplayOnly', 'display-only', 'DisplayOnly'],
      ['CheckBox', 'check-box', 'CheckBox'],
      ['RichTextEditor', 'rich-text-editor', 'RichTextEditor'],
    ]);
  });

  it('converts entity names', function() {
    check([
      ['Contact', 'contact', 'Contact'],
      ['UFGroup', 'uf-group', 'UfGroup'],
      ['IM', 'im', 'Im'],
      ['ACL', 'acl', 'Acl'],
      ['ACLEntityRole', 'acl-entity-role', 'AclEntityRole'],
      ['ContributionRecur', 'contribution-recur', 'ContributionRecur'],
      ['OptionValue', 'option-value', 'OptionValue'],
    ]);
  });

  it('converts legacy snake_case input', function() {
    check([
      ['uf_group', 'uf-group', 'UfGroup'],
      ['contribution_recur', 'contribution-recur', 'ContributionRecur'],
      ['activity_contact', 'activity-contact', 'ActivityContact'],
    ]);
  });

  it('treats a digit as its own word', function() {
    check([
      ['Individual1', 'individual-1', 'Individual1'],
      ['Select2', 'select-2', 'Select2'],
      ['Contact2Contact', 'contact-2-contact', 'Contact2Contact'],
      ['1abc', '1-abc', '1Abc'],
      ['v1_2', 'v-1-2', 'V12'],
    ]);
  });

  it('splits a run of capitals before a capitalised word', function() {
    check([
      ['XMLHttpRequest', 'xml-http-request', 'XmlHttpRequest'],
      ['LKvt', 'lk-vt', 'LkVt'],
      ['ABC', 'abc', 'Abc'],
      ['ABCDef', 'abc-def', 'AbcDef'],
      ['aB', 'a-b', 'AB'],
    ]);
  });

  it('converts compound field keys', function() {
    check([
      ['contact.first_name', 'contact-first-name', 'ContactFirstName'],
      ['Contact_Employer_Organization_01.id', 'contact-employer-organization-01-id', 'ContactEmployerOrganization01Id'],
      ['Activity:subject', 'activity-subject', 'ActivitySubject'],
      ['custom_12', 'custom-12', 'Custom12'],
    ]);
  });

  it('folds accented Latin-1 letters instead of dropping them', function() {
    check([
      ['Caf\u00e9 Owner', 'cafe-owner', 'CafeOwner'],
      ['stra\u00dfe', 'strasse', 'Strasse'],
      ['\u00c6ther', 'aether', 'Aether'],
      ['\u00d8resund', 'oresund', 'Oresund'],
      ['na\u00efve', 'naive', 'Naive'],
    ]);
  });

  it('treats an apostrophe as a separator', function() {
    check([
      ["O'Brien", 'o-brien', 'OBrien'],
      ["Children's Fund", 'children-s-fund', 'ChildrenSFund'],
    ]);
  });

  it('drops characters beyond Latin-1', function() {
    check([
      ['\u65e5\u672c\u8a9e', '', ''],
      ['\u65e5Foo\u8a9eBar', 'foo-bar', 'FooBar'],
    ]);
  });

  it('returns an empty string when there are no words', function() {
    check([
      ['', '', ''],
      ['   ', '', ''],
      ['---', '', ''],
      ['___', '', ''],
      ['.', '', ''],
    ]);
  });

  it('coerces null and undefined to an empty string', function() {
    check([
      [null, '', ''],
      [undefined, '', ''],
    ]);
  });

});
