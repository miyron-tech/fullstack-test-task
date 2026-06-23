# Frontend - Admin Transactions

Single-page admin view that fetches and renders a transactions table, with a
per-row "Возврат" (Refund) action.

## Stack

React 19 + TypeScript + Vite. Plain CSS, native `fetch` (no axios, no UI library).

## Run

```bash
npm install
npm run dev      # dev server, default http://localhost:5173
npm run build    # type-check (tsc) + production build
npm run preview  # serve the production build
```

## Configuration

The backend base URL is configurable via the `VITE_API_BASE` env var
(default `http://localhost:8000`). Copy `.env.example` to `.env` and adjust:

```bash
cp .env.example .env
```

## API contract

- `GET ${VITE_API_BASE}/api/admin/transactions` - returns a JSON array of
  transactions (see `src/types.ts`).
- `POST ${VITE_API_BASE}/api/admin/transactions/{id}/refund` - body
  `{ amount, reason }`, header `Idempotency-Key: <uuid>`. Returns
  `{ refundId, status }`.

## What to implement

This is a skeleton. The refund flow is left as a stub. Search for
`TODO(candidate)` markers:

- `src/api/client.ts` - implement `requestRefund`: generate an `Idempotency-Key`
  (UUID) and `POST` the refund request.
- `src/components/TransactionsTable.tsx` - wire up the per-row "Возврат" button:
  open a refund modal, call `requestRefund`, and refresh the row.

`fetchTransactions` and the table/loading/error rendering are already done.

## Layout

```
src/
  api/client.ts                     # fetchTransactions (done) + requestRefund (TODO stub)
  components/TransactionsTable.tsx  # table render + refund button (TODO handler)
  types.ts                          # Transaction / RefundResponse types
  App.tsx                           # load on mount, loading/error states
  App.css                           # table + page styles
  index.css                         # global resets
  main.tsx                          # entry point
```
