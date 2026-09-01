<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Permission;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\Validation\Rule;use Illuminate\View\View;
class PermissionController extends Controller{
 public function __construct(){$this->middleware('permission:list-permissions')->only(['index','show']);$this->middleware('permission:create-permissions')->only(['create','store']);$this->middleware('permission:edit-permissions')->only(['edit','update']);$this->middleware('permission:delete-permissions')->only('destroy');}
 public function index(Request $r):View{$permissions=Permission::query()->when($r->filled('q'),fn($q)=>$q->where('name','like','%'.$r->q.'%'))->when($r->filled('group'),fn($q)=>$q->where('group_name',$r->group))->orderBy('group_name')->orderBy('name')->paginate(30)->withQueryString();return view('admin.permissions.index',compact('permissions'));}
 public function create():View{return view('admin.permissions.create');}
 public function store(Request $r):RedirectResponse{Permission::create($r->validate(['name'=>['required','string','max:150','unique:permissions,name'],'group_name'=>['required','string','max:100']])+['guard_name'=>'web']);return redirect()->route('permissions.index')->with('message','Permission created.');}
 public function show(Permission $permission):RedirectResponse{return redirect()->route('permissions.edit',$permission);}
 public function edit(Permission $permission):View{return view('admin.permissions.edit',compact('permission'));}
 public function update(Request $r,Permission $permission):RedirectResponse{$permission->update($r->validate(['name'=>['required','string','max:150',Rule::unique('permissions')->ignore($permission)],'group_name'=>['required','string','max:100']]));return back()->with('message','Permission updated.');}
 public function destroy(Permission $permission):RedirectResponse{$permission->delete();return redirect()->route('permissions.index')->with('message','Permission deleted.');}
}
