<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->workflow->shipmentStatuses())],
        ]);

        try {
            $this->workflow->updateShipment($shipment, $data, $request->user());
        } catch (InvalidOrderTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'อัปเดตสถานะการจัดส่งแล้ว');
    }
}
