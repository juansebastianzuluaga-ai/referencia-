<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltersPaginatedResults
{
    /**
     * @param  Builder<*>  $query
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $filterable
     * @param  array<string, array{relation: string, column: string}>  $relationFilters
     * @param  array<int, string>  $sortable
     */
    protected function filterPaginated(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $filterable = [],
        array $relationFilters = [],
        array $sortable = [],
        int $defaultPageSize = 10,
        int $maxPageSize = 100,
    ): LengthAwarePaginator {
        $filters = $this->requestFilters($request);
        $pageSize = min($request->integer('per_page', $defaultPageSize), $maxPageSize);

        $this->applyGeneralSearch($query, $filters['general'] ?? null, $searchable);
        $this->applyColumnFilters($query, $filters, $filterable);
        $this->applyRelationFilters($query, $filters, $relationFilters);
        $this->applySorting($query, $request, $sortable);

        return $query->paginate($pageSize);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestFilters(Request $request): array
    {
        return array_filter(
            array_merge($request->except(['page', 'per_page', 'sort_by', 'sort_direction']), $request->input('filters', [])),
            fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<int, string>  $searchable
     */
    private function applyGeneralSearch(Builder $query, mixed $searchTerm, array $searchable): void
    {
        if (blank($searchTerm) || empty($searchable)) {
            return;
        }

        $query->where(function (Builder $query) use ($searchTerm, $searchable): void {
            foreach ($searchable as $column) {
                $query->orWhere($column, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $filterable
     */
    private function applyColumnFilters(Builder $query, array $filters, array $filterable): void
    {
        foreach ($filterable as $column) {
            if (! array_key_exists($column, $filters)) {
                continue;
            }

            $value = $filters[$column];

            if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1') {
                $query->where($column, filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value);

                continue;
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);

                continue;
            }

            $query->where($column, 'LIKE', "%{$value}%");
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, array{relation: string, column: string}>  $relationFilters
     */
    private function applyRelationFilters(Builder $query, array $filters, array $relationFilters): void
    {
        foreach ($relationFilters as $filterKey => $definition) {
            if (! array_key_exists($filterKey, $filters)) {
                continue;
            }

            $values = (array) $filters[$filterKey];

            $query->whereHas($definition['relation'], function (Builder $query) use ($definition, $values): void {
                $query->whereIn($definition['column'], $values);
            });
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<int, string>  $sortable
     */
    private function applySorting(Builder $query, Request $request, array $sortable): void
    {
        $sortBy = $request->string('sort_by')->toString();

        if (! in_array($sortBy, $sortable, true)) {
            $query->latest('id');

            return;
        }

        $direction = $request->string('sort_direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $direction);
    }
}
