export interface Transaction {
  id: number;
  merchantId: number;
  merchantName: string;
  /** Decimal string, e.g. "100.00" */
  amount: string;
  currency: string;
  /** Decimal string, e.g. "2.50" */
  feeDisplayed: string;
  status: string;
  /** ISO 8601 timestamp */
  createdAt: string;
  /** Decimal string, e.g. "0.00" */
  refundedAmount: string;
}

export interface RefundResponse {
  refundId: number;
  status: string;
}
