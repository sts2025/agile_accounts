<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the password for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('What is the email of the user you want to update?');
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('No user found with that email address.');
            return 1;
        }

        $password = $this->secret('Enter the new password');
        
        $user->password = Hash::make($password);
        $user->save();

        $this->info('The password has been reset successfully!');
        return 0;
    }
}