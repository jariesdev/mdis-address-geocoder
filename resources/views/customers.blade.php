@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="card-title">
                <h3>Customers</h3>
            </div>
            <div class="py-3">
                <import-customers :import-id="{{ request()->customerImport }}"></import-customers>
            </div>
        </div>
    </div>
@endsection
