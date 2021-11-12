<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Suppose you have `$context['participantId']`. You can infer a
 * corresponding `$context['eventId']`. The ImpliedContextSubscriber reads
 * values like `participantId` and fills-in values like `eventId`.
 *
 * @package Civi\Token
 */
class ImpliedContextSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.token.list' => ['onRegisterTokens', 1000],
      'civi.token.eval' => ['onEvaluateTokens', 1000],
    ];
  }

  /**
   * When listing tokens, ensure that implied data is visible.
   *
   * Ex: If `$context['participantId']` is part of the schema, then
   * `$context['eventId']` will also be part of the schema.
   *
   * This fires early during the `civi.token.list` process to ensure that
   * other listeners see the updated schema.
   *
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   */
  public function onRegisterTokens(TokenRegisterEvent $e): void {
    $tokenProc = $e->getTokenProcessor();
    foreach ($this->findRelevantMappings($tokenProc) as $mapping) {
      $tokenProc->addSchema($mapping['destEntityId']);
    }
  }

  /**
   * When evaluating tokens, ensure that implied data is loaded.
   *
   * Ex: If `$context['participantId']` is supplied, then lookup the
   * corresponding `$context['eventId']`.
   *
   * This fires early during the `civi.token.list` process to ensure that
   * other listeners see the autoloaded values.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   */
  public function onEvaluateTokens(TokenValueEvent $e): void {
    $tokenProc = $e->getTokenProcessor();
    foreach ($this->findRelevantMappings($tokenProc) as $mapping) {
      $getSrcId = function($row) use ($mapping) {
        return $row->context[$mapping['srcEntityId']] ?? $row->context[$mapping['srcEntityRec']]['id'] ?? NULL;
      };

      $ids = [];
      foreach ($tokenProc->getRows() as $row) {
        $ids[] = $getSrcId($row);
      }
      $ids = \array_diff(\array_unique($ids), [NULL]);
      if (empty($ids)) {
        continue;
      }

      [$srcTable, $fkColumn] = explode('.', $mapping['fk']);
      $fks = \CRM_Utils_SQL_Select::from($srcTable)
        ->where('id in (#ids)', ['ids' => $ids])
        ->select(['id', $fkColumn])
        ->execute()
        ->fetchMap('id', $fkColumn);

      $tokenProc->addSchema($mapping['destEntityId']);
      foreach ($tokenProc->getRows() as $row) {
        $srcId = $getSrcId($row);
        if ($srcId && empty($row->context[$mapping['destEntityId']])) {
          $row->context($mapping['destEntityId'], $fks[$srcId]);
        }
      }
    }
  }

  /**
   * Are there any context-fields for which we should do lookups?
   *
   * Ex: If the `$tokenProcessor` has the `participantId`s, then we would want
   * to know any rules that involve `participantId`. But we don't need to know
   * rules that involve `contributionId`.
   *
   * @param \Civi\Token\TokenProcessor $tokenProcessor
   */
  private function findRelevantMappings(TokenProcessor $tokenProcessor): iterable {
    $schema = $tokenProcessor->context['schema'];
    yield from [];
    foreach ($this->getMappings() as $mapping) {
      if (in_array($mapping['srcEntityRec'], $schema) || in_array($mapping['srcEntityId'], $schema)) {
        yield $mapping;
      }
    }
  }

  private function getMappings(): iterable {
    yield [
      'srcEntityId' => 'participantId',
      'srcEntityRec' => 'participant',
      'fk' => 'civicrm_participant.event_id',
      'destEntityId' => 'eventId',
    ];
  }

}
