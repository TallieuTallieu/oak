<?php

namespace Oak\Cookie;

use Oak\Contracts\Cookie\CookieInterface;

class Cookie implements CookieInterface
{
    /**
     * The `SameSite` values browsers accept
     *
     * @var array<int, string>
     */
    private const SAME_SITE_VALUES = ['Lax', 'Strict', 'None'];

    /**
     * @var string $path
     */
    private $path;

    /**
     * @var bool $secure
     */
    private $secure;

    /**
     * @var bool $httpOnly
     */
    private $httpOnly;

    /**
     * @var 'Lax'|'Strict'|'None' $sameSite
     */
    private $sameSite;

    /**
     * Cookie constructor.
     *
     * @param string $path
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite One of `Lax`, `Strict` or `None`
     * @throws \InvalidArgumentException When $sameSite is not a value browsers
     *                                   accept, or when `None` is combined with
     *                                   an insecure cookie
     */
    public function __construct(
        string $path,
        bool $secure,
        bool $httpOnly,
        string $sameSite = 'Lax',
    ) {
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $this->normalizeSameSite($sameSite, $secure);
    }

    /**
     * Validates a `SameSite` value and normalizes its casing
     *
     * @param string $sameSite
     * @param bool $secure
     * @return 'Lax'|'Strict'|'None'
     * @throws \InvalidArgumentException
     */
    private function normalizeSameSite(string $sameSite, bool $secure): string
    {
        $normalized = match (strtolower($sameSite)) {
            'lax' => 'Lax',
            'strict' => 'Strict',
            'none' => 'None',
            default => throw new \InvalidArgumentException(
                'Invalid cookie SameSite value "' .
                    $sameSite .
                    '", expected one of: ' .
                    implode(', ', self::SAME_SITE_VALUES),
            ),
        };

        // Browsers reject `SameSite=None` on a cookie that is not `Secure`,
        // which drops the cookie without a word. Refuse the combination here
        // instead, where it is still traceable to the configuration.
        if ($normalized === 'None' && !$secure) {
            throw new \InvalidArgumentException(
                'Cookie SameSite "None" requires the cookie to be secure. ' .
                    'Set "cookie.secure" to true or pick another SameSite value.',
            );
        }

        return $normalized;
    }

    /**
     * Gets the `SameSite` value cookies are written with
     *
     * @return string
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @return mixed|void
     */
    public function set(string $name, $value, int $expire = 0)
    {
        $value = json_encode($value);

        if ($value === false) {
            throw new \InvalidArgumentException(
                'Cookie value could not be encoded as JSON',
            );
        }

        // The options-array form is the only one that can write `SameSite`
        setcookie($name, $value, [
            'expires' => $expire,
            'path' => $this->path,
            'domain' => '',
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);

        $_COOKIE[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) ? json_decode($value) : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Removes a cookie by expiring it in the past
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $this->path,
            'domain' => '',
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);

        unset($_COOKIE[$name]);
    }
}
