(function($) {
  var CRM = (window.CRM) ? (window.CRM) : (window.CRM = {});
  if (!CRM.ProfileSelector) CRM.ProfileSelector = {};

  CRM.ProfileSelector.DummyModel = CRM.Backbone.Model.extend({
    defaults: {
      profile_id: null
    }
  });
})(cj);
