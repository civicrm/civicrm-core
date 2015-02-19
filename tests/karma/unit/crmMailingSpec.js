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
        crmLegacy.url({back: '/*path*?*query*', front: '/*path*?*query*'});
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
