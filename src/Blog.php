<?php

declare(strict_types=1);

namespace Fopost\Sdk;

/**
 * Every status a blog article or keyword can hold, and where articles can publish.
 *
 * Model fields stay plain strings, so a status added server side still
 * parses on an older SDK.
 */
final class Blog
{
    /** @var array<int, string> */
    public const ARTICLE_STATUSES = [
        'queued',
        'writing',
        'draft',
        'scheduled',
        'publishing',
        'published',
        'failed',
        'rejected',
    ];

    /** @var array<int, string> */
    public const KEYWORD_STATUSES = ['suggested', 'planned', 'written', 'dismissed'];

    /** @var array<int, string> */
    public const TARGET_KINDS = ['product_blog', 'account', 'website'];

    /** @var array<int, string> */
    public const IMAGE_TIERS = ['draft', 'standard', 'best'];

    /** The webhook event a published article raises. */
    public const ARTICLE_PUBLISHED_EVENT = 'article.published';
}
