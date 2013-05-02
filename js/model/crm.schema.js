(function($) {
  var CRM = (window.CRM) ? (window.CRM) : (window.CRM = {});
  if (!CRM.Schema) CRM.Schema = {};

  /**
   * Civi data models require more attributes than basic Backbone models:
   *  - sections: array of field-groupings
   *  - schema: array of fields, keyed by field name, per backbone-forms
   *
   * @see https://github.com/powmedia/backbone-forms
   */

  CRM.Schema.IndividualModel = CRM.Backbone.Model.extend({
    sections: {
      'default': {title: 'Individual'},
      'custom1': {title: 'Individual: Favorite Things', is_addable: true},
      'custom2': {title: 'Individual: Custom Things', is_addable: true}
    },
    schema: {
      first_name: { type: 'Text', title: 'First name', civiFieldType: 'Individual' },
      last_name: { type: 'Text', title: 'Last name', civiFieldType: 'Individual' },
      legal_name: { type: 'Text', title: 'Legal name', civiFieldType: 'Contact' },
      street_address: { validators: ['required', 'email'], title: 'Email', civiFieldType: 'Contact', civiIsLocation: true, civiIsPhone: false },
      email: { validators: ['required', 'email'], title: 'Email', civiFieldType: 'Contact', civiIsLocation: true, civiIsPhone: true },
      custom_123: { type: 'Checkbox', section: 'custom1', title: 'Likes whiskers on kittens', civiFieldType: 'Individual'},
      custom_456: { type: 'Checkbox', section: 'custom1', title: 'Likes dog bites', civiFieldType: 'Individual' },
      custom_789: { type: 'Checkbox', section: 'custom1', title: 'Likes bee stings', civiFieldType: 'Individual' },
      custom_012: { type: 'Text', section: 'custom2', title: 'Pass phrase', civiFieldType: 'Contact' }
    },
    initialize: function() {
    }
  });


  CRM.Schema.ActivityModel = CRM.Backbone.Model.extend({
    sections: {
      'default': {title: 'Activity'},
      'custom3': {title: 'Activity: Questions', is_addable: true}
    },
    schema: {
      subject: { type: 'Text', title: 'Subject', civiFieldType: 'Activity' },
      location: { type: 'Text', title: 'Location', civiFieldType: 'Activity' },
      activity_date_time: { type: 'DateTime', title: 'Date-Time', civiFieldType: 'Activity' },
      custom_789: { type: 'Select', section: 'custom3', title: 'How often do you eat cheese?',
        options: ['Never', 'Sometimes', 'Often'],
        civiFieldType: 'Activity'
      }
    },
    initialize: function() {
    }
  });
})(cj);