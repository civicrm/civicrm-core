'use strict';

describe('afForm conditional rules', () => {

  let afForm;

  beforeEach(() => {
    module('af');
    // afForm injects crmApi4 and FileUploader, which `af` does not itself require.
    module('api4');
    module('angularFileUpload');
  });

  beforeEach(inject(['crmLegacy', (crmLegacy) => {
    // afForm builds its file-upload url as it is constructed; a real page sets these templates.
    crmLegacy.url({
      back: '/civicrm/crmajax-placeholder-url-path?civicrm-placeholder-url-query=1',
      front: '/civicrm/crmajax-placeholder-url-path?civicrm-placeholder-url-query=1'
    });
  }]));

  beforeEach(inject(($componentController, $rootScope) => {
    afForm = $componentController('afForm', {$scope: $rootScope.$new(), $element: angular.element('<af-form></af-form>')});
    afForm.registerEntity({modelName: 'Individual1'});
    afForm.getData('Individual1').push({fields: {}});
  }));

  const check = (op, expected) => afForm.evaluateBooleanExpression([['[Individual1.0.do_not_email]', op, expected]]);

  const setValue = (value) => {
    afForm.getData('Individual1')[0].fields.do_not_email = value;
  };

  it('treats an untouched Yes/No field as No, like the server', () => {
    expect(check('=', false)).toBe(true);
    expect(check('=', true)).toBe(false);
    expect(check('!=', true)).toBe(true);
    expect(check('!=', false)).toBe(false);
  });

  it('compares a set Yes/No field by its value', () => {
    setValue(true);
    expect(check('=', true)).toBe(true);
    expect(check('=', false)).toBe(false);
    setValue(false);
    expect(check('=', false)).toBe(true);
    expect(check('!=', false)).toBe(false);
  });

  it('casts to boolean as PHP does', () => {
    setValue('0');
    expect(check('=', false)).toBe(true);
    setValue(1);
    expect(check('=', true)).toBe(true);
    setValue(null);
    expect(check('=', false)).toBe(true);
  });

});
