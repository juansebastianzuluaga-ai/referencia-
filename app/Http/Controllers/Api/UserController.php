<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    use FiltersPaginatedResults;
    use PasswordValidationRules;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [
        'user_name',
        'first_name',
        'middle_name',
        'last_name',
        'sur_name',
        'email',
        'identification_number',
    ];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [
        'user_name',
        'first_name',
        'middle_name',
        'last_name',
        'sur_name',
        'email',
        'identification_number',
        'job_title',
        'identification_type_id',
        'is_active',
        'must_change_password',
        'must_update_profile',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'user_name',
        'first_name',
        'last_name',
        'email',
        'created_at',
    ];

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.view'), Response::HTTP_FORBIDDEN);

        $users = $this->filterPaginated(
            query: User::query()->with(['identificationType', 'roles']),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            relationFilters: [
                'roles' => ['relation' => 'roles', 'column' => 'id'],
            ],
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            UserResource::collection($users),
            'Usuarios consultados correctamente',
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = DB::transaction(function () use ($validated, $roles): User {
            $user = User::create($validated);
            $this->syncRoles($user, $roles);

            return $user;
        });

        return $this->sendResponse(
            UserResource::make($user->load(['identificationType', 'roles'])),
            'Usuario creado correctamente',
            Response::HTTP_CREATED,
        );
    }

    public function show(User $user): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('users.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse(UserResource::make($user->load(['identificationType', 'roles'])));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        $user->update($validated);

        if (is_array($roles)) {
            $this->syncRoles($user, $roles);
        }

        return $this->sendResponse(
            UserResource::make($user->load(['identificationType', 'roles'])),
            'Usuario actualizado correctamente',
        );
    }

    public function destroy(User $user): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('users.delete'), Response::HTTP_FORBIDDEN);

        if ($user->user_name === 'superadmin') {
            return $this->sendError('No se puede eliminar al usuario superadmin.', Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return $this->sendResponse(new \stdClass, 'Usuario eliminado correctamente');
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.update'), Response::HTTP_FORBIDDEN);

        if ($user->user_name === 'superadmin') {
            return $this->sendError('No se puede cambiar la contraseña del superadmin desde aquí.', Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return $this->sendResponse(
            UserResource::make($user->load(['identificationType', 'roles'])),
            'Contraseña actualizada correctamente',
        );
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    private function syncRoles(User $user, array $roleNames): void
    {
        $user->syncRoles($roleNames);
    }
}
