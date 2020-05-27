(function(angular, $, _) {

  angular.module('crmMailing').controller('ListMailingsCtrl', ['crmLegacy', 'crmNavigator', function ListMailingsCtrl(crmLegacy, crmNavigator) {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    var new_url = crmLegacy.url('civicrm/mailing/browse/unscheduled', {reset: 1, scheduled: 'false'});
    crmNavigator.redirect(new_url);
  }]);

})(angular, CRM.$, CRM._);
