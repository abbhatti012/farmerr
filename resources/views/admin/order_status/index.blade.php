@extends('admin.layout.app')
@section('content')
<style>
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background-color: #f5f5f5;
    }
</style>

<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar d-flex pb-3 pb-lg-5">
        <div class="d-flex flex-stack flex-row-fluid">
            <div class="d-flex flex-column flex-row-fluid">
                <!-- Breadcrumbs -->
                <div class="page-title d-flex align-items-center me-3">
                    <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                        <span>Order Status Management</span>
                    </h1>
                </div>

                <!-- Breadcrumb Navigation -->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-3 fs-7">
                    <li class="breadcrumb-item text-gray-700 fw-bold">
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-700 text-hover-primary">
                            <i class="ki-outline ki-home text-gray-700 fs-6"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-gray-700 fw-bold">Dashboard</li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-gray-700 fw-bold">Order Status</li>
                </ul>
            </div>

            <!-- Action Button -->
            <!-- <div class="d-flex align-self-center flex-center flex-shrink-0">
                <a href="{{ url('admin/orders_update') }}" class="btn btn-sm btn-dark ms-3 px-4 py-3">
                    Update Order
                </a>
            </div> -->
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div class="row">
            <!-- Left Column: Add New Order Status Form -->
            <div class="col-4">
                <div class="card card-flush py-4 mb-5">
                    <div class="card-header">
                        <h2>Add Order Status</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.order_status.store') }}" method="POST">
                            @csrf
                            <div class="mb-5">
                                <label class="form-label">Status Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter status name" required>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Status List Table -->
            <div class="col-8">
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                        <!-- Search Form -->
                        <form method="GET" class="d-flex justify-content-end align-items-center" action="{{ route('admin.order_status.index') }}">
                            <div class="card-title">
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                                    <input type="text" class="form-control form-control-solid w-250px ps-12" name="search" value="{{ request('search') }}" placeholder="Search Status" />
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary ms-3">Search</button>
                        </form>
                    </div>

                    <div class="card-body pt-0 table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="status_table">
                            <thead>
                                <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                                    <th>#</th>
                                    <th>Status Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statuses as $status)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $status->name }}</td>
                                    <td>{{ $status->description }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $status->status == '1' ? 'success' : 'danger' }}">
                                            {{ $status->status == '1' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-light-primary edit-btn" data-id="{{ $status->id }}">Edit</button>
                                        <form action="{{ route('admin.order_status.destroy', $status->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Collapsible Edit Form -->
                                <tr id="edit-form-row-{{ $status->id }}" class="edit-form-row" style="display: none;">
                                    <td colspan="5">
                                        <form action="{{ route('admin.order_status.update', $status->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row">
                                                <div class="col-4">
                                                    <label>Status Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $status->name }}" required>
                                                </div>
                                                <div class="col-4">
                                                    <label>Description</label>
                                                    <input type="text" name="description" class="form-control" value="{{ $status->description }}">
                                                </div>
                                                <div class="col-2">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control">
                                                        <option value="1" {{ $status->status == '1' ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ $status->status == '0' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="col-2 pt-4">
                                                    <button type="submit" class="btn btn-sm btn-success">Update</button>
                                                    <button type="button" class="btn btn-sm btn-secondary cancel-edit-btn" data-id="{{ $status->id }}">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.edit-btn').on('click', function() {
            var statusId = $(this).data('id');
            $('.edit-form-row').hide();
            $('#edit-form-row-' + statusId).toggle();
        });

        $('.cancel-edit-btn').on('click', function() {
            var statusId = $(this).data('id');
            $('#edit-form-row-' + statusId).hide();
        });
    });
</script>
@endpush
@endsection
