<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{
    /**
     * Base API URL
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiBaseUrl = config('app.url') . '/api/v1';
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        try {
            // Prepare API request parameters
            $queryParams = [];
            if ($request->has('search')) {
                $queryParams['search'] = $request->search;
            }
            
            // Call the API endpoint
            $response = Http::get($this->apiBaseUrl . '/customers', $queryParams);
            
            if (!$response->successful()) {
                Log::error('API request failed: ' . $response->body());
                return back()->with('error', 'Terjadi kesalahan saat memuat data pelanggan. Silakan coba lagi nanti.');
            }
            
            $data = $response->json();
            
            if (!isset($data['data']) || !isset($data['success']) || $data['success'] !== true) {
                Log::error('API response format invalid: ' . json_encode($data));
                return back()->with('error', 'Format respons API tidak valid.');
            }
            
            $customers = $data['data'];
            
            // Wrap response data in paginator for compatibility with the view
            $customers = new \Illuminate\Pagination\LengthAwarePaginator(
                $customers['data'],
                $customers['total'],
                $customers['per_page'],
                $customers['current_page'],
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            // Get customer statistics for dashboard widgets
            $statsResponse = Http::get($this->apiBaseUrl . '/customers-stats');
            $statistics = null;
            
            if ($statsResponse->successful()) {
                $statsData = $statsResponse->json();
                if (isset($statsData['success']) && $statsData['success'] === true && isset($statsData['data'])) {
                    $statistics = $statsData['data'];
                } else {
                    Log::warning('Invalid stats response format: ' . json_encode($statsData));
                }
            } else {
                Log::warning('Failed to retrieve customer statistics: ' . $statsResponse->body());
            }
            
            return view('admin.customers.index', compact('customers', 'statistics'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat data pelanggan: ' . $e->getMessage());
        }
    }

    /**
     * Display customer details
     */
    public function show($id)
    {
        try {
            // Call the API endpoint to get customer details
            $response = Http::get($this->apiBaseUrl . '/customers/' . $id);
            
            if (!$response->successful()) {
                Log::error('API request failed: ' . $response->body());
                return back()->with('error', 'Terjadi kesalahan saat memuat detail pelanggan. Silakan coba lagi nanti.');
            }
            
            $responseData = $response->json();
            
            if (!isset($responseData['data']) || !isset($responseData['success']) || $responseData['success'] !== true) {
                Log::error('API response format invalid: ' . json_encode($responseData));
                return back()->with('error', 'Format respons API tidak valid.');
            }
            
            $data = $responseData['data'];
            
            if (!isset($data['customer']) || !isset($data['stats']) || !isset($data['orders'])) {
                Log::error('Customer data is incomplete: ' . json_encode($data));
                return back()->with('error', 'Data pelanggan tidak lengkap.');
            }
            
            $customer = $data['customer'];
            $stats = $data['stats'];
            
            // Wrap orders in paginator for compatibility with the view
            $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                $data['orders']['data'],
                $data['orders']['total'],
                $data['orders']['per_page'],
                $data['orders']['current_page'],
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            $monthlyStats = $data['monthly_stats'] ?? [];
            
            return view('admin.customers.show', compact('customer', 'orders', 'stats', 'monthlyStats'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@show: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail pelanggan: ' . $e->getMessage());
        }
    }

    /**
     * Export customers data
     */
    public function export(Request $request)
    {
        try {
            // To be implemented - export customer data to CSV/Excel
            return redirect()->route('admin.customers.index')
                ->with('error', 'Fitur export data pelanggan belum tersedia.');
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@export: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mengekspor data pelanggan.');
        }
    }
} 