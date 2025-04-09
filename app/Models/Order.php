namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'total_price', 'status', 'created_at', 'updated_at'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
