<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get all users
     * Required roles: admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('employee_number', 'like', "%$search%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Get user by ID
     * Required roles: admin
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => new UserResource($user)
        ], 200);
    }

    /**
     * Update user
     * Required roles: admin
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'specialization' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => new UserResource($user)
        ], 200);
    }

    /**
     * Delete user (soft delete - set is_active to false)
     * Required roles: admin
     */
    public function destroy(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully'
        ], 200);
    }

    /**
     * Create user invitation
     * Required roles: admin
     */
    public function createInvitation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:invitations,email|unique:users,email',
            'role' => 'required|in:admin,doctor,receptionist,cashier,nurse,lab_technician,pharmacist',
        ]);

        $token = Str::random(32);
        $expiresAt = now()->addDays(7); // Invitation expires in 7 days

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => $token,
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'created_by' => auth()->user()->id,
        ]);

        // Create invitation link
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $invitationLink = "{$frontendUrl}/invite/accept?token={$token}";

        // Send invitation email
        try {
            \Mail::send('emails.invitation', [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invitationLink' => $invitationLink,
                'expiresAt' => $expiresAt,
            ], function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('You are invited to Hospital Management System');
            });
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation created and sent successfully',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $expiresAt,
            ]
        ], 201);
    }

    /**
     * Get all invitations
     * Required roles: admin
     */
    public function getInvitations(Request $request): AnonymousResourceCollection
    {
        $query = Invitation::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $invitations = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return \App\Http\Resources\InvitationResource::collection($invitations);
    }

    /**
     * Validate invitation token (public)
     */
    public function validateInvitation(string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token'
            ], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Invitation has already been {$invitation->status}"
            ], 422);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation has expired'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
            ]
        ], 200);
    }

    /**
     * Accept invitation and create account (public)
     */
    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token'
            ], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Invitation has already been {$invitation->status}"
            ], 422);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation has expired'
            ], 422);
        }

        $validated = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        // Create user account
        $user = User::create([
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'role' => $invitation->role,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        // Mark invitation as accepted
        $invitation->update([
            'status' => 'accepted',
            'used_at' => now(),
        ]);

        // Generate token for auto-login
        $authToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'user' => new UserResource($user),
            'token' => $authToken,
        ], 201);
    }
}
