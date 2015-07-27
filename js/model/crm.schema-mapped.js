/**
 * Dynamically-generated alternative to civi.core.js
 */
(function($, _) {
  if (!CRM.Schema) CRM.Schema = {};

  /**
   * Data models used by the Civi form designer require more attributes than basic Backbone models:
   *  - sections: array of field-groupings
   *  - schema: array of fields, keyed by field name, per backbone-forms; extra attributes:
   *     + section: string, index to the 'sections' array
   *     + civiFieldType: string
   *
   * @see https://github.com/powmedia/backbone-forms
   */

  CRM.Schema.BaseModel = CRM.Backbone.Model.extend({
    initialize: function() {
    }
  });

  CRM.Schema.loadModels = function(civiSchema) {
    _.each(civiSchema, function(value, key, list) {
      CRM.Schema[key] = CRM.Schema.BaseModel.extend(value);
    });
  };

  CRM.Schema.reloadModels = function(options) {
    return $
      .ajax({
        url: CRM.url("civicrm/profile-editor/schema"),
        data: {
          'entityTypes': _.keys(CRM.civiSchema).join(',')
        },
        type: 'POST',
        dataType: 'json',
        success: function(data) {
          if (data) {
            CRM.civiSchema = data;
            CRM.Schema.loadModels(CRM.civiSchema);
          }
        }
      });
  };

  CRM.Schema.loadModels(CRM.civiSchema);
})(CRM.$, CRM._);
