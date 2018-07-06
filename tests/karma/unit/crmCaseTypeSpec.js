/* global $, _, CRM:true */
'use strict';

describe('crmCaseType', function() {
  var $controller;
  var $compile;
  var $httpBackend;
  var $q;
  var $rootScope;
  var $timeout;
  var apiCalls;
  var ctrl;
  var compile;
  var defaultAssigneeDefaultValue;
  var scope;

  beforeEach(function() {
    CRM.resourceUrls = {
      'civicrm': ''
    };
    // CRM_Case_XMLProcessor::REL_TYPE_CNAME
    CRM.crmCaseType = {
      'REL_TYPE_CNAME': 'label_b_a'
    };
    module('crmCaseType');
    module('crmJsonComparator');
    inject(function(crmJsonComparator) {
      crmJsonComparator.register(jasmine);
    });
  });

  beforeEach(inject(function(_$controller_, _$compile_, _$httpBackend_, _$q_, _$rootScope_, _$timeout_) {
    $controller = _$controller_;
    $compile = _$compile_;
    $httpBackend = _$httpBackend_;
    $q = _$q_;
    $rootScope = _$rootScope_;
    $timeout = _$timeout_;
  }));

  describe('CaseTypeCtrl', function() {
    beforeEach(function () {
      apiCalls = {
        actStatuses: {
          values: [
            {
              "id": "272",
              "option_group_id": "25",
              "label": "Scheduled",
              "value": "1",
              "name": "Scheduled",
              "filter": "0",
              "is_default": "1",
              "weight": "1",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1"
            },
            {
              "id": "273",
              "option_group_id": "25",
              "label": "Completed",
              "value": "2",
              "name": "Completed",
              "filter": "0",
              "weight": "2",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1"
            }
          ]
        },
        caseStatuses: {
          values: [
            {
              "id": "290",
              "option_group_id": "28",
              "label": "Ongoing",
              "value": "1",
              "name": "Open",
              "grouping": "Opened",
              "filter": "0",
              "is_default": "1",
              "weight": "1",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1"
            },
            {
              "id": "291",
              "option_group_id": "28",
              "label": "Resolved",
              "value": "2",
              "name": "Closed",
              "grouping": "Closed",
              "filter": "0",
              "weight": "2",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1"
            }
          ]
        },
        actTypes: {
          values: [
            {
              "id": "784",
              "option_group_id": "2",
              "label": "ADC referral",
              "value": "62",
              "name": "ADC referral",
              "filter": "0",
              "is_default": "0",
              "weight": "64",
              "is_optgroup": "0",
              "is_reserved": "0",
              "is_active": "1",
              "component_id": "7"
            },
            {
              "id": "32",
              "option_group_id": "2",
              "label": "Add Client To Case",
              "value": "27",
              "name": "Add Client To Case",
              "filter": "0",
              "is_default": "0",
              "weight": "26",
              "description": "",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1",
              "component_id": "7"
            },
            {
              "id": "18",
              "option_group_id": "2",
              "label": "Open Case",
              "value": "13",
              "name": "Open Case",
              "filter": "0",
              "is_default": "0",
              "weight": "13",
              "is_optgroup": "0",
              "is_reserved": "1",
              "is_active": "1",
              "component_id": "7",
              "icon": "fa-folder-open-o"
            },
            {
              "id": "857",
              "option_group_id": "2",
              "label": "Medical evaluation",
              "value": "55",
              "name": "Medical evaluation",
              "filter": "0",
              "is_default": "0",
              "weight": "56",
              "is_optgroup": "0",
              "is_reserved": "0",
              "is_active": "1",
              "component_id": "7"
            },
          ]
        },
        relTypes: {
          values: [
            {
              "id": "14",
              "name_a_b": "Benefits Specialist is",
              "label_a_b": "Benefits Specialist is",
              "name_b_a": "Benefits Specialist",
              "label_b_a": "Benefits Specialist",
              "description": "Benefits Specialist",
              "contact_type_a": "Individual",
              "contact_type_b": "Individual",
              "is_reserved": "0",
              "is_active": "1"
            },
            {
              "id": "9",
              "name_a_b": "Case Coordinator is",
              "label_a_b": "Case Coordinator is",
              "name_b_a": "Case Coordinator",
              "label_b_a": "Case Coordinator",
              "description": "Case Coordinator",
              "contact_type_a": "Individual",
              "contact_type_b": "Individual",
              "is_reserved": "0",
              "is_active": "1"
            },
            {
              "id": "2",
              "name_a_b": "Spouse of",
              "label_a_b": "Spouse of",
              "name_b_a": "Spouse of",
              "label_b_a": "Spouse of",
              "description": "Spousal relationship.",
              "contact_type_a": "Individual",
              "contact_type_b": "Individual",
              "is_reserved": "0",
              "is_active": "1"
            }
          ]
        },
        caseType: {
          "id": "1",
          "name": "housing_support",
          "title": "Housing Support",
          "description": "Help homeless individuals obtain temporary and long-term housing",
          "is_active": "1",
          "is_reserved": "0",
          "weight": "1",
          "is_forkable": "1",
          "is_forked": "",
          "definition": {
            "activityTypes": [
              {"name": "Open Case", "max_instances": "1"}
            ],
            "activitySets": [
              {
                "name": "standard_timeline",
                "label": "Standard Timeline",
                "timeline": "1",
                "activityTypes": [
                  {
                    "name": "Open Case",
                    "status": "Completed"
                  },
                  {
                    "name": "Medical evaluation",
                    "reference_activity": "Open Case",
                    "reference_offset": "1",
                    "reference_select": "newest"
                  }
                ]
              }
            ],
            "caseRoles": [
              {
                "name": "Homeless Services Coordinator",
                "creator": "1",
                "manager": "1"
              }
            ]
          }
        },
        defaultAssigneeTypes: {
          values: [
              {
                "id": "1174",
                "option_group_id": "152",
                "label": "None",
                "value": "1",
                "name": "NONE",
                "filter": "0",
                "is_default": "1",
                "weight": "1",
                "is_optgroup": "0",
                "is_reserved": "0",
                "is_active": "1"
              },
              {
                "id": "1175",
                "option_group_id": "152",
                "label": "By relationship to workflow client",
                "value": "2",
                "name": "BY_RELATIONSHIP",
                "filter": "0",
                "is_default": "0",
                "weight": "2",
                "is_optgroup": "0",
                "is_reserved": "0",
                "is_active": "1"
              },
              {
                "id": "1176",
                "option_group_id": "152",
                "label": "Specific contact",
                "value": "3",
                "name": "SPECIFIC_CONTACT",
                "filter": "0",
                "is_default": "0",
                "weight": "3",
                "is_optgroup": "0",
                "is_reserved": "0",
                "is_active": "1"
              },
              {
                "id": "1177",
                "option_group_id": "152",
                "label": "User creating the workflow",
                "value": "4",
                "name": "USER_CREATING_THE_CASE",
                "filter": "0",
                "is_default": "0",
                "weight": "4",
                "is_optgroup": "0",
                "is_reserved": "0",
                "is_active": "1"
              }
          ]
        }
      };
      defaultAssigneeDefaultValue = _.find(apiCalls.defaultAssigneeTypes.values, { is_default: '1' });
      scope = $rootScope.$new();
      ctrl = $controller('CaseTypeCtrl', {$scope: scope, apiCalls: apiCalls});
    });

    it('should load activity statuses', function() {
      expect(scope.activityStatuses).toEqualData(apiCalls.actStatuses.values);
    });

    it('should load activity types', function() {
      expect(scope.activityTypes['ADC referral']).toEqualData(apiCalls.actTypes.values[0]);
    });

    it('should store the default assignee types', function() {
      expect(scope.defaultAssigneeTypes).toBe(apiCalls.defaultAssigneeTypes.values);
    });

    it('should store the default assignee types values indexed by name', function() {
      var defaultAssigneeTypeValues = _.chain(apiCalls.defaultAssigneeTypes.values)
        .indexBy('name').mapValues('value').value();

      expect(scope.defaultAssigneeTypeValues).toEqual(defaultAssigneeTypeValues);
    });

    it('should store the default assignee relationship type options', function() {
      var defaultRelationshipTypeOptions = _.transform(apiCalls.relTypes.values, function(result, relType) {
        var isBidirectionalRelationship = relType.label_a_b === relType.label_b_a;

        result.push({
          label: relType.label_b_a,
          value: relType.id + '_b_a'
        });

        if (!isBidirectionalRelationship) {
          result.push({
            label: relType.label_a_b,
            value: relType.id + '_a_b'
          });
        }
      }, []);

      expect(scope.defaultRelationshipTypeOptions).toEqual(defaultRelationshipTypeOptions);
    });

    it('addActivitySet should add an activitySet to the case type', function() {
      scope.addActivitySet('timeline');
      var activitySets = scope.caseType.definition.activitySets;
      var newSet = activitySets[activitySets.length - 1];
      expect(newSet.name).toBe('timeline_1');
      expect(newSet.timeline).toBe('1');
      expect(newSet.label).toBe('Timeline');
    });

    it('addActivitySet handles second timeline correctly', function() {
      scope.addActivitySet('timeline');
      scope.addActivitySet('timeline');
      var activitySets = scope.caseType.definition.activitySets;
      var newSet = activitySets[activitySets.length - 1];
      expect(newSet.name).toBe('timeline_2');
      expect(newSet.timeline).toBe('1');
      expect(newSet.label).toBe('Timeline #2');
    });

    describe('when clearing the activity\'s default assignee type values', function() {
      var activity;

      beforeEach(function() {
        activity = {
          default_assignee_relationship: 1,
          default_assignee_contact: 2
        };

        scope.clearActivityDefaultAssigneeValues(activity);
      });

      it('clears the default assignee relationship for the activity', function() {
        expect(activity.default_assignee_relationship).toBe(null);
      });

      it('clears the default assignee contact for the activity', function() {
        expect(activity.default_assignee_contact).toBe(null);
      });
    });

    describe('when adding a new activity to a set', function() {
      var activitySet;

      beforeEach(function() {
        activitySet = { activityTypes: [] };
        scope.activityTypes = { comment: { label: 'Add a new comment' } };

        scope.addActivity(activitySet, 'comment');
      });

      it('adds a new Comment activity to the set', function() {
        expect(activitySet.activityTypes[0]).toEqual({
          name: 'comment',
          label: scope.activityTypes.comment.label,
          status: 'Scheduled',
          reference_activity: 'Open Case',
          reference_offset: '1',
          reference_select: 'newest',
          default_assignee_type: defaultAssigneeDefaultValue.value
        });
      });
    });

    describe('when creating a new workflow', function() {
      beforeEach(inject(function ($controller) {
        apiCalls.caseType = null;

        ctrl = $controller('CaseTypeCtrl', {$scope: scope, apiCalls: apiCalls});
      }));

      it('sets default values for the case type title, name, and active status', function() {
        expect(scope.caseType).toEqual(jasmine.objectContaining({
          title: '',
          name: '',
          is_active: '1'
        }));
      });

      it('adds an Open Case activty to the default activty set', function() {
        expect(scope.caseType.definition.activitySets[0].activityTypes).toEqual([{
          name: 'Open Case',
          label: 'Open Case',
          status: 'Completed',
          default_assignee_type: defaultAssigneeDefaultValue.value
        }]);
      });
    });
  });

  describe('crmAddName', function () {
    var element;

    beforeEach(function() {
      scope = $rootScope.$new();
      scope.activityTypeOptions = [1, 2, 3];
      element = '<span crm-add-name crm-options="activityTypeOptions"></span>';

      spyOn(CRM.$.fn, 'crmSelect2').and.callThrough();

      element = $compile(element)(scope);
      scope.$digest();
    });

    describe('when initialized', function () {
      var returnValue;

      beforeEach (function () {
        var dataFunction = CRM.$.fn.crmSelect2.calls.argsFor(0)[0].data;
        returnValue = dataFunction();
      });

      it('updates the UI with updated value of scope variable', function () {
        expect(returnValue).toEqual({ results: scope.activityTypeOptions });
      });
    });
  });

  describe('crmEditableTabTitle', function () {
    var element, titleLabel, penIcon, saveButton, cancelButton;

    beforeEach(function() {
      scope = $rootScope.$new();
      element = '<div crm-editable-tab-title title="Click to edit">' +
        '<span>{{ activitySet.label }}</span>' +
        '</div>';

      scope.activitySet = { label: 'Title'};
      element = $compile(element)(scope);

      titleLabel = $(element).find('span');
      penIcon = $(element).find('i.fa-pencil');
      saveButton = $(element).find('button[type=button]');
      cancelButton = $(element).find('button[type=cancel]');

      scope.$digest();
    });

    describe('when initialized', function () {
      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });
    });

    describe('when clicked on title label', function () {
      beforeEach(function () {
        titleLabel.click();
      });

      it('hides the pen icon', function () {
        expect(penIcon.css('display') === 'none').toBe(true);
      });

      it('shows the save button', function () {
        expect(saveButton.parent().css('display') !== 'none').toBe(true);
      });

      it('makes the title editable', function () {
        expect(titleLabel.attr('contenteditable')).toBe('true');
      });
    });

    describe('when clicked outside of the editable area', function () {
      beforeEach(function () {
        titleLabel.click();
        titleLabel.text('Updated Title');
        titleLabel.blur();
        $timeout.flush();
        scope.$digest();
      });

      it('shows the pen icon', function () {
        expect(penIcon.css('display') !== 'none').toBe(true);
      });

      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });

      it('makes the title non editable', function () {
        expect(titleLabel.attr('contenteditable')).not.toBe('true');
      });

      it('does not update the title in angular context', function () {
        expect(scope.activitySet.label).toBe('Title');
      });
    });

    describe('when ESCAPE key is pressed while typing', function () {
      beforeEach(function () {
        var eventObj = $.Event('keydown');
        eventObj.key = 'Escape';

        titleLabel.click();
        titleLabel.text('Updated Title');
        titleLabel.trigger(eventObj);
        scope.$digest();
      });

      it('shows the pen icon', function () {
        expect(penIcon.css('display') !== 'none').toBe(true);
      });

      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });

      it('makes the title non editable', function () {
        expect(titleLabel.attr('contenteditable')).not.toBe('true');
      });

      it('does not update the title', function () {
        expect(scope.activitySet.label).toBe('Title');
      });
    });

    describe('when ENTER key is pressed while typing', function () {
      beforeEach(function () {
        var eventObj = $.Event('keydown');
        eventObj.key = 'Enter';

        titleLabel.click();
        titleLabel.text('Updated Title');
        titleLabel.trigger(eventObj);
        scope.$digest();
      });

      it('shows the pen icon', function () {
        expect(penIcon.css('display') !== 'none').toBe(true);
      });

      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });

      it('makes the title non editable', function () {
        expect(titleLabel.attr('contenteditable')).not.toBe('true');
      });

      it('updates the title in angular context', function () {
        expect(scope.activitySet.label).toBe('Updated Title');
      });
    });

    describe('when SAVE button is clicked', function () {
      beforeEach(function () {
        titleLabel.click();
        titleLabel.text('Updated Title');
        saveButton.click();
        scope.$digest();
      });

      it('shows the pen icon', function () {
        expect(penIcon.css('display') !== 'none').toBe(true);
      });

      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });

      it('makes the title non editable', function () {
        expect(titleLabel.attr('contenteditable')).not.toBe('true');
      });

      it('updates the title in angular context', function () {
        expect(scope.activitySet.label).toBe('Updated Title');
      });
    });

    describe('when CANCEL button is clicked', function () {
      beforeEach(function () {
        titleLabel.click();
        titleLabel.text('Updated Title');
        cancelButton.click();
        scope.$digest();
      });

      it('shows the pen icon', function () {
        expect(penIcon.css('display') !== 'none').toBe(true);
      });

      it('hides the save and cancel button', function () {
        expect(saveButton.parent().css('display') === 'none').toBe(true);
        expect(cancelButton.parent().css('display') === 'none').toBe(true);
      });

      it('makes the title non editable', function () {
        expect(titleLabel.attr('contenteditable')).not.toBe('true');
      });

      it('does not update the title in angular context', function () {
        expect(scope.activitySet.label).toBe('Title');
      });
    });
  });

  describe('CaseTypeListCtrl', function() {
    var caseTypes, crmApiSpy;

    beforeEach(function() {
      caseTypes = {
        values: {
          1: { id: 1 },
          2: { id: 2 },
          3: { id: 3 }
        }
      };
      crmApiSpy = jasmine.createSpy('crmApi').and.returnValue($q.resolve());
      scope = $rootScope.$new();
      ctrl = $controller('CaseTypeListCtrl', {
        $scope: scope,
        caseTypes: caseTypes,
        crmApi: crmApiSpy
      });
    });

    it('should store an index of case types', function() {
      expect(scope.caseTypes).toEqual(caseTypes.values);
    });

    describe('toggleCaseType', function() {
      var caseType = { id: _.uniqueId() };

      describe('when the case is active', function() {
        beforeEach(function() {
          caseType.is_active = '1';

          scope.toggleCaseType(caseType);
        });

        it('sets the case type as inactive', function() {
          expect(crmApiSpy).toHaveBeenCalledWith('CaseType', 'create', jasmine.objectContaining({
            id: caseType.id,
            is_active: '0'
          }), true);
        });
      });

      describe('when the case is inactive', function() {
        beforeEach(function() {
          caseType.is_active = '0';

          scope.toggleCaseType(caseType);
        });

        it('sets the case type as active', function() {
          expect(crmApiSpy).toHaveBeenCalledWith('CaseType', 'create', jasmine.objectContaining({
            id: caseType.id,
            is_active: '1'
          }), true);
        });
      });
    });

    describe('deleteCaseType', function() {
      var caseType = { id: _.uniqueId() };

      beforeEach(function() {
        crmApiSpy.and.returnValue($q.resolve(caseType));
        scope.caseTypes[caseType.id] = caseType;

        scope.deleteCaseType(caseType);
        scope.$digest();
      });

      describe('when the case type can be deleted', function() {
        it('deletes the case from the api', function() {
          expect(crmApiSpy).toHaveBeenCalledWith('CaseType', 'delete', { id: caseType.id }, jasmine.any(Object));
        });

        it('removes the case type from the list', function() {
          expect(scope.caseTypes[caseType.id]).toBeUndefined();
        });
      });

      describe('when the case type cannot be delted', function() {
        var error = { error_message: 'Error Message' };

        beforeEach(function() {
          var errorHandler;

          crmApiSpy.and.returnValue($q.reject(error));
          scope.caseTypes[caseType.id] = caseType;

          spyOn(CRM, 'alert');
          scope.deleteCaseType(caseType);
          scope.$digest();

          errorHandler = crmApiSpy.calls.mostRecent().args[3].error;
          errorHandler(error);
        });

        it('displays the error message', function() {
          expect(CRM.alert).toHaveBeenCalledWith(error.error_message, 'Error', 'error');
        });
      });

      describe('revertCaseType', function() {
        var caseType = {
          id: _.uniqueId(),
          definition: {},
          is_forked: '1'
        };

        describe('when reverting a case type', function() {
          beforeEach(function() {
            scope.revertCaseType(caseType);
          });

          it('resets the case type information using the api', function() {
            expect(crmApiSpy).toHaveBeenCalledWith('CaseType', 'create', jasmine.objectContaining({
              id: caseType.id,
              definition: 'null',
              is_forked: '0'
            }), true);
          });
        });
      });
    });
  });
});
