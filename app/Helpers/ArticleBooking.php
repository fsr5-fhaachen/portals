<?php

namespace App\Helpers;

use App\Models\Article;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Booking of an article in the strichlistensystem, shared by the kiosk and the self service.
 * They only differ in the access check, the start of the article limit and the messages about the limit.
 */
class ArticleBooking
{
    /**
     * Validate and book an article. Returns null on success, otherwise the error message for the flash.
     * The booked amount is the validated "amount" of the request.
     *
     * @throws ValidationException if the amount or the price is invalid
     */
    public function book(Request $request, User $user, Article $article, CarbonInterface $limitSince, string $limitReachedMessage, string $amountMaxMessage): ?string
    {
        if (! $article->is_active) {
            return 'Der Artikel <strong>'.e($article->name).'</strong> ist nicht mehr verfügbar.';
        }

        $remainingAmount = $user->remainingArticleAmount($article, $limitSince);
        if ($remainingAmount < 1) {
            return $limitReachedMessage;
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$remainingAmount],
            // the price the person saw, the booking is rejected if the price changed in the meantime
            'price' => ['present', 'nullable', 'integer'],
        ], [
            'amount.max' => $amountMaxMessage,
        ]);
        $expectedPrice = $validated['price'] === null ? null : (int) $validated['price'];

        // the price is checked again under a lock, a changed price or a reached limit (e.g. by a parallel booking) fails the booking
        if (! $user->buyArticle($article, (int) $validated['amount'], $request->ip(), $limitSince, $expectedPrice)) {
            if ($article->refresh()->price !== $expectedPrice) {
                return 'Der Preis von <strong>'.e($article->name).'</strong> hat sich geändert. Bitte prüfe den neuen Preis und buche erneut.';
            }

            return $limitReachedMessage;
        }

        return null;
    }
}
