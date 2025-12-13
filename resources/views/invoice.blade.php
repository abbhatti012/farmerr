<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            color: #000;
            font-size: 14px;
        }

        .header,
        .footer,
        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .inline-boxes {
            table-layout: fixed;
            width: 100%;
        }

        .inline-boxes td {
            vertical-align: top;
            width: 50%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 6px 10px;
            border-bottom: 1px solid #000;
            vertical-align: top;
        }

        .table th {
            text-align: left;
        }

        .table td:last-child,
        .table th:last-child {
            text-align: right;
        }

        .footer p,
        .thankyou {
            text-align: center;
        }

        hr {
            border: 1px solid #000;
        }

        .logo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-row img {
            height: 40px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo-row">
            <div style="display: flex; align-items: center; gap: 10px;">
                <!-- <img src="{{ asset('path-to-your-logo.png') }}" alt="Farmerr Logo"> -->
                <strong>FARMERR.IN</strong>
            </div>
            <div style="text-align: right;">
                Order #{{ $order->order_number }}<br>
                {{ \Carbon\Carbon::parse($order->order_date)->format('j F Y') }}
            </div>
        </div>
    </div>

    <div class="section">
        <table class="inline-boxes">
            <tr>
                <td>
                    <div class="section-title">SHIP TO</div>
                    <p>
                        {{ $order->customer->first_name }} {{ $order->customer->last_name }}<br>
                        {{ $order->shippingAddress->address1 }} {{ $order->shippingAddress->address2 }}<br>
                        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->province }} {{ $order->shippingAddress->zip }}<br>
                        {{ $order->shippingAddress->country }}<br>
                        {{ $order->shippingAddress->phone }}
                    </p>
                </td>
                <td>
                    <div class="section-title">BILL TO</div>
                    <p>
                        {{ $order->customer->first_name }} {{ $order->customer->last_name }}<br>
                        {{ $order->billingAddress->address1 }} {{ $order->billingAddress->address2 }}<br>
                        {{ $order->billingAddress->city }}, {{ $order->billingAddress->province }} {{ $order->billingAddress->zip }}<br>
                        {{ $order->billingAddress->country }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">ITEMS</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->lineItems as $item)
                <tr>
                    <td>
                        {{ $item->name }}<br>
                        @if($item->variant) <small>{{ $item->variant }}</small><br> @endif
                        <small>SKU: {{ $item->sku }}</small>
                    </td>
                    <td>{{ $item->quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($order->note)
    <div class="section">
        <div class="section-title">NOTES</div>
        <p>{{ $order->note }}</p>
    </div>
    @endif

    <div class="thankyou">
        <p>Thank you for shopping with us!</p>
    </div>

    <hr>

    <div class="footer">
        <p><strong><a href="https://farmerr.in/"  style="text-decoration: none; color: #000000;">farmerr.in</a></strong><br>
            @php
            $province = strtolower($order->shippingAddress->province);
            @endphp
            @if($province === 'karnataka')
            32/1 5th Cross Wilson Garden, 560027 Bengaluru KA, India<br>
            @elseif($province === 'telangana')
            B-2-293/82/F/A/24& 24/1
            Basement, Jubilee Hills Road,
            Hyderabad, Telangana 500096, India<br>
            <b>GSTIN:</b> 36AALCK2953Q1ZR, <b>Contact:</b> 8309848906<br>
            @elseif($province === 'delhi')
            Shop No. 16, Ground Floor,
            MP Mall, Pitampura, Delhi 110034, India<br>
            <b>GSTIN:</b> 07AALCK2953Q1ZS, <b>Contact:</b> 7042112482<br>
            @else
            India<br>
            @endif
            <a href="mailto:info@farmerr.in" style="text-decoration: none; color: #000000;">info@farmerr.in</a><br>
            <a href="https://farmerr.in/"  style="text-decoration: none; color: #000000;">farmerr.in</a></p>
    </div>
</body>

</html>