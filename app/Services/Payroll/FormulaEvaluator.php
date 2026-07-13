<?php

namespace App\Services\Payroll;

use RuntimeException;

class FormulaEvaluator
{
    /**
     * Safely evaluate a payroll formula string.
     * Only allows: component codes, numbers, +, -, *, /, ()
     * No eval(). Uses a simple recursive descent parser.
     */
    public function evaluate(string $formula, array $componentValues): float
    {
        // Replace component codes with their numeric values
        foreach ($componentValues as $code => $value) {
            $formula = str_ireplace($code, (string) $value, $formula);
        }

        // Special variables
        if (isset($componentValues['working_days'])) {
            $formula = str_replace('working_days', $componentValues['working_days'], $formula);
        }
        if (isset($componentValues['absent_days'])) {
            $formula = str_replace('absent_days', $componentValues['absent_days'], $formula);
        }
        if (isset($componentValues['overtime_hours'])) {
            $formula = str_replace('overtime_hours', $componentValues['overtime_hours'], $formula);
        }
        if (isset($componentValues['days_worked'])) {
            $formula = str_replace('days_worked', $componentValues['days_worked'], $formula);
        }

        // Sanitize: only allow digits, decimal, operators, parentheses, spaces
        $sanitized = preg_replace('/[^0-9\.\+\-\*\/\(\)\s]/', '', $formula);

        if (empty(trim($sanitized))) {
            return 0.0;
        }

        // Prevent consecutive operators (security)
        if (preg_match('/[\+\-\*\/]{2,}/', preg_replace('/\s+/', '', $sanitized))) {
            throw new RuntimeException("Invalid formula: consecutive operators detected.");
        }

        try {
            return $this->calculate(trim($sanitized));
        } catch (\Throwable $e) {
            throw new RuntimeException("Formula evaluation failed for: {$formula}. Error: {$e->getMessage()}");
        }
    }

    private function calculate(string $expression): float
    {
        // Use a simple tokenizer + evaluator
        $tokens = $this->tokenize($expression);
        return (float) $this->parseExpression($tokens);
    }

    private function tokenize(string $expr): array
    {
        $tokens  = [];
        $i       = 0;
        $len     = strlen($expr);

        while ($i < $len) {
            if ($expr[$i] === ' ') { $i++; continue; }

            if (is_numeric($expr[$i]) || $expr[$i] === '.') {
                $num = '';
                while ($i < $len && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i++];
                }
                $tokens[] = (float) $num;
                continue;
            }

            if (in_array($expr[$i], ['+', '-', '*', '/', '(', ')'])) {
                $tokens[] = $expr[$i++];
                continue;
            }

            $i++;
        }

        return $tokens;
    }

    private array $tokens  = [];
    private int   $pos     = 0;

    private function parseExpression(array $tokens): float
    {
        $this->tokens = $tokens;
        $this->pos    = 0;
        return $this->parseAddSub();
    }

    private function parseAddSub(): float
    {
        $left = $this->parseMulDiv();

        while ($this->pos < count($this->tokens) &&
               in_array($this->tokens[$this->pos], ['+', '-'])) {
            $op    = $this->tokens[$this->pos++];
            $right = $this->parseMulDiv();
            $left  = $op === '+' ? $left + $right : $left - $right;
        }

        return $left;
    }

    private function parseMulDiv(): float
    {
        $left = $this->parsePrimary();

        while ($this->pos < count($this->tokens) &&
               in_array($this->tokens[$this->pos], ['*', '/'])) {
            $op    = $this->tokens[$this->pos++];
            $right = $this->parsePrimary();

            if ($op === '/' && $right == 0) {
                throw new RuntimeException("Division by zero in formula.");
            }

            $left = $op === '*' ? $left * $right : $left / $right;
        }

        return $left;
    }

    private function parsePrimary(): float
    {
        if ($this->pos >= count($this->tokens)) {
            throw new RuntimeException("Unexpected end of formula.");
        }

        $token = $this->tokens[$this->pos];

        if (is_numeric($token)) {
            $this->pos++;
            return (float) $token;
        }

        if ($token === '(') {
            $this->pos++; // consume '('
            $value = $this->parseAddSub();
            if ($this->pos < count($this->tokens) && $this->tokens[$this->pos] === ')') {
                $this->pos++; // consume ')'
            }
            return $value;
        }

        if ($token === '-') {
            $this->pos++;
            return -$this->parsePrimary();
        }

        throw new RuntimeException("Unexpected token: {$token}");
    }
}