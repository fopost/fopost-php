<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** Any other non-2xx response, including 5xx and unmapped 4xx. */
class ApiException extends FopostException
{
}
