<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database notification sent to a business owner when a customer's checkout
 * successfully creates an order containing one or more of their products.
 *
 * The payload is scoped to the receiving seller: a multi-seller order only
 * carries that seller's own items and subtotal, never another shop's data.
 */
class NewOrderReceived extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{product_name: string, quantity: int, subtotal: float}>  $sellerItems
     * @param  float  $sellerSubtotal  Sum of the receiving seller's item subtotals
     */
    public function __construct(
        public Order $order,
        public array $sellerItems,
        public float $sellerSubtotal,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $productSummary = collect($this->sellerItems)
            ->map(fn (array $item): string => $item['product_name'].' × '.$item['quantity'])
            ->implode(', ');

        $message = $this->order->customer_name
            .' placed order #'.$this->order->order_number
            .' containing your products: '.$productSummary
            .'. Your subtotal: ৳'.number_format($this->sellerSubtotal, 2)
            .'. Status: '.ucfirst($this->order->status).'.';

        return [
            'title' => 'New Order Received!',
            'message' => $message,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer_name,
            'order_status' => $this->order->status,
            'placed_at' => $this->order->created_at?->toDateTimeString(),
            'seller_subtotal' => round($this->sellerSubtotal, 2),
            'items' => array_map(fn (array $item): array => [
                'product_name' => $item['product_name'],
                'quantity' => (int) $item['quantity'],
                'subtotal' => round((float) $item['subtotal'], 2),
            ], $this->sellerItems),
            'url' => route('owner.order.manage').'#order-'.$this->order->id,
        ];
    }
}
