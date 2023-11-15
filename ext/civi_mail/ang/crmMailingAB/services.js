(function (angular, $, _) {

  function OptionGroup(values) {
    this.get = function get(value) {
      var r = _.where(values, {value: '' + value});
      return r.length > 0 ? r[0] : null;
    };
    this.getByName = function get(name) {
      var r = _.where(values, {name: '' + name});
      return r.length > 0 ? r[0] : null;
    };
    this.getAll = function getAll() {
      return values;
    };
  }

  angular.module('crmMailingAB').factory('crmMailingABCriteria', function () {
    // TODO Get data from server
    var values = {
      '1': {value: 'subject', name: 'subject', label: ts('Test different "Subject" lines')},
      '2': {value: 'from', name: 'from', label: ts('Test different "From" lines')},
      '3': {value: 'full_email', name: 'full_email', label: ts('Test entirely different emails')}
    };
    return new OptionGroup(values);
  });

  angular.module('crmMailingAB').factory('crmMailingABStatus', function () {
    // TODO Get data from server
    var values = {
      '1': {value: '1', name: 'Draft', label: ts('Draft')},
      '2': {value: '2', name: 'Testing', label: ts('Testing')},
      '3': {value: '3', name: 'Final', label: ts('Final')}
    };
    return new OptionGroup(values);
  });

  // CrmMailingAB is a data-model which combines an AB test (APIv3 "MailingAB"), three mailings (APIv3 "Mailing"),
  // and three sets of attachments (APIv3 "Attachment").
  //
  // example:
  //   var abtest = new CrmMailingAB(123);
  //   abtest.load().then(function(){
  //     alert("Mailing A is named "+abtest.mailings.a.name);
  //   });
  angular.module('crmMailingAB').factory('CrmMailingAB', function (crmApi, crmMailingMgr, $q, CrmAttachments) {
    function CrmMailingAB(id) {
      this.id = id;
      this.mailings = {};
      this.attachments = {};
    }

    angular.extend(CrmMailingAB.prototype, {
      getAutosaveSignature: function() {
        //modified date is unset so that it gets ignored in comparison
        //its value is overwritten with the save response from the server and may differ from the local value,
        //which would result in an unnecessary auto-save
        var mailings = angular.copy(this.mailings);
        _.each(mailings, function(mailing) {
          mailing.modified_date = undefined;
        });
        return [
          this.ab,
          mailings,
          this.attachments.a.getAutosaveSignature(),
          this.attachments.b.getAutosaveSignature(),
          this.attachments.c.getAutosaveSignature()
        ];
      },
      // @return Promise CrmMailingAB
      load: function load() {
        var crmMailingAB = this;
        if (!crmMailingAB.id) {
          crmMailingAB.ab = {
            name: '',
            status: 'Draft',
            mailing_id_a: null,
            mailing_id_b: null,
            mailing_id_c: null,
            testing_criteria: 'subject',
            winner_criteria: null,
            specific_url: '',
            declare_winning_time: null,
            group_percentage: 10
          };
          var mailingDefaults = {
            // Most defaults provided by Mailing.create API, but we
            // want to force-enable tracking.
            open_tracking: "1",
            url_tracking: "1",
            mailing_type:"experiment"
          };
          crmMailingAB.mailings.a = crmMailingMgr.create(mailingDefaults);
          crmMailingAB.mailings.b = crmMailingMgr.create(mailingDefaults);
          mailingDefaults.mailing_type = 'winner';
          crmMailingAB.mailings.c = crmMailingMgr.create(mailingDefaults);
          crmMailingAB.attachments.a = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab.mailing_id_a};
          });
          crmMailingAB.attachments.b = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab.mailing_id_b};
          });
          crmMailingAB.attachments.c = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab.mailing_id_c};
          });

          var dfr = $q.defer();
          dfr.resolve(crmMailingAB);
          return dfr.promise;
        }
        else {
          return crmApi('MailingAB', 'get', {id: crmMailingAB.id})
            .then(function (abResult) {
              if (abResult.count != 1) {
                throw "Failed to load AB Test";
              }
              crmMailingAB.ab = abResult.values[abResult.id];
              return crmMailingAB._loadMailings();
            });
        }
      },
      // @return Promise CrmMailingAB
      save: function save() {
        var crmMailingAB = this;
        return crmMailingAB._saveMailings()
          .then(function () {
            return crmApi('MailingAB', 'create', crmMailingAB.ab)
              .then(function (abResult) {
                if (!crmMailingAB.id) {
                  crmMailingAB.id = crmMailingAB.ab.id = abResult.id;
                }
              });
          })
          .then(function () {
            return crmMailingAB;
          });
      },
      // Schedule the test
      // @return Promise CrmMailingAB
      // Note: Submission may cause the server state to change. Consider abtest.submit().then(...abtest.load()...)
      submitTest: function submitTest() {
        var crmMailingAB = this;
        var params = {
          id: this.ab.id,
          status: 'Testing',
          approval_date: 'now',
          scheduled_date: this.mailings.a.scheduled_date ? this.mailings.a.scheduled_date : 'now'
        };
        return crmApi('MailingAB', 'submit', params)
          .then(function () {
            return crmMailingAB.load();
          });
      },
      // Schedule the final mailing
      // @return Promise CrmMailingAB
      // Note: Submission may cause the server state to change. Consider abtest.submit().then(...abtest.load()...)
      submitFinal: function submitFinal(winner_id) {
        var crmMailingAB = this;
        var params = {
          id: this.ab.id,
          status: 'Final',
          winner_id: winner_id,
          approval_date: 'now',
          scheduled_date: this.mailings.c.scheduled_date ? this.mailings.c.scheduled_date : 'now'
        };
        return crmApi('MailingAB', 'submit', params)
          .then(function () {
            return crmMailingAB.load();
          });
      },
      // @param mailing Object (per APIv3)
      // @return Promise
      'delete': function () {
        if (this.id) {
          return crmApi('MailingAB', 'delete', {id: this.id});
        }
        else {
          var d = $q.defer();
          d.resolve();
          return d.promise;
        }
      },
      // Load mailings A, B, and C (if available)
      // @return Promise CrmMailingAB
      _loadMailings: function _loadMailings() {
        var crmMailingAB = this;
        var todos = {};
        _.each(['a', 'b', 'c'], function (mkey) {
          if (crmMailingAB.ab['mailing_id_' + mkey]) {
            todos[mkey] = crmMailingMgr.get(crmMailingAB.ab['mailing_id_' + mkey])
              .then(function (mailing) {
                crmMailingAB.mailings[mkey] = mailing;
                crmMailingAB.attachments[mkey] = new CrmAttachments(function () {
                  return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab['mailing_id_' + mkey]};
                });
                return crmMailingAB.attachments[mkey].load();
              }).catch(function (ex){
                console.error(ex);
                throw new Error('Failed to load Mailings');
              });
          }
          else {
            crmMailingAB.mailings[mkey] = crmMailingMgr.create();
            crmMailingAB.attachments[mkey] = new CrmAttachments(function () {
              return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab['mailing_id_' + mkey]};
            });
          }
        });
        return $q.all(todos).then(function () {
          return crmMailingAB;
        });
      },
      // Save mailings A, B, and C (if available)
      // @return Promise CrmMailingAB
      _saveMailings: function _saveMailings() {
        var crmMailingAB = this;
        var todos = {};
        var p = $q.when(true);
        _.each(['a', 'b', 'c'], function (mkey) {
          if (!crmMailingAB.mailings[mkey]) {
            return;
          }
          if (crmMailingAB.ab['mailing_id_' + mkey]) {
            // paranoia: in case caller forgot to manage id on mailing
            crmMailingAB.mailings[mkey].id = crmMailingAB.ab['mailing_id_' + mkey];
          }
          p = p.then(function(){
            return crmMailingMgr.save(crmMailingAB.mailings[mkey])
              .then(function () {
                crmMailingAB.ab['mailing_id_' + mkey] = crmMailingAB.mailings[mkey].id;
                return crmMailingAB.attachments[mkey].save();
              });
          });
        });
        return p.then(function () {
          return crmMailingAB;
        });
      }

    });
    return CrmMailingAB;
  });

})(angular, CRM.$, CRM._);
