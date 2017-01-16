(function(angular, $, _) {
  // example: <select multiple crm-mailing-recipients crm-mailing="mymailing" crm-avail-groups="myGroups" crm-avail-mailings="myMailings"></select>
  // FIXME: participate in ngModel's validation cycle
  angular.module('crmMailing').directive('crmMailingRecipients', function(crmUiAlert) {
    return {
      restrict: 'AE',
      require: 'ngModel',
      scope: {
        ngRequired: '@'
      },
      link: function(scope, element, attrs, ngModel) {
        scope.recips = ngModel.$viewValue;
        scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);
        refreshMandatory();

        var ts = scope.ts = CRM.ts(null);

        /// Convert MySQL date ("yyyy-mm-dd hh:mm:ss") to JS date object
        scope.parseDate = function(date) {
          if (!angular.isString(date)) {
            return date;
          }
          var p = date.split(/[\- :]/);
          return new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]), parseInt(p[3]), parseInt(p[4]), parseInt(p[5]));
        };

        /// Remove {value} from {array}
        function arrayRemove(array, value) {
          var idx = array.indexOf(value);
          if (idx >= 0) {
            array.splice(idx, 1);
          }
        }

        // @param string id an encoded string like "4 civicrm_mailing include"
        // @return Object keys: entity_id, entity_type, mode
        function convertValueToObj(id) {
          var a = id.split(" ");
          return {entity_id: parseInt(a[0]), entity_type: a[1], mode: a[2]};
        }

        // @param Object mailing
        // @return array list of values like "4 civicrm_mailing include"
        function convertMailingToValues(recipients) {
          var r = [];
          angular.forEach(recipients.groups.include, function(v) {
            r.push(v + " civicrm_group include");
          });
          angular.forEach(recipients.groups.exclude, function(v) {
            r.push(v + " civicrm_group exclude");
          });
          angular.forEach(recipients.mailings.include, function(v) {
            r.push(v + " civicrm_mailing include");
          });
          angular.forEach(recipients.mailings.exclude, function(v) {
            r.push(v + " civicrm_mailing exclude");
          });
          return r;
        }

        function refreshMandatory() {
          if (ngModel.$viewValue && ngModel.$viewValue.groups) {
            scope.mandatoryGroups = _.filter(scope.$parent.$eval(attrs.crmMandatoryGroups), function(grp) {
              return _.contains(ngModel.$viewValue.groups.include, parseInt(grp.id));
            });
            scope.mandatoryIds = _.map(_.pluck(scope.$parent.$eval(attrs.crmMandatoryGroups), 'id'), function(n) {
              return parseInt(n);
            });
          }
          else {
            scope.mandatoryGroups = [];
            scope.mandatoryIds = [];
          }
        }

        function isMandatory(grpId) {
          return _.contains(scope.mandatoryIds, parseInt(grpId));
        }

        var refreshUI = ngModel.$render = function refresuhUI() {
          scope.recips = ngModel.$viewValue;
          if (ngModel.$viewValue) {
            $(element).select2('val', convertMailingToValues(ngModel.$viewValue));
            validate();
            refreshMandatory();
          }
        };

        // @return string HTML representing an option
        function formatItem(item) {
          if (!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          var option = convertValueToObj(item.id);
          var icon = (option.entity_type === 'civicrm_mailing') ? 'fa-envelope' : 'fa-users';
          var spanClass = (option.mode == 'exclude') ? 'crmMailing-exclude' : 'crmMailing-include';
          if (option.entity_type != 'civicrm_mailing' && isMandatory(option.entity_id)) {
            spanClass = 'crmMailing-mandatory';
          }
          return '<i class="crm-i '+icon+'"></i> <span class="' + spanClass + '">' + item.text + '</span>';
        }

        function validate() {
          if (scope.$parent.$eval(attrs.ngRequired)) {
            var empty = (_.isEmpty(ngModel.$viewValue.groups.include) && _.isEmpty(ngModel.$viewValue.mailings.include));
            ngModel.$setValidity('empty', !empty);
          }
          else {
            ngModel.$setValidity('empty', true);
          }
        }

        $(element).select2({
          width: '36em',
          dropdownAutoWidth: true,
          placeholder: "Groups or Past Recipients",
          formatResult: formatItem,
          formatSelection: formatItem,
          escapeMarkup: function(m) {
            return m;
          },
          multiple: true,
          initSelection: function(el, cb) {
            var values = el.val().split(',');
            
            var gids = [];
            var mids = [];

            for (i in values) {
              var dv = convertValueToObj(values[i]);
              if (dv.entity_type == 'civicrm_group') {
                gids.push(dv.entity_id);
              }
              else if (dv.entity_type == 'civicrm_mailing') {
                mids.push(dv.entity_id);
              }
            }

            CRM.api3('Group', 'getlist', { params: { id: { IN: gids } }, extra: ["is_hidden"] }).then(
              function(glist) {
                CRM.api3('Mailing', 'getlist', { params: { id: { IN: mids } } }).then(
                  function(mlist) {
                    var datamap = [];

                    var groupNames = [];
                    var civiMails = [];
                    
                    $(glist.values).each(function (idx, group) {
                      var key = group.id + ' civicrm_group include';
                      groupNames.push({id: parseInt(group.id), title: group.label, is_hidden: group.extra.is_hidden});
                      
                      if (values.indexOf(key) >= 0) {
                        datamap.push({id: key, text: group.label});
                      }
                      
                      key = group.id + ' civicrm_group exclude';
                      if (values.indexOf(key) >= 0) {
                        datamap.push({id: key, text: group.label});
                      }
                    });

                    $(mlist.values).each(function (idx, group) {
                      var key = group.id + ' civicrm_mailing include';
                      civiMails.push({id: parseInt(group.id), name: group.label});
                      
                      if (values.indexOf(key) >= 0) {
                        datamap.push({id: key, text: group.label});
                      }
                      
                      key = group.id + ' civicrm_mailing exclude';
                      if (values.indexOf(key) >= 0) {
                        datamap.push({id: key, text: group.label});
                      }
                    });

                    scope.$parent.crmMailingConst.groupNames = groupNames;
                    scope.$parent.crmMailingConst.civiMails = civiMails;

                    refreshMandatory();
                    
                    cb(datamap);
                  })
              });
          },
          ajax: {
            url: CRM.url('civicrm/ajax/rest'),
            quietMillis: 300,
            data: function(input, page_num) {
              var params = {
                input: input,
                page_num: page_num,
                params: { is_hidden: 0 },
              };
              return params;
            },
            transport: function(params) {
              CRM.api3('Group', 'getlist', params.data).then(function(groups) {

                params.data.params.options = { sort: "is_archived asc, scheduled_date desc" };
                
                CRM.api3('Mailing', 'getlist', params.data).then(function(mailings) {
                  return { groups: groups, mailings: mailings }
                }, params.error).then(params.success, params.error)
              }, params.error);
            },
            results: function(data) {
              results = [];
              if(data.groups.count) {
                results.push({ text: ts('Include Group'),
                               children: $.map(data.groups.values, function(obj) {
                                 return { id: obj.id + ' civicrm_group include',
                                          text: obj.label }
                               })
                             });
                results.push({ text: ts('Exclude Group'),
                               children: $.map(data.groups.values, function(obj) {
                                 return { id: obj.id + ' civicrm_group exclude',
                                          text: obj.label }
                               }),
                             });
              }
              if(data.mailings.count) {
                results.push({ text: ts('Include Mailing'),
                               children: $.map(data.mailings.values, function(obj) {
                                 return { id: obj.id + ' civicrm_mailing include',
                                          text: obj.label }
                               }),
                             });
                results.push({ text: ts('Exclude Mailing'),
                               children: $.map(data.mailings.values, function(obj) {
                                 return { id: obj.id + ' civicrm_mailing exclude',
                                          text: obj.label }
                               }),
                             });
              }
              return {
                more: data.mailings.more_results || data.groups.more_results,
                results: results,
              }
            },
          },
        });

        $(element).on('select2-selecting', function(e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          if (option.mode == 'exclude') {
            ngModel.$viewValue[typeKey].exclude.push(option.entity_id);
            arrayRemove(ngModel.$viewValue[typeKey].include, option.entity_id);
          }
          else {
            ngModel.$viewValue[typeKey].include.push(option.entity_id);
            arrayRemove(ngModel.$viewValue[typeKey].exclude, option.entity_id);
          }
          scope.$apply();
          $(element).select2('close');
          validate();
          e.preventDefault();
        });

        $(element).on("select2-removing", function(e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          if (typeKey == 'groups' && isMandatory(option.entity_id)) {
            crmUiAlert({
              text: ts('This mailing was generated based on search results. The search results cannot be removed.'),
              title: ts('Required')
            });
            e.preventDefault();
            return;
          }
          scope.$parent.$apply(function() {
            arrayRemove(ngModel.$viewValue[typeKey][option.mode], option.entity_id);
          });
          validate();
          e.preventDefault();
        });

        scope.$watchCollection("recips.groups.include", refreshUI);
        scope.$watchCollection("recips.groups.exclude", refreshUI);
        scope.$watchCollection("recips.mailings.include", refreshUI);
        scope.$watchCollection("recips.mailings.exclude", refreshUI);
        setTimeout(refreshUI, 50);

        scope.$watchCollection(attrs.crmAvailGroups, function() {
          scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        });
        scope.$watchCollection(attrs.crmAvailMailings, function() {
          scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);
        });
        scope.$watchCollection(attrs.crmMandatoryGroups, function() {
          refreshMandatory();
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
