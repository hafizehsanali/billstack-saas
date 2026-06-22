<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $this->validated($request);

        $unit = Unit::create([
            ...$data,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        return response()->json($this->resource($unit), 201);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $unit->update($this->validated($request, $unit));

        return response()->json($this->resource($unit->fresh()));
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($unit->isInUse()) {
            return response()->json([
                'message' => 'This unit is currently used by a product or purchase and cannot be deleted.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted successfully.',
        ]);
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units')->where('tenant_id', $tenantId)->ignore($unit),
            ],
            'symbol' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:255'],
        ]);
    }

    private function resource(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'description' => $unit->description,
            'label' => "{$unit->name} ({$unit->symbol}) - {$unit->description}",
        ];
    }
}
