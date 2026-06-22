<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')
            ->withCount(['products', 'children'])
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return redirect(route('categories.index').'#add-category');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $category = Category::create([
            'tenant_id' => auth()->user()->tenant_id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
        ]);

        if ($request->expectsJson()) {
            return response()->json($this->resource($category), 201);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $parentId = $data['parent_id'] ?? null;

        if ($parentId && $this->descendantIds($category)->contains((int) $parentId)) {
            $message = 'A category cannot be placed under one of its own subcategories.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['parent_id' => $message]);
        }

        $category->update($data);

        if ($request->expectsJson()) {
            return response()->json($this->resource($category->fresh()));
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->deletionError($request, 'This category is used by products and cannot be deleted.');
        }

        if ($category->children()->exists()) {
            return $this->deletionError($request, 'Move or delete this category\'s subcategories first.');
        }

        $category->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Category deleted successfully.']);
        }

        return back()->with('success', 'Category deleted successfully.');
    }

    private function descendantIds(Category $category)
    {
        $all = Category::get(['id', 'parent_id']);
        $descendants = collect();
        $pending = collect([$category->id]);

        while ($pending->isNotEmpty()) {
            $children = $all->whereIn('parent_id', $pending)->pluck('id');
            $descendants = $descendants->merge($children);
            $pending = $children;
        }

        return $descendants->map(fn ($id) => (int) $id);
    }

    private function deletionError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->withErrors(['category' => $message]);
    }

    private function resource(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
        ];
    }
}
