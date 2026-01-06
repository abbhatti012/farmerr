@extends('admin.layout.app')
@section('content')
<div class="container py-4">
    <h2 class="mb-4">Edit Order #{{ $order->order_number }}</h2>

    <form id="editOrderForm" method="POST" action="{{ route('admin.orders.update', $order->id) }}">
        @csrf
        @method('PUT')

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
                <input type="text" name="customer_first_name" class="form-control" value="{{ old('customer_first_name', $order->customer->first_name ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Customer Last Name *</label>
                <input type="text" name="customer_last_name" class="form-control" value="{{ old('customer_last_name', $order->customer->last_name ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Billing Name *</label>
                <input type="text" name="billing_name" class="form-control" value="{{ old('billing_name', $order->billingAddress->name ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $order->billingAddress->phone ?? $order->customer->phone ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email ID *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $order->customer->email ?? '') }}" required />
            </div>
        </div>

        <h4 class="mt-4">Billing Address</h4>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Address Line 1 *</label>
                <input type="text" name="billing_address1" class="form-control" value="{{ old('billing_address1', $order->billingAddress->address1 ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">City *</label>
                <input type="text" name="billing_city" class="form-control" value="{{ old('billing_city', $order->billingAddress->city ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Province / State *</label>
                <input type="text" name="billing_province" class="form-control" value="{{ old('billing_province', $order->billingAddress->province ?? '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Country *</label>
                <input type="text" name="billing_country" class="form-control" value="{{ old('billing_country', $order->billingAddress->country ?? 'India') }}" required />
            </div>
            <input type="hidden" name="billing_country_code" value="{{ old('billing_country_code', $order->billingAddress->country_code ?? 'IN') }}" />
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
                    <input type="text" name="shipping_address1" class="form-control" value="{{ old('shipping_address1', $order->shippingAddress->address1 ?? '') }}" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="shipping_city" class="form-control" value="{{ old('shipping_city', $order->shippingAddress->city ?? '') }}" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Province / State *</label>
                    <input type="text" name="shipping_province" class="form-control" value="{{ old('shipping_province', $order->shippingAddress->province ?? '') }}" required />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Country *</label>
                    <input type="text" name="shipping_country" class="form-control" value="{{ old('shipping_country', $order->shippingAddress->country ?? 'India') }}" required />
                </div>
                <input type="hidden" name="shipping_country_code" value="{{ old('shipping_country_code', $order->shippingAddress->country_code ?? 'IN') }}" />
            </div>
        </div>

        <h4 class="mt-4">2. Payment Type</h4>
        <div class="mb-3">
            <select name="payment_type" class="form-select" required>
                <option value="payg" {{ old('payment_type', $order->payment_type ?? 'payg') === 'payg' ? 'selected' : '' }}>Pay-As-You-Go</option>
                <option value="monthly" {{ old('payment_type', $order->payment_type ?? 'payg') === 'monthly' ? 'selected' : '' }} disabled>Monthly Billing (only for approved customers)</option>
            </select>
            <small class="text-muted">Monthly billing is available only for approved customers.</small>
        </div>

        <h4 class="mt-4">3. Order Details</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Order Type *</label>
                <select name="order_type" class="form-select" required>
                    <option value="Bouquet" {{ old('order_type', $order->occasion ?? 'Bouquet') === 'Bouquet' ? 'selected' : '' }}>Bouquet</option>
                    <option value="Loose" {{ old('order_type', $order->occasion ?? 'Bouquet') === 'Loose' ? 'selected' : '' }}>Loose</option>
                    <option value="Dried" {{ old('order_type', $order->occasion ?? 'Bouquet') === 'Dried' ? 'selected' : '' }}>Dried</option>
                    <option value="Vase" {{ old('order_type', $order->occasion ?? 'Bouquet') === 'Vase' ? 'selected' : '' }}>Vase</option>
                    <option value="Farmer Express" {{ old('order_type', $order->occasion ?? 'Bouquet') === 'Farmer Express' ? 'selected' : '' }}>Farmer Express</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Delivery Time Slot *</label>
                <input type="text" name="delivery_time_slot" class="form-control" value="{{ old('delivery_time_slot', $order->delivery_time_slot ?? '') }}" required placeholder="e.g. 10:00 - 12:00" />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Delivery Date *</label>
                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '') }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Order Date *</label>
                <input type="datetime-local" name="order_date" class="form-control" value="{{ old('order_date', $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d\TH:i') : '') }}" required />
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Order Items / Notes *</label>
                <textarea name="order_notes" class="form-control" rows="4" required>{{ old('order_notes', $order->note ?? '') }}</textarea>
            </div>

            <div class="col-12 mt-4">
                <h5>Add Line Items or Description</h5>
                <div class="mb-3">
                    <button type="button" id="addDescriptionBtn" class="btn btn-outline-primary me-2" {{ $order->description ? 'disabled' : '' }}>
                        {{ $order->description ? 'Description Added' : 'Add Description' }}
                    </button>
                    <small class="text-muted">Choose either line items or description (not both). Description is useful when you don't have specific products to add.</small>
                </div>
            </div>

            <!-- Description Section -->
            <div id="descriptionSection" class="col-12 mb-3" style="display: {{ $order->description ? 'block' : 'none' }};">
                @if($order->description)
                <div class="alert alert-info">
                    <strong>Description Order:</strong> This order uses a custom description. 
                    Make sure the <strong>Grand Total (Paid)</strong> amount is set correctly in the pricing section below.
                </div>
                @endif
                <label class="form-label">Order Description *</label>
                <textarea name="description" id="descriptionTextarea" class="form-control" rows="4" placeholder="Enter order description..." {{ $order->description ? 'required' : '' }}>{{ old('description', $order->description ?? '') }}</textarea>
                <button type="button" id="removeDescriptionBtn" class="btn btn-sm btn-danger mt-2">Remove Description</button>
            </div>

            <!-- Line Items Section -->
            <div id="lineItemsSection" style="display: {{ $order->description ? 'none' : 'block' }};">
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
                                <th>Tax</th>
                                <th>Variant</th>
                                <th>SKU</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->lineItems as $index => $lineItem)
                            <tr>
                                <td>{{ $lineItem->name ?? 'N/A' }}</td>
                                <td>
                                    <select class="form-select form-select-sm tax-select" name="items[{{ $index }}][tax_id]">
                                        <option value="">Default</option>
                                        @foreach($zohoTaxes as $tax)
                                            <option value="{{ $tax['tax_id'] ?? $tax['id'] }}" 
                                                {{ ($lineItem->tax_id ?? '') == ($tax['tax_id'] ?? $tax['id']) ? 'selected' : '' }}>
                                                {{ $tax['tax_name'] ?? $tax['name'] }} ({{ $tax['tax_percentage'] ?? $tax['tax_percentage'] }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>{{ $lineItem->title ?? 'N/A' }}</td>
                                <td>{{ $lineItem->sku ?? 'N/A' }}</td>
                                <td class="text-end">{{ $lineItem->price }}
                                    <input type="hidden" name="items[{{ $index }}][price]" value="{{ $lineItem->price }}">
                                </td>
                                <td class="text-end">{{ $lineItem->quantity }}
                                    <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $lineItem->quantity }}">
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger removeItemBtn">Remove</button></td>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $lineItem->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $lineItem->variant_id }}">
                                <input type="hidden" name="items[{{ $index }}][sku]" value="{{ $lineItem->sku }}">
                                <input type="hidden" name="items[{{ $index }}][title]" value="{{ $lineItem->title ?? '' }}">
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <h5 class="mt-3">Pricing & Status</h5>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Subtotal (₹)</label>
                <input type="number" step="0.01" name="subtotal_price" class="form-control" value="{{ old('subtotal_price', $order->subtotal_price) }}" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Tax (₹)</label>
                <input type="number" step="0.01" name="total_tax" class="form-control" value="{{ old('total_tax', $order->total_tax) }}" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Shipping (₹)</label>
                <input type="number" step="0.01" name="total_shipping_price" class="form-control" value="{{ old('total_shipping_price', $order->total_shipping_price) }}" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Total Discounts (₹)</label>
                <input type="number" step="0.01" name="total_discounts" class="form-control" value="{{ old('total_discounts', $order->total_discounts) }}" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Line Items Price (₹)</label>
                <input type="number" step="0.01" name="total_line_items_price" class="form-control" value="{{ old('total_line_items_price', $order->total_line_items_price) }}" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Grand Total (Paid) (₹)</label>
                <input type="number" step="0.01" name="total_price" class="form-control" value="{{ old('total_price', $order->total_price) }}" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control" value="{{ old('currency', $order->currency) }}" />
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Payment Status</label>
                <select name="financial_status" class="form-select">
                    <option value="pending" {{ old('financial_status', $order->financial_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ old('financial_status', $order->financial_status) === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="refunded" {{ old('financial_status', $order->financial_status) === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="partially_refunded" {{ old('financial_status', $order->financial_status) === 'partially_refunded' ? 'selected' : '' }}>Partially Refunded</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Fulfillment Status</label>
                <select name="fulfillment_status" class="form-select">
                    <option value="unfulfilled" {{ old('fulfillment_status', $order->fulfillment_status) === 'unfulfilled' ? 'selected' : '' }}>Unfulfilled</option>
                    <option value="fulfilled" {{ old('fulfillment_status', $order->fulfillment_status) === 'fulfilled' ? 'selected' : '' }}>Fulfilled</option>
                    <option value="partial" {{ old('fulfillment_status', $order->fulfillment_status) === 'partial' ? 'selected' : '' }}>Partial</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="buyer_accepts_marketing" id="acceptsMarketing" value="1" {{ old('buyer_accepts_marketing', $order->buyer_accepts_marketing) ? 'checked' : '' }}>
                    <label class="form-check-label" for="acceptsMarketing">Buyer accepts marketing</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirmed" id="orderConfirmed" value="1" {{ old('confirmed', $order->confirmed) ? 'checked' : '' }}>
                    <label class="form-check-label" for="orderConfirmed">Confirmed</label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $order->contact_email) }}" />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-control" placeholder="comma separated" value="{{ old('tags', $order->tags) }}" />
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Order Channel *</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="order_channel" id="channelWhatsApp" value="whatsapp" {{ old('order_channel', $order->order_channel ?? 'whatsapp') === 'whatsapp' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="channelWhatsApp">WhatsApp</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="order_channel" id="channelCall" value="call" {{ old('order_channel', $order->order_channel ?? 'whatsapp') === 'call' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="channelCall">Call</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Update Order</button>
            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-secondary ms-2">Cancel</a>
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
        const form = document.getElementById('editOrderForm');
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
            
            // Additional validation for description orders
            const hasDescription = descriptionTextarea.value.trim() !== '';
            const totalPrice = parseFloat(document.querySelector('input[name="total_price"]').value) || 0;
            
            if (hasDescription && totalPrice <= 0) {
                e.preventDefault();
                document.querySelector('input[name="total_price"]').focus();
                alert('Description orders must have a total price greater than 0. Please set the Grand Total (Paid) amount.');
                return false;
            }
            
            return true;
        });

        // -- Add Item (vanilla JS) --
        const addItemBtn = document.getElementById('addItemBtn');
        const variantSelect = document.getElementById('variantSelect');
        const variantQty = document.getElementById('variantQty');
        const itemsTbody = document.querySelector('#itemsTable tbody');

        // Zoho taxes passed from server-side if available; fallback to empty array
        window.zohoTaxes = @json($zohoTaxes ?? []);

        // Description functionality
        const addDescriptionBtn = document.getElementById('addDescriptionBtn');
        const removeDescriptionBtn = document.getElementById('removeDescriptionBtn');
        const descriptionSection = document.getElementById('descriptionSection');
        const lineItemsSection = document.getElementById('lineItemsSection');
        const descriptionTextarea = document.getElementById('descriptionTextarea');

        if (addDescriptionBtn) {
            addDescriptionBtn.addEventListener('click', function() {
                // Show description section
                descriptionSection.style.display = 'block';
                // Hide line items section
                lineItemsSection.style.display = 'none';
                // Disable add description button
                addDescriptionBtn.disabled = true;
                addDescriptionBtn.textContent = 'Description Added';
                // Make description required
                descriptionTextarea.setAttribute('required', 'required');
                // Clear any existing line items
                const existingRows = itemsTbody.querySelectorAll('tr');
                existingRows.forEach(row => {
                    if (!row.querySelector('input[name*="[product_id]"]').value.includes('existing')) {
                        row.remove();
                    }
                });
                // Focus on total price field and highlight it
                const totalPriceField = document.querySelector('input[name="total_price"]');
                if (totalPriceField) {
                    totalPriceField.focus();
                    totalPriceField.style.border = '2px solid #007bff';
                    totalPriceField.placeholder = 'Enter total amount for this service';
                }
            });
        }

        if (removeDescriptionBtn) {
            removeDescriptionBtn.addEventListener('click', function() {
                // Hide description section
                descriptionSection.style.display = 'none';
                // Show line items section
                lineItemsSection.style.display = 'block';
                // Enable add description button
                addDescriptionBtn.disabled = false;
                addDescriptionBtn.textContent = 'Add Description';
                // Remove description requirement
                descriptionTextarea.removeAttribute('required');
                // Clear description
                descriptionTextarea.value = '';
                // Reset total price field styling
                const totalPriceField = document.querySelector('input[name="total_price"]');
                if (totalPriceField) {
                    totalPriceField.style.border = '';
                    totalPriceField.placeholder = '';
                }
            });
        }

        if (addItemBtn) {
            addItemBtn.addEventListener('click', function() {
                const opt = variantSelect.options[variantSelect.selectedIndex];
                if (!opt || !opt.value) {
                    alert('Please select a product variant to add.');
                    return;
                }

                // If this is the first new item being added, disable description
                const existingItemsCount = {{ $order->lineItems->count() }};
                const newItemsCount = itemsTbody.querySelectorAll('tr').length - existingItemsCount;
                if (newItemsCount === 0) {
                    addDescriptionBtn.disabled = true;
                    addDescriptionBtn.textContent = 'Line Items Added';
                    descriptionSection.style.display = 'none';
                    descriptionTextarea.removeAttribute('required');
                    descriptionTextarea.value = '';
                }

                const variantId = opt.value;
                const productId = opt.dataset.product_id || '';
                const sku = opt.dataset.sku || '';
                const price = opt.dataset.price || '0';
                const title = opt.dataset.title || opt.text || '';
                const productTitle = opt.dataset.product_title || '';
                const qty = parseInt(variantQty.value, 10) || 1;

                const index = itemsTbody.querySelectorAll('tr').length + {{ $order->lineItems->count() }};
                const tr = document.createElement('tr');
                // Build tax select HTML from cached zohoTaxes if available
                let taxSelectHtml = '<select class="form-select form-select-sm tax-select" name="items[' + index + '][tax_id]">';
                taxSelectHtml += '<option value="">Default</option>';
                if (window.zohoTaxes && Array.isArray(window.zohoTaxes)) {
                    window.zohoTaxes.forEach(t => {
                        const label = (t.tax_name || t.name) + ' (' + (t.tax_percentage ?? t.tax_percentage) + '%)';
                        taxSelectHtml += '<option value="' + (t.tax_id || t.id) + '">' + escapeHtml(label) + '</option>';
                    });
                }
                taxSelectHtml += '</select>';

                tr.innerHTML = `
                    <td>${escapeHtml(productTitle)}</td>
                    <td>${taxSelectHtml}</td>
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
                // Recompute pricing fields after adding
            });
        }
                computeTotals();
            });
        }

        // remove item
        itemsTbody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('removeItemBtn')) {
                const row = e.target.closest('tr');
                if (row) row.remove();
                // Recompute totals after removal
                computeTotals();
            }
        });

        // Update preview/totals when variant selection or qty changes
        variantSelect.addEventListener('change', function() {
            // if no items added yet, show the selection as the subtotal preview
            const rows = itemsTbody.querySelectorAll('tr').length;
            if (rows === 0) {
                updateTotalsFromSelection();
            }
        });

        variantQty.addEventListener('input', function() {
            const rows = itemsTbody.querySelectorAll('tr').length;
            if (rows === 0) {
                updateTotalsFromSelection();
            }
        });

        // Recompute totals when tax/shipping/discount fields change
        const taxInput = document.querySelector('input[name="total_tax"]');
        const shippingInput = document.querySelector('input[name="total_shipping_price"]');
        const discountsInput = document.querySelector('input[name="total_discounts"]');

        function priceInputsChanged() {
            const rows = itemsTbody.querySelectorAll('tr').length;
            if (rows > 0) {
                computeTotals();
            } else {
                updateTotalsFromSelection();
            }
        }

        if (taxInput) taxInput.addEventListener('input', priceInputsChanged);
        if (shippingInput) shippingInput.addEventListener('input', priceInputsChanged);
        if (discountsInput) discountsInput.addEventListener('input', priceInputsChanged);

        function updateTotalsFromSelection() {
            const opt = variantSelect.options[variantSelect.selectedIndex];
            if (!opt || !opt.value) {
                // clear totals
                setNumberInput('subtotal_price', '');
                setNumberInput('total_line_items_price', '');
                setNumberInput('total_price', '');
                return;
            }
            const price = parseFloat(opt.dataset.price) || 0;
            const qty = parseInt(variantQty.value, 10) || 1;
            const subtotal = price * qty;
            setNumberInput('subtotal_price', subtotal.toFixed(2));
            setNumberInput('total_line_items_price', subtotal.toFixed(2));
            // compute grand total using tax/shipping/discount if present
            recomputeGrandTotal(subtotal);
        }

        function computeTotals() {
            // Sum existing items in the table
            let subtotal = 0;
            const rows = itemsTbody.querySelectorAll('tr');
            rows.forEach(r => {
                const priceInput = r.querySelector('input[name$="[price]"]');
                const qtyInput = r.querySelector('input[name$="[quantity]"]');
                const price = priceInput ? parseFloat(priceInput.value) : 0;
                const qty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
                subtotal += (price || 0) * (qty || 0);
            });
            setNumberInput('subtotal_price', subtotal.toFixed(2));
            setNumberInput('total_line_items_price', subtotal.toFixed(2));
            recomputeGrandTotal(subtotal);
        }

        function recomputeGrandTotal(subtotal) {
            const tax = parseFloat(document.querySelector('input[name="total_tax"]').value) || 0;
            const shipping = parseFloat(document.querySelector('input[name="total_shipping_price"]').value) || 0;
            const discounts = parseFloat(document.querySelector('input[name="total_discounts"]').value) || 0;
            const grand = (subtotal + tax + shipping - discounts) || 0;
            setNumberInput('total_price', grand.toFixed(2));
        }

        // Helper to set numeric inputs by name
        function setNumberInput(name, value) {
            const el = document.querySelector('input[name="' + name + '"]');
            if (!el) return;
            el.value = value;
        }

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