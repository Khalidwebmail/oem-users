<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permissions = Permission::all();

        return new JsonResource($permissions);
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
        Permission::create($data);

        return response()->json(['message' => 'Permission created'], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        return new JsonResource($permission);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $data = $request->all();

        $permission->update($data);

        $permission->refresh();

        return new JsonResource($permission);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($permission)
    {
        $permission = Permission::find($permission);

        if(! $permission) {
            return response()->json(['error' => 'This permission does not exists'], 404);
        }

        $permission->delete();

        return response()->json(['message' => 'Permission deletion Successful!'], 200);
    }

    public function assign(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $permissions = stristr($request->permissions, ',') ? explode(',', $request->permissions) : $request->permissions;
        $user->syncpermissions($permissions);

        return response()->json(['message' => 'Operation successful!'], 200);
    }
}
