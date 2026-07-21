<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\GoatImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoatImageController extends Controller
{
    /**
     * Store a new gallery image (file upload or camera capture).
     */
    public function store(Request $request, Goat $goat)
    {
        $validated = $request->validate([
            'image'     => ['required', 'image', 'max:5120'],
            'age_months' => ['required', 'numeric', 'min:0'],
            'weight_kg'  => ['required', 'numeric', 'min:0'],
            'taken_at'   => ['required', 'date', 'before_or_equal:today'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['goat_id']    = $goat->id;
        $validated['image_path'] = $request->file('image')->store('goats/gallery', 'public');

        unset($validated['image']);

        GoatImage::create($validated);

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Photo added to gallery.');
    }

    /**
     * Soft-delete a gallery image.
     */
    public function destroy(GoatImage $goat_image)
    {
        $goat = $goat_image->goat;

        Storage::disk('public')->delete($goat_image->image_path);
        $goat_image->delete();

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Photo removed from gallery.');
    }
}
