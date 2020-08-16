<?php

declare(strict_types=1);

namespace Drupal\qa\Plugin\QaCheck\Dependencies;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

/**
 * Class FunctionCallVisitor extract the names of called functions.
 *
 * @see \Drupal\qa\Plugin\QaCheck\Dependencies\Undeclared::functionCalls()
 */
class FunctionCallVisitor extends NodeVisitorAbstract {

  /**
   * The visitor write pad.
   *
   * @var array
   */
  public $pad;

  /**
   * FunctionCallVisitor constructor.
   */
  public function __construct() {
    $this->pad = [];
  }

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node) {
    // Why this test ?
    // - Method calls need a different handling, using MethodCall.
    // - Closure calls are named by the variable holding them, and are
    //   necessarily defined, so don't need to be tracked.
    if ($node instanceof FuncCall && isset($node->name->parts)) {
      $this->pad[] = implode('\\', $node->name->parts);
    }
  }

}
