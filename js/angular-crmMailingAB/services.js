(function (angular, $, _) {

  // FIXME: surely there's already some helper which can do this in one line?
  // @return string "YYYY-MM-DD hh:mm:ss"
  var createNow = function () {
    var currentdate = new Date();
    var yyyy = currentdate.getFullYear();
    var mm = currentdate.getMonth() + 1;
    mm = mm < 10 ? '0' + mm : mm;
    var dd = currentdate.getDate();
    dd = dd < 10 ? '0' + dd : dd;
    var hh = currentdate.getHours();
    hh = hh < 10 ? '0' + hh : hh;
    var min = currentdate.getMinutes();
    min = min < 10 ? '0' + min : min;
    var sec = currentdate.getSeconds();
    sec = sec < 10 ? '0' + sec : sec;
    return yyyy + "-" + mm + "-" + dd + " " + hh + ":" + min + ":" + sec;
  };

  angular.module('crmMailingAB').factory('crmMailingABCriteria', function () {
    // TODO Get data from server
    var values = {
      '1': {value: '1', name: 'Subject lines', label: ts('Test different "Subject" lines')},
      '2': {value: '2', name: 'From names', label: ts('Test different "From" lines')},
      '3': {value: '3', name: 'Two different emails', label: ts('Test entirely different emails')}
    };
    return {
      get: function get(value) {
        var r = _.where(values, {value: '' + value});
        return r.length > 0 ? r[0] : null;
      },
      getAll: function getAll() {
        return values;
      }
    };
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
      // @return Promise CrmMailingAB
      load: function load() {
        var crmMailingAB = this;
        if (!crmMailingAB.id) {
          crmMailingAB.ab = {
            name: 'Example', // FIXME
            status: 'Draft',
            mailing_id_a: null,
            mailing_id_b: null,
            mailing_id_c: null,
            domain_id: null,
            testing_criteria_id: 1, // FIXME
            winner_criteria_id: null,
            specific_url: '',
            declare_winning_time: null,
            group_percentage: 10
          };
          crmMailingAB.mailings.a = crmMailingMgr.create();
          crmMailingAB.mailings.b = crmMailingMgr.create();
          crmMailingAB.mailings.c = crmMailingMgr.create();
          crmMailingAB.attachments.a = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab['mailing_id_a']};
          });
          crmMailingAB.attachments.b = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab['mailing_id_b']};
          });
          crmMailingAB.attachments.c = new CrmAttachments(function () {
            return {entity_table: 'civicrm_mailing', entity_id: crmMailingAB.ab['mailing_id_c']};
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
                crmMailingAB.ab.id = abResult.id;
              });
          })
          .then(function () {
            return crmMailingAB;
          });
      },
      // Schedule the test
      // @return Promise CrmMailingAB
      // Note: Submission may cause the server state to change. Consider abtest.submit().then(abtest.load)
      submit: function submit(newStatus) {
        var crmMailingAB = this;
        var params = {
          id: this.id,
          status: newStatus,
          approval_date: createNow(),
          scheduled_date: this.mailings.a.scheduled_date ? this.mailings.a.scheduled_date : createNow()
        };
        return crmApi('MailingAB', 'submit', params)
          .then(function () {
            return crmMailingAB;
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
