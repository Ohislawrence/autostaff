<?php

namespace App\Http\Controllers;

use App\Models\BuyerPersona;
use App\Services\Prospecting\BuyerPersonaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuyerPersonaController extends Controller
{
    public function __construct(protected BuyerPersonaService $service) {}

    public function index()
    {
        $orgId = $this->currentOrganizationId();
        if (! $orgId) {
            return redirect()->route('onboarding.show');
        }

        return Inertia::render('Prospecting/Personas', [
            'personas' => BuyerPersona::where('organization_id', $orgId)
                ->withCount('campaigns')
                ->latest()
                ->get(),
            'templates' => BuyerPersona::whereNull('organization_id')
                ->where('is_template', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function useTemplate(Request $request)
    {
        $organization = $this->currentOrganization();
        if (! $organization) {
            return redirect()->route('onboarding.show');
        }

        $validated = $request->validate([
            'persona_id' => 'required|integer',
        ]);

        $template = BuyerPersona::whereNull('organization_id')
            ->where('is_template', true)
            ->findOrFail($validated['persona_id']);

        $copy = $template->replicate();
        $copy->organization_id = $organization->id;
        $copy->is_template = false;
        $copy->save();

        return back()->with('success', 'Persona "' . $copy->name . '" added from the template library.');
    }

    public function store(Request $request)
    {
        $organization = $this->currentOrganization();
        if (! $organization) {
            return redirect()->route('onboarding.show');
        }

        $validated = $request->validate($this->service->rules());
        $persona = $this->service->create($validated, $organization);

        return back()->with('success', 'Buyer persona "' . $persona->name . '" created.');
    }

    public function update(Request $request, int $persona)
    {
        $persona = $this->personaOrFail($persona);
        $validated = $request->validate($this->service->rules());

        $this->service->update($persona, $validated);

        return back()->with('success', 'Buyer persona "' . $persona->name . '" updated.');
    }

    public function destroy(int $persona)
    {
        $this->personaOrFail($persona)->delete();

        return back()->with('success', 'Buyer persona deleted.');
    }

    public function generate(Request $request)
    {
        $organization = $this->currentOrganization();
        if (! $organization) {
            return redirect()->route('onboarding.show');
        }

        $validated = $request->validate([
            'offer' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
        ]);

        $persona = $this->service->generate($validated, $organization);

        return back()->with('success', 'Persona "' . $persona->name . '" generated. Review and edit it below.');
    }

    protected function personaOrFail(int $id): BuyerPersona
    {
        return BuyerPersona::where('organization_id', $this->currentOrganizationId())->findOrFail($id);
    }
}
