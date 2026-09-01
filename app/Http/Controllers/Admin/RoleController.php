<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Permission;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\Validation\Rule;use Illuminate\View\View;use Spatie\Permission\Models\Role;
class RoleController extends Controller{
 public function __construct(){$this->middleware('permission:list-roles')->only(['index','show']);$this->middleware('permission:create-roles')->only(['create','store']);$this->middleware('permission:edit-roles')->only(['edit','update']);$this->middleware('permission:delete-roles')->only('destroy');}
 public function index(Request $r):View{$roles=Role::withCount(['users','permissions'])->when($r->filled('q'),fn($q)=>$q->where('name','like','%'.$r->q.'%'))->orderBy('name')->paginate(20)->withQueryString();return view('admin.roles.index',compact('roles'));}
 public function create():View{return view('admin.roles.create',['permissions'=>Permission::orderBy('name')->get()->groupBy('group_name')]);}
 public function store(Request $r):RedirectResponse{$d=$r->validate(['name'=>['required','string','max:100','unique:roles,name'],'permission'=>['nullable','array'],'permission.*'=>['exists:permissions,name']]);$role=Role::create(['name'=>$d['name'],'guard_name'=>'web']);$role->syncPermissions($d['permission']??[]);return redirect()->route('roles.index')->with('message','Role created.');}
 public function show(Role $role):RedirectResponse{return redirect()->route('roles.edit',$role);}
 public function edit(Role $role):View{return view('admin.roles.edit',['role'=>$role,'permissions'=>Permission::orderBy('name')->get()->groupBy('group_name')]);}
 public function update(Request $r,Role $role):RedirectResponse{abort_if($role->name==='superadmin'&&$r->name!=='superadmin',422,'The superadmin role cannot be renamed.');$d=$r->validate(['name'=>['required','string','max:100',Rule::unique('roles')->ignore($role)],'permission'=>['nullable','array'],'permission.*'=>['exists:permissions,name']]);$role->update(['name'=>$d['name']]);$role->syncPermissions($d['permission']??[]);return back()->with('message','Role updated.');}
 public function destroy(Role $role):RedirectResponse{abort_if($role->name==='superadmin',422,'The superadmin role cannot be deleted.');$role->delete();return redirect()->route('roles.index')->with('message','Role deleted.');}
}
