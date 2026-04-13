<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicalTestController extends Controller
{
    /**
     * List all active medical tests (available to all authenticated users)
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicalTest::query();

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('test_name', 'like', "%$search%")
                    ->orWhere('test_code', 'like', "%$search%")
                    ->orWhere('category', 'like', "%$search%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $perPage = $request->input('per_page', 50);
        $tests = $query->orderBy('test_name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tests->items(),
            'meta' => [
                'current_page' => $tests->currentPage(),
                'last_page' => $tests->lastPage(),
                'per_page' => $tests->perPage(),
                'total' => $tests->total(),
            ]
        ], 200);
    }

    /**
     * Create a new medical test (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_name' => 'required|string|max:255',
            'test_code' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $test = MedicalTest::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Medical test created successfully',
            'test' => $test
        ], 201);
    }

    /**
     * Update a medical test (admin only)
     */
    public function update(Request $request, MedicalTest $medicalTest): JsonResponse
    {
        $validated = $request->validate([
            'test_name' => 'sometimes|string|max:255',
            'test_code' => 'sometimes|nullable|string|max:100',
            'category' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string',
            'cost' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $medicalTest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Medical test updated successfully',
            'test' => $medicalTest
        ], 200);
    }

    /**
     * Soft-delete (deactivate) a medical test (admin only)
     */
    public function destroy(MedicalTest $medicalTest): JsonResponse
    {
        $medicalTest->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Medical test deactivated successfully'
        ], 200);
    }
}
