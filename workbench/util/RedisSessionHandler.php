<?php

//source: https://gist.github.com/zacharyrankin/51cc9fe809486be31ac4083af7631e66

class RedisSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private Redis $redis;
    private int $ttl;

    public function __construct(Redis $redis, int $ttl)
    {
        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $session_id): bool
    {
        $this->redis->del($session_id);

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $session_id): string
    {
        if ($result = $this->redis->get($session_id)) {
            return $result;
        }

        return '';
    }

    public function write(string $session_id, string $session_data): bool
    {
        $this->redis->setEx($session_id, $this->ttl, $session_data);

        return true;
    }

    public function validateId(string $session_id): bool
    {
        return $this->read($session_id) != '';
    }

    public function updateTimestamp(string $session_id, string $session_data): bool
    {
        return $this->write($session_id, $session_data);
    }
}