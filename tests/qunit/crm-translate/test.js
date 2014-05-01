module('Translation');

test('ts()', function() {
  equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
});

test('CRM.translate()', function() {
  CRM.translate('org.example.foo', function(ts){
    equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
  });
});
