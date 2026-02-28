<?php

#error_reporting(E_ALL);
#ini_set('display_errors', true);
#ini_set('display_startup_errors', true);

require_once 'logger.civix.php';

function logger_civicrm_buildForm($formName, &$form) {


    return;


    $result = civicrm_api3('SystemLog', 'get', [
      'debug' => 1,
      'sequential' => 1,
      'return' => ["id", "message", "context", "level", "timestamp", "contact_id", "hostname"],
      'timestamp' => ['>' => "-3 months"],
    ]);

    $civirulesLogs = civicrm_api4('CivirulesLog', 'get', [
      'where' => [
        ['level', '=', 'error'],
        ['timestamp', '>', '-3 months'],
      ],
      'limit' => 25,
      'checkPermissions' => TRUE,
    ]);


    $extdebug = 3;

//  watchdog('php', '<pre>formname        :$formName</pre>', null, WATCHDOG_DEBUG);
//  watchdog('php', '<pre>form (ext)      :$form</pre>', null, WATCHDOG_DEBUG);

//  $reflBar    = $form->getProperty('_loggerId');

//  $reflForm   = new ReflectionClass('form');
//  $reflBar    = $reflForm->getProperty('_loggerId');

/*
    $reflBar->setAccessible(true);

    $newform    = new form();
    $reflBar->getValue($newform);
*/

//  watchdog('php', '<pre>reflBar       :$reflBar</pre>', null, WATCHDOG_DEBUG);

    if ($formName == 'CRM_Logger_Form_Registration_ParticipantConfirm') {

//      $defaults['emptySeats:protected']       = 6161;
        $defaults['emptySeats']                 = 6161;
        $defaults['requireSpace']               = 0;
        $defaults['_isEventFull']               = 0;
        $defaults['_allowConfirmation']         = 1;
        $defaults['_allowWaitlist']             = 0;
        $defaults['_requireApproval']           = 0;

        $form->setDefaults($defaults);

//      $form->setVar( 'emptySeats:protected',  6161 );
        $form->setVar( 'emptySeats',            6161 );
        $form->setVar( 'requireSpace',          0    );
        $form->setVar( '_isEventFull',          0    );
        $form->setVar( '_allowConfirmation',    1    );
        $form->setVar( '_allowWaitlist',        0    );
        $form->setVar( '_requireApproval',      0    );

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,3, "formName",      $formName);
        wachthond($extdebug,3, "form (new)",    $form);
        wachthond($extdebug,3, "defaults",      $defaults);
        wachthond($extdebug,3, "emptySeats",    $emptySeats);
        wachthond($extdebug,1, "#########################################################################");
    }

    if ($formName == 'CRM_Logger_Form_Participant') {
        $defaults['is_notify'] = 0;
        $form->setDefaults($defaults);
        #$is_notify = 0;
//      watchdog('php', '<pre>[DISABLE NOTIFY] ON $formName</pre>', null, WATCHDOG_DEBUG);
    }
}

/*
function logger_civicrm_post($op, $objectName, $objectId, &$objectRef) {

    watchdog('php', '<pre>*** 0 START EXTENSION EVENT KENMERKEN [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG);
    #if ($op == 'edit' && $objectName == 'Event') {
    if ($objectName == 'Event') {
        watchdog('php', '<pre>*** 1 START EXTENSION EVENT KENMERKEN [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG);
    }
}
*/

function logger_civicrm_custom($op, $groupID, $entityID, &$params) { 

	$extdebug	= 4;
    $todaydatetime  = date("Y-m-d H:i:s");

    // M61: Er is hier nog nix > return
    return;

	if (!in_array($groupID, array("101"))) { // ALLEEN PART + EVENT PROFILES
	//if (!in_array($groupID, array("139","190","165"))) { // ALLEEN PART PROFILES
		// 101  EVENT KENMERKEN
		// 211  EVENT KENMERKEN WERVING 
		// 103	TAB  logger
		// 139	PART DEEL
		// 190	PART LEID
		// (140	PART LEID VOG)
		// 181	TAB  INTAKE
		// 165	PART REFERENTIE
		#wachthond($extdebug,4, "--- SKIP EXTENSION EVENT (not in proper group) [groupID: '.$groupID.'] [op: '.$op.']---");
		return; //   if not, get out of here
	}

	if (in_array($groupID, array("101"))) {

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.X EVENT KENMERKEN START", "[groupID: $groupID] [op: $op]");
        wachthond($extdebug,1, "#########################################################################");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.1 GET EVENT INFO");
        wachthond($extdebug,1, "#########################################################################");

        # ATTEMPTY TO RETREIVE FISCAL YEAR START VALUE FROM CACHE
        #Civi::cache()->set('cache_fiscalyear_start',   $fiscalyear_start);
        #Civi::cache()->set('cache_fiscalyear_end',     $fiscalyear_end);

        $cache_fiscalyear_start = Civi::cache()->get('cache_fiscalyear_start');
        $cache_fiscalyear_end   = Civi::cache()->get('cache_fiscalyear_end');

        # ATTEMPTY TO RETREIVE FISCAL YEAR START VALUE FROM CACHE
        wachthond($extdebug,1, "cache_fiscalyear_start",    $cache_fiscalyear_start);
        wachthond($extdebug,1, "cache_fiscalyear_end",      $cache_fiscalyear_end);

        $params_logger_get = [
            'checkPermissions'  => FALSE,
            'debug'             => FALSE,
            'select'    => [
                'id',
                'title', 
                'start_date', 
                'end_date',
                'is_online_registration',
                'registration_start_date',
                'registration_end_date',
                'logger_type_id', 
                'Event_Kenmerken_Werving.Vacatures_Groepsleiding', 
                'Event_Kenmerken_Werving.Vacatures_Keukenteam', 
                'Event_Kenmerken_Werving.Vacatures_Sport_Spelteam',                
                'Event_Kenmerken_Werving.Plekken_jongens', 
                'Event_Kenmerken_Werving.Plekken_meisjes', 
                'has_waitlist', 
                'waitlist_text', 
                'logger_full_text', 
                'max_participants', 
                'Event_Kenmerken.lastminute_plekken', 
                'Event_Kenmerken_Werving.datum_update',
            ],
            'where'     => [
                ['id', '=', $entityID],
            ],
        ];

        wachthond($extdebug,3, "params_logger_get",          $params_logger_get);
        $result_logger_get = civicrm_api4('Event', 'get',    $params_logger_get);
        wachthond($extdebug,4, "result_logger_get",          $result_logger_get);

        $logger_id               = $result_logger_get[0]['id'];
		$logger_title			= $result_logger_get[0]['title'];
		$logger_startdate		= $result_logger_get[0]['start_date'];

        $logger_is_online_reg    = $result_logger_get[0]['is_online_registration'];
        $logger_regdate_start    = $result_logger_get[0]['registration_start_date'];
        $logger_regdate_einde    = $result_logger_get[0]['registration_end_date'];

		$logger_type_id			= $result_logger_get[0]['logger_type_id'];
        $logger_plekken_jongens	= $result_logger_get[0]['Event_Kenmerken_Werving.Plekken_jongens'];
        $logger_plekken_meisjes	= $result_logger_get[0]['Event_Kenmerken_Werving.Plekken_meisjes'];

        $logger_vacature_groep   = $result_logger_get[0]['Event_Kenmerken_Werving.Vacatures_Groepsleiding'];
        $logger_vacature_keuken  = $result_logger_get[0]['Event_Kenmerken_Werving.Vacatures_Keukenteam'];
        $logger_vacature_spel    = $result_logger_get[0]['Event_Kenmerken_Werving.Vacatures_Sport_Spelteam'];

        $logger_haswaitlist		= $result_logger_get[0]['has_waitlist'];
        $logger_waitlisttext		= $result_logger_get[0]['waitlist_text'];
   	    $logger_fulltext			= $result_logger_get[0]['logger_full_text'];
        $logger_max_participants = $result_logger_get[0]['max_participants'];

        $logger_lastminute		= $result_logger_get[0]['lastminute_plekken'];
        $logger_laatsteupdate    = $result_logger_get[0]['Event_Kenmerken_Werving.datum_update'];

		wachthond($extdebug,2, "logger_type_id         ", $logger_type_id);
		wachthond($extdebug,2, "logger_plekken_jongens ", $logger_plekken_jongens);
    	wachthond($extdebug,2, "logger_plekken_meisjes ", $logger_plekken_meisjes);

        wachthond($extdebug,2, "logger_vacature_groep  ", $logger_vacature_groep);
        wachthond($extdebug,2, "logger_vacature_keuken ", $logger_vacature_keuken);
        wachthond($extdebug,2, "logger_vacature_spel   ", $logger_vacature_spel);

    	wachthond($extdebug,2, "logger_haswaitlist     ", $logger_haswaitlist);
    	wachthond($extdebug,2, "logger_waitlisttext    ", $logger_waitlisttext);
    	wachthond($extdebug,2, "logger_fulltext        ", $logger_fulltext);
    	wachthond($extdebug,2, "logger_max_participants", $logger_max_participants);
    	wachthond($extdebug,2, "logger_lastminute      ", $logger_lastminute);

        wachthond($extdebug,2, "logger_is_online_reg   ", $logger_is_online_reg);
        wachthond($extdebug,2, "logger_regdate_start   ", $logger_regdate_start);
        wachthond($extdebug,2, "logger_regdate_einde   ", $logger_regdate_einde);
        wachthond($extdebug,2, "todaydatetime         ", $todaydatetime);

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.2 CONFIGURE EVENT STATUS OPEN");
        wachthond($extdebug,1, "#########################################################################");

        if ($logger_is_online_reg == 1 AND $logger_regdate_start AND $logger_regdate_einde) {

            $vandaag_reg_open = date_between('today',$todaydatetime, 'kampdagen', $logger_regdate_start, $logger_regdate_einde); 

            if ( date_bigger($todaydatetime, $logger_regdate_start) == 1) {
               $loggerstatusopen    =   "nogniet";
            }
            if ($vandaag_reg_open == 1) {
               $loggerstatusopen    =   "registratie";
            }
            if ( date_bigger($logger_regdate_einde, $todaydatetime) == 1) {
               $loggerstatusopen    =   "voorbij";
            }

            wachthond($extdebug,2, "loggerstatusopen", $loggerstatusopen);

            if (strtotime($todaydatetime) < strtotime($logger_regdate_start) ) {
               $loggerstatusopen    =   "nogniet";
            }
            if ( strtotime($todaydatetime) > strtotime($logger_regdate_start) AND strtotime($todaydatetime) < strtotime($logger_regdate_einde) ) {
               $loggerstatusopen    =   "registratie";
            }
            if ( strtotime($todaydatetime) > strtotime($logger_regdate_einde) ) {
               $loggerstatusopen    =   "voorbij";
            }

        } else {
               $loggerstatusopen    =   "onbekend";
        }

        wachthond($extdebug,2, "loggerstatusopen", $loggerstatusopen);

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.2 CONFIGURE STATUS WAITLISTTEXT");
        wachthond($extdebug,1, "#########################################################################");

        $logger_jong_free_meis_free  = "Er is op dit moment nog plek voor zowel jongens als meisjes";

    	$logger_jong_wait_meis_wait	= "Dit kamp is op dit moment zo goed als vol. Per deelnemer moeten we bekijken of er nog plek is. Dit heeft te maken met de verhouding jongens/meisjes en de beschikbare plekken in de slaapzalen. U kunt uw kind aanmelden voor de wachtlijst. We zullen u op de hoogte stellen of, en zo ja, wanneer de aanmelding alsnog doorgang kan vinden.";

    	$logger_jong_wait_meis_free	= "LET OP: Voor dit kamp is nog voldoende plek voor meisjes. Voor jongens moeten nog puzzelen en daarom komen ze op de wachtlijst. Meisjes komen trouwens eerst ook op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw dochter alsnog af te kunnen ronden.";
    	$logger_jong_free_meis_wait	= "LET OP: Voor dit kamp is nog voldoende plek voor jongens. Voor meisjes moeten nog puzzelen en daarom komen ze op de wachtlijst. Jongens komen trouwens eerst ook op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw zoon alsnog af te kunnen ronden.";

    	$logger_jong_full_meis_free	= "LET OP: Voor dit kamp is op dit moment geen plek meer voor jongens. Er is alleen nog plek voor meisjes. Meisjes komen eerst op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw dochter alsnog af te kunnen ronden.";
    	$logger_jong_free_meis_full	= "LET OP: Voor dit kamp is op dit moment geen plek meer voor meisjes. Er is alleen nog plek voor jongens. Jongens komen eerst op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw zoon alsnog af te kunnen ronden.";

    	$logger_jong_full_meis_wait	= "LET OP: Voor dit kamp is op dit moment geen plek meer voor jongens. Voor meisjes zijn we aan het puzzelen met de groeps- en slaapzaalindeling en daarom kunt u uw dochter aanmelden voor de wachtlijst. We zullen u op de hoogte stellen of, en zo ja, wanneer de aanmelding alsnog doorgang kan vinden.";
    	$logger_jong_wait_meis_full	= "LET OP: Voor dit kamp is op dit moment geen plek meer voor meisjes. Voor jongens zijn we aan puzzelen met de groeps- en slaapzaalindeling en daarom kunt u uw zoon aanmelden voor de wachtlijst. We zullen u op de hoogte stellen of, en zo ja, wanneer de aanmelding alsnog doorgang kan vinden.";

    	$logger_waitlist_naarjk		= "Hierboven ziet u wat de beschikbaarheid is voor jongens en voor meisjes. Op dit moment komt iedereen sowieso eerst op de wachtlijst. Voor wie er toch plek is sturen we een email om de aanmelding af te ronden. Jongens en meiden die rond december 16 worden zijn van harte welkom om mee te gaan met het Jeugdkamp in plaats van het Tienerkamp.";

//    	$logger_fulltext 		    = "Helaas zijn er voor dit kamp geen plekken meer beschikbaar. De aanmeldingen voor volgend jaar gaan op 1 januari weer open. We verwijzen u voor deze zomer graag naar onze collega kamporganisaties die u kunt vinden op www.christelijkekampen.nl";
        $logger_fulltext 		= "Helaas zijn er voor dit kamp geen plekken meer beschikbaar. Mogelijk is er nog wel plek in de andere week. Kijk voor de beschikbaarheid op www.onvergetelijk.nl/ouders/aanmelden";

        $logger_jong_full_meis_full  = "Helaas zijn er voor dit kamp geen plekken meer beschikbaar. Mogelijk is er nog wel plek in de andere week. Kijk voor de beschikbaarheid op www.onvergetelijk.nl/ouders/aanmelden";

        $waitlist_text              = NULL;
        $max_participants           = NULL;
        $has_waitlist               = NULL;
        $loggerstatus                = NULL;
        $loggervol                   = NULL;
//      $logger_plekken_jongens      = NULL;
//      $logger_plekken_meisjes      = NULL;

    	// PLEK VOOR JONGENS & PLEK VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(";-)",":-)"), true) 	   AND in_array($logger_plekken_meisjes, array(";-)",":-)"), true)) {
    		$has_waitlist 		= 0;
    		$max_participants 	= 200;
    		$waitlist_text		= $logger_jong_free_meis_free;
    		$lastminutelijst	= 1; // op de lastminutelijst?
            $loggerstatus        = $loggerstatusopen;
            $loggervol           = 0;
    		wachthond($extdebug,4, "PLEK VOOR JONGENS & MEISJES");
    	}
    
    	// WAIT VOOR JONGENS & PLEK VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(":-|"), true)           AND in_array($logger_plekken_meisjes, array(";-)",":-)"), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_wait_meis_free;
    		$lastminutelijst 	= 1; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;
    		wachthond($extdebug,4, "WACHTLIJST VOOR JONGENS & PLEK VOOR MEISJES");
    	}
       	// PLEK VOOR JONGENS & WAIT VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(";-)",":-)"), true)     AND in_array($logger_plekken_meisjes, array(":-|"), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_free_meis_wait;
    		$lastminutelijst 	= 1; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;            
    		wachthond($extdebug,4, "WACHTLIJST VOOR MEISJES & PLEK VOOR JONGENS");
    	}
    	// WACHT VOOR JONGENS & WAIT VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(":-|"), true)           AND in_array($logger_plekken_meisjes, array(":-|"), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_wait_meis_wait;
    		$lastminutelijst 	= 0; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;            
    		wachthond($extdebug,4, "WACHTLIJST VOOR JONGENS & MEISJES");
    	}
    	// VOL VOOR JONGENS & PLEK VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(":-("), true) 		   AND in_array($logger_plekken_meisjes, array(";-)",":-)"), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_full_meis_free;
    		$lastminutelijst 	= 1; // op de lastminutelijst?
            $loggerstatus        = "vol";
            $loggervol           = 0;           
    		wachthond($extdebug,4, "VOL VOOR JONGENS & PLEK VOOR MEISJES");
    	}
    	// PLEK VOOR JONGENS & VOL VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(";-)",":-)"), true)     AND in_array($logger_plekken_meisjes, array(":-("), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_free_meis_full;
    		$lastminutelijst 	= 1; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;            
    		wachthond($extdebug,4, "PLEK VOOR JONGENS & VOL VOOR MEISJES");
    	}
    	// VOL VOOR JONGENS & VOL VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(":-("), true) 		    AND in_array($logger_plekken_meisjes, array(":-("), true)) {
    		$has_waitlist 		= FALSE;
     		$max_participants 	= 1;
     		$waitlist_text		= $logger_jong_full_meis_full;
    		$lastminutelijst 	= 0; // op de lastminutelijst?
            $loggerstatus        = "vol";
            $loggervol           = 1;            
    		wachthond($extdebug,4, "VOL VOOR JONGENS & MEISJES");
    	}
    	// VOL VOOR JONGENS & WAIT VOOR MEISJES
    	if (in_array($logger_plekken_jongens, array(":-("), true) 		    AND in_array($logger_plekken_meisjes, array(":-|"), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
    		$waitlist_text		= $logger_jong_full_meis_wait;
    		$lastminutelijst 	= 1; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;            
    		wachthond($extdebug,4, "VOL VOOR JONGENS & WACHTLIJST VOOR MEISJES");
    	}
    	// WAIT VOOR JONGENS & VOL VOOR JONGENS
    	if (in_array($logger_plekken_jongens, array(":-|"), true) 		    AND in_array($logger_plekken_meisjes, array(":-("), true)) {
    		$has_waitlist 		= TRUE;
     		$max_participants 	= 1;
     		$waitlist_text		= $logger_jong_wait_meis_full;
    		$lastminutelijst 	= 0; // op de lastminutelijst?
            $loggerstatus        = "wachtlijst";
            $loggervol           = 0;            
    		wachthond($extdebug,4, "VOL VOOR MEISJES & WACHTLIJST VOOR MEISJES");
    	}

        $waitlisttext_old   = $logger_waitlisttext;
        $waitlisttext_new   = $waitlist_text;
        wachthond($extdebug,3, "waitlisttext_old       ", "$waitlisttext_old");
        wachthond($extdebug,3, "waitlisttext_new       ", "$waitlisttext_new");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.3 EVENT UPDATE");
        wachthond($extdebug,1, "#########################################################################");

        $params_logger_update = [
            'checkPermissions' => FALSE,
            'where' => [
                ['id', '=', $entityID],
            ],
            'values' => [
                'max_participants'  => $max_participants,
                'has_waitlist'      => $has_waitlist,
                'waitlist_text'     => $waitlist_text,
            ],
        ];

        if ($loggerstatus)   { $params_logger_update['values']['Event_Kenmerken_Werving.loggerstatus']   = $loggerstatus;   }
        if ($loggervol)      { $params_logger_update['values']['Event_Kenmerken_Werving.loggervol']      = $loggervol;      }      

        ###############################################################################################################
        # als de waitlisttext wijzigt, pas dan het veld 'laatste update' aan
        ###############################################################################################################
        if ($waitlisttext_new != $waitlisttext_old) {
            $todaydatetime  = date("Y-m-d H:i");
            $params_logger_update['values']['Event_Kenmerken_Werving.datum_update']     = $todaydatetime;
            wachthond($extdebug,4, "datum aangepast", $todaydatetime);
        }

        wachthond($extdebug,3, "params_logger_update",           $params_logger_update);
        $result_logger_update = civicrm_api4('Event', 'update',  $params_logger_update);
        wachthond($extdebug,1, "params_logger_update EXECUTED");        
        wachthond($extdebug,4, "result_logger_update",           $result_logger_update);

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### ONVERGETELIJK EVENT 1.X EVENT KENMERKEN EINDE");
        wachthond($extdebug,1, "#########################################################################");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT EINDE 1.1 EXTENSION EVENT KENMERKEN");
        wachthond($extdebug,1, "#########################################################################");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT START 1.2 REGISTER TESTDEEL FOR THIS EVENT", "[$logger_id / $logger_title]");
        wachthond($extdebug,1, "#########################################################################");

    	$loggerjaar     = date('Y', strtotime($logger_startdate));
        $regdate       = ($loggerjaar.'-01-01');
        $todaydatetime = date("Y-m-d");

        if ($logger_type_id == 11) { $contact_id = 14336; $birthdate = "2012-04-01"; $groepklas = 'groep_5'; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 21) { $contact_id = 14337; $birthdate = "2012-04-01"; $groepklas = 'groep_5'; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 12) { $contact_id = 14338; $birthdate = "2010-04-01"; $groepklas = 'klas_1';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 22) { $contact_id = 14339; $birthdate = "2010-04-01"; $groepklas = 'klas_1';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 13) { $contact_id = 14340; $birthdate = "2008-04-01"; $groepklas = 'klas_2';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 23) { $contact_id = 14341; $birthdate = "2008-04-01"; $groepklas = 'klas_2';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 14) { $contact_id = 14342; $birthdate = "2006-04-01"; $groepklas = 'klas_4';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 24) { $contact_id = 14343; $birthdate = "2006-04-01"; $groepklas = 'klas_3';  $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 33) { $contact_id = 13876; $birthdate = "2000-04-01"; $groepklas = 'vervolg'; $letter = "A"; $kleur = "blauw"; }

        if (strtotime($todaydatetime) < strtotime($logger_startdate)) {
            $params_testdeel_get = [
                'select' => [
                    'id', 
                    'logger_id',
                ],
                'where' => [
                    ['logger_id',    '=', $entityID],
                    ['contact_id',  '=', $contact_id],
                    ['is_test',     'IN', [TRUE, FALSE]],
                ],
            ];
            wachthond($extdebug,3, "params_testdeel_get",               $params_testdeel_get);
            $result_testdeel_get = civicrm_api4('Participant', 'get',   $params_testdeel_get);
            wachthond($extdebug,4, "result_testdeel_get",               $result_testdeel_get);

            $result_testdeel_get_count = $result_testdeel_get->count();
            wachthond($extdebug,3, "result_testdeel_get_count ",        $result_testdeel_get_count);

            $testdeel_part_id = $result_testdeel_get[0]['id'];
            wachthond($extdebug,3, "testdeel_part_id",                  $testdeel_part_id);

            if (empty($testdeel_part_id) AND !empty($contact_id)) {

                $params_testdeel_create = [
                    'checkPermissions'  => FALSE,
                    'values' => [
                        'contact_id'                => $contact_id,
                        'logger_id'                  => $entityID,
                        'status_id'                 => 1,           // 'Registered'
                        'register_date'             => $regdate,
                        'role_id'                   => [7],         // 'Deelnemer'
                        'PART_DEEL.Groep_klas'      => $groepklas,
                        'PART_INTERN.Groepje'       => $letter,
                        'PART_INTERN.Groepjeskleur' => $kleur,
                    ],
                ];

                wachthond($extdebug,3, "params_testdeel_create",                    $params_testdeel_create);
                if ($result_testdeel_get_count == 0) {
                    $result_testdeel_create = civicrm_api4('Participant', 'create', $params_testdeel_create);
                    wachthond($extdebug,4, "result_testdeel_create EXECUTED");
                    wachthond($extdebug,4, "result_testdeel_create",                $result_testdeel_create);
                    wachthond($extdebug,1, "Registration testdeel [cid: $contact_id] for $logger_title (eid: $logger_id)", "CREATED");
                }

            }

            if ($testdeel_part_id AND $result_testdeel_get_count == 1) {

                $params_testdeel_cont_update = [
                    'checkPermissions' => FALSE,
                    'values' => [
                        'birth_date'    => $birthdate,
                        'gender_id'     => 2, 
                    ],
                    'where' => [
                        ['id',         '=', $contact_id],
                    ],
                ];

                wachthond($extdebug,3, "params_testdeel_cont_update",           $params_testdeel_cont_update);
                $result_testdeel_cont_update = civicrm_api4('Contact','update', $params_testdeel_cont_update);
                wachthond($extdebug,3, "params_testdeel_cont_update EXECUTED");
                wachthond($extdebug,4, "result_testdeel_cont_update",           $result_testdeel_cont_update);

                $params_testdeel_part_update = [
                    'checkPermissions'  => FALSE,
                    'values' => [
                        'id'                        => $testdeel_part_id,
    //                  'contact_id'                => $contact_id,
    //                  'logger_id'                  => $entityID,
                        'status_id'                 => 1,           // 'Registered'
                        'register_date'             => $regdate,
                        'role_id'                   => [7],         // 'Deelnemer'
                        'PART_DEEL.Groep_klas'      => $groepklas,
                        'PART_INTERN.Groepje'       => $letter,
                        'PART_INTERN.Groepjeskleur' => $kleur,
                    ],
                    'where' => [
                        ['id',      '=',  $testdeel_part_id],
                        ['is_test', 'IN', [TRUE, FALSE]],
                    ],
                ];

                wachthond($extdebug,3, "params_testdeel_part_update",                   $params_testdeel_part_update);
                if ($result_testdeel_get_count == 1) {
                    $result_testdeel_part_update = civicrm_api4('Participant','update', $params_testdeel_part_update);
                }
                wachthond($extdebug,3, "params_testdeel_part_update EXECUTED");
                wachthond($extdebug,4, "result_testdeel_part_update",                   $result_testdeel_part_update);
            }

            wachthond($extdebug,1, "Registration testdeel [cid: $contact_id] for $logger_title (eid: $logger_id)","UPDATED");            
        }

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT EINDE 1.2 REGISTER TESTDEEL FOR THIS EVENT", "[$logger_id / $logger_title]");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT START 1.3 REGISTER TESTLEID FOR THIS EVENT", "[$logger_id / $logger_title]");
        wachthond($extdebug,1, "#########################################################################");

        $loggerjaar     = date('Y', strtotime($logger_startdate));
        $regdate       = ($loggerjaar.'-01-01');
        $todaydatetime = date("Y-m-d");

        $ditjaarleid_eid = 271;
        $ditjaarleid_rol = "groepsleiding";

        if ($logger_type_id == 11) { $contact_id = 14432; $kamp = "KK1"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 21) { $contact_id = 14433; $kamp = "KK2"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 12) { $contact_id = 14434; $kamp = "BK1"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 22) { $contact_id = 14435; $kamp = "BK2"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 13) { $contact_id = 14436; $kamp = "TK1"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 23) { $contact_id = 14437; $kamp = "TK2"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 14) { $contact_id = 14438; $kamp = "JK1"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 24) { $contact_id = 14439; $kamp = "JK2"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }
        if ($logger_type_id == 33) { $contact_id = 14440; $kamp = "TOP"; $birthdate = "1999-04-01"; $letter = "A"; $kleur = "blauw"; }

        #wachthond($extdebug,4, "logger_type_id  :$logger_type_id");
        #wachthond($extdebug,4, "logger_id       :$entityID");
        #wachthond($extdebug,4, "contact_id     :$contact_id");
        #wachthond($extdebug,4, "groepklas      :$groepklas");
        #wachthond($extdebug,4, "regdate        :$regdate");

        if (strtotime($todaydatetime) < strtotime($logger_startdate)) {
            $params_testleid_get = [
                'select' => [
                    'id', 
                    'logger_id',
                ],
                'where' => [
                    ['logger_id',    '=', $ditjaarleid_eid],
                    ['contact_id',  '=', $contact_id],
                    ['is_test',     'IN', [TRUE, FALSE]],
                ],
            ];
            wachthond($extdebug,3, "params_testleid_get",               $params_testleid_get);
            $result_testleid_get = civicrm_api4('Participant', 'get',   $params_testleid_get);
            wachthond($extdebug,4, "result_testleid_get",               $result_testleid_get);

            $result_testleid_get_count = $result_testleid_get->count();
            wachthond($extdebug,3, "result_testleid_get_count",         $result_testleid_get_count);

            $testleid_part_id = $result_testleid_get[0]['id'];
            wachthond($extdebug,3, "testleid_part_id",                  $testleid_part_id);

            if (empty($testleid_part_id) AND !empty($contact_id)) {

                $params_testleid_create = [
                    'checkPermissions'  => FALSE,
                    'values' => [
                        'contact_id'                => $contact_id,
                        'logger_id'                  => $ditjaarleid_eid,
                        'status_id'                 => 1,           // 'Registered'
                        'register_date'             => $regdate,
                        'role_id'                   => [6],         // 'Leiding'

                        'PART_LEID.Functie'         => $ditjaarleid_rol,
                        'PART_LEID.Welk_kamp'       => $kamp,
                        'PART_INTERN.Groepje'       => $letter,
                        'PART_INTERN.Groepjeskleur' => $kleur,
                    ],
                ];
                wachthond($extdebug,3, "params_testleid_create",                    $params_testleid_create);
                if ($result_testleid_get_count == 0) {
                    $result_testleid_create = civicrm_api4('Participant', 'create', $params_testleid_create);
                    wachthond($extdebug,4, "result_testleid_create EXECUTED");
                    wachthond($extdebug,4, "result_testleid_create",                $result_testleid_create);
                    wachthond($extdebug,1, "Registration testleid [cid: $contact_id] for $logger_title (eid: $logger_id)", "CREATED");
                }
            }

            if ($testleid_part_id AND $result_testleid_get_count == 1) {

                $params_testleid_cont_update = [
                    'checkPermissions' => FALSE,
                    'values' => [
                        'birth_date'    => $birthdate,
                        'gender_id'     => 2,  // jongen                  
                    ],
                    'where' => [
                        ['id',         '=', $contact_id],                    ],
                ];

                wachthond($extdebug,3, "params_testleid_cont_update",           $params_testleid_cont_update);
                $result_testleid_cont_update = civicrm_api4('Contact','update', $params_testleid_cont_update);
                wachthond($extdebug,3, "params_testleid_cont_update EXECUTED");
                wachthond($extdebug,4, "result_testleid_cont_update",           $result_testleid_cont_update);

                $params_testleid_part_update = [
                    'checkPermissions'  => FALSE,
                    'values' => [
    //                  'id'                        => $part_id_leid,
    //                  'contact_id'                => $contact_id,
    //                  'logger_id'                  => $entityID,
                        'status_id'                 => 1,           // 'Registered'
                        'register_date'             => $regdate,
                        'role_id'                   => [6],         // 'Leiding'
                        'PART_LEID.Functie'         => $ditjaarleid_rol,
                        'PART_LEID.Welk_kamp'       => $kamp,
                        'PART_INTERN.Groepje'       => $letter,
                        'PART_INTERN.Groepjeskleur' => $kleur,
                    ],
                    'where' => [
                        ['id',      '=',  $testleid_part_id],
                        ['is_test', 'IN', [TRUE, FALSE]],
                    ],
                ];

                wachthond($extdebug,3, "params_testleid_part_update",               $params_testleid_part_update);
                $result_testleid_part_update = civicrm_api4('Participant','update', $params_testleid_part_update);
                wachthond($extdebug,3, "params_testleid_part_update EXECUTED");
                wachthond($extdebug,4, "result_testleid_part_update",               $result_testleid_part_update);
            }

            wachthond($extdebug,1, "Registration testleid [cid: $contact_id] for $logger_title (eid: $logger_id)","UPDATED");
        }

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT 1.3 EINDE REGISTER TESTLEID FOR THIS EVENT", "[$logger_id / $logger_title]");

        wachthond($extdebug,1, "#########################################################################");
        wachthond($extdebug,1, "### NL.ONVERGETELIJK.EVENT EINDE EXTENSION", "[groupID: $groupID] [op: $op]");
        wachthond($extdebug,1, "#########################################################################");

		return; //   if not, get out of here
	}
}

/**
 * Implementation of hook_civicrm_config
 */
function logger_civicrm_config(&$config) {
	_logger_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */

/*
function logger_civicrm_xmlMenu(&$files) {
	_logger_civix_civicrm_xmlMenu($files);
}
*/

/**
 * Implementation of hook_civicrm_install
 */
function logger_civicrm_install() {
	#CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, __DIR__ . '/sql/auto_install.sql');
	return _logger_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function logger_civicrm_uninstall() {
	#CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, __DIR__ . '/sql/auto_uninstall.sql');
	return _logger_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function logger_civicrm_enable() {
	return _logger_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function logger_civicrm_disable() {
	return _logger_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */

/*
function logger_civicrm_managed(&$entities) {
	return _logger_civix_civicrm_managed($entities);
}
*/