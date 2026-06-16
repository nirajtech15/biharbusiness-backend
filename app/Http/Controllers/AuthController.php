<?php

namespace App\Http\Controllers;
use App\Models\OtpLog;
use App\Services\SmsService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function showLoginForm() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    public function showRegisterForm() { return view('auth.register'); }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'mobile' => 'required|string|max:15|unique:users',
            'email' => 'required|email|max:150|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'city' => 'nullable|string|max:100',
            'role' => 'nullable|in:customer,business_owner',
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
    public function sendOtp(Request $request, SmsService $smsService)
{
    $request->validate([
        'mobile' => 'required|string|max:15',
    ]);

    $mobile = $request->mobile;
    $otp = rand(100000, 999999);

    OtpLog::updateOrCreate(
        ['mobile' => $mobile],
        [
            'otp' => $otp,
            'expiry' => now()->addMinutes(5),
            'used' => 0,
        ]
    );

    session(['otp_mobile' => $mobile]);

    $result = $smsService->sendOtp($mobile, (string) $otp);

    if (!$result['success']) {
        return back()->with('error', $result['message']);
    }

    return redirect()->route('otp.verify.form')->with('success', $result['message']);
}
public function appRegister(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:100',
        'mobile' => 'required|string|max:15|unique:users,mobile',
        'email' => 'nullable|email|max:150|unique:users,email',
        'password' => 'required|string|min:6',
        'city' => 'nullable|string|max:100',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'mobile' => $validated['mobile'],
        'email' => $validated['email'] ?? null,
        'password' => Hash::make($validated['password']),
        'city' => $validated['city'] ?? null,
        'role' => 'business_owner',
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'city' => $user->city,
            'role' => $user->role,
        ],
    ]);
}

public function appLogin(Request $request)
{
    $validated = $request->validate([
        'mobile' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('mobile', $validated['mobile'])->first();

    if (!$user || !Hash::check($validated['password'], $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid mobile or password',
        ], 401);
    }

    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'city' => $user->city ?? null,
            'role' => $user->role ?? 'customer',
        ],
    ]);
}
}
