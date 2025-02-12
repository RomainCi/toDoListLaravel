<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserProfil\StoreRequest;
use App\Http\Requests\UserProfil\UpdateRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\UserProfilResource;
use App\Models\User;
use App\Service\ErrorService;
use App\Service\ProfilService;
use App\Service\S3Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class UserProfilController extends Controller
{
    protected $errorService;
    protected $profilService;
    protected $s3Service;

    public function __construct(ErrorService $errorService, ProfilService $profilService, S3Service $s3Service)
    {
        $this->errorService = $errorService;
        $this->profilService = $profilService;
        $this->s3Service = $s3Service;
    }

    public function index(): JsonResponse
    {
        try {
            $path = $this->profilService->index();
            $success = true;
            $url = $path ? $this->s3Service->getUrl($path) : null;
            $message = $url ? "Un lien a été généré." : "Aucune image n'a été trouvée.";
        } catch (Exception $e) {
            Log::error("Erreur dans UserProfilController Index : " . $e->getMessage(), ['exception' => $e]);
            $success = false;
            $message = $this->errorService->message();
            $url = null;
        }
        return (new UserProfilResource(compact('url', 'message', 'success')))->response()->setStatusCode($success ? 200 : 500);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $request->validated();
        $image = $request->file('picture');
        try {
            DB::beginTransaction();
            $user = User::find(Auth::id());
            $path = $this->s3Service->put($image, $user,"photoProfil");
            $this->profilService->store($path, $user);
            DB::commit();
            $success = true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans UserProfilController Store" . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Votre image de profil a bien été uploadée" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function update(UpdateRequest $request):JsonResponse
    {
        $request->validated();
        $image = $request->file('picture');
        try {
            DB::beginTransaction();
            $user = User::with('profil')->find(Auth::id());
            if (!$user->profil) {
                return (new BaseResource([
                    'success' => true,
                    'message' => 'Aucune image de profil à modifier.',
                ]))->response()->setStatusCode(404);
            }
            $this->s3Service->destroy($user->profil->picture);
            $path = $this->s3Service->put($image, $user,"photoProfil");
            $this->profilService->update($path, $user);
            $success = true;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans UserProfilController Update" . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Votre image de profil a bien été modifié" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function destroy(): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = User::with('profil')->find(Auth::id());
            if (!$user->profil) {
                return (new BaseResource([
                    'success' => true,
                    'message' => 'Aucune image de profil à supprimer.',
                ]))->response()->setStatusCode(400);
            }
            $this->s3Service->destroy($user->profil->picture);
            $this->profilService->destroy($user->profil);
            DB::commit();
            $success = true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans UserProfilController Store" . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Votre image de profil a bien été supprimé" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }
}
