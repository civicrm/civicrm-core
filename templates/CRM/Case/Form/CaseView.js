// https://civicrm.org/licensing
(function($, CRM) {

  function refresh() {
    $('#crm-main-content-wrapper').crmSnippet('refresh');
  }

  function open(url, options) {
    if (CRM.config.ajaxPopupsEnabled) {
      CRM.loadForm(url, options).on('crmFormSuccess', refresh);
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
          key: $(this).data('key')
        };
        return $.post(url, data);
      },
      '#merge_cases': function() {
        if ($('select#merge_case_id').val()) {
          $('select#merge_case_id').appendTo('form#CaseView');
          $('[name="_qf_CaseView_next_merge_case"]').click();
        }
      },
      '#deleteCaseRole': function() {
        var params = $.extend({case_id: caseId()}, $(this).data());
        return $.post(CRM.url('civicrm/ajax/delcaserole'), params);
      }
    };

  function detachMiniForms() {
    detached = {};
    $.each(miniForms, function(selector) {
      detached[selector] = $(selector).detach().removeClass('hiddenElement');
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
      .on('change', 'select[name=timeline_id]', function() {
        if ($(this).val()) {
          CRM.confirm(ts('Add'), {
            title: $('option:first', this).text(),
            message: ts('Add the %1 set of scheduled activities to this case?', {1: '<em>' + $('option:selected', this).text() + '</em>'})
          })
            .on('crmConfirmYes', function () {
              $('[name=_qf_CaseView_next]').click();
            })
            .on('crmConfirmNo', function() {
              $('select[name=timeline_id]').select2('val', '');
            });
        }
      })
      .on('change', 'select[name=report_id]', function() {
        if ($(this).val()) {
          var url = CRM.url('civicrm/case/report', {
            reset: 1,
            cid: contactId(),
            caseid: caseId(),
            asn: $(this).val()
          });
          open(url, {dialog: {width: '50%', height: 'auto'}});
          $(this).select2('val', '');
        }
      })
      .on('click', 'a.case-miniform', function() {
        var dialog,
          $el = $(this),
          target = $el.attr('href');
        function submit() {
          var submission = miniForms[target].call($el[0]);
          if (submission) {
            dialog.parent().block();
            submission.done(function() {
              dialog.dialog('close');
              refresh();
            });
            return false;
          }
        }
        dialog = CRM.confirm(submit, {
          title: $(this).attr('title') || $(this).text(),
          message: detached[target],
          close: function() {
            detached[target] = $(target, dialog).detach();
            $(dialog).dialog('destroy').remove();
          }
        });
        return false;
      });
    $().crmAccordions();
  });
}(cj, CRM))
