<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Transformer\Exception;

/**
 * This is thrown if a transformer had an issue transforming a message.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ConvertToArrayFailedException extends \RuntimeException implements TransformerException
{
}
