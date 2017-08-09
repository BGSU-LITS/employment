<?php
/**
 * Session Manager Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App;

/**
 * A simple class to manage sessions.
 */
class Session
{
    /**
     * Data stored in the session.
     * @var array
     */
    private $session;

    /**
     * Construct the class with session data.
     * @param array $session Session data, typically $_SESSION superglobal.
     */
    public function __construct(&$session)
    {
        $this->session = &$session;
    }

    /**
     * Get an item from the session data.
     * @param string $key The key of the data to get.
     * @return mixed The item from the session data.
     */
    public function __get($key)
    {
        return $this->session[$key];
    }

    /**
     * Checks if a certain item is set within the session data.
     * @param string $key The key of the data to check.
     * @return bool Whether the item is set in the session data.
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->session);
    }

    /**
     * Sets an item in the session data.
     * @param string $key The key of the data to be set.
     * @param mixed $value The value of the data to be set.
     */
    public function __set($key, $value)
    {
        $this->session[$key] = $value;
    }

    /**
     * Removes an item from the session data.
     * @param string $key The key of the data to be remvoed.
     */
    public function __unset($key)
    {
        unset($this->session[$key]);
    }
}
