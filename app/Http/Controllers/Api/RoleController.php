<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RoleController extends BaseController
{
    use FiltersPaginatedResults;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'name',
        'display_name',
        'created_at',
    ];

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('roles.view'), Response::HTTP_FORBIDDEN);

        $roles = $this->filterPaginated(
            query: Role::query()->with('permissions')->withCount('users'),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            relationFilters: [
                'permissions' => ['relation' => 'permissions', 'column' => 'id'],
            ],
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            RoleResource::collection($roles),
            'Roles consultados correctamente',
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $validated['guard_name'] = 'web';

        $role = DB::transaction(function () use ($validated, $permissions): Role {
            $role = Role::create($validated);
            $this->syncPermissions($role, $permissions);

            return $role;
        });

        return $this->sendResponse(RoleResource::make($role->load('permissions')), 'Rol creado correctamente', Response::HTTP_CREATED);
    }

    public function show(Role $role): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('roles.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse(RoleResource::make($role->load('permissions')));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? null;
        unset($validated['permissions']);

        DB::transaction(function () use ($role, $validated, $permissions): void {
            $role->update($validated);

            if (is_array($permissions)) {
                $this->syncPermissions($role, $permissions);
            }
        });

        return $this->sendResponse(RoleResource::make($role->load('permissions')), 'Rol actualizado correctamente');
    }

    public function destroy(Role $role): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('roles.delete'), Response::HTTP_FORBIDDEN);
        abort_if($role->name === 'super-admin', Response::HTTP_UNPROCESSABLE_ENTITY);

        $role->delete();

        return $this->sendResponse(new \stdClass, 'Rol eliminado correctamente');
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    private function syncPermissions(Role $role, array $permissionNames): void
    {
        $role->syncPermissions($permissionNames);
    }
}
