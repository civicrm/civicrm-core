// https://civicrm.org/licensing
(function($, CRM) {

  function refresh(table) {
    $('#crm-main-content-wrapper').crmSnippet('refresh');
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
        var tagsChecked = $("#tags", this) ? $("#tags", this).val() : '',
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
        var $contactField = $('[name=add_role_contact_id]', this);
        $('[name=role_type]', this)
          .off('.miniform')
          .on('change.miniform', function() {
            var val = $(this).val();
            $contactField.val('').change().prop('disabled', !val);
            if (val) {
              prepareRelationshipField(val, $contactField);
            }
          })
          .val('')
          .change();
        $contactField.val('').crmEntityRef();
      },
      post: function(data) {
        var contactID = $('[name=add_role_contact_id]', this).val(),
          relType = $('[name=role_type]', this).val();
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
        // Clear stale value since this form can be reused multiple times
        $('[name=edit_role_contact_id]', this).val('');
        prepareRelationshipField(data.rel_type, $('[name=edit_role_contact_id]', this));
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

  function prepareRelationshipField(relType, $contactField) {
    var
      pieces = relType.split('_'),
      rType = pieces[0],
      target = pieces[2], // b or a
      relationshipType = CRM.vars.relationshipTypes[rType],
      api = {params: {}};
    if (relationshipType['contact_type_' + target]) {
      api.params.contact_type = relationshipType['contact_type_' + target];
    }
    if (relationshipType['contact_sub_type_' + target]) {
      api.params.contact_sub_type = relationshipType['contact_sub_type_' + target];
    }
    if (relationshipType['group_' + target]) {
      api.params.group = {IN: relationshipType['group_' + target]};
    }
    $contactField
      .data('create-links', !relationshipType['group_' + target])
      .data('api-params', api)
      .data('user-filter', {})
      .attr('placeholder', relationshipType['placeholder_' + target])
      .change()
      .crmEntityRef();
  }

  function detachMiniForms() {
    detached = {};
    $.each(miniForms, function(selector) {
      detached[selector] = $(selector).detach().removeClass('hiddenElement');
    });
  }

  function showHideInactiveRoles() {
    let showInactive = $('#role_inactive').prop('checked');
    $('[id^=caseRoles-selector] tbody tr').not('.disabled').toggle(!showInactive);
    $('[id^=caseRoles-selector] tbody tr.disabled').toggle(showInactive);
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
      // When changing case subject, record an activity
      .on('crmFormSuccess', '[data-field=subject]', function(e, value) {
        var id = caseId();
        CRM.api3('Activity', 'create', {
          case_id: id,
          activity_type_id: 'Change Case Subject',
          subject: value,
          status_id: 'Completed'
        }).done(function() {
          $('#case_id_' + id).dataTable().api().draw();
        });
      })
      // Toggle to show/hide inactive case roles
      .on('crmLoad', 'table#caseRoles-selector-' + caseId(), showHideInactiveRoles)
      .on('change', '#role_inactive', showHideInactiveRoles)
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
            });
          }
          return submission;
        }
        dialog = CRM.confirm({
          title: $(this).attr('title') || $(this).text(),
          message: detached[target],
          resizable: true,
          options: {yes: ts('Save'), no: ts('Cancel')},
          open: function() {
            if (miniForms[target].pre) miniForms[target].pre.call(this, $el.data());
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
          $('details', this).each(function() {
            accordionStates.push($(this).prop('open') ? true : false);
          });
        }
      })
      .on('crmLoad', function(e) {
        if ($(e.target).is(this)) {
          var $targets = $('details', this);
          $.each(accordionStates, function(i, isOpen) {
            if (isOpen) {
              $targets.eq(i).prop('open', true);
            }
            else {
              $targets.eq(i).removeProp('open');
            }
          });
        }
      });
  });
}(cj, CRM));
