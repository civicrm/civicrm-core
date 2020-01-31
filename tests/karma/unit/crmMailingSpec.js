'use strict';

describe('crmMailing', function() {

  beforeEach(function() {
    module('crmUtil');
    module('crmMailing');
  });

  describe('ListMailingsCtrl', function() {
    var ctrl;
    var navigator;

    beforeEach(function() {
      navigator = jasmine.createSpyObj('crmNavigator', ['redirect']);
      module(function ($provide) {
        $provide.value('crmNavigator', navigator);
      });
      inject(['crmLegacy', function(crmLegacy) {
        crmLegacy.url({back: '/civicrm-placeholder-url-path?civicrm-placeholder-url-query=1', front: '/civicrm-placeholder-url-path?civicrm-placeholder-url-query=1'});
      }]);
      inject(['$controller', function($controller) {
        ctrl = $controller('ListMailingsCtrl', {});
      }]);
    });

    it('should redirect to unscheduled', function() {
      expect(navigator.redirect).toHaveBeenCalled();
    });
  });
});
