<?php

declare(strict_types=1);

/**
 * Thin Supabase REST (PostgREST) client.
 * ALL calls happen server-side. The service-role key never reaches the browser;
 * the anon key is only used for public reads so RLS stays meaningful.
 */

final class SupabaseClient
{
    private string $url;

    public function __construct(?string $url = null, private ?string $anonKey = null, private ?string $serviceKey = null)
    {
        $this->url        = rtrim($url ?? (string)Config::get('SUPABASE_URL', ''), '/');
        $this->anonKey    = $anonKey ?? Config::get('SUPABASE_ANON_KEY');
        $this->serviceKey = $serviceKey ?? Config::get('SUPABASE_SERVICE_ROLE_KEY');
    }

    public static function configured(): bool
    {
        return (bool)Config::get('SUPABASE_URL') && (bool)Config::get('SUPABASE_ANON_KEY');
    }

    /**
     * @param array<string,string> $query   PostgREST query params
     * @param array<string,string> $headers extra headers (Prefer, Range…)
     * @return array{ok:bool,status:int,data:mixed,error:?string,headers:array<string,string>}
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
        bool $privileged = false,
        array $headers = []
    ): array {
        if (!$this->url || !$this->anonKey) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'supabase_not_configured', 'headers' => []];
        }

        $key = ($privileged && $this->serviceKey) ? $this->serviceKey : $this->anonKey;
        $qs  = '';
        foreach ($query as $k => $v) {
            // PostgREST operators contain dots/commas/parens; encode only what's required.
            $qs .= ($qs === '' ? '?' : '&') . rawurlencode($k) . '=' . str_replace('%2C', ',', rawurlencode((string)$v));
        }

        $headerLines = array_merge([
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ], array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers));

        $ch = curl_init($this->url . '/rest/v1/' . ltrim($path, '/') . $qs);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders) {
                $parts = explode(':', trim($line), 2);
                if (count($parts) === 2) $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status === 0) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err ?: 'network_error', 'headers' => []];
        }

        $data  = json_decode((string)$raw, true);
        $error = null;
        if ($status >= 400) {
            $error = is_array($data) ? (string)($data['message'] ?? $data['error_description'] ?? 'request_failed') : 'request_failed';
        }

        return [
            'ok'      => $status >= 200 && $status < 300,
            'status'  => $status,
            'data'    => $data,
            'error'   => $error,
            'headers' => $respHeaders ?? [],
        ];
    }

    /** Total row count from Content-Range when Prefer: count=exact was sent. */
    public static function totalCount(array $response): ?int
    {
        $range = $response['headers']['content-range'] ?? '';
        if ($range !== '' && str_contains($range, '/')) {
            $t = explode('/', $range, 2)[1];
            if (ctype_digit($t)) return (int)$t;
        }
        return null;
    }

    /** SELECT rows (+ optional exact total). */
    public function select(string $table, array $query, bool $privileged = false, bool $withCount = false): array
    {
        $headers = $withCount ? ['Prefer' => 'count=exact'] : [];
        $res = $this->request('GET', $table, $query, null, $privileged, $headers);
        return [
            'rows'   => is_array($res['data']) && array_is_list($res['data']) ? $res['data'] : [],
            'total'  => $withCount ? self::totalCount($res) : null,
            'ok'     => $res['ok'],
            'status' => $res['status'],
            'error'  => $res['error'],
        ];
    }

    public function selectOne(string $table, array $query, bool $privileged = false): ?array
    {
        $query['limit'] = '1';
        $res = $this->select($table, $query, $privileged);
        return $res['rows'][0] ?? null;
    }

    public function insert(string $table, array $row, bool $upsert = false, string $onConflict = ''): array
    {
        $query = [];
        if ($upsert) {
            $query['on_conflict'] = $onConflict;
            return $this->request('POST', $table, $query, $row, true, ['Prefer' => 'return=representation,resolution=merge-duplicates']);
        }
        return $this->request('POST', $table, [], $row, true, ['Prefer' => 'return=representation']);
    }

    public function update(string $table, array $match, array $changes): array
    {
        $query = [];
        foreach ($match as $col => $val) $query[$col] = 'eq.' . $val;
        return $this->request('PATCH', $table, $query, $changes, true);
    }

    public function delete(string $table, array $match): array
    {
        $query = [];
        foreach ($match as $col => $val) $query[$col] = 'eq.' . $val;
        return $this->request('DELETE', $table, $query, null, true);
    }
}
