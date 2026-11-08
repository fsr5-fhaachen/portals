declare namespace Strichlistensystem {
  // person as provided by the kiosk (see StrichlistensystemKioskController::toPerson)
  type Person = {
    id: number;
    firstname: string;
    lastname: string;
    course: App.Models.Course | null;
    avatar_url: string | null;
    roles?: string[];
    has_pin?: boolean;
  };

  // balance of a person in cents (see User::strichlistensystemBalance)
  type Balance = {
    total: number;
    paid: number;
    open: number;
    bookings_without_price: number;
  };

  // row of the billing table (see StrichlistensystemBillingTable)
  type BillingRow = {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    is_disabled: boolean;
    article_action_logs_count: number;
    // booked amount per article (without cancelled bookings)
    articles: { name: string; amount: number }[];
    total: number;
    paid: number;
    open: number;
  };

  // whether a person wants to pay by bank transfer and the address for the invoice
  type BillingDetails = {
    wants_bank_transfer: boolean;
    name: string;
    street: string;
    postal_code: string;
    city: string;
  };

  // item of an invoice (amounts in cents)
  type InvoiceItem = {
    name: string;
    quantity: number;
    unit_price: number;
    total: number;
    // vat rate in percent that is included in the prices, 0 without vat (missing on old invoices)
    vat_rate?: number;
  };

  // invoice data of a person in the payment dialog (see DashboardAdminStrichlistensystemController::billingUser)
  type PaymentBilling = {
    wants_bank_transfer: boolean;
    details: {
      name: string;
      street: string;
      postal_code: string;
      city: string;
    };
    // reasons why no invoice can be created, empty if an invoice can be created
    problems: string[];
    preview: {
      items: InvoiceItem[];
      subtotal: number;
      paid: number;
      amount_due: number;
    };
    invoices: {
      id: number;
      number: string;
      issued_on: string;
      amount_due: number;
      is_cancelled: boolean;
    }[];
  };

  // invoice in the list of all invoices (see app/Helpers/Table/StrichlistensystemInvoicesTable)
  type InvoiceRow = {
    id: number;
    number: string;
    title: string;
    issued_on: string;
    due_on: string;
    recipient: {
      name: string;
      street: string;
      postal_code: string;
      city: string;
    };
    user: {
      id: number;
      firstname: string;
      lastname: string;
      email: string;
    } | null;
    created_by: { id: number; firstname: string; lastname: string } | null;
    subtotal: number;
    paid: number;
    amount_due: number;
    status: "open" | "paid" | "cancelled";
  };

  // booking of a person (see app/Helpers/Table/StrichlistensystemBookingsTable)
  type BookingRow = {
    id: number;
    created_at: string;
    is_cancelled: boolean;
    price: number | null;
    user: { id: number; firstname: string; lastname: string } | null;
    article: { id: number; name: string } | null;
  };

  // payment of a person (see DashboardAdminStrichlistensystemController::billingUser)
  type PaymentMethod = "cash" | "bank_transfer";

  type Payment = {
    id: number;
    amount: number;
    method: PaymentMethod;
    created_at: string;
    deleted_at: string | null;
    created_by: { id: number; firstname: string; lastname: string } | null;
    is_undoable: boolean;
  };
}
