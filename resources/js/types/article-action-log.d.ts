declare namespace App.Models {
  type ArticleActionLog = {
    id: number;
    deleted_at: string /* Date */ | null;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    user_id: number;
    article_id: number;
    // price in cents at the time of the booking, null if the article had no price yet
    price: number | null;
    cancel_until: string /* Date */ | null;
    user?: User | null;
    article?: Article | null;
    use_factory?: any | null;
  };
}
