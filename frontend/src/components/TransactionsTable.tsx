import type { Transaction } from "../types";

interface TransactionsTableProps {
  transactions: Transaction[];
}

export function TransactionsTable({ transactions }: TransactionsTableProps) {
  function handleRefundClick(tx: Transaction) {
    // TODO(candidate): open refund modal, call requestRefund, refresh row
    console.log("Refund requested for transaction", tx.id);
  }

  return (
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
                onClick={() => handleRefundClick(tx)}
              >
                Возврат
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
