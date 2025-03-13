namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ...existing code...

    public function showLoginForm()
    {
        return view('auth.login'); // Ensure this view exists
    }

    // ...existing code...
}
