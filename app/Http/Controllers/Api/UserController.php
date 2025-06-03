<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function getRoles(Request $request)
    {
        $idrol =  $request->idrol;
        $idsrol =  $request->idsrol;
        $username =  $request->username;
        $response = Role::orderBy('id', 'desc');

        if (!empty($idrol)) {
            $response = $response->where('id', $idrol);
        }
        if (!empty($idsrol)) {
            $idsrol =  explode(',', $request->idsrol);
            $response = $response->whereIn('id', $idsrol);
        }
        if (!empty($username)) {
            $user = User::where('username', $username)->first();
            $response = $user->roles;
            return response()->json($response);
        }

        $response = $response->get();
        return response()->json($response);
    }


    public function indexPagination(Request $request)
    {
        $username = $request->username;
        $nombres = $request->nombres;
        $apellidos = $request->apellidos;
        $roleId = $request->role;

        $query = User::with('roles', 'roles.permissions')->orderBy('id', 'asc');

        if (!empty($username)) {
            $query->where('username', 'like', '%' . $username . '%');
        }
        if (!empty($nombres)) {
            $query->where('firstname', 'like', '%' . $nombres . '%');
        }
        if (!empty($apellidos)) {
            $query->where('lastname', 'like', '%' . $apellidos . '%');
        }
        if (!empty($roleId)) {
            $query->whereHas('roles', function ($query) use ($roleId) {
                $query->where('id', $roleId);
            });
        }

        $response = $query->paginate(10);

        return response()->json($response);
    }
}
