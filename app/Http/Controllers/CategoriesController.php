<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use Illuminate\Support\Facades\DB;
class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $sql = " select * from categories ";
        $categories = DB::select($sql);
        return view('admin.categories.index',compact('categories'));
    }
}
