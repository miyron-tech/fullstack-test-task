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
  const idempotencyKey =
    typeof crypto !== "undefined" && "randomUUID" in crypto
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(16).slice(2)}`;

  const res = await fetch(`${API_BASE}/api/admin/transactions/${id}/refund`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Idempotency-Key": idempotencyKey,
    },
    body: JSON.stringify({ amount, reason }),
  });

  if (!res.ok) {
    let message = `Refund failed: ${res.status} ${res.statusText}`;
    try {
      const body = (await res.json()) as { error?: string };
      if (body && typeof body.error === "string") message = body.error;
    } catch {
    }
    throw new Error(message);
  }

  return (await res.json()) as RefundResponse;
}