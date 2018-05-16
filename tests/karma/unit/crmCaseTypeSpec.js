/* global $, _, CRM:true */
'use strict';

describe('crmCaseType', function() {
  var $controller;
  var $compile;
  var $httpBackend;
  var $q;
  var $rootScope;
  var apiCalls;
  var ctrl;
  var compile;
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

  beforeEach(inject(function(_$controller_, _$compile_, _$httpBackend_, _$q_, _$rootScope_) {
    $controller = _$controller_;
    $compile = _$compile_;
    $httpBackend = _$httpBackend_;
    $q = _$q_;
    $rootScope = _$rootScope_;
  }));

  describe('CaseTypeCtrl', function() {
    beforeEach(function () {
      apiCalls = {
        caseTypeCategories: getCaseTypeCategoriesSampleData(),
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
        }
      };
      scope = $rootScope.$new();
      ctrl = $controller('CaseTypeCtrl', {$scope: scope, apiCalls: apiCalls});
    });

    it('should load activity statuses', function() {
      expect(scope.activityStatuses).toEqualData(apiCalls.actStatuses.values);
    });

    it('should load activity types', function() {
      expect(scope.activityTypes['ADC referral']).toEqualData(apiCalls.actTypes.values[0]);
    });

    it('should load case type categories', function() {
      expect(scope.caseTypeCategories).toEqual(apiCalls.caseTypeCategories.values);
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

    describe('when creating a new case type', function() {
      var expectedDefaultCategory;

      beforeEach(inject(function ($controller) {
        apiCalls.caseType = null;
        expectedDefaultCategory = _.find(apiCalls.caseTypeCategories.values, { is_default: '1' }) || {};
        ctrl = $controller('CaseTypeCtrl', {$scope: scope, apiCalls: apiCalls});
      }));

      it('sets the case type category equal to the default one', function() {
        expect(scope.caseType.category).toEqual(expectedDefaultCategory.value);
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

  describe('CaseTypeListCtrl', function() {
    var caseTypes, crmApiSpy, expectedCaseTypeCategoriesIndexed;

    beforeEach(function() {
      var caseTypeCategories = getCaseTypeCategoriesSampleData();

      caseTypes = {
        values: {
          1: { id: 1 },
          2: { id: 2 },
          3: { id: 3 }
        }
      };
      expectedCaseTypeCategoriesIndexed = _.indexBy(caseTypeCategories.values, 'value');
      crmApiSpy = jasmine.createSpy('crmApi').and.returnValue($q.resolve());
      scope = $rootScope.$new();
      ctrl = $controller('CaseTypeListCtrl', {
        $scope: scope,
        caseTypes: caseTypes,
        caseTypeCategories: caseTypeCategories,
        crmApi: crmApiSpy
      });
    });

    it('should store an index of case types', function() {
      expect(scope.caseTypes).toEqual(caseTypes.values);
    });

    it('should store a list of case type categories indexed by value', function() {
      expect(scope.caseTypeCategoriesIndexed).toEqual(expectedCaseTypeCategoriesIndexed);
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

  /**
   * Returns a sample API response for case type categories option values.
   */
  function getCaseTypeCategoriesSampleData() {
    return {
      values: [
        {
          "id": "1170",
          "option_group_id": "153",
          "label": "Workflow",
          "value": "1",
          "name": "WORKFLOW",
          "filter": "0",
          "is_default": "1",
          "weight": "1",
          "is_optgroup": "0",
          "is_reserved": "1",
          "is_active": "1"
        },
        {
          "id": "1171",
          "option_group_id": "153",
          "label": "Vacancy",
          "value": "2",
          "name": "VACANCY",
          "filter": "0",
          "is_default": "0",
          "weight": "2",
          "is_optgroup": "0",
          "is_reserved": "1",
          "is_active": "1"
        }
      ]
    };
  }
});
