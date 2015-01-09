'use strict';

describe('crmJsonComparator', function() {
  var comparator;

  beforeEach(function() {
    module('crmJsonComparator');
  });

  beforeEach(function() {
    inject(function(crmJsonComparator) {
      comparator = crmJsonComparator;
    });
  });

  it('should return false when comparing different objects', function() {
    var result = comparator.compare({'foo': 'bar'}, {'bar': 'foo'});
    expect(result.pass).toBe(false);
  });

  it('should return true when comparing equal objects', function() {
    var result = comparator.compare({'bar': 'foo'}, {'bar': 'foo'});
    expect(result.pass).toBe(true);
  });

  it('should explain what part of the comparison failed when comparing objects', function() {
    var result = comparator.compare({'foo': 'bar'}, {'bar': 'foo'});
    expect(result.message).toBe('Could not find key \'bar\' in actual data at root.');
  });

  it('should handle nested objects', function() {
     var result = comparator.compare({'foo': {'bif': 'bam'}}, {'foo': {'bif': 'bam'}});
    expect(result.pass).toBe(true);
  });

  it('should handle differences in nested objects', function() {
     var result = comparator.compare({'foo': {'bif': 'bam'}}, {'foo': {'bif': 'bop'}});
    expect(result.pass).toBe(false);
    expect(result.message).toBe("Expected 'bam' to be 'bop' at root[foo][bif].");
  });

  it('should handle arrays', function() {
    var result = comparator.compare([1, 2, 3, 4], [1, 2, 3, 4]);
    expect(result.pass).toBe(true);
  });

  it('should handle arrays with differences', function() {
    var result = comparator.compare([1, 2, 2, 4], [1, 2, 3, 4]);
    expect(result.pass).toBe(false);
    expect(result.message).toBe("Expected '2' to be '3' at root[2].");
  });

  it('should handle nested arrays and objects', function() {
    var result = comparator.compare([1, 2, {'foo': 'bar'}, 4], [1, 2, {'foo': 'bar'}, 4]);
    expect(result.pass).toBe(true);
  });

  it('should handle nested arrays and objects with differences', function() {
    var result = comparator.compare([1, 2, {'foo': 'bar'}, 4], [1, 2, {'foo': 'bif'}, 4]);
    expect(result.pass).toBe(false);
    expect(result.message).toBe("Expected 'bar' to be 'bif' at root[2][foo].");
  });

  it('should complain when comparing an object to an array', function() {
    var result = comparator.compare({'foo': 'bar'}, [1, 2, 3]);
    expect(result.pass).toBe(false);
    expect(result.message).toBe("The expected data has an array at root, but the actual data has something else ([object Object])");
  });

  it('should complain when comparing an array to an object', function() {
    var result = comparator.compare([1, 2, 3], {'foo': 'bar'});
    expect(result.pass).toBe(false);
    expect(result.message).toBe("The expected data has an object at root, but the actual data has something else (1,2,3)");
  });

});
