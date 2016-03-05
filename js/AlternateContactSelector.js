CRM.$(function($) {
  'use strict';

  function assignAutoComplete(id_field, profileids) {
    $('#' + id_field).on('change', function (event, data) {
      var contactID = $(this).val();
      CRM.api3('profile', 'get', {'profile_id': profileids, 'contact_id': contactID})
        .done(function (result) {
          $.each(result.values, function (id, value) {
            $.each(value, function (fieldname, fieldvalue) {
              $('#' + fieldname).val(fieldvalue).change();
              $("[name=" + fieldname + "]").val([fieldvalue]);
              if ($.isArray(fieldvalue)) {
                $.each(fieldvalue, function (index, val) {
                  $("#" + fieldname + "_" + val).prop('checked', true);
                });
              }
            });
          });
        }
      );
    });
  }

  $(CRM.form.autocompletes).each(function (index, autocomplete) {
    assignAutoComplete(autocomplete.id_field, CRM.ids.profile || []);
  });

});

