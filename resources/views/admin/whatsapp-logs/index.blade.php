@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>WhatsApp Message Logs</h3>
                </div>
                <div class="card-body">
                    
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search order/phone" 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="message_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="automatic" {{ request('message_type') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                                <option value="manual" {{ request('message_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>

                    {{-- Stats --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>Total Messages</h6>
                                    <h3>{{ $logs->total() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Sent</h6>
                                    <h3>{{ \App\Models\WhatsAppMessageLog::where('status', 'sent')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6>Failed</h6>
                                    <h3>{{ \App\Models\WhatsAppMessageLog::where('status', 'failed')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6>Pending</h6>
                                    <h3>{{ \App\Models\WhatsAppMessageLog::where('status', 'pending')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Order</th>
                                    <th>Phone</th>
                                    <th>Template</th>
                                    <th>Type</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Sent By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $log->created_at->format('d-M-Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $log->order_id) }}" target="_blank">
                                            {{ $log->order->order_number ?? $log->order_id }}
                                        </a>
                                    </td>
                                    <td>{{ $log->recipient_phone }}</td>
                                    <td><small>{{ $log->template_name ?? 'N/A' }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $log->message_type === 'automatic' ? 'primary' : 'secondary' }}">
                                            {{ ucfirst($log->message_type) }}
                                        </span>
                                    </td>
                                    <td><small>{{ str_replace('_', ' ', ucfirst($log->trigger_event ?? '-')) }}</small></td>
                                    <td>{!! $log->status_badge !!}</td>
                                    <td>
                                        @if($log->sender)
                                            {{ $log->sender->name }}
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-logs.show', $log->id) }}" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($log->status === 'failed')
                                        <form action="{{ route('admin.whatsapp-logs.retry', $log->id) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" title="Retry">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">No logs found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection