<?php
declare(strict_types = 1);
/*
 * Go! AOP framework
 *
 * @copyright Copyright 2013, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Go\Aop\Framework;

use Closure;
use Go\Aop\Intercept\Joinpoint;
use function get_class, is_string;

/**
 * Interceptor to dynamically trigger an user notice/warning/error on method call
 *
 * This interceptor can be used as active replacement for the "deprecated" tag or to notify about
 * probable issues with specific method.
 */
final class DeclareErrorInterceptor extends AbstractInterceptor
{

    /**
     * Error message to show for this interceptor
     *
     * @var string
     */
    protected $message;

    /**
     * Default level of error
     *
     * @var int
     */
    protected $level;

    /**
     * Default constructor for interceptor
     */
    public function __construct(string $message, int $errorLevel, string $pointcutExpression)
    {
        $adviceMethod  = self::getDeclareErrorAdvice();
        $this->message = $message;
        $this->level   = $errorLevel;
        parent::__construct($adviceMethod, -256, $pointcutExpression);
    }

    /**
     * @inheritdoc
     */
    public static function unserializeAdvice(array $adviceData): Closure
    {
        return self::getDeclareErrorAdvice();
    }

    /**
     * @inheritdoc
     */
    public function invoke(Joinpoint $joinpoint)
    {
        $reflection    = $joinpoint->getStaticPart();
        $reflectorName = 'unknown';
        if ($reflection && method_exists($reflection, 'getName')) {
            $reflectorName = $reflection->getName();
        }
        ($this->adviceMethod)($joinpoint->getThis(), $reflectorName, $this->message, $this->level);

        return $joinpoint->proceed();
    }

    /**
     * Returns an advice
     */
    private static function getDeclareErrorAdvice(): Closure
    {
        static $adviceMethod;
        if (!$adviceMethod) {
            $adviceMethod = function ($object, $reflectorName, $message, $level = E_USER_NOTICE) {
                $class   = is_string($object) ? $object : get_class($object);
                $message = vsprintf('[AOP Declare Error]: %s has an error: "%s"', [
                    $class . '->' . $reflectorName,
                    $message
                ]);
                trigger_error($message, $level);
            };
        }

        return $adviceMethod;
    }
}
