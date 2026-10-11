<?php

namespace App\Http\Controllers;

use App\Helpers\Table\ArticlesTable;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardAdminStrichlistensystemController extends Controller
{
    /**
     * Display the article management
     */
    public function index(Request $request): Response|JsonResponse
    {
        $articlesTable = new ArticlesTable($request);
        if ($articlesTable->wantsChanges()) {
            return $articlesTable->changesResponse();
        }

        return Inertia::render('Dashboard/Admin/Strichlistensystem/Index', [
            'articles' => $articlesTable->scroll(),
            'icons' => config('strichlistensystem.icons'),
            'limitWindow' => config('strichlistensystem.self_service_limit_window'),
        ]);
    }

    /**
     * Create a new article
     */
    public function storeArticle(Request $request): RedirectResponse
    {
        $article = Article::create($this->validateArticle($request));

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde erstellt.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Update an article
     */
    public function updateArticle(Request $request, Article $article): RedirectResponse
    {
        $article->update($this->validateArticle($request, $article));

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde gespeichert.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Activate or deactivate an article. Deactivated articles are hidden and can not be booked anymore.
     */
    public function toggleArticleActive(Article $article): RedirectResponse
    {
        $article->is_active = ! $article->is_active;
        $article->save();

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde '.($article->is_active ? 'aktiviert' : 'deaktiviert').'.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Delete an article without bookings
     */
    public function deleteArticle(Article $article): RedirectResponse
    {
        if ($article->articleActionLogs()->withTrashed()->exists()) {
            Session::flash('error', 'Der Artikel <strong>'.e($article->name).'</strong> wurde bereits gebucht und kann deshalb nicht gelöscht werden.');

            return Redirect::route('dashboard.admin.strichlistensystem.index');
        }

        $article->delete();

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde gelöscht.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Download the bookings (without cancelled ones) of all users as csv
     */
    public function export(): StreamedResponse
    {
        $fileName = 'strichlisten_export.csv';

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Encoding' => 'UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $articles = Article::orderBy('show_order')->orderBy('name')->get();

        $columns = ['Nachname', 'Vorname'];
        foreach ($articles as $article) {
            $columns[] = 'Anz_'.$article->name;
        }

        $callback = function () use ($columns, $articles) {
            // amounts by user id and article id
            $amounts = [];
            ArticleActionLog::selectRaw('user_id, article_id, count(*) as amount')
                ->groupBy('user_id', 'article_id')
                ->get()
                ->each(function ($row) use (&$amounts) {
                    $amounts[$row->user_id][$row->article_id] = (int) $row->amount;
                });

            $file = fopen('php://output', 'w');

            // add BOM so that excel detects utf-8
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns, ';');

            User::orderBy('lastname')->orderBy('firstname')->chunk(500, function ($users) use ($file, $articles, $amounts) {
                foreach ($users as $user) {
                    $row = [$user->lastname, $user->firstname];
                    foreach ($articles as $article) {
                        $row[] = $amounts[$user->id][$article->id] ?? 0;
                    }

                    fputcsv($file, $row, ';');
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Validate the request data of an article.
     *
     * @return array{name: string, icon: string, show_order: int, max_order_amount: int, is_active: bool}
     */
    private function validateArticle(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:50', Rule::unique('articles', 'name')->ignore($article?->id)],
            'icon' => ['required', 'string', Rule::in(config('strichlistensystem.icons'))],
            'show_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'max_order_amount' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
