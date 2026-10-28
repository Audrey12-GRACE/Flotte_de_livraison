<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use Illuminate\Http\Request;

class LivreurController extends Controller
{
    public function index()
    {
        return Livreur::withCount('courses')->get();
    }

  public function store(Request $request)
{
    \Log::info($request->all()); // Log the incoming request data for debugging
    $data = $request->validate([
    'nom' => 'required|string|max:255',
    'telephone' => ['nullable', 'string', 'max:30', 'regex:/^(\+228\s?)?\d{2}(\s?\d{2}){3}$/'],
    'vehicule' => 'nullable|string|max:100',
    'zone' => 'nullable|string|max:100',
   ], [
    'telephone.regex' => 'Le numéro doit être au format +228 00 00 00 00.',
]);
    
    $livreur = Livreur::create($data);
    return response()->json($livreur, 201);
}

public function update(Request $request, Livreur $livreur)
{
    $data = $request->validate([
        'nom' => 'required|string|max:255',
        'telephone' => 'nullable|string|max:30',
        'vehicule' => 'nullable|string|max:100',
        'zone' => 'nullable|string|max:100',
    ]);
    $livreur->update($data);
    return $livreur;
}

public function destroy(Livreur $livreur)
{
    if ($livreur->courses()->where('statut', 'en_cours')->exists()) {
        return response()->json([
            'message' => 'Ce livreur ne peut pas être retiré car une course est actuellement prise en charge.'
        ], 422);
    }
    $livreur->delete();
    return response()->noContent();
}
}