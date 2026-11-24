<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courier\IndexCourierRequest;
use App\Http\Requests\Courier\StoreCourierRequest;
use App\Http\Requests\Courier\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    /**
     * Daftar kurir dengan pencarian, filter level, pengurutan, dan pagination.
     */
    public function index(IndexCourierRequest $request): AnonymousResourceCollection
    {
        $couriers = Courier::query()
            ->when($request->filled('search'), fn (Builder $query) => $query->searchName($request->validated('search')))
            ->when($request->filled('level'), fn (Builder $query) => $query->levels($request->levels()))
            ->orderBy($request->sortColumn(), $request->sortDirection())
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return CourierResource::collection($couriers);
    }

    /**
     * Tambah kurir. Tanggal pendaftaran default-nya hari ini.
     */
    public function store(StoreCourierRequest $request): CourierResource
    {
        $courier = Courier::create([
            ...$request->validated(),
            'registered_at' => $request->validated('registered_at') ?? today(),
        ]);

        return new CourierResource($courier);
    }

    /**
     * Tampilkan semua data satu kurir.
     */
    public function show(Courier $courier): CourierResource
    {
        return new CourierResource($courier);
    }

    /**
     * Ubah field yang dikirim saja (PUT dan PATCH).
     */
    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return new CourierResource($courier);
    }

    /**
     * Hapus kurir secara permanen.
     */
    public function destroy(Courier $courier): Response
    {
        $courier->delete();

        return response()->noContent();
    }
}
