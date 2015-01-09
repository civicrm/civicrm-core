/* jasmine specs for controllers go here */
describe('Mailing Controllers', function() {

  describe('Mailing Ctrl ', function(){
    var scope, ctrl;

    beforeEach(module('crmMailing'));
    beforeEach(inject(function($rootScope, $controller) {
      scope = $rootScope.$new();
      ctrl = $controller('mailingCtrl', {$scope: scope});
    }));


    it('should check if 5 groups are there', function() {
      expect(scope.cool_api.length).toBe(3);

    });
  });
});
