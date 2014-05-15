CRM.$(function($) {
  'use strict';

  // Behind the scenes method deals with browser for setting cursor position
  $.caretTo = function (el, index) {
    if (el.createTextRange) {
      var range = el.createTextRange();
      range.move("character", index);
      range.select();
    }
    else if (el.selectionStart != null) {
      el.focus();
      el.setSelectionRange(index, index);
    }
  };

  /**
   * Display a personalized message containing the contact's name
   * and a variable from the server
   */
  function assignAutoComplete( id_field, profileids, autocomplete) {
    if(profileids === undefined) {profileids = [];}

    var customIdObj = $('#' + id_field);

    customIdObj.on('change', function (event, data) {
        var contactID = $(this).val();
        console.log(contactID);
        var namefields = ['first_name', 'last_name', 'middle_name'];
        CRM.api('profile', 'get', {'profile_id': profileids, 'contact_id': contactID}, {
          success: function (result) {
            $.each(result.values, function (id, value) {
              $.each(value, function (fieldname, fieldvalue) {
                $('#' + fieldname).val(fieldvalue);
              });
            });
          }
        });
      }
      )
    }

  /**
   * Show or hide the autocomplete and change the text
   */
  function showHideAutoComplete(id_field, hidden_text, shown_text, profileids) {
    $('#crm-contact-toggle-' + id_field).on('click', function(event) {
      event.preventDefault();
      $('#' + name_field).toggle();
      if($('#' + name_field).is(":visible")) {
        $('#crm-contact-toggle-text-'  + id_field).text(shown_text);
      }
      else{
        $('#crm-contact-toggle-text-'  + id_field).text(hidden_text);
        $('#' + id_field + ', #' + name_field).val('');
        CRM.api('profile', 'get', {'profile_id' : profileids}, {
          success: function(result) {
            $.each(result.values, function (id, values){
            $.each(values, function (fieldname, fieldvalue) {
              $('#' + fieldname).val(fieldvalue);
            });
            });
          }
        });
      }
    });
  }

  var autocompletes = CRM.form.autocompletes;
  $(autocompletes).each(function (index, autocomplete) {
    assignAutoComplete(autocomplete.id_field, CRM.ids.profile, autocomplete);
    }
  );

});

