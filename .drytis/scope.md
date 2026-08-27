# Scope

## In Scope
1. **Goat Management**: full CRUD, life-cycle status, health/vaccination logs, breeding records
2. **Investors & Capital**: investor profiles, capital investments (add-ons), share % auto-calc
3. **Transactions**: income & expense entry, categorised, optional goat link
4. **Profit / Loss**: per-period P&L, profit distribution per investor based on share %
5. **Dashboard & Reports**: KPIs, investor statements, CSV export

## Out of Scope (v1)
- Multi-currency support
- SMS / email notifications
- Mobile app (responsive web only)
- Multi-farm / multi-tenant
- Payroll module
- Automated accounting journal / double-entry

## Phases
1. Foundation — env keys, Tailwind, Breeze auth, blueprint docs
2. Goat module — CRUD + health & breeding logs
3. Investors & investments — CRUD + ownership %
4. Transactions & P&L — income/expense + period P&L computation
5. Profit distribution — auto-calculate per-investor payouts
6. Dashboard & reports — KPIs + investor statements + CSV export
7. Verification — infra gate, reviewer, tester
8. Yearly Clearance — annual settlement: year report (sales/purchases/expenses/gross/net), partner-wise settlement with opening balance chaining, permanent completion entry + CSV/PDF exports, YoY comparison. No modification of source transactions.
