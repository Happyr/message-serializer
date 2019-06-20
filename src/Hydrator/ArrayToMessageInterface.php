<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator;

use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;

interface ArrayToMessageInterface
{
    /**
     * @return object
     *
     * @throws HydratorException
     */
    public function toMessage(array $data);
}
