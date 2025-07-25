<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\role_user;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\utils\helpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Config;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;
use \Nwidart\Modules\Facades\Module;

class UserController extends BaseController
{

    //------------- GET ALL USERS---------\\

    public function index(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'view', User::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $columns = array(0 => 'username', 1 => 'statut', 2 => 'phone', 3 => 'email');
        $param = array(0 => 'like', 1 => '=', 2 => 'like', 3 => 'like');
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $users = User::where(function ($query) use ($ShowRecord) {
            if (!$ShowRecord) {
                return $query->where('id', '=', Auth::user()->id);
            }
        });

        //Multiple Filter
        $Filtred = $helpers->filter($users, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('username', 'LIKE', "%{$request->search}%")
                        ->orWhere('firstname', 'LIKE', "%{$request->search}%")
                        ->orWhere('lastname', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $users = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        $roles = Role::where('deleted_at', null)->get(['id', 'name']);
        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'users' => $users,
            'roles' => $roles,
            'warehouses' => $warehouses,
            'totalRows' => $totalRows,
        ]);
    }

    //------------- GET USER Auth ---------\\

   public function GetUserAuth(Request $request)
    {
        $helpers = new Helpers();
        $user = Auth::user();
        $settings = Setting::first();

        $userData = [
            'avatar'              => $user->avatar,
            'username'            => $user->username,
            'currency'            => $helpers->Get_Currency(),
            'logo'                => $settings->logo ?? null,
            'default_language'    => $settings->default_language ?? 'en',
            'show_language'       => $settings->show_language ?? false,
            'footer'              => $settings->footer ?? '',
            'developed_by'        => $settings->developed_by ?? '',
            'app_name'            => $settings->app_name ?? config('app.name'),
            'page_title_suffix'   => $settings->page_title_suffix ?? '',
        ];

        $permissions = $user->roles()->first()?->permissions->pluck('name') ?? [];

        $productsAlerts = product_warehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->whereRaw('qte <= stock_alert')
            ->whereNull('product_warehouse.deleted_at')
            ->count();

        $ModulesEnabled = collect(Module::allEnabled())->map(function ($module) {
            $name = strtolower($module->getName());
            return [
                'name'       => config("$name.name"),
                'url'        => config("$name.url"),
                'icon'       => config("$name.icon"),
                'permission' => config("$name.permission"),
            ];
        })->toArray();

        return response()->json([
            'success'        => true,
            'user'           => $userData,
            'notifs'         => $productsAlerts,
            'permissions'    => $permissions,
            'ModulesEnabled' => $ModulesEnabled,
        ]);
    }


    //------------- GET USER ROLES ---------\\

    public function GetUserRole(Request $request)
    {

        $roles = Auth::user()->roles()->with('permissions')->first();

        $data = [];
        if ($roles) {
            foreach ($roles->permissions as $permission) {
                $data[] = $permission->name;

            }
            return response()->json(['success' => true, 'data' => $data]);
        }

    }

    //------------- STORE NEW USER ---------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', User::class);
        $this->validate($request, [
            'email' => 'required|unique:users',
        ], [
            'email.unique' => 'This Email already taken.',
        ]);
        \DB::transaction(function () use ($request) {
            if ($request->hasFile('avatar')) {

                $image = $request->file('avatar');
                $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

                $image_resize = Image::make($image->getRealPath());
                $image_resize->resize(128, 128);
                $image_resize->save(public_path('/images/avatar/' . $filename));

            } else {
                $filename = 'no_avatar.png';
            }

            if($request['is_all_warehouses'] == '1' || $request['is_all_warehouses'] == 'true'){
                $is_all_warehouses = 1;
            }else{
                $is_all_warehouses = 0;
            }

            $User = new User;
            $User->firstname = $request['firstname'];
            $User->lastname  = $request['lastname'];
            $User->username  = $request['username'];
            $User->email     = $request['email'];
            $User->phone     = $request['phone'];
            $User->password  = Hash::make($request['password']);
            $User->avatar    = $filename;
            $User->role_id   = $request['role'];
            $User->is_all_warehouses   = $is_all_warehouses;
            $User->save();

            $role_user = new role_user;
            $role_user->user_id = $User->id;
            $role_user->role_id = $request['role'];
            $role_user->save();

            if(!$User->is_all_warehouses){
                $User->assignedWarehouses()->sync($request['assigned_to']);
            }
    
        }, 10);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', User::class);

        $assigned_warehouses = UserWarehouse::where('user_id', $id)->pluck('warehouse_id')->toArray();
        $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $assigned_warehouses)->pluck('id')->toArray();

        return response()->json([
            'assigned_warehouses' => $warehouses,
        ]);
    }

    //------------- UPDATE  USER ---------\\

    public function update(Request $request, $id)
    {        
        $this->authorizeForUser($request->user('api'), 'update', User::class);
        
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'email' => Rule::unique('users')->ignore($id),
        ], [
            'email.unique' => 'This Email already taken.',
        ]);

        \DB::transaction(function () use ($id ,$request) {
            $user = User::findOrFail($id);
            $current = $user->password;

            if ($request->NewPassword != 'null') {
                if ($request->NewPassword != $current) {
                    $pass = Hash::make($request->NewPassword);
                } else {
                    $pass = $user->password;
                }

            } else {
                $pass = $user->password;
            }

            $currentAvatar = $user->avatar;
            if ($request->avatar != $currentAvatar) {

                $image = $request->file('avatar');
                $path = public_path() . '/images/avatar';
                $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

                $image_resize = Image::make($image->getRealPath());
                $image_resize->resize(128, 128);
                $image_resize->save(public_path('/images/avatar/' . $filename));

                $userPhoto = $path . '/' . $currentAvatar;
                if (file_exists($userPhoto)) {
                    if ($user->avatar != 'no_avatar.png') {
                        @unlink($userPhoto);
                    }
                }
            } else {
                $filename = $currentAvatar;
            }

            if($request['is_all_warehouses'] == '1' || $request['is_all_warehouses'] == 'true'){
                $is_all_warehouses = 1;
            }else{
                $is_all_warehouses = 0;
            }

            User::whereId($id)->update([
                'firstname' => $request['firstname'],
                'lastname' => $request['lastname'],
                'username' => $request['username'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'password' => $pass,
                'avatar' => $filename,
                'statut' => $request['statut'],
                'is_all_warehouses' => $is_all_warehouses,
                'role_id' => $request['role'],

            ]);

            role_user::where('user_id' , $id)->update([
                'user_id' => $id,
                'role_id' => $request['role'],
            ]);

            $user_saved = User::where('deleted_at', '=', null)->findOrFail($id);
            $user_saved->assignedWarehouses()->sync($request['assigned_to']);

        }, 10);
        
        return response()->json(['success' => true]);

    }


    //------------- UPDATE PROFILE ---------\\

    public function updateProfile(Request $request, $id)
    {

        $this->validate($request, [
            'firstname' => 'required',
            'lastname' => 'required',
            'username' => 'required',
            'email' => 'required|email|unique:users',
            'email' => Rule::unique('users')->ignore($id),
            'phone' => 'required',
            ]
        );
        
        $id = Auth::user()->id;
        $user = User::findOrFail($id);
        $current = $user->password;

        if ($request->NewPassword != 'undefined') {
            if ($request->NewPassword != $current) {
                $pass = Hash::make($request->NewPassword);
            } else {
                $pass = $user->password;
            }

        } else {
            $pass = $user->password;
        }

        $currentAvatar = $user->avatar;
        if ($request->avatar != $currentAvatar) {

            $image = $request->file('avatar');
            $path = public_path() . '/images/avatar';
            $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

            $image_resize = Image::make($image->getRealPath());
            $image_resize->resize(128, 128);
            $image_resize->save(public_path('/images/avatar/' . $filename));

            $userPhoto = $path . '/' . $currentAvatar;

            if (file_exists($userPhoto)) {
                if ($user->avatar != 'no_avatar.png') {
                    @unlink($userPhoto);
                }
            }
        } else {
            $filename = $currentAvatar;
        }

        User::whereId($id)->update([
            'firstname' => $request['firstname'],
            'lastname' => $request['lastname'],
            'username' => $request['username'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => $pass,
            'avatar' => $filename,

        ]);

        return response()->json(['avatar' => $filename, 'user' => $request['username']]);

    }

    //----------- IsActivated (Update Statut User) -------\\

    public function IsActivated(request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', User::class);

        $user = Auth::user();
        if ($request['id'] !== $user->id) {
            User::whereId($id)->update([
                'statut' => $request['statut'],
            ]);
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
            ]);
        }
    }

    public function GetPermissions()
    {
        $roles = Auth::user()->roles()->with('permissions')->first();
        $data = [];
        if ($roles) {
            foreach ($roles->permissions as $permission) {
                $item[$permission->name]['slug'] = $permission->name;
                $item[$permission->name]['id'] = $permission->id;

            }
            $data[] = $item;
        }
        return $data[0];

    }

    //------------- GET USER Auth ---------\\

    public function GetInfoProfile(Request $request)
    {
        $data = Auth::user();
        return response()->json(['success' => true, 'user' => $data]);
    }

}
