<?php

namespace App\Support;

use Illuminate\Support\Collection;

class HelpCenter
{
    public static function categories(): array
    {
        return [
            [
                'slug' => 'getting-started',
                'title' => 'Getting started',
                'description' => 'Everything you need to get your site live for the first time.',
            ],
            [
                'slug' => 'editing-your-site',
                'title' => 'Editing your site',
                'description' => 'Change your content, links, photos, and customer-facing details.',
            ],
            [
                'slug' => 'domains-and-billing',
                'title' => 'Domains & billing',
                'description' => 'Understand free vs premium, custom domains, and what happens when you upgrade.',
            ],
            [
                'slug' => 'seo-and-visibility',
                'title' => 'SEO & visibility',
                'description' => 'Help Google understand your site and improve how it appears in search.',
            ],
            [
                'slug' => 'messages-and-leads',
                'title' => 'Messages & leads',
                'description' => 'Manage contact forms, enquiries, and customer replies.',
            ],
        ];
    }

    public static function faqs(): array
    {
        return [
            [
                'question' => 'Do I need a Google Business Profile to use 321Sites?',
                'answer' => 'No. You can build a site entirely from scratch by entering your own business details — name, address, hours, photos, services, and anything else you want to show. If you do have a Google Business Profile, you can search for it on the homepage and 321Sites will pre-fill your business name, address, phone, and hours for you to review and submit.',
            ],
            [
                'question' => 'What if my business information changes later?',
                'answer' => 'You can update everything directly inside your 321Sites dashboard at any time — no external account needed. If you originally imported from Google and your listing has since changed, you can manually update the relevant fields in the dashboard to keep things current.',
            ],
            [
                'question' => 'Can I use my own domain name?',
                'answer' => 'Yes. Premium plans let you connect your own custom domain. Free plans use your 321Sites subdomain.',
            ],
            [
                'question' => 'Will my site show up on Google?',
                'answer' => 'It can, as long as indexing is turned on in SEO & Visibility. 321Sites also gives you editable meta fields, schema markup, and a sitemap to help search engines understand the page.',
            ],
            [
                'question' => 'What happens if I cancel Premium?',
                'answer' => 'Your premium-only features, such as custom domains and advanced contact tools, stop working at the end of your billing period. Your account and website data are still kept unless you delete the account.',
            ],
        ];
    }

    public static function articleSummaries(): array
    {
        return self::articles()
            ->map(fn (array $article) => collect($article)->except(['sections', 'related'])->all())
            ->values()
            ->all();
    }

    public static function featuredArticles(): array
    {
        $featuredSlugs = [
            'get-your-business-online',
            'google-or-from-scratch',
            'connect-a-custom-domain',
            'customer-enquiries-and-contact-forms',
        ];

        return self::articles()
            ->filter(fn (array $article) => in_array($article['slug'], $featuredSlugs, true))
            ->values()
            ->all();
    }

    public static function findArticle(string $slug): ?array
    {
        return self::articles()
            ->firstWhere('slug', $slug);
    }

    public static function relatedArticles(array $article): array
    {
        return self::articles()
            ->filter(fn (array $candidate) => in_array($candidate['slug'], $article['related'] ?? [], true))
            ->map(fn (array $candidate) => collect($candidate)->except(['sections', 'related'])->all())
            ->values()
            ->all();
    }

    public static function categoriesWithCounts(): array
    {
        $counts = self::articles()
            ->countBy('category_slug');

        return collect(self::categories())
            ->map(fn (array $category) => [
                ...$category,
                'count' => $counts->get($category['slug'], 0),
            ])
            ->values()
            ->all();
    }

    private static function articles(): Collection
    {
        $categories = collect(self::categories())->keyBy('slug');

        return collect([
            [
                'slug' => 'get-your-business-online',
                'title' => 'Get your business online in five minutes',
                'summary' => 'Two ways to start: import from Google or fill in your own details. Either way, you can publish in minutes.',
                'read_time' => '4 min read',
                'category_slug' => 'getting-started',
                'search_terms' => ['launch', 'setup', 'publish', 'start', 'new site', 'from scratch', 'manual'],
                'sections' => [
                    [
                        'id' => 'two-ways-to-start',
                        'title' => 'Two ways to start',
                        'paragraphs' => [
                            'You do not need a Google Business Profile to build a site with 321Sites. There are two starting points — pick whichever suits you.',
                        ],
                        'bullets' => [
                            'Search for your Google listing on the homepage — 321Sites will pre-fill your business name, address, phone, and hours for you to review before submitting.',
                            'Or go straight to the setup form and fill everything in yourself — no Google account needed.',
                        ],
                    ],
                    [
                        'id' => 'what-you-can-change',
                        'title' => 'What you fill in during setup',
                        'paragraphs' => [
                            'The setup flow walks you through your business name and type, contact details, opening hours, a description, logo, photos, quick links, and services.',
                            'You can come back and edit everything later from your dashboard, so do not worry about making it perfect on day one.',
                        ],
                    ],
                    [
                        'id' => 'after-publishing',
                        'title' => 'What happens after publishing',
                        'paragraphs' => [
                            'Once you create your account, your site goes live on your 321Sites web address. From there, you can edit any content, connect a custom domain, manage customer enquiries, and update your SEO settings.',
                        ],
                    ],
                ],
                'related' => ['google-or-from-scratch', 'edit-your-site-content', 'seo-and-google-visibility'],
            ],
            [
                'slug' => 'google-or-from-scratch',
                'title' => 'Starting with Google or from scratch',
                'summary' => 'Google Business Profile is optional. Understand the difference between the two starting points and pick the right one for you.',
                'read_time' => '3 min read',
                'category_slug' => 'getting-started',
                'search_terms' => ['google listing', 'google business profile', 'GBP', 'need google', 'from scratch', 'manual entry', 'no google'],
                'sections' => [
                    [
                        'id' => 'starting-from-google',
                        'title' => 'Starting from a Google Business Profile',
                        'paragraphs' => [
                            'If your business already has a Google Business Profile, you can search for it on the 321Sites homepage. When you select your listing, 321Sites pre-fills your business name, address, phone number, and opening hours in the setup form.',
                            'You review those fields before submitting — nothing is saved until you confirm. Photos, reviews, and any other content are added by you during setup.',
                        ],
                    ],
                    [
                        'id' => 'starting-from-scratch',
                        'title' => 'Starting from scratch',
                        'paragraphs' => [
                            'No Google account? No problem. Click "Start from scratch" on the homepage and fill in your business details directly — name, type, address, phone number, opening hours, description, photos, services, and links.',
                            'Everything is stored in 321Sites and you can edit it any time from your dashboard. There is no need to connect a Google account at any point.',
                        ],
                    ],
                    [
                        'id' => 'which-to-choose',
                        'title' => 'Which should you choose?',
                        'paragraphs' => [
                            'Use the Google import if your listing is complete and up to date — it is the fastest way to get a polished site live.',
                            'Use the scratch form if you do not have a Google listing, your listing is incomplete or outdated, or you just prefer to control everything yourself.',
                        ],
                    ],
                ],
                'related' => ['get-your-business-online', 'edit-your-site-content'],
            ],
            [
                'slug' => 'edit-your-site-content',
                'title' => 'Edit your site content, services, and links',
                'summary' => 'Update the sections customers see, including your description, services, quick links, and contact details.',
                'read_time' => '5 min read',
                'category_slug' => 'editing-your-site',
                'search_terms' => ['services', 'buttons', 'content', 'edit website', 'logo', 'description'],
                'sections' => [
                    [
                        'id' => 'edit-my-site',
                        'title' => 'Use the Edit My Site tab',
                        'paragraphs' => [
                            'Your dashboard includes an Edit My Site area where you can control which sections appear, update your description, upload a logo, manage links, and fine-tune the overall content.',
                            'All content on your site is editable here — there is no external account required to make changes.',
                        ],
                    ],
                    [
                        'id' => 'quick-links',
                        'title' => 'Quick links and booking buttons',
                        'paragraphs' => [
                            'Quick links are perfect for booking systems, quote forms, menus, social profiles, or price lists. If 321Sites spots a booking platform on your website, it can suggest a link automatically.',
                            'If one of your quick links is clearly a booking link, 321Sites can also surface it as a more prominent call to action on the live site.',
                        ],
                    ],
                    [
                        'id' => 'logos-and-photos',
                        'title' => 'Logos, photos, and trust signals',
                        'paragraphs' => [
                            'A clear logo, a strong short description, and a couple of good photos usually make the biggest difference to how polished your site feels.',
                            'You can upload photos and a logo directly inside 321Sites during setup or any time afterwards from your dashboard.',
                        ],
                    ],
                ],
                'related' => ['get-your-business-online', 'customer-enquiries-and-contact-forms'],
            ],
            [
                'slug' => 'connect-a-custom-domain',
                'title' => 'Connect your own custom domain',
                'summary' => 'Use your own web address instead of an 321Sites subdomain.',
                'read_time' => '6 min read',
                'category_slug' => 'domains-and-billing',
                'search_terms' => ['custom domain', 'domain', 'dns', 'www', 'go live'],
                'sections' => [
                    [
                        'id' => 'what-you-need',
                        'title' => 'What you need before you begin',
                        'paragraphs' => [
                            'Custom domains are available on Premium. You will need to own the domain already through a registrar such as GoDaddy, Cloudflare, or another provider.',
                            'Inside the Web Address tab, enter your domain and choose whether to connect it automatically or manually, depending on your provider.',
                        ],
                    ],
                    [
                        'id' => 'automatic-vs-manual',
                        'title' => 'Automatic vs manual setup',
                        'paragraphs' => [
                            'If your provider supports automatic setup, 321Sites can guide you through a consent flow and add the DNS records for you.',
                            'If not, the dashboard shows the exact DNS records to add manually. Once the records are in place, use the verification button in the dashboard.',
                        ],
                    ],
                    [
                        'id' => 'after-connection',
                        'title' => 'After your domain is connected',
                        'paragraphs' => [
                            'Your old 321Sites subdomain can still work as a backup, but the custom domain becomes the main address for customers and search engines.',
                            '321Sites also uses the custom domain as the canonical URL when the site is live there.',
                        ],
                    ],
                ],
                'related' => ['free-vs-premium', 'seo-and-google-visibility'],
            ],
            [
                'slug' => 'free-vs-premium',
                'title' => 'Free vs Premium: what changes when you upgrade',
                'summary' => 'A simple breakdown of which features are included for free and which ones need Premium.',
                'read_time' => '4 min read',
                'category_slug' => 'domains-and-billing',
                'search_terms' => ['pricing', 'premium', 'free plan', 'upgrade', 'billing'],
                'sections' => [
                    [
                        'id' => 'free-plan',
                        'title' => 'What you get on the free plan',
                        'paragraphs' => [
                            'The free plan is designed to get a business online quickly. You can publish a simple site on an 321Sites web address and edit the core content.',
                        ],
                        'bullets' => [
                            '321Sites subdomain',
                            'Editable content and design basics',
                            'Start from Google import or fill in your own details',
                        ],
                    ],
                    [
                        'id' => 'premium-features',
                        'title' => 'What Premium adds',
                        'paragraphs' => [
                            'Premium is for businesses that want more control and stronger lead generation.',
                        ],
                        'bullets' => [
                            'Custom domains',
                            'Advanced contact tools and customer enquiry handling',
                            'Google Analytics connection',
                            'Other premium-only growth features as they roll out',
                        ],
                    ],
                    [
                        'id' => 'what-if-you-cancel',
                        'title' => 'What happens if you cancel',
                        'paragraphs' => [
                            'If you cancel Premium, your premium-only features stop working at the end of the billing period. Your account and content stay in place unless you choose to remove them.',
                        ],
                    ],
                ],
                'related' => ['connect-a-custom-domain', 'customer-enquiries-and-contact-forms'],
            ],
            [
                'slug' => 'keeping-your-site-up-to-date',
                'title' => 'Keeping your site content up to date',
                'summary' => 'How to update your hours, photos, reviews, description, and contact details as your business changes.',
                'read_time' => '3 min read',
                'category_slug' => 'editing-your-site',
                'search_terms' => ['refresh', 'update hours', 'reviews', 'photos', 'change details', 'edit content'],
                'sections' => [
                    [
                        'id' => 'edit-in-dashboard',
                        'title' => 'Edit everything in your dashboard',
                        'paragraphs' => [
                            'All of your site content — business name, address, phone, opening hours, description, photos, services, and links — can be updated directly from your dashboard at any time.',
                            'Head to the Edit My Site tab and change whatever needs updating. Save, and your site reflects the new information immediately.',
                        ],
                    ],
                    [
                        'id' => 'if-you-started-from-google',
                        'title' => 'If you started from a Google listing',
                        'paragraphs' => [
                            'When you chose your Google listing during setup, the details were pre-filled into the setup form and saved when you submitted. That information now lives in 321Sites and is not linked to Google — changes on your Google listing do not flow through automatically.',
                            'To keep things current, just update the relevant fields in your dashboard directly.',
                        ],
                    ],
                    [
                        'id' => 'reviews',
                        'title' => 'Keeping reviews current',
                        'paragraphs' => [
                            'Reviews are added manually during setup and stored in 321Sites. You can edit, remove, or add reviews from the dashboard at any time.',
                        ],
                    ],
                ],
                'related' => ['google-or-from-scratch', 'seo-and-google-visibility'],
            ],
            [
                'slug' => 'customer-enquiries-and-contact-forms',
                'title' => 'Customer enquiries and contact forms',
                'summary' => 'How the contact form works, where messages appear, and how to reply quickly.',
                'read_time' => '5 min read',
                'category_slug' => 'messages-and-leads',
                'search_terms' => ['contact form', 'messages', 'enquiries', 'inbox', 'reply'],
                'sections' => [
                    [
                        'id' => 'where-messages-go',
                        'title' => 'Where messages go',
                        'paragraphs' => [
                            'When someone sends a message through your website, it appears inside your Customer Enquiries inbox in the dashboard.',
                            'If the feature is enabled for your plan, 321Sites can also email the enquiry through to the contact email on the site.',
                        ],
                    ],
                    [
                        'id' => 'preferred-time',
                        'title' => 'Preferred date and time requests',
                        'paragraphs' => [
                            'Appointment-style businesses can collect an optional preferred date and time from customers. That request appears in the inbox alongside the message so it is easy to reply with a suitable slot.',
                        ],
                    ],
                    [
                        'id' => 'replying-fast',
                        'title' => 'Tips for replying fast',
                        'paragraphs' => [
                            'Fast replies usually convert best. Keep your contact email up to date, check the Customer Enquiries tab regularly, and reply directly from your inbox when possible.',
                        ],
                    ],
                ],
                'related' => ['edit-your-site-content', 'free-vs-premium'],
            ],
            [
                'slug' => 'seo-and-google-visibility',
                'title' => 'SEO and Google visibility basics',
                'summary' => 'Understand the SEO & Visibility settings and the quickest wins for local search.',
                'read_time' => '6 min read',
                'category_slug' => 'seo-and-visibility',
                'search_terms' => ['SEO', 'google', 'meta title', 'meta description', 'indexing', 'search console'],
                'sections' => [
                    [
                        'id' => 'meta-fields',
                        'title' => 'Use the meta title and description well',
                        'paragraphs' => [
                            'The SEO & Visibility tab lets you edit the page title and short description that search engines may show in results.',
                            'Try to include your business name, your main service, and the town or area you serve, without sounding robotic.',
                        ],
                    ],
                    [
                        'id' => 'google-preview',
                        'title' => 'Use the Google preview as you type',
                        'paragraphs' => [
                            'The preview updates while you edit your title and description so you can sense-check how your listing may appear before you save it.',
                            'It is not a guarantee of exactly what Google will show, but it is a useful guide.',
                        ],
                    ],
                    [
                        'id' => 'indexing',
                        'title' => 'Turn indexing on when you are ready',
                        'paragraphs' => [
                            'If you want your site to appear on Google, keep indexing enabled. Turn it off only if you are still working on the site privately.',
                        ],
                    ],
                    [
                        'id' => 'local-seo-basics',
                        'title' => 'Quick local SEO wins',
                        'paragraphs' => [
                            'Make sure your business name, address, phone number, and category are accurate in your 321Sites dashboard. Add recent photos, keep your hours current, and write a clear service-focused description.',
                            'These basics usually matter far more than trying to cram in lots of keywords.',
                        ],
                    ],
                ],
                'related' => ['connect-a-custom-domain', 'keeping-your-site-up-to-date'],
            ],
            [
                'slug' => 'google-analytics-and-traffic',
                'title' => 'Connect Google Analytics and understand traffic',
                'summary' => 'Track how many people visit your site and where they come from.',
                'read_time' => '4 min read',
                'category_slug' => 'seo-and-visibility',
                'search_terms' => ['analytics', 'visitors', 'traffic', 'measurement id', 'ga4'],
                'sections' => [
                    [
                        'id' => 'what-you-need',
                        'title' => 'What you need',
                        'paragraphs' => [
                            'You need a Google Analytics property and a Measurement ID that looks like G-XXXXXXXXXX.',
                            'Paste that Measurement ID into the SEO & Visibility tab and save your changes.',
                        ],
                    ],
                    [
                        'id' => 'what-you-can-learn',
                        'title' => 'What Analytics can tell you',
                        'paragraphs' => [
                            'Analytics helps you understand whether people are finding your site, which pages they are landing on, and whether your marketing efforts are driving visits.',
                        ],
                    ],
                    [
                        'id' => 'keep-it-simple',
                        'title' => 'Keep it simple at first',
                        'paragraphs' => [
                            'For most small businesses, the most useful starting questions are just how many people are visiting, which channels are working, and whether traffic improves over time.',
                        ],
                    ],
                ],
                'related' => ['seo-and-google-visibility', 'free-vs-premium'],
            ],
        ])->map(function (array $article) use ($categories) {
            $category = $categories->get($article['category_slug']);

            return [
                ...$article,
                'category_title' => $category['title'],
                'category_description' => $category['description'],
            ];
        });
    }
}
