# Yearly Clearance / Annual Settlement

## Purpose
Close and settle the financial records for a complete business year. The feature collects and summarizes all financial activities for a selected year, produces a reviewable report, then — on user confirmation — creates a **permanent, immutable completion entry**. Existing transactions are **never modified or deleted**.

## Scope — files to create/modify

### New files
| File | Purpose |
|---|---|
| `database/migrations/2026_08_27_000000_create_yearly_clearances_table.php` | Header snapshot table |
| `database/migrations/2026_08_27_000001_create_yearly_clearance_partners_table.php` | Partner-line table |
| `app/Models/YearlyClearance.php` | Model with casts + relations |
| `app/Models/YearlyClearancePartner.php` | Model with casts + relations |
| `app/Services/YearlyClearanceService.php` | `buildReport(int $year): array` + `complete(int $year, User $user): YearlyClearance` |
| `app/Http/Controllers/YearlyClearanceController.php` | index / report / complete / show / csv / pdf |
| `resources/views/reports/yearly-clearance/index.blade.php` | Year list + comparison |
| `years` view | `resources/views/reports/yearly-clearance/report.blade.php` — report for review |
| `resources/views/reports/yearly-clearance/show.blade.php` | Completed entry view |
| `resources/views/reports/pdf/yearly-clearance.blade.php` | DomPDF template |
| `tests/Feature/YearlyClearanceTest.php` | Feature tests |

### Modified files
| File | Change |
|---|---|
| `routes/web.php` | Add `reports.yearly-clearance.*` route group |
| `app/Providers/AppServiceProvider.php` | Register both models for activity-log tracking |
| `resources/views/layouts/app.blade.php` | Title map + More-sheet link |
| `resources/views/layouts/navigation.blade.php` | Financials dropdown link |

## Financial year definition
Calendar year (Jan 1 – Dec 31), matching the app's `budget_entries` year convention. Year must be ≥ 2020 and ≤ current year + 1.

## Domain mapping
| Requirement term | App domain |
|---|---|
| Sales / revenue | `transactions` where `type=income` (all income categories) |
| Purchases | `transactions` where `type=expense AND category='Goat Purchases'` |
| Business expenses | `transactions` where `type=expense AND category != 'Goat Purchases'` |
| Payments | Expense transactions |
| Received amounts | Income transactions |
| Investments | `investments` rows in year (by `investor_id`, `date` in year) |
| Withdrawals (received by partner) | Income transactions with `investor_id` set, category `Other Income` (i.e. money going to a partner) |
| Profit/loss share | `net profit × share_pct/100` (capital-weighted `share_pct`, computed at completion) |
| Opening balance | Chained from previous year's clearance `final_balance` if exists, else all-time ledger up to Dec 31(year−1) |

### Key semantics (deliberate design decisions)
1. **`share_pct` recomputed at completion time** from capital at that moment (same source as `Investor::share_pct` — capital-derived), snapshotted into the line.
2. **Withdrawals received** = sum of income transactions **linked to the investor** during the year. These are money already paid out to that partner. (Income txns are farm income; when linked to an investor they represent an amount routed to/received by that partner.)
3. **Expenses paid by partner** = sum of expense transactions linked to the investor during the year (mirrors existing `expense_paid` accessor, but year-scoped).
4. **Loss share** = when net profit < 0, the loss share of each partner is `|net| × share_pct/100`, entered as negative profit share.
5. **Final partner balance** = `opening + investment + profit_share + expenses_paid_by_partner − withdrawals_received`, where profit_share is signed (negative on loss). Positive = business owes partner; negative = partner owes business; 0 = settled.

## DB schema

### `yearly_clearances` (one row per completed year)
| Column | Type |
|---|---|
| id | bigint PK |
| year | unsigned smallint, unique |
| clearance_date | date |
| total_sales | decimal(12,2) |
| total_purchases | decimal(12,2) |
| total_expenses | decimal(12,2) |
| total_expenses_business | decimal(12,2) — excl. purchases |
| gross_profit | decimal(12,2) |
| net_profit | decimal(12,2) |
| herd_value_opening / herd_value_closing | decimal(12,2) |
| outstanding_business_balance | decimal(12,2) |
| report_payload | JSON — full computed report at completion |
| completed_by | FK users nullOnDelete |
| completed_at | datetime |
| timestamps |

### `yearly_clearance_partners` (one row per partner in a completed year)
| Column | Type |
|---|---|
| id | bigint PK |
| yearly_clearance_id | FK cascade |
| investor_id | FK investors cascade |
| investor_name | string — snapshot |
| share_pct | decimal(5,2) |
| opening_balance | decimal(12,2) |
| investment | decimal(12,2) |
| profit_share | decimal(12,2) signed |
| expenses_paid_by_partner | decimal(12,2) |
| withdrawals_received | decimal(12,2) |
| final_balance | decimal(12,2) |
| status | enum('payable_to_partner','receivable_from_partner','settled') |
| timestamps |

## Routes
```
GET  /reports/yearly-clearance                      reports.yearly-clearance.index
GET  /reports/yearly-clearance/report?year=         reports.yearly-clearance.report
POST /reports/yearly-clearance/complete             reports.yearly-clearance.complete
GET  /reports/yearly-clearance/{clearance}          reports.yearly-clearance.show
GET  /reports/yearly-clearance/{clearance}/csv      reports.yearly-clearance.csv
GET  /reports/yearly-clearance/{clearance}/pdf      reports.yearly-clearance.pdf
```

## Acceptance criteria (checkboxes)
- [ ] Index page lists completed clearances with year, date, net profit, actions (View / CSV / PDF); year-over-year comparison table
- [ ] Report page for any selected year shows: totals (sales, purchases, expenses, gross, net), partner-wise settlement table with opening/investment/profit share/withdrawals/final balance/status
- [ ] Report is available for review **before** completion (even for years with zero data — shows zeros)
- can be reviewed before completing
- [ ] `Complete Yearly Clearance` button on report page, with confirm dialog; on success redirects to `show` of the new entry with success flash
- [ ] Completion entry contains: financial year, clearance date, totals, partner lines, user, datetime, report payload
- [ ] **Completing a year twice is blocked** (unique year + validation error flash)
- [ ] Existing transactions / investments / distributions are **not modified** (feature test asserts row counts unchanged)
- [ ] Previous-year clearance chaining: year N's opening balance = year N−1's final_balance when a previous clearance exists
- [ ] No previous clearance → opening balance computed from all records before Jan 1 of the year (investments before year + profit share of prior periods − withdrawals − expense adjustments)
- [ ] Status labels: payable to partner / receivable from partner / settled
- [ ] CSV + PDF export of a completed clearance
- [ ] Activity log entries created for clearance create
- [ ] All routes behind `auth`
- [ ] Existing test suite still passes
- [ ] Mobile: page title shows in mobile app bar via title map; link reachable from More sheet + desktop Financials dropdown

## Tests (tests/Feature/YearlyClearanceTest.php)
Use `RefreshDatabase`, MySQL test DB, existing conventions (`#[Test]` attributes, `actingAs(User::factory()->create())`).

1. `test_report_page_renders_totals_and_partner_settlement` — seed investors + capital + transactions; GET report; assert KPI values and partner row numbers.
2. `test_complete_creates_clearance_with_correct_totals_and_chains_previous_year` — complete 2025 (set up 2025 data), then complete 2026 with additional data; assert 2026 opening balances equal 2025 final balances; assert statuses.
3. `test_completing_year_twice_is_blocked` — complete, then POST complete again → redirect + error flash + row count still 1.
4. `test_source_transactions_are_not_modified` — snapshot amounts/dates of all txns+investments, complete, assert identical.
5. `test_completion_records_user_and_datetime_and_report_payload` — assert `completed_by`, `completed_at` set, JSON payload has `totals`.
6. `test_report_available_with_zero_data` — GET report for a year with no data → 200, all totals zero.
6. `test_csv_and_pdf_exports_work` — after completing, GET csv → streamed content contains partner name; GET pdf → `application/pdf` content type.
7. `test_guest_is_redirected_to_login` — no auth → redirect login.
8. `test_loss_year_deducts_from_partner_lines` — expense-heavy year → negative profit share, status `receivable_from_partner` for investors with positive balance gone negative... assert negative profit_share stored.
9. `test_activity_log_recorded` — after complete, assertDatabaseHas activity_logs row for YearlyClearance create.
10. `test_year_validation_rejects_invalid_years` — year 1999 / 2050 → error / 302 back with error.

## Edge cases
- Year with zero activity → all zero totals, report still renders, can complete with zeros
- Investor with zero capital → share_pct 0 → gets a line with profit share 0 (still shown, status computed from opening+investment)
- Rounding: every stored figure rounded to 2dp; share percentages rounded to 2dp
- Previous clearance deleted (cascade deletes lines) — future years keep their own snapshots, unaffected
- Negative opening balance carried from prior loss year → reduces final balance
- Absent/`null` investor on transactions (unlinked) → excluded from partner lines, still counted in farm totals
- Financial year in the future (allowed for planning/review only) — completing a future year is allowed but flagged with a warning in UI; not blocked

## Out of scope
- Custom fiscal-year boundaries (Jan–Dec only)
- Deleting/voiding a completed clearance (by design — permanent record)
- Reversal entries
- Seed data / demo data
