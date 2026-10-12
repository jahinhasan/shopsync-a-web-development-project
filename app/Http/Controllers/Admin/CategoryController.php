<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::with('parent')->withCount('products')->latest()->paginate(15)]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create():View
    {
        return view('admin.categories.create', ['categories' => Category::orderBy('name')->get()]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug']=$data['slug']?? Str::slug($data['name']);
        Category::create($data);
        return to_route('admin.categories.index')->with('success', 'Category created successfully.');

    }

    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View
    {
               return view('admin.categories.edit', ['category' => $category, 'categories' => Category::whereKeyNot($category->id)->orderBy('name')->get()]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
                $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $category->update($data);

        return to_route('admin.categories.index')->with('success', 'Category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
      
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Move or remove this category’s products first.']);
        }

        $category->delete();

        return to_route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
