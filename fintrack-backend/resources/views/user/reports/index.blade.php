@extends('layouts.user')

@section('title', 'Reports')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Financial Reports</h2>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Generate Reports</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#monthlyReport">
                                <i class="fas fa-calendar"></i> Monthly Report
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#categoryReport">
                                <i class="fas fa-chart-pie"></i> Category Report
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#yearlyReport">
                                <i class="fas fa-chart-bar"></i> Yearly Report
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary w-100">
                                <i class="fas fa-download"></i> Export as PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spending Overview -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Monthly Spending Trend</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">Chart will appear here</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Spending by Category</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">Chart will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
