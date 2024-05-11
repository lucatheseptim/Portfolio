<?php
require_once ("/var/www/html/lectra/stage/Services/Services.php");
include_once ("/var/www/html/lectra/stage/config.inc.php");

use PHPUnit\Framework\TestCase;

class LectraAuthServiceTest extends TestCase{
    private static $reject_credentials = "aaabasd";
    private static $working_credentials;

    public static function setUpBeforeClass(): void{
        global $lectra_credentials; // from config.inc.php
        self::$working_credentials = $lectra_credentials;
    }

    public function testGetAuthWorkingCredentials(): void{
        // Not saved
        $token = LectraAuthService::getToken(
            self::$working_credentials,
            LectraPlanService::RES_PLAN,
            LectraPlanService::RES_KEY_PLAN
        );
        $this->assertNotEquals("", $token);
        $auth = LectraAuth::where("token", $token);
        $this->assertNotEmpty($auth);
        $this->assertEquals(1, sizeof($auth));
        // Saved
        $token = LectraAuthService::getToken(
            self::$working_credentials,
            LectraPlanService::RES_PLAN,
            LectraPlanService::RES_KEY_PLAN
        );
        $this->assertNotEquals("", $token);
        $auth = LectraAuth::where("token", $token);
        $this->assertNotEmpty($auth);
        $this->assertEquals(1, sizeof($auth));
    }

    public function testGetAuthNotWorkingCredentials(): void{
        $token = LectraAuthService::getToken(
            self::$reject_credentials,
            LectraPlanService::RES_PLAN,
            LectraPlanService::RES_KEY_PLAN
        );
        $this->assertEquals("", $token);
        $this->assertEmpty(LectraAuth::where("token", $token));
    }
}
