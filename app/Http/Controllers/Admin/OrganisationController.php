<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrganisationRequest;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganisationController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Organisation::class);
        $organisations = Organisation::all();

        return Inertia::render('dashboard/organisations/Index', ['organisations' => $organisations]);
    }

    public function show(Organisation $organisation): Response
    {
        Gate::authorize('view', Organisation::class);
        $organisation->load('users');

        return Inertia::render('dashboard/organisations/Show', ['organisation' => $organisation]);
    }

    public function update(UpdateOrganisationRequest $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', Organisation::class);
        if ($request->hasFile('image')) {
            if ($organisation->image_path && Storage::disk('hetzner')->exists($organisation->image_path)) {
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

    public function detachUser(Request $request, Organisation $organisation, User $user): RedirectResponse
    {
        Gate::authorize('update', Organisation::class);
        $organisation->users()->detach($user->id);

        return redirect()->route('admin.organisations.show', $organisation->slug);
    }
}
