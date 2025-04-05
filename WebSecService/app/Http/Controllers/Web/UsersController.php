<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Artisan;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller {

	use ValidatesRequests;

    public function list(Request $request) {
        if(!auth()->user()->hasPermissionTo('show_users'))abort(401);
        $query = User::select('*');
        $query->when($request->keywords, 
        fn($q)=> $q->where("name", "like", "%$request->keywords%"));
        $users = $query->get();
        return view('users.list', compact('users'));
    }

	public function register(Request $request) {
        return view('users.register');
    }

    public function doRegister(Request $request) {

    	try {
    		$this->validate($request, [
	        'name' => ['required', 'string', 'min:5'],
	        'email' => ['required', 'email', 'unique:users'],
	        'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
	    	]);
    	}
    	catch(\Exception $e) {
    		return redirect()->back()->withInput($request->input())->withErrors('Invalid registration information.');
    	}

    	$user = new User();
	    $user->name = $request->name;
	    $user->email = $request->email;
	    $user->password = bcrypt($request->password); //Secure
        $user->credit = 0.00;
	    $user->save();
        
        // Assign Customer role if it exists
        try {
            $customerRole = Role::where('name', 'Customer')->first();
            if ($customerRole) {
                $user->assignRole($customerRole);
            }
        } catch(\Exception $e) {
            // Log error but continue - don't stop registration if role assignment fails
            \Log::error("Failed to assign Customer role: " . $e->getMessage());
        }

        Auth::login($user);
        return redirect('/');
    }

    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request) {
    	
    	if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return redirect()->back()->withInput($request->input())->withErrors('Invalid login information.');

        $user = User::where('email', $request->email)->first();
        Auth::setUser($user);

        return redirect('/');
    }

    public function doLogout(Request $request) {
    	
    	Auth::logout();

        return redirect('/');
    }

    public function profile(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $permissions = [];
        foreach($user->permissions as $permission) {
            $permissions[] = $permission;
        }
        foreach($user->roles as $role) {
            foreach($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }
        
        // Get purchased products for customer view
        $purchasedProducts = $user->purchases()->with('product')->latest()->get();

        return view('users.profile', compact('user', 'permissions', 'purchasedProducts'));
    }

    public function edit(Request $request, User $user = null) {
   
        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }
    
        $roles = [];
        foreach(Role::all() as $role) {
            $role->taken = ($user->hasRole($role->name));
            $roles[] = $role;
        }

        $permissions = [];
        $directPermissionsIds = $user->permissions()->pluck('id')->toArray();
        foreach(Permission::all() as $permission) {
            $permission->taken = in_array($permission->id, $directPermissionsIds);
            $permissions[] = $permission;
        }      

        return view('users.edit', compact('user', 'roles', 'permissions'));
    }

    public function save(Request $request, User $user) {

        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $user->name = $request->name;
        $user->save();

        if(auth()->user()->hasPermissionTo('admin_users')) {
            $user->syncRoles($request->roles);
            $user->syncPermissions($request->permissions);

            Artisan::call('cache:clear');
        }

        return redirect(route('profile', ['user'=>$user->id]));
    }

    public function delete(Request $request, User $user) {

        if(!auth()->user()->hasPermissionTo('delete_users')) abort(401);

        //$user->delete();

        return redirect()->route('users');
    }

    public function editPassword(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        return view('users.edit_password', compact('user'));
    }

    public function savePassword(Request $request, User $user) {

        if(auth()->id()==$user?->id) {
            
            $this->validate($request, [
                'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            ]);

            if(!Auth::attempt(['email' => $user->email, 'password' => $request->old_password])) {
                
                Auth::logout();
                return redirect('/');
            }
        }
        else if(!auth()->user()->hasPermissionTo('edit_users')) {

            abort(401);
        }

        $user->password = bcrypt($request->password); //Secure
        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }

    public function addCredit(Request $request, User $user)
    {
        // Only employees and admins can add credit
        if(!auth()->user()->hasPermissionTo('manage_customer_credit')) abort(401);
        
        // Check if user is a customer
        if(!$user->hasRole('Customer')) {
            return redirect()->back()->withErrors('Credit can only be added to Customer accounts.');
        }
        
        $this->validate($request, [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        
        $user->credit += $request->amount;
        $user->save();
        
        return redirect()->route('profile', ['user' => $user->id])->with('success', 'Credit added successfully.');
    }
    
    public function listCustomers(Request $request)
    {
        // Only employees and admins can list customers
        if(!auth()->user()->hasAnyRole(['Employee', 'Admin'])) abort(401);
        
        $query = User::role('Customer')->select('*');
        $query->when($request->keywords, 
        fn($q)=> $q->where("name", "like", "%$request->keywords%"));
        $customers = $query->get();
        
        return view('users.customers', compact('customers'));
    }
    
    public function createEmployee(Request $request)
    {
        // Only admins can create employees
        if(!auth()->user()->hasRole('Admin')) abort(401);
        
        if ($request->isMethod('post')) {
            try {
                $this->validate($request, [
                    'name' => ['required', 'string', 'min:5'],
                    'email' => ['required', 'email', 'unique:users'],
                    'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
                ]);
                
                $employee = new User();
                $employee->name = $request->name;
                $employee->email = $request->email;
                $employee->password = bcrypt($request->password);
                $employee->credit = 0.00;
                $employee->save();
                
                // Assign Employee role
                $employee->assignRole('Employee');
                
                return redirect()->route('users')->with('success', 'Employee created successfully.');
            } catch(\Exception $e) {
                return redirect()->back()->withInput($request->input())->withErrors('Invalid employee information.');
            }
        }
        
        return view('users.create_employee');
    }
} 