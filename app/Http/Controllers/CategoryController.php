<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
       $data = $request->validated();
        Category::create([
            'tenant_id' => auth()->user()->tenant_id,
             'name' => $data['name']
        ]);

        return redirect()->route('categories.index')
         ->with('success', 'Category created successfully.');
    }
}