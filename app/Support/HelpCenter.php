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
                'answer' => 'Yes. 321Sites starts by pulling in the information from your Google Business Profile, so you need a live listing before you can generate a site. If you do not have one yet, our help team can point you in the right direction.',
            ],
            [
                'question' => 'What if my business information changes later?',
                'answer' => 'You can update your website inside 321Sites, and your core Google Business Profile information can also be refreshed into the site later. That keeps your hours, reviews, photos, and contact details aligned.',
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
            'connect-a-custom-domain',
            'customer-enquiries-and-contact-forms',
            'seo-and-google-visibility',
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
                'summary' => 'Search for your Google Business Profile, personalise the essentials, and publish your site.',
                'read_time' => '4 min read',
                'category_slug' => 'getting-started',
                'search_terms' => ['launch', 'setup', 'publish', 'start', 'new site'],
                'sections' => [
                    [
                        'id' => 'before-you-start',
                        'title' => 'Before you start',
                        'paragraphs' => [
                            '321Sites works best when your Google Business Profile already has your business name, phone number, address, opening hours, and a few photos in place.',
                            'You do not need any technical skills. If your listing is live on Google, you can usually have a website published in a few minutes.',
                        ],
                        'bullets' => [
                            'Search for your business on the 321Sites homepage.',
                            'Choose the correct Google listing from the results.',
                            'Add anything you want to personalise, such as a logo, description, links, or services.',
                            'Create your account and publish the site.',
                        ],
                    ],
                    [
                        'id' => 'what-you-can-change',
                        'title' => 'What you can change during setup',
                        'paragraphs' => [
                            'The setup flow lets you add a logo, write a friendlier description, add booking or quote buttons, and fine-tune the content customers see first.',
                            'You can come back and edit the site later from your dashboard, so do not worry about making everything perfect on day one.',
                        ],
                    ],
                    [
                        'id' => 'after-publishing',
                        'title' => 'What happens after publishing',
                        'paragraphs' => [
                            'Once you create your account, your site goes live on your 321Sites web address. From there, you can edit content, connect a custom domain, manage enquiries, and update your SEO settings.',
                        ],
                    ],
                ],
                'related' => ['do-you-need-a-google-business-profile', 'edit-your-site-content', 'seo-and-google-visibility'],
            ],
            [
                'slug' => 'do-you-need-a-google-business-profile',
                'title' => 'Do you need a Google Business Profile?',
                'summary' => 'Why 321Sites starts with Google, and what to do if you do not have a listing yet.',
                'read_time' => '3 min read',
                'category_slug' => 'getting-started',
                'search_terms' => ['google listing', 'google business profile', 'GBP', 'need google'],
                'sections' => [
                    [
                        'id' => 'why-google-matters',
                        'title' => 'Why Google matters',
                        'paragraphs' => [
                            '321Sites uses your Google Business Profile as the starting point for your website. That means your business name, address, phone number, opening hours, reviews, and photos can all be pulled into the site automatically.',
                            'For sole traders and small businesses, this saves a lot of time because you are not typing everything out from scratch.',
                        ],
                    ],
                    [
                        'id' => 'if-you-do-not-have-one',
                        'title' => 'If you do not have a listing yet',
                        'paragraphs' => [
                            'You will need to create and verify a Google Business Profile before you can build a site with 321Sites.',
                            'If you are unsure where to start, email help@321sites.com and we can guide you through the basics.',
                        ],
                        'bullets' => [
                            'Use your real business name and contact details.',
                            'Choose the closest business category you can.',
                            'Add a few photos and your opening hours.',
                            'Wait until Google shows the listing publicly before searching for it inside 321Sites.',
                        ],
                    ],
                ],
                'related' => ['get-your-business-online', 'refresh-google-business-profile-data'],
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
                            'This is the best place to make day-to-day changes without touching your Google listing.',
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
                            'If your business relies on visual proof, make sure your Google listing has recent photos as well as anything you upload directly inside 321Sites.',
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
                            'Google Business Profile-powered site setup',
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
                'slug' => 'refresh-google-business-profile-data',
                'title' => 'Refresh your Google Business Profile data',
                'summary' => 'Keep your site aligned with Google when your hours, photos, reviews, or contact details change.',
                'read_time' => '4 min read',
                'category_slug' => 'editing-your-site',
                'search_terms' => ['refresh', 'google sync', 'update hours', 'reviews', 'photos'],
                'sections' => [
                    [
                        'id' => 'when-to-refresh',
                        'title' => 'When to refresh',
                        'paragraphs' => [
                            'If you have updated your opening hours, photos, reviews, phone number, or other public information on Google, it makes sense to refresh your site data afterwards so the website stays current.',
                        ],
                    ],
                    [
                        'id' => 'what-updates',
                        'title' => 'What refreshes from Google',
                        'paragraphs' => [
                            'Core Google-driven details such as your business profile information, reviews, and photos can be pulled in again. Any direct overrides you added inside 321Sites should still take priority where relevant.',
                        ],
                    ],
                    [
                        'id' => 'best-practice',
                        'title' => 'Best practice',
                        'paragraphs' => [
                            'Treat Google as the source of truth for your core business details, and use 321Sites for the extra polish that turns that listing into a proper website.',
                        ],
                    ],
                ],
                'related' => ['do-you-need-a-google-business-profile', 'seo-and-google-visibility'],
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
                            'Make sure your business name, address, phone number, and category are accurate on Google. Add recent photos, keep your hours current, and write a clear service-focused description.',
                            'These basics usually matter far more than trying to cram in lots of keywords.',
                        ],
                    ],
                ],
                'related' => ['connect-a-custom-domain', 'refresh-google-business-profile-data'],
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
