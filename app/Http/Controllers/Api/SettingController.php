<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SettingController extends BaseController
{
    public function index(): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('settings.view'), Response::HTTP_FORBIDDEN);

        $settings = Setting::all()->groupBy('group');

        $grouped = $settings->mapWithKeys(fn ($items, $group) => [
            $group => SettingResource::collection($items),
        ]);

        return $this->sendResponse($grouped, 'Configuraciones consultadas correctamente');
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            foreach ($validated['settings'] as $item) {
                $setting = Setting::where('key', $item['key'])->first();

                if ($setting) {
                    $setting->update([
                        'value' => Setting::encodeValue($item['value'], $setting->type),
                    ]);
                }
            }
        });

        $settings = Setting::all()->groupBy('group');

        $grouped = $settings->mapWithKeys(fn ($items, $group) => [
            $group => SettingResource::collection($items),
        ]);

        return $this->sendResponse($grouped, 'Configuraciones actualizadas correctamente');
    }
}
