@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="card-title">
                <h3>New Customer Import</h3>
            </div>
            <div class="card-subtitle">
                Please fill-up the fields, all are required.
            </div>
            <div class="py-3">
                <customer-import-create></customer-import-create>
            </div>
        </div>
    </div>
@endsection
