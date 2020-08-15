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
    // Method calls need a different handling, using MethodCall.
    if ($node instanceof FuncCall) {
      $this->pad[] = implode('\\', $node->name->parts);
    }
  }

}
