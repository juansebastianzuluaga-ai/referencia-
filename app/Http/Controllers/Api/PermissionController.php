<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PermissionController extends BaseController
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
        abort_unless($request->user()->hasPermission('permissions.view'), Response::HTTP_FORBIDDEN);

        $permissions = $this->filterPaginated(
            query: Permission::query(),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            PermissionResource::collection($permissions),
            'Permisos consultados correctamente',
        );
    }
}
