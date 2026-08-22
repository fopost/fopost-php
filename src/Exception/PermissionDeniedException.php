<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** 403: the key is valid but lacks the scope or workspace access. */
class PermissionDeniedException extends FopostException
{
}
