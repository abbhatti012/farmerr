<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function index()
    {
        $statuses = OrderStatus::all();
        return view('admin.order_status.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        OrderStatus::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => '1',
        ]);

        return redirect()->route('admin.order_status.index')->with('success', 'Order status added successfully');
    }

    public function update(Request $request, $id)
    {
        $status = OrderStatus::findOrFail($id);

        $status->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.order_status.index')->with('success', 'Order status updated successfully');
    }

    public function updateStatus(Request $request)
    {
        $status = OrderStatus::findOrFail($request->id);
        $status->status = $request->status;
        $status->save();

        return redirect()->route('admin.order_status.index')->with('success', 'Status updated successfully');
    }

    public function destroy($id)
    {
        $status = OrderStatus::findOrFail($id);
        $status->delete();

        return redirect()->route('admin.order_status.index')->with('success', 'Order status deleted successfully');
    }
}
