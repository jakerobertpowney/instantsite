<?php

use App\Mail\ContactFormMail;
use App\Models\ContactSubmission;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function subscribeUserToPremium(User $user): void
{
    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'default',
        'stripe_id' => 'sub_' . $user->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_premium',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('premium site contact submissions store preferred date and time and send an email', function () {
    Mail::fake();

    $user = User::factory()->create();
    subscribeUserToPremium($user);

    $site = Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-contact',
        'domain_type' => 'subdomain',
        'subdomain' => 'booking-demo',
        'data' => [
            'displayName' => ['text' => 'Booking Demo'],
            'contact' => 'owner@example.com',
        ],
    ]);

    $response = $this->post('http://booking-demo.instantsite.test/contact', [
        'email' => 'customer@example.com',
        'subject' => 'Hair appointment',
        'message' => 'Do you have any space next week?',
        'preferred_contact_time' => 'Tuesday afternoon',
        'website' => '',
    ]);

    $response
        ->assertOk()
        ->assertJson(['success' => true]);

    $submission = ContactSubmission::query()->first();

    expect($submission)
        ->not->toBeNull()
        ->and($submission->site_id)->toBe($site->id)
        ->and($submission->preferred_contact_time)->toBe('Tuesday afternoon');

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
        return $mail->hasTo('owner@example.com')
            && $mail->preferredContactTime === 'Tuesday afternoon'
            && $mail->senderEmail === 'customer@example.com';
    });
});

test('honeypot contact submissions return success without storing or sending', function () {
    Mail::fake();

    $user = User::factory()->create();
    subscribeUserToPremium($user);

    Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-honeypot',
        'domain_type' => 'subdomain',
        'subdomain' => 'spam-trap',
        'data' => [
            'displayName' => ['text' => 'Spam Trap'],
            'contact' => 'owner@example.com',
        ],
    ]);

    $response = $this->post('http://spam-trap.instantsite.test/contact', [
        'email' => 'bot@example.com',
        'subject' => 'Spam',
        'message' => 'Spam message',
        'preferred_contact_time' => 'Any time',
        'website' => 'https://spam.example',
    ]);

    $response
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(ContactSubmission::count())->toBe(0);

    Mail::assertNothingSent();
});

test('submissions page includes the preferred contact time', function () {
    $user = User::factory()->create();

    $site = Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-dashboard-submissions',
        'domain_type' => 'subdomain',
        'subdomain' => 'dashboard-submissions',
        'data' => [],
    ]);

    ContactSubmission::create([
        'site_id' => $site->id,
        'email' => 'customer@example.com',
        'subject' => 'Consultation',
        'message' => 'Could you fit me in next week?',
        'preferred_contact_time' => 'Thursday morning',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('submissions'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Submissions')
            ->where('submissions.data.0.preferred_contact_time', 'Thursday morning')
        );
});
