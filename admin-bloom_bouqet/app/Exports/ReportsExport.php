<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsExport
{
    protected $startDate;
    protected $endDate;
    protected $reportType;

    public function __construct($startDate, $endDate, $reportType = 'orders')
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = Carbon::parse($endDate)->endOfDay();
        $this->reportType = $reportType;
    }

    /**
     * Generate CSV file and return download response (Excel-compatible)
     */
    public function download($filename)
    {
        $data = $this->getData();
        $headers = $this->getHeaders();

        // Set headers for CSV download (Excel-compatible)
        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Create streaming response
        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');

            // Add BOM for proper UTF-8 encoding in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, $headers);

            // Add data
            foreach ($data as $row) {
                fputcsv($file, $this->formatRow($row));
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $responseHeaders);
    }



    /**
     * Get data based on report type
     */
    private function getData()
    {
        switch ($this->reportType) {
            case 'orders':
                return $this->getOrdersData();
            case 'summary':
                return $this->getSummaryData();
            case 'products':
                return $this->getProductsData();
            default:
                return $this->getOrdersData();
        }
    }

    /**
     * Get orders data for export
     */
    private function getOrdersData()
    {
        return Order::with(['user'])
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get headers based on report type
     */
    private function getHeaders()
    {
        switch ($this->reportType) {
            case 'orders':
                return [
                    'ID Pesanan',
                    'Tanggal',
                    'Nama Pelanggan',
                    'Email',
                    'Telepon',
                    'Total Amount',
                    'Status',
                    'Metode Pembayaran',
                    'Status Pembayaran',
                    'Produk',
                    'Alamat Pengiriman'
                ];
            case 'summary':
                return [
                    'Metrik',
                    'Nilai',
                    'Periode'
                ];
            case 'products':
                return [
                    'Nama Produk',
                    'Total Terjual',
                    'Harga Satuan',
                    'Total Pendapatan'
                ];
            default:
                return [];
        }
    }

    /**
     * Format row data based on report type
     */
    private function formatRow($row)
    {
        switch ($this->reportType) {
            case 'orders':
                return $this->formatOrderRow($row);
            case 'summary':
                return [
                    $row['metric'],
                    $row['value'],
                    $row['period']
                ];
            case 'products':
                return [
                    $row['name'],
                    $row['quantity'],
                    'Rp ' . number_format($row['price'], 0, ',', '.'),
                    'Rp ' . number_format($row['total_revenue'], 0, ',', '.')
                ];
            default:
                return [];
        }
    }

    /**
     * Format order row for Excel
     */
    private function formatOrderRow($order)
    {
        $products = '';
        $orderItems = $order->getFormattedItems();

        foreach ($orderItems as $item) {
            $products .= $item['name'] . " (Qty: " . $item['quantity'] . "); ";
        }

        // Get shipping address
        $shippingAddress = '';
        if ($order->shipping_address) {
            $address = is_string($order->shipping_address)
                ? json_decode($order->shipping_address, true)
                : $order->shipping_address;

            if (is_array($address)) {
                $shippingAddress = ($address['address'] ?? '') . ', ' .
                                 ($address['city'] ?? '') . ', ' .
                                 ($address['postal_code'] ?? '');
            }
        }

        return [
            $order->order_id ?? $order->id,
            $order->created_at->format('d/m/Y H:i:s'),
            $order->customer_name ?? ($order->user->name ?? 'Guest'),
            $order->customer_email ?? ($order->user->email ?? '-'),
            $order->customer_phone ?? $order->phone_number ?? '-',
            'Rp ' . number_format($order->total_amount, 0, ',', '.'),
            $this->getStatusLabel($order->status),
            $this->getPaymentMethodLabel($order->payment_method),
            $this->getPaymentStatusLabel($order->payment_status),
            rtrim($products, '; '),
            $shippingAddress
        ];
    }

    /**
     * Get summary data for export
     */
    private function getSummaryData()
    {
        $orders = $this->getOrdersData();

        return collect([
            [
                'metric' => 'Total Pesanan',
                'value' => $orders->count(),
                'period' => $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')
            ],
            [
                'metric' => 'Total Pendapatan',
                'value' => 'Rp ' . number_format($orders->sum('total_amount'), 0, ',', '.'),
                'period' => $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')
            ],
            [
                'metric' => 'Rata-rata Pesanan',
                'value' => 'Rp ' . number_format($orders->avg('total_amount'), 0, ',', '.'),
                'period' => $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')
            ],
            [
                'metric' => 'Pesanan Selesai',
                'value' => $orders->where('status', 'delivered')->count(),
                'period' => $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')
            ]
        ]);
    }

    /**
     * Get products data for export
     */
    private function getProductsData()
    {
        $orders = $this->getOrdersData();
        $productStats = collect();

        foreach ($orders as $order) {
            $orderItems = $order->getFormattedItems();

            foreach ($orderItems as $item) {
                $existing = $productStats->firstWhere('name', $item['name']);

                if ($existing) {
                    $existing['quantity'] += $item['quantity'];
                    $existing['total_revenue'] += ($item['price'] * $item['quantity']);
                } else {
                    $productStats->push([
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total_revenue' => ($item['price'] * $item['quantity'])
                    ]);
                }
            }
        }

        return $productStats->sortByDesc('total_revenue');
    }



    /**
     * Get status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'waiting_for_payment' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'shipping' => 'Dikirim',
            'delivered' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get payment method label
     */
    private function getPaymentMethodLabel($method)
    {
        $labels = [
            'qris' => 'QRIS',
            'bank_transfer' => 'Transfer Bank',
            'credit_card' => 'Kartu Kredit',
            'cod' => 'Bayar di Tempat'
        ];

        return $labels[$method] ?? ucfirst($method ?? 'Unknown');
    }

    /**
     * Get payment status label
     */
    private function getPaymentStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan'
        ];

        return $labels[$status] ?? ucfirst($status ?? 'Unknown');
    }
}
