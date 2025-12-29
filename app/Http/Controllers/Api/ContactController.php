<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // Store the contact message
        $contact = Contact::create($validated);

        // Optionally send email notification (you can configure this later)
        // Mail::to('info@oweru.com')->send(new ContactFormMail($contact));

        return response()->json([
            'message' => 'Thank you for your message! We\'ll get back to you soon.',
            'contact' => $contact,
        ], 201);
    }

    public function index(Request $request)
    {
        // Only admin and moderator can access
        if (!in_array(auth()->user()->role, ['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Contact::query()->latest();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $contacts = $query->paginate($perPage);

        return response()->json($contacts);
    }

    public function show($id)
    {
        // Only admin and moderator can access
        if (!in_array(auth()->user()->role, ['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contact = Contact::findOrFail($id);
        return response()->json($contact);
    }
}

