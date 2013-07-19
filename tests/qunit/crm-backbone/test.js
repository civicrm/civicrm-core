/* ------------ Fixtures/constants ------------ */

var VALID_CONTACT_ID = 3;
var MALFORMED_CONTACT_ID = 'z';

var ContactModel = Backbone.Model.extend({});
CRM.Backbone.extendModel(ContactModel, 'Contact');

var ContactCollection = Backbone.Collection.extend({
  model: ContactModel
});
CRM.Backbone.extendCollection(ContactCollection);

/* ------------ Assertions ------------ */

/**
 * Assert "result" contains an API error
 * @param result
 */
function assertApiError(result) {
  equal(1, result.is_error, 'Expected error boolean');
  ok(result.error_message.length > 0, 'Expected error message')
}

/**
 * When calling an AJAX operation which should return successfully,
 * make sure that there's no error by setting a callback (error: onUnexpectedError)
 */
function onUnexpectedError(ignore, result) {
  if (result && result.error_message) {
    ok(false, "API returned an unexpected error: " + result.error_message);
  } else {
    ok(false, "API returned an unexpected error: (missing message)");
  }
  start();
}

/**
 * When calling an AJAX operation which should return an error,
 * make sure that there's no success by setting a callback (success: onUnexpectedSuccess)
 */
function onUnexpectedSuccess(ignore) {
  ok(false, "API succeeded - but failure was expected");
  start();
}

/* ------------ Test cases ------------ */

module('model - read');

asyncTest("fetch (ok)", function() {
  var c = new ContactModel({id: VALID_CONTACT_ID});
  c.fetch({
    error: onUnexpectedError,
    success: function() {
      notEqual(-1, _.indexOf(['Individual', 'Household', 'Organization'], c.get('contact_type')), 'Loaded contact with valid contact_type');
      ok(c.get('display_name') != '', 'Loaded contact with valid name');
      start();
    }
  });
});

asyncTest("fetch (error)", function() {
  var c = new ContactModel({id: MALFORMED_CONTACT_ID});
  c.fetch({
    success: onUnexpectedSuccess,
    error: function(model, error) {
      assertApiError(error);
      start();
    }
  });
});

module('model - create');

asyncTest("create/read/delete/read (ok)", function() {
  var TOKEN = new Date().getTime();
  var c1 = new ContactModel({
    contact_type: "Individual",
    first_name: "George" + TOKEN,
    last_name: "Anon" + TOKEN
  });

  // Create the new contact
  c1.save({}, {
    error: onUnexpectedError,
    success: function() {
      equal(c1.get("first_name"), "George" + TOKEN, "save() should return new first name");

      // Fetch the newly created contact
      var c2 = new ContactModel({id: c1.get('id')});
      c2.fetch({
        error: onUnexpectedError,
        success: function() {
          equal(c2.get("first_name"), c1.get("first_name"), "fetch() should return first name");

          // Destroy the newly created contact
          c2.destroy({
            error: onUnexpectedError,
            success: function() {

              // Attempt (but fail) to fetch the deleted contact
              var c3 = new ContactModel({id: c1.get('id')});
              c3.fetch({
                success: onUnexpectedSuccess,
                error: function(model, error) {
                  assertApiError(error);
                  start();
                }
              }); // fetch
            }
          }); // destroy
        }
      }); // fetch
    }
  }); // save
});

asyncTest("create (error)", function() {
  var TOKEN = new Date().getTime();
  var c1 = new ContactModel({
    // MISSING: contact_type: "Individual",
    first_name: "George" + TOKEN,
    last_name: "Anon" + TOKEN
  });

  // Create the new contact
  c1.save({}, {
    success: onUnexpectedSuccess,
    error: function(model, error) {
      assertApiError(error);
      start();
    }
  });
});

module('model - update');

asyncTest("update (ok)", function() {
  var NICKNAME = "George" + new Date().getTime();
  var c = new ContactModel({id: VALID_CONTACT_ID});
  c.save({
    nick_name: NICKNAME
  }, {
    error: onUnexpectedError,
    success: function() {
      equal(c.get("nick_name"), NICKNAME, "save() should return new nickname");

      var c2 = new ContactModel({id: VALID_CONTACT_ID});
      c2.fetch({
        error: onUnexpectedError,
        success: function() {
          equal(c2.get("nick_name"), NICKNAME, "fetch() should return new nickname");
          start();
        }
      });
    }
  });
});

asyncTest("update (error)", function() {
  var NICKNAME = "George" + new Date().getTime();
  var c = new ContactModel({id: VALID_CONTACT_ID});
  c.save({
    contact_type: 'Not-a.va+lidConta(ype'
  }, {
    success: onUnexpectedSuccess,
    error: function(model, error) {
      assertApiError(error);
      start();
    }
  });
});


module('collection - read');

asyncTest("fetch by contact_type (1+ results)", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      contact_type: 'Organization'
    }
  });
  c.fetch({
    error: onUnexpectedError,
    success: function() {
      ok(c.models.length > 0, "Expected at least one contact");
      c.each(function(model) {
        equal(model.get('contact_type'), 'Organization', 'Expected contact with type organization');
        ok(model.get('display_name') != '', 'Expected contact with valid name');
      });
      start();
    }
  });
});

asyncTest("fetch by crazy name (0 results)", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      display_name: 'asdf23vmlk2309lk2lkasdk-23ASDF32f'
    }
  });
  c.fetch({
    error: onUnexpectedError,
    success: function() {
      equal(c.models.length, 0, "Expected no contacts");
      start();
    }
  });
});

asyncTest("fetch by malformed ID (error)", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      id: MALFORMED_CONTACT_ID
    }
  });
  c.fetch({
    success: onUnexpectedSuccess,
    error: function(collection, error) {
      assertApiError(error);
      start();
    }
  });
});

module('fetchCreate');

asyncTest("fetchCreate by ID (1 result)", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      id: VALID_CONTACT_ID
    }
  });
  c.fetchCreate({
    error: onUnexpectedError,
    success: function(model) {
      equal(model.get('id'), VALID_CONTACT_ID);
      ok(model.get('contact_type') != '', 'Expected contact with valid type')
      ok(model.get('id'), 'Expected contact with valid ID')
      start();
    }
  });
});

asyncTest("fetchCreate by crazy name (0 results) - autocreate", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      organization_name: 'asdf23vmlk2309lk2lkasdk-23ASDF32f'
    }
  });
  c.fetchCreate({
    defaults: {
      contact_type: 'Organization'
    },
    error: onUnexpectedError,
    success: function(model) {
      equal(model.get('organization_name'), 'asdf23vmlk2309lk2lkasdk-23ASDF32f', 'Expected default values from crmCriteria');
      equal(model.get('contact_type'), 'Organization', 'Expected default values from parameters');
      ok(!model.get('id'), 'Expected contact without valid ID')
      start();
    }
  });
});

asyncTest("fetchCreate by malformed ID (error)", function() {
  var c = new ContactCollection([], {
    crmCriteria: {
      id: MALFORMED_CONTACT_ID
    }
  });
  c.fetch({
    success: onUnexpectedSuccess,
    error: function(collection, error) {
      assertApiError(error);
      start();
    }
  });
});
