@extends('admin.layout.app')
@section('content')
<style>
    .font_set {
        font-size: 1.6rem !important;
    }
</style>

<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
<!-- <div class="d-flex flex-column flex-column-fluid">
    <h1 class="font_set">Update Product SKU</h1>

    <form id="skuUpdateForm" method="POST" action="{{ route('admin.products.update.sku', ['productId' => 7144103411911]) }}">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label for="variant_id">Variant ID</label>
            <input type="text" class="form-control" id="variant_id" name="variant_id" value="7144103411911" placeholder="Enter Variant ID" required>
        </div>

        <div class="form-group mb-3">
            <label for="sku">SKU</label>
            <input type="text" class="form-control" id="sku" name="sku" value="DB00002222" placeholder="Enter SKU" required>
        </div>

        <button type="submit" class="btn btn-primary btn-update-sku">Update SKU</button>
    </form>
</div> -->

<!-- <div>
    <h1>Active Visitors: <span id="active-visitors">0</span></h1>
    <h2>Page Views: <span id="page-views">0</span></h2>
</div> -->
    <!--begin::Toolbar-->
    {{-- <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

        <!--begin::Toolbar container-->
        <div class="d-flex flex-stack flex-row-fluid">
            <!--begin::Toolbar container-->
            <div class="d-flex flex-column flex-row-fluid">
                <!--begin::Toolbar wrapper-->

                <!--begin::Page title-->
                <div class="page-title d-flex align-items-center me-3">
                    <!--begin::Title-->
                    <h1
                        class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                        <span>
                            <span class="fw-light">
                                Welcome back
                            </span>,&nbsp;</span>

                        <!--begin::Description-->
                        <span class="page-desc text-gray-600 fs-base fw-semibold">
                            You are logged in as a
                            Farmerr Owner </span>
                        <!--end::Description-->
                    </h1>
                    <!--end::Title-->
                </div>
                <!--end::Page title-->

            </div>
            <!--end::Toolbar container-->

            <!--begin::Actions-->
            <div class="d-flex align-self-center flex-center flex-shrink-0">
                <a href="#" class="btn btn-sm btn-success d-flex flex-center ms-3 px-4 py-3"
                    data-bs-toggle="modal" data-bs-target="#kt_modal_invite_friends">
                    <i class="ki-outline ki-plus-square fs-2"></i>
                    <span>Invite</span>
                </a>

                <a href="#" class="btn btn-sm btn-dark ms-3 px-4 py-3" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_new_target">
                    Create <span class="d-none d-sm-inline">Target</span>
                </a>
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Toolbar container-->
    </div> --}}
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content  flex-column-fluid ">

        <!--begin::Row-->
        <div class="row g-5 g-xl-10 mb-5 mb-xl-0">
            <!--begin::Col-->
            <div class="col-md-3 mb-xl-10">
                <!--begin::Card widget 28-->
                <div class="card card-flush ">
                    <!--begin::Header-->
                    <div class="card-header pt-7">
                        <!--begin::Card title-->
                        <div class="card-title flex-stack flex-row-fluid">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-45px me-5">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-outline ki-basket-ok fs-2x text-gray-800"></i>
                                </span>
                            </div>
                            <!--end::Symbol-->

                            <!--begin::Wrapper-->
                            <!-- <div class="me-n2">
                                <span
                                    class="badge badge-light-success align-self-center fs-base">
                                    <i
                                        class="ki-outline ki-arrow-up fs-5 text-success ms-n1"></i>
                                    2.2%
                                </span>

                            </div> -->
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Header-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card body-->
                    <div class="card-body d-flex align-items-end">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column">
                            <span class="fw-bolder fs-2x font_set text-gray-900">{{ @$totalOrdersToday }}</span>
                            <span class="fw-bold fs-7 text-gray-500">
                                Today's Order
                            </span>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card widget 28-->
            </div>
            <!--end::Col-->

            <!--begin::Col-->
            <div class="col-md-3 mb-xl-10">
                <!--begin::Card widget 28-->
                <div class="card card-flush ">
                    <!--begin::Header-->
                    <div class="card-header pt-7">
                        <!--begin::Card title-->
                        <div class="card-title flex-stack flex-row-fluid">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-45px me-5">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-outline ki-cheque fs-2x text-gray-800"></i>
                                </span>
                            </div>
                            <!--end::Symbol-->

                            <!--begin::Wrapper-->
                            <!-- <div class="me-n2">
                                <span
                                    class="badge badge-light-success align-self-center fs-base">
                                    <i
                                        class="ki-outline ki-arrow-up fs-5 text-success ms-n1"></i>
                                    2.2%
                                </span>

                            </div> -->
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Header-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card body-->
                    <div class="card-body d-flex align-items-end">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column">
                            <!-- text-gray-900  -->
                            <span class="fw-bolder fs-2x font_set text-success">&#x20b9;{{ @$totalAmountToday }}</span>
                            <span class="fw-bold fs-7 text-gray-500">
                                Total Amount
                            </span>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card widget 28-->
            </div>
            <!--end::Col-->

            @foreach ($salesData as $data)
            <!--begin::Col-->
            <div class="col-md-2 mb-xl-10">
                <!--begin::Card widget 28-->
                <div class="card card-flush ">
                    <!--begin::Header-->
                    <div class="card-header pt-7">
                        <!--begin::Card title-->
                        <div class="card-title flex-stack flex-row-fluid">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-45px me-5">
                                <span class=" bg-light-info"> <!-- symbol-label -->
                                    <!-- <i class="ki-outline ki-instagram fs-2x text-gray-800"></i> -->
                                    {{ $data->state }}
                                </span>
                            </div>
                            <!--end::Symbol-->

                            <!--begin::Wrapper-->
                            <!-- <div class="me-n2">
                                <span
                                    class="badge badge-light-success align-self-center fs-base">
                                    <i
                                        class="ki-outline ki-arrow-up fs-5 text-success ms-n1"></i>
                                    2.2%
                                </span>

                            </div> -->
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Header-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card body-->
                    <div class="card-body d-flex align-items-end">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column">
                            <span class="fw-bolder fs-2x font_set text-gray-900">&#x20b9;{{ number_format($data->total_sales, 2) }}</span>
                            <span class="fw-bold fs-7 text-gray-500">
                            Orders: {{ $data->total_orders }}
                        </span>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card widget 28-->
            </div>
            <!--end::Col-->
            @endforeach

        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
            <!--begin::Col-->
            <div class="col-xl-6">

                <!--begin::List widget 23-->
                <div class="card card-flush h-xl-100">
                    <!--begin::Header-->
                    <div class="card-header pt-7">
                        <!--begin::Title-->
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">
                                Recent Orders
                            </span>
                            <!-- <span class="text-gray-500 mt-1 fw-semibold fs-6">
                                Hey
                            </span> -->
                        </h3>
                        <!--end::Title-->

                        <!--begin::Toolbar-->
                        <div class="card-toolbar">

                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Header-->

                    <!--begin::Body-->
                    <div class="card-body pt-5">
                        <!--begin::Items-->
                        <div class>

                            @foreach($recentOrders as $order)

                            <!--begin::Item-->
                            <div class="d-flex flex-stack">
                                <!--begin::Section-->
                                <div class="d-flex align-items-center me-5">

                                    <!--begin::Content-->
                                    <div class="me-5">
                                        <!--begin::Title-->
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-gray-800 fw-bold text-hover-primary fs-6">
                                            #{{ $order->order_number }}
                                        </a>
                                        <!--end::Title-->

                                        <!--begin::Desc-->
                                        <span
                                            class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">{{$order->customer->first_name .' '. $order->customer->last_name}}</span>
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Content-->
                                </div>
                                <!--end::Section-->

                                <!--begin::Wrapper-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Number-->
                                    <span class="text-gray-800 fw-bold fs-4 me-3">{{ ucfirst($order->financial_status) }}</span>
                                    <!--end::Number-->

                                    <!--begin::Info-->
                                    <div class="m-0">
                                        <!--begin::Label-->
                                        <div class="badge badge-light-success">
                                        &#x20b9;{{ $order->total_price }}
                                        </div>
                                        <!--end::Label-->

                                    </div>
                                    <!--end::Info-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Item-->

                            <!--begin::Separator-->
                            <div class="separator separator-dashed my-3"></div>
                            <!--end::Separator-->

                            @endforeach

                        </div>
                        <!--end::Items-->
                    </div>
                    <!--end: Card Body-->
                </div>
                <!--end::List widget 23-->
            </div>
            <!--end::Col-->

            <!--begin::Col-->
            <div class="col-xl-6">

                <!--begin::Chart Widget 33-->
                <div class="card card-flush h-xl-100">
                    <!--begin::Header-->
                    <div class="card-header pt-5 mb-6">
                        <!--begin::Title-->
                        <h3 class="card-title align-items-start flex-column">
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Currency-->
                                <!-- <span class="fs-3 fw-semibold text-gray-500 align-self-start me-1">&#x20b9;</span> -->
                                <!--end::Currency-->

                                <!--begin::Value-->
                                <span id="sale-amount-value" class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">&#x20b9;{{ $salesAmounts['oneD'] }}</span>
                                <!--end::Value-->

                                <!--begin::Label-->
                                <!-- <span class="badge badge-light-success fs-base">
                                    <i
                                        class="ki-outline ki-arrow-up fs-5 text-success ms-n1"></i>
                                    9.2%
                                </span> -->
                                <!--end::Label-->
                            </div>
                            <!--end::Statistics-->

                            <!--begin::Description-->
                            <span class="fs-6 fw-semibold text-gray-500">
                                Total Sale Amount
                            </span>
                            <!--end::Description-->
                        </h3>
                        <!--end::Title-->

                    </div>
                    <!--end::Header-->

                    <!--begin::Body-->
                    <div class="card-body py-0 px-0">
                        <!--begin::Nav-->
                        <ul class="nav d-flex justify-content-between mb-3 mx-9">
                            <!--begin::Item-->
                            <li class="nav-item mb-3">
                                <!--begin::Link-->
                                <a class="nav-link btn btn-flex flex-center btn-active-danger btn-color-gray-600 btn-active-color-white rounded-2 w-45px h-35px active"
                                    data-bs-toggle="tab" id="kt_charts_widget_33_tab_1"
                                    href="#kt_charts_widget_33_tab_content_1">
                                    1d
                                </a>
                                <!--end::Link-->
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="nav-item mb-3">
                                <!--begin::Link-->
                                <a class="nav-link btn btn-flex flex-center btn-active-danger btn-color-gray-600 btn-active-color-white rounded-2 w-45px h-35px "
                                    data-bs-toggle="tab" id="kt_charts_widget_33_tab_2"
                                    href="#kt_charts_widget_33_tab_content_2">
                                    5d
                                </a>
                                <!--end::Link-->
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="nav-item mb-3">
                                <!--begin::Link-->
                                <a class="nav-link btn btn-flex flex-center btn-active-danger btn-color-gray-600 btn-active-color-white rounded-2 w-45px h-35px "
                                    data-bs-toggle="tab" id="kt_charts_widget_33_tab_3" href="#kt_charts_widget_33_tab_content_3">
                                    1m
                                </a>
                                <!-- end::Link -->
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="nav-item mb-3">
                                <!--begin::Link-->
                                <a class="nav-link btn btn-flex flex-center btn-active-danger btn-color-gray-600 btn-active-color-white rounded-2 w-45px h-35px" data-bs-toggle="tab" id="kt_charts_widget_33_tab_4" href="#kt_charts_widget_33_tab_content_4">
                                    6m
                                </a>
                                <!--end::Link-->
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="nav-item mb-3">
                                <!--begin::Link-->
                                <a class="nav-link btn btn-flex flex-center btn-active-danger btn-color-gray-600 btn-active-color-white rounded-2 w-45px h-35px " data-bs-toggle="tab" id="kt_charts_widget_33_tab_5" href="#kt_charts_widget_33_tab_content_5">
                                    1y
                                </a>
                                <!--end::Link-->
                            </li>
                            <!--end::Item-->

                        </ul>
                        <!--end::Nav-->

                        <!--begin::Tab Content-->
                        <div class="tab-content mt-n6">

                            <!--begin::Tap pane-->
                            <div class="tab-pane fade active show"
                                id="kt_charts_widget_33_tab_content_1">
                                <!--begin::Chart-->
                                <div id="kt_charts_widget_33_chart_1" data-kt-chart-color="info"
                                    class="min-h-auto h-200px ps-3 pe-6"></div>
                                <!--end::Chart-->
                            </div>
                            <!--end::Tap pane-->

                            <!--begin::Tap pane-->
                            <div class="tab-pane fade " id="kt_charts_widget_33_tab_content_2">
                                <!--begin::Chart-->
                                <div id="kt_charts_widget_33_chart_2" data-kt-chart-color="info"
                                    class="min-h-auto h-200px ps-3 pe-6"></div>
                                <!--end::Chart-->

                            </div>
                            <!--end::Tap pane-->

                            <!--begin::Tap pane-->
                            <div class="tab-pane fade " id="kt_charts_widget_33_tab_content_3">
                                <!--begin::Chart-->
                                <div id="kt_charts_widget_33_chart_3" data-kt-chart-color="info"
                                    class="min-h-auto h-200px ps-3 pe-6"></div>
                                <!--end::Chart-->

                            </div>
                            <!--end::Tap pane-->

                            <!--begin::Tap pane-->
                            <div class="tab-pane fade " id="kt_charts_widget_33_tab_content_4">
                                <!--begin::Chart-->
                                <div id="kt_charts_widget_33_chart_4" data-kt-chart-color="info"
                                    class="min-h-auto h-200px ps-3 pe-6"></div>
                                <!--end::Chart-->

                            </div>
                            <!--end::Tap pane-->

                            <!--begin::Tap pane-->
                            <div class="tab-pane fade " id="kt_charts_widget_33_tab_content_5">
                                <!--begin::Chart-->
                                <div id="kt_charts_widget_33_chart_5" data-kt-chart-color="info"
                                    class="min-h-auto h-200px ps-3 pe-6"></div>
                                <!--end::Chart-->

                            </div>
                            <!--end::Tap pane-->

                        </div>
                        <!--end::Tab Content-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::Chart Widget 33-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

      
    </div>
    <!--end::Content-->

</div>
<!--end::Content wrapper-->

@endsection


@push('scripts')
<script>
    // Data passed from the controller
    var oneDaySalesData = @json($oneDaySalesData);
    var fiveDaysSalesData = @json($fiveDaysSalesData);
    var oneMonthSalesData = @json($oneMonthSalesData);
    var sixMonthsSalesData = @json($sixMonthsSalesData);
    var oneYearSalesData = @json($oneYearSalesData);


    function renderChart(e, tabSelector, chartSelector, salesData, categories, renderInitially = false) {
        var element = document.querySelector(chartSelector);

        if (element) {
            var chartColor = element.getAttribute("data-kt-chart-color"),
                chartHeight = parseInt(KTUtil.css(element, "height")),
                grayColor = KTUtil.getCssVariableValue("--bs-gray-500"),
                dashedColor = KTUtil.getCssVariableValue("--bs-border-dashed-color"),
                chartColorValue = KTUtil.getCssVariableValue("--bs-" + chartColor),
                options = {
                    series: [{
                        name: "Sales",
                        data: salesData.sales.map(Number)
                    }],
                    chart: {
                        fontFamily: "inherit",
                        type: "area",
                        height: chartHeight,
                        toolbar: { show: false },
                    },
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    fill: {
                        type: "gradient",
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.2,
                            stops: [15, 120, 100],
                        },
                    },
                    stroke: {
                        curve: "smooth",
                        show: true,
                        width: 3,
                        colors: [chartColorValue],
                    },
                    xaxis: {
                        categories: categories,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        tickPlacement: 'on',
                        tickAmount: categories.length,
                        labels: {
                            rotate: 0,
                            rotateAlways: false,
                            show: chartSelector !== "#kt_charts_widget_33_chart_3", // Hide labels for one-month chart
                            style: { colors: grayColor, fontSize: "12px" },
                        },
                        crosshairs: {
                            position: "front",
                            stroke: { color: chartColorValue, width: 1, dashArray: 3 },
                        },
                        tooltip: {
                            enabled: true,
                            formatter: undefined,
                            offsetY: 0,
                            style: { fontSize: "12px" },
                        },
                    },
                    yaxis: {
                        tickAmount: 4,
                        labels: { show: false },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    states: {
                        normal: { filter: { type: "none", value: 0 } },
                        hover: { filter: { type: "none", value: 0 } },
                        active: {
                            allowMultipleDataPointsSelection: false,
                            filter: { type: "none", value: 0 },
                        },
                    },
                    tooltip: {
                        style: { fontSize: "12px" },
                        y: {
                            formatter: function (val) {
                                return "₹" + val;
                            },
                        },
                    },
                    colors: [chartColorValue],
                    grid: {
                        borderColor: dashedColor,
                        strokeDashArray: 3,
                        yaxis: { lines: { show: true } },
                    },
                    markers: { strokeColor: chartColorValue, strokeWidth: 3 },
                };

            e.self = new ApexCharts(element, options);

            var tabElement = document.querySelector(tabSelector);

            if (renderInitially) {
                setTimeout(function () {
                    e.self.render(), (e.rendered = true);
                }, 200);
            }

            tabElement.addEventListener("shown.bs.tab", function (t) {
                if (!e.rendered) {
                    e.self.render(), (e.rendered = true);
                }
            });
        }
    }

    var oneDayChart = { self: null, rendered: false };
    var fiveDaysChart = { self: null, rendered: false };
    var oneMonthChart = { self: null, rendered: false };
    var sixMonthChart = { self: null, rendered: false };
    var onYearChart = { self: null, rendered: false };

    // Initialize the charts
    renderChart(oneDayChart, "#kt_charts_widget_33_tab_1", "#kt_charts_widget_33_chart_1", oneDaySalesData, oneDaySalesData.labels, true);
    renderChart(fiveDaysChart, "#kt_charts_widget_33_tab_2", "#kt_charts_widget_33_chart_2", fiveDaysSalesData, fiveDaysSalesData.labels, false);
    renderChart(oneMonthChart, "#kt_charts_widget_33_tab_3", "#kt_charts_widget_33_chart_3", oneMonthSalesData, oneMonthSalesData.labels, false);
    renderChart(sixMonthChart, "#kt_charts_widget_33_tab_4", "#kt_charts_widget_33_chart_4", sixMonthsSalesData, sixMonthsSalesData.labels, false);
    renderChart(onYearChart, "#kt_charts_widget_33_tab_5", "#kt_charts_widget_33_chart_5", oneYearSalesData, oneYearSalesData.labels, false);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var salesAmounts = @json($salesAmounts); // Ensure this matches the structure from your controller

        function updateSaleAmount(period) {
            var saleAmountElement = document.getElementById('sale-amount-value');

            if (salesAmounts.hasOwnProperty(period)) {
                var formattedAmount = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(salesAmounts[period]);
                saleAmountElement.textContent = formattedAmount;
            } else {
                console.error('Invalid period key:', period);
                saleAmountElement.textContent = '₹0.00';
            }
        }

        var tabs = document.querySelectorAll('.nav-link');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                // Correct the extraction of period from id
                var period = this.getAttribute('href').replace('#kt_charts_widget_33_tab_content_', '').trim();
                
                // Convert extracted period value to expected keys
                switch (period) {
                    case '1':
                        period = 'oneD';
                        break;
                    case '2':
                        period = 'fiveDd';
                        break;
                    case '3':
                        period = 'oneM';
                        break;
                    case '4':
                        period = 'sixM';
                        break;
                    case '5':
                        period = 'oneY';
                        break;
                    default:
                        console.error('Invalid period key:', period);
                        return;
                }

                updateSaleAmount(period);
            });
        });

        // Initialize with the default active tab
        var initialTab = document.querySelector('.nav-link.active');
        if (initialTab) {
            var initialPeriod = initialTab.getAttribute('href').replace('#kt_charts_widget_33_tab_content_', '').trim();
            
            // Convert initial period as well
            switch (initialPeriod) {
                case '1':
                    initialPeriod = 'oneD';
                    break;
                case '2':
                    initialPeriod = 'fiveDd';
                    break;
                case '3':
                    initialPeriod = 'oneM';
                    break;
                case '4':
                    initialPeriod = 'sixM';
                    break;
                case '5':
                    initialPeriod = 'oneY';
                    break;
                default:
                    console.error('Invalid initial period key:', initialPeriod);
                    return;
            }

            updateSaleAmount(initialPeriod);
        } else {
            console.error('No active tab found on page load.');
        }
    });
</script>
<script>
    // setInterval(() => {
    //     fetch('/admin/live-analytics')
    //         .then(response => response.json())
    //         .then(data => {
    //             document.getElementById('active-visitors').textContent = data.activeVisitors || 0;
    //             document.getElementById('page-views').textContent = data.pageViews || 0;
    //         })
    //         .catch(error => console.error('Error fetching live analytics:', error));
    // }, 5000); // Update every 5 seconds
</script>



@endpush