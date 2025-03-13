use App\Http\Controllers\AdminController;

// ...existing code...

Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');

// ...existing code...