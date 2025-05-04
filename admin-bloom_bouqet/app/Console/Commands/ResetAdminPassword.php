<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset or create admin account';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = 'admin@gmail.com';
        $password = 'Adminbloom';
        
        // Check if admin exists
        $admin = Admin::where('email', $email)->first();
        
        if ($admin) {
            // Update existing admin
            $admin->password = Hash::make($password);
            $admin->save();
            $this->info('Admin password reset successfully');
        } else {
            // Create new admin
            Admin::create([
                'username' => 'Admin' . rand(1000, 9999),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->info('New admin created successfully');
        }
        
        $this->info('Login with:');
        $this->info("Email: $email");
        $this->info("Password: $password");
        
        return Command::SUCCESS;
    }
} 