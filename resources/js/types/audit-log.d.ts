declare namespace AuditLog {
  type Actor = {
    id: number;
    name: string;
    roles: string[];
  };

  type Change = {
    field: string;
    label: string;
    old: string | null;
    new: string | null;
  };

  type Entry = {
    id: number;
    created_at: string | null;
    actor: Actor | null;
    action: string;
    action_label: string;
    subject_type: string;
    subject_type_label: string;
    subject_id: number;
    subject_label: string;
    subject_deleted: boolean;
    changes: Change[];
  };

  type Filters = {
    user_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    action: string | null;
    from: string | null;
    to: string | null;
    staff_only: boolean;
  };

  type Option = {
    value: string;
    label: string;
  };
}
