<<<<<<< HEAD
module('Translation');

test('ts()', function() {
  equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
});

test('CRM.ts()', function() {
  (function (ts) {
    equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
  }(CRM.ts('org.example.foo')));
});
=======
module('Translation');

test('ts()', function() {
  equal(ts('Lexicographical enigma'), "Lexicographical enigma", "If a string has no translation, pass it through");
  equal(ts('One, two, three'), "Un, deux, trois", "We expect translations to work");
  equal(ts('I know'), "Je sais", "We expect translations to work");
});

test('CRM.ts()', function() {
  (function (ts) {
    equal(ts('Lexicographical enigma'), "Lexicographical enigma", "If a string has no translation, pass it through");
    equal(ts('One, two, three'), "Un, deux, trois", "Fallback to translations from default domain");
    equal(ts('I know'), "Je connais", "We expect translations to work");
  }(CRM.ts('org.example.foo')));
});
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
