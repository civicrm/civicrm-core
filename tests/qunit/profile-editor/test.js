module('parseTypeList');

var explodeCases = [
  {group_type: '', expected: {coreTypes: {}, subTypes: {}}},
  {group_type: 'Individual', expected: {coreTypes: {'Individual': true}, subTypes: {}}},
  {group_type: 'Activity,Contact', expected: {coreTypes: {'Activity':true, 'Contact': true}, subTypes: {}}},
  {group_type: 'Individual,Activity,Student\0ActivityType:2:28', expected: {coreTypes: {'Individual':true, 'Activity': true, 'Student': true}, subTypes: {"ActivityType":{"2": true, "28": true}}}},
  {group_type: 'Individual,Activity,Student;;ActivityType:2:28', expected: {coreTypes: {'Individual':true, 'Activity': true, 'Student': true}, subTypes: {"ActivityType":{"2": true, "28": true}}}},
  {group_type: ['Individual,Activity,Student','ActivityType:2:28'], expected: {coreTypes: {'Individual':true, 'Activity': true, 'Student': true}, subTypes: {"ActivityType":{"2": true, "28": true}}}}
];

_.each(explodeCases, function(explodeCase, explodeCaseIndex) {
  test("#" + explodeCaseIndex + ": With group_type=" + explodeCase.group_type, function() {
    deepEqual(CRM.UF.parseTypeList(explodeCase.group_type), explodeCase.expected);
  });
});

module('UFGroupModel.checkGroupType');

/**
 * For a description of group_type, see CRM_Core_BAO_UFGroup::updateGroupTypes
 */

var cases = [
  {group_type: null, validTypes: 'Individual,Contact,Activity', expected: true},
  {group_type: '', validTypes: 'Individual,Contact,Activity', expected: true},
  {group_type: 'Individual,Event', validTypes: 'Individual, Contact,Activity', expected: false},
  {group_type: 'Individual,Event', validTypes: 'Individual', expected: false},
  {group_type: 'Individual,Event', validTypes: 'Event,Individual', expected: true},
  {group_type: 'Individual', validTypes: 'Individual,Contact,Activity', expected: true},
  {group_type: 'Activity,Contact', validTypes: 'Individual,Contact,Activity', expected: true},
  {group_type: 'Activity,Contact', validTypes: 'Individual,Contact,Activity\0ActivityType:28', expected: true},
  {group_type: 'Individual,Activity\0ActivityType:2', validTypes: 'Individual,Contact,Activity', expected: false},
  {group_type: 'Individual,Activity\0ActivityType:2', validTypes: 'Individual,Contact,Activity\0ActivityType:28', expected: false},
  {group_type: 'Individual,Activity\0ActivityType:28', validTypes: 'Individual,Contact,Activity', expected: false},
  {group_type: 'Individual,Activity\0ActivityType:28', validTypes: 'Individual,Contact,Activity\0ActivityType:28', expected: true},
  {group_type: 'Individual,Activity\0ActivityType:2:28', validTypes: 'Individual,Contact,Activity', expected: false},
  {group_type: 'Individual,Activity\0ActivityType:2:28', validTypes: 'Individual,Contact,Activity\0ActivityType:28', expected: true},
  {group_type: 'Individual,Activity,Student\0ActivityType:28', validTypes: 'Individual,Contact,Activity\0ActivityType:28', expected: false},
  {group_type: 'Individual,Activity,Student\0ActivityType:28', validTypes: 'Individual,Student,Contact,Activity\0ActivityType:28', expected: true}
];

_.each(cases, function(caseDetails, caseIndex) {
  test("#" + caseIndex + ": With group_type=" + caseDetails.group_type, function() {
    var ufGroupModel = new CRM.UF.UFGroupModel({
      group_type: caseDetails.group_type
    });
    equal(ufGroupModel.checkGroupType(caseDetails.validTypes), caseDetails.expected);
  });
});
