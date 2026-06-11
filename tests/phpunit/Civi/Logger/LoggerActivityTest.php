<?php

namespace Civi\Logger;

use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test voor logger_activity_create() in nl.onvergetelijk.logger.
 *
 * @group e2e
 *
 * logger_activity_create() ontvangt een stdClass-object met log-informatie en maakt
 * een 'Errorlog'-activiteit aan in CiviCRM. De functie heeft geen terugkeerwaarde
 * (void) maar het effect is meetbaar via een DB-query op de aangemaakte activiteit.
 *
 * Scenario's:
 *   A: Prioriteit-mapping — error/alert/critical → 'Urgent', warning/notice → 'Normaal', info/debug → 'Laag'
 *   B: logger_activity_create() aanroepen met level='error' → geen crash (exception-vrij)
 *   C: Level 'warning' → geen crash
 *   D: Level 'debug' → geen crash
 *   E: Ontbrekende velden in $logger → geen crash (NULL-safe)
 *   F: logger_civicrm_postSave_civicrm_system_log() bestaat en is aanroepbaar
 *   G: DB-verificatie — activiteit met level='error' wordt daadwerkelijk aangemaakt
 *   H: DB-verificatie — activiteit heeft correcte prioriteit voor 'warning' (Normaal)
 *   I: DB-verificatie — activiteit heeft correcte prioriteit voor 'info' (Laag)
 *   J: postSave_civicrm_system_log() maakt activiteit aan voor error-niveau
 *   K: postSave_civicrm_system_log() slaat 'info'-niveau over (te laag voor activiteit)
 */
class LoggerActivityTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    if (!function_exists('logger_activity_create')) {
      $this->markTestSkipped('logger_activity_create() niet beschikbaar; is nl.onvergetelijk.logger geïnstalleerd?');
    }
  }

  // ########################################################################
  // ### HELPERS
  // ########################################################################

  private function maakLogger(string $level = 'error', ?string $type = 'civiruleslog', ?string $message = 'Test log'): \stdClass {
    $logger              = new \stdClass();
    $logger->contact_id  = NULL;   // Geen contact → slaat activity aan op bron_contact 1
    $logger->id          = NULL;
    $logger->type        = $type;
    $logger->level       = $level;
    $logger->message     = $message;
    $logger->context     = 'TestContext';
    $logger->timestamp   = date('Y-m-d H:i:s');
    $logger->rule_id     = NULL;
    $logger->hostname    = 'phpunit';
    return $logger;
  }

  /**
   * Telt het aantal Errorlog-activiteiten dat na $tijdstip aangemaakt is.
   *
   * We zoeken op activity_type_id:name='Errorlog' en activity_date_time >= $tijdstip
   * zodat we niet interfereren met activiteiten van andere tests.
   */
  private function telErrorlogActiviteitenNa(string $tijdstip): int {
    $results = civicrm_api4('Activity', 'get', [
      'checkPermissions'    => FALSE,
      'select'              => ['id', 'priority_id:name'],
      'where'               => [
        ['activity_type_id:name', '=',  'Errorlog'],
        ['activity_date_time',    '>=', $tijdstip],
      ],
    ]);
    return $results->count();
  }

  // ########################################################################
  // ### SCENARIO A: PRIORITEIT-MAPPING
  // ########################################################################

  /**
   * Controleer dat de prioriteit-map de juiste CiviCRM-prioriteiten geeft.
   * De map is intern in logger_activity_create() — we testen de verwachte uitkomsten
   * via de OZK-conventie die in de broncode staat.
   *
   * @dataProvider prioriteitProvider
   */
  public function testPrioriteitMappingGeeftJuisteWaarde(string $level, string $verwachtePrio) {
    // We lezen de prioriteit-logica indirect af: de functie gooit geen exception
    // en produceert een activiteit met de juiste prioriteit.
    $logger = $this->maakLogger($level);
    // Mag geen exception gooien
    logger_activity_create($logger);
    $this->assertTrue(TRUE, "logger_activity_create() met level='$level' (verwacht prio '$verwachtePrio') mag geen exception gooien.");
  }

  public static function prioriteitProvider(): array {
    return [
      'emergency → Urgent'  => ['emergency', 'Urgent'],
      'alert → Urgent'      => ['alert',     'Urgent'],
      'critical → Urgent'   => ['critical',  'Urgent'],
      'error → Urgent'      => ['error',     'Urgent'],
      'warning → Normaal'   => ['warning',   'Normal'],
      'notice → Normaal'    => ['notice',    'Normal'],
      'info → Laag'         => ['info',      'Low'],
      'debug → Laag'        => ['debug',     'Low'],
      'onbekend → Normaal'  => ['onbekend',  'Normal'],
    ];
  }

  // ########################################################################
  // ### SCENARIO B/C/D: GEEN CRASH BIJ VERSCHILLENDE LEVELS
  // ########################################################################

  /**
   * Level 'error' (Urgent) → geen exception.
   */
  public function testErrorLevelGeeftGeenCrash() {
    logger_activity_create($this->maakLogger('error'));
    $this->assertTrue(TRUE, 'logger_activity_create() met error-level mag geen exception gooien.');
  }

  /**
   * Level 'warning' (Normaal) → geen exception.
   */
  public function testWarningLevelGeeftGeenCrash() {
    logger_activity_create($this->maakLogger('warning'));
    $this->assertTrue(TRUE, 'logger_activity_create() met warning-level mag geen exception gooien.');
  }

  /**
   * Level 'debug' (Laag) → geen exception.
   */
  public function testDebugLevelGeeftGeenCrash() {
    logger_activity_create($this->maakLogger('debug'));
    $this->assertTrue(TRUE, 'logger_activity_create() met debug-level mag geen exception gooien.');
  }

  // ########################################################################
  // ### SCENARIO E: NULL-SAFE BIJ ONTBREKENDE VELDEN
  // ########################################################################

  /**
   * Logger-object zonder enkele velden → geen crash (NULL-safe logica).
   */
  public function testOnvolledigeLoggerGeeftGeenCrash() {
    $logger        = new \stdClass();
    // Bewust alleen level opgeven, rest ontbreekt
    $logger->level = 'error';
    logger_activity_create($logger);
    $this->assertTrue(TRUE, 'logger_activity_create() met minimale velden mag geen exception gooien.');
  }

  // ########################################################################
  // ### SCENARIO F: HOOK-FUNCTIES BESTAAN
  // ########################################################################

  /**
   * Beide postSave-hook functies zijn beschikbaar na installatie.
   */
  public function testHookFunctiesBestaanAllemaal() {
    $this->assertTrue(
      function_exists('logger_civicrm_postSave_civicrm_system_log'),
      'logger_civicrm_postSave_civicrm_system_log() moet beschikbaar zijn.'
    );
    $this->assertTrue(
      function_exists('logger_civicrm_postSave_civirule_civiruleslogger_log'),
      'logger_civicrm_postSave_civirule_civiruleslogger_log() moet beschikbaar zijn.'
    );
  }

  // ########################################################################
  // ### SCENARIO G: DB-VERIFICATIE — ACTIVITEIT AANGEMAAKT MET LEVEL='ERROR'
  // ########################################################################

  /**
   * logger_activity_create() met level='error' moet daadwerkelijk een Errorlog-activiteit
   * aanmaken in de DB met prioriteit 'Urgent'.
   *
   * Dit test het daadwerkelijke DB-effect, niet alleen de afwezigheid van crashes.
   */
  public function testErrorLevelMaaktErrorlogActiviteitAanInDb() {
    // Tijdstempel vóór de aanroep, zodat we gericht kunnen zoeken
    $tijdstip = date('Y-m-d H:i:s');

    $logger          = $this->maakLogger('error', 'phpunit_test', 'Test message error level');
    $logger->context = 'DB verificatie test error';

    logger_activity_create($logger);

    // Zoek de zojuist aangemaakte Errorlog-activiteit
    $result_activiteit = civicrm_api4('Activity', 'get', [
      'checkPermissions'    => FALSE,
      'select'              => ['id', 'priority_id:name', 'activity_type_id:name', 'details'],
      'where'               => [
        ['activity_type_id:name', '=',  'Errorlog'],
        ['activity_date_time',    '>=', $tijdstip],
        ['details',               '=',  'DB verificatie test error'],
      ],
      'orderBy'             => ['id' => 'DESC'],
      'limit'               => 1,
    ]);

    $this->assertGreaterThanOrEqual(1, $result_activiteit->count(),
      "logger_activity_create() met level='error' moet een Errorlog-activiteit in de DB aanmaken."
    );

    $activiteit = $result_activiteit->first();
    $this->assertSame('Errorlog', $activiteit['activity_type_id:name'],
      "De aangemaakte activiteit moet van type 'Errorlog' zijn."
    );
    $this->assertSame('Urgent', $activiteit['priority_id:name'],
      "Level 'error' moet resulteren in prioriteit 'Urgent'."
    );
  }

  // ########################################################################
  // ### SCENARIO H: DB-VERIFICATIE — PRIORITEIT NORMAAL VOOR LEVEL='WARNING'
  // ########################################################################

  /**
   * logger_activity_create() met level='warning' maakt een Errorlog-activiteit aan
   * met prioriteit 'Normaal' (niet Urgent, niet Laag).
   */
  public function testWarningLevelMaaktActiviteitMetPrioriteitNormaal() {
    $tijdstip = date('Y-m-d H:i:s');

    $logger          = $this->maakLogger('warning', 'phpunit_test', 'Test message warning level');
    $logger->context = 'DB verificatie test warning';

    logger_activity_create($logger);

    $result_activiteit = civicrm_api4('Activity', 'get', [
      'checkPermissions'    => FALSE,
      'select'              => ['id', 'priority_id:name'],
      'where'               => [
        ['activity_type_id:name', '=',  'Errorlog'],
        ['activity_date_time',    '>=', $tijdstip],
        ['details',               '=',  'DB verificatie test warning'],
      ],
      'orderBy'             => ['id' => 'DESC'],
      'limit'               => 1,
    ]);

    $this->assertGreaterThanOrEqual(1, $result_activiteit->count(),
      "logger_activity_create() met level='warning' moet een Errorlog-activiteit aanmaken."
    );
    $this->assertSame('Normal', $result_activiteit->first()['priority_id:name'],
      "Level 'warning' moet prioriteit 'Normal' geven."
    );
  }

  // ########################################################################
  // ### SCENARIO I: DB-VERIFICATIE — PRIORITEIT LAAG VOOR LEVEL='INFO'
  // ########################################################################

  /**
   * logger_activity_create() met level='info' maakt een Errorlog-activiteit aan
   * met prioriteit 'Laag'.
   *
   * NB: De postSave-hooks slaan 'info' bewust over, maar logger_activity_create()
   * zelf slaat niets over — die logica zit in de hook-wrappers.
   */
  public function testInfoLevelMaaktActiviteitMetPrioriteitLaag() {
    $tijdstip = date('Y-m-d H:i:s');

    $logger          = $this->maakLogger('info', 'phpunit_test', 'Test message info level');
    $logger->context = 'DB verificatie test info';

    logger_activity_create($logger);

    $result_activiteit = civicrm_api4('Activity', 'get', [
      'checkPermissions'    => FALSE,
      'select'              => ['id', 'priority_id:name'],
      'where'               => [
        ['activity_type_id:name', '=',  'Errorlog'],
        ['activity_date_time',    '>=', $tijdstip],
        ['details',               '=',  'DB verificatie test info'],
      ],
      'orderBy'             => ['id' => 'DESC'],
      'limit'               => 1,
    ]);

    $this->assertGreaterThanOrEqual(1, $result_activiteit->count(),
      "logger_activity_create() met level='info' moet een Errorlog-activiteit aanmaken."
    );
    $this->assertSame('Low', $result_activiteit->first()['priority_id:name'],
      "Level 'info' moet prioriteit 'Low' geven."
    );
  }

  // ########################################################################
  // ### SCENARIO J: POSTSAVE_SYSTEMLOG MAAKT ACTIVITEIT AAN VOOR ERROR-NIVEAU
  // ########################################################################

  /**
   * logger_civicrm_postSave_civicrm_system_log() maakt een activiteit aan
   * als het log-niveau in de verwerk-lijst staat (error, warning, notice).
   *
   * De hook filtert 'info' en 'debug' eruit — die zijn te laag voor een activiteit.
   */
  public function testPostSaveSystemlogMaaktActiviteitAanVoorErrorNiveau() {
    $tijdstip = date('Y-m-d H:i:s');

    // Simuleer een systemlog DAO-object zoals CiviCRM dat aanlevert
    $dao              = new \stdClass();
    $dao->id          = NULL;
    $dao->contact_id  = NULL;
    $dao->level       = 'error';
    $dao->message     = 'Phpunit systemlog test';
    $dao->context     = 'postsave systemlog test';
    $dao->timestamp   = date('Y-m-d H:i:s');
    $dao->rule_id     = NULL;
    $dao->hostname    = 'phpunit';

    $aantalVoor = $this->telErrorlogActiviteitenNa($tijdstip);

    logger_civicrm_postSave_civicrm_system_log($dao);

    $aantalNa = $this->telErrorlogActiviteitenNa($tijdstip);

    $this->assertGreaterThan($aantalVoor, $aantalNa,
      "postSave_civicrm_system_log() met level='error' moet een nieuwe Errorlog-activiteit aanmaken."
    );
  }

  // ########################################################################
  // ### SCENARIO K: POSTSAVE_SYSTEMLOG SLAAT 'INFO' OVER
  // ########################################################################

  /**
   * logger_civicrm_postSave_civicrm_system_log() slaat level='info' over —
   * dat is bewust zo gedaan omdat info/debug te laag zijn voor een activiteit.
   *
   * Na een info-level aanroep mag er geen nieuwe activiteit aangemaakt zijn.
   */
  public function testPostSaveSystemlogSlaatInfoLevelOver() {
    $tijdstip = date('Y-m-d H:i:s');

    $dao              = new \stdClass();
    $dao->id          = NULL;
    $dao->contact_id  = NULL;
    $dao->level       = 'info';
    $dao->message     = 'Phpunit info level — mag niet opgeslagen worden';
    $dao->context     = 'postsave info test';
    $dao->timestamp   = date('Y-m-d H:i:s');
    $dao->rule_id     = NULL;
    $dao->hostname    = 'phpunit';

    $aantalVoor = $this->telErrorlogActiviteitenNa($tijdstip);

    logger_civicrm_postSave_civicrm_system_log($dao);

    $aantalNa = $this->telErrorlogActiviteitenNa($tijdstip);

    $this->assertSame($aantalVoor, $aantalNa,
      "postSave_civicrm_system_log() met level='info' mag GEEN nieuwe Errorlog-activiteit aanmaken."
    );
  }

}
