declare namespace App.Models {
  type Article = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    icon: string;
    // path of the uploaded image in the s3 bucket, the image is shown instead of the icon
    image: string | null;
    // url of the uploaded image, null if the article has no image
    image_url: string | null;
    show_order: number;
    max_order_amount: number;
    // price in cents, null if no price is set yet
    price: number | null;
    is_active: boolean;
    remaining_amount?: number;
    limit_resets_at?: string | null;
    article_action_logs_count?: number;
    article_action_logs?: ArticleActionLog[] | null;
    use_factory?: any | null;
  };
}
