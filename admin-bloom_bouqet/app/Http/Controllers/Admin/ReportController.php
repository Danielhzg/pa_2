<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;

class ReportController extends Controller
{
    /**
     * Display sales reports for the admin panel.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Get date range from request - if empty, use all time data
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Convert to Carbon instances for queries
            if ($startDate && $endDate) {
                $startDateTime = Carbon::parse($startDate)->startOfDay();
                $endDateTime = Carbon::parse($endDate)->endOfDay();
            } else {
                // If no dates provided, use all time data (last 365 days for performance)
                $startDateTime = Carbon::now()->subDays(365)->startOfDay();
                $endDateTime = Carbon::now()->endOfDay();
                // Keep startDate and endDate as null to show "All Time" in UI
            }
            
            // Get order statistics
            $orderStats = $this->getOrderStats($startDateTime, $endDateTime);
            
            // Get daily sales data
            $dailySales = $this->getDailySales($startDateTime, $endDateTime);
            
            // Get dynamic sales chart data
            $salesChartData = $this->getSalesChartData($startDateTime, $endDateTime);
            
            // Get top selling products
            $topProducts = $this->getTopProducts($startDateTime, $endDateTime, 10);
            
            // Get monthly revenue data
            $monthlyRevenueData = $this->getMonthlyRevenueData($startDateTime, $endDateTime);
            
            // Get order status distribution for pie chart
            $orderStatusDistribution = $this->getOrderStatusDistribution($startDateTime, $endDateTime);
            
            // Get latest orders for the table
            $latestOrders = Order::with('user')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Debug logging
            Log::info('Report data generated', [
                'date_range' => "$startDate to $endDate",
                'sales_chart_data_exists' => !empty($salesChartData),
                'sales_chart_labels_count' => isset($salesChartData['labels']) ? count($salesChartData['labels']) : 0,
                'sales_chart_datasets_count' => isset($salesChartData['datasets']) ? count($salesChartData['datasets']) : 0,
                'monthly_revenue_data_exists' => !empty($monthlyRevenueData),
                'monthly_revenue_labels_count' => isset($monthlyRevenueData['chartData']['labels']) ? count($monthlyRevenueData['chartData']['labels']) : 0,
                'monthly_revenue_datasets_count' => isset($monthlyRevenueData['chartData']['datasets']) ? count($monthlyRevenueData['chartData']['datasets']) : 0,
                'order_stats' => [
                    'total_orders' => $orderStats['total_orders'],
                    'total_revenue' => $orderStats['total_revenue'],
                    'average_order' => $orderStats['average_order'],
                    'monthly_revenue' => $orderStats['monthly_revenue']
                ]
            ]);
            
            return view('admin.reports.index', compact(
                'startDate',
                'endDate',
                'orderStats',
                'dailySales',
                'salesChartData',
                'topProducts',
                'monthlyRevenueData',
                'orderStatusDistribution',
                'latestOrders'
            ));
        } catch (\Exception $e) {
            Log::error('Error in ReportController@index: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('admin.reports.index')->with('error', 'Terjadi kesalahan saat memuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Export orders data as CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            // Get date range from request - if empty, use default range
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Convert to Carbon instances for queries
            if ($startDate && $endDate) {
                $startDateTime = Carbon::parse($startDate)->startOfDay();
                $endDateTime = Carbon::parse($endDate)->endOfDay();
            } else {
                // If no dates provided, use last 30 days for export
                $startDateTime = Carbon::now()->subDays(30)->startOfDay();
                $endDateTime = Carbon::now()->endOfDay();
                $startDate = $startDateTime->format('Y-m-d');
                $endDate = $endDateTime->format('Y-m-d');
            }
            
            // Get orders for the date range
            $orders = Order::with(['user'])
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Define headers for CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="orders_' . $startDate . '_to_' . $endDate . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            // Create and stream the CSV
            $callback = function() use ($orders) {
                $file = fopen('php://output', 'w');
                
                // Add headers
                fputcsv($file, [
                    'ID', 'Tanggal', 'Pelanggan', 'Email', 'Total', 'Status', 
                    'Metode Pembayaran', 'Status Pembayaran', 'Produk', 'Kuantitas', 'Harga Satuan'
                ]);
                
                // Add order data
                foreach ($orders as $order) {
                    $products = '';
                    $quantities = '';
                    $prices = '';
                    
                    // Get items either from JSON or from relationship
                    $orderItems = $order->getFormattedItems();
                    
                    foreach ($orderItems as $item) {
                        $products .= $item['name'] . "; ";
                        $quantities .= $item['quantity'] . "; ";
                        $prices .= 'Rp ' . number_format($item['price'], 0, ',', '.') . "; ";
                    }
                    
                    fputcsv($file, [
                        $order->id,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->user->name ?? 'Guest',
                        $order->user->email ?? '-',
                        'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                        $order->status_label,
                        $order->payment_method ?? 'Unknown',
                        $order->payment_status_label,
                        rtrim($products, '; '),
                        rtrim($quantities, '; '),
                        rtrim($prices, '; ')
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error in ReportController@export: ' . $e->getMessage());

            // Return empty CSV with error message
            $callback = function() use ($e) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Error', 'Message']);
                fputcsv($file, ['Export Failed', $e->getMessage()]);
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="error_report.csv"'
            ]);
        }
    }

    /**
     * Export reports data as Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        try {
            // Get date range from request - if empty, use default range
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $reportType = $request->input('type', 'orders'); // orders, summary, products

            // Set default dates if not provided
            if (!$startDate || !$endDate) {
                $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }

            // Generate filename
            $filename = 'laporan_' . $reportType . '_' . $startDate . '_to_' . $endDate;

            // Create and download CSV file (Excel-compatible)
            $export = new ReportsExport($startDate, $endDate, $reportType);
            return $export->download($filename);

        } catch (\Exception $e) {
            Log::error('Error in ReportController@exportExcel: ' . $e->getMessage());

            // Return empty CSV with error message
            $callback = function() use ($e) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                fputcsv($file, ['Error', 'Message']);
                fputcsv($file, ['Excel Export Failed', $e->getMessage()]);
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="error_excel_export.csv"'
            ]);
        }
    }


    
    /**
     * Get order statistics for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getOrderStats(Carbon $startDate, Carbon $endDate)
    {
        // Log untuk debugging
        Log::info('getOrderStats dipanggil dengan rentang tanggal', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString()
        ]);

        // Count total orders in date range
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Calculate total revenue from valid orders (processing, shipping, delivered)
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'processing', 'shipping'])
            ->sum('total_amount');
        
        // Calculate average order value
        $averageOrder = $totalOrders > 0 
            ? Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['delivered', 'processing', 'shipping'])
                ->avg('total_amount') 
            : 0;
        
        // Hitung pendapatan untuk periode yang dipilih
        $periodRevenue = $totalRevenue; // Gunakan total revenue yang sudah dihitung
        
        // Log hasil perhitungan untuk debugging
        Log::info('Hasil perhitungan statistik pesanan', [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order' => $averageOrder,
            'period_revenue' => $periodRevenue
        ]);
        
        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order' => $averageOrder,
            'monthly_revenue' => $periodRevenue, // Tetap gunakan nama yang sama untuk kompatibilitas
            'period_label' => $this->getPeriodLabel($startDate, $endDate) // Label periode yang lebih deskriptif
        ];
    }
    
    /**
     * Generate a descriptive label for the selected date period
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    private function getPeriodLabel(Carbon $startDate, Carbon $endDate)
    {
        // Jika rentang 7 hari
        if ($startDate->diffInDays($endDate) == 6) {
            return '7 Hari Terakhir';
        }
        
        // Jika rentang 30 hari
        if ($startDate->diffInDays($endDate) == 29) {
            return '30 Hari Terakhir';
        }
        
        // Jika dalam bulan yang sama
        if ($startDate->format('Y-m') == $endDate->format('Y-m')) {
            return 'Bulan ' . $startDate->format('F Y');
        }
        
        // Jika dalam tahun yang sama
        if ($startDate->format('Y') == $endDate->format('Y')) {
            return $startDate->format('d M') . ' - ' . $endDate->format('d M Y');
        }
        
        // Default: tampilkan rentang lengkap
        return $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
    }
    
    /**
     * Get daily sales data for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getDailySales(Carbon $startDate, Carbon $endDate)
    {
        // Create date range array (not collection) to ensure all dates have data points
        $dateRange = [];
        $currentDate = clone $startDate;
        
        // Build date range array with zero values
        while ($currentDate <= $endDate) {
            $dateRange[] = [
                'date' => $currentDate->format('Y-m-d'),
                'formatted_date' => $currentDate->format('d M'),
                'total' => 0
            ];
            $currentDate->addDay();
        }
        
        // Get actual sales data
        $salesData = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('payment_method'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'processing', 'shipping'])
            ->groupBy('date', 'payment_method')
            ->get();
        
        // Get all payment methods used in the date range
        $paymentMethods = $salesData->pluck('payment_method')->unique()->filter()->values();
        
        // Store payment method totals by date
        $paymentDataByDate = [];
        
        // Initialize data structure
        foreach ($dateRange as $dateItem) {
            $paymentDataByDate[$dateItem['date']] = [
                'total' => 0,
                'payment_methods' => []
            ];
            
            // Initialize each payment method with zero
            foreach ($paymentMethods as $method) {
                $paymentDataByDate[$dateItem['date']]['payment_methods'][$method] = 0;
            }
        }
        
        // Merge actual data with the date range
        foreach ($salesData as $sale) {
            if (isset($paymentDataByDate[$sale->date])) {
                // Add to total
                $paymentDataByDate[$sale->date]['total'] += (float) $sale->total;
                
                // Add to payment method total if method exists
                if (!empty($sale->payment_method)) {
                    $paymentDataByDate[$sale->date]['payment_methods'][$sale->payment_method] = (float) $sale->total;
                }
            }
        }
        
        // Update the date range with the totals
        foreach ($dateRange as $key => $dateItem) {
            if (isset($paymentDataByDate[$dateItem['date']])) {
                $dateRange[$key]['total'] = $paymentDataByDate[$dateItem['date']]['total'];
                $dateRange[$key]['payment_methods'] = $paymentDataByDate[$dateItem['date']]['payment_methods'];
            }
        }
        
        // Return as collection after all modifications are done
        return collect($dateRange);
    }
    
    /**
     * Get payment methods distribution for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getPaymentMethods(Carbon $startDate, Carbon $endDate)
    {
        try {
            // Get data from orders
            $paymentData = DB::table('orders')
                ->select(
                    'payment_method as method',
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->orderBy('count', 'desc')
                ->get();
                
            // If no data, provide default data
            if ($paymentData->isEmpty()) {
                return collect([
                    ['method' => 'bank_transfer', 'count' => 0],
                    ['method' => 'qris', 'count' => 0]
                ]);
            }
                
            return $paymentData;
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getPaymentMethods: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default data on error
            return collect([
                ['method' => 'bank_transfer', 'count' => 0],
                ['method' => 'qris', 'count' => 0]
            ]);
        }
    }
    
    /**
     * Get top selling products for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getTopProducts(Carbon $startDate, Carbon $endDate, $limit = 10)
    {
        try {
        // Get all products
        $allProducts = Product::all();
        
        // Get orders in the date range
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['delivered', 'processing', 'shipping'])
            ->get();
        
            // Initialize an array (not a collection) to track product sales
            $productSalesArray = [];
        
            // Populate initial sales data
        foreach ($allProducts as $product) {
                $productSalesArray[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity_sold' => 0,
                'total_sales' => 0,
                'product' => $product,
                ];
        }
        
        // Analyze each order to count product sales
        foreach ($orders as $order) {
                // Get formatted order items that handles both JSON and relationships
                $orderItems = $order->getFormattedItems();
            
                if (count($orderItems) > 0) {
                    // Process each item
                foreach ($orderItems as $item) {
                    $productId = $item['product_id'] ?? 0;
                    
                        // If this product is in our tracking array
                        if (isset($productSalesArray[$productId])) {
                            // Get item data
                        $quantity = $item['quantity'] ?? 0;
                        $price = $item['price'] ?? 0;
                        
                            // Update sales data in our array
                            $productSalesArray[$productId]['quantity_sold'] += $quantity;
                            $productSalesArray[$productId]['total_sales'] += ($quantity * $price);
                    }
                }
            } else {
                    // Fallback: If no items found, assign to first product as before
                    if (!empty($productSalesArray) && $order->total_amount > 0) {
                        // Get first product ID
                        $firstProductId = array_key_first($productSalesArray);
                        if ($firstProductId) {
                            $productSalesArray[$firstProductId]['quantity_sold'] += 1;
                            $productSalesArray[$firstProductId]['total_sales'] += $order->total_amount;
                        }
                }
            }
        }
        
            // Convert to collection, sort, and return top products
            $productSalesCollection = collect(array_values($productSalesArray));
            return $productSalesCollection->sortByDesc('quantity_sold')
            ->take($limit)
            ->values();
                
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getTopProducts: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return collect(); // Return empty collection if error
        }
    }

    /**
     * Get monthly payment methods statistics for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getMonthlyPaymentMethods(Carbon $startDate, Carbon $endDate)
    {
        try {
            // Create month range array
            $monthRange = [];
            $currentDate = clone $startDate->startOfMonth();
            $endMonthDate = clone $endDate->startOfMonth();
            
            // Build month range array
            while ($currentDate <= $endMonthDate) {
                $monthRange[] = [
                    'month' => $currentDate->format('Y-m'),
                    'month_name' => $currentDate->format('M Y'),
                    'payment_methods' => []
                ];
                $currentDate->addMonth();
            }
            
            // Get payment methods data by month
            $paymentData = DB::table('orders')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    'payment_method',
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('payment_method')
                ->groupBy('month', 'payment_method')
                ->orderBy('month')
                ->get();
            
            // Get all unique payment methods
            $allPaymentMethods = $paymentData->pluck('payment_method')->unique()->values();
            
            // If no payment methods found, add a dummy one to avoid empty charts
            if ($allPaymentMethods->isEmpty()) {
                $allPaymentMethods = collect(['bank_transfer']);
            }
            
            // Initialize payment methods for each month
            foreach ($monthRange as &$month) {
                foreach ($allPaymentMethods as $method) {
                    $month['payment_methods'][$method] = 0;
                }
            }
            
            // Fill in actual data
            foreach ($paymentData as $data) {
                // Find the month in our range
                $monthKey = array_search($data->month, array_column($monthRange, 'month'));
                
                if ($monthKey !== false) {
                    // Add the count to the month's payment method
                    $monthRange[$monthKey]['payment_methods'][$data->payment_method] = $data->count;
                }
            }
            
            // Log for debugging
            Log::info('Monthly payment methods data generated', [
                'months_count' => count($monthRange),
                'payment_methods' => $allPaymentMethods->toArray(),
                'has_data' => !empty($monthRange)
            ]);
            
            return [
                'months' => collect($monthRange)->pluck('month_name'),
                'payment_methods' => $allPaymentMethods,
                'data' => collect($monthRange)
            ];
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getMonthlyPaymentMethods: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a valid empty structure instead of just an empty collection
            return [
                'months' => collect(['Jan', 'Feb', 'Mar']),
                'payment_methods' => collect(['bank_transfer']),
                'data' => collect([
                    ['month' => '2023-01', 'month_name' => 'Jan', 'payment_methods' => ['bank_transfer' => 0]],
                    ['month' => '2023-02', 'month_name' => 'Feb', 'payment_methods' => ['bank_transfer' => 0]],
                    ['month' => '2023-03', 'month_name' => 'Mar', 'payment_methods' => ['bank_transfer' => 0]]
                ])
            ];
        }
    }
    
    /**
     * Get customer payment method statistics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getCustomerPaymentMethods(Carbon $startDate, Carbon $endDate)
    {
        try {
            return DB::table('orders')
                ->select(
                    'user_id',
                    'payment_method',
                    DB::raw('COUNT(*) as count')
                )
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->whereNotNull('payment_method')
                ->groupBy('user_id', 'payment_method')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    // Get user name
                    $user = DB::table('users')->where('id', $item->user_id)->first();
                    $item->user_name = $user ? $user->name : 'Unknown';
                    return $item;
                });
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getCustomerPaymentMethods: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Get sales data for the dynamic chart
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getSalesChartData(Carbon $startDate, Carbon $endDate)
    {
        try {
            // Create date range array
            $dateRange = [];
            $currentDate = clone $startDate;
            
            // Build date range array with zero values
            while ($currentDate <= $endDate) {
                $dateRange[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'formatted_date' => $currentDate->format('d M'),
                    'total' => 0,
                    'order_count' => 0,
                    'status_counts' => [
                        'processing' => 0,
                        'shipping' => 0,
                        'delivered' => 0,
                        'cancelled' => 0,
                        'waiting_for_payment' => 0
                    ]
                ];
                $currentDate->addDay();
            }
            
            // Get sales data grouped by date and status
            $salesData = DB::table('orders')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    'status',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_amount')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get();
                
            // Get all statuses used in the date range
            $statuses = $salesData->pluck('status')->unique()->filter()->values();
            
            // Store data by date
            $dataByDate = [];
            
            // Initialize data structure
            foreach ($dateRange as $dateItem) {
                $dataByDate[$dateItem['date']] = [
                    'total' => 0,
                    'order_count' => 0,
                    'status_counts' => [
                        'processing' => 0,
                        'shipping' => 0,
                        'delivered' => 0,
                        'cancelled' => 0,
                        'waiting_for_payment' => 0
                    ]
                ];
            }
            
            // Merge actual data with the date range
            foreach ($salesData as $sale) {
                if (isset($dataByDate[$sale->date])) {
                    // Add to total
                    $dataByDate[$sale->date]['total'] += (float) $sale->total_amount;
                    $dataByDate[$sale->date]['order_count'] += (int) $sale->order_count;
                    
                    // Add to status count if status exists
                    if (!empty($sale->status) && isset($dataByDate[$sale->date]['status_counts'][$sale->status])) {
                        $dataByDate[$sale->date]['status_counts'][$sale->status] = (int) $sale->order_count;
                    }
                }
            }
            
            // Update the date range with the totals
            foreach ($dateRange as $key => $dateItem) {
                if (isset($dataByDate[$dateItem['date']])) {
                    $dateRange[$key]['total'] = $dataByDate[$dateItem['date']]['total'];
                    $dateRange[$key]['order_count'] = $dataByDate[$dateItem['date']]['order_count'];
                    $dateRange[$key]['status_counts'] = $dataByDate[$dateItem['date']]['status_counts'];
                }
            }
            
            // Ensure we have at least one date in the range
            if (empty($dateRange)) {
                $today = Carbon::now();
                $dateRange[] = [
                    'date' => $today->format('Y-m-d'),
                    'formatted_date' => $today->format('d M'),
                    'total' => 0,
                    'order_count' => 0,
                    'status_counts' => [
                        'processing' => 0,
                        'shipping' => 0,
                        'delivered' => 0,
                        'cancelled' => 0,
                        'waiting_for_payment' => 0
                    ]
                ];
            }
            
            // Prepare datasets for chart
            $datasets = [
                [
                    'label' => 'Total Penjualan',
                    'data' => collect($dateRange)->pluck('total')->toArray(),
                    'type' => 'line',
                    'borderColor' => '#FF1493',
                    'backgroundColor' => 'rgba(255, 20, 147, 0.1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                    'order' => 0
                ],
                [
                    'label' => 'Jumlah Pesanan',
                    'data' => collect($dateRange)->pluck('order_count')->toArray(),
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(147, 94, 183, 0.6)',
                    'borderColor' => '#935EB7',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1',
                    'order' => 1
                ]
            ];
            
            // Add datasets for each status
            $statusColors = [
                'processing' => ['#17A2B8', 'rgba(23, 162, 184, 0.6)'],
                'shipping' => ['#9C27B0', 'rgba(156, 39, 176, 0.6)'],
                'delivered' => ['#28A745', 'rgba(40, 167, 69, 0.6)'],
                'cancelled' => ['#DC3545', 'rgba(220, 53, 69, 0.6)'],
                'waiting_for_payment' => ['#FFC107', 'rgba(255, 193, 7, 0.6)']
            ];
            
            foreach ($statuses as $status) {
                $statusData = collect($dateRange)->map(function($item) use ($status) {
                    return $item['status_counts'][$status] ?? 0;
                })->toArray();
                
                $color = $statusColors[$status] ?? ['#777777', 'rgba(119, 119, 119, 0.6)'];
                
                $datasets[] = [
                    'label' => ucfirst(str_replace('_', ' ', $status)),
                    'data' => $statusData,
                    'type' => 'bar',
                    'backgroundColor' => $color[1],
                    'borderColor' => $color[0],
                    'borderWidth' => 1,
                    'stack' => 'status',
                    'yAxisID' => 'y1',
                    'hidden' => true,
                    'order' => 2
                ];
            }
            
            return [
                'labels' => collect($dateRange)->pluck('formatted_date')->toArray(),
                'datasets' => $datasets
            ];
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getSalesChartData: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default structure on error
            return [
                'labels' => ['No Data'],
                'datasets' => [
                    [
                        'label' => 'Total Penjualan',
                        'data' => [0],
                        'type' => 'line',
                        'borderColor' => '#FF1493',
                        'backgroundColor' => 'rgba(255, 20, 147, 0.1)',
                        'fill' => false
                    ]
                ]
            ];
        }
    }

    /**
     * Get monthly revenue data for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getMonthlyRevenueData(Carbon $startDate, Carbon $endDate)
    {
        try {
            // Extend the start date to the beginning of the month
            $startMonthDate = clone $startDate->startOfMonth();
            $endMonthDate = clone $endDate->endOfMonth();
            
            // Log date ranges for debugging
            Log::info('Monthly revenue date range', [
                'original_range' => [$startDate->toDateString(), $endDate->toDateString()],
                'month_range' => [$startMonthDate->toDateString(), $endMonthDate->toDateString()]
            ]);
            
            // Get monthly revenue data
            $monthlyData = DB::table('orders')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('DATE_FORMAT(created_at, "%b %Y") as month_name'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('COUNT(*) as order_count')
                )
                ->whereBetween('created_at', [$startMonthDate, $endMonthDate])
                ->whereIn('status', ['delivered', 'processing', 'shipping'])
                ->groupBy('month', 'month_name')
                ->orderBy('month')
                ->get();
            
            // Create month range array to ensure all months have data points
            $monthRange = [];
            $currentDate = clone $startMonthDate;
            
            // Build month range array with zero values
            while ($currentDate <= $endMonthDate) {
                $monthKey = $currentDate->format('Y-m');
                $monthName = $currentDate->format('M Y');
                
                $monthRange[$monthKey] = [
                    'month' => $monthKey,
                    'month_name' => $monthName,
                    'total_revenue' => 0,
                    'order_count' => 0
                ];
                
                $currentDate->addMonth();
            }
            
            // Fill in actual data
            foreach ($monthlyData as $data) {
                if (isset($monthRange[$data->month])) {
                    $monthRange[$data->month]['total_revenue'] = (float) $data->total_revenue;
                    $monthRange[$data->month]['order_count'] = (int) $data->order_count;
                }
            }
            
            // Ensure we have at least one month in the range
            if (empty($monthRange)) {
                $today = Carbon::now();
                $monthKey = $today->format('Y-m');
                $monthName = $today->format('M Y');
                
                $monthRange[$monthKey] = [
                    'month' => $monthKey,
                    'month_name' => $monthName,
                    'total_revenue' => 0,
                    'order_count' => 0
                ];
            }
            
            // Convert to indexed array
            $monthRangeArray = array_values($monthRange);
            
            // Prepare chart data
            $chartData = [
                'labels' => collect($monthRangeArray)->pluck('month_name')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Pendapatan Bulanan',
                        'data' => collect($monthRangeArray)->pluck('total_revenue')->toArray(),
                        'backgroundColor' => 'rgba(212, 106, 159, 0.2)',
                        'borderColor' => '#D46A9F',
                        'borderWidth' => 2,
                        'tension' => 0.3,
                        'fill' => true
                    ],
                    [
                        'label' => 'Jumlah Pesanan',
                        'data' => collect($monthRangeArray)->pluck('order_count')->toArray(),
                        'backgroundColor' => 'rgba(147, 94, 183, 0.7)',
                        'borderColor' => '#935EB7',
                        'borderWidth' => 2,
                        'type' => 'bar',
                        'yAxisID' => 'y1'
                    ]
                ]
            ];
            
            return [
                'data' => collect($monthRangeArray),
                'chartData' => $chartData
            ];
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getMonthlyRevenueData: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a valid empty structure
            return [
                'data' => collect([]),
                'chartData' => [
                    'labels' => ['Jan', 'Feb', 'Mar'],
                    'datasets' => [
                        [
                            'label' => 'Pendapatan Bulanan',
                            'data' => [0, 0, 0],
                            'backgroundColor' => 'rgba(212, 106, 159, 0.2)',
                            'borderColor' => '#D46A9F',
                            'borderWidth' => 2
                        ],
                        [
                            'label' => 'Jumlah Pesanan',
                            'data' => [0, 0, 0],
                            'backgroundColor' => 'rgba(147, 94, 183, 0.7)',
                            'borderColor' => '#935EB7',
                            'borderWidth' => 2,
                            'type' => 'bar',
                            'yAxisID' => 'y1'
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Get order status distribution for the specified date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getOrderStatusDistribution(Carbon $startDate, Carbon $endDate)
    {
        try {
            // Get order status counts
            $statusData = DB::table('orders')
                ->select(
                    'status',
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('status')
                ->groupBy('status')
                ->get();
            
            // Define status labels and colors
            $statusLabels = [
                'waiting_for_payment' => 'Menunggu Pembayaran',
                'processing' => 'Diproses',
                'shipping' => 'Pengiriman',
                'delivered' => 'Selesai',
                'cancelled' => 'Dibatalkan'
            ];
            
            $statusColors = [
                'waiting_for_payment' => '#FFC107', // Yellow
                'processing' => '#17A2B8', // Blue
                'shipping' => '#9C27B0', // Purple
                'delivered' => '#28A745', // Green
                'cancelled' => '#DC3545' // Red
            ];
            
            // Prepare data for chart
            $labels = [];
            $data = [];
            $colors = [];
            
            // Ensure all statuses are represented even if count is 0
            $allStatuses = array_keys($statusLabels);
            $statusCounts = [];
            
            foreach ($allStatuses as $status) {
                $statusCounts[$status] = 0;
            }
            
            // Fill in actual counts
            foreach ($statusData as $item) {
                if (isset($statusCounts[$item->status])) {
                    $statusCounts[$item->status] = $item->count;
                }
            }
            
            // Build chart data arrays
            foreach ($statusCounts as $status => $count) {
                $labels[] = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                $data[] = $count;
                $colors[] = $statusColors[$status] ?? '#777777';
            }
            
            return [
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors
            ];
        } catch (\Exception $e) {
            Log::error('Error in ReportController@getOrderStatusDistribution: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default data on error
            return [
                'labels' => ['Menunggu Pembayaran', 'Diproses', 'Pengiriman', 'Selesai', 'Dibatalkan'],
                'data' => [0, 0, 0, 0, 0],
                'colors' => ['#FFC107', '#17A2B8', '#9C27B0', '#28A745', '#DC3545']
            ];
        }
    }
} 