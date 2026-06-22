<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();

        return view('brands.index', compact('brands'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        $brand = Brand::create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json($this->resource($brand), 201);
        }

        return back()->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request, $brand);

        $brand->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->expectsJson()) {
            return response()->json($this->resource($brand->fresh()));
        }

        return back()->with('success', 'Brand updated successfully.');
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        if ($brand->products()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This brand is used by products and cannot be deleted.',
                ], 422);
            }

            return back()->withErrors([
                'brand' => 'This brand is used by products and cannot be deleted.',
            ]);
        }

        $brand->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Brand deleted successfully.']);
        }

        return back()->with('success', 'Brand deleted successfully.');
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands')->where('tenant_id', $request->user()->tenant_id)->ignore($brand),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function resource(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'description' => $brand->description,
            'is_active' => $brand->is_active,
        ];
    }
}
