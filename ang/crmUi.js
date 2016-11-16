/// crmUi: Sundry UI helpers
(function (angular, $, _) {

  var uidCount = 0,
    pageTitle = 'CiviCRM',
    documentTitle = 'CiviCRM';

  angular.module('crmUi', [])

    // example <div crm-ui-accordion crm-title="ts('My Title')" crm-collapsed="true">...content...</div>
    // WISHLIST: crmCollapsed should support two-way/continuous binding
    .directive('crmUiAccordion', function() {
      return {
        scope: {
          crmUiAccordion: '='
        },
        template: '<div ng-class="cssClasses"><div class="crm-accordion-header">{{crmUiAccordion.title}} <a crm-ui-help="help" ng-if="help"></a></div><div class="crm-accordion-body" ng-transclude></div></div>',
        transclude: true,
        link: function (scope, element, attrs) {
          scope.cssClasses = {
            'crm-accordion-wrapper': true,
            collapsed: scope.crmUiAccordion.collapsed
          };
          scope.help = null;
          scope.$watch('crmUiAccordion', function(crmUiAccordion) {
            if (crmUiAccordion && crmUiAccordion.help) {
              scope.help = crmUiAccordion.help.clone({}, {
                title: crmUiAccordion.title
              });
            }
          });
        }
      };
    })

    // Examples:
    //   crmUiAlert({text: 'My text', title: 'My title', type: 'error'});
    //   crmUiAlert({template: '<a ng-click="ok()">Hello</a>', scope: $scope.$new()});
    //   var h = crmUiAlert({templateUrl: '~/crmFoo/alert.html', scope: $scope.$new()});
    //   ... h.close(); ...
    .service('crmUiAlert', function($compile, $rootScope, $templateRequest, $q) {
      var count = 0;
      return function crmUiAlert(params) {
        var id = 'crmUiAlert_' + (++count);
        var tpl = null;
        if (params.templateUrl) {
          tpl = $templateRequest(params.templateUrl);
        }
        else if (params.template) {
          tpl = params.template;
        }
        if (tpl) {
          params.text = '<div id="' + id + '"></div>'; // temporary stub
        }
        var result = CRM.alert(params.text, params.title, params.type, params.options);
        if (tpl) {
          $q.when(tpl, function(html) {
            var scope = params.scope || $rootScope.$new();
            var linker = $compile(html);
            $('#' + id).append($(linker(scope)));
          });
        }
        return result;
      };
    })

    // Simple wrapper around $.crmDatepicker.
    // example with no time input: <input crm-ui-datepicker="{time: false}" ng-model="myobj.datefield"/>
    // example with custom date format: <input crm-ui-datepicker="{date: 'm/d/y'}" ng-model="myobj.datefield"/>
    .directive('crmUiDatepicker', function () {
      return {
        restrict: 'AE',
        require: 'ngModel',
        scope: {
          crmUiDatepicker: '='
        },
        link: function (scope, element, attrs, ngModel) {
          ngModel.$render = function () {
            element.val(ngModel.$viewValue).change();
          };

          element
            .crmDatepicker(scope.crmUiDatepicker)
            .on('change', function() {
              var requiredLength = 19;
              if (scope.crmUiDatepicker && scope.crmUiDatepicker.time === false) {
                requiredLength = 10;
              }
              if (scope.crmUiDatepicker && scope.crmUiDatepicker.date === false) {
                requiredLength = 8;
              }
              ngModel.$setValidity('incompleteDateTime', !($(this).val().length && $(this).val().length !== requiredLength));
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
          var args = $location.search();
          return (args && args.angularDebug) ? '<div crm-ui-accordion=\'{title: ts("Debug (%1)", {1: crmUiDebug}), collapsed: true}\'><pre>{{data|json}}</pre></div>' : '';
        },
        link: function(scope, element, attrs) {
          var args = $location.search();
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
    // example: <div crm-ui-field="{name: 'subform.myfield', title: ts('My Field'), help: hs('help_field_name')}"> {{mydata}} </div>
    .directive('crmUiField', function() {
      // Note: When writing new templates, the "label" position is particular. See/patch "var label" below.
      var templateUrls = {
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
          var layout = tAttrs.crmLayout ? tAttrs.crmLayout : 'default';
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
            var id = crmUiIdCtrl.get(attrs.crmUiId);
            element.attr('id', id);
          }
        }
      };
    })

    // for example, see crmUiHelp
    .service('crmUiHelp', function(){
      // example: var h = new FieldHelp({id: 'foo'}); h.open();
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

      // example: var hs = crmUiHelp({file: 'CRM/Foo/Bar'});
      return function(defaults){
        // example: hs('myfield')
        // example: hs({id: 'myfield', title: 'Foo Bar', file: 'Whiz/Bang'})
        return function(options) {
          if (_.isString(options)) {
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
            var crmUiHelp = scope.$eval(attrs.crmUiHelp);
            var title = crmUiHelp && crmUiHelp.get('title') ? ts('%1 Help', {1: crmUiHelp.get('title')}) : ts('Help');
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

          var id = crmUiIdCtrl.get(attrs.crmUiFor);
          element.attr('for', id);
          var ngModel = null;

          var updateCss = function () {
            scope.cssClasses['crm-error'] = !ngModel.$valid && !ngModel.$pristine;
          };

          // Note: if target element is dynamically generated (eg via ngInclude), then it may not be available
          // immediately for initialization. Use retries/retryDelay to initialize such elements.
          var init = function (retries, retryDelay) {
            var input = $('#' + id);
            if (input.length === 0) {
              if (retries) {
                $timeout(function(){
                  init(retries-1, retryDelay);
                }, retryDelay);
              }
              return;
            }

            var tgtScope = scope;//.$parent;
            if (attrs.crmDepth) {
              for (var i = attrs.crmDepth; i > 0; i--) {
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
          var ids = {};
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
          var iframe = $(elm)[0];
          iframe.setAttribute('width', '100%');
          iframe.setAttribute('frameborder', '0');

          var refresh = function () {
            if (attrs.crmUiIframeSrc) {
              iframe.setAttribute('src', scope.$parent.$eval(attrs.crmUiIframeSrc));
            }
            else {
              var iframeHtml = scope.$parent.$eval(attrs.crmUiIframe);

              var doc = iframe.document;
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

          var editor = CRM.wysiwyg.create(elm);
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
      var defaultVal = function (defaultValue) {
        var f = function (scope) {
          return defaultValue;
        };
        f.assign = function (scope, value) {
          // ignore changes
        };
        return f;
      };

      // like $parse, but accepts a defaultValue in case expr is undefined
      var parse = function (expr, defaultValue) {
        return expr ? $parse(expr) : defaultVal(defaultValue);
      };

      return {
        template: '',
        link: function (scope, element, attrs) {
          var binding = parse(attrs.binding, true);
          var titleLocked = parse(attrs.titleLocked, ts('Locked'));
          var titleUnlocked = parse(attrs.titleUnlocked, ts('Unlocked'));

          $(element).addClass('crm-i lock-button');
          var refresh = function () {
            var locked = binding(scope);
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
      //
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
          var idx = this.values.indexOf(name);
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
          var options = angular.extend({var: 'crmUiOrderBy'}, scope.$eval(attrs.crmUiOrder));
          scope[options.var] = new CrmUiOrderCtrl(options.defaults);
        }
      };
    })

    // For usage, see crmUiOrder (above)
    .directive('crmUiOrderBy', function() {
      return {
        link: function(scope, element, attrs) {
          function updateClass(crmUiOrderCtrl, name) {
            var dir = crmUiOrderCtrl.getDir(name);
            element
              .toggleClass('sorting_asc', dir === '+')
              .toggleClass('sorting_desc', dir === '-')
              .toggleClass('sorting', dir === '');
          }

          element.on('click', function(e){
            var tgt = scope.$eval(attrs.crmUiOrderBy);
            tgt[0].toggle(tgt[1]);
            updateClass(tgt[0], tgt[1]);
            e.preventDefault();
            scope.$digest();
          });

          var tgt = scope.$eval(attrs.crmUiOrderBy);
          updateClass(tgt[0], tgt[1]);
        }
      };
    })

    // Display a fancy SELECT (based on select2).
    // usage: <select crm-ui-select="{placeholder:'Something',allowClear:true,...}" ng-model="myobj.field"><option...></select>
    .directive('crmUiSelect', function ($parse, $timeout) {
      return {
        require: '?ngModel',
        scope: {
          crmUiSelect: '='
        },
        link: function (scope, element, attrs, ngModel) {
          // In cases where UI initiates update, there may be an extra
          // call to refreshUI, but it doesn't create a cycle.

          if (ngModel) {
            ngModel.$render = function () {
              $timeout(function () {
                // ex: msg_template_id adds new item then selects it; use $timeout to ensure that
                // new item is added before selection is made
                element.select2('val', ngModel.$modelValue);
              });
            };
          }
          function refreshModel() {
            var oldValue = ngModel.$viewValue, newValue = element.select2('val');
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
              $timeout(ngModel.$render);
            }
          }

          init();
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
              element.select2('val', ngModel.$modelValue);
            });
          };
          function refreshModel() {
            var oldValue = ngModel.$viewValue, newValue = element.select2('val');
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
          crmUiTabSet: '@'
        },
        templateUrl: '~/crmUi/tabset.html',
        transclude: true,
        controllerAs: 'crmUiTabSetCtrl',
        controller: function($scope, $parse) {
          var tabs = $scope.tabs = []; // array<$scope>
          this.add = function(tab) {
            if (!tab.id) throw "Tab is missing 'id'";
            tabs.push(tab);
          };
        },
        link: function (scope, element, attrs) {}
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
          var validationKey = attrs.crmUiValidateName ? attrs.crmUiValidateName : 'crmUiValidate';
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
          var model = $parse(attrs.crmUiVisible);
          function updatecChildren() {
            element.css('visibility', model(scope.$parent) ? 'inherit' : 'hidden');
          }
          updatecChildren();
          scope.$parent.$watch(attrs.crmUiVisible, updatecChildren);
        }
      };
    })

    // example: <div crm-ui-wizard="myWizardCtrl"><div crm-ui-wizard-step crm-title="ts('Step 1')">...</div><div crm-ui-wizard-step crm-title="ts('Step 2')">...</div></div>
    // Note: "myWizardCtrl" has various actions/properties like next() and $first().
    // WISHLIST: Allow each step to determine if it is "complete" / "valid" / "selectable"
    // WISHLIST: Allow each step to enable/disable (show/hide) itself
    .directive('crmUiWizard', function() {
      return {
        restrict: 'EA',
        scope: {
          crmUiWizard: '@'
        },
        templateUrl: '~/crmUi/wizard.html',
        transclude: true,
        controllerAs: 'crmUiWizardCtrl',
        controller: function($scope, $parse) {
          var steps = $scope.steps = []; // array<$scope>
          var crmUiWizardCtrl = this;
          var maxVisited = 0;
          var selectedIndex = null;

          var findIndex = function() {
            var found = null;
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
            if (index < this.$index()) return '√';
            if (index === this.$index()) return '»';
            return ' ';
          };
          this.isSelectable = function(step) {
            if (step.selected) return false;
            var result = false;
            angular.forEach(steps, function(otherStep, otherKey) {
              if (step === otherStep && otherKey <= maxVisited) result = true;
            });
            return result;
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
            var key = null;
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
          var realButtonsEl = $(element).closest('.crm-wizard').find('.crm-wizard-buttons');
          $(element).appendTo(realButtonsEl);
        }
      };
    })

    // Example for Font Awesome: <button crm-icon="fa-check">Save</button>
    // Example for jQuery UI (deprecated): <button crm-icon="check">Save</button>
    .directive('crmIcon', function() {
      return {
        restrict: 'EA',
        link: function (scope, element, attrs) {
          if (element.is('[crm-ui-tab]')) {
            // handled in crmUiTab ctrl
            return;
          }
          if (attrs.crmIcon.substring(0,3) == 'fa-') {
            $(element).prepend('<i class="crm-i ' + attrs.crmIcon + '"></i> ');
          }
          else {
            $(element).prepend('<span class="icon ui-icon-' + attrs.crmIcon + '"></span> ');
          }
          if ($(element).is('button')) {
            $(element).addClass('crm-button');
          }
        }
      };
    })

    // example: <div crm-ui-wizard-step crm-title="ts('My Title')" ng-form="mySubForm">...content...</div>
    // If there are any conditional steps, then be sure to set a weight explicitly on *all* steps to maintain ordering.
    // example: <div crm-ui-wizard-step="100" crm-title="..." ng-if="...">...content...</div>
    .directive('crmUiWizardStep', function() {
      var nextWeight = 1;
      return {
        require: ['^crmUiWizard', 'form'],
        restrict: 'EA',
        scope: {
          crmTitle: '@', // expression, evaluates to a printable string
          crmUiWizardStep: '@' // int, a weight which determines the ordering of the steps
        },
        template: '<div class="crm-wizard-step" ng-show="selected" ng-transclude/></div>',
        transclude: true,
        link: function (scope, element, attrs, ctrls) {
          var crmUiWizardCtrl = ctrls[0], form = ctrls[1];
          if (scope.crmUiWizardStep) {
            scope.crmUiWizardStep = parseInt(scope.crmUiWizardStep);
          } else {
            scope.crmUiWizardStep = nextWeight++;
          }
          scope.isStepValid = function() {
            return form.$valid;
          };
          crmUiWizardCtrl.add(scope);
          element.on('$destroy', function(){
            crmUiWizardCtrl.remove(scope);
          });
        }
      };
    })

    // Example: <button crm-confirm="{message: ts('Are you sure you want to continue?')}" on-yes="frobnicate(123)">Frobincate</button>
    // Example: <button crm-confirm="{type: 'disable', obj: myObject}" on-yes="myObject.is_active=0; myObject.save()">Disable</button>
    // Example: <button crm-confirm="{templateUrl: '~/path/to/view.html', export: {foo: bar}}" on-yes="frobnicate(123)">Frobincate</button>
    .directive('crmConfirm', function ($compile, $rootScope, $templateRequest, $q) {
      // Helpers to calculate default options for CRM.confirm()
      var defaultFuncs = {
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
      var confirmCount = 0;
      return {
        link: function (scope, element, attrs) {
          $(element).click(function () {
            var options = scope.$eval(attrs.crmConfirm);
            if (attrs.title && !options.title) {
              options.title = attrs.title;
            }
            var defaults = (options.type) ? defaultFuncs[options.type](options) : {};

            var tpl = null, stubId = null;
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

            CRM.confirm(_.extend(defaults, options))
              .on('crmConfirm:yes', function() { scope.$apply(attrs.onYes); })
              .on('crmConfirm:no', function() { scope.$apply(attrs.onNo); });

            if (tpl && stubId) {
              $q.when(tpl, function(html) {
                var scope = options.scope || $rootScope.$new();
                if (options.export) {
                  angular.extend(scope, options.export);
                }
                var linker = $compile(html);
                $('#' + stubId).append($(linker(scope)));
              });
            }
          });
        }
      };
    })

    // Sets document title & page title; attempts to override CMS title markup for the latter
    // WARNING: Use only once per route!
    // Example (same title for both): <h1 crm-page-title>{{ts('Hello')}}</h1>
    // Example (separate document title): <h1 crm-document-title="ts('Hello')" crm-page-title><i class="crm-i fa-flag"></i>{{ts('Hello')}}</h1>
    .directive('crmPageTitle', function($timeout) {
      return {
        scope: {
          crmDocumentTitle: '='
        },
        link: function(scope, $el, attrs) {
          function update() {
            $timeout(function() {
              var newPageTitle = _.trim($el.html()),
                newDocumentTitle = scope.crmDocumentTitle || $el.text();
              document.title = $('title').text().replace(documentTitle, newDocumentTitle);
              // If the CMS has already added title markup to the page, use it
              $('h1').not('.crm-container h1').each(function() {
                if (_.trim($(this).html()) === pageTitle) {
                  $(this).html(newPageTitle);
                  $el.hide();
                }
              });
              pageTitle = newPageTitle;
              documentTitle = newDocumentTitle;
            });
          }

          scope.$watch(function() {return scope.crmDocumentTitle + $el.html();}, update);
        }
      };
    })

    .run(function($rootScope, $location) {
      /// Example: <button ng-click="goto('home')">Go home!</button>
      $rootScope.goto = function(path) {
        $location.path(path);
      };
      // useful for debugging: $rootScope.log = console.log || function() {};
    })
  ;

})(angular, CRM.$, CRM._);
