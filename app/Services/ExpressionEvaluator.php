<?php

namespace App\Services;

use NXP\Exception\DivisionByZeroException;
use NXP\Exception\MathExecutorException;
use NXP\MathExecutor;
use Throwable;

class ExpressionEvaluator
{
    /**
     * Evaluate a mathematical expression string.
     *
     * @return array{
     *     expression: string,
     *     had_error: bool,
     *     result: string|null,
     *     error_message: string|null
     * }
     */
    public function evaluate(string $expression): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(10);
        }

        $trimmed = trim($expression);

        $executor = new MathExecutor();

        try {
            $this->assertExpressionComplexity($trimmed);
            $value = $executor->execute($trimmed);

            return [
                'expression' => $trimmed,
                'had_error' => false,
                'result' => is_numeric($value) ? (string) $value : null,
                'error_message' => null,
            ];
        } catch (DivisionByZeroException $exception) {
            return [
                'expression' => $trimmed,
                'had_error' => true,
                'result' => null,
                'error_message' => 'Division by zero is not allowed.',
            ];
        } catch (MathExecutorException|Throwable $exception) {
            return [
                'expression' => $trimmed,
                'had_error' => true,
                'result' => null,
                'error_message' => 'The expression could not be evaluated.',
            ];
        }
    }

    private function assertExpressionComplexity(string $expression): void
    {
        $maxDepth = 20;
        $maxOperators = 100;

        $depth = 0;
        $operators = 0;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($char === '(') {
                $depth++;

                if ($depth > $maxDepth) {
                    throw new MathExecutorException('Expression is too deeply nested.');
                }
            } elseif ($char === ')') {
                if ($depth > 0) {
                    $depth--;
                }
            } elseif (str_contains('+-*/^', $char)) {
                $operators++;

                if ($operators > $maxOperators) {
                    throw new MathExecutorException('Expression is too complex.');
                }
            }
        }
    }
}

