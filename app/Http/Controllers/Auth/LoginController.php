<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        Log::info('Percobaan Login:', ['username' => $request->username]);

        try {
            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);

            $userCheck = DB::table('user')
                ->whereRaw("CAST(AES_DECRYPT(id_user, 'nur') AS CHAR) = ?", [$request->username])
                ->whereRaw("CAST(AES_DECRYPT(password, 'windi') AS CHAR) = ?", [$request->password])
                ->first();

            if ($userCheck) {
                Log::info('Login User Berhasil');
                $user = User::where('id_user', $userCheck->id_user)->first();
                if ($user) {
                    Auth::login($user);
                    session([
                        'role' => 'user',
                        'decrypted_id' => $request->username,
                        'jenis_poli' => Jadwal::where('kd_dokter', $request->username)->value('kd_poli')
                    ]);
                    return redirect()->intended('/dashboard');
                }
            }

            Log::info('Mencoba cek tabel Admin...');
            
            $adminCheck = DB::table('admin')
                ->whereRaw("CAST(AES_DECRYPT(usere, 'nur') AS CHAR) = ?", [$request->username])
                ->whereRaw("CAST(AES_DECRYPT(passworde, 'windi') AS CHAR) = ?", [$request->password])
                ->first();

            if ($adminCheck) {
                Log::info('Login Admin Berhasil');
                session([
                    'role' => 'admin',
                    'login_admin' => true,
                    'decrypted_id' => $request->username,
                    'nama_lengkap' => 'Administrator'
                ]);
                return redirect()->intended('/dashboard');
            }

            Log::warning('Login Gagal: Data tidak ditemukan di user maupun admin');
            return back()->withErrors(['username' => 'Username atau Password salah!']);

        } catch (\Exception $e) {
            Log::error('Error Login: ' . $e->getMessage());
            return back()->withErrors(['username' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();
        return redirect('/login');
    }
}