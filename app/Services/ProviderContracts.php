<?php

declare(strict_types=1);

interface AnimeMetadataProvider
{
    public static function getAnimeDetail(int $id): ?array;
}

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
    public static function findChapters(array $queries): array;

    /**
     * Get chapter pages
     * @return array<int, string>
     */
    public static function getPages(string $chapterId): array;
}
