# Database Schema

## Tables

### users (Laravel default + Breeze)
Standard Laravel users table.

### breeds
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | unique |
| description | text, nullable | |
| timestamps | | |

### goats
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| tag_id | string | unique ear-tag |
| name | string, nullable | |
| breed_id | FK → breeds | |
| gender | enum(male, female) | |
| dob | date, nullable | |
| weight_kg | decimal(8,2), default 0 | |
| status | enum(kid, grower, breeder, sold, deceased) | default: kid |
| purchase_price | decimal(12,2), default 0 | |
| sale_price | decimal(12,2), default 0 | |
| photo | string, nullable | file path |
| notes | text, nullable | |
| timestamps + soft deletes | | |

### health_records
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| goat_id | FK → goats | |
| type | enum(vaccination, treatment, checkup, other) | |
| date | date | |
| cost | decimal(12,2), default 0 | |
| notes | text, nullable | |
| timestamps | | |

### breeding_records
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| sire_id | FK → goats | male parent |
| dam_id | FK → goats | female parent |
| breeding_date | date | |
| kidding_date | date, nullable | |
| litter_size | int, default 0 | |
| notes | text, nullable | |
| timestamps | | |

### investors
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| email | string, nullable | |
| phone | string, nullable | |
| address | text, nullable | |
| notes | text, nullable | |
| timestamps | | |

### investments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| investor_id | FK → investors | |
| amount | decimal(12,2) | |
| date | date | |
| notes | text, nullable | |
| timestamps | | |

### ownership_units
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| goat_id | FK → goats, nullable | per-goat ownership |
| investor_id | FK → investors | |
| share_pct | decimal(5,2) | 0–100 |
| timestamps | | |

### transactions
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| type | enum(income, expense) | |
| category | string | e.g. sale, feed, vet |
| amount | decimal(12,2) | |
| date | date | |
| goat_id | FK → goats, nullable | optional link |
| investor_id | FK → investors, nullable | optional link (e.g. payout) |
| notes | text, nullable | |
| timestamps | | |

### profit_distributions
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| investor_id | FK → investors | |
| period_start | date | |
| period_end | date | |
| total_profit | decimal(12,2) | |
| share_pct | decimal(5,2) | snapshot at distribution |
| amount | decimal(12,2) | distributed amount |
| status | enum(pending, paid) | default: pending |
| notes | text, nullable | |
| timestamps | | |

## Key Relationships
- Goat → Breed (belongsTo)
- Goat → HealthRecords (hasMany)
- Goat → BreedingRecords (as sire or dam)
- Investor → Investments (hasMany)
- Investor → OwnershipUnits (hasMany) → Goat
- Investor → Transactions (hasMany)
- Investor → ProfitDistributions (hasMany)

## API Endpoints (web routes, resource controllers)
| Method | URI | Description |
|--------|-----|-------------|
| GET | /dashboard | Dashboard KPIs |
| Resource | /goats | Goat CRUD |
| Resource | /goats/{goat}/health | Health log (nested) |
| Resource | /goats/{goat}/breeding | Breeding log (nested) |
| Resource | /investors | Investor CRUD |
| Resource | /investors/{investor}/investments | Investment log |
| Resource | /transactions | Transactions CRUD |
| GET | /reports/profit-loss | P&L report |
| POST | /reports/distribute | Create profit distribution |
| GET | /reports/investor/{id} | Investor statement |
| GET | /reports/investor/{id}/csv | CSV export |

## Yearly Clearance (added 2026-08)
- `yearly_clearances`: one permanent completion entry per financial year (unique `year`). Snapshots total_sales, total_purchases, total_expenses, gross/net profit, opening/closing herd values, outstanding business balance, full `report_payload` JSON, `completed_by` (FK users), `completed_at`.
- `yearly_clearance_partners`: one settlement line per investor per clearance. Snapshots investor_name, share_pct, opening_balance, investment, profit_share (signed), expenses_paid_by_partner, withdrawals_received, final_balance, status enum(payable_to_partner / receivable_from_partner / settled).
- Partner math: `final = opening + investment + profit_share + expenses_paid_by_partner − withdrawals_received`.
- Opening balance chains from previous year's clearance when present; otherwise derived from all records before Jan 1.
- Existing transactions/investments are NEVER modified by clearance.

## Yearly Clearance endpoints
| Method | URI | Description |
|--------|-----|-------------|
| GET | /reports/yearly-clearance | Completed clearances + YoY comparison |
| GET | /reports/yearly-clearance/report?year= | Reviewable report (pre/post completion) |
| POST | /reports/yearly-clearance/complete | Creates completion entry |
| GET | /reports/yearly-clearance/{clearance} | Completed entry view |
| GET | /reports/yearly-clearance/{clearance}/csv | CSV export |
| GET | /reports/yearly-clearance/{clearance}/pdf | PDF export |
