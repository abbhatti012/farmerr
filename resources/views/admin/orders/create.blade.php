@extends('admin.layout.app')
@section('content')
<div class="container py-4">
    <h2 class="mb-4">Create Order</h2>

    <form id="createOrderForm" method="POST" action="{{ route('admin.orders.store') }}">
        @csrf

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('message'))
            <div class="alert alert-info">{{ session('message') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h4>1. Customer Details</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Customer First Name *</label>
                <input type="text" name="customer_first_name" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Customer Last Name *</label>
                <input type="text" name="customer_last_name" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Billing Name *</label>
                <input type="text" name="billing_name" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email ID *</label>
                <input type="email" name="email" class="form-control" required />
            </div>
        </div>

        <h4 class="mt-4">Billing Address</h4>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Address Line 1 *</label>
                <input type="text" name="billing_address1" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">City *</label>
                <input type="text" name="billing_city" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Province / State *</label>
                <input type="text" name="billing_province" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Country *</label>
                <input type="text" name="billing_country" class="form-control" required value="India" />
            </div>
            <input type="hidden" name="billing_country_code" value="IN" />
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="sameAsBilling" />
            <label class="form-check-label" for="sameAsBilling">
                Shipping address same as billing
            </label>
        </div>

        <h4 class="mt-4">Shipping Address</h4>
        <div id="shippingAddressSection">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="shipping_address1" class="form-control" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="shipping_city" class="form-control" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Province / State *</label>
                    <input type="text" name="shipping_province" class="form-control" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Country *</label>
                    <input type="text" name="shipping_country" class="form-control" required value="India" />
                </div>
                <input type="hidden" name="shipping_country_code" value="IN" />
            </div>
        </div>

        <h4 class="mt-4">2. Payment Type</h4>
        <div class="mb-3">
            <select name="payment_type" class="form-select" required>
                <option value="payg" selected>Pay-As-You-Go</option>
                <option value="monthly" disabled>Monthly Billing (only for approved customers)</option>
            </select>
            <small class="text-muted">Monthly billing is available only for approved customers.</small>
        </div>

        <h4 class="mt-4">3. Order Details</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Order Type *</label>
                <select name="order_type" class="form-select" required>
                    <option value="Bouquet">Bouquet</option>
                    <option value="Loose">Loose</option>
                    <option value="Dried">Dried</option>
                    <option value="Vase">Vase</option>
                    <option value="Farmer Express">Farmer Express</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Delivery Time Slot *</label>
                <input type="text" name="delivery_time_slot" class="form-control" required placeholder="e.g. 10:00 - 12:00" />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Delivery Date *</label>
                <input type="date" name="delivery_date" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Order Date *</label>
                <input type="datetime-local" name="order_date" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}" />
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Order Items / Notes *</label>
                <textarea name="order_notes" class="form-control" rows="4" required></textarea>
            </div>

            <div class="col-12 mt-4">
                <h5>Add Line Items</h5>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Product Variant</label>
                <select id="variantSelect" class="form-select">
                    <option value="">Choose product variant</option>
                    @foreach($variants as $v)
                        <option value="{{ $v->id }}" data-product_id="{{ $v->product_id }}" data-sku="{{ $v->sku }}" data-price="{{ $v->price }}" data-title="{{ $v->title }}" data-product_title="{{ $v->product->title ?? '' }}">{{ $v->product->title ?? 'Product' }} — {{ $v->title }} ({{ $v->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" id="variantQty" class="form-control" value="1" min="1" />
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="button" id="addItemBtn" class="btn btn-secondary">Add Item</button>
            </div>

            <div class="col-12">
                <table class="table table-sm" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Qty</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="col-12">
                <h5 class="mt-3">Pricing & Status</h5>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Subtotal (₹)</label>
                <input type="number" step="0.01" name="subtotal_price" class="form-control" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Tax (₹)</label>
                <input type="number" step="0.01" name="total_tax" class="form-control" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Shipping (₹)</label>
                <input type="number" step="0.01" name="total_shipping_price" class="form-control" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Total Discounts (₹)</label>
                <input type="number" step="0.01" name="total_discounts" class="form-control" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Line Items Price (₹)</label>
                <input type="number" step="0.01" name="total_line_items_price" class="form-control" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Grand Total (Paid) (₹)</label>
                <input type="number" step="0.01" name="total_price" class="form-control" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control" value="INR" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Payment Status</label>
                <select name="financial_status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="refunded">Refunded</option>
                    <option value="partially_refunded">Partially Refunded</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Fulfillment Status</label>
                <select name="fulfillment_status" class="form-select">
                    <option value="unfulfilled">Unfulfilled</option>
                    <option value="fulfilled">Fulfilled</option>
                    <option value="partial">Partial</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="buyer_accepts_marketing" id="acceptsMarketing" value="1">
                    <label class="form-check-label" for="acceptsMarketing">Buyer accepts marketing</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirmed" id="orderConfirmed" value="1">
                    <label class="form-check-label" for="orderConfirmed">Confirmed</label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" class="form-control" />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-control" placeholder="comma separated" />
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Order Channel *</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="order_channel" id="channelWhatsApp" value="whatsapp" checked required>
                        <label class="form-check-label" for="channelWhatsApp">WhatsApp</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="order_channel" id="channelCall" value="call" required>
                        <label class="form-check-label" for="channelCall">Call</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Create Order</button>
            <a href="{{ route('admin.orders.list') }}" class="btn btn-secondary ms-2">Cancel</a>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sameCheckbox = document.getElementById('sameAsBilling');
        sameCheckbox.addEventListener('change', function() {
            const checked = this.checked;
            const shippingSection = document.getElementById('shippingAddressSection');
            if (checked) {
                // copy billing values to shipping and disable
                document.querySelector('input[name="shipping_address1"]').value = document.querySelector('input[name="billing_address1"]').value || '';
                document.querySelector('input[name="shipping_city"]').value = document.querySelector('input[name="billing_city"]').value || '';
                document.querySelector('input[name="shipping_province"]').value = document.querySelector('input[name="billing_province"]').value || '';
                const billingCountry = document.querySelector('input[name="billing_country"]').value || '';
                document.querySelector('input[name="shipping_country"]').value = billingCountry;
                const billingCountryCode = document.querySelector('input[name="billing_country_code"]').value || 'IN';
                const shippingCountryCodeInput = document.querySelector('input[name="shipping_country_code"]');
                if (shippingCountryCodeInput) shippingCountryCodeInput.value = billingCountryCode;
                shippingSection.querySelectorAll('input').forEach(i => i.setAttribute('readonly', true));
            } else {
                shippingSection.querySelectorAll('input').forEach(i => i.removeAttribute('readonly'));
            }
        });

        // Ensure mandatory fields before submitting
        const form = document.getElementById('createOrderForm');
        form.addEventListener('submit', function(e) {
            // built-in HTML5 required validation will fire first; keep this as a safety net
            const required = form.querySelectorAll('[required]');
            for (let i = 0; i < required.length; i++) {
                if (!required[i].value) {
                    e.preventDefault();
                    required[i].focus();
                    alert('Please fill all mandatory fields.');
                    return false;
                }
            }
            return true;
        });

        // -- Add Item (vanilla JS) --
        const addItemBtn = document.getElementById('addItemBtn');
        const variantSelect = document.getElementById('variantSelect');
        const variantQty = document.getElementById('variantQty');
        const itemsTbody = document.querySelector('#itemsTable tbody');

        if (addItemBtn) {
            addItemBtn.addEventListener('click', function() {
                const opt = variantSelect.options[variantSelect.selectedIndex];
                if (!opt || !opt.value) {
                    alert('Please select a product variant to add.');
                    return;
                }

                const variantId = opt.value;
                const productId = opt.dataset.product_id || '';
                const sku = opt.dataset.sku || '';
                const price = opt.dataset.price || '0';
                const title = opt.dataset.title || opt.text || '';
                const productTitle = opt.dataset.product_title || '';
                const qty = parseInt(variantQty.value, 10) || 1;

                    const index = itemsTbody.querySelectorAll('tr').length;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(productTitle)}</td>
                        <td>${escapeHtml(title)}</td>
                        <td>${escapeHtml(sku)}</td>
                        <td class="text-end">${price}<input type="hidden" name="items[${index}][price]" value="${price}"></td>
                        <td class="text-end">${qty}<input type="hidden" name="items[${index}][quantity]" value="${qty}"></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeItemBtn">Remove</button></td>
                        <input type="hidden" name="items[${index}][product_id]" value="${productId}">
                        <input type="hidden" name="items[${index}][variant_id]" value="${variantId}">
                        <input type="hidden" name="items[${index}][sku]" value="${sku}">
                        <input type="hidden" name="items[${index}][title]" value="${escapeAttr(title)}">
                    `;

                itemsTbody.appendChild(tr);
                variantSelect.selectedIndex = 0;
                variantQty.value = 1;
            });
        }

        // remove item
        itemsTbody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('removeItemBtn')) {
                const row = e.target.closest('tr');
                if (row) row.remove();
            }
        });

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"'`=\/]/g, function(s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'})[s];
            });
        }

        function escapeAttr(str) {
            return escapeHtml(str).replace(/"/g, '&quot;');
        }
    });
</script>

@endsection
