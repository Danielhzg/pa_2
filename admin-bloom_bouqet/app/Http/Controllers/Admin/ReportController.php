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
            // Set default date range if not provided
            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            
            // Convert to Carbon instances for queries
            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();
            
            // Get order statistics
            $orderStats = $this->getOrderStats($startDateTime, $endDateTime);
            
            // Get daily sales data
            $dailySales = $this->getDailySales($startDateTime, $endDateTime);
            
            // Get payment methods distribution
            $paymentMethods = $this->getPaymentMethods($startDateTime, $endDateTime);
            
            // Get top selling products
            $topProducts = $this->getTopProducts($startDateTime, $endDateTime, 10);
            
            return view('admin.reports.index', compact(
                'orderStats',
                'dailySales', 
                'paymentMethods', 
                'topProducts'
            ));
        } catch (\Exception $e) {
            Log::error('Error in ReportController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat laporan: ' . $e->getMessage());
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
            // Set default date range if not provided
            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            
            // Convert to Carbon instances for queries
            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();
            
            // Get orders for the date range
            $orders = Order::with(['user', 'items.product'])
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
                    
                    foreach ($order->items as $item) {
                        $products .= $item->product->name . "; ";
                        $quantities .= $item->quantity . "; ";
                        $prices .= 'Rp ' . number_format($item->price, 0, ',', '.') . "; ";
                    }
                    
                    fputcsv($file, [
                        $order->id,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->user->name ?? 'Guest',
                        $order->user->email ?? '-',
                        'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                        $order->status,
                        $order->payment_method,
                        $order->payment_status,
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
            return back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
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
        // Count total orders in date range
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Calculate total revenue from completed and processing orders
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'processing'])
            ->sum('total_amount');
        
        // Calculate average order value
        $averageOrder = $totalOrders > 0 
            ? Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['completed', 'processing'])
                ->avg('total_amount') 
            : 0;
        
        // Count active orders (pending and processing)
        $activeOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'processing'])
            ->count();
        
        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order' => $averageOrder,
            'active_orders' => $activeOrders
        ];
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
        return DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'processing'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
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
        return DB::table('orders')
            ->select(
                'payment_method as method',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->orderBy('count', 'desc')
            ->get();
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
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.image',
                'products.price',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_sales')
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereIn('orders.status', ['completed', 'processing'])
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price')
            ->orderBy('quantity_sold', 'desc')
            ->limit($limit)
            ->get();
    }
} 