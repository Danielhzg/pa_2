    @extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4 fw-bold">Welcome Back, Admin! 👋</h2>
    
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary mb-2">Total Pesanan</h6>
                            <h3 class="mb-0 fw-bold">150</h3>
                            <p class="text-success mb-0 mt-2">
                                <i class="fas fa-arrow-up"></i> +12.5%
                            </p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag text-white fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary mb-2">Total Produk</h6>
                            <h3 class="mb-0 fw-bold">45</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-box text-white fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary mb-2">Pelanggan</h6>
                            <h3 class="mb-0 fw-bold">89</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users text-white fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary mb-2">Pendapatan</h6>
                            <h3 class="mb-0 fw-bold">Rp 5.240.000</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign text-white fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Penjualan Mingguan</h5>
                        <select class="form-select form-select-sm w-auto">
                            <option>7 Hari Terakhir</option>
                            <option>30 Hari Terakhir</option>
                        </select>
                    </div>
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Produk Terlaris</h5>
                    <canvas id="productsChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sales Chart
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        datasets: [{
            label: 'Penjualan',
            data: [65, 59, 80, 81, 56, 55, 40],
            borderColor: '#7e57c2',
            backgroundColor: 'rgba(126, 87, 194, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                },
                ticks: {
                    color: '#6c757d'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6c757d'
                }
            }
        }
    }
});

// Products Chart
new Chart(document.getElementById('productsChart'), {
    type: 'doughnut',
    data: {
        labels: ['Bucket Mawar', 'Bucket Lily', 'Bucket Mix'],
        datasets: [{
            data: [45, 32, 23],
            backgroundColor: ['#7e57c2', '#b085f5', '#d4bffc']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#6c757d'
                }
            }
        }
    }
});
</script>
@endsection