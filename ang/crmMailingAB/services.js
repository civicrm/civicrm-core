(function (angular, $, _) {

  function OptionGroup(values) {
    this.get = function get(value) {
      var r = _.where(values, {value: '' + value});
      return r.length > 0 ? r[0] : null;
    };
    this.getByName = function get(name) {
      var r = _.where(values, {name: '' + name});
      return r.length > 0 ? r[0] : null;
    };
    this.getAll = function getAll() {
      return values;
    };
  }

  angular.module('crmMailingAB').factory('crmMailingABCriteria', function () {
    // TODO Get data from server
    var values = {
      '1': {value: 'subject', name: 'subject', label: ts('Test different "Subject" lines')},
      '2': {value: 'from', name: 'from', label: ts('Test different "From" lines')},
      '3': {value: 'full_email', name: 'full_email', label: ts('Test entirely different emails')}
    };
    return new OptionGroup(values);
  });

  angular.module('crmMailingAB').factory('crmMailingABStatus', function () {
    // TODO Get data from server
    var values = {
      '1': {value: '1', name: 'Draft', label: ts('Draft')},
      '2': {value: '2', name: 'Testing', label: ts('Testing')},
      '3': {value: '3', name: 'Final', label: ts('Final')}
    };
    return new OptionGroup(values);
  });

})(angular, CRM.$, CRM._);
