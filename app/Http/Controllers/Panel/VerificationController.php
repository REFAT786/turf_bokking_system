<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Owner identity verification: owners upload documents, the super admin reviews them. */
class VerificationController extends Controller
{
    public const DISK = 'local'; // storage/app/private — never publicly reachable

    public static function rules(): array
    {
        $file = ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

        return [
            'nid_number' => ['required', 'regex:/^(\d{10}|\d{13}|\d{17})$/'],
            'nid_front' => $file,
            'nid_back' => $file,
            // A real photo of the owner's face (with the NID card) — no PDFs, so it can be compared visually.
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'business_document' => $file,
        ];
    }

    public static function messages(): array
    {
        return [
            'nid_number.regex' => 'Enter your NID number (10, 13 or 17 digits).',
            '*.mimes' => 'Upload a photo (JPG, PNG, WEBP) or a PDF.',
            '*.max' => 'Each file must be 5 MB or smaller.',
            'selfie.mimes' => 'The selfie must be a photo (JPG, PNG or WEBP).',
            'selfie.required' => 'Take a selfie holding your NID card.',
        ];
    }

    /** Store uploaded documents for $owner and put the account in the review queue. */
    public static function submit(Request $request, User $owner): void
    {
        $dir = "owner-documents/{$owner->id}";
        $old = array_filter([$owner->nid_front_path, $owner->nid_back_path, $owner->selfie_path, $owner->business_document_path]);

        $owner->update([
            'nid_number' => $request->input('nid_number'),
            'nid_front_path' => $request->file('nid_front')->store($dir, self::DISK),
            'nid_back_path' => $request->file('nid_back')->store($dir, self::DISK),
            'selfie_path' => $request->file('selfie')->store($dir, self::DISK),
            'business_document_path' => $request->file('business_document')->store($dir, self::DISK),
            'verification_status' => User::VERIFICATION_PENDING,
            'verification_note' => null,
            'verification_submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
        ]);

        Storage::disk(self::DISK)->delete($old);
    }

    public function show(Request $request)
    {
        return view('panel.verification', ['owner' => $request->user()]);
    }

    public function store(Request $request)
    {
        $owner = $request->user();
        abort_if($owner->isVerifiedOwner(), 403, 'Your account is already verified.');

        $request->validate(self::rules(), self::messages());
        self::submit($request, $owner);

        return redirect()->route('panel.verification')->with('success', 'Documents submitted. We will review them shortly.');
    }
}
