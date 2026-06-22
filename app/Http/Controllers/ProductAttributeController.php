<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductAttributeController extends Controller
{
    public function index(): View
    {
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();

        return view('product-attributes.index', compact('attributes'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        $attribute = DB::transaction(function () use ($request, $data): ProductAttribute {
            $attribute = ProductAttribute::create([
                'tenant_id' => $request->user()->tenant_id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncValues($attribute, $data['values']);

            return $attribute;
        });

        if ($request->expectsJson()) {
            return response()->json($this->resource($attribute->fresh('values')), 201);
        }

        return back()->with('success', 'Product attribute created successfully.');
    }

    public function update(Request $request, ProductAttribute $productAttribute): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request, $productAttribute);

        DB::transaction(function () use ($request, $data, $productAttribute): void {
            $productAttribute->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncValues($productAttribute, $data['values']);
        });

        if ($request->expectsJson()) {
            return response()->json($this->resource($productAttribute->fresh('values')));
        }

        return back()->with('success', 'Product attribute updated successfully.');
    }

    public function destroy(Request $request, ProductAttribute $productAttribute): RedirectResponse|JsonResponse
    {
        if ($productAttribute->values()->whereHas('variants')->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This attribute is used by product variants and cannot be deleted.',
                ], 422);
            }

            return back()->withErrors([
                'attribute' => 'This attribute is used by product variants and cannot be deleted.',
            ]);
        }

        $productAttribute->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Product attribute deleted successfully.']);
        }

        return back()->with('success', 'Product attribute deleted successfully.');
    }

    private function validated(Request $request, ?ProductAttribute $attribute = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_attributes')
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->ignore($attribute),
            ],
            'values' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $values = collect(explode(',', $data['values']))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique(fn (string $value) => Str::lower($value))
            ->values();

        if ($values->isEmpty()) {
            abort(422, 'Add at least one attribute value.');
        }

        $data['values'] = $values;

        return $data;
    }

    private function syncValues(ProductAttribute $attribute, $values): void
    {
        $keptIds = [];

        foreach ($values as $index => $value) {
            $model = $attribute->values()->firstOrCreate(
                ['value' => $value],
                ['slug' => Str::slug($value), 'sort_order' => $index]
            );
            $model->update(['sort_order' => $index]);
            $keptIds[] = $model->id;
        }

        $attribute->values()
            ->whereNotIn('id', $keptIds)
            ->whereDoesntHave('variants')
            ->delete();
    }

    private function resource(ProductAttribute $attribute): array
    {
        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'is_active' => $attribute->is_active,
            'values' => $attribute->values->map(fn ($value) => [
                'id' => $value->id,
                'value' => $value->value,
            ])->values()->all(),
            'can_delete' => $attribute->values->every(
                fn ($value) => $value->variants()->doesntExist()
            ),
        ];
    }
}
