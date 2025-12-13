@extends('admin.layout.app')
@section('content')
<style>
    .clickable-row {
        cursor: pointer;
    }

    .clickable-row:hover {
        background-color: #f5f5f5;
    }

    /* Sticky Date Header Styles */
    .sticky-date-header {
        position: fixed;
        top: 70px; /* Adjust based on your navbar height */
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 24px;
        font-weight: 600;
        font-size: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        border-radius: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        min-width: 300px;
        justify-content: center;
    }

    .sticky-date-header .date-icon {
        font-size: 20px;
    }

    .sticky-date-header .date-text {
        font-size: 18px;
        letter-spacing: 0.5px;
    }

    .date-group-row {
        background: #f8f9fa !important;
        border-top: 2px solid #e9ecef !important;
        border-bottom: 2px solid #e9ecef !important;
        font-weight: 600;
        position: relative;
    }

    .date-group-row td {
        padding: 12px 16px !important;
        font-size: 15px;
    }

    /* Table wrapper for scroll detection */
    .table-scroll-wrapper {
        position: relative;
    }

    /* Animation for date change */
    @keyframes dateChange {
        0% {
            transform: translateY(-10px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .sticky-date-header.date-changed {
        animation: dateChange 0.3s ease;
    }

    /* Card body adjustment for sticky header */
    .card-body.pt-0 {
        position: relative;
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
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Toolbar container-->
    </div>
    <!--end::Toolbar-->

    <!-- Sticky Date Header - Fixed to viewport -->
    <div class="sticky-date-header" id="stickyDateHeader" style="display: none;">
        <span class="date-icon">📅</span>
        <span class="date-text" id="currentDateDisplay">—</span>
    </div>

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
            <div class="card-body pt-0 table-responsive table-scroll-wrapper">

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
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @if($orders && $orders->count())
                        @foreach ($orders as $item)
                        
                        @if(is_array($item) && isset($item['__group']))
                        {{-- This is a date group header --}}
                        @php
                        $groupKey = $item['__group'];
                        $display = $groupKey ? $groupKey : '—';
                        @endphp
                        <tr class="date-group-row" data-delivery-date="{{ $display }}">
                            <td colspan="8">
                                <i class="ki-outline ki-calendar fs-3 me-2"></i>
                                Delivery Date: <strong>{{ $display }}</strong>
                            </td>
                        </tr>
                        
                        @else
                        {{-- This is an actual order --}}
                        <tr class="clickable-row" data-href="{{ route('admin.orders.show', $item->id) }}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <a href="{{ route('admin.orders.show', $item->id) }}" class="text-gray-800 text-hover-primary fs-5 fw-bold">
                                            #{{ $item->order_number }}
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <td class="pe-0">
                                <span class="fw-bold">
                                    {{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d-m-Y H:i') : '—' }}
                                </span>
                            </td>

                            <td class="pe-0">
                                @php
                                $raw = trim($item->delivery_date ?? '');
                                $displayDelivery = '—';
                                try {
                                    if($raw !== '') {
                                        $dt = \Carbon\Carbon::parse($raw);
                                        $displayDelivery = $dt->format('d/m/Y');
                                    }
                                } catch (\Exception $e) {
                                    if($raw !== '') $displayDelivery = $raw;
                                }
                                @endphp
                                <span class="fw-bold ms-3">{{ $displayDelivery }}</span>
                            </td>

                            <td class="pe-0">
                                @if($item->customer)
                                {{ $item->customer->first_name ?? '' }} {{ $item->customer->last_name ?? '' }}
                                @else
                                —
                                @endif
                            </td>

                            <td class="text-center">
                                @php $giftOrNote = $item->gift_message ?? $item->note; @endphp
                                @if($giftOrNote)
                                <a href="javascript:void(0);" class="view-gift-message" data-gift="{{ $giftOrNote }}">
                                    <i class="ki-outline ki-gift fs-2 text-success"></i>
                                </a>
                                @endif
                            </td>

                            <td class="pe-0">
                                <div class="badge badge-light-success">
                                    {{ ucfirst($item->financial_status) }}
                                </div>
                            </td>

                            <td class="pe-0">
                                <div class="badge badge-light-success">
                                    {{ $item->total_price }}
                                </div>
                            </td>

                            <td>
                                @if(!empty($item->shippingAddress))
                                {{ $item->shippingAddress->address1 }}, {{ $item->shippingAddress->city }}, {{ $item->shippingAddress->province }}
                                @elseif(!empty($item->billingAddress))
                                {{ $item->billingAddress->address1 }}, {{ $item->billingAddress->city }}, {{ $item->billingAddress->province }}
                                @else
                                —
                                @endif
                            </td>
                        </tr>
                        @endif
                        
                        @endforeach

                        {{-- pagination --}}
                        <tr>
                            <td colspan="8">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="d-flex flex-wrap py-2 mr-3">
                                        {{ $orders->links('vendor.pagination.bootstrap-4') }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="8" class="text-center">No orders found</td>
                        </tr>
                        @endif
                    </tbody>

                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Products-->
    </div>
    <!--end::Content-->

</div>

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

        // Sticky Date Header Logic
        const stickyHeader = document.getElementById('stickyDateHeader');
        const dateDisplay = document.getElementById('currentDateDisplay');
        const dateGroupRows = document.querySelectorAll('.date-group-row');
        
        let currentDate = '';
        let ticking = false;

        function updateStickyHeader() {
            if (dateGroupRows.length === 0) return;

            // Get the sticky header position (top of viewport + sticky offset)
            const triggerPoint = 100; // Distance from top of viewport
            
            let newDate = '';
            let closestRow = null;
            let closestDistance = Infinity;
            
            // Find the date group row that's currently at or above the trigger point
            dateGroupRows.forEach(function(row) {
                const rowRect = row.getBoundingClientRect();
                const rowTop = rowRect.top;
                
                // If row is above or at trigger point, it's a candidate
                if (rowTop <= triggerPoint) {
                    const distance = triggerPoint - rowTop;
                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closestRow = row;
                    }
                }
            });

            // Update the sticky header with the closest date
            if (closestRow) {
                newDate = closestRow.getAttribute('data-delivery-date');
                
                if (newDate && newDate !== currentDate) {
                    currentDate = newDate;
                    dateDisplay.textContent = 'Delivery Date: ' + currentDate;
                    stickyHeader.style.display = 'flex';
                    
                    // Add animation class
                    stickyHeader.classList.add('date-changed');
                    setTimeout(() => {
                        stickyHeader.classList.remove('date-changed');
                    }, 300);
                }
            } else {
                // No date row above trigger point, hide sticky header
                if (currentDate !== '') {
                    stickyHeader.style.display = 'none';
                    currentDate = '';
                }
            }

            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                window.requestAnimationFrame(updateStickyHeader);
                ticking = true;
            }
        }

        // Listen to window scroll (not table scroll)
        window.addEventListener('scroll', requestTick);
        
        // Also check on table scroll if it exists
        const tableWrapper = document.querySelector('.table-scroll-wrapper');
        if (tableWrapper) {
            tableWrapper.addEventListener('scroll', requestTick);
        }
        
        // Initial check
        setTimeout(updateStickyHeader, 100);
    });
</script>