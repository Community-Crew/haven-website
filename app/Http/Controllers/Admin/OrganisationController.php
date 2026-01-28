<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrganisationController extends Controller
{
    public function index()
    {
        $organisations = Organisation::all();
        return Inertia::render('dashboard/organisations/Index', ['organisations' => $organisations]);
    }

    public function show(Organisation $organisation)
    {
        $organisation->load('users');
        return Inertia::render('dashboard/organisations/Show', ['organisation' => $organisation]);
    }

    public function update(Request $request, Organisation $organisation)
    {
        $request->validate([
            'name' => 'required|string',
            'about' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5000',
        ]);

        if ($request->hasFile('image')) {
            if($organisation->image_path && Storage::disk('hetzner')->exists($organisation->image_path)){
                Storage::disk('hetzner')->delete($organisation->image_path);
            }

            $path = $request->file('image')->store('organisations', 'hetzner');
            $organisation->image_path = $path;
        }

        $organisation->name = $request->name;
        $organisation->about = $request->about;

        $organisation->save();

        return redirect()->route('admin.organisations.show', $organisation->slug);
    }

    public function detachUser(Request $request, Organisation $organisation, User $user)
    {
        $organisation->users()->detach($user->id);

        return redirect()->route('admin.organisations.show', $organisation->slug);
    }

}
