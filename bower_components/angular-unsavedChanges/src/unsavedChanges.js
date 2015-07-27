'use strict';
/*jshint globalstrict: true*/
/*jshint undef:false */

// @todo NOTE We should investigate changing default to 
// $routeChangeStart see https://github.com/angular-ui/ui-router/blob/3898270241d4e32c53e63554034d106363205e0e/src/compat.js#L126

angular.module('unsavedChanges', ['lazyModel'])

.provider('unsavedWarningsConfig', function() {

    var _this = this;

    // defaults
    var logEnabled = false;
    var useTranslateService = true;
    var routeEvent = ['$locationChangeStart', '$stateChangeStart'];
    var navigateMessage = 'You will lose unsaved changes if you leave this page';
    var reloadMessage = 'You will lose unsaved changes if you reload this page';

    Object.defineProperty(_this, 'navigateMessage', {
        get: function() {
            return navigateMessage;
        },
        set: function(value) {
            navigateMessage = value;
        }
    });

    Object.defineProperty(_this, 'reloadMessage', {
        get: function() {
            return reloadMessage;
        },
        set: function(value) {
            reloadMessage = value;
        }
    });

    Object.defineProperty(_this, 'useTranslateService', {
        get: function() {
            return useTranslateService;
        },
        set: function(value) {
            useTranslateService = !! (value);
        }
    });

    Object.defineProperty(_this, 'routeEvent', {
        get: function() {
            return routeEvent;
        },
        set: function(value) {
            if (typeof value === 'string') value = [value];
            routeEvent = value;
        }
    });
    Object.defineProperty(_this, 'logEnabled', {
        get: function() {
            return logEnabled;
        },
        set: function(value) {
            logEnabled = !! (value);
        }
    });

    this.$get = ['$injector',
        function($injector) {

            function translateIfAble(message) {
                if ($injector.has('$translate') && useTranslateService) {
                    return $injector.get('$translate')(message);
                } else {
                    return false;
                }
            }

            var publicInterface = {
                // log function that accepts any number of arguments
                // @see http://stackoverflow.com/a/7942355/1738217
                log: function() {
                    if (console.log && logEnabled && arguments.length) {
                        var newarr = [].slice.call(arguments);
                        if (typeof console.log === 'object') {
                            log.apply.call(console.log, console, newarr);
                        } else {
                            console.log.apply(console, newarr);
                        }
                    }
                }
            };

            Object.defineProperty(publicInterface, 'useTranslateService', {
                get: function() {
                    return useTranslateService;
                }
            });

            Object.defineProperty(publicInterface, 'reloadMessage', {
                get: function() {
                    return translateIfAble(reloadMessage) || reloadMessage;
                }
            });

            Object.defineProperty(publicInterface, 'navigateMessage', {
                get: function() {
                    return translateIfAble(navigateMessage) || navigateMessage;
                }
            });

            Object.defineProperty(publicInterface, 'routeEvent', {
                get: function() {
                    return routeEvent;
                }
            });

            Object.defineProperty(publicInterface, 'logEnabled', {
                get: function() {
                    return logEnabled;
                }
            });

            return publicInterface;
        }
    ];
})

.service('unsavedWarningSharedService', ['$rootScope', 'unsavedWarningsConfig', '$injector',
    function($rootScope, unsavedWarningsConfig, $injector) {

        // Controller scopped variables
        var _this = this;
        var allForms = [];
        var areAllFormsClean = true;
        var removeFunctions = [angular.noop];

        // @note only exposed for testing purposes.
        this.allForms = function() {
            return allForms;
        };

        // save shorthand reference to messages
        var messages = {
            navigate: unsavedWarningsConfig.navigateMessage,
            reload: unsavedWarningsConfig.reloadMessage
        };

        // Check all registered forms 
        // if any one is dirty function will return true

        function allFormsClean() {
            areAllFormsClean = true;
            angular.forEach(allForms, function(item, idx) {
                unsavedWarningsConfig.log('Form : ' + item.$name + ' dirty : ' + item.$dirty);
                if (item.$dirty) {
                    areAllFormsClean = false;
                }
            });
            return areAllFormsClean; // no dirty forms were found
        }

        // adds form controller to registered forms array
        // this array will be checked when user navigates away from page
        this.init = function(form) {
            if (allForms.length === 0) setup();
            unsavedWarningsConfig.log("Registering form", form);
            allForms.push(form);
        };

        this.removeForm = function(form) {
            var idx = allForms.indexOf(form);

            // this form is not present array
            // @todo needs test coverage 
            if (idx === -1) return;

            allForms.splice(idx, 1);
            unsavedWarningsConfig.log("Removing form from watch list", form);

            if (allForms.length === 0) tearDown();
        };

        function tearDown() {
            unsavedWarningsConfig.log('No more forms, tearing down');
            angular.forEach(removeFunctions, function(fn) {
                fn();
            });
            window.onbeforeunload = null;
        }

        // Function called when user tries to close the window
        this.confirmExit = function() {
            // @todo this could be written a lot cleaner! 
            if (!allFormsClean()) return messages.reload;
            tearDown();
        };

        // bind to window close
        // @todo investigate new method for listening as discovered in previous tests

        function setup() {
            unsavedWarningsConfig.log('Setting up');

            window.onbeforeunload = _this.confirmExit;

            var eventsToWatchFor = unsavedWarningsConfig.routeEvent;

            angular.forEach(eventsToWatchFor, function(aEvent) {
                // calling this function later will unbind this, acting as $off()
                var removeFn = $rootScope.$on(aEvent, function(event, next, current) {
                    unsavedWarningsConfig.log("user is moving with " + aEvent);
                    // @todo this could be written a lot cleaner! 
                    if (!allFormsClean()) {
                        unsavedWarningsConfig.log("a form is dirty");
                        if (!confirm(messages.navigate)) {
                            unsavedWarningsConfig.log("user wants to cancel leaving");
                            event.preventDefault(); // user clicks cancel, wants to stay on page 
                        } else {
                            unsavedWarningsConfig.log("user doesn't care about loosing stuff");
                        }
                    } else {
                        unsavedWarningsConfig.log("all forms are clean");
                    }

                });
                removeFunctions.push(removeFn);
            });
        }
    }
])

.directive('unsavedWarningClear', ['unsavedWarningSharedService',
    function(unsavedWarningSharedService) {
        return {
            scope: true,
            require: '^form',
            priority: 3000,
            link: function(scope, element, attrs, formCtrl) {
                element.bind('click', function(event) {
                    formCtrl.$setPristine();
                });

            }
        };
    }
])

.directive('unsavedWarningForm', ['unsavedWarningSharedService',
    function(unsavedWarningSharedService) {
        return {
            require: 'form',
            link: function(scope, formElement, attrs, formCtrl) {

                // register this form
                unsavedWarningSharedService.init(formCtrl);

                // bind to form submit, this makes the typical submit button work
                // in addition to the ability to bind to a seperate button which clears warning
                formElement.bind('submit', function(event) {
                    if (formCtrl.$valid) {
                        formCtrl.$setPristine();
                    }
                });

                // @todo check destroy on clear button too? 
                scope.$on('$destroy', function() {
                    unsavedWarningSharedService.removeForm(formCtrl);
                });
            }
        };
    }
]);


/**
 * --------------------------------------------
 * Lazy model adapted from vitalets
 * @see https://github.com/vitalets/lazy-model/
 * --------------------------------------------
 *
 */
angular.module('lazyModel', [])

.directive('lazyModel', ['$parse', '$compile',
    function($parse, $compile) {
        return {
            restrict: 'A',
            priority: 500,
            terminal: true,
            require: '^form',
            scope: true,
            compile: function compile(elem, attr) {
                // getter and setter for original model
                var ngModelGet = $parse(attr.lazyModel);
                var ngModelSet = ngModelGet.assign;
                // set ng-model to buffer in isolate scope
                elem.attr('ng-model', 'buffer');
                // remove lazy-model attribute to exclude recursion
                elem.removeAttr("lazy-model");
                return {
                    pre: function(scope, elem) {
                        // initialize buffer value as copy of original model 
                        scope.buffer = ngModelGet(scope.$parent);
                        // compile element with ng-model directive pointing to buffer value   
                        $compile(elem)(scope);
                    },
                    post: function postLink(scope, elem, attr, formCtrl) {
                        // bind form submit to write back final value from buffer
                        var form = elem.parent();
                        while (form[0].tagName !== 'FORM') {
                            form = form.parent();
                        }
                        form.bind('submit', function() {
                            // form valid - save new value
                            if (formCtrl.$valid) {
                                scope.$apply(function() {
                                    ngModelSet(scope.$parent, scope.buffer);
                                });
                            }
                        });
                        form.bind('reset', function(e) {
                            e.preventDefault();
                            scope.$apply(function() {
                                scope.buffer = ngModelGet(scope.$parent);
                            });
                        });
                    }
                };
            }
        };
    }
]);
