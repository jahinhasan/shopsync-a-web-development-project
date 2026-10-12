<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;



class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';
    protected $description = 'Create anadminstarator account';
    public function handle(): int
    {
        $name = trim($this->ask('Admin name') ?? '');
        $email = strtolower(trim($this->ask('Admin email') ?? ''));
        $password =$this->secret('admin password');

        $validator = Validator::make(compact('name','email','password'),[
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required','email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')],
            'password' => ['required','string', 'min:8'],
        ]);

        if($validator->fails()){
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'admin';
        $user->email_verified_at = now();
        $user->save();

        $this->info('Admin account created successfully.');
        return self::SUCCESS;
    }
}
