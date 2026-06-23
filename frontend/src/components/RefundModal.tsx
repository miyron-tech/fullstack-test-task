import { useState } from "react";
import type { Transaction } from "../types";
import { requestRefund } from "../api/client";

export interface RowPatch {
  id: number;
  status: string;
  refundedAmount: string;
}

interface RefundModalProps {
  tx: Transaction;
  onClose: () => void;
  onSuccess: (patch: RowPatch) => void;
}

const AMOUNT_RE = /^\d{1,12}(\.\d{1,2})?$/;

export function RefundModal({ tx, onClose, onSuccess }: RefundModalProps) {
  const refundable = (Number(tx.amount) - Number(tx.refundedAmount)).toFixed(2);
  const [amount, setAmount] = useState(refundable);
  const [reason, setReason] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function validate(): string | null {
    if (!AMOUNT_RE.test(amount)) {
      return 'Сумма должна быть положительным числом, напр. "10.00".';
    }
    if (Number(amount) <= 0) {
      return "Сумма возврата должна быть больше нуля.";
    }
    if (Number(amount) > Number(refundable)) {
      return `Сумма превышает доступные к возврату ${refundable} ${tx.currency}.`;
    }
    return null;
  }

  async function handleSubmit() {
    const validationError = validate();
    if (validationError) {
      setError(validationError);
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const result = await requestRefund(tx.id, amount, reason.trim());
      onSuccess({
        id: tx.id,
        status: result.transactionStatus,
        refundedAmount: result.refundedAmount,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Не удалось выполнить возврат.");
      setSubmitting(false);
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose} role="presentation">
      <div
        className="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="refund-title"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 id="refund-title" className="modal-title">
          Возврат по транзакции #{tx.id}
        </h2>
        <p className="modal-sub">
          {tx.merchantName} · доступно к возврату {refundable} {tx.currency}
        </p>

        <label className="field">
          <span>Сумма</span>
          <input
            type="text"
            inputMode="decimal"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            disabled={submitting}
            autoFocus
          />
        </label>

        <label className="field">
          <span>Причина (необязательно)</span>
          <textarea
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            rows={3}
            disabled={submitting}
          />
        </label>

        {error && <p className="modal-error">{error}</p>}

        <div className="modal-actions">
          <button
            type="button"
            className="btn-secondary"
            onClick={onClose}
            disabled={submitting}
          >
            Отмена
          </button>
          <button
            type="button"
            className="btn-primary"
            onClick={handleSubmit}
            disabled={submitting}
          >
            {submitting ? "Обработка…" : "Сделать возврат"}
          </button>
        </div>
      </div>
    </div>
  );
}