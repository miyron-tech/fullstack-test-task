import type { RefundResponse, Transaction } from "../types";

const API_BASE = import.meta.env.VITE_API_BASE ?? "http://localhost:8000";

export async function fetchTransactions(): Promise<Transaction[]> {
  const res = await fetch(`${API_BASE}/api/admin/transactions`);
  if (!res.ok) {
    throw new Error(`Failed to load transactions: ${res.status} ${res.statusText}`);
  }
  return (await res.json()) as Transaction[];
}

export async function requestRefund(
  id: number,
  amount: string,
  reason: string,
): Promise<RefundResponse> {
  // TODO(candidate): generate a unique Idempotency-Key (UUID) and POST the refund.
  // Send POST to `${API_BASE}/api/admin/transactions/${id}/refund`
  //   - JSON body: { amount, reason }
  //   - header: "Idempotency-Key": <uuid>
  // Check res.ok, throw on error, and return the parsed RefundResponse.
  void id;
  void amount;
  void reason;
  throw new Error("requestRefund not implemented");
}
