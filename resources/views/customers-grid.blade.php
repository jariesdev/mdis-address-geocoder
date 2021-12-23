@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="card-title">
                <h3>Update Customers</h3>
            </div>
            <div class="py-3">
                <import-grid-customers
                    nominatim-url="{{ config('nominatim.url') }}"
                    :import-id="{{ request()->customerImport }}"></import-grid-customers>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        main.container.content {
            max-width: 100% !important;
        }
    </style>
@endpush
