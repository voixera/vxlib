<?php

declare(strict_types=1);

/** User accounts (Discord identities). */

final class UserRepository
{
    private SupabaseClient $db;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->db = $client ?? new SupabaseClient();
    }

    public function findByDiscordId(string $discordId): ?array
    {
        if (!SupabaseClient::configured()) return null;
        return $this->db->selectOne('users', ['select' => '*', 'discord_id' => 'eq.' . $discordId], privileged: true);
    }

    public function find(int $id): ?array
    {
        if (!SupabaseClient::configured()) return null;
        return $this->db->selectOne('users', ['select' => '*', 'id' => 'eq.' . $id], privileged: true);
    }

    /** Create or refresh a user from a Discord profile. */
    public function upsertFromDiscord(array $discordUser, ?string $email): ?array
    {
        if (!SupabaseClient::configured() || empty($discordUser['id'])) return null;

        $now = gmdate('c');
        $row = [
            'discord_id'   => (string)$discordUser['id'],
            'username'     => mb_substr((string)($discordUser['username'] ?? ''), 0, 64),
            'display_name' => mb_substr((string)($discordUser['global_name'] ?? '') !== '' ? $discordUser['global_name'] : ($discordUser['username'] ?? ''), 0, 64),
            'avatar_url'   => self::avatarUrl($discordUser),
            'email'        => $email,
            'updated_at'   => $now,
        ];

        $existing = $this->findByDiscordId((string)$discordUser['id']);
        if ($existing) {
            $res = $this->db->update('users', ['id' => (string)$existing['id']], $row);
            return $res['ok'] ? array_merge($existing, $row) : $existing;
        }

        $row['created_at'] = $now;
        $res = $this->db->insert('users', $row);
        $inserted = is_array($res['data']) && isset($res['data'][0]) ? $res['data'][0] : null;
        return $inserted ?? ($this->findByDiscordId((string)$discordUser['id']));
    }

    /** Persist reader/UI preferences (jsonb). */
    public function savePrefs(int $userId, array $prefs): bool
    {
        $res = $this->db->update('users', ['id' => (string)$userId], ['prefs' => $prefs]);
        return (bool)$res['ok'];
    }

    public static function savePrefsStatic(int $userId, array $prefs): bool
    {
        return (new self())->savePrefs($userId, $prefs);
    }

    public static function avatarUrl(array $discordUser): ?string
    {
        $id = $discordUser['id'] ?? null;
        $hash = $discordUser['avatar'] ?? null;
        if (!$id || !$hash) return null;
        return 'https://cdn.discordapp.com/avatars/' . $id . '/' . $hash . '.png?size=128';
    }
}
