<?php

namespace App\Http\Controllers;

use App\Models\Mention;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MentionController extends Controller
{
    public function index()
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;

        $mentions = Mention::where('ecole_id', $ecoleId)
            ->orderBy('min_note')
            ->get();

        return view('dashboard.pages.parametrage.mention', compact('mentions'));
    }


    public function store(Request $request)
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;

        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mentions')->where(fn($query) => $query->where('ecole_id', $ecoleId)),
            ],
            'description' => 'nullable|string',
            'min_note' => 'nullable|integer|min:0|max:20',
            'max_note' => 'nullable|integer|min:0|max:20|gte:min_note',
        ]);

        $validated['ecole_id'] = $ecoleId;

        Mention::create($validated);

        return redirect()->route('mentions.index')->with('success', 'Mention créée avec succès');
    }

    public function update(Request $request, Mention $mention)
    {
        $ecoleId = auth()->user()->ecole_id ?? 1;

        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mentions')->ignore($mention->id)->where(fn($query) => $query->where('ecole_id', $ecoleId)),
            ],
            'description' => 'nullable|string',
            'min_note' => 'nullable|integer|min:0|max:20',
            'max_note' => 'nullable|integer|min:0|max:20|gte:min_note',
        ]);

        $validated['ecole_id'] = $ecoleId;

        $mention->update($validated);

        return redirect()->route('mentions.index')->with('success', 'Mention mise à jour avec succès');
    }

    public function destroy(Mention $mention)
    {
        $mention->delete();
        return redirect()->route('mentions.index')->with('success', 'Mention supprimée avec succès');
    }
}
