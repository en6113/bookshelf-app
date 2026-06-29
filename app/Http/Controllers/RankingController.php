<?php

namespace App\Http\Controllers;

use App\Actions\GetTopRatedBooksAction;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(GetTopRatedBooksAction $getTopRatedBooks): View
    {
        $rankedBooks = $getTopRatedBooks->execute(10);

        return view('ranking.index', compact('rankedBooks'));
    }
}
