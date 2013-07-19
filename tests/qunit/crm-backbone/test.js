/* ------ Fixtures/constants ----- */

var VALID_CONTACT_ID = 3;
var INVALID_CONTACT_ID = 'z';

var ContactModel = Backbone.Model.extend({});
CRM.Backbone.extendModel(ContactModel, 'Contact');

var ContactCollection = Backbone.Collection.extend({
  model: ContactModel
});
CRM.Backbone.extendCollection(ContactCollection);

/* ------ Assertions ------ */

function assertApiError(result) {
  equal(1, result.is_error, 'Expected error boolean');
  ok(result.error_message.length > 0, 'Expected error message')
}
function onUnexpectedError(ignore, result) {
  if (result && result.error_message) {
    ok(false, "API returned an unexpected error: " + result.error_message);
  } else {
    ok(false, "API returned an unexpected error: (missing message)");
  }
  start();
}
function onUnexpectedSuccess(ignore) {
  ok(false, "API succeeded - but failure was expected");
  start();
}

/* ------ Test cases ------ */

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
  var c = new ContactModel({id: INVALID_CONTACT_ID});
  c.fetch({
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
      id: INVALID_CONTACT_ID
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

