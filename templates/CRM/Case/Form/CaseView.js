// https://civicrm.org/licensing
(function($, CRM) {

  function refresh() {
    $('#crm-main-content-wrapper').crmSnippet('refresh');
  }

  function open(url) {
    if (CRM.config.ajaxPopupsEnabled) {
      CRM.loadForm(url).on('crmFormSuccess', refresh);
    }
    else {
      window.location.href = url;
    }
  }

  function caseId() {
    return $('.crm-entity[data-entity=case]').data('id');
  }

  function contactId() {
    return $('.crm-entity[data-entity=case]').data('cid');
  }

  /**
   * The CaseView form includes some extra fields which are meant to open in a dialog.
   * We stash them initially and pop them up as needed
   * TODO: Creating a separate form class for each of these and opening them as standard popup links would be simpler and more reusable
   * @type {*}
   */
  var detached = {},
    miniForms = {
      '#manageTags': function() {
        console.log(this);
        var tagsChecked = $("#manageTags #tags").select2('val').join(','),
          tagList = {},
          url = CRM.url('civicrm/case/ajax/processtags');
        $("#manageTags input[name^=case_taglist]").each(function() {
          var tsId = $(this).attr('id').split('_');
          tagList[tsId[2]] = $(this).val();
        });
        var data = {
          case_id: caseId(),
          tag: tagsChecked,
          taglist: tagList,
          key: $(this).data('qfkey')
        };

        return $.post(url, data);
      }
    };

  function detachMiniForms() {
    detached = {};
    $.each(miniForms, function(selector) {
      detached[selector] = $(selector).detach();
    });
  }

  $('#crm-container').on('crmLoad', '#crm-main-content-wrapper', detachMiniForms);

  $(document).ready(function() {
    detachMiniForms();
    $('#crm-container')
      .on('change', 'select[name=add_activity_type_id]', function() {
        open($(this).val());
        $(this).select2('val', '');
      })
      .on('click', 'a.case-miniform', function() {
        var dialog,
          $el = $(this),
          target = $el.attr('href');
        function submit() {
          dialog.parent().block();
          miniForms[target].call($el[0]).done(function() {
            dialog.dialog('close');
            refresh();
          });
          return false;
        }
        dialog = CRM.confirm(submit, {
          title: $(this).text(),
          message: detached[target],
          close: function() {
            detached[target] = $(target, dialog).detach();
            $(dialog).dialog('destroy').remove();
          }
        });
        return false;
      });
  });
}(cj, CRM))
