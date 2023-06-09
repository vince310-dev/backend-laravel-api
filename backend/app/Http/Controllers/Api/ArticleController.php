<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::query();

        $sources = Setting::select('name as api')->where(['user_id' => $request->input('user'), 'type' => 'source'])->get()->toArray();
        $authors = Setting::select('name as authors')->where(['user_id' => $request->input('user'), 'type' => 'author'])->get()->toArray();
        $categories = Setting::select('name as categories')->where(['user_id' => $request->input('user'), 'type' => 'category'])->get()->toArray();

        if ($request->input('user')) {
            $query->whereNotIn('api', $sources);
            $query->whereNotIn('category', $categories);
            $query->whereNotIn('author', $authors);
        }

        if ($source = $request->input('source')) {
            foreach (explode(";", $source) as $index => $sourceItem) {
                if ($index === 0) {
                    $query->where('api', $sourceItem);
                } else {
                    $query->orWhere('api', $sourceItem);
                }
            }
        }

        if ($category = $request->input('category')) {
            foreach (explode(";", $category) as $index => $categoryItem) {
                if ($index === 0) {
                    $query->where('category', $categoryItem);
                } else {
                    $query->orWhere('category', $categoryItem);
                }
            }
        }

        if ($date = $request->input('date')) {
            $query->whereDate('published_at', $date);
        }

        if ($author = $request->input('author')) {
            foreach (explode(";", $author) as $index => $authorItem) {
                if ($index === 0) {
                    $query->where('author', $authorItem);
                } else {
                    $query->orWhere('author', $authorItem);
                }
            }
        }

        if ($s = $request->input('s')) {
            $query->where('title', 'LIKE', '%' . $s . '%')->orWhere('description', 'LIKE', '%' . $s . '%');
        }

        if ($sort = $request->input('sort')) {
            $query->orderBy('id', $sort);
        }

        $total = $query->count();
        $perPage = 24;
        $page = $request->input('page', 1);

        $articles = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'articles' => $articles,
            'total' => $total,
            'page' => $page,
            'lastPage' => ceil($total / $perPage)
        ], 200);
    }

    public function feed(Request $request)
    {
        $query = Article::query();

        $sources = Setting::select('name as api')->where(['user_id' => $request->input('user'), 'type' => 'source'])->get()->toArray();
        $authors = Setting::select('name as authors')->where(['user_id' => $request->input('user'), 'type' => 'author'])->get()->toArray();
        $categories = Setting::select('name as categories')->where(['user_id' => $request->input('user'), 'type' => 'category'])->get()->toArray();

        if ($request->input('user')) {
            $query->whereNotIn('api', $sources);
            $query->whereNotIn('category', $categories);
            $query->whereNotIn('author', $authors);
        }

        if ($source = $request->input('source')) {
            foreach (explode(";", $source) as $sourceItem) {
                $query->orWhere('api', $sourceItem);
            }
        }

        if ($category = $request->input('category')) {
            foreach (explode(";", $category) as $categoryItem) {
                $query->orWhere('category', $categoryItem);
            }
        }

        if ($author = $request->input('author')) {
            foreach (explode(";", $author) as $authorItem) {
                $query->orWhere('author', $authorItem);
            }
        }

        $articles = $query->get();

        return response()->json([
            'articles' => $articles,
        ], 200);
    }

    public function getSetting(Request $request)
    {
        $sources = Setting::select('name')->where(['user_id' => $request->input('user'), 'type' => 'source'])->get()->toArray();
        $authors = Setting::select('name')->where(['user_id' => $request->input('user'), 'type' => 'author'])->get()->toArray();
        $categories = Setting::select('name')->where(['user_id' => $request->input('user'), 'type' => 'category'])->get()->toArray();

        return response()->json([
            'sources' => $sources,
            'authors' => $authors,
            'categories' => $categories
        ], 200);
    }

    public function getFields()
    {
        $sources = Article::select('api')->distinct()->get();
        $authors = Article::select('author')->distinct()->get();
        $categories = Article::select('category')->distinct()->get();

        return response()->json([
            'sources' => $sources,
            'authors' => $authors,
            'categories' => $categories,
        ], 200);
    }

    public function addNews()
    {
        $this->newsApi();
        $this->theGuardian();
        $this->mediaStack();
        return redirect('http://127.0.0.1:8080');
    }

    public static function mediaStack()
    {
        $api = 'http://api.mediastack.com/v1/news?access_key=31b746f4139bc9126bd0c8c44425e63f';
        $response = Http::get($api);
        $articles = json_decode($response->body())->data;

        foreach ($articles as $news) {
            if (!DB::table('articles')->where('title', $news->title)->first()) {
                $article = new Article();
                $article->source_id = $news->source ? $news->source : 'Unknown';
                $article->source_name = $news->source ? $news->source : 'Unknown';
                $article->api = 'Media Stack';
                $article->author = $news->author ? explode(',', $news->author)[0] : 'Unknown';
                $article->title = $news->title;
                $article->description = $news->description ? $news->description : 'There is no description! Kindly click on below link to read full news.';
                $article->category = $news->category ? $news->category : 'General';
                $article->url = $news->url;
                $article->url_to_image = $news->image ? explode(' ', $news->image)[0] : 'https://placehold.co/1280x750';
                $article->published_at = $news->published_at;
                $article->save();
            }
        }
        return response([
            'message' => 'Data inserted successfully'
        ], 201);
    }

    public static function newsApi()
    {
        $api = 'https://newsapi.org/v2/top-headlines?country=us&apiKey=213107fd09ef4f90a246c43fd716a1c0';
        $response = Http::get($api);
        $articles = json_decode($response->body())->articles;

        foreach ($articles as $news) {
            if (!DB::table('articles')->where('title', $news->title)->first()) {
                $article = new Article();
                $article->source_id = $news->source->id ? $news->source->id : 'Unknown';
                $article->source_name = $news->source->name ? $news->source->name : 'Unknown';
                $article->api = 'News API';
                $article->author = $news->author ? explode(',', $news->author)[0] : 'Unknown';
                $article->title = $news->title;
                $article->description = $news->description ? $news->description : '';
                $article->category = 'General';
                $article->url = $news->url;
                $article->url_to_image = $news->urlToImage ? $news->urlToImage : 'https://placehold.co/1280x750';
                $article->published_at = $news->publishedAt;
                $article->save();
            }
        }
        return response([
            'message' => 'Data inserted successfully'
        ], 201);
    }

    public static function theGuardian()
    {
        $api = 'https://content.guardianapis.com/search?api-key=ad47a302-e2cd-41e2-a505-b348ea046afa';
        $response = Http::get($api);
        $articles = json_decode($response->body())->response->results;

        foreach ($articles as $news) {
            if (!DB::table('articles')->where('title', $news->webTitle)->first()) {
                $article = new Article();
                $article->source_id = $news->id ? $news->id : 'Unknown';
                $article->source_name = 'The Guardian';
                $article->api = 'The Guardian';
                $article->author = 'Guardian Desk';
                $article->title = $news->webTitle;
                $article->description = $news->webTitle;
                $article->category = $news->sectionName;
                $article->url = $news->webUrl;
                $article->url_to_image = 'https://birn.eu.com/wp-content/uploads/2018/11/guardian-300x201.png';
                $article->published_at = $news->webPublicationDate;
                $article->save();
            }
        }
        return response([
            'message' => 'Data inserted successfully'
        ], 201);
    }

    public function addSetting(Request $request)
    {
        $data = Setting::where($request->all())->get();
        if (count($data) < 1) {
            $res = DB::table('settings')->insert($request->all());
        }
        return response($res);
    }

    public function deleteSetting(Request $request)
    {
        $res = DB::table('settings')->where($request->all())->delete();
        return response($res);
    }
}
