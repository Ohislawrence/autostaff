<?php

namespace Nomdal\Actions;

use Nomdal\ApiClient;

class Leads
{
    public static function init(): void
    {
        // Contact Form 7 integration (no hard dependency — guarded at runtime).
        add_action('wpcf7_mail_sent', [self::class, 'handleContactForm7']);
    }

    public static function handleContactForm7($contactForm): void
    {
        if (! class_exists('WPCF7_Submission')) {
            return;
        }

        $submission = \WPCF7_Submission::get_instance();
        if (! $submission) {
            return;
        }

        $client = ApiClient::fromSettings();
        if (! $client) {
            return;
        }

        $posted = $submission->get_posted_data();

        $client->createLead([
            'first_name' => sanitize_text_field($posted['your-name'] ?? ''),
            'email' => sanitize_email($posted['your-email'] ?? ''),
            'phone' => sanitize_text_field($posted['your-phone'] ?? ''),
            'notes' => sanitize_textarea_field($posted['your-message'] ?? ''),
            'source' => 'wordpress_contact_form',
            'metadata' => [
                'form_id' => method_exists($contactForm, 'id') ? $contactForm->id() : null,
            ],
        ]);
    }
}
