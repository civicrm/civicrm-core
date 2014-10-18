module('Translation');

test('ts()', function() {
  equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
});

test('CRM.ts()', function() {
  (function (ts) {
    equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
  }(CRM.ts('org.example.foo')));
});
