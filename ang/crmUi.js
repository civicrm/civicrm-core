/// crmUi: Sundry UI helpers
(function (angular, $, _) {

  let uidCount = 0,
    pageTitleHTML = 'CiviCRM',
    documentTitle = 'CiviCRM';

  angular.module('crmUi', CRM.angRequires('crmUi'))

    // example <div crm-ui-accordion="{title: ts('My Title'), collapsed: true}">...content...</div>
    // @deprecated: just use <details><summary> markup
    .directive('crmUiAccordion', function() {
      return {
        scope: {
          crmUiAccordion: '='
        },
        template: '<details class="crm-accordion-bold"><summary>{{crmUiAccordion.title}} <a crm-ui-help="help" ng-if="help"></a></summary><div class="crm-accordion-body" ng-transclude></div></details>',
        transclude: true,
        link: function (scope, element, attrs) {
          scope.help = null;
          let openSet = false;
          scope.$watch('crmUiAccordion', function(crmUiAccordion, oldVal) {
            if (crmUiAccordion) {
              // Only process this once
              if (!openSet) {
                $(element).children('details').prop('open', !crmUiAccordion.collapsed);
                openSet = true;
              }
              if (crmUiAccordion.help) {
                scope.help = crmUiAccordion.help.clone({}, {
                  title: crmUiAccordion.title
                });
              }
            }
          });
        }
      };
    })

    // Examples:
    //   crmUiAlert({text: 'My text', title: 'My title', type: 'error'});
    //   crmUiAlert({template: '<a ng-click="ok()">Hello</a>', scope: $scope.$new()});
    //   let h = crmUiAlert({templateUrl: '~/crmFoo/alert.html', scope: $scope.$new()});
    //   ... h.close(); ...
    .service('crmUiAlert', function($compile, $rootScope, $templateRequest, $q) {
      let count = 0;
      return function crmUiAlert(params) {
        const id = 'crmUiAlert_' + (++count);
        let tpl = null;
        if (params.templateUrl) {
          tpl = $templateRequest(params.templateUrl);
        }
        else if (params.template) {
          tpl = params.template;
        }
        if (tpl) {
          params.text = '<div id="' + id + '"></div>'; // temporary stub
        }
        const result = CRM.alert(params.text, params.title, params.type, params.options);
        if (tpl) {
          $q.when(tpl, function(html) {
            const scope = params.scope || $rootScope.$new();
            const linker = $compile(html);
            $('#' + id).append($(linker(scope)));
          });
        }
        return result;
      };
    })

    // Simple wrapper around $.crmDatepicker.
    // example with no time input: <input crm-ui-datepicker="{time: false}" ng-model="myobj.datefield"/>
    // example with custom date format: <input crm-ui-datepicker="{date: 'm/d/y'}" ng-model="myobj.datefield"/>
    .directive('crmUiDatepicker', function ($timeout) {
      return {
        restrict: 'AE',
        require: 'ngModel',
        scope: {
          crmUiDatepicker: '='
        },
        link: function (scope, element, attrs, ngModel) {
          ngModel.$render = function () {
            const viewVal = ngModel.$viewValue || '';
            // Prevent unnecessarily triggering ngChagne
            if (element.val() != viewVal) {
              element.val(viewVal).change();
            }
          };
          let settings = angular.copy(scope.crmUiDatepicker || {});
          // Set defaults to be non-restrictive
          settings.start_date_years = settings.start_date_years || 100;
          settings.end_date_years = settings.end_date_years || 100;

          // Wait for interpolated elements like {{placeholder}} to render
          $timeout(function() {
            element
              .crmDatepicker(settings)
              .on('change', function () {
                // Because change gets triggered from the $render function we could be either inside or outside the $digest cycle
                $timeout(function() {
                  let requiredLength = 19;
                  if (settings.time === false) {
                    requiredLength = 10;
                  }
                  if (settings.date === false) {
                    requiredLength = 8;
                  }
                  else if (typeof settings.date === 'string') {
                    const lowerFormat = settings.date.toLowerCase();
                    // FIXME: parseDate doesn't work with incomplete date formats; skip validation if no month, day or year in format
                    if (lowerFormat.indexOf('y') < 0 || lowerFormat.indexOf('m') < 0 || lowerFormat.indexOf('d') < 0) {
                      // skipping the validation by setting the actual length of datepicker value
                      requiredLength = element.val().length;
                    }
                  }
                  ngModel.$setValidity('incompleteDateTime', !(element.val().length && element.val().length !== requiredLength));
                });
              });
          });
        }
      };
    })

    // Display debug information (if available)
    // For richer DX, checkout Batarang/ng-inspector (Chrome/Safari), or AngScope/ng-inspect (Firefox).
    // example: <div crm-ui-debug="myobject" />
    .directive('crmUiDebug', function ($location) {
      return {
        restrict: 'AE',
        scope: {
          crmUiDebug: '@'
        },
        template: function() {
          const args = $location.search();
          if (args && args.angularDebug) {
            const jsonTpl = (CRM.angular.modules.indexOf('jsonFormatter') < 0) ? '<pre>{{data|json}}</pre>' : '<json-formatter json="data" open="1"></json-formatter>';
            return '<div crm-ui-accordion=\'{title: ts("Debug (%1)", {1: crmUiDebug}), collapsed: true}\'>' + jsonTpl + '</div>';
          }
          return '';
        },
        link: function(scope, element, attrs) {
          const args = $location.search();
          if (args && args.angularDebug) {
            scope.ts = CRM.ts(null);
            scope.$parent.$watch(attrs.crmUiDebug, function(data) {
              scope.data = data;
            });
          }
        }
      };
    })

    // Display a field/row in a field list
    // example: <div crm-ui-field="{title: ts('My Field')}"> {{mydata}} </div>
    // example: <div crm-ui-field="{name: 'subform.myfield', title: ts('My Field')}"> <input crm-ui-id="subform.myfield" name="myfield" /> </div>
    // example: <div crm-ui-field="{name: 'subform.myfield', title: ts('My Field')}"> <input crm-ui-id="subform.myfield" name="myfield" required /> </div>
    // example: <div crm-ui-field="{name: 'subform.myfield', title: ts('My Field'), help: hs('help_field_name'), required: true}"> {{mydata}} </div>
    .directive('crmUiField', function() {
      // Note: When writing new templates, the "label" position is particular. See/patch "var label" below.
      const templateUrls = {
        default: '~/crmUi/field.html',
        checkbox: '~/crmUi/field-cb.html'
      };

      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        scope: {
          // {title, name, help, helpFile}
          crmUiField: '='
        },
        templateUrl: function(tElement, tAttrs){
          const layout = tAttrs.crmLayout ? tAttrs.crmLayout : 'default';
          return templateUrls[layout];
        },
        transclude: true,
        link: function (scope, element, attrs, crmUiIdCtrl) {
          $(element).addClass('crm-section');
          scope.help = null;
          scope.$watch('crmUiField', function(crmUiField) {
            if (crmUiField && crmUiField.help) {
              scope.help = crmUiField.help.clone({}, {
                title: crmUiField.title
              });
            }
          });
        }
      };
    })

    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiId', function () {
      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        link: {
          pre: function (scope, element, attrs, crmUiIdCtrl) {
            const id = crmUiIdCtrl.get(attrs.crmUiId);
            element.attr('id', id);
          }
        }
      };
    })

    // for example, see crmUiHelp
    .service('crmUiHelp', function(){
      // example: const h = new FieldHelp({id: 'foo'}); h.open();
      function FieldHelp(options) {
        this.options = options;
      }
      angular.extend(FieldHelp.prototype, {
        get: function(n) {
          return this.options[n];
        },
        open: function open() {
          CRM.help(this.options.title, {id: this.options.id, file: this.options.file});
        },
        clone: function clone(options, defaults) {
          return new FieldHelp(angular.extend({}, defaults, this.options, options));
        }
      });

      // example: const hs = crmUiHelp({file: 'CRM/Foo/Bar'});
      return function(defaults){
        // example: hs('myfield')
        // example: hs({id: 'myfield', title: 'Foo Bar', file: 'Whiz/Bang'})
        return function(options) {
          if (typeof options === 'string') {
            options = {id: options};
          }
          return new FieldHelp(angular.extend({}, defaults, options));
        };
      };
    })

    // Display a help icon
    // Example: Use a default *.hlp file
    //   scope.hs = crmUiHelp({file: 'Path/To/Help/File'});
    //   HTML: <a crm-ui-help="hs({title:ts('My Field'), id:'my_field'})">
    // Example: Use an explicit *.hlp file
    //   HTML: <a crm-ui-help="hs({title:ts('My Field'), id:'my_field', file:'CRM/Foo/Bar'})">
    .directive('crmUiHelp', function() {
      return {
        restrict: 'EA',
        link: function(scope, element, attrs) {
          setTimeout(function() {
            const crmUiHelp = scope.$eval(attrs.crmUiHelp);
            let title = crmUiHelp && crmUiHelp.get('title') ? ts('%1 Help', {1: crmUiHelp.get('title')}) : ts('Help');
            element.attr('title', title);
          }, 50);

          element
            .addClass('helpicon')
            .attr('href', '#')
            .on('click', function(e) {
              e.preventDefault();
              scope.$eval(attrs.crmUiHelp).open();
            });
        }
      };
    })

    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiFor', function ($parse, $timeout) {
      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        template: '<span ng-class="cssClasses"><span ng-transclude/><span crm-ui-visible="crmIsRequired" class="crm-marker" title="This field is required.">*</span></span>',
        transclude: true,
        link: function (scope, element, attrs, crmUiIdCtrl) {
          scope.crmIsRequired = false;
          scope.cssClasses = {};

          if (!attrs.crmUiFor) return;

          const id = crmUiIdCtrl.get(attrs.crmUiFor);
          element.attr('for', id);
          let ngModel = null;

          const updateCss = function () {
            scope.cssClasses['crm-error'] = !ngModel.$valid && !ngModel.$pristine;
          };

          // Note: if target element is dynamically generated (eg via ngInclude), then it may not be available
          // immediately for initialization. Use retries/retryDelay to initialize such elements.
          const init = function (retries, retryDelay) {
            const input = $('#' + id);
            if (input.length === 0 && !attrs.crmUiForceRequired) {
              if (retries) {
                $timeout(function(){
                  init(retries-1, retryDelay);
                }, retryDelay);
              }
              return;
            }

            if (attrs.crmUiForceRequired) {
              scope.crmIsRequired = true;
              return;
            }

            let tgtScope = scope;//.$parent;
            if (attrs.crmDepth) {
              for (let i = attrs.crmDepth; i > 0; i--) {
                tgtScope = tgtScope.$parent;
              }
            }

            if (input.attr('ng-required')) {
              scope.crmIsRequired = scope.$parent.$eval(input.attr('ng-required'));
              scope.$parent.$watch(input.attr('ng-required'), function (isRequired) {
                scope.crmIsRequired = isRequired;
              });
            }
            else {
              scope.crmIsRequired = input.prop('required');
            }

            ngModel = $parse(attrs.crmUiFor)(tgtScope);
            if (ngModel) {
              ngModel.$viewChangeListeners.push(updateCss);
            }
          };

          $timeout(function(){
            init(3, 100);
          });
        }
      };
    })

    // Define a scope in which a name like "subform.foo" maps to a unique ID.
    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiIdScope', function () {
      return {
        restrict: 'EA',
        scope: {},
        controllerAs: 'crmUiIdCtrl',
        controller: function($scope) {
          const ids = {};
          this.get = function(name) {
            if (!ids[name]) {
              ids[name] = "crmUiId_" + (++uidCount);
            }
            return ids[name];
          };
        },
        link: function (scope, element, attrs) {}
      };
    })

    // Display an HTML blurb inside an IFRAME.
    // example: <iframe crm-ui-iframe="getHtmlContent()"></iframe>
    // example:  <iframe crm-ui-iframe crm-ui-iframe-src="getUrl()"></iframe>
    .directive('crmUiIframe', function ($parse) {
      return {
        scope: {
          crmUiIframeSrc: '@', // expression which evaluates to a URL
          crmUiIframe: '@' // expression which evaluates to HTML content
        },
        link: function (scope, elm, attrs) {
          const iframe = $(elm)[0];
          iframe.setAttribute('width', '100%');
          iframe.setAttribute('height', '250px');
          iframe.setAttribute('frameborder', '0');

          const refresh = function () {
            if (attrs.crmUiIframeSrc) {
              iframe.setAttribute('src', scope.$parent.$eval(attrs.crmUiIframeSrc));
            }
            else {
              let iframeHtml = scope.$parent.$eval(attrs.crmUiIframe);

              let doc = iframe.document;
              if (iframe.contentDocument) {
                doc = iframe.contentDocument;
              }
              else if (iframe.contentWindow) {
                doc = iframe.contentWindow.document;
              }

              doc.open();
              doc.writeln(iframeHtml);
              doc.close();
            }
          };

          // If the iframe is in a dialog, respond to resize events
          $(elm).parent().on('dialogresize dialogopen', function(e, ui) {
            $(this).css({padding: '0', margin: '0', overflow: 'hidden'});
            iframe.setAttribute('height', '' + $(this).innerHeight() + 'px');
          });

          $(elm).parent().on('dialogresize', function(e, ui) {
            iframe.setAttribute('class', 'resized');
          });

          scope.$parent.$watch(attrs.crmUiIframe, refresh);
        }
      };
    })

    // Example:
    //   <a ng-click="$broadcast('my-insert-target', 'some new text')>Insert</a>
    //   <textarea crm-ui-insert-rx='my-insert-target'></textarea>
    .directive('crmUiInsertRx', function() {
      return {
        link: function(scope, element, attrs) {
          scope.$on(attrs.crmUiInsertRx, function(e, tokenName) {
            CRM.wysiwyg.insert(element, tokenName);
            $(element).select2('close').select2('val', '');
            CRM.wysiwyg.focus(element);
          });
        }
      };
    })

    // Define a rich text editor.
    // example: <textarea crm-ui-id="myForm.body_html" crm-ui-richtext name="body_html" ng-model="mailing.body_html"></textarea>
    .directive('crmUiRichtext', function ($timeout) {
      return {
        require: '?ngModel',
        link: function (scope, elm, attr, ngModel) {

          // Wait for #id to stabilize so the wysiwyg doesn't init with an id like `cke_{{:: fieldId }}`
          $timeout(function() {
            const editor = CRM.wysiwyg.create(elm);

            if (!ngModel) {
              return;
            }

            if (attr.ngBlur) {
              $(elm).on('blur', function() {
                $timeout(function() {
                  scope.$eval(attr.ngBlur);
                });
              });
            }

            ngModel.$render = function(value) {
              editor.done(function() {
                CRM.wysiwyg.setVal(elm, ngModel.$viewValue || '');
              });
            };
          });
        }
      };
    })

    // Display a lock icon (based on a boolean).
    // example: <a crm-ui-lock binding="mymodel.boolfield"></a>
    // example: <a crm-ui-lock
    //            binding="mymodel.boolfield"
    //            title-locked="ts('Boolfield is locked')"
    //            title-unlocked="ts('Boolfield is unlocked')"></a>
    .directive('crmUiLock', function ($parse, $rootScope) {
      const defaultVal = function (defaultValue) {
        const f = function (scope) {
          return defaultValue;
        };
        f.assign = function (scope, value) {
          // ignore changes
        };
        return f;
      };

      // like $parse, but accepts a defaultValue in case expr is undefined
      const parse = function (expr, defaultValue) {
        return expr ? $parse(expr) : defaultVal(defaultValue);
      };

      return {
        template: '',
        link: function (scope, element, attrs) {
          const binding = parse(attrs.binding, true);
          const titleLocked = parse(attrs.titleLocked, ts('Locked'));
          const titleUnlocked = parse(attrs.titleUnlocked, ts('Unlocked'));

          $(element).addClass('crm-i lock-button');
          const refresh = function () {
            const locked = binding(scope);
            if (locked) {
              $(element)
                .removeClass('fa-unlock')
                .addClass('fa-lock')
                .prop('title', titleLocked(scope))
              ;
            }
            else {
              $(element)
                .removeClass('fa-lock')
                .addClass('fa-unlock')
                .prop('title', titleUnlocked(scope))
              ;
            }
          };

          $(element).click(function () {
            binding.assign(scope, !binding(scope));
            //scope.$digest();
            $rootScope.$digest();
          });

          scope.$watch(attrs.binding, refresh);
          scope.$watch(attrs.titleLocked, refresh);
          scope.$watch(attrs.titleUnlocked, refresh);

          refresh();
        }
      };
    })

    // CrmUiOrderCtrl is a controller class which manages sort orderings.
    // Ex:
    //   JS:   $scope.myOrder = new CrmUiOrderCtrl(['+field1', '-field2]);
    //         $scope.myOrder.toggle('field1');
    //         $scope.myOrder.setDir('field2', '');
    //   HTML: <tr ng-repeat="... | order:myOrder.get()">...</tr>
    .service('CrmUiOrderCtrl', function(){
      function CrmUiOrderCtrl(defaults){
        this.values = defaults;
      }
      angular.extend(CrmUiOrderCtrl.prototype, {
        get: function get() {
          return this.values;
        },
        getDir: function getDir(name) {
          if (this.values.indexOf(name) >= 0 || this.values.indexOf('+' + name) >= 0) {
            return '+';
          }
          if (this.values.indexOf('-' + name) >= 0) {
            return '-';
          }
          return '';
        },
        // @return bool TRUE if something is removed
        remove: function remove(name) {
          const idx = this.values.indexOf(name);
          if (idx >= 0) {
            this.values.splice(idx, 1);
            return true;
          }
          else {
            return false;
          }
        },
        setDir: function setDir(name, dir) {
          return this.toggle(name, dir);
        },
        // Toggle sort order on a field.
        // To set a specific order, pass optional parameter 'next' ('+', '-', or '').
        toggle: function toggle(name, next) {
          if (!next && next !== '') {
            next = '+';
            if (this.remove(name) || this.remove('+' + name)) {
              next = '-';
            }
            if (this.remove('-' + name)) {
              next = '';
            }
          }

          if (next == '+') {
            this.values.unshift('+' + name);
          }
          else if (next == '-') {
            this.values.unshift('-' + name);
          }
        }
      });
      return CrmUiOrderCtrl;
    })

    // Define a controller which manages sort order. You may interact with the controller
    // directly ("myOrder.toggle('fieldname')") order using the helper, crm-ui-order-by.
    // example:
    //   <span crm-ui-order="{var: 'myOrder', defaults: {'-myField'}}"></span>
    //   <th><a crm-ui-order-by="[myOrder,'myField']">My Field</a></th>
    //   <tr ng-repeat="... | order:myOrder.get()">...</tr>
    //   <button ng-click="myOrder.toggle('myField')">
    .directive('crmUiOrder', function(CrmUiOrderCtrl) {
      return {
        link: function(scope, element, attrs){
          const options = angular.extend({var: 'crmUiOrderBy'}, scope.$eval(attrs.crmUiOrder));
          scope[options.var] = new CrmUiOrderCtrl(options.defaults);
        }
      };
    })

    // For usage, see crmUiOrder (above)
    .directive('crmUiOrderBy', function() {
      return {
        link: function(scope, element, attrs) {
          function updateClass(crmUiOrderCtrl, name) {
            const dir = crmUiOrderCtrl.getDir(name);
            element
              .toggleClass('sorting_asc', dir === '+')
              .toggleClass('sorting_desc', dir === '-')
              .toggleClass('sorting', dir === '');
          }

          element.on('click', function(e){
            const tgt = scope.$eval(attrs.crmUiOrderBy);
            tgt[0].toggle(tgt[1]);
            updateClass(tgt[0], tgt[1]);
            e.preventDefault();
            scope.$digest();
          });

          const tgt = scope.$eval(attrs.crmUiOrderBy);
          updateClass(tgt[0], tgt[1]);
        }
      };
    })

    // Display a fancy SELECT (based on select2).
    // usage: <select crm-ui-select="{placeholder:'Something',allowClear:true,...}" ng-model="myobj.field"><option...></select>
    .directive('crmUiSelect', function ($parse, $timeout) {
      return {
        require: '?ngModel',
        priority: 1,
        scope: {
          crmUiSelect: '='
        },
        link: function (scope, element, attrs, ngModel) {
          // In cases where UI initiates update, there may be an extra
          // call to refreshUI, but it doesn't create a cycle.

          if (ngModel && !attrs.ngOptions) {
            ngModel.$render = function () {
              $timeout(function () {
                // ex: msg_template_id adds new item then selects it; use $timeout to ensure that
                // new item is added before selection is made
                let newVal = _.cloneDeep(ngModel.$modelValue);
                // Fix possible data-type mismatch
                if (typeof newVal === 'string' && element.select2('container').hasClass('select2-container-multi')) {
                  newVal = newVal.length ? newVal.split(scope.crmUiSelect.separator || ',') : [];
                }
                element.select2('val', newVal);
              });
            };
          }
          function refreshModel() {
            const oldValue = ngModel.$viewValue;
            let newValue = element.select2('val');
            // Let ng-list do the splitting
            if (Array.isArray(newValue) && attrs.ngList) {
              newValue = newValue.join(attrs.ngList);
            }
            if (oldValue != newValue) {
              scope.$parent.$apply(function () {
                ngModel.$setViewValue(newValue);
              });
            }
          }

          function init() {
            // TODO watch select2-options
            element.crmSelect2(scope.crmUiSelect || {});
            if (ngModel) {
              element.on('change', refreshModel);
            }
          }

          // If using ngOptions, the above methods do not work because option values get rewritten.
          // Skip init and do something simpler.
          if (attrs.ngOptions) {
            $timeout(function() {
              element.crmSelect2(scope.crmUiSelect || {});
              // Ensure widget is updated when model changes
              ngModel.$render = function () {
                element.val(ngModel.$viewValue || '').change();
              };
            });
          } else {
            // Wait for interpolated elements like {{placeholder}} to render
            $timeout(init);
          }
        }
      };
    })

    // Use a select2 widget as a pick-list. Instead of updating ngModel, the select2 widget will fire an event.
    // This similar to ngModel+ngChange, except that value is never stored in a model. It is only fired in the event.
    // usage: <select crm-ui-select='{...}' on-crm-ui-select="alert("User picked this item: " + selection)"></select>
    .directive('onCrmUiSelect', function () {
      return {
        priority: 10,
        link: function (scope, element, attrs) {
          element.on('select2-selecting', function(e) {
            e.preventDefault();
            element.select2('close').select2('val', '');
            scope.$apply(function() {
              scope.$eval(attrs.onCrmUiSelect, {selection: e.val});
            });
          });
        }
      };
    })

    // Render a crmEntityRef widget
    // usage: <input crm-entityref="{entity: 'Contact', select: {allowClear:true}}" ng-model="myobj.field" />
    .directive('crmEntityref', function ($parse, $timeout) {
      return {
        require: '?ngModel',
        scope: {
          crmEntityref: '='
        },
        link: function (scope, element, attrs, ngModel) {
          // In cases where UI initiates update, there may be an extra
          // call to refreshUI, but it doesn't create a cycle.

          ngModel.$render = function () {
            $timeout(function () {
              // ex: msg_template_id adds new item then selects it; use $timeout to ensure that
              // new item is added before selection is made
              let newVal = _.cloneDeep(ngModel.$modelValue);
              // Fix possible data-type mismatch
              if (typeof newVal === 'string' && element.select2('container').hasClass('select2-container-multi')) {
                newVal = newVal.length ? newVal.split(',') : [];
              }
              element.select2('val', newVal);
            });
          };
          function refreshModel() {
            const oldValue = ngModel.$viewValue;
            let newValue = element.select2('val');
            if (oldValue != newValue) {
              scope.$parent.$apply(function () {
                ngModel.$setViewValue(newValue);
              });
            }
          }

          function init() {
            // TODO can we infer "entity" from model?
            element.crmEntityRef(scope.crmEntityref || {});
            element.on('change', refreshModel);
            $timeout(ngModel.$render);
          }

          init();
        }
      };
    })

    // Render a crmAutocomplete APIv4 widget
    // usage: <input crm-autocomplete="'Contact'" crm-autocomplete-params={savedSearch: 'mySearch', filters: {is_deceased: false}}" ng-model="myobj.field" />
    .directive('crmAutocomplete', function () {
      return {
        require: {
          crmAutocomplete: 'crmAutocomplete',
          ngModel: '?ngModel'
        },
        priority: 100,
        bindToController: {
          entity: '<crmAutocomplete',
          crmAutocompleteParams: '<',
          multi: '<',
          autoOpen: '<',
          quickAdd: '<',
          quickEdit: '<',
          staticOptions: '<'
        },
        link: function(scope, element, attr, ctrl) {
          // Copied from ng-list but applied conditionally if field is multi-valued
          const parseList = function(viewValue) {
            // If the viewValue is invalid (say required but empty) it will be `undefined`
            if (typeof viewValue === 'undefined') {
              return;
            }

            if (!ctrl.crmAutocomplete.multi) {
              return viewValue;
            }

            const list = [];

            if (viewValue) {
              _.each(viewValue.split(','), function(value) {
                if (value) {
                  list.push(_.trim(value));
                }
              });
            }

            return list;
          };

          if (ctrl.ngModel) {
            // Ensure widget is updated when model changes
            ctrl.ngModel.$render = function() {
              // Trigger change so the Select2 renders the current value,
              // but only if the value has actually changed (to avoid recursion)
              // We need to coerce null|false in the model to '' and numbers to strings.
              // We need 0 not to be equivalent to null|false|''
              const newValue = (ctrl.ngModel.$viewValue === null || ctrl.ngModel.$viewValue === undefined || ctrl.ngModel.$viewValue === false) ? '' : ctrl.ngModel.$viewValue.toString();
              if (newValue !== element.val().toString()) {
                element.val(newValue).change();
              }
            };

            // Copied from ng-list
            ctrl.ngModel.$parsers.push(parseList);
            ctrl.ngModel.$formatters.push(function(value) {
              return Array.isArray(value) ? value.join(',') : value;
            });

            // Copied from ng-list
            ctrl.ngModel.$isEmpty = function(value) {
              return !value || !value.length;
            };
          }
        },
        controller: function($element, $timeout) {
          const ctrl = this;

          // Intitialize widget, and re-render it every time params change
          this.$onChanges = function() {
            // Timeout is to wait for `placeholder="{{ ts(...) }}"` to be resolved
            $timeout(function() {
              // Only auto-open if there are no static options or quickAdd links
              const autoOpen = ctrl.autoOpen &&
                !(ctrl.staticOptions && ctrl.staticOptions.length) &&
                !(ctrl.quickAdd === true || (ctrl.quickAdd && ctrl.quickAdd.length));
              $element.crmAutocomplete(ctrl.entity, ctrl.crmAutocompleteParams || {}, {
                multiple: ctrl.multi,
                minimumInputLength: autoOpen ? 0 : 1,
                static: ctrl.staticOptions || [],
                quickAdd: ctrl.quickAdd,
                quickEdit: ctrl.quickEdit,
              });
            });
          };
        }
      };
    })

    // validate multiple email text
    // usage: <input crm-multiple-email type="text" ng-model="myobj.field" />
    .directive('crmMultipleEmail', function ($parse, $timeout) {
      return {
        require: 'ngModel',
        link: function(scope, element, attrs, ctrl) {
          ctrl.$parsers.unshift(function(viewValue) {
            // if empty value provided simply bypass validation
            if (_.isEmpty(viewValue)) {
              ctrl.$setValidity('crmMultipleEmail', true);
              return viewValue;
            }

            // split email string on basis of comma
            const emails = viewValue.split(',');
            // regex pattern for single email
            const emailRegex = /\S+@\S+\.\S+/;

            const validityArr = emails.map(function(str){
              return emailRegex.test(str.trim());
            });

            if ($.inArray(false, validityArr) > -1) {
              ctrl.$setValidity('crmMultipleEmail', false);
            } else {
              ctrl.$setValidity('crmMultipleEmail', true);
            }
            return viewValue;
          });
        }
      };
    })
    // example <div crm-ui-tab id="tab-1" crm-title="ts('My Title')" count="3">...content...</div>
    // WISHLIST: use a full Angular component instead of an incomplete jQuery wrapper
    .directive('crmUiTab', function($parse) {
      return {
        require: '^crmUiTabSet',
        restrict: 'EA',
        scope: {
          crmTitle: '@',
          crmIcon: '@',
          count: '@',
          id: '@'
        },
        template: '<div ng-transclude></div>',
        transclude: true,
        link: function (scope, element, attrs, crmUiTabSetCtrl) {
          crmUiTabSetCtrl.add(scope);
        }
      };
    })

    // example: <div crm-ui-tab-set><div crm-ui-tab crm-title="Tab 1">...</div><div crm-ui-tab crm-title="Tab 2">...</div></div>
    .directive('crmUiTabSet', function() {
      return {
        restrict: 'EA',
        scope: {
          crmUiTabSet: '@',
          tabSetOptions: '<'
        },
        templateUrl: '~/crmUi/tabset.html',
        transclude: true,
        controllerAs: 'crmUiTabSetCtrl',
        controller: function($scope, $element, $timeout) {
          let init;
          $scope.tabs = [];
          this.add = function(tab) {
            if (!tab.id) throw "Tab is missing 'id'";
            $scope.tabs.push(tab);

            // Init jQuery.tabs() once all tabs have been added
            if (init) {
              $timeout.cancel(init);
            }
            init = $timeout(function() {
              $element.find('.crm-tabset').tabs($scope.tabSetOptions);
            });
          };
        }
      };
    })

    // Generic, field-independent form validator.
    // example: <span ng-model="placeholder" crm-ui-validate="foo && bar || whiz" />
    // example: <span ng-model="placeholder" crm-ui-validate="foo && bar || whiz" crm-ui-validate-name="myError" />
    .directive('crmUiValidate', function() {
      return {
        restrict: 'EA',
        require: 'ngModel',
        link: function(scope, element, attrs, ngModel) {
          const validationKey = attrs.crmUiValidateName ? attrs.crmUiValidateName : 'crmUiValidate';
          scope.$watch(attrs.crmUiValidate, function(newValue){
            ngModel.$setValidity(validationKey, !!newValue);
          });
        }
      };
    })

    // like ng-show, but hides/displays elements using "visibility" which maintains positioning
    // example <div crm-ui-visible="false">...content...</div>
    .directive('crmUiVisible', function($parse) {
      return {
        restrict: 'EA',
        scope: {
          crmUiVisible: '@'
        },
        link: function (scope, element, attrs) {
          const model = $parse(attrs.crmUiVisible);
          function updatecChildren() {
            element.css('visibility', model(scope.$parent) ? 'inherit' : 'hidden');
          }
          updatecChildren();
          scope.$parent.$watch(attrs.crmUiVisible, updatecChildren);
        }
      };
    })

    // example: <div crm-ui-wizard="myWizardCtrl"><div crm-ui-wizard-step crm-title="ts('Step 1')">...</div><div crm-ui-wizard-step crm-title="ts('Step 2')">...</div></div>
    // example with custom nav classes: <div crm-ui-wizard crm-ui-wizard-nav-class="ng-animate-out ...">...</div>
    // Note: "myWizardCtrl" has various actions/properties like next() and $first().
    // WISHLIST: Allow each step to determine if it is "complete" / "valid" / "selectable"
    // WISHLIST: Allow each step to enable/disable (show/hide) itself
    .directive('crmUiWizard', function() {
      return {
        restrict: 'EA',
        scope: {
          crmUiWizard: '@',
          crmUiWizardNavClass: '@' // string, A list of classes that will be added to the nav items
        },
        templateUrl: '~/crmUi/wizard.html',
        transclude: true,
        controllerAs: 'crmUiWizardCtrl',
        controller: function($scope, $parse) {
          const steps = $scope.steps = []; // array<$scope>
          let maxVisited = 0;
          let selectedIndex = null;

          const findIndex = function() {
            let found = null;
            angular.forEach(steps, function(step, stepKey) {
              if (step.selected) found = stepKey;
            });
            return found;
          };

          /// @return int the index of the current step
          this.$index = function() { return selectedIndex; };
          /// @return bool whether the currentstep is first
          this.$first = function() { return this.$index() === 0; };
          /// @return bool whether the current step is last
          this.$last = function() { return this.$index() === steps.length -1; };
          this.$maxVisit = function() { return maxVisited; };
          this.$validStep = function() {
            return steps[selectedIndex] && steps[selectedIndex].isStepValid();
          };
          this.iconFor = function(index) {
            if (index < this.$index()) return 'crm-i fa-check';
            if (index === this.$index()) return 'crm-i fa-angle-double-right';
            return '';
          };
          this.isSelectable = function(step) {
            if (step.selected) return false;
            return this.$validStep();
          };

          /*** @param Object step the $scope of the step */
          this.select = function(step) {
            angular.forEach(steps, function(otherStep, otherKey) {
              otherStep.selected = (otherStep === step);
              if (otherStep === step && maxVisited < otherKey) maxVisited = otherKey;
            });
            selectedIndex = findIndex();
          };
          /*** @param Object step the $scope of the step */
          this.add = function(step) {
            if (steps.length === 0) {
              step.selected = true;
              selectedIndex = 0;
            }
            steps.push(step);
            steps.sort(function(a,b){
              return a.crmUiWizardStep - b.crmUiWizardStep;
            });
            selectedIndex = findIndex();
          };
          this.remove = function(step) {
            let key = null;
            angular.forEach(steps, function(otherStep, otherKey) {
              if (otherStep === step) key = otherKey;
            });
            if (key !== null) {
              steps.splice(key, 1);
            }
          };
          this.goto = function(index) {
            if (index < 0) index = 0;
            if (index >= steps.length) index = steps.length-1;
            this.select(steps[index]);
          };
          this.previous = function() { this.goto(this.$index()-1); };
          this.next = function() { this.goto(this.$index()+1); };
          if ($scope.crmUiWizard) {
            $parse($scope.crmUiWizard).assign($scope.$parent, this);
          }
        },
        link: function (scope, element, attrs) {
          scope.ts = CRM.ts(null);

          element.find('.crm-wizard-buttons button[ng-click^=crmUiWizardCtrl]').click(function () {
            // These values are captured inside the click handler to ensure the
            // positions/sizes of the elements are captured at the time of the
            // click vs. at the time this directive is initialized.
            const topOfWizard = element.offset().top;
            const heightOfMenu = $('#civicrm-menu').height() || 0;

            $('html')
              // stop any other animations that might be happening...
              .stop()
              // gracefully slide the user to the top of the wizard
              .animate({scrollTop: topOfWizard - heightOfMenu}, 1000);
          });
        }
      };
    })

    // Use this to add extra markup to wizard
    .directive('crmUiWizardButtons', function() {
      return {
        require: '^crmUiWizard',
        restrict: 'EA',
        scope: {},
        template: '<span ng-transclude></span>',
        transclude: true,
        link: function (scope, element, attrs, crmUiWizardCtrl) {
          const realButtonsEl = $(element).closest('.crm-wizard').find('.crm-wizard-buttons');
          $(element).appendTo(realButtonsEl);
        }
      };
    })

    // Example for Font Awesome: <button crm-icon="fa-check">Save</button>
    // Example for jQuery UI (deprecated): <button crm-icon="fa-check">Save</button>
    .directive('crmIcon', function() {
      return {
        restrict: 'EA',
        link: function (scope, element, attrs) {
          if (element.is('[crm-ui-tab]')) {
            // handled in crmUiTab ctrl
            return;
          }
          if (attrs.crmIcon) {
            if (attrs.crmIcon.substring(0,3) == 'fa-') {
              $(element).prepend('<i class="crm-i ' + attrs.crmIcon + '" role="img" aria-hidden="true"></i> ');
            }
            else {
              $(element).prepend('<span class="icon ui-icon-' + attrs.crmIcon + '"></span> ');
            }
          }

          // Add crm-* class to non-bootstrap buttons
          if ($(element).is('button:not(.btn)')) {
            $(element).addClass('crm-button');
          }
        }
      };
    })

    // example: <div crm-ui-wizard-step crm-title="ts('My Title')" ng-form="mySubForm">...content...</div>
    // If there are any conditional steps, then be sure to set a weight explicitly on *all* steps to maintain ordering.
    // example: <div crm-ui-wizard-step="100" crm-title="..." ng-if="...">...content...</div>
    // example with custom classes: <div crm-ui-wizard-step="100" crm-ui-wizard-step-class="ng-animate-out ...">...content...</div>
    .directive('crmUiWizardStep', function() {
      let nextWeight = 1;
      return {
        require: ['^crmUiWizard', 'form'],
        restrict: 'EA',
        scope: {
          crmTitle: '@', // expression, evaluates to a printable string
          crmUiWizardStep: '@', // int, a weight which determines the ordering of the steps
          crmUiWizardStepClass: '@' // string, A list of classes that will be added to the template
        },
        template: '<div class="crm-wizard-step {{crmUiWizardStepClass}}" ng-show="selected" ng-transclude/></div>',
        transclude: true,
        link: function (scope, element, attrs, ctrls) {
          const crmUiWizardCtrl = ctrls[0];
          const form = ctrls[1];
          if (scope.crmUiWizardStep) {
            scope.crmUiWizardStep = parseInt(scope.crmUiWizardStep);
          } else {
            scope.crmUiWizardStep = nextWeight++;
          }
          scope.isStepValid = function() {
            return form.$valid;
          };
          crmUiWizardCtrl.add(scope);
          scope.$on('$destroy', function(){
            crmUiWizardCtrl.remove(scope);
          });
        }
      };
    })

    // Example: <button crm-confirm="{message: ts('Are you sure you want to continue?')}" on-yes="frobnicate(123)">Frobincate</button>
    // Example: <button crm-confirm="{type: 'disable', obj: myObject}" on-yes="myObject.is_active=0; myObject.save()">Disable</button>
    // Example: <button crm-confirm="{templateUrl: '~/path/to/view.html', export: {foo: bar}}" on-yes="frobnicate(123)">Frobincate</button>
    // Example: <button crm-confirm="{confirmed: true}" on-yes="frobnicate(123)">Frobincate</button>
    .directive('crmConfirm', function ($compile, $rootScope, $templateRequest, $q) {
      // Helpers to calculate default options for CRM.confirm()
      const defaultFuncs = {
        'disable': function (options) {
          return {
            message: ts('Are you sure you want to disable this?'),
            options: {no: ts('Cancel'), yes: ts('Disable')},
            width: 300,
            title: ts('Disable %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        },
        'revert': function (options) {
          return {
            message: ts('Are you sure you want to revert this?'),
            options: {no: ts('Cancel'), yes: ts('Revert')},
            width: 300,
            title: ts('Revert %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        },
        'delete': function (options) {
          return {
            message: ts('Are you sure you want to delete this?'),
            options: {no: ts('Cancel'), yes: ts('Delete')},
            width: 300,
            title: ts('Delete %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        }
      };
      let confirmCount = 0;
      return {
        link: function (scope, element, attrs) {
          $(element).click(function () {
            const options = scope.$eval(attrs.crmConfirm);
            if (attrs.title && !options.title) {
              options.title = attrs.title;
            }
            const defaults = (options.type) ? defaultFuncs[options.type](options) : {};

            let tpl = null;
            let stubId = null;
            if (!options.message) {
              if (options.templateUrl) {
                tpl = $templateRequest(options.templateUrl);
              }
              else if (options.template) {
                tpl = options.template;
              }
              if (tpl) {
                stubId = 'crmUiConfirm_' + (++confirmCount);
                options.message = '<div id="' + stubId + '"></div>';
              }
            }

            if (options.confirmed) {
              scope.$apply(attrs.onYes);
              return;
            }

            CRM.confirm(_.extend(defaults, options))
              .on('crmConfirm:yes', function() { scope.$apply(attrs.onYes); })
              .on('crmConfirm:no', function() { scope.$apply(attrs.onNo); });

            if (tpl && stubId) {
              $q.when(tpl, function(html) {
                const scope = options.scope || $rootScope.$new();
                if (options.export) {
                  angular.extend(scope, options.export);
                }
                const linker = $compile(html);
                $('#' + stubId).append($(linker(scope)));
              });
            }
          });
        }
      };
    })

    // Sets document title & page title; attempts to override CMS title markup for the latter
    // WARNING: Use only once per route!
    // WARNING: This directive works only if your AngularJS base page does not
    // set a custom title (i.e., it has an initial title of "CiviCRM"). See the
    // global variables pageTitleHTML and documentTitle.
    // Example (same title for both): <h1 crm-page-title>{{ts('Hello')}}</h1>
    // Example (separate document title): <h1 crm-document-title="ts('Hello')" crm-page-title><i class="crm-i fa-flag" role="img" aria-hidden="true"></i>{{ts('Hello')}}</h1>
    .directive('crmPageTitle', function($timeout) {
      return {
        scope: {
          crmDocumentTitle: '='
        },
        link: function(scope, $el, attrs) {
          function update() {
            $timeout(function() {
              const newPageTitleHTML = $el.html().trim(),
                newDocumentTitle = scope.crmDocumentTitle || $el.text(),
                dialog = $el.closest('.ui-dialog-content');
              if (dialog.length) {
                dialog.dialog('option', 'title', newDocumentTitle);
                $el.hide();
              } else {
                document.title = $('title').text().replace(documentTitle, newDocumentTitle);
                [].forEach.call(document.querySelectorAll('h1:not(.crm-container h1), .crm-page-title-wrapper>h1'), h1 => {
                  if (h1.classList.contains('crm-page-title') || h1.innerHTML.trim() === pageTitleHTML) {
                    h1.classList.add('crm-page-title');
                    h1.innerHTML = newPageTitleHTML;
                    $el.hide();
                  }
                });
                pageTitleHTML = newPageTitleHTML;
                documentTitle = newDocumentTitle;
              }
            });
          }

          scope.$watch(function() {return scope.crmDocumentTitle + $el.html();}, update);
        }
      };
    })

    // Single-line editable text using ngModel & html5 contenteditable
    // Supports a `placeholder` attribute which shows up if empty and no `default-value`.
    // The `default-value` attribute will force a value if empty (mutually-exclusive with `placeholder`).
    // Usage: <span crm-ui-editable ng-model="model.text" placeholder="Enter text"></span>
    .directive("crmUiEditable", function() {
      return {
        restrict: "A",
        require: "ngModel",
        scope: {
          defaultValue: '='
        },
        link: function(scope, element, attrs, ngModel) {
          function read() {
            let htmlVal = element.text();
            if (!htmlVal) {
              htmlVal = scope.defaultValue || '';
              element.text(htmlVal);
            }
            ngModel.$setViewValue(htmlVal);
          }

          ngModel.$render = function() {
            element.text(ngModel.$viewValue || scope.defaultValue || '');
          };

          // Special handling for enter and escape keys
          element.on('keydown', function(e) {
            // Enter: prevent line break and save
            if (e.which === 13) {
              e.preventDefault();
              element.blur();
            }
            // Escape: undo
            if (e.which === 27) {
              element.text(ngModel.$viewValue || scope.defaultValue || '');
              element.blur();
            }
          });

          element.on("blur change", function() {
            scope.$apply(read);
          });

          element.attr('contenteditable', 'true');
        }
      };
    })

    // Adds an icon picker widget
    // Example: `<input crm-ui-icon-picker ng-model="model.icon">`
    .directive('crmUiIconPicker', function($timeout) {
      return {
        restrict: 'A',
        require: '?ngModel', // Soft require ngModel
        controller: function($element, $scope, $attrs) {
          CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').then(function() {
            $timeout(function() {
              $element.crmIconPicker();

              // If ngModel is present, set up two-way binding
              if ($attrs.ngModel) {
                $scope.$watch($attrs.ngModel, function(newValue) {
                  if (newValue !== undefined) {
                    // Update the value in the picker
                    $element.val(newValue).trigger('change');
                  }
                });
              }
            });
          });
        }
      };
    })

    // Reformat an array of objects for compatibility with select2
    .factory('formatForSelect2', function() {
      return function(input, key, label, extra) {
        return input.reduce((result, item) => {
          const formatted = {id: item[key], text: item[label]};

          if (extra) {
            // Handle extra properties
            extra.forEach(prop => formatted[prop] = item[prop]);
          }

          result.push(formatted);
          return result;
        }, []);
      };
    })

    .run(function($rootScope, $location) {
      /// Example: <button ng-click="goto('home')">Go home!</button>
      $rootScope.goto = function(path) {
        $location.path(path);
      };
      // useful for debugging: $rootScope.log = console.log || function() {};
    });

})(angular, CRM.$, CRM._);
