<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessageLog;
use App\Models\Order;
use App\Services\GupshupService;
use Illuminate\Http\Request;

class WhatsAppLogController extends Controller
{
    protected GupshupService $gupshup;

    public function __construct(GupshupService $gupshup)
    {
        $this->gupshup = $gupshup;
    }

    /**
     * Display all WhatsApp logs
     */
    public function index(Request $request)
    {
        $query = WhatsAppMessageLog::with(['order', 'sender']);

        // Search by order number or phone
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhereHas('order', function($q2) use ($search) {
                      $q2->where('order_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by message type
        if ($type = $request->input('message_type')) {
            $query->where('message_type', $type);
        }

        // Filter by date range
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.whatsapp-logs.index', compact('logs'));
    }

    /**
     * Show single log detail
     */
    public function show($id)
    {
        $log = WhatsAppMessageLog::with(['order', 'sender'])->findOrFail($id);
        return view('admin.whatsapp-logs.show', compact('log'));
    }

    /**
     * Get logs for specific order
     */
    public function orderLogs($orderId)
    {
        $order = Order::findOrFail($orderId);
        $logs = WhatsAppMessageLog::where('order_id', $orderId)
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return view('admin.whatsapp-logs.order', compact('order', 'logs'));
    }

    /**
     * Retry failed message
     */
    public function retry($id)
    {
        $log = WhatsAppMessageLog::findOrFail($id);

        if ($log->status !== 'failed') {
            return back()->with('error', 'Only failed messages can be retried.');
        }

        // Resend the message
        $response = $this->gupshup->sendTemplate([
            'send_to'     => $log->recipient_phone,
            'template_id' => $log->template_id,
            'vars'        => $log->template_vars ?? [],
            'campaign_id' => 'retry-' . $log->order->order_number,
        ]);

        // Create new log entry
        WhatsAppMessageLog::create([
            'order_id' => $log->order_id,
            'recipient_phone' => $log->recipient_phone,
            'template_id' => $log->template_id,
            'template_name' => $log->template_name,
            'template_vars' => $log->template_vars,
            'message_type' => 'manual',
            'trigger_event' => $log->trigger_event . '_retry',
            'response' => $response,
            'status' => !empty($response['error']) ? 'failed' : 'sent',
            'gupshup_message_id' => $response['response']['id'] ?? null,
            'error_message' => $response['message'] ?? null,
            'sent_by' => auth('admin')->id(),
        ]);

        return back()->with('message', 'Message retry attempted. Check logs for status.');
    }
}