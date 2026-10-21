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
    total: number;
    paid: number;
    open: number;
  };

  // payment of a person (see DashboardAdminStrichlistensystemController::billingUser)
  type Payment = {
    id: number;
    amount: number;
    created_at: string;
    deleted_at: string | null;
    created_by: { id: number; firstname: string; lastname: string } | null;
    is_undoable: boolean;
  };
}
