// https://civicrm.org/licensing
(function($, CRM) {

  function refresh(table) {
    if (table) {
      $(table).dataTable().fnDraw();
    } else {
      $('#crm-main-content-wrapper').crmSnippet('refresh');
    }
  }

  function open(url, options, table) {
    if (CRM.config.ajaxPopupsEnabled) {
      CRM.loadForm(url, options).on('crmFormSuccess', function() {
        refresh(table);
      });
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
   * @type {pre, post}
   */
  var miniForms = {
    '#manageTagsDialog': {
      post: function(data) {
        var tagsChecked = $("#tags", this) ? $("#tags", this).select2('val').join(',') : '',
          tagList = {},
          url = CRM.url('civicrm/case/ajax/processtags');
        $("input[name^=case_taglist]", this).each(function() {
          var tsId = $(this).attr('id').split('_');
          tagList[tsId[2]] = $(this).val();
        });
        $.extend(data, {
          case_id: caseId(),
          tag: tagsChecked,
          taglist: tagList
        });
        return $.post(url, data);
      }
    },
    '#mergeCasesDialog': {
      post: function(data) {
        if ($('select#merge_case_id').val()) {
          $('select#merge_case_id').appendTo('form#CaseView');
          $('[name="_qf_CaseView_next_merge_case"]').click();
        } else {
          return false;
        }
      }
    },
    '#deleteCaseRoleDialog': {
      post: function(data) {
        data.case_id = caseId();
        return $.post(CRM.url('civicrm/ajax/delcaserole'), data);
      }
    },
    '#addCaseRoleDialog': {
      pre: function() {
        $('[name=role_type]', this).val('').change();
        $('[name=add_role_contact_id]', this).val('').crmEntityRef({create: true, api: {params: {contact_type: 'Individual'}}});
      },
      post: function(data) {
        var contactID = $('[name=add_role_contact_id]').val(),
          relType = $('[name=role_type]').val();
        if (contactID && relType) {
          $.extend(data, {
            case_id: caseId(),
            rel_contact: contactID,
            rel_type: relType,
            contact_id: contactId()
          });
          return $.post(CRM.url('civicrm/ajax/relation'), data);
        }
        return false;
      }
    },
    '#editCaseRoleDialog': {
      pre: function(data) {
        var params = {create: true};
        if (data.contact_type) {
          params.api = {params: {contact_type: data.contact_type}};
        }
        $('[name=edit_role_contact_id]', this).val('').crmEntityRef(params);
      },
      post: function(data) {
        data.rel_contact = $('[name=edit_role_contact_id]', this).val();
        if (data.rel_contact) {
          $.extend(data, {
            case_id: caseId(),
            contact_id: contactId()
          });
          return $.post(CRM.url('civicrm/ajax/relation'), data);
        }
        return false;
      }
    },
    '#addClientDialog': {
      pre: function() {
        $('[name=add_client_id]', this).val('').crmEntityRef({create: true});
      },
      post: function(data) {
        data.contactID = $('[name=add_client_id]', this).val();
        if (data.contactID) {
          data.caseID = caseId();
          return $.post(CRM.url('civicrm/case/ajax/addclient'), data);
        }
        return false;
      }
    },
    '#addMembersToGroupDialog': {
      pre: function() {
        $('[name=add_member_to_group_contact_id]', this).val('').crmEntityRef({create: true, select: {multiple: true}});
      },
      post: function(data) {
        var requests = [],
          cids = $('[name=add_member_to_group_contact_id]', this).val();
        if (cids) {
          $.each(cids.split(','), function (k, cid) {
            requests.push(['group_contact', 'create', $.extend({contact_id: cid}, data)]);
          });
          return CRM.api3(requests);
        }
        return false;
      }
    }
  },
    detached = {};

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
          CRM.confirm({
            title: $('option:first', this).text(),
            message: ts('Add the %1 set of scheduled activities to this case?', {1: '<em>' + $('option:selected', this).text() + '</em>'})
          })
            .on('crmConfirm:yes', function() {
              $('[name=_qf_CaseView_next]').click();
            })
            .on('crmConfirm:no', function() {
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
      .on('click', 'a.case-miniform', function(e) {
        var dialog,
          $el = $(this),
          target = $el.attr('href');
        function submit() {
          // Call post function with dialog as context and link data as param
          var submission = miniForms[target].post.call(dialog[0], $.extend({}, $el.data()));
          // Function should return a deferred object
          if (submission) {
            dialog.block();
            submission.done(function(data) {
              dialog.dialog('close');
              var table = $el.closest('table.dataTable');
              refresh(table.length ? table : $el.attr('rel'));
              if ($.isPlainObject(data) && data.is_error && data.error_message) {
                CRM.alert(data.error_message, ts('Error'), 'error');
              }
            });
            return false;
          }
          // Validation failed - show an error msg on empty fields
          else if (submission === false) {
            $(':input', dialog).not('.select2-container *').each(function() {
              if (!$(this).val()) {
                $(this).crmError(ts('Please select a value'));
              }
            })
          }
          return submission;
        }
        dialog = CRM.confirm({
          title: $(this).attr('title') || $(this).text(),
          message: detached[target],
          resizable: true,
          options: {yes: ts('Save'), no: ts('Cancel')},
          open: function() {
            miniForms[target].pre && miniForms[target].pre.call(this, $el.data());
          }
        })
          .on('dialogclose', function() {
            detached[target] = $(target, dialog).detach();
          })
          .on('crmConfirm:yes', submit);
        e.preventDefault();
      });

    // Keep the state of accordions when refreshing
    var accordionStates = [];
    $('#crm-main-content-wrapper')
      .on('crmBeforeLoad', function(e) {
        if ($(e.target).is(this)) {
          accordionStates = [];
          $('.crm-accordion-wrapper', this).each(function() {
            accordionStates.push($(this).hasClass('collapsed'));
          });
        }
      })
      .on('crmLoad', function(e) {
        if ($(e.target).is(this)) {
          var $targets = $('.crm-accordion-wrapper', this);
          $.each(accordionStates, function(i, isCollapsed) {
            $targets.eq(i).toggleClass('collapsed', isCollapsed);
          });
        }
      });
  });
}(cj, CRM))
