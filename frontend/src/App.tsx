import { useState } from "react";
import type { Transaction } from "../types";
import { RefundModal, type RowPatch } from "./RefundModal";

interface TransactionsTableProps {
  transactions: Transaction[];
  onRefunded: (patch: RowPatch) => void;
}

function refundable(tx: Transaction): number {
  return Number(tx.amount) - Number(tx.refundedAmount);
}

function isRefundable(tx: Transaction): boolean {
  return refundable(tx) > 0 && tx.status.toLowerCase() !== "refunded";
}

export function TransactionsTable({ transactions, onRefunded }: TransactionsTableProps) {
  const [activeTx, setActiveTx] = useState<Transaction | null>(null);

  return (
    <>
      <table className="tx-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Merchant</th>
            <th className="num">Amount</th>
            <th>Currency</th>
            <th className="num">Fee</th>
            <th>Status</th>
            <th className="num">Refunded</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {transactions.map((tx) => (
            <tr key={tx.id}>
              <td>{tx.id}</td>
              <td>{tx.merchantName}</td>
              <td className="num">{tx.amount}</td>
              <td>{tx.currency}</td>
              <td className="num">{tx.feeDisplayed}</td>
              <td>
                <span className={`status status-${tx.status.toLowerCase()}`}>
                  {tx.status}
                </span>
              </td>
              <td className="num">{tx.refundedAmount}</td>
              <td>{new Date(tx.createdAt).toLocaleString()}</td>
              <td>
                <button
                  type="button"
                  className="refund-btn"
                  disabled={!isRefundable(tx)}
                  onClick={() => setActiveTx(tx)}
                >
                  Возврат
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {activeTx && (
        <RefundModal
          tx={activeTx}
          onClose={() => setActiveTx(null)}
          onSuccess={(patch) => {
            onRefunded(patch);
            setActiveTx(null);
          }}
        />
      )}
    </>
  );
}