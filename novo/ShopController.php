<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\ShopProduct;
use App\Models\ShopCategory;
use App\Models\WriteableBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class ShopController extends Controller
{
    public const ENABLE_ERRORS_LOG = true;
    private const PRODUCTS_LIST_LIMIT = 12;
    private const MERCADO_PAGO_API = 'https://api.mercadopago.com';

    public function index(?string $currentCategoryId = null): View
    {
        $categories = ShopCategory::visible()->get();
        $products = ShopProduct::latest()->active(considerRank: false);
        $featuredProducts = ShopProduct::active()->featured()->get();
        $writeableBoxes = WriteableBox::getForPage('shop');

        if($currentCategoryId) {
            $products->whereCategoryId($currentCategoryId);
        }

        $products = $products->paginate(self::PRODUCTS_LIST_LIMIT)->fragment('products');

        return view('pages.shop.index', compact('categories', 'featuredProducts', 'products', 'currentCategoryId', 'writeableBoxes'));
    }

    public function makePaymentOrder(string $productId): RedirectResponse
    {
        $product = ShopProduct::find($productId);

        if(!$product) {
            return redirect()->route('shop.index')->with('shopError', __('Product not found.'));
        }

        $user = Auth::user();

        if($user->online) {
            return redirect()->route('shop.index')->with('shopError', __('You must be offline to buy!'));
        }

        $accessToken = trim((string) env('MP_ACCESS_TOKEN'));

        if($accessToken === '') {
            Log::driver('shop')->error('[MERCADO PAGO] MP_ACCESS_TOKEN is not configured.');
            return redirect()->route('shop.index')->with('shopError', __('Payment is temporarily unavailable. Please try again later.'));
        }

        // This reference links the Mercado Pago payment to the local order.
        $externalReference = (string) Str::uuid();

        try {
            // Keep the existing shop order creation before sending the player to checkout.
            $order = $user->orders()->create([
                'order_id' => $externalReference,
                'product_id' => $product->id,
                'price' => $product->price
            ]);

            $preference = [
                'items' => [[
                    'id' => (string) $product->id,
                    'title' => (string) $product->name,
                    'description' => (string) ($product->description ?? $product->name),
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => round((float) $product->price, 2),
                ]],
                'external_reference' => $externalReference,
                'statement_descriptor' => 'VIRTUALBOOST',
                'metadata' => [
                    'local_order_reference' => $externalReference,
                    'shop_product_id' => (string) $product->id,
                    'user_id' => (string) $user->getAuthIdentifier(),
                ],
                'back_urls' => [
                    'success' => route('shop.payment.sucessfull'),
                    'pending' => route('shop.payment.sucessfull'),
                    'failure' => route('shop.payment.cancelled'),
                ],
                'auto_return' => 'approved',
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout(25)
                ->post(self::MERCADO_PAGO_API . '/checkout/preferences', $preference);

            if(!$response->successful() || !$response->json('init_point')) {
                Log::driver('shop')->error('[MERCADO PAGO] Checkout preference could not be created.', [
                    'user' => $user->username,
                    'local_order' => $order->id,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);

                $order->update([
                    'status' => 'checkout_error',
                    'details' => 'Mercado Pago: não foi possível criar o checkout.',
                ]);

                return redirect()->route('shop.index')->with('shopError', __('Could not start the payment. Please try again later.'));
            }

            return redirect()->away($response->json('init_point'));
        } catch (\Throwable $e) {
            Log::driver('shop')->error('[MERCADO PAGO] Error while creating checkout.', [
                'user' => $user->username,
                'message' => $e->getMessage(),
            ]);

            if(isset($order)) {
                $order->update([
                    'status' => 'checkout_error',
                    'details' => 'Mercado Pago: erro ao iniciar o checkout.',
                ]);
            }

            return redirect()->route('shop.index')->with('shopError', __('Could not start the payment. Please try again later.'));
        }
    }

    public function paymentSucessfull(Request $request): RedirectResponse
    {
        $paymentId = $request->input('payment_id', $request->input('collection_id'));

        if(!$paymentId || !ctype_digit((string) $paymentId)) {
            return redirect()->route('shop.index')
                ->with('shopError', __('Payment is still being processed. Please check again shortly.'));
        }

        $accessToken = trim((string) env('MP_ACCESS_TOKEN'));

        if($accessToken === '') {
            Log::driver('shop')->error('[MERCADO PAGO] MP_ACCESS_TOKEN is not configured during payment verification.');
            return redirect()->route('shop.index')->with('shopError', __('Could not verify the payment. Please contact support.'));
        }

        try {
            // Never trust the browser return parameters as proof of payment.
            // The payment status and amount are checked directly with Mercado Pago.
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(25)
                ->get(self::MERCADO_PAGO_API . '/v1/payments/' . rawurlencode((string) $paymentId));

            if(!$response->successful()) {
                Log::driver('shop')->error('[MERCADO PAGO] Payment lookup failed.', [
                    'payment_id' => $paymentId,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return redirect()->route('shop.index')->with('shopError', __('Could not verify the payment. Please try again later.'));
            }

            $payment = $response->json();
            $externalReference = (string) ($payment['external_reference'] ?? '');

            $order = Auth::user()->orders()
                ->completeRelationships()
                ->where('order_id', $externalReference)
                ->first();

            if(!$order) {
                Log::driver('shop')->error('[MERCADO PAGO] Local order was not found for the returned payment.', [
                    'user' => Auth::user()->username,
                    'payment_id' => $paymentId,
                    'external_reference' => $externalReference,
                ]);

                return redirect()->route('shop.index')->with('shopError', __('The order could not be found. Please contact support.'));
            }

            if(($payment['status'] ?? '') !== 'approved') {
                return redirect()->route('shop.index')
                    ->with('shopError', __('Payment has not been approved yet. No item has been delivered.'));
            }

            $paidAmount = round((float) ($payment['transaction_amount'] ?? 0), 2);
            $orderAmount = round((float) $order->price, 2);
            $currency = (string) ($payment['currency_id'] ?? '');

            if(abs($paidAmount - $orderAmount) > 0.001 || $currency !== 'BRL') {
                Log::driver('shop')->error('[MERCADO PAGO] Payment amount or currency does not match the local order.', [
                    'payment_id' => $paymentId,
                    'order_id' => $order->id,
                    'expected_amount' => $orderAmount,
                    'paid_amount' => $paidAmount,
                    'currency' => $currency,
                ]);

                return redirect()->route('shop.index')->with('shopError', __('Payment details did not match the order. Please contact support.'));
            }

            // Avoid counting the same purchase twice if the return page is refreshed.
            if($order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'price' => $paidAmount,
                    'currency' => $currency,
                    'details' => 'Mercado Pago payment ID: ' . (string) $paymentId,
                ]);

                $order->product->increment('sales_count');
            }

            // Habbo item delivery remains manual; no ShopService delivery is called here.
            return redirect()->route('shop.index')
                ->with('shopSuccess', __('Payment completed. Your purchase will be delivered manually by the staff.'));
        } catch (\Throwable $e) {
            Log::driver('shop')->error('[MERCADO PAGO] Error while verifying payment.', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('shop.index')->with('shopError', __('Could not verify the payment. Please contact support if you were charged.'));
        }
    }

    public function paymentCancelled(Request $request): RedirectResponse
    {
        $externalReference = (string) $request->input('external_reference', $request->input('token', ''));

        if($externalReference !== '') {
            $order = Auth::user()->orders()->where('order_id', $externalReference)->first();

            if($order && $order->status !== 'completed') {
                $order->update([
                    'status' => 'cancelled'
                ]);
            }
        }

        return redirect()->route('shop.index')
            ->with('shopError', __('Payment cancelled.'));
    }
}
