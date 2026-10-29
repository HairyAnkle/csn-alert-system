<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegisteredUserController extends Controller
{
    /**
     * Public self-registration is disabled for the Web Command Center.
     * Web portal accounts are provisioned exclusively by Superadmin via signed invitations.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Public registration is disabled. Web Command Center access is restricted to authorized personnel.');
    }

    /**
     * Reject any incoming self-registration request.
     */
    public function store(): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Public registration is disabled. Web Command Center access is restricted to authorized personnel.');
    }
}
