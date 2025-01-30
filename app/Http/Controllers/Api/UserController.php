<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Service\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\json;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new UserResource(Auth::user()),
            ]);
        } catch (Exception $e) {
            Log::error("Erreur lors show User : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
    }

    public function showForAdmin(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, UserService $userService): JsonResponse
    {
        $validated = $request->validated();
        $success = $userService->update($validated);
        if ($success) {
            return response()->json([
                "success" => $success,
                "data" => new UserResource(Auth::user()),
            ]);
        }
        return response()->json([
            "success" => false,
            "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserService $userService,Request $request)
    {
        $success = $userService->destroy($request);
        dump(Auth::user());
        if ($success) {
            return response()->json([
                'success' => $success,
                'message' => "Suppression réussie",
            ]);
        }
        return response()->json([
            "success" => $success,
            "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
        ], 500);
    }
}
