(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2');

  crmMailing2.factory('crmMailingMgr', function($q, crmApi) {
    var pickDefaultMailComponent = function(type) {
      var mcs = _.where(CRM.crmMailing.headerfooterList, {
        component_type:type,
        is_default: "1"
      });
      return (mcs.length >= 1) ? mcs[0].id : null;
    };

    return {
      // @param scalar idExpr a number or the literal string 'new'
      // @return Promise|Object Mailing (per APIv3)
      getOrCreate: function (idExpr) {
        return (idExpr == 'new') ? this.create() : this.get(idExpr);
      },
      // @return Promise Mailing (per APIv3)
      get: function (id) {
        return crmApi('Mailing', 'getsingle', {id: id}).then(function(mailing){
          return crmApi('MailingGroup', 'get', {mailing_id: id}).then(function(groupResult){
            mailing.groups = {include: [], exclude: []};
            mailing.mailings = {include: [], exclude: []};
            _.each(groupResult.values, function(mailingGroup) {
              var bucket = (mailingGroup.entity_table == 'civicrm_group') ? 'groups' : 'mailings';
              mailing[bucket][mailingGroup.group_type].push(mailingGroup.entity_id);
            });
            return mailing;
          });
        });
      },
      // @return Object Mailing (per APIv3)
      create: function () {
        return {
          name: "revert this", // fixme
          campaign_id: null,
          from: _.where(CRM.crmMailing.fromAddress, {is_default: "1"})[0].label,
          replyto_email: "",
          subject: "For {contact.display_name}", // fixme
          dedupe_email: "1",
          groups: {include: [2], exclude: [4]}, // fixme
          mailings: {include: [], exclude: []},
          body_html: "<b>Hello</b> {contact.display_name}", // fixme
          body_text: "Hello {contact.display_name}", // fixme
          footer_id: null, // pickDefaultMailComponent('Footer'),
          header_id: null, // pickDefaultMailComponent('Header'),
          visibility: "Public Pages",
          url_tracking: "1",
          dedupe_email: "1",
          forward_replies: "0",
          auto_responder: "0",
          open_tracking: "1",
          override_verp: "1",
          optout_id: pickDefaultMailComponent('OptOut'),
          reply_id: pickDefaultMailComponent('Reply'),
          resubscribe_id: pickDefaultMailComponent('Resubscribe'),
          unsubscribe_id: pickDefaultMailComponent('Unsubscribe')
        };
      },

      // @param mailing Object (per APIv3)
      // @return Promise an object with "subject", "body_text", "body_html"
      preview: function preview(mailing) {
        var params = _.extend({}, mailing, {
          options:  {force_rollback: 1},
          'api.Mailing.preview': {
            id: '$value.id',
          }
        });
        return crmApi('Mailing', 'create', params).then(function(result){
          console.log('preview', params, result);
          return result.values[result.id]['api.Mailing.preview'].values;
        });
      },

      // @param mailing Object (per APIv3)
      // @param int previewLimit
      // @return Promise for a list of recipients (mailing_id, contact_id, api.contact.getvalue, api.email.getvalue)
      previewRecipients: function(mailing, previewLimit) {
        // To get list of recipients, we tentatively save the mailing and
        // get the resulting recipients -- then rollback any changes.
        var params = _.extend({}, mailing, {
          options:  {force_rollback: 1},
          'api.MailingRecipients.get': {
            mailing_id: '$value.id',
            options: { limit: previewLimit },
            'api.contact.getvalue': {'return': 'display_name'},
            'api.email.getvalue': {'return': 'email'}
          }
        });
        return crmApi('Mailing', 'create', params).then(function(recipResult){
          return recipResult.values[recipResult.id]['api.MailingRecipients.get'].values;
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
