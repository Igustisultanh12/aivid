<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ruang-dharma:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat akun Administrator pertama untuk Ruang Dharma secara interaktif dan aman.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==========================================");
        $this->info("   Ruang Dharma - Pembuatan Akun Admin    ");
        $this->info("==========================================");

        $name = text(
            label: 'Nama Lengkap Admin:',
            placeholder: 'Contoh: I Gusti Sultan',
            required: true,
            validate: fn (string $val) => match (true) {
                strlen($val) < 3 => 'Nama minimal 3 karakter.',
                default => null,
            }
        );

        $email = text(
            label: 'Alamat Email Admin:',
            placeholder: 'admin@ruangdharma.id',
            required: true,
            validate: function (string $val) {
                $v = Validator::make(['email' => $val], ['email' => 'required|email|unique:users,email']);
                if ($v->fails()) {
                    return 'Email tidak valid atau sudah terdaftar.';
                }
                return null;
            }
        );

        $pass = password(
            label: 'Kata Sandi Admin (minimal 8 karakter):',
            required: true,
            validate: fn (string $val) => match (true) {
                strlen($val) < 8 => 'Kata sandi minimal 8 karakter.',
                default => null,
            }
        );

        $confirm = password(
            label: 'Konfirmasi Kata Sandi:',
            required: true,
            validate: fn (string $val) => match (true) {
                $val !== $pass => 'Konfirmasi kata sandi tidak cocok.',
                default => null,
            }
        );

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($pass),
            'role' => 'admin',
            'email_verified_at' => now(),
            'quota_total' => 999,
            'quota_used' => 0,
        ]);

        $this->newLine();
        info("Akun Administrator berhasil dibuat!");
        $this->line("Nama  : {$admin->name}");
        $this->line("Email : {$admin->email}");
        $this->line("Role  : {$admin->role}");
        $this->line("Status: Email terverifikasi");
        $this->newLine();

        return Command::SUCCESS;
    }
}
