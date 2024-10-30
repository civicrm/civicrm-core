(function(angular, $, _) {

  var lastEmailTokenAlert = null;
  angular.module('crmMailing').controller('EmailBodyCtrl', function EmailBodyCtrl($scope, crmMailingMgr, crmUiAlert, $timeout) {
    var ts = CRM.ts('civi_mail');

    // ex: if (!hasAllTokens(myMailing, 'body_text)) alert('Oh noes!');
    $scope.hasAllTokens = function hasAllTokens(mailing, field) {
      return _.isEmpty(crmMailingMgr.findMissingTokens(mailing, field));
    };

    // ex: checkTokens(myMailing, 'body_text', 'insert:body_text')
    // ex: checkTokens(myMailing, '*')
    $scope.checkTokens = function checkTokens(mailing, field, insertEvent) {
      if (lastEmailTokenAlert) {
        lastEmailTokenAlert.close();
      }
      var missing, insertable;
      if (field == '*') {
        insertable = false;
        missing = angular.extend({},
          crmMailingMgr.findMissingTokens(mailing, 'body_html'),
          crmMailingMgr.findMissingTokens(mailing, 'body_text')
        );
      }
      else {
        insertable = !_.isEmpty(insertEvent);
        missing = crmMailingMgr.findMissingTokens(mailing, field);
      }
      if (!_.isEmpty(missing)) {
        lastEmailTokenAlert = crmUiAlert({
          type: 'error',
          title: ts('Required tokens'),
          templateUrl: '~/crmMailing/EmailBodyCtrl/tokenAlert.html',
          scope: angular.extend($scope.$new(), {
            insertable: insertable,
            insertToken: function(token) {
              $timeout(function() {
                $scope.$broadcast(insertEvent, '{' + token + '}');
                $timeout(function() {
                  checkTokens(mailing, field, insertEvent);
                });
              });
            },
            missing: missing
          })
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
