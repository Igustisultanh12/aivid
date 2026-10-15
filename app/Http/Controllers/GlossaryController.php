<?php

namespace App\Http\Controllers;

use App\Models\GlossaryTerm;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlossaryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GlossaryTerm::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('term', 'like', "%{$search}%")
                  ->orWhere('meaning', 'like', "%{$search}%")
                  ->orWhere('related_traditions', 'like', "%{$search}%");
            });
        }

        $terms = $query->orderBy('term')->paginate(20)->withQueryString();

        return Inertia::render('Glossary/Index', [
            'terms' => $terms,
            'filters' => $request->only(['search']),
        ]);
    }
}
