(function (angular, $, _) {

  // The representation of from/reply-to addresses is inconsistent in the mailing data-model,
  // so the UI must do some adaptation. The crmFromAddresses provides a richer way to slice/dice
  // the available "From:" addrs. Records are like the underlying OptionValues -- but add "email"
  // and "author".
  angular.module('crmMailing').factory('crmFromAddresses', function ($q, crmApi) {
    var emailRegex = /^"(.*)" <([^@>]*@[^@>]*)>$/;
    var addrs = _.map(CRM.crmMailing.fromAddress, function (addr) {
      var match = emailRegex.exec(addr.label);
      return angular.extend({}, addr, {
        email: match ? match[2] : '(INVALID)',
        author: match ? match[1] : '(INVALID)'
      });
    });

    function first(array) {
      return (array.length === 0) ? null : array[0];
    }

    return {
      getAll: function getAll() {
        return addrs;
      },
      getByAuthorEmail: function getByAuthorEmail(author, email, autocreate) {
        var result = null;
        _.each(addrs, function (addr) {
          if (addr.author == author && addr.email == email) {
            result = addr;
          }
        });
        if (!result && autocreate) {
          result = {
            label: '(INVALID) "' + author + '" <' + email + '>',
            author: author,
            email: email
          };
          addrs.push(result);
        }
        return result;
      },
      getByEmail: function getByEmail(email) {
        return first(_.where(addrs, {email: email}));
      },
      getByLabel: function (label) {
        return first(_.where(addrs, {label: label}));
      },
      getDefault: function getDefault() {
        return first(_.where(addrs, {is_default: "1"}));
      }
    };
  });

  angular.module('crmMailing').factory('crmMsgTemplates', function ($q, crmApi) {
    var tpls = _.map(CRM.crmMailing.mesTemplate, function (tpl) {
      return angular.extend({}, tpl, {
        //id: tpl parseInt(tpl.id)
      });
    });
    window.tpls = tpls;
    var lastModifiedTpl = null;
    return {
      // Get a template
      // @param id MessageTemplate id  (per APIv3)
      // @return Promise MessageTemplate (per APIv3)
      get: function get(id) {
        return crmApi('MessageTemplate', 'getsingle', {
           "return": "id,msg_subject,msg_html,msg_title,msg_text",
           "id": id
        });
      },
      // Save a template
      // @param tpl MessageTemplate (per APIv3) For new templates, omit "id"
      // @return Promise MessageTemplate (per APIv3)
      save: function (tpl) {
        return crmApi('MessageTemplate', 'create', tpl).then(function (response) {
          if (!tpl.id) {
            tpl.id = '' + response.id; //parseInt(response.id);
            tpls.push(tpl);
          }
          lastModifiedTpl = tpl;
          return tpl;
        });
      },
      // @return Object MessageTemplate (per APIv3)
      getLastModifiedTpl: function () {
        return lastModifiedTpl;
      },
      getAll: function getAll() {
        return tpls;
      }
    };
  });

  // The crmMailingMgr service provides business logic for loading, saving, previewing, etc
  angular.module('crmMailing').factory('crmMailingMgr', function ($q, crmApi, crmFromAddresses, crmQueue) {
    var qApi = crmQueue(crmApi);
    var pickDefaultMailComponent = function pickDefaultMailComponent(type) {
      var mcs = _.where(CRM.crmMailing.headerfooterList, {
        component_type: type,
        is_default: "1"
      });
      return (mcs.length >= 1) ? mcs[0].id : null;
    };

    return {
      // @param scalar idExpr a number or the literal string 'new'
      // @return Promise|Object Mailing (per APIv3)
      getOrCreate: function getOrCreate(idExpr) {
        return (idExpr == 'new') ? this.create() : this.get(idExpr);
      },
      // @return Promise Mailing (per APIv3)
      get: function get(id) {
        var crmMailingMgr = this;
        var mailing;
        return qApi('Mailing', 'getsingle', {id: id})
          .then(function (getResult) {
            mailing = getResult;
            return $q.all([
              crmMailingMgr._loadGroups(mailing),
              crmMailingMgr._loadJobs(mailing)
            ]);
          })
          .then(function () {
            return mailing;
          });
      },
      // Call MailingGroup.get and merge results into "mailing"
      _loadGroups: function (mailing) {
        return crmApi('MailingGroup', 'get', {mailing_id: mailing.id})
          .then(function (groupResult) {
            mailing.recipients = {};
            mailing.recipients.groups = {include: [], exclude: [], base: []};
            mailing.recipients.mailings = {include: [], exclude: []};
            _.each(groupResult.values, function (mailingGroup) {
              var bucket = (/^civicrm_group/.test(mailingGroup.entity_table)) ? 'groups' : 'mailings';
              var entityId = parseInt(mailingGroup.entity_id);
              mailing.recipients[bucket][mailingGroup.group_type.toLowerCase()].push(entityId);
            });
          });
      },
      // Call MailingJob.get and merge results into "mailing"
      _loadJobs: function (mailing) {
        return crmApi('MailingJob', 'get', {mailing_id: mailing.id, is_test: 0})
          .then(function (jobResult) {
            mailing.jobs = mailing.jobs || {};
            angular.extend(mailing.jobs, jobResult.values);
          });
      },
      // @return Object Mailing (per APIv3)
      create: function create(params) {
        var defaults = {
          jobs: {}, // {jobId: JobRecord}
          recipients: {
            groups: {include: [], exclude: [], base: []},
            mailings: {include: [], exclude: []}
          },
          name: "",
          campaign_id: null,
          replyto_email: "",
          subject: "",
          body_html: "",
          body_text: ""
        };
        return angular.extend({}, defaults, params);
      },

      // @param mailing Object (per APIv3)
      // @return Promise
      'delete': function (mailing) {
        if (mailing.id) {
          return qApi('Mailing', 'delete', {id: mailing.id});
        }
        else {
          var d = $q.defer();
          d.resolve();
          return d.promise;
        }
      },

      // Search the body, header, and footer for required tokens.
      // ex: var msgs = findMissingTokens(mailing, 'body_html');
      findMissingTokens: function(mailing, field) {
        var missing = {};
        if (!_.isEmpty(mailing[field]) && !CRM.crmMailing.disableMandatoryTokensCheck) {
          var body = '';
          if (mailing.footer_id) {
            var footer = _.where(CRM.crmMailing.headerfooterList, {id: mailing.footer_id});
            body = body + footer[0][field];

          }
          body = body + mailing[field];
          if (mailing.header_id) {
            var header = _.where(CRM.crmMailing.headerfooterList, {id: mailing.header_id});
            body = body + header[0][field];
          }

          angular.forEach(CRM.crmMailing.requiredTokens, function(value, token) {
            if (!_.isObject(value)) {
              if (body.indexOf('{' + token + '}') < 0) {
                missing[token] = value;
              }
            }
            else {
              var count = 0;
              angular.forEach(value, function(nestedValue, nestedToken) {
                if (body.indexOf('{' + nestedToken + '}') >= 0) {
                  count++;
                }
              });
              if (count === 0) {
                angular.extend(missing, value);
              }
            }
          });
        }
        return missing;
      },

      // Copy all data fields in (mailingFrom) to (mailingTgt) -- except for (excludes)
      // ex: crmMailingMgr.mergeInto(newMailing, mailingTemplate, ['subject']);
      mergeInto: function mergeInto(mailingTgt, mailingFrom, excludes) {
        var MAILING_FIELDS = [
          // always exclude: 'id'
          'name',
          'campaign_id',
          'from_name',
          'from_email',
          'replyto_email',
          'subject',
          'dedupe_email',
          'recipients',
          'body_html',
          'body_text',
          'footer_id',
          'header_id',
          'visibility',
          'url_tracking',
          'dedupe_email',
          'forward_replies',
          'auto_responder',
          'open_tracking',
          'override_verp',
          'optout_id',
          'reply_id',
          'resubscribe_id',
          'unsubscribe_id'
        ];
        if (!excludes) {
          excludes = [];
        }
        _.each(MAILING_FIELDS, function (field) {
          if (!_.contains(excludes, field)) {
            mailingTgt[field] = mailingFrom[field];
          }
        });
      },

      // @param mailing Object (per APIv3)
      // @return Promise an object with "subject", "body_text", "body_html"
      preview: function preview(mailing) {
        if (CRM.crmMailing.workflowEnabled && !CRM.checkPerm('create mailings') && !CRM.checkPerm('access CiviMail')) {
          return qApi('Mailing', 'preview', {id: mailing.id}).then(function(result) {
            return result.values;
          });
        }
        else {
          // Protect against races in saving and previewing by chaining create+preview.
          var params = angular.extend({}, mailing, mailing.recipients, {
            options: {force_rollback: 1},
            'api.Mailing.preview': {
              id: '$value.id'
            }
          });
          delete params.recipients; // the content was merged in
          return qApi('Mailing', 'create', params).then(function(result) {
            // changes rolled back, so we don't care about updating mailing
            return result.values[result.id]['api.Mailing.preview'].values;
          });
        }
      },

      // @param mailing Object (per APIv3)
      // @param int previewLimit
      // @return Promise for a list of recipients (mailing_id, contact_id, api.contact.getvalue, api.email.getvalue)
      previewRecipients: function previewRecipients(mailing, previewLimit) {
        // To get list of recipients, we tentatively save the mailing and
        // get the resulting recipients -- then rollback any changes.
        var params = angular.extend({}, mailing, mailing.recipients, {
          name: 'placeholder', // for previewing recipients on new, incomplete mailing
          subject: 'placeholder', // for previewing recipients on new, incomplete mailing
          options: {force_rollback: 1},
          'api.mailing_job.create': 1, // note: exact match to API default
          'api.MailingRecipients.get': {
            mailing_id: '$value.id',
            options: {limit: previewLimit},
            'api.contact.getvalue': {'return': 'display_name'},
            'api.email.getvalue': {'return': 'email'}
          }
        });
        delete params.recipients; // the content was merged in
        return qApi('Mailing', 'create', params).then(function (recipResult) {
          // changes rolled back, so we don't care about updating mailing
          return recipResult.values[recipResult.id]['api.MailingRecipients.get'].values;
        });
      },

      previewRecipientCount: function previewRecipientCount(mailing) {
        // To get list of recipients, we tentatively save the mailing and
        // get the resulting recipients -- then rollback any changes.
        var params = angular.extend({}, mailing, mailing.recipients, {
          name: 'placeholder', // for previewing recipients on new, incomplete mailing
          subject: 'placeholder', // for previewing recipients on new, incomplete mailing
          options: {force_rollback: 1},
          'api.mailing_job.create': 1, // note: exact match to API default
          'api.MailingRecipients.getcount': {
            mailing_id: '$value.id'
          }
        });
        delete params.recipients; // the content was merged in
        return qApi('Mailing', 'create', params).then(function (recipResult) {
          // changes rolled back, so we don't care about updating mailing
          return recipResult.values[recipResult.id]['api.MailingRecipients.getcount'];
        });
      },

      // Save a (draft) mailing
      // @param mailing Object (per APIv3)
      // @return Promise
      save: function(mailing) {
        var params = angular.extend({}, mailing, mailing.recipients);

        // Angular ngModel sometimes treats blank fields as undefined.
        angular.forEach(mailing, function(value, key) {
          if (value === undefined || value === null) {
            mailing[key] = '';
          }
        });

        // WORKAROUND: Mailing.create (aka CRM_Mailing_BAO_Mailing::create()) interprets scheduled_date
        // as an *intent* to schedule and creates tertiary records. Saving a draft with a scheduled_date
        // is therefore not allowed. Remove this after fixing Mailing.create's contract.
        delete params.scheduled_date;

        delete params.jobs;

        delete params.recipients; // the content was merged in

        return qApi('Mailing', 'create', params).then(function(result) {
          if (result.id && !mailing.id) {
            mailing.id = result.id;
          }  // no rollback, so update mailing.id
          // Perhaps we should reload mailing based on result?
          return mailing;
        });
      },

      // Schedule/send the mailing
      // @param mailing Object (per APIv3)
      // @return Promise
      submit: function (mailing) {
        var crmMailingMgr = this;
        var params = {
          id: mailing.id,
          approval_date: 'now',
          scheduled_date: mailing.scheduled_date ? mailing.scheduled_date : 'now'
        };
        return qApi('Mailing', 'submit', params)
          .then(function (result) {
            angular.extend(mailing, result.values[result.id]); // Perhaps we should reload mailing based on result?
            return crmMailingMgr._loadJobs(mailing);
          })
          .then(function () {
            return mailing;
          });
      },

      // Immediately send a test message
      // @param mailing Object (per APIv3)
      // @param to Object with either key "email" (string) or "gid" (int)
      // @return Promise for a list of delivery reports
      sendTest: function (mailing, recipient) {
        var params = angular.extend({}, mailing, mailing.recipients, {
          // options:  {force_rollback: 1}, // Test mailings include tracking features, so the mailing must be persistent
          'api.Mailing.send_test': {
            mailing_id: '$value.id',
            test_email: recipient.email,
            test_group: recipient.gid
          }
        });

        // WORKAROUND: Mailing.create (aka CRM_Mailing_BAO_Mailing::create()) interprets scheduled_date
        // as an *intent* to schedule and creates tertiary records. Saving a draft with a scheduled_date
        // is therefore not allowed. Remove this after fixing Mailing.create's contract.
        delete params.scheduled_date;

        delete params.jobs;

        delete params.recipients; // the content was merged in

        return qApi('Mailing', 'create', params).then(function (result) {
          if (result.id && !mailing.id) {
            mailing.id = result.id;
          }  // no rollback, so update mailing.id
          return result.values[result.id]['api.Mailing.send_test'].values;
        });
      }
    };
  });

  // The preview manager performs preview actions while putting up a visible UI (e.g. dialogs & status alerts)
  angular.module('crmMailing').factory('crmMailingPreviewMgr', function (dialogService, crmMailingMgr, crmStatus) {
    return {
      // @param mode string one of 'html', 'text', or 'full'
      // @return Promise
      preview: function preview(mailing, mode) {
        var templates = {
          html: '~/crmMailing/PreviewMgr/html.html',
          text: '~/crmMailing/PreviewMgr/text.html',
          full: '~/crmMailing/PreviewMgr/full.html'
        };
        var result = null;
        var p = crmMailingMgr
          .preview(mailing)
          .then(function (content) {
            var options = CRM.utils.adjustDialogDefaults({
              autoOpen: false,
              title: ts('Subject: %1', {
                1: content.subject
              })
            });
            result = dialogService.open('previewDialog', templates[mode], content, options);
          });
        crmStatus({start: ts('Previewing...'), success: ''}, p);
        return result;
      },

      // @param to Object with either key "email" (string) or "gid" (int)
      // @return Promise
      sendTest: function sendTest(mailing, recipient) {
        var promise = crmMailingMgr.sendTest(mailing, recipient)
            .then(function (deliveryInfos) {
              var count = Object.keys(deliveryInfos).length;
              if (count === 0) {
                CRM.alert(ts('Could not identify any recipients. Perhaps the group is empty?'));
              }
            })
          ;
        return crmStatus({start: ts('Sending...'), success: ts('Sent')}, promise);
      }
    };
  });

  angular.module('crmMailing').factory('crmMailingStats', function (crmApi, crmLegacy) {
    var statTypes = [
      // {name: 'Recipients', title: ts('Intended Recipients'),   searchFilter: '',                           eventsFilter: '&event=queue'},
      {name: 'Delivered',     title: ts('Successful Deliveries'), searchFilter: '&mailing_delivery_status=Y', eventsFilter: '&event=delivered'},
      {name: 'Opened',        title: ts('Tracked Opens'),         searchFilter: '&mailing_open_status=Y',     eventsFilter: '&event=opened'},
      {name: 'Unique Clicks', title: ts('Click-throughs'),        searchFilter: '&mailing_click_status=Y',    eventsFilter: '&event=click&distinct=1'},
      // {name: 'Forward',    title: ts('Forwards'),              searchFilter: '&mailing_forward=1',         eventsFilter: '&event=forward'},
      // {name: 'Replies',    title: ts('Replies'),               searchFilter: '&mailing_reply_status=Y',    eventsFilter: '&event=reply'},
      {name: 'Bounces',       title: ts('Bounces'),               searchFilter: '&mailing_delivery_status=N', eventsFilter: '&event=bounce'},
      {name: 'Unsubscribers', title: ts('Unsubscribes'),          searchFilter: '&mailing_unsubscribe=1',     eventsFilter: '&event=unsubscribe'}
      // {name: 'OptOuts',    title: ts('Opt-Outs'),              searchFilter: '&mailing_optout=1',          eventsFilter: '&event=optout'}
    ];

    return {
      getStatTypes: function() {
        return statTypes;
      },

      /**
       * @param mailingIds object
       *   List of mailing IDs ({a: 123, b: 456})
       * @return Promise
       *   List of stats for each mailing
       *   ({a: ...object..., b: ...object...})
       */
      getStats: function(mailingIds) {
        var params = {};
        angular.forEach(mailingIds, function(mailingId, name) {
          params[name] = ['Mailing', 'stats', {mailing_id: mailingId}];
        });
        return crmApi(params).then(function(result) {
          var stats = {};
          angular.forEach(mailingIds, function(mailingId, name) {
            stats[name] = result[name].values[mailingId];
          });
          return stats;
        });
      },

      /**
       * Determine the legacy URL for a report about a given mailing and stat.
       *
       * @param mailing object
       * @param statType object (see statTypes above)
       * @param view string ('search', 'event', 'report')
       * @param returnPath string|null Return path (relative to Angular base)
       * @return string|null
       */
      getUrl: function getUrl(mailing, statType, view, returnPath) {
        switch (view) {
          case 'events':
            var retParams = returnPath ? '&context=angPage&angPage=' + returnPath : '';
            return crmLegacy.url('civicrm/mailing/report/event',
              'reset=1&mid=' + mailing.id + statType.eventsFilter + retParams);

          case 'search':
            return crmLegacy.url('civicrm/contact/search/advanced',
              'force=1&mailing_id=' + mailing.id + statType.searchFilter);

          // TODO: case 'report':
          default:
            return null;
        }
      }
    };
  });

  // crmMailingSimpleDirective is a template/factory-function for constructing very basic
  // directives that accept a "mailing" argument. Please don't overload it. If one continues building
  // this, it risks becoming a second system that violates Angular architecture (and prevents one
  // from using standard Angular docs+plugins). So this really shouldn't do much -- it is really
  // only for simple directives. For something complex, suck it up and write 10 lines of boilerplate.
  angular.module('crmMailing').factory('crmMailingSimpleDirective', function ($q, crmMetadata, crmUiHelp) {
    return function crmMailingSimpleDirective(directiveName, templateUrl) {
      return {
        scope: {
          crmMailing: '@'
        },
        templateUrl: templateUrl,
        link: function (scope, elm, attr) {
          scope.$parent.$watch(attr.crmMailing, function(newValue){
            scope.mailing = newValue;
          });
          scope.crmMailingConst = CRM.crmMailing;
          scope.ts = CRM.ts(null);
          scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
          scope[directiveName] = attr[directiveName] ? scope.$parent.$eval(attr[directiveName]) : {};
          $q.when(crmMetadata.getFields('Mailing'), function(fields) {
            scope.mailingFields = fields;
          });
        }
      };
    };
  });

})(angular, CRM.$, CRM._);
