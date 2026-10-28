<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourseController extends Controller
{
    public function index()
    {
        return Course::with('livreur')->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'adresse' => 'required|string|max:255',
            'montant' => 'required|integer|min:1',
            'livreur_id' => 'nullable|exists:livreurs,id',
        ]);
        if ($response = $this->verifierLivreurLibre($request, $data['livreur_id'] ?? null)) {
            return $response;
        }
        $course = Course::create($data);
        return response()->json($course->load('livreur'), 201);
    }

    public function show(Course $course)
    {
        return $course->load('livreur');
    }

    public function update(Request $request, Course $course)
    {
        if ($course->statut !== 'en_attente') {
            return response()->json(['message' => 'Seule une course en attente peut être modifiée'], 422);
        }
        $data = $request->validate([
            'adresse' => 'sometimes|string|max:255',
            'montant' => 'sometimes|integer|min:1',
            'livreur_id' => 'nullable|exists:livreurs,id',
        ]);
        $nouveau = $data['livreur_id'] ?? null;
        if ($nouveau != $course->livreur_id && ($response = $this->verifierLivreurLibre($request, $nouveau))) {
            return $response;
        }

        $course->update($data);
        return $course->load('livreur');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->noContent();
    }

    public function avancer(Course $course)
{
    if ($course->statut === 'en_attente') {
        if (!$course->livreur_id) {
            return response()->json([
                'message' => 'Affectez un livreur avant de prendre en charge cette course.'
            ], 422);
        }
        $course->update(['statut' => 'en_cours']);
    } elseif ($course->statut === 'en_cours') {
        $course->update(['statut' => 'livree', 'livree_le' => now()]);
    } else {
        return response()->json(['message' => 'Cette course ne peut plus avancer'], 422);
    }
    return $course->load('livreur');
}

    public function annuler(Course $course)
    {
        if (!in_array($course->statut, ['en_attente', 'en_cours'])) {
            return response()->json(['message' => 'Cette course ne peut plus être annulée'], 422);
        }
        $course->update(['statut' => 'annulee', 'annulee_le' => now()]);
        return $course->load('livreur');
    }

    public function stats(Request $request)
{
    $request->validate([
        'debut' => 'nullable|date',
        'fin' => 'nullable|date|after_or_equal:debut',
        'livreur_id' => 'nullable|exists:livreurs,id',
    ]);

    $livrees = Course::where('statut', 'livree')
        ->when($request->debut, fn ($q, $d) => $q->where('livree_le', '>=', Carbon::parse($d)->startOfDay()))
        ->when($request->fin, fn ($q, $f) => $q->where('livree_le', '<=', Carbon::parse($f)->endOfDay()))
        ->when($request->livreur_id, fn ($q, $id) => $q->where('livreur_id', $id));

    return [
        'ca_total' => (int) $livrees->sum('montant'),
        'courses_livrees' => $livrees->count(),
        'courses_en_cours' => Course::where('statut', 'en_cours')->count(),
        'courses_en_attente' => Course::where('statut', 'en_attente')->count(),
        'courses_annulees' => Course::where('statut', 'annulee')->count(),
        'livreurs_actifs' => Course::where('statut', 'en_cours')->distinct('livreur_id')->count('livreur_id'),
        'livreurs_total' => Livreur::count(),
    ];
}
private function verifierLivreurLibre(Request $request, $livreurId)
{
    if (!$livreurId || $request->boolean('confirmer')) {
        return null;
    }
    $occupe = Course::where('livreur_id', $livreurId)->where('statut', 'en_cours')->exists();
    if ($occupe) {
        return response()->json([
            'message' => 'Ce livreur a déjà une course en cours. L’affecter quand même ?',
            'code' => 'livreur_occupe',
        ], 409);
    }
    return null;
}
}