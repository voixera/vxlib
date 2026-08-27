<?php

declare(strict_types=1);

interface MangaMetadataProvider
{
    public static function getMangaDetail(int $id): ?array;
}

interface ChapterProvider
{
    public static function getProviderName(): string;

    /**
     * Search manga chapter feed
     * @param array<string> $queries
     * @return array<int, array{id: string, number: string, title: string, language: ?string, publish_date: ?string, group: ?string, source: string}>
     */
    public static function findChapters(array $queries, ?int $year = null, ?string $author = null): array;

    /**
     * Get chapter pages
     * @return array<int, string>
     */
    public static function getPages(string $chapterId): array;
}

/**
 * Metadata + chapter discovery for a manga/manhwa/manhua source.
 * Implemented by swappable providers (WeebCentral, MangaDex, …).
 */
interface MangaProvider
{
    /** Human-readable provider name. */
    public static function getName(): string;

    /**
     * Search titles.
     * @return array{items: array<int, array{id: string, title: string, cover: ?string, type: ?string, url_detail: string}>, has_next: bool, query: string}
     */
    public static function search(string $query, int $page = 1): array;

    /** Series metadata by provider id. */
    public static function detail(string $id): ?array;

    /**
     * Chapter list for a series.
     * @return array<int, array{id: string, number: ?string, title: string, date: ?string, url: string}>
     */
    public static function chapters(string $id, int $page = 1): array;
}

/**
 * Assembles the full reader view-model so the UI never depends on a concrete provider.
 */
interface ReaderProvider
{
    /**
     * @return ?array{series: ?array, chapters: array, current: ?array, prev: ?string, next: ?string, provider: string}
     */
    public static function read(string $seriesId, string $chapterId): ?array;
}
