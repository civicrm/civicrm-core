Overview
----------------------------------------
Follow-up to #37298. Moves `CRM_Contribute_BAO_FinancialProcessor`'s fee-selection-change code onto its own `createFinancialTrxn()`/`createFinancialItem()` helpers instead of calling out to `CRM_Financial_BAO_FinancialItem::create()`/`::add()`, and fixes a gap where a fee-selection change that didn't alter the amount owed left the changed line items with no transaction to link to.

Before
----------------------------------------
Changing a participant's (or member's) fee selection to a different option of the **same price** - e.g. swapping "Fifty euros" for "Fifty euros again" - updated the line items, but since the balance owed didn't change, no transaction was recorded for the change at all. The contribution's `amount_level` (and, if the amount actually paid didn't already match the stored total, `total_amount`) could be left showing the old selection rather than the new one.

After
----------------------------------------
A fee-selection change that nets to no balance change now still records a transaction (at a $0 total) and links every line item touched by the change - the option being dropped and the option being added - to it, the same way a change that does move the balance already does. The contribution's `amount_level`/`total_amount`/`net_amount`/`fee_amount`/`tax_amount` are kept in sync with the current selection either way, not only when the balance changes.

Technical Details
----------------------------------------
- `CRM_Contribute_BAO_FinancialProcessor::recordAdjustedAmount()` previously only created a `FinancialTrxn` and synced the contribution's `total_amount`/`net_amount`/`fee_amount`/`tax_amount`/`amount_level` inside the `if ($balanceAmt)` branch, returning `NULL` when the balance was unchanged. Both now happen unconditionally; the function returns a plain `int` trxn id (never `NULL`).
- `addFinancialItemsOnLineItemsChange()`'s `$trxnID` param is now a non-nullable `int`, since its only caller (`changeFeeSelections()`) always has a real trxn id now.
- `FinancialProcessor` no longer calls `CRM_Financial_BAO_FinancialItem::create()`/`::add()` anywhere. It uses the in-house `createFinancialTrxn()`/`createFinancialItem()` helpers throughout (added in #37298), with plain `int|null` trxn ids rather than the array-wrapped `$trxnIds['id']`/`$trxnArray` shape those BAO functions required.
- This also removes `addFinancialItem()`'s fallback lookup of "the earliest trxn for this contribution" when no trxn id was passed in. That fallback could attribute a financial item to a trxn that had never actually paid for it - the root cause of `ChangeFeeSelectionTest::testSwappingEqualPricedOptionKeepsThePayment()`'s disabled financial validation, which this PR re-enables.

Comments
----------------------------------------
- `updateFinancialAccountsOnPaymentInstrumentChange()` has a similar simplification opportunity (a proportional-split calculation that is mathematically a no-op in the only tested case - a payment-instrument change on its own). Left out of this PR since it isn't provably safe in the untested case where a financial-type change and a payment-instrument change land in the same update - the ratio stops being 1 there and nothing currently exercises that combination.
- I haven't been able to run the test suite in this environment. `testSwappingEqualPricedOptionKeepsThePayment()`'s re-enabled validation, and the wider `ChangeFeeSelectionTest`/`PaymentTest` suites, are worth a real run before merge.
