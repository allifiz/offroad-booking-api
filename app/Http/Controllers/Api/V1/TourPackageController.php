<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TourPackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TourPackageResource;
use App\Models\TourPackage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TourPackageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TourPackageResource::collection(
            TourPackage::query()
                ->where('status', TourPackageStatus::ACTIVE->value)
                ->latest()
                ->paginate(10)
        );
    }

    public function show(TourPackage $tourPackage): TourPackageResource
    {
        abort_unless($tourPackage->status === TourPackageStatus::ACTIVE, 404);

        return new TourPackageResource($tourPackage);
    }
}
