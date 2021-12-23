@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="card-title d-flex align-items-center">
                <h3>Customers</h3>
                <a href="{{ route('imports.customers-grid.index', request()->customerImport) }}" class="ms-auto">Grid View</a>
            </div>
            <div class="py-3">
                <import-customers nominatim-url="{{ config('nominatim.url') }}" :import-id="{{ request()->customerImport }}"></import-customers>
            </div>
        </div>
    </div>
@endsection
