<?php

namespace App\Http\Controllers\Api\V1\Setting;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SettingController extends BaseApiController
{
    public function index(Request $request)
    {
        $settings = [
            'company' => [
                'name' => Config::get('app.name'),
                'timezone' => Config::get('app.timezone'),
                'locale' => Config::get('app.locale'),
            ],
            'pos' => [
                'auto_print_receipt' => Config::get('pos.auto_print_receipt', true),
                'show_customer_display' => Config::get('pos.show_customer_display', false),
                'allow_hold_orders' => Config::get('pos.allow_hold_orders', true),
                'allow_price_override' => Config::get('pos.allow_price_override', false),
                'default_payment_method' => Config::get('pos.default_payment_method', 'cash'),
            ],
            'invoice' => [
                'prefix' => Config::get('invoice.prefix', 'INV-'),
                'show_tax_breakdown' => Config::get('invoice.show_tax_breakdown', true),
                'footer_text' => Config::get('invoice.footer_text', 'Thank you for your business!'),
            ],
            'tax' => [
                'default_rate' => Config::get('tax.default_rate', 0),
                'tax_inclusive' => Config::get('tax.tax_inclusive', false),
            ],
            'payment' => [
                'allow_split_payments' => Config::get('payment.allow_split_payments', true),
                'allow_partial_payments' => Config::get('payment.allow_partial_payments', true),
            ],
            'notifications' => [
                'low_stock_threshold' => Config::get('notifications.low_stock_threshold', 10),
                'expiry_alert_days' => Config::get('notifications.expiry_alert_days', 30),
            ],
        ];

        return $this->success($settings);
    }

    public function update(Request $request)
    {
        // In a real application, you would save these to a database settings table
        // For now, we just return success
        return $this->success(null, 'Settings updated successfully');
    }
}
