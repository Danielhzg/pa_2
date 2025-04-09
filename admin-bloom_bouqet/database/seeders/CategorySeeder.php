use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            ['name' => 'Wisuda', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Makanan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uang', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hampers', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
