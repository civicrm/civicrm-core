(function(angular, $, _) {
  // Example usage: <crm oembed-sharing page="https://example.com/foo/bar?id=123"></div>
  angular.module('crmOembedSharing').directive('crmOembedSharing', function() {
    return {
      restrict: 'AE',
      templateUrl: '~/crmOembedSharing/main.html',
      scope: {
        urls: '=',
        options: '=',
      },
      link: function($scope, $el, $attr) {
        var ts = $scope.ts = CRM.ts('oembed');

        function addQuery(baseUrl, key, value) {
          if (value !== '') {
            baseUrl += (!baseUrl.includes('?') ? '?' : '&');
            baseUrl += encodeURIComponent(key) + '=' + encodeURIComponent(value);
          }
          return baseUrl;
        }

        $scope.ctrl = {
          getShareUrl: function() {
            var buf = $scope.urls.share;
            buf = addQuery(buf, 'maxwidth', $scope.options.maxwidth);
            buf = addQuery(buf, 'maxheight', $scope.options.maxheight);
            return buf;
          },
          getIframeCode: function() {
            var iframe = cj('<iframe/>');
            iframe.attr('src', $scope.urls.iframe);
            if ($scope.options.maxwidth) iframe.attr('width', $scope.options.maxwidth);
            if ($scope.options.maxheight) iframe.attr('height', $scope.options.maxheight);
            return iframe[0].outerHTML;
          }
        };

      }
    };
  });
})(angular, CRM.$, CRM._);
