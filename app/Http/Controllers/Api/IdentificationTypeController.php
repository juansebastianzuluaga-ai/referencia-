<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\StoreIdentificationTypeRequest;
use App\Http\Requests\UpdateIdentificationTypeRequest;
use App\Http\Resources\IdentificationTypeResource;
use App\Models\IdentificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IdentificationTypeController extends BaseController
{
    use FiltersPaginatedResults;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [
        'code',
        'name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [
        'code',
        'name',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'code',
        'name',
        'created_at',
    ];

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('identification-types.view'), Response::HTTP_FORBIDDEN);

        $identificationTypes = $this->filterPaginated(
            query: IdentificationType::query(),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            IdentificationTypeResource::collection($identificationTypes),
            'Tipos de identificacion consultados correctamente',
        );
    }

    public function store(StoreIdentificationTypeRequest $request): JsonResponse
    {
        return $this->sendResponse(
            IdentificationTypeResource::make(IdentificationType::create($request->validated())),
            'Tipo de identificacion creado correctamente',
            Response::HTTP_CREATED,
        );
    }

    public function show(IdentificationType $identificationType): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('identification-types.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse(IdentificationTypeResource::make($identificationType));
    }

    public function update(UpdateIdentificationTypeRequest $request, IdentificationType $identificationType): JsonResponse
    {
        $identificationType->update($request->validated());

        return $this->sendResponse(IdentificationTypeResource::make($identificationType), 'Tipo de identificacion actualizado correctamente');
    }

    public function destroy(IdentificationType $identificationType): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('identification-types.delete'), Response::HTTP_FORBIDDEN);
        abort_if($identificationType->users()->exists(), Response::HTTP_UNPROCESSABLE_ENTITY);

        $identificationType->delete();

        return $this->sendResponse(new \stdClass, 'Tipo de identificacion eliminado correctamente');
    }
}
