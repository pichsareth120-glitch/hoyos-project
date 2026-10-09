<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'personal');
        $addresses = $user->addresses()->latest()->get();
        $orders = $user->orders()->with(['items.variant.product.primaryImage', 'shipment', 'latestPayment'])->latest()->get();

        return view('account.index', compact('user', 'tab', 'addresses', 'orders'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'nullable|string|max:50',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'birth_day' => 'nullable|numeric|min:1|max:31',
            'birth_month' => 'nullable|numeric|min:1|max:12',
            'birth_year' => 'nullable|numeric|min:1920|max:' . date('Y'),
        ]);

        $hasAnyDob = !empty($validated['birth_year']) || !empty($validated['birth_month']) || !empty($validated['birth_day']);
        $hasAllDob = !empty($validated['birth_year']) && !empty($validated['birth_month']) && !empty($validated['birth_day']);

        $dob = null;
        if ($hasAnyDob) {
            if (!$hasAllDob) {
                return back()->withInput()->withErrors([
                    'birth_day' => 'Please provide complete day, month, and year for date of birth.',
                ]);
            }

            if (!checkdate((int) $validated['birth_month'], (int) $validated['birth_day'], (int) $validated['birth_year'])) {
                return back()->withInput()->withErrors([
                    'birth_day' => 'The provided date of birth is invalid for the calendar.',
                ]);
            }

            $dob = sprintf('%04d-%02d-%02d', $validated['birth_year'], $validated['birth_month'], $validated['birth_day']);
        }

        $emailChanged = $user->email !== $validated['email'];

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (array_key_exists('title', $validated)) {
            $updates['title'] = $validated['title'];
        }
        if (array_key_exists('first_name', $validated)) {
            $updates['first_name'] = $validated['first_name'];
        }
        if (array_key_exists('last_name', $validated)) {
            $updates['last_name'] = $validated['last_name'];
        }
        if (array_key_exists('phone', $validated)) {
            $updates['phone'] = $validated['phone'];
        }
        if ($hasAnyDob || array_key_exists('birth_day', $validated) || array_key_exists('birth_month', $validated) || array_key_exists('birth_year', $validated)) {
            $updates['date_of_birth'] = $dob;
        }

        $user->update($updates);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
        }

        return redirect()->route('account.index', ['tab' => 'personal'])
            ->with('success', 'Personal information updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('account.index', ['tab' => 'personal'])
            ->with('success', 'Password updated successfully.');
    }

    public function storeAddress(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:30',
            'province' => 'required|string|max:100',
            'address_line' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = !empty($validated['is_default']) || $user->addresses()->count() === 0;

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create([
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'province' => $validated['province'],
            'address_line' => $validated['address_line'],
            'notes' => $validated['notes'] ?? null,
            'is_default' => $isDefault,
        ]);

        return redirect()->route('account.index', ['tab' => 'addresses'])
            ->with('success', 'Address added successfully.');
    }

    public function destroyAddress(Address $address)
    {
        $user = Auth::user();

        if ($address->user_id !== $user->id) {
            abort(403);
        }

        $address->delete();

        // If default address was deleted, make the latest one default
        if ($address->is_default) {
            $next = $user->addresses()->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()->route('account.index', ['tab' => 'addresses'])
            ->with('success', 'Address deleted successfully.');
    }
}
