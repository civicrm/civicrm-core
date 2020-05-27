'use strict';

(function(root) {
  var Comparator = function() {};
  Comparator.prototype = {
    compare: function(actual, expected) {
      this.result = {
        'pass': true,
      };
      this.internal_compare('root', actual, expected);
      return this.result;
    },
    internal_compare: function(context, actual, expected) {
      if (expected instanceof Array) {
        return this.internal_compare_array(context, actual, expected);
      } else if (expected instanceof Object) {
        return this.internal_compare_object(context, actual, expected);
      } else {
        return this.internal_compare_value(context, actual, expected);
      }
      return true;
    },
    internal_compare_array: function(context, actual, expected) {
      if (!(actual instanceof Array)) {
        this.result.pass = false;
        this.result.message = "The expected data has an array at " + context + ", but the actual data has something else (" + actual + ")";
        return false;
      }
      if (expected.length != actual.length) {
        this.result.pass = false;
        this.result.message = "The expected data has an array with " + expected.length + " items in it, but the actual data has " + actual.length + " items.";
        return false;
      }
      for (var i = 0; i < expected.length; i++) {
        var still_matches = this.internal_compare(context + "[" + i + "]", actual[i], expected[i]);
        if (!still_matches) {
          return false;
        }
      }
      return true;
    },
    internal_compare_object: function(context, actual, expected) {
      if (!(actual instanceof Object) || actual instanceof Array) {
        this.result.pass = false;
        this.result.message = "The expected data has an object at root, but the actual data has something else (" + actual + ")";
        return false;
      }
      for (var key in expected) {
        if (!(key in actual)) {
          this.result.pass = false;
          this.result.message = "Could not find key '" + key + "' in actual data at " + context + ".";
          return false;
        }
        var still_matches = this.internal_compare(context + "[" + key + "]", actual[key], expected[key]);
        if (!still_matches) {
          return false;
        }
      }
      for (var key in actual) {
        if (!(key in expected)) {
          this.result.pass = false;
          this.result.message = "Did not expect key " + key + " in actual data at " + context + ".";
          return false;
        }
      }
      return true;
    },
    internal_compare_value: function(context, actual, expected) {
      if (expected === actual) {
        return true;
      }
      this.result.pass = false;
      this.result.message = "Expected '" + actual + "' to be '" + expected + "' at " + context + ".";
      return false;
    },
    register: function(jasmine) {
      var comparator = this;
      jasmine.addMatchers({
        toEqualData: function(expected) {
          return {
            compare: $.proxy(comparator.compare, comparator)
          }
        }
      });
    }
  };
  var module = angular.module('crmJsonComparator', []);
  module.service('crmJsonComparator', Comparator);
})(angular);
