(function(angular, $, _) {
  angular.module('unvalidatedJwtDecode', CRM.angRequires('unvalidatedJwtDecode'));
  angular.module('unvalidatedJwtDecode').filter('unvalidatedJwtDecode', function() {
    return function(token) {
      if (!token) return null;
      var payload = token.split('.')[1];
      var tokenData = url_base64_decode(payload);
      try {
        return JSON.parse(tokenData);
      } catch (e) {
        return tokenData;
      }
    };
  });

  function url_base64_decode(str) {
    var output = str.replace(/-/g, '+').replace(/_/g, '/');
    switch (output.length % 4) {
      case 0:
        break;
      case 2:
        output += '==';
        break;
      case 3:
        output += '=';
        break;
      default:
        throw 'Illegal base64url string!';
    }
    return decodeURIComponent(window.escape(atob(output)));
  }
})(angular, CRM.$, CRM._);
