'use strict';

describe('CRM.sigfig', function() {
  it('should round while preserving significant digits', function(){
    expect(CRM.sigfig(9, 1)).toBe(9);
    expect(CRM.sigfig(172, 1)).toBe(200);
    expect(CRM.sigfig(172, 2)).toBe(170);
    expect(CRM.sigfig(176, 2)).toBe(180);
    expect(CRM.sigfig(1492, 1)).toBe(1000);
    expect(CRM.sigfig(1492, 2)).toBe(1500);
    expect(CRM.sigfig(1492, 3)).toBe(1490);
    expect(CRM.sigfig(10943, 3)).toBe(10900);
  });
});
