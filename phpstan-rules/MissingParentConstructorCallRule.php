<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor that does not call the constructor of its parent class.
 *
 * PhpStorm reports it as "Missing parent constructor call"; PHPStan only with the strict rules, which the modules do not
 * use. The properties that the parent constructor sets stay uninitialized. If skipping it is intended, for example for
 * a class that Magento creates without its dependencies, "@noinspection PhpMissingParentConstructorInspection" with a
 * reason in the docblock of the constructor suppresses it.
 *
 * @implements Rule<InClassMethodNode>
 */
class MissingParentConstructorCallRule implements Rule
{
    private const INSPECTION = 'PhpMissingParentConstructorInspection';

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getOriginalNode();
        $parentClass = $scope->getClassReflection()?->getParentClass();

        if ($method->name->toLowerString() !== '__construct' || $method->stmts === null || $parentClass === null ||
            ! $parentClass->hasConstructor() || $parentClass->getConstructor()->isPrivate() ||
            $this->isSuppressed($method) || $this->callsParentConstructor($method->stmts)) {

            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'The constructor does not call the constructor of its parent class %s. Call ' .
                    'parent::__construct(), or add "@noinspection %s" with a reason to the docblock if it is skipped ' .
                    'on purpose.',
                    $parentClass->getDisplayName(),
                    self::INSPECTION
                )
            )->identifier('infrangible.missingParentConstructorCall')->build()
        ];
    }

    /**
     * Checks if the statements call parent::__construct().
     *
     * @param Node\Stmt[] $statements
     *
     * @return bool
     */
    private function callsParentConstructor(array $statements): bool
    {
        $nodeFinder = new NodeFinder();

        return $nodeFinder->findFirst(
            $statements,
            static function (Node $node): bool {
                if (! $node instanceof StaticCall) {
                    return false;
                }

                $class = $node->class;
                $name = $node->name;

                return $class instanceof Node\Name && $class->toLowerString() === 'parent' &&
                    $name instanceof Node\Identifier && $name->toLowerString() === '__construct';
            }
        ) !== null;
    }

    /**
     * Checks if the docblock of the constructor suppresses the inspection of PhpStorm.
     *
     * @param Node\Stmt\ClassMethod $method
     *
     * @return bool
     */
    private function isSuppressed(Node\Stmt\ClassMethod $method): bool
    {
        $docComment = $method->getDocComment();

        return $docComment !== null &&
            preg_match('/@noinspection\s+[^\n]*\b(' . self::INSPECTION . '|ALL)\b/', $docComment->getText()) === 1;
    }
}
