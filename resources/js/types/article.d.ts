declare namespace App.Models {
  type Article = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    icon: string;
    show_order: number;
    max_order_amount: number;
    is_active: boolean;
    remaining_amount?: number;
    limit_resets_at?: string | null;
    article_action_logs_count?: number;
    article_action_logs?: ArticleActionLog[] | null;
    use_factory?: any | null;
  };
}
