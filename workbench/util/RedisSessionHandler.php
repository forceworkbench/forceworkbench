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

    public function destroy($session_id): bool
    {
        $this->redis->del($session_id);

        return true;
    }

    public function gc($max_lifetime): int
    {
        return true;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function read($session_id): string
    {
        if ($result = $this->redis->get($session_id)) {
            return $result;
        }

        return '';
    }

    public function write($session_id, $session_data): bool
    {
        $this->redis->setEx($session_id, $this->ttl, $session_data);

        return true;
    }

    public function validateId($session_id)
    {
        return $this->read($session_id) != '';
    }

    public function updateTimestamp($session_id, $session_data)
    {
        return $this->write($session_id, $session_data);
    }
}