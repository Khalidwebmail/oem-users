<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleAssignRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();
        return new JsonResource($roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        Role::create($data);
        return $this->sendResponse(['message' => 'Created'], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::findOrFail($id);
        return new JsonResource($role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->all();

        $role->update($data);

        $role->refresh();

        return new JsonResource($role);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($role)
    {
        $role = Role::find($role);

        if(! $role) {
            return response()->json(['error' => 'This role does not exists'], 404);
        }

        $role->delete();

        return response()->json(['message' => 'Role Deletion Successful!'], 200);
    }

    public function assign(RoleAssignRequest $request, User $user)
    {
        $roles = stristr($request->roles, ',') ? explode(',', $request->roles) : $request->roles;
        $user->syncRoles($roles);

        return response()->json(['message' => 'Operation successful!'], 200);
    }

    public function assignPermissionViaRole(Request $request, Role $role)
    {
        $permissions = stristr($request->permissions, ',') ? explode(',', $request->permissions) : $request->permissions;
        $role->syncPermissions($permissions);

        return response()->json(['message' => 'Operation successful!'], 200);
    }
}
