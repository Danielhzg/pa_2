<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Sales Summary
            $salesSummary = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as average_order_value')
                )
                ->first();

            // Payment Methods Summary
            $paymentMethods = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('payment_method')
                ->get();

            // Daily Sales
            $dailySales = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top Products
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->select(
                    'order_items.product_id',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
                )
                ->groupBy('order_items.product_id')
                ->orderBy('total_quantity', 'desc')
                ->limit(10)
                ->get();

            return view('admin.reports.index', compact(
                'salesSummary',
                'paymentMethods',
                'dailySales',
                'topProducts',
                'startDate',
                'endDate'
            ));
        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate report. Please try again.');
        }
    }

    public function export(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $orders = DB::table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->select(
                    'orders.id',
                    'orders.order_id',
                    'users.name as customer_name',
                    'users.email as customer_email',
                    'orders.total_amount',
                    'orders.payment_method',
                    'orders.status',
                    'orders.payment_status',
                    'orders.created_at'
                )
                ->get();

            // Generate CSV content
            $csv = "Order ID,Customer Name,Email,Total Amount,Payment Method,Status,Payment Status,Date\n";
            
            foreach ($orders as $order) {
                $csv .= implode(',', [
                    $order->order_id,
                    $order->customer_name,
                    $order->customer_email,
                    $order->total_amount,
                    $order->payment_method,
                    $order->status,
                    $order->payment_status,
                    $order->created_at
                ]) . "\n";
            }

            $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
            
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename={$filename}");
        } catch (\Exception $e) {
            Log::error('Error exporting report: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export report. Please try again.');
        }
    }
} 