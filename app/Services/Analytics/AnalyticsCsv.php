<?php

namespace App\Services\Analytics;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the CSV export of an analytics payload. Every advanced chart on the
 * reports pages has this file as its machine-readable twin, so no value is
 * gated behind hover or colour.
 */
class AnalyticsCsv
{
    /**
     * @param  array<string, mixed>  $payload  payload from AnalyticsService::overview()
     * @return StreamedResponse
     */
    public static function stream(array $payload)
    {
        $window = $payload['window'];

        $filename = sprintf(
            'shopsync-report-%s-%s.csv',
            $window['from'],
            $window['to'],
        );

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ৳ correctly

            fputcsv($out, ['ShopSync report', $payload['window']['from'], 'to', $payload['window']['to']]);
            fputcsv($out, ['Granularity', $payload['window']['granularity'], 'Generated', now()->toDateTimeString()]);
            fputcsv($out, []);

            fputcsv($out, ['Net sales series']);
            fputcsv($out, ['Bucket', 'Orders', 'Units sold', 'Net sales (BDT)', 'Cumulative net sales (BDT)']);

            foreach ($payload['series']['buckets'] ?? [] as $index => $bucket) {
                fputcsv($out, [
                    $bucket,
                    $payload['series']['orders'][$index],
                    $payload['series']['units'][$index],
                    number_format($payload['series']['netSales'][$index], 2, '.', ''),
                    number_format($payload['series']['cumulative'][$index], 2, '.', ''),
                ]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Top products (Pareto)']);
            fputcsv($out, ['Rank', 'Product', 'Units', 'Revenue (BDT)', 'Share %', 'Cumulative share %']);

            foreach ($payload['topProducts'] ?? [] as $index => $product) {
                fputcsv($out, [
                    $index + 1,
                    $product['name'],
                    $product['units'],
                    number_format($product['revenue'], 2, '.', ''),
                    $product['share'],
                    $product['cumulative'],
                ]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Order status distribution (items)']);
            fputcsv($out, ['Status', 'Items', 'Share %']);

            foreach ($payload['statusDistribution'] ?? [] as $status) {
                fputcsv($out, [$status['status'], $status['count'], $status['share']]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Category performance']);
            fputcsv($out, ['Category', 'Units', 'Revenue (BDT)', 'Share %']);

            foreach ($payload['categoryPerformance'] ?? [] as $category) {
                fputcsv($out, [
                    $category['name'],
                    $category['units'],
                    number_format($category['revenue'], 2, '.', ''),
                    $category['share'],
                ]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Voucher usage (window)']);
            fputcsv($out, ['Code', 'Orders', 'Voucher discount (BDT)']);

            foreach ($payload['vouchers']['usage'] ?? [] as $voucher) {
                fputcsv($out, [
                    $voucher['code'],
                    $voucher['orders'],
                    number_format($voucher['discount'], 2, '.', ''),
                ]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Payment methods']);
            fputcsv($out, ['Method', 'Orders', 'Share %']);

            foreach ($payload['payments']['mix'] ?? [] as $payment) {
                fputcsv($out, [$payment['method'], $payment['count'], $payment['share']]);
            }

            fputcsv($out, []);

            fputcsv($out, ['Inventory risk (burn-down)']);
            fputcsv($out, ['Product', 'Stock', 'Sold in window', 'Burn per day', 'Days of stock', 'Restocks']);

            foreach ($payload['inventoryRisk'] ?? [] as $risk) {
                fputcsv($out, [
                    $risk['name'],
                    $risk['stock'],
                    $risk['soldUnits'],
                    $risk['burnPerDay'],
                    $risk['daysOfStock'] ?? 'n/a',
                    $risk['restocks'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
