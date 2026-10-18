<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A public source stopped serving pages anonymously (sign-up wall or rate limit).
 * The affected companies must stay pending and be retried later; scraping must not
 * work around the wall.
 */
class SourceBlockedException extends RuntimeException {}
