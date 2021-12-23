<?php

namespace App\Http\Controllers\Api;

use App\Jobs\BatchCustomerCoordinateSearch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function batchUpdate(Request $request)
    {
        $customers = $request->customers;
        foreach ($customers as $customer) {
            Customer::query()
                ->where('id', $customer['id'])
                ->update(Arr::only($customer, [
                    'street',
                    'barangay_name',
                    'municipality_name',
                    'province_name',
                    'region',
                    'island',
                    'latitude',
                    'longitude',
                ]));
        }
        $customerModels = Customer::query()->whereIn('id', collect($customers)->map->id)->get();

        dispatch(new BatchCustomerCoordinateSearch($customerModels, $customerModels->first()->customerImport))->onQueue('default');

        return JsonResource::collection($customerModels)->additional([
            [
                'message' => 'updated',
                'success' => true,
            ]
        ]);
    }
}
