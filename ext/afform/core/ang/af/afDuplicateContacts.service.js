(function(angular, $) {
  "use strict";

  // Warn about existing contacts that look like the one being entered,
  // using the same "begins with" lookup as the standard contact form.
  angular.module('af').factory('afDuplicateContacts', function($rootScope, crmApi4, crmThrottle, crmUiAlert) {
    const ts = CRM.ts('org.civicrm.afform');

    function getWhere(contactType, values) {
      const where = [];
      Object.keys(values).forEach((name) => {
        if (values[name].length >= CRM.af.checkSimilarMinLength) {
          where.push([name === 'email' ? 'email_primary.email' : name, 'LIKE', values[name] + '%']);
        }
      });
      if (where.length && contactType) {
        where.push(['contact_type', '=', contactType]);
      }
      return where;
    }

    // One checker per fieldset, so that two contacts being entered on the same
    // form don't share a query or overwrite one another's message.
    function createChecker() {
      let params,
        message;

      // Always the same function, so crmThrottle can discard all but the latest check
      function runQuery() {
        return crmApi4('Contact', 'get', params);
      }

      function closeMessage() {
        if (message && message.close) {
          message.close();
        }
        message = null;
      }

      function showMessage(contacts) {
        const scope = $rootScope.$new();
        scope.ts = ts;
        scope.crmUrl = CRM.url;
        scope.contacts = contacts;
        message = crmUiAlert({
          templateUrl: '~/af/afDuplicateContacts.html',
          title: contacts.length === 1 ? ts('Similar Contact Found') : ts('Similar Contacts Found'),
          type: 'alert',
          scope: scope,
          options: {expires: false}
        });
      }

      return function check(contactType, values) {
        const where = getWhere(contactType, values);
        if (!where.length) {
          closeMessage();
          return;
        }
        params = {
          select: ['display_name', 'email_primary.email'],
          where: where,
          orderBy: {sort_name: 'ASC'},
          limit: 10
        };
        crmThrottle(runQuery).then((contacts) => {
          closeMessage();
          if (contacts.length) {
            showMessage(contacts);
          }
        });
      };
    }

    return {
      enabled: function() {
        return !!(CRM.af && CRM.af.checkSimilarContacts);
      },
      createChecker: createChecker
    };
  });

})(angular, CRM.$);
