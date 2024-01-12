(function(angular, $, _) {
  // example <select crm-mailing-templates crm-mailing="mymailing"></select>
  angular.module('crmMailing').directive('crmMailingTemplates', function(crmUiAlert) {
      return {
          restrict: 'AE',
          require: 'ngModel',
          scope: {
            ngRequired: '@'
          },
          link: function(scope, element, attrs, ngModel) {
            scope.template = ngModel.$viewValue;

            var refreshUI = ngModel.$render = function refresuhUI() {
              scope.template = ngModel.$viewValue;
              if (ngModel.$viewValue) {
                $(element).select2('val', ngModel.$viewValue);
              }
            };

            // @return string HTML representing an option
            function formatItem(item) {
              if (!item.id) {
                // return `text` for optgroup
                return item.text;
              }
              return '<span class="crmMailing-template">' + item.text + '</span>';
            }

            var rcpAjaxState = {
              input: '',
              entity: 'civicrm_msg_templates',
              page_n: 0,
              page_i: 0,
            };

            $(element).select2({
              width: '36em',
              placeholder: "<i class='fa fa-clipboard'></i> Mailing Templates",
              formatResult: formatItem,
              escapeMarkup: function(m) {
                return m;
              },
              multiple: false,
              initSelection: function(el, cb) {

                  var value = el.val();

                  CRM.api3('MessageTemplate', 'getlist', {params: {id: value}, label_field: 'msg_title'}).then(function(tlist) {

                      var template = {};

                      if (tlist.count) {
                        $(tlist.values).each(function(id, val) {
                          template.id = val.id;
                          template.text = val.label;
                        });
                      }

                      cb(template);
                  });
              },
              ajax: {
                  url: CRM.url('civicrm/ajax/rest'),
                  quietMillis: 300,
                  data: function(input, page_num) {
                    if (page_num <= 1) {
                      rcpAjaxState = {
                        input: input,
                        entity: 'civicrm_msg_templates',
                        page_n: 0,
                      };
                    }

                    rcpAjaxState.page_i = page_num - rcpAjaxState.page_n;
                    var filterParams = {is_active: 1, workflow_name: {"IS NULL": 1}};

                    var params = {
                      input: input,
                      page_num: rcpAjaxState.page_i,
                      label_field: 'msg_title',
                      search_field: 'msg_title',
                      params: filterParams,
                    };
                    return params;
                  },
                  transport: function(params) {
                    CRM.api3('MessageTemplate', 'getlist', params.data).then(params.success, params.error);
                  },
                  results: function(data) {

                    var results = {
                      children: $.map(data.values, function(obj) {
                        return {id: obj.id, text: obj.label};
                      })
                    };

                    if (rcpAjaxState.page_i == 1 && data.count) {
                      results.text = ts('Message Templates');
                    }

                    return {
                      more: data.more_results,
                      results: [results]
                    };
                  },
                }
            });

            $(element).on('select2-selecting', function(e) {
              // in here is where the template HTML should be loaded
              var entity_id = parseInt(e.val);
              ngModel.$viewValue = entity_id;

              scope.$parent.loadTemplate(scope.$parent.$parent.mailing, entity_id);
              scope.$apply();
              $(element).select2('close');
              e.preventDefault();
            });


            scope.$watchCollection("template", refreshUI);
            setTimeout(refreshUI, 50);
          }
      };


  });
})(angular, CRM.$, CRM._);
