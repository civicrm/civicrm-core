(function(angular, $, _) {

  angular.module('crmStatusPage')
    .filter('trusted', function($sce){ return $sce.trustAsHtml; })

    // Todo: abstract this into a generic crmUi directive?
    .directive('statuspagePopupMenu', function($timeout) {
      return {
        templateUrl: '~/crmStatusPage/SnoozeOptions.html',
        transclude: true,

        link: function(scope, element, attr) {
          element.on('click', '.hush-menu-button', function() {
            $timeout(function() {
              $('ul', element).show().menu();
              element.closest('h3').addClass('menuopen');
              $('body').one('click', function() {
                $('ul', element).menu('destroy').hide();
                element.closest('h3').removeClass('menuopen');
              });
            });
          });
          // TODO: Is there a more "Angular" way to do this animation?
          element.on('click', 'button:not(.hush-menu-button), li', function() {
            $(this).closest('div.crm-status-item').slideUp();
          });
        }
      };
    });

})(angular, CRM.$, CRM._);
