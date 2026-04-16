<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WardBedController extends Controller
{
    public function indexWards(): JsonResponse
    {
        $wards = Ward::withCount(['beds'])->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $wards->map(function ($ward) {
                return [
                    'id' => $ward->id,
                    'name' => $ward->name,
                    'description' => $ward->description,
                    'is_active' => $ward->is_active,
                    'beds_count' => $ward->beds_count,
                ];
            }),
        ]);
    }

    public function storeWard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $ward = Ward::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ward created successfully',
            'data' => $ward,
        ], 201);
    }

    public function updateWard(Request $request, Ward $ward): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $ward->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ward updated successfully',
            'data' => $ward,
        ]);
    }

    public function destroyWard(Ward $ward): JsonResponse
    {
        $ward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ward deleted successfully',
        ]);
    }

    public function indexBeds(Request $request): JsonResponse
    {
        $query = Bed::with('ward');

        if ($request->has('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }

        if ($request->has('available')) {
            $query->where('is_occupied', false)->where('is_active', true);
        }

        $beds = $query->orderBy('ward_id')->orderBy('bed_number')->get();

        return response()->json([
            'success' => true,
            'data' => $beds->map(function ($bed) {
                return [
                    'id' => $bed->id,
                    'ward_id' => $bed->ward_id,
                    'ward_name' => $bed->ward?->name,
                    'bed_number' => $bed->bed_number,
                    'is_occupied' => $bed->is_occupied,
                    'is_active' => $bed->is_active,
                ];
            }),
        ]);
    }

    public function storeBed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ward_id' => 'required|exists:wards,id',
            'bed_number' => 'required|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $existing = Bed::where('ward_id', $validated['ward_id'])
            ->where('bed_number', $validated['bed_number'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Bed number already exists in this ward',
            ], 422);
        }

        $bed = Bed::create($validated);
        $bed->load('ward');

        return response()->json([
            'success' => true,
            'message' => 'Bed created successfully',
            'data' => [
                'id' => $bed->id,
                'ward_id' => $bed->ward_id,
                'ward_name' => $bed->ward?->name,
                'bed_number' => $bed->bed_number,
                'is_occupied' => $bed->is_occupied,
                'is_active' => $bed->is_active,
            ],
        ], 201);
    }

    public function updateBed(Request $request, Bed $bed): JsonResponse
    {
        $validated = $request->validate([
            'bed_number' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['bed_number'])) {
            $existing = Bed::where('ward_id', $bed->ward_id)
                ->where('bed_number', $validated['bed_number'])
                ->where('id', '!=', $bed->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bed number already exists in this ward',
                ], 422);
            }
        }

        $bed->update($validated);
        $bed->load('ward');

        return response()->json([
            'success' => true,
            'message' => 'Bed updated successfully',
            'data' => [
                'id' => $bed->id,
                'ward_id' => $bed->ward_id,
                'ward_name' => $bed->ward?->name,
                'bed_number' => $bed->bed_number,
                'is_occupied' => $bed->is_occupied,
                'is_active' => $bed->is_active,
            ],
        ]);
    }

    public function destroyBed(Bed $bed): JsonResponse
    {
        $bed->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bed deleted successfully',
        ]);
    }
}
