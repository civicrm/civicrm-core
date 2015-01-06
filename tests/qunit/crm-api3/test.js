/* ------------ Test cases ------------ */
module('api single');

asyncTest("simple api call", function() {
  CRM.api3('contact', 'get').done(function(result) {
    equal(result.is_error, 0, 'contact get');
    start();
  });
});

module('api multiple');

asyncTest("array api calls", function() {
  var params = [
    ['email', 'get', {email: '@'}],
    ['phone', 'get', {phone: '123'}]
  ];
  CRM.api3(params).done(function(result) {
    equal(result[0].is_error, 0, 'email get');
    equal(result[1].is_error, 0, 'phone get');
    start();
  });
});

asyncTest("named api calls", function() {
  var params = {
    one: ['email', 'getoptions', {field: 'location_type_id'}],
    two: ['phone', 'getoptions', {field: 'phone_type_id', sequential: 1}],
    three: ['phone', 'get']
  };
  CRM.api3(params).done(function(result) {
    ok(result.one.count > 0, 'email getoptions');
    ok(result.two.count > 0, 'phone getoptions');
    ok(result.three.count > 0, 'phone get');
    start();
  });
});
