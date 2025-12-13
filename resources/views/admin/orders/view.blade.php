@extends('admin.layout.app')
@section('content')
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
                        <span>Order Details</span>

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
                        Order Details </li>
                    <!--end::Item-->


                </ul>
                <!--end::Breadcrumb-->

            </div>
            <!--end::Toolbar container-->

            <!--begin::Actions-->
            <div class="d-flex align-self-center flex-center flex-shrink-0">
                @if($order->fulfillment_status !== 'fulfilled')
    <form action="{{ route('admin.orders.fulfill', $order->id) }}" method="POST" style="display:inline;">
        @csrf
        <button
            type="submit"
            class="btn btn-sm btn-success d-flex flex-center ms-3 px-4 py-3"
            onclick="return confirm('Are you sure you want to fulfill order #{{ $order->order_number }}?');"
        >
            <span>Mark Fulfilled</span>
        </button>
    </form>
@else
    <span class="btn btn-sm btn-light-success d-flex flex-center ms-3 px-4 py-3">
        <i class="ki-outline ki-check fs-5 me-1"></i> Fulfilled Already
    </span>
@endif


                @if($order->financial_status === 'paid')
                <span class="btn btn-sm btn-primary d-flex flex-center ms-3 px-4 py-3">
                    <span><a href="{{ route('admin.create.zoho.order', $order->order_number) }}" class="text-white text-hover-black">Create Zoho Order</span></a>
                </span>
                @else($order->financial_status === 'refunded')
                <span class="btn btn-sm btn-primary d-flex flex-center ms-3 px-4 py-3">
                    <span><a href="{{ route('admin.create.zoho.order', $order->order_number) }}" class="text-white text-hover-black">Create Zoho Order</span></a>
                </span>
                <form action="{{ route('admin.orders.createCreditNote', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary text-white text-hover-black ms-3">Create Credit Note</button>
                </form>
                @endif
                <span class="btn btn-sm btn-info d-flex flex-center ms-3 px-4 py-3">
                    <span><a href="{{ route('admin.download.invoice', $order->id) }}" class="text-white text-hover-black">Packing Slip</span></a>
                </span>
            </div>
            <!--end::Actions-->


        </div>
        <!--end::Toolbar container-->

    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content  flex-column-fluid ">

        @include('utils.show_success')
        @include('utils.show_error')
        <!--begin::Order details page-->
        <div class="d-flex flex-column gap-7 gap-lg-4">
            <div class="d-flex flex-wrap flex-stack gap-5 gap-lg-10">
                <!--begin:::Tabs-->
                <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-lg-n2 me-auto">
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_ecommerce_sales_order_summary">Order Summary</a>
                    </li>
                    <!--end:::Tab item-->
                    <!-- <div class="d-flex gap-2 mt-4">
                        @if($previousOrder)
                        <a href="{{ route('admin.orders.show', $previousOrder->id) }}" class="btn btn-outline-secondary">
                            &larr; Previous Order
                        </a>
                        @endif

                        @if($nextOrder)
                        <a href="{{ route('admin.orders.show', $nextOrder->id) }}" class="btn btn-outline-secondary">
                            Next Order &rarr;
                        </a>
                        @endif
                    </div> -->

                    <!--end:::Tab item-->
                </ul>
                <!--end:::Tabs-->

                <!--begin::Button-->
                <a href="{{ route('admin.orders.list') }}" class="btn btn-icon btn-light btn-active-secondary btn-sm ms-auto me-lg-n7">
                    <i class="ki-outline ki-left fs-2"></i> </a>
                <!--end::Button-->
            </div>
            <!--begin::Order summary-->
            <div class="container-fuild px-0">
                <div class="row ">
                    <!--begin::Order details-->
                    <div class="col-md-4">
                        <div class="card  card-flush py-4 flex-row-fluid">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Order Details (#{{ $order->order_number }})</h2>
                                </div>
                            </div>
                            <!--end::Card header-->

                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <!--begin::Table-->
                                    <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                                        <tbody class="fw-semibold text-gray-600">
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-calendar fs-2 me-2"></i>Order Date
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-end">{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-wallet fs-2 me-2"></i> Payment Status
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-end">
                                                    {{ ucfirst($order->financial_status) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-truck fs-2 me-2"></i> Delivery Date
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-end">{{ $order->delivery_date ?? (@$order->noteAttributes->value ?? '—') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    <!--end::Order details-->

                    <!--begin::Customer details-->
                    <div class="col-md-4">
                        <div class="card card-flush py-4  flex-row-fluid">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Customer Details</h2>
                                </div>
                            </div>
                            <!--end::Card header-->

                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <!--begin::Table-->
                                    <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                                        <tbody class="fw-semibold text-gray-600">
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-profile-circle fs-2 me-2"></i> Customer
                                                    </div>
                                                </td>

                                                <td class="fw-bold text-end">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <!--begin::Name-->
                                                        <a href="#" class="text-gray-600 text-hover-primary">
                                                            {{ ucfirst($order->customer->first_name).' '.ucfirst($order->customer->last_name) }}
                                                        </a>
                                                        <!--end::Name-->
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-sms fs-2 me-2"></i> Email
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-end">
                                                    <a href="#" class="text-gray-600 text-hover-primary">
                                                        {{ $order->customer->email }} </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-outline ki-phone fs-2 me-2"></i> Phone
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-end">{{ @$order->billingAddress->phone }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    <!--end::Customer details-->
                    <!--begin::Documents-->
                    <div class="col-md-4">
                        <div class="card  card-flush py-3 flex-row-fluid">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Fullfill Order</h2>
                                </div>
                            </div>
                            <!--end::Card header-->

                            <!--begin::Card body-->
                            <!-- <div class="card-body pb-3 pt-0"> -->
                            <!-- <div class="">
                                    <form method="POST" action="{{ route('admin.orders.fulfill', $order->shopify_order_id) }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-12 form-group">
                                                <label for="tracking_number">Tracking Number</label>
                                                <input type="text" class="form-control" name="number" required>
                                            </div>
                                            <div class="col-md-12 mt-2 form-group">
                                                <label for="tracking_url">Tracking URL</label>
                                                <input type="text" class="form-control" name="url" required>
                                            </div>
                                            <div class="col-md-12 mt-2 form-group">
                                                <button class="btn btn-primary" type="submit">Fulfill Order</button>
                                            </div>
                                        </div>

                                    </form>
                                </div> -->
                            <!-- </div> -->
                            <div class="card-body pb-3 pt-0">
                                <!-- <form method="POST" action="{{ route('admin.send-template-sms') }}">
                                    @csrf
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                                    <div class="mb-3">
                                        <label for="template-select" class="form-label">Select Template</label>
                                        @php
                                        // Normalize to $tpls for the loop
                                        $tpls = $templates['templates'] ?? $templates ?? [];
                                        @endphp

                                        <select id="template-select" name="template_id" class="form-select" required>
                                            <option value="">Choose a template</option>
                                            @foreach ($tpls as $t)
                                            @php
                                            $id = $t['tempId'] ?? ($t['templateId'] ?? null);
                                            $name = $t['templateName'] ?? ($t['elementName'] ?? ($t['name'] ?? 'Unnamed'));
                                            $lang = $t['language'] ?? ($t['languageCode'] ?? '—');
                                            $cat = $t['category'] ?? ($t['oldCategory'] ?? '—');
                                            $sample = $t['sampleText'] ?? '';
                                            @endphp
                                            @continue(!$id)
                                            <option
                                                value="{{ $id }}"
                                                data-name="{{ $name }}"
                                                data-sample="{{ e(trim($sample)) }}">
                                                {{ $name }} — {{ $lang }} ({{ $cat }})
                                            </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="template_name" id="template_name" value="">

                                        <input type="hidden" name="template_name" id="template_name" value="">
                                        <small id="tpl-preview" class="text-muted d-block mt-2" style="white-space:pre-wrap;"></small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="customer-phone" class="form-label">Customer Phone</label>
                                        <input type="text" id="customer-phone" name="customer_phone" class="form-control"
                                            value="{{ $order->billingAddress->phone }}" readonly>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Send Message</button>
                                </form> -->
                                <form method="POST" action="{{ route('admin.send-template-sms') }}">
                                    @csrf
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                                    <div class="mb-3">
                                        <label for="template-select" class="form-label">Select Template</label>
                                        <select id="template-select" name="template_id" class="form-select" required>
                                            <option value="">Choose a template</option>
                                            @foreach ($tpls as $t)
                                            @php
                                            $id = $t['tempId'] ?? ($t['templateId'] ?? null);
                                            $name = $t['templateName'] ?? ($t['elementName'] ?? ($t['name'] ?? 'Unnamed'));
                                            $lang = $t['language'] ?? ($t['languageCode'] ?? '—');
                                            $cat = $t['category'] ?? ($t['oldCategory'] ?? '—');
                                            $sample = $t['sampleText'] ?? '';
                                            @endphp
                                            @continue(!$id)
                                            <option
                                                value="{{ $id }}"
                                                data-name="{{ $name }}"
                                                data-sample="{{ e(trim($sample)) }}">
                                                {{ $name }} — {{ $lang }} ({{ $cat }})
                                            </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="template_name" id="template_name" value="">
                                        <small id="tpl-preview" class="text-muted d-block mt-2" style="white-space:pre-wrap;"></small>
                                    </div>

                                    {{-- EXTRA FIELDS FOR "out_for_delivery2" --}}
                                    <div id="ofd-extra" class="mb-3" style="display:none;">
                                        <div class="mb-2">
                                            <label class="form-label">Expected delivery date</label>
                                            <input type="text"
                                                name="expected_delivery"
                                                class="form-control"
                                                value="{{ $order->delivery_date ?? ($order->noteAttributes->value ?? '') }}">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Delivery partner name</label>
                                            <input type="text" name="partner_name" class="form-control" placeholder="e.g. Dunzo / Shadowfax">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Delivery partner phone</label>
                                            <input type="text" name="partner_phone" class="form-control" placeholder="10-digit number">
                                        </div>
                                    </div>
                                    {{-- EXTRA FIELD FOR REFUND TEMPLATES --}}
                                    <div id="refund-extra" class="mb-3" style="display:none;">
                                        <label class="form-label">Refund amount (₹)</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="refund_amount"
                                            id="refund_amount"
                                            class="form-control"
                                            value="{{ $order->total_price }}">
                                        <div class="form-text">
                                            Change this if you are refunding a partial amount.
                                        </div>
                                    </div>


                                    <div class="mb-3">
                                        <label for="customer-phone" class="form-label">Customer Phone</label>
                                        <input type="text" id="customer-phone" name="customer_phone" class="form-control"
                                            value="{{ $order->billingAddress->phone }}" readonly>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Send Message</button>
                                </form>

                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    <!--end::Documents-->
                </div>
            </div>
            <!--end::Order summary-->
            {{-- Add this section after order details --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">WhatsApp Message History</h5>
        <a href="{{ route('admin.whatsapp-logs.order', $order->id) }}" class="btn btn-sm btn-primary">
            View All Messages
        </a>
    </div>
    <div class="card-body">
        @if($order->whatsappLogs && $order->whatsappLogs->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Template</th>
                            <th>Type</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Sent By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->whatsappLogs->take(5) as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('d-M-Y H:i') }}</small>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $log->template_name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-{{ $log->message_type === 'automatic' ? 'primary' : 'secondary' }}">
                                        {{ ucfirst($log->message_type) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ str_replace('_', ' ', ucfirst($log->trigger_event ?? 'N/A')) }}</small>
                                </td>
                                <td>{!! $log->status_badge !!}</td>
                                <td>
                                    @if($log->sender)
                                        <small>{{ $log->sender->name }}</small>
                                    @else
                                        <small class="text-muted">System</small>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#logDetail{{ $log->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>

                            {{-- Modal for details --}}
                            <div class="modal fade" id="logDetail{{ $log->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Message Log #{{ $log->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row">
                                                <dt class="col-sm-4">Recipient Phone:</dt>
                                                <dd class="col-sm-8">{{ $log->recipient_phone }}</dd>

                                                <dt class="col-sm-4">Template ID:</dt>
                                                <dd class="col-sm-8"><code>{{ $log->template_id }}</code></dd>

                                                <dt class="col-sm-4">Template Name:</dt>
                                                <dd class="col-sm-8">{{ $log->template_name ?? 'N/A' }}</dd>

                                                <dt class="col-sm-4">Variables Sent:</dt>
                                                <dd class="col-sm-8">
                                                    <pre class="bg-light p-2 small">{{ json_encode($log->template_vars, JSON_PRETTY_PRINT) }}</pre>
                                                </dd>

                                                @if($log->gupshup_message_id)
                                                    <dt class="col-sm-4">Gupshup Message ID:</dt>
                                                    <dd class="col-sm-8"><code>{{ $log->gupshup_message_id }}</code></dd>
                                                @endif

                                                @if($log->error_message)
                                                    <dt class="col-sm-4">Error:</dt>
                                                    <dd class="col-sm-8"><span class="text-danger">{{ $log->error_message }}</span></dd>
                                                @endif

                                                <dt class="col-sm-4">Full Response:</dt>
                                                <dd class="col-sm-8">
                                                    <pre class="bg-light p-2 small" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->response, JSON_PRETTY_PRINT) }}</pre>
                                                </dd>

                                                <dt class="col-sm-4">Sent At:</dt>
                                                <dd class="col-sm-8">{{ $log->created_at->format('d-M-Y H:i:s') }}</dd>
                                            </dl>
                                        </div>
                                        <div class="modal-footer">
                                            @if($log->status === 'failed')
                                            <form action="{{ route('admin.whatsapp-logs.retry', $log->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-redo"></i> Retry Send
                                                </button>
                                            </form>
                                            @endif
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($order->whatsappLogs->count() > 5)
                <div class="text-center mt-2">
                    <a href="{{ route('admin.whatsapp-logs.order', $order->id) }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $order->whatsappLogs->count() }} Messages
                    </a>
                </div>
            @endif
        @else
            <p class="text-muted mb-0">No WhatsApp messages sent for this order yet.</p>
        @endif
    </div>
</div>
            <!--begin::Order summary-->
            <div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">
                <!--begin::Order details-->

                <!--end::Order details-->

                @php
                $displayNote = $order->note ?? $order->gift_message;
                $isGiftMessage = is_null($order->note) && !empty($order->gift_message);
                @endphp

                @if($displayNote)
                <div class="card card-flush py-4 col-4 flex-row-fluid">
                    <!--begin::Card header-->
                    <div class="card-header">
                        <div class="card-title">
                            <h2>{{ $isGiftMessage ? 'Gift Message' : 'Note (If any)' }}</h2>
                        </div>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <div>
                            <b>{{ $displayNote }}</b>
                        </div>
                    </div>
                    <!--end::Card body-->
                </div>
                @endif
            </div>

            <!--end::Order summary-->

            <!--begin::Tab content-->
            <div class="tab-content">
                <!--begin::Tab pane-->
                <div class="tab-pane fade show active" id="kt_ecommerce_sales_order_summary" role="tab-panel">
                    <!--begin::Orders-->
                    <div class="container-fuild px-0">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="card card-flush py-4 flex-row-fluid overflow-hidden">
                                    <!--begin::Card header-->
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h2>Order #{{ @$order->order_number }}</h2>
                                        </div>
                                    </div>
                                    <!--end::Card header-->

                                    <!--begin::Card body-->
                                    <div class="card-body pt-0">
                                        <div class="table-responsive">
                                            <!--begin::Table-->
                                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th class="min-w-175px">Product</th>
                                                        <th class="min-w-100px text-end">SKU</th>
                                                        <th class="min-w-70px text-end">Qty</th>
                                                        <th class="min-w-100px text-end">Unit Price</th>
                                                        <th class="min-w-100px text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="fw-semibold text-gray-600">
                                                    @php
                                                    $total_price = 0;
                                                    @endphp
                                                    @foreach ($order->lineItems as $lineItem)
                                                    @php

                                                    $total_price += $lineItem->price * $lineItem->quantity;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <!--begin::Title-->
                                                                <div class="">
                                                                    <a href="#" class="fw-bold text-gray-600 text-hover-primary">{{ $lineItem->name }}</a>
                                                                    <div class="fs-7 text-muted">Delivery Date: {{ $order->delivery_date ?? (@$order->noteAttributes->value ?? '—') }}</div>
                                                                </div>
                                                                <!--end::Title-->
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $lineItem->sku }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $lineItem->quantity }} x {{ $lineItem->weight }}
                                                        </td>
                                                        <td class="text-end">
                                                            ₹{{ number_format($lineItem->price, 2) }} x {{ $lineItem->quantity }}
                                                        </td>
                                                        <td class="text-end">
                                                            ₹{{ number_format($lineItem->price * $lineItem->quantity, 2) }}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td colspan="3" class="text-end">
                                                            Subtotal
                                                        </td>
                                                        <td class="text-end">
                                                            {{ count($order->lineItems)}} Item
                                                        </td>
                                                        <td class="text-end">
                                                            ₹{{ number_format($total_price) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end">
                                                            Shipping <br>
                                                            <p>{{ @$order->shippingLines->title }}</p>
                                                        </td>
                                                        <td class="text-end">
                                                            ₹{{ number_format($order->total_shipping_price) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="fs-3 text-gray-900 text-end">
                                                            Grand Total
                                                        </td>
                                                        <td class="text-gray-900 fs-3 fw-bolder text-end">
                                                            ₹{{ number_format($total_price + $order->total_shipping_price ) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="fs-3 text-gray-900 text-end">
                                                            Paid
                                                        </td>
                                                        <td class="text-gray-900 fs-3 fw-bolder text-end">
                                                            ₹{{ number_format($order->total_price) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!--end::Table-->
                                        </div>
                                    </div>
                                    <!--end::Card body-->
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex flex-column gap-7 gap-lg-10">
                                    <!--begin::Payment address-->
                                    <div class="card card-flush py-4  flex-row-fluid position-relative">
                                        <!--begin::Background-->
                                        <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                                            <i class="ki-solid ki-two-credit-cart" style="font-size: 14em">
                                            </i>
                                        </div>
                                        <!--end::Background-->

                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>Billing Address</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->

                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            {{ @$order->billingAddress->address1 }} {{ @$order->billingAddress->address2 }},<br />
                                            {{ @$order->billingAddress->city }} , {{ @$order->billingAddress->zip }},<br />
                                            {{ @$order->billingAddress->province }}
                                            {{ @$order->billingAddress->country }} <br>
                                            {{ @$order->billingAddress->phone }}
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Payment address-->
                                    <!--begin::Shipping address-->
                                    <div class="card card-flush py-4  flex-row-fluid position-relative">
                                        <!--begin::Background-->
                                        <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                                            <i class="ki-solid ki-delivery" style="font-size: 13em">
                                            </i>
                                        </div>
                                        <!--end::Background-->

                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>Shipping Address</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->

                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            @if($order->shippingAddress && $order->shippingAddress->address1)
                                            {{ $order->shippingAddress->address1 }} {{ $order->shippingAddress->address2 }},<br />
                                            {{ $order->shippingAddress->city }} , {{ $order->shippingAddress->zip }},<br />
                                            {{ $order->shippingAddress->province }} {{ $order->shippingAddress->country }} <br>
                                            {{ $order->shippingAddress->phone }}
                                            @else
                                            <p>Picked up from the store</p>
                                            @endif
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Shipping address-->
                                </div>
                            </div>

                            <!--begin::Product List-->

                            <!--end::Product List-->
                        </div>
                    </div>
                    <!--end::Orders-->
                </div>
                <!--end::Tab pane-->

            </div>
            <!--end::Tab content-->
        </div>
        <!--end::Order details page-->
    </div>
    <!--end::Content-->

</div>
<!--end::Content wrapper-->
@endsection
@push('scripts')
<script>
    const selectEl = document.getElementById('template-select');
    const previewEl = document.getElementById('tpl-preview');
    const tplNameEl = document.getElementById('template_name');
    const ofdExtra = document.getElementById('ofd-extra');
    const refundExtra = document.getElementById('refund-extra');

    // all template IDs that should show OFD extra fields
    const OUT_FOR_DELIVERY_IDS = [
        '6cf1eaeb-291a-4dfd-b8cf-59a5629edf34',
        '1572786370714481'
    ];

    // ✅ IDs for refund templates (same as in controller `$refundTemplates`)
    const REFUND_TEMPLATE_IDS = [
        '3af48e44-0ec8-472d-b0bc-c87ccc300359',
        '817566097803666',
    ];

    function onTplChange() {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt) return;

        // fill preview + hidden name
        if (previewEl) previewEl.textContent = opt.dataset.sample || '';
        if (tplNameEl) tplNameEl.value = opt.dataset.name || '';

        const selectedId = opt.value;

        // OFD extra
        if (OUT_FOR_DELIVERY_IDS.includes(selectedId)) {
            ofdExtra.style.display = 'block';
        } else {
            ofdExtra.style.display = 'none';
        }

        // Refund extra
        if (REFUND_TEMPLATE_IDS.includes(selectedId)) {
            refundExtra.style.display = 'block';
        } else {
            refundExtra.style.display = 'none';
        }
    }

    // init
    selectEl.addEventListener('change', onTplChange);
    onTplChange();
</script>
@endpush