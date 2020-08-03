<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use LdapRecord\Models\ActiveDirectory\User AS LdapUser;
use App\Ldap\Rules\HasIdCard;

class UserSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:ADMIN');
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $search = null;
        if (preg_match("/^\d+$/", $request->userSearch)) {
            // Search by ID
            $search = str_pad($request->userSearch, 7, "0", STR_PAD_LEFT);
            $user = User::where('erp_id', $search)->first();
        } else {
            // Search by username
            $search = $request->userSearch;
            $user = User::where('username', $search)->first();
        }

        if ($user) {
            if ($request->expectsJson()) {
                $roles = $user->roles ? $user->roles : [];
                return response()->json([
                    "id" => $user->id,
                    "erp_id" => $user->erp_id,
                    "name" => $user->name,
                    "username" => $user->username,
                    "admin" => in_array("ADMIN", $roles),
                    "manager" => in_array("MANAGER", $roles),
                    "override" => in_array("OVERRIDE", $roles)
                ]);
            }

            return redirect()->route('users.index');
        }

        // Search AD for valid user
        if (preg_match("/^\d+$/", $request->userSearch)) {
            // Search by ID
            $user = LdapUser::where('employeeID', '=', $search)->first();
        } else {
            // Search by username
            $user = LdapUser::where('sAMAccountName', '=', $search)->first();
        }

        if ($user && ($user->getFirstAttribute('samAccountName') === getenv("ADMIN_ACCOUNT") 
            || !empty($user->getFirstAttribute('extensionAttribute13')))) {
            
            // Add new user found in LDAP
            $newUser = new User();
            $newUser->name = $user->getFirstAttribute('displayName');
            $newUser->username = $user->getFirstAttribute('samAccountName');
            $newUser->email = $user->getFirstAttribute('mail');
            $newUser->erp_id = $user->getFirstAttribute('employeeID');
            $newUser->infinias_id = $user->getFirstAttribute('extensionAttribute13');
            $newUser->guid = $user->getConvertedGuid();
            $newUser->domain = 'default';
            $newUser->save();

            // Return new user
            if ($request->expectsJson()) {
                $roles = $newUser->roles ? $newUser->roles : [];
                return response()->json([
                    "id" => $newUser->id,
                    "erp_id" => $newUser->erp_id,
                    "name" => $newUser->name,
                    "username" => $newUser->username,
                    "admin" => in_array("ADMIN", $roles),
                    "manager" => in_array("MANAGER", $roles),
                    "override" => in_array("OVERRIDE", $roles)
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "User not found"
            ], 204);
        }

        return redirect()->route('users.index');
    }
}