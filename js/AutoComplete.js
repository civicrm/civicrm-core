cj(function ($) {
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

  //The following methods are queued under fx for more
  //flexibility when combining with $.fn.delay() and
  //jQuery effects.

  //Set caret to a particular index
  $.fn.caretTo = function (index, offset) {
    return this.queue(function (next) {
      if (isNaN(index)) {
        var i = $(this).val().indexOf(index);
        if (offset === true) {
          i += index.length;
        }
        else if (offset) {
          i += offset;
        }
        $.caretTo(this, i);
      }
      else {
        $.caretTo(this, index);
      }
      next();
    });
};

  /**
   * Display a personalized message containing the contact's name
   * and a variable from the server
   */
  function assignAutoComplete(select_field, id_field, url, varmax, profileids, autocomplete) {
    if(varmax === undefined) {varmax = 10;}
    if(profileids === undefined) {profileids = [];}

    if(url === undefined) {
      url = CRM.url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1');
    }

    var customObj   = $('#' + select_field);
    var customIdObj = $('#' + id_field);

    if (!customObj.hasClass('ac_input')) {
      customObj.autocomplete(url,
        { width : 250, selectFirst : false, matchContains: true, max: varmax }).result(
        function (event, data) {
          var contactID = data[1];
          customIdObj.val(contactID);
          customObj.caretTo(0);
          var namefields = ['first_name', 'last_name', 'middle_name'];
          CRM.api('profile', 'get', {'profile_id' : profileids, 'contact_id' : contactID}, {
            success: function(result) {
              $.each(result.values, function (id, value){
              $.each(value, function (fieldname, fieldvalue) {
                $('#' + fieldname).val(fieldvalue);
              });
              });
            }
          });
        }
      );
      customObj.click(function () {
        customIdObj.val('');
      });
    }
    
    if(autocomplete.show_hide) {
      customObj.hide();
      showHideAutoComplete(select_field, id_field,
        autocomplete.show_text,
        autocomplete.hide_text,
        profileids
      );
    }
  }
  
  /**
   * Show or hide the autocomplete and change the text
   */
  function showHideAutoComplete(name_field, id_field, hidden_text, shown_text, profileids) {
    $('#crm-contact-toggle-' + id_field).on('click', function(event) {
      event.preventDefault();
      $('#' + name_field).toggle();
      if($('#' + name_field).is(":visible")) {
        $('#crm-contact-toggle-text-'  + id_field).text(shown_text);
      }
      else{
        $('#crm-contact-toggle-text-'  + id_field).text(hidden_text);
        $('#' + id_field).val('');
        $('#' + name_field).val('');
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
  var url = CRM.url(autocompletes.url[0], autocompletes.url[1]);

  $(autocompletes).each(function (index, autocomplete) {
    assignAutoComplete(autocomplete.name_field, autocomplete.id_field, url, autocomplete.max, CRM.ids.profile, autocomplete);
    }
  );

});

