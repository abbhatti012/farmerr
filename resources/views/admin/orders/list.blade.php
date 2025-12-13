@extends('admin.layout.app')
@section('content')
<style>
    .clickable-row {
        cursor: pointer;
    }

    .clickable-row:hover {
        background-color: #f5f5f5;
        /* Optional hover effect */
    }
</style>
<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">

    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

        <!--begin::Toolbar container-->
        <div class="d-flex flex-stack flex-row-fluid">
            <!--begin::Toolbar container-->
            <div class="d-flex flex-column flex-row-fluid">
                <!--begin::Toolbar wrapper-->

                <!--begin::Page title-->
                <div class="page-title d-flex align-items-center me-3">
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                        <span>Orders</span>

                    </h1>
                    <!--end::Title-->
                </div>
                <!--end::Page title-->


                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-3 fs-7">

                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        <a href="#" class="text-white text-hover-primary">
                            <i class="ki-outline ki-home text-gray-700 fs-6"></i> </a>
                    </li>
                    <!--end::Item-->

                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
                    </li>
                    <!--end::Item-->


                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        Dashboard </li>
                    <!--end::Item-->

                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
                    </li>
                    <!--end::Item-->


                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        Orders </li>
                    <!--end::Item-->

                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
                    </li>
                    <!--end::Item-->


                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-700">
                        Orders List </li>
                    <!--end::Item-->


                </ul>
                <!--end::Breadcrumb-->

            </div>
            <!--end::Toolbar container-->

            <!--begin::Actions-->
            <div class="d-flex align-self-center flex-center flex-shrink-0">

                <a href="{{ url('admin/orders_update') }}" class="btn btn-sm btn-dark ms-3 px-4 py-3">
                    Update <span class="d-none d-sm-inline">Order</span>
                </a>
                <!-- New button to trigger Zoho order creation -->
                <!-- <form action="{{ route('admin.orders.create.zoho') }}" method="POST" class="ms-3">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary px-4 py-3">
            Create Zoho Orders
        </button>
    </form> -->
                <!-- Send SMS Button with POST Request -->
                <!-- <form action="{{ url('admin/send-template-sms') }}" method="POST" class="ms-3">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary px-4 py-3">
            Send <span class="d-none d-sm-inline">SMS</span>
        </button>
    </form> -->
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Toolbar container-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content  flex-column-fluid ">

        <!--begin::Products-->
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">

                <div class="d-flex">
                    <div
                        class="border border-gray-300 border-dashed rounded min-w-200px w-200 py-2 px-4 me-6">
                        <span class="fs-8 text-gray-500 fw-bold">Today's Orders</span>
                        <div class="fs-2 fw-bold text-success">{{ $totalOrdersToday }}</div>
                    </div>
                    <div
                        class="border border-gray-300 border-dashed rounded min-w-200px w-200 py-2 px-4 ">
                        <span class="fs-8 text-gray-500 fw-bold">Total Amount</span>

                        <div class="fs-2 fw-bold text-success">₹{{ number_format($totalAmountToday, 2) }}</div>
                    </div>

                </div>
                <!--begin::Card title-->
                <form method="GET" class="d-flex flex-wrap justify-content-end align-items-center py-3 px-2 gap-3" action="{{ route('admin.orders.list') }}">
                    <div class="card-title">
                        <select name="payment_status" class="form-select w-auto">
                            <option value="">All Statuses</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            <option value="partially_refunded" {{ request('payment_status') == 'partially_refunded' ? 'selected' : '' }}>Partially Refunded</option>
                        </select>

                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                            <input type="text" class="form-control form-control-solid w-250px ps-12" name="search" value="{{ request('search') }}" placeholder="Search Order" />
                        </div>
                        <!--end::Search-->
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                </form>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-0 table-responsive">

                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="orders_table">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-">Order</th>
                            <th class=" min-w-">Date</th>
                            <th class=" min-w-">Delivery Date</th>
                            <th class=" min-w-">Customer</th>
                            <th class="min-w-">Notes</th>
                            <th class=" min-w-">Payment Status</th>
                            <th class=" min-w-">Amount</th>
                            <th class=" min-w-">Destination</th>
                            <!-- <th class=" min-w-">Tags</th> -->
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @if($orders)
                        @foreach ($orders as $val)
                        <tr class="clickable-row" data-href="{{ route('admin.orders.show', $val->id) }}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="">
                                        <a href="{{ route('admin.orders.show', $val->id) }}" class="text-gray-800 text-hover-primary fs-5 fw-bold">
                                            #{{ $val->order_number }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="pe-0">
                                <span class="fw-bold">
                                    {{ \Carbon\Carbon::parse($val->order_date)->format('d-m-Y H:i') }}
                                </span>
                            </td>
                            <td class="pe-0">
                                <span class="fw-bold ms-3">
                                    {{ $val->delivery_date ?? (@$val->noteAttributes->value ?? '—') }}
                                </span>
                            </td>

                            <td class="pe-0">
                                {{$val->customer->first_name .' '. $val->customer->last_name}}
                            </td>
                            <td class="text-center">
                                @php
                                $giftOrNote = $val->gift_message ?? $val->note;
                                @endphp

                                @if($giftOrNote)
                                <a href="javascript:void(0);" class="view-gift-message" data-gift="{{ $giftOrNote }}">
                                    <i class="ki-outline ki-gift fs-2 text-success" title="View Gift Message / Note">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                                @endif
                            </td>


                            <td class="pe-0">
                                <div class="badge badge-light-success">
                                    {{ ucfirst($val->financial_status) }}
                                </div>
                            </td>
                            <td class="pe-0">
                                <div class="badge badge-light-success">
                                    {{ $val->total_price }}
                                </div>
                            </td>
                            <td>
                                @if(!empty($val->shippingAddress))
                                {{ $val->shippingAddress->address1 }}, {{ $val->shippingAddress->city }}, {{ $val->shippingAddress->province }}
                                @else
                                {{ $val->billingAddress->address1 }}, {{ $val->billingAddress->city }}, {{ $val->billingAddress->province }}
                                @endif
                            </td>
                            <!-- <td>{{$val->tags}}</td> -->
                        </tr>
                        @endforeach
                    </tbody>

                </table>
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        {{ $orders->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
                @endif
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Products-->
    </div>
    <!--end::Content-->

</div>
<!-- Note Modal -->
<!-- Gift Message / Note Modal -->
<div class="modal fade" id="giftMessageModal" tabindex="-1" aria-labelledby="giftMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="giftMessageModalLabel">Gift Message / Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="giftMessageContent" class="mb-0"></p>
            </div>
        </div>
    </div>
</div>


<!--end::Content wrapper-->
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.clickable-row').forEach(function(row) {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Row click navigation
        document.querySelectorAll('.clickable-row').forEach(function(row) {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });

        // Unified Gift/Note modal
        document.querySelectorAll('.view-gift-message').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.stopPropagation();
                const message = this.getAttribute('data-gift') || '—';
                document.getElementById('giftMessageContent').innerText = message;
                const modal = new bootstrap.Modal(document.getElementById('giftMessageModal'));
                modal.show();
            });
        });
    });
</script>
