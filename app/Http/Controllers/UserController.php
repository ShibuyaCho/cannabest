<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUser;   
use App\Models\Role;
use App\User;
use DB;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
public function index()
{
    // 1) grab the current user's org
    $orgId = auth()->user()->organization_id;

    // 2) only fetch users in that org, eager-loading their roles
    $users = User::where('organization_id', $orgId)
                 ->with('role')    // assumes you have a role() relationship
                 ->get();

    return view('backend.users.index', ['users' => $users]);
}
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [
            'roles' => Role::get()
        ];

        return view('backend.users.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUser  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {
        // 1) Get only the validated inputs
        $data = $request->validated();

        // 2) Tie them to the same organization as the admin
        $data['organization_id'] = Auth::user()->organization_id;

       
        // 4) Create the user (role_id is in $data already)
        $user = User::create($data);

        return redirect()
            ->route('users.index')
            ->with('message-success', 'User created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('backend.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        $data = [
            'user'  => $user,
            'roles' => Role::get()
        ];

        return view('backend.users.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUser  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\UpdateUser $request, $id)
    {
        $form = $request->all();

        $user = User::findOrFail($id);
        $user->update($form);
        
        $role = DB::table('role_user')->where("user_id", $id)->first();
        if ($role) { 
            DB::table('role_user')
                ->where("user_id", $id)
                ->update(["role_id" => $form['role_id']]);
        } else { 
            DB::table('role_user')->insert([
                "role_id" => $form['role_id'],
                "user_id" => $id
            ]);
        }

        return redirect('users')
            ->with('message-success', 'User updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect('users')
            ->with('message-success', 'User deleted!');
    }

    /**
     * Store a newly created employee with budtender permissions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeEmployee(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        $form = $request->all();

        // Force the role to budtender (role_id = 3)
        $form['role_id'] = 3;

        // Hash the password
        $form['password'] = bcrypt($form['password']);

        // Create the user
        $user = User::create($form);

        // Insert into the pivot table for roles
        DB::table('role_user')->insert([
            'role_id' => $form['role_id'],
            'user_id' => $user->id
        ]);

        // Redirect to the login page with a success message
        return redirect()->route('login')
            ->with('message-success', 'User created successfully! Please log in.');
    }
}
