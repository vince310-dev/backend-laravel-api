<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ArticleController;
use Illuminate\Console\Command;

class AddNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $articles = new ArticleController();
        $articles->addNews();
    }
}
