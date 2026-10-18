<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeInterface;
use Fopost\Sdk\Http\HttpClient;
use Fopost\Sdk\Model\Model;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PageMeta;
use InvalidArgumentException;

abstract class Resource
{
    public function __construct(protected readonly HttpClient $http)
    {
    }

    protected static function unwrap(mixed $body): mixed
    {
        return HttpClient::unwrap($body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    protected static function compact(array $body): array
    {
        return array_filter($body, static fn (mixed $v): bool => $v !== null);
    }

    /** @return array<string, mixed> */
    protected static function asArray(mixed $body): array
    {
        return is_array($body) ? $body : ['data' => $body];
    }

    /**
     * Items under `data` plus the sibling `meta`, as one Page.
     *
     * @template T of Model
     * @param class-string<T> $model
     * @return Page<T>
     */
    protected static function page(string $model, mixed $body): Page
    {
        $items = $model::listFrom(is_array($body) && isset($body['data']) ? $body['data'] : $body);
        $rawMeta = is_array($body) && isset($body['meta']) && is_array($body['meta']) ? $body['meta'] : null;

        return new Page($items, $rawMeta !== null ? PageMeta::fromArray($rawMeta) : PageMeta::empty());
    }

    protected static function iso(string|DateTimeInterface|null $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return str_replace('+00:00', 'Z', $value->format(DateTimeInterface::ATOM));
        }

        return $value;
    }

    /**
     * A path becomes {filename, contents, mimeType}; an array is checked for the same keys.
     *
     * @param array<int, string|array<string, mixed>> $files
     * @return array<int, array{filename: string, contents: string, mimeType: string}>
     */
    protected static function normalizeFiles(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            if (is_string($file)) {
                $contents = is_file($file) ? file_get_contents($file) : false;
                if ($contents === false) {
                    throw new InvalidArgumentException("fopost: cannot read file {$file}");
                }
                $mime = function_exists('mime_content_type') ? mime_content_type($file) : false;
                $out[] = [
                    'filename' => basename($file),
                    'contents' => $contents,
                    'mimeType' => is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream',
                ];
                continue;
            }
            $filename = $file['filename'] ?? null;
            $contents = $file['contents'] ?? null;
            if (!is_string($filename) || $filename === '' || !is_string($contents)) {
                throw new InvalidArgumentException('fopost: a file needs a filename and its contents');
            }
            $mime = $file['mimeType'] ?? null;
            $out[] = [
                'filename' => $filename,
                'contents' => $contents,
                'mimeType' => is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream',
            ];
        }

        return $out;
    }
}
