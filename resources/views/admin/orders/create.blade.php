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
            <!-- <div class="col-12 mb-3">
                <label class="form-label">Order Items / Notes *</label>
                <textarea name="order_notes" class="form-control" rows="4" required></textarea>
            </div> -->

            <div class="col-12 mt-4">
                <h5>Add Line Items</h5>
                <div class="mb-3">
                    <small class="text-muted">Search for products or enter a custom description. Custom descriptions will be sent to Zoho as misc charges (no SKU required).</small>
                </div>
            </div>

            <!-- Unified Line Items Section -->
            <div id="lineItemsSection">
                <div class="row">
                    <div class="col-md-4 mb-3" style="position: relative;">
                        <label class="form-label">Search Product or Enter Description</label>
                        <input type="text" id="itemSearch" class="form-control" placeholder="Type to search products or enter custom description..." autocomplete="off" />
                        <div id="searchResults" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; border: 1px solid #ced4da; border-radius: 0.375rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);"></div>
                        <input type="hidden" id="selectedVariantId" />
                        <input type="hidden" id="selectedProductId" />
                        <input type="hidden" id="selectedSku" />
                        <input type="hidden" id="selectedPrice" />
                        <input type="hidden" id="selectedTitle" />
                        <input type="hidden" id="selectedProductTitle" />
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" id="variantQty" class="form-control" value="1" min="1" />
                    </div>
                    <div class="col-md-3 mb-3" id="customPriceSection" style="display: none;">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" id="customPrice" class="form-control" value="0" min="0" step="0.01" placeholder="Enter price" />
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="button" id="addItemBtn" class="btn btn-secondary">Add Item</button>
                    </div>
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
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Hidden description field for backend -->
            <input type="hidden" name="description" id="descriptionField" />

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
                <select name="currency" class="form-select">
                    <option value="INR" selected>INR</option>
                </select>
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
            
            // Check if at least one item is added or description is provided
            const hasItems = itemsTbody.querySelectorAll('tr').length > 0;
            if (!hasItems) {
                e.preventDefault();
                itemSearch.focus();
                alert('Please add at least one item or description to the order.');
                return false;
            }
            
            return true;
        });

        // -- Add Item (vanilla JS) --
        const addItemBtn = document.getElementById('addItemBtn');
        const itemSearch = document.getElementById('itemSearch');
        const searchResults = document.getElementById('searchResults');
        const variantQty = document.getElementById('variantQty');
        const itemsTbody = document.querySelector('#itemsTable tbody');

        // Zoho taxes passed from server-side if available; fallback to empty array
        window.zohoTaxes = @json($zohoTaxes ?? []);

        // Store all variants for searching
        const allVariants = @json($variantsForJs);

        let selectedVariant = null;
        let searchTimeout = null;

        // Search functionality
        itemSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    selectedVariant = null;
                    clearHiddenFields();
                    return;
                }

                const matches = allVariants.filter(v => 
                    v.search_text.toLowerCase().includes(query.toLowerCase())
                );

                if (matches.length > 0) {
                    showSearchResults(matches, query);
                } else {
                    showCustomDescriptionOption(query);
                }
            }, 300);
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#itemSearch') && !e.target.closest('#searchResults')) {
                searchResults.style.display = 'none';
            }
        });

        function showSearchResults(matches, query) {
            let html = '';
            matches.slice(0, 10).forEach(variant => {
                html += `<div class="dropdown-item" style="cursor: pointer;" data-variant='${JSON.stringify(variant)}'>
                    <strong>${escapeHtml(variant.product_title)}</strong> — ${escapeHtml(variant.title)}<br>
                    <small class="text-muted">SKU: ${escapeHtml(variant.sku)} | Price: ₹${variant.price}</small>
                </div>`;
            });
            
            // Add custom description option at the end
            html += `<div class="dropdown-divider"></div>`;
            html += `<div class="dropdown-item" style="cursor: pointer;" data-custom-description="${escapeHtml(query)}">
                <i class="fas fa-plus text-primary"></i> Add "${escapeHtml(query)}" as custom description
            </div>`;
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

        function showCustomDescriptionOption(query) {
            const html = `<div class="dropdown-item" style="cursor: pointer;" data-custom-description="${escapeHtml(query)}">
                <i class="fas fa-plus text-primary"></i> Add "${escapeHtml(query)}" as custom description
            </div>`;
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

        // Handle search result selection
        searchResults.addEventListener('click', function(e) {
            const item = e.target.closest('.dropdown-item');
            if (!item) return;

            const customPriceSection = document.getElementById('customPriceSection');

            if (item.dataset.variant) {
                // Product variant selected
                selectedVariant = JSON.parse(item.dataset.variant);
                itemSearch.value = `${selectedVariant.product_title} — ${selectedVariant.title}`;
                setHiddenFields(selectedVariant);
                // Hide price field for product variants
                customPriceSection.style.display = 'none';
            } else if (item.dataset.customDescription) {
                // Custom description selected
                selectedVariant = null;
                itemSearch.value = item.dataset.customDescription;
                clearHiddenFields();
                // Show price field for custom descriptions
                customPriceSection.style.display = 'block';
            }
            
            searchResults.style.display = 'none';
        });

        function setHiddenFields(variant) {
            document.getElementById('selectedVariantId').value = variant.id;
            document.getElementById('selectedProductId').value = variant.product_id;
            document.getElementById('selectedSku').value = variant.sku;
            document.getElementById('selectedPrice').value = variant.price;
            document.getElementById('selectedTitle').value = variant.title;
            document.getElementById('selectedProductTitle').value = variant.product_title;
        }

        function clearHiddenFields() {
            document.getElementById('selectedVariantId').value = '';
            document.getElementById('selectedProductId').value = '';
            document.getElementById('selectedSku').value = '';
            document.getElementById('selectedPrice').value = '';
            document.getElementById('selectedTitle').value = '';
            document.getElementById('selectedProductTitle').value = '';
        }

        // Description functionality - removed as we're using unified approach

        function clearTotals() {
            setNumberInput('subtotal_price', '');
            setNumberInput('total_line_items_price', '');
            setNumberInput('total_price', '');
        }

        if (addItemBtn) {
            addItemBtn.addEventListener('click', function() {
                const searchValue = itemSearch.value.trim();
                if (!searchValue) {
                    alert('Please search for a product or enter a description.');
                    return;
                }

                const qty = parseInt(variantQty.value, 10) || 1;
                const index = itemsTbody.querySelectorAll('tr').length;

                if (selectedVariant) {
                    // Adding a product variant
                    const variant = selectedVariant;
                    
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

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(variant.product_title)}</td>
                        <td>${taxSelectHtml}</td>
                        <td>${escapeHtml(variant.title)}</td>
                        <td>${escapeHtml(variant.sku)}</td>
                        <td class="text-end">${variant.price}<input type="hidden" name="items[${index}][price]" value="${variant.price}"></td>
                        <td class="text-end">${qty}<input type="hidden" name="items[${index}][quantity]" value="${qty}"></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeItemBtn">Remove</button></td>
                        <input type="hidden" name="items[${index}][product_id]" value="${variant.product_id}">
                        <input type="hidden" name="items[${index}][variant_id]" value="${variant.id}">
                        <input type="hidden" name="items[${index}][sku]" value="${variant.sku}">
                        <input type="hidden" name="items[${index}][title]" value="${escapeAttr(variant.title)}">
                    `;
                    itemsTbody.appendChild(tr);
                } else {
                    // Adding a custom description item
                    const customPrice = parseFloat(document.getElementById('customPrice').value) || 0;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td colspan="3">${escapeHtml(searchValue)} <span class="badge bg-info">Custom (Misc Charge)</span></td>
                        <td>MISC-SERVICE</td>
                        <td class="text-end">${customPrice.toFixed(2)}<input type="hidden" name="items[${index}][price]" value="${customPrice}"></td>
                        <td class="text-end">${qty}<input type="hidden" name="items[${index}][quantity]" value="${qty}"></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeItemBtn">Remove</button></td>
                        <input type="hidden" name="items[${index}][product_id]" value="">
                        <input type="hidden" name="items[${index}][variant_id]" value="">
                        <input type="hidden" name="items[${index}][sku]" value="MISC-SERVICE">
                        <input type="hidden" name="items[${index}][title]" value="${escapeAttr(searchValue)}">
                        <input type="hidden" name="items[${index}][is_custom]" value="1">
                    `;
                    itemsTbody.appendChild(tr);
                    
                    // Update description field for backend
                    updateDescriptionField();
                }

                // Reset form
                itemSearch.value = '';
                variantQty.value = 1;
                document.getElementById('customPrice').value = 0;
                document.getElementById('customPriceSection').style.display = 'none';
                selectedVariant = null;
                clearHiddenFields();
                searchResults.style.display = 'none';
                
                // Recompute pricing fields after adding
                computeTotals();
            });
        }

        // remove item
        itemsTbody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('removeItemBtn')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                    // Update description field after removal
                    updateDescriptionField();
                }
                
                // Recompute totals after removal
                computeTotals();
            }
        });

        // Update description field with custom items
        function updateDescriptionField() {
            const customItems = [];
            const rows = itemsTbody.querySelectorAll('tr');
            rows.forEach(row => {
                const isCustom = row.querySelector('input[name$="[is_custom]"]');
                if (isCustom && isCustom.value === '1') {
                    const titleInput = row.querySelector('input[name$="[title]"]');
                    const qtyInput = row.querySelector('input[name$="[quantity]"]');
                    if (titleInput && qtyInput) {
                        const qty = parseInt(qtyInput.value) || 1;
                        customItems.push(qty > 1 ? `${titleInput.value} (x${qty})` : titleInput.value);
                    }
                }
            });
            
            document.getElementById('descriptionField').value = customItems.join(', ');
        }

        // Update preview/totals when quantity changes
        variantQty.addEventListener('input', function() {
            // Update preview if a variant is selected
            if (selectedVariant) {
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
            } else if (selectedVariant) {
                updateTotalsFromSelection();
            }
        }

        if (taxInput) taxInput.addEventListener('input', priceInputsChanged);
        if (shippingInput) shippingInput.addEventListener('input', priceInputsChanged);
        if (discountsInput) discountsInput.addEventListener('input', priceInputsChanged);

        function updateTotalsFromSelection() {
            if (!selectedVariant) {
                // clear totals
                setNumberInput('subtotal_price', '');
                setNumberInput('total_line_items_price', '');
                setNumberInput('total_price', '');
                return;
            }
            const price = parseFloat(selectedVariant.price) || 0;
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
