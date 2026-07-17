<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTourPackageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTourPackageRequest;
use App\Http\Resources\TourPackageResource;
use App\Models\TourPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TourPackageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TourPackageResource::collection(TourPackage::query()->latest()->paginate(10));
    }

    public function store(StoreTourPackageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        $tourPackage = TourPackage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket wisata berhasil dibuat.',
            'data' => new TourPackageResource($tourPackage),
        ], Response::HTTP_CREATED);
    }

    public function show(TourPackage $tourPackage): TourPackageResource
    {
        return new TourPackageResource($tourPackage);
    }

    public function update(UpdateTourPackageRequest $request, TourPackage $tourPackage): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('slug', $data) && blank($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $tourPackage->name).'-'.Str::lower(Str::random(5));
        }

        $tourPackage->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket wisata berhasil diperbarui.',
            'data' => new TourPackageResource($tourPackage->refresh()),
        ]);
    }

    public function destroy(TourPackage $tourPackage): JsonResponse
    {
        $tourPackage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket wisata berhasil dihapus.',
        ]);
    }
}
