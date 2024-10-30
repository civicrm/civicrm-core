(function(angular, $, _) {

  var crmCaseType = angular.module('crmCaseType', CRM.angRequires('crmCaseType'));

  // Note: This template will be passed to cloneDeep(), so don't put any funny stuff in here!
  var newCaseTypeTemplate = {
    title: "",
    name: "",
    is_active: "1",
    weight: "1",
    definition: {
      activityTypes: [
        {name: 'Open Case', max_instances: 1},
        {name: 'Email'},
        {name: 'Follow up'},
        {name: 'Meeting'},
        {name: 'Phone Call'}
      ],
      activitySets: [
        {
          name: 'standard_timeline',
          label: 'Standard Timeline',
          timeline: '1', // Angular won't bind checkbox correctly with numeric 1
          activityTypes: [
            {name: 'Open Case', status: 'Completed' }
          ]
        }
      ],
      caseRoles: [
        { name: 'Case Coordinator', creator: '1', manager: '1'}
      ]
    }
  };

  crmCaseType.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/caseType', {
        templateUrl: '~/crmCaseType/list.html',
        controller: 'CaseTypeListCtrl',
        resolve: {
          caseTypes: function($route, crmApi) {
            return crmApi('CaseType', 'get', {options: {limit: 0}});
          }
        }
      });
      $routeProvider.when('/caseType/:id', {
        templateUrl: '~/crmCaseType/edit.html',
        controller: 'CaseTypeCtrl',
        resolve: {
          apiCalls: function($route, crmApi) {
            var reqs = {};
            reqs.actStatuses = ['OptionValue', 'get', {
              option_group_id: 'activity_status',
              sequential: 1,
              options: {limit: 0}
            }];
            reqs.caseStatuses = ['OptionValue', 'get', {
              option_group_id: 'case_status',
              sequential: 1,
              options: {
                sort: 'weight',
                limit: 0
              }
            }];
            reqs.actTypes = ['OptionValue', 'get', {
              option_group_id: 'activity_type',
              sequential: 1,
              options: {
                sort: 'name',
                limit: 0
              }
            }];
            reqs.defaultAssigneeTypes = ['OptionValue', 'get', {
              option_group_id: 'activity_default_assignee',
              sequential: 1,
              options: {
                limit: 0
              }
            }];
            reqs.relTypes = ['RelationshipType', 'get', {
              sequential: 1,
              options: {
                sort: 'label_a_b',
                limit: 0
              }
            }];
            if ($route.current.params.id !== 'new') {
              reqs.caseType = ['CaseType', 'getsingle', {
                id: $route.current.params.id
              }];
            }
            return crmApi(reqs);
          }
        }
      });
    }
  ]);

  // Add a new record by name.
  // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />
  crmCaseType.directive('crmAddName', function() {
    return {
      restrict: 'AE',
      template: '<input class="add-activity crm-action-menu fa-plus" type="hidden" />',
      link: function(scope, element, attrs) {

        var input = $('input', element);

        scope._resetSelection = function() {
          $(input).select2('close');
          $(input).select2('val', '');
          scope[attrs.crmVar] = '';
        };

        $(input).crmSelect2({
          data: function () {
            return { results: scope[attrs.crmOptions] };
          },
          createSearchChoice: function(term) {
            return {id: term, text: term + ' (' + ts('new') + ')'};
          },
          createSearchChoicePosition: 'bottom',
          placeholder: attrs.placeholder
        });
        $(input).on('select2-selecting', function(e) {
          scope[attrs.crmVar] = e.val;
          scope.$evalAsync(attrs.crmOnAdd);
          scope.$evalAsync('_resetSelection()');
          e.preventDefault();
        });
      }
    };
  });

  crmCaseType.directive('crmEditableTabTitle', function($timeout) {
    return {
      restrict: 'AE',
      link: function(scope, element, attrs) {
        element.addClass('crm-editable crm-editable-enabled');
        var titleLabel = $(element).find('span');
        var penIcon = $('<i class="crm-i fa-pencil crm-editable-placeholder" aria-hidden="true"></i>').prependTo(element);
        var saveButton = $('<button type="button"><i class="crm-i fa-check" aria-hidden="true"></i></button>').appendTo(element);
        var cancelButton = $('<button type="cancel"><i class="crm-i fa-times" aria-hidden="true"></i></button>').appendTo(element);
        $('button', element).wrapAll('<div class="crm-editable-form" style="display:none" />');
        var buttons = $('.crm-editable-form', element);
        titleLabel.on('click', startEditMode);
        penIcon.on('click', startEditMode);

        function detectEscapeKeyPress (event) {
          var isEscape = false;

          if ("key" in event) {
              isEscape = (event.key == "Escape" || event.key == "Esc");
          } else {
              isEscape = (event.keyCode == 27);
          }

          return isEscape;
        }

        function detectEnterKeyPress (event) {
          var isEnter = false;

          if ("key" in event) {
            isEnter = (event.key == "Enter");
          } else {
            isEnter = (event.keyCode == 13);
          }

          return isEnter;
        }

        function startEditMode () {
          if (titleLabel.is(":focus")) {
            return;
          }

          penIcon.hide();
          buttons.show();

          saveButton.click(function () {
            updateTextValue();
            stopEditMode();
          });

          cancelButton.click(function () {
            revertTextValue();
            stopEditMode();
          });

          $(element).addClass('crm-editable-editing');

          titleLabel
            .attr("contenteditable", "true")
            .focus()
            .focusout(function (event) {
              $timeout(function () {
                revertTextValue();
                stopEditMode();
              }, 500);
            })
            .keydown(function(event) {
              event.stopImmediatePropagation();

              if(detectEscapeKeyPress(event)) {
                revertTextValue();
                stopEditMode();
              } else if(detectEnterKeyPress(event)) {
                event.preventDefault();
                updateTextValue();
                stopEditMode();
              }
            });
        }

        function stopEditMode () {
          titleLabel.removeAttr("contenteditable").off("focusout");
          titleLabel.off("keydown");
          saveButton.off("click");
          cancelButton.off("click");
          $(element).removeClass('crm-editable-editing');

          penIcon.show();
          buttons.hide();
        }

        function revertTextValue () {
          titleLabel.text(scope.activitySet.label);
        }

        function updateTextValue () {
          var updatedTitle = titleLabel.text();

          scope.$evalAsync(function () {
            scope.activitySet.label = updatedTitle;
          });
        }
      }
    };
  });

  crmCaseType.controller('CaseTypeCtrl', function($scope, $timeout, crmApi, apiCalls, crmUiHelp) {
    var defaultAssigneeDefaultValue, ts;

    (function init () {

      ts = $scope.ts = CRM.ts('civi_case');
      $scope.hs = crmUiHelp({file: 'CRM/Case/CaseType'});
      $scope.locks = { caseTypeName: true, activitySetName: true };
      $scope.workflows = { timeline: 'Timeline', sequence: 'Sequence' };
      defaultAssigneeDefaultValue = _.find(apiCalls.defaultAssigneeTypes.values, { is_default: '1' }) || {};

      storeApiCallsResults();
      initCaseType();
      initCaseTypeDefinition();
      initSelectedStatuses();

      $timeout(function() {
        $('form[name=editCaseTypeForm] .crmCaseType-acttab').tabs({show: true, hide: true});
      });
    })();

    /// Stores the api calls results in the $scope object
    function storeApiCallsResults() {
      $scope.activityStatuses = apiCalls.actStatuses.values;
      $scope.caseStatuses = _.indexBy(apiCalls.caseStatuses.values, 'name');
      $scope.activityTypes = _.indexBy(apiCalls.actTypes.values, 'name');
      $scope.activityTypeOptions = _.map(apiCalls.actTypes.values, formatActivityTypeOption);
      $scope.defaultAssigneeTypes = apiCalls.defaultAssigneeTypes.values;
      // for dropdown lists, only include enabled choices
      $scope.relationshipTypeOptions = getRelationshipTypeOptions(true);
      // for comparisons, include disabled
      $scope.relationshipTypeOptionsAll = getRelationshipTypeOptions(false);
      // stores the default assignee values indexed by their option name:
      $scope.defaultAssigneeTypeValues = _.chain($scope.defaultAssigneeTypes)
        .indexBy('name').mapValues('value').value();
    }

    // Returns the relationship type options. If the relationship is
    // bidirectional (Ex: Spouse of) it adds a single option otherwise it adds
    // two options representing the relationship type directions (Ex: Employee
    // of, Employer of).
    //
    // The relationship dropdown needs to be given IDs with direction,
    // while the role name in the xml needs values that are names (with
    // implicit direction).
    //
    // At any rate, the labels should follow the convention in the UI of
    // describing case roles from the perspective of the client, while the
    // names must follow the convention in the XML of describing case roles
    // from the perspective of the non-client.
    //
    // @param onlyActive bool
    //   If true, only include enabled relationship types.
    // @return array[object]
    //   object: {
    //     xmlName: The name corresponding to what's stored in xml/caseRoles.
    //     text: The text in dropdowns, i.e. <option value="id">text</option>
    //       It's called text because that is what select2 is expecting.
    //     id: The id value in dropdowns, i.e. <option value="id">text</option>
    //       Is the concatenation of id+direction, e.g. 2_a_b.
    //       It's called id because that is what select2 is expecting.
    //   }
    function getRelationshipTypeOptions(onlyActive) {
      var relationshipTypesToUse;
      if (onlyActive) {
        relationshipTypesToUse = _.filter(apiCalls.relTypes.values, {is_active: "1"});
      } else {
        relationshipTypesToUse = apiCalls.relTypes.values;
      }
      return _.transform(relationshipTypesToUse, function(result, relType) {
        var isBidirectionalRelationship = relType.label_a_b === relType.label_b_a;

        // The order here of a's and b's here is important regarding
        // unidirectional and bidirectional. Because the xml spec DOES support
        // direction for activity auto-assignees, if this is changed you might
        // end up with activity assignees in existing xml that then come
        // through as blank in the dropdown on the timelines tab if it's a
        // bidirectional relationship. E.g. if spouse is stored in an existing
        // xml definition as 2_a_b, but we only push 2_b_a, then it won't match
        // in the dropdown.
        //
        // This has some implications for when we add a new type on the fly
        // later, but it works out ok as long as we also do it in the same
        // direction there. See notes in addRoleOnTheFly().
        result.push({
          // This is what we want to store in the caseRoles.name field,
          // which corresponds to the xml file, when we send it back to
          // the server.
          xmlName: relType.name_a_b,
          // This has to be called text because select2 is expecting it.
          // And yes it's the opposite direction from name.
          text: relType.label_b_a,
          // This has to be called id because select2 is expecting it.
          id: relType.id + '_a_b'
        });

        if (!isBidirectionalRelationship) {
          result.push({
            xmlName: relType.name_b_a,
            text: relType.label_a_b,
            id: relType.id + '_b_a'
          });
        }
      }, []);
    }

    /// initializes the case type object
    function initCaseType() {
      var isNewCaseType = !apiCalls.caseType;

      if (isNewCaseType) {
        $scope.caseType = _.cloneDeep(newCaseTypeTemplate);
      } else {
        $scope.caseType = apiCalls.caseType;
      }
    }

    /// initializes the case type definition object
    function initCaseTypeDefinition() {
      $scope.caseType.definition = $scope.caseType.definition || [];
      $scope.caseType.definition.activityTypes = $scope.caseType.definition.activityTypes || [];
      $scope.caseType.definition.activitySets = $scope.caseType.definition.activitySets || [];
      $scope.caseType.definition.caseRoles = $scope.caseType.definition.caseRoles || [];
      $scope.caseType.definition.statuses = $scope.caseType.definition.statuses || [];
      $scope.caseType.definition.timelineActivityTypes = $scope.caseType.definition.timelineActivityTypes || [];
      $scope.caseType.definition.restrictActivityAsgmtToCmsUser = $scope.caseType.definition.restrictActivityAsgmtToCmsUser || 0;
      $scope.caseType.definition.activityAsgmtGrps = $scope.caseType.definition.activityAsgmtGrps || [];

      _.each($scope.caseType.definition.activitySets, function (set) {
        _.each(set.activityTypes, function (type, name) {
          var isDefaultAssigneeTypeUndefined = _.isUndefined(type.default_assignee_type);
          var typeDefinition = $scope.activityTypes[type.name];
          type.label = (typeDefinition && typeDefinition.label) || type.name;

          if (isDefaultAssigneeTypeUndefined) {
            type.default_assignee_type = defaultAssigneeDefaultValue.value;
          }
        });
      });

      // Go lookup and add client-perspective labels for
      // $scope.caseType.definition.caseRoles, since the xml doesn't have them
      // and we need to display them in the roles table.
      _.each($scope.caseType.definition.caseRoles, function (set) {
        _.each($scope.relationshipTypeOptionsAll, function (relationshipTypeOption) {
          if (relationshipTypeOption.xmlName == set.name) {
            // relationshipTypeOption.text here corresponds to one of the
            // apiCalls.relTypes label fields (i.e. civicrm_relationship_type
            // label database fields). It has to be called text because
            // it's used in select2 which expects it to be called text.
            set.displayLabel = relationshipTypeOption.text;
            // break out of inner `each` loop
            return false;
          }
        });
      });
    }

    /// initializes the selected statuses
    function initSelectedStatuses() {
      $scope.selectedStatuses = {};

      _.each(apiCalls.caseStatuses.values, function (status) {
        $scope.selectedStatuses[status.name] = !$scope.caseType.definition.statuses.length || $scope.caseType.definition.statuses.indexOf(status.name) > -1;
      });
    }

    $scope.addActivitySet = function(workflow) {
      var activitySet = {};
      activitySet[workflow] = '1';
      activitySet.activityTypes = [];

      var offset = 1;
      var names = _.pluck($scope.caseType.definition.activitySets, 'name');
      while (_.contains(names, workflow + '_' + offset)) offset++;
      activitySet.name = workflow + '_' + offset;
      activitySet.label = (offset == 1  ) ? $scope.workflows[workflow] : ($scope.workflows[workflow] + ' #' + offset);

      $scope.caseType.definition.activitySets.push(activitySet);
      _.defer(function() {
        $('.crmCaseType-acttab').tabs('refresh').tabs({active: -1});
      });
    };

    function formatActivityTypeOption(type) {
      return {id: type.name, text: type.label, icon: type.icon};
    }

    function addActivityToSet(activitySet, activityTypeName) {
      activitySet.activityTypes = activitySet.activityTypes || [];
	    var activity = {
          name: activityTypeName,
          label: $scope.activityTypes[activityTypeName].label,
          status: 'Scheduled',
          reference_activity: 'Open Case',
          reference_offset: '1',
          reference_select: 'newest',
          default_assignee_type: $scope.defaultAssigneeTypeValues.NONE
      };
      activitySet.activityTypes.push(activity);
      if(typeof activitySet.timeline !== "undefined" && activitySet.timeline == "1") {
        $scope.caseType.definition.timelineActivityTypes.push(activity);
      }
    }

    function resetTimelineActivityTypes() {
        $scope.caseType.definition.timelineActivityTypes = [];
        angular.forEach($scope.caseType.definition.activitySets, function(activitySet) {
            angular.forEach(activitySet.activityTypes, function(activityType) {
                $scope.caseType.definition.timelineActivityTypes.push(activityType);
            });
        });
    }

    function createActivity(name, callback) {
      CRM.loadForm(CRM.url('civicrm/admin/options/activity_type', {action: 'add', reset: 1, label: name, component_id: 7}))
        .on('crmFormSuccess', function(e, data) {
          $scope.activityTypes[data.optionValue.name] = data.optionValue;
          $scope.activityTypeOptions.push(formatActivityTypeOption(data.optionValue));
          callback(data.optionValue);
          $scope.$digest();
        });
    }

    // Add a new activity entry to an activity-set
    $scope.addActivity = function(activitySet, activityType) {
      if ($scope.activityTypes[activityType]) {
        addActivityToSet(activitySet, activityType);
      } else {
        createActivity(activityType, function(newActivity) {
          addActivityToSet(activitySet, newActivity.name);
        });
      }
    };

    /// Add a new top-level activity-type entry
    $scope.addActivityType = function(activityType) {
      var names = _.pluck($scope.caseType.definition.activityTypes, 'name');
      if (!_.contains(names, activityType)) {
        // Add an activity type that exists
        if ($scope.activityTypes[activityType]) {
          $scope.caseType.definition.activityTypes.push({name: activityType});
        } else {
          createActivity(activityType, function(newActivity) {
            $scope.caseType.definition.activityTypes.push({name: newActivity.name});
          });
        }
      }
    };

    /// Clears the activity's default assignee values for relationship and contact
    $scope.clearActivityDefaultAssigneeValues = function(activity) {
      activity.default_assignee_relationship = null;
      activity.default_assignee_contact = null;
    };

    // Add a new role.
    // Called from the select2 dropdown when a selection is made.
    //
    // @param roles array
    //   The roles currently in the table.
    // @param roleIdOrLabel string
    //   The trick here is that since you can add roles on the fly, the
    //   roleIdOrLabel parameter can be two different types of things. It can be
    //   the id, like '2_a_b' if it's an existing choice that was selected, or
    //   it can be a LABEL if they typed something that isn't in the list. If
    //   the latter the select2 has no choice but to give us a label because
    //   there is no id yet.
    $scope.addRole = function(roles, roleIdOrLabel) {
      var matchingRole;
      // First check does what we've been given match up to any relationship
      // type, based on id, which is the id from the select2 (i.e. html
      // <option value="id">)
      var matchingRoles = _.filter($scope.relationshipTypeOptions, {id: roleIdOrLabel});
      if (matchingRoles.length) {
        matchingRole = matchingRoles.shift();
      }
      // If found, is the corresponding machine name in the list of existing
      // roles for the case type. Unfortunately, caseRoles only stores name,
      // which doesn't indicate the id or direction, because the xml spec
      // doesn't support those.
      var names = _.pluck($scope.caseType.definition.caseRoles, 'name');
      if (matchingRole) {
        // If it's not in the table already, add it, otherwise do nothing since
        // don't want to add it twice.
        if (!_.contains(names, matchingRole.xmlName)) {
          roles.push({name: matchingRole.xmlName, displayLabel: matchingRole.text});
        }
      } else {
         // Not a known relationship type, so create on-the-fly.
         // At this point roleIdOrLabel must be the new label they just typed.
         CRM.loadForm(CRM.url('civicrm/admin/reltype', {action: 'add', reset: 1, label_a_b: roleIdOrLabel}))
          .on('crmFormSuccess', function(e, data) {
            var newType = _.values(data.relationshipType)[0];
            $scope.$apply(function() {
              $scope.addRoleOnTheFly(roles, newType);
            });
          });
      }
    };

    // Add a newly created relationship type as a role to the table and
    // update the list of options.
    //
    // @param roles array
    //   The roles currently in the table.
    // @param newType array
    //   The array returned from the api call that created the new type
    //   earlier.
    $scope.addRoleOnTheFly = function(roles, newType) {
      // Add it to the roles table. Assume they want the A-B direction since
      // that's what they would have typed.
      // Name and label are opposites here because name represents the value
      // in the xml here which historically is the opposite.
      roles.push({name: newType.name_b_a, displayLabel: newType.label_a_b});

      // But now add both directions as option choices for future dropdown
      // selections.
      // Note that to keep in line with the original population on init,
      // we're pushing a different direction here than we just added to the
      // table, but there's only two possibilities:
      // 1. Labels are the same, and since it's a new type name_a_b and
      // name_b_a will therefore be the same, and so it doesn't matter which
      // name is stored in caseRoles.
      // 2. Labels are different, in which case we're also going to push the
      // other direction below.
      // So either way we're covered.
      // See also note in getRelationshipTypeOptions().
      var newRelTypeOption = {
        xmlName: newType.name_a_b,
        // Yes text is the opposite direction from name here.
        text: newType.label_b_a,
        id: newType.id + '_a_b'
      };
      $scope.relationshipTypeOptions.push(newRelTypeOption);
      $scope.relationshipTypeOptionsAll.push(newRelTypeOption);
      // Add the other direction if different.
      if (newType.label_a_b != newType.label_b_a) {
        newRelTypeOption = {
          xmlName: newType.name_b_a,
          text: newType.label_a_b,
          id: newType.id + '_b_a'
        };
        $scope.relationshipTypeOptions.push(newRelTypeOption);
        $scope.relationshipTypeOptionsAll.push(newRelTypeOption);
      }
    };

    $scope.onManagerChange = function(managerRole) {
      angular.forEach($scope.caseType.definition.caseRoles, function(caseRole) {
        if (caseRole != managerRole) {
          caseRole.manager = '0';
        }
      });
    };

    $scope.removeItem = function(array, item) {
      var idx = _.indexOf(array, item);
      if (idx != -1) {
        array.splice(idx, 1);
        resetTimelineActivityTypes();
      }
    };

    $scope.isForkable = function() {
      return !$scope.caseType.id || $scope.caseType.is_forkable;
    };

    $scope.newStatus = function() {
      CRM.loadForm(CRM.url('civicrm/admin/options/case_status', {action: 'add', reset: 1}))
        .on('crmFormSuccess', function(e, data) {
          $scope.caseStatuses[data.optionValue.name] = data.optionValue;
          $scope.selectedStatuses[data.optionValue.name] = true;
          $scope.$digest();
        });
    };

    $scope.isNewActivitySetAllowed = function(workflow) {
      switch (workflow) {
        case 'timeline':
          return true;
        case 'sequence':
          return 0 === _.where($scope.caseType.definition.activitySets, {sequence: '1'}).length;
        default:
          CRM.console('warn', 'Denied access to unrecognized workflow: (' + workflow + ')');
          return false;
      }
    };

    $scope.isActivityRemovable = function(activitySet, activity) {
      return true;
    };

    $scope.isValidName = function(name) {
      return !name || name.match(/^[a-zA-Z0-9_]+$/);
    };

    $scope.getWorkflowName = function(activitySet) {
      var result = 'Unknown';
      _.each($scope.workflows, function(value, key) {
        if (activitySet[key]) result = value;
      });
      return result;
    };

    /**
     * Determine which HTML partial to use for a particular
     *
     * @return string URL of the HTML partial
     */
    $scope.activityTableTemplate = function(activitySet) {
      if (activitySet.timeline) {
        return '~/crmCaseType/timelineTable.html';
      } else if (activitySet.sequence) {
        return '~/crmCaseType/sequenceTable.html';
      } else {
        return '';
      }
    };

    $scope.dump = function() {
      console.log($scope.caseType);
    };

    $scope.save = function() {
      // Add selected statuses
      var selectedStatuses = [];
      _.each($scope.selectedStatuses, function(v, k) {
        if (v) selectedStatuses.push(k);
      });
      // Ignore if ALL or NONE selected
      $scope.caseType.definition.statuses = selectedStatuses.length == _.size($scope.selectedStatuses) ? [] : selectedStatuses;

      if ($scope.caseType.definition.activityAsgmtGrps) {
        $scope.caseType.definition.activityAsgmtGrps = $scope.caseType.definition.activityAsgmtGrps.toString().split(",");
      }

      function dropDisplaylabel (v) {
        delete v.displayLabel;
      }

      // strip out labels from $scope.caseType.definition.caseRoles
      _.map($scope.caseType.definition.caseRoles, dropDisplaylabel);

      var result = crmApi('CaseType', 'create', $scope.caseType, true);
      result.then(function(data) {
        if (data.is_error === 0 || data.is_error == '0') {
          $scope.caseType.id = data.id;
          window.location.href = '#/caseType';
        }
      });
    };

    $scope.$watchCollection('caseType.definition.activitySets', function() {
      _.defer(function() {
        $('.crmCaseType-acttab').tabs('refresh');
      });
    });

    var updateCaseTypeName = function () {
      if (!$scope.caseType.id && $scope.locks.caseTypeName) {
        // Should we do some filtering? Lowercase? Strip whitespace?
        var t = $scope.caseType.title ? $scope.caseType.title : '';
        $scope.caseType.name = t.replace(/ /g, '_').replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
      }
    };
    $scope.$watch('locks.caseTypeName', updateCaseTypeName);
    $scope.$watch('caseType.title', updateCaseTypeName);

    if (!$scope.isForkable()) {
      CRM.alert(ts('The CiviCase XML file for this case-type prohibits editing the definition.'));
    }

  });

  crmCaseType.controller('CaseTypeListCtrl', function($scope, crmApi, caseTypes) {
    var ts = $scope.ts = CRM.ts('civi_case');

    $scope.caseTypes = caseTypes.values;
    $scope.toggleCaseType = function (caseType) {
      caseType.is_active = (caseType.is_active == '1') ? '0' : '1';
      crmApi('CaseType', 'create', caseType, true)
        .catch(function (data) {
          caseType.is_active = (caseType.is_active == '1') ? '0' : '1'; // revert
          $scope.$digest();
        });
    };
    $scope.deleteCaseType = function (caseType) {
      crmApi('CaseType', 'delete', {id: caseType.id}, {
        error: function (data) {
          CRM.alert(data.error_message, ts('Error'), 'error');
        }
      })
        .then(function (data) {
          delete caseTypes.values[caseType.id];
        });
    };
    $scope.revertCaseType = function (caseType) {
      caseType.definition = 'null';
      caseType.is_forked = '0';
      crmApi('CaseType', 'create', caseType, true)
        .catch(function (data) {
          caseType.is_forked = '1'; // restore
          $scope.$digest();
        });
    };
  });

})(angular, CRM.$, CRM._);
