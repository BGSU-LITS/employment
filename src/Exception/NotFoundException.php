<?php
/**
 * Not Found Exception Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Exception;

/**
 * An exception for when a resource could not be found.
 */
class NotFoundException extends AbstractException
{
    public $title = 'Not Found';
    public $message = 'The requested resource could not be found.';
}
